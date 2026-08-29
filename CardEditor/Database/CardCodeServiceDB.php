<?php

include_once __DIR__ . '/CardAbilityDB.php';

/**
 * Persistence and history operations used only by the hosted Card Code API.
 * Developer clients never receive database credentials; they authenticate with scoped bearer tokens.
 */
final class CardCodeServiceDB
{
    private $conn;

    public function __construct($conn)
    {
        if (!$conn) throw new RuntimeException('Database connection failed');
        $this->conn = $conn;
        CardAbilityDB::EnsureSchema($conn);
        self::EnsureSchema($conn);
    }

    public static function EnsureSchema($conn): void
    {
        static $checked = [];
        $key = spl_object_id($conn);
        if (isset($checked[$key])) return;
        $path = __DIR__ . '/../../Database/card_code_service_schema.sql';
        foreach (explode(';', (string)file_get_contents($path)) as $statement) {
            $statement = trim(preg_replace('/^\s*--.*$/m', '', $statement));
            if ($statement === '') continue;
            if (!mysqli_query($conn, $statement)) {
                throw new RuntimeException('Could not initialize Card Code service schema: ' . mysqli_error($conn));
            }
        }
        self::EnsureColumn($conn, 'card_code_api_tokens', 'token_prefix', "ALTER TABLE card_code_api_tokens ADD COLUMN token_prefix VARCHAR(16) NULL AFTER token_hash");
        self::EnsureColumn($conn, 'card_code_api_tokens', 'role', "ALTER TABLE card_code_api_tokens ADD COLUMN role VARCHAR(32) NOT NULL DEFAULT 'reader' AFTER root_name");
        self::EnsureColumn($conn, 'card_code_api_tokens', 'created_by_user_id', "ALTER TABLE card_code_api_tokens ADD COLUMN created_by_user_id INT NULL AFTER scopes");
        self::EnsureColumn($conn, 'card_code_api_tokens', 'created_by_name', "ALTER TABLE card_code_api_tokens ADD COLUMN created_by_name VARCHAR(128) NULL AFTER created_by_user_id");
        self::EnsureColumn($conn, 'card_code_api_tokens', 'expires_at', "ALTER TABLE card_code_api_tokens ADD COLUMN expires_at TIMESTAMP NULL DEFAULT NULL AFTER created_at");
        // Tokens created before roles existed retain their original permissions in the GUI.
        mysqli_query($conn, "UPDATE card_code_api_tokens SET role = CASE
            WHEN scopes LIKE '%admin%' OR scopes LIKE '%restore%' THEN 'owner'
            WHEN scopes LIKE '%checkpoint%' THEN 'maintainer'
            WHEN scopes LIKE '%write%' THEN 'developer'
            ELSE 'reader' END
            WHERE token_prefix IS NULL AND role = 'reader'");
        $checked[$key] = true;
    }

    private static function EnsureColumn($conn, string $table, string $column, string $alter): void
    {
        $result = mysqli_query($conn, "SHOW COLUMNS FROM `$table` LIKE '" . mysqli_real_escape_string($conn, $column) . "'");
        $exists = $result && mysqli_num_rows($result) > 0;
        if ($result) mysqli_free_result($result);
        if (!$exists && !mysqli_query($conn, $alter)) throw new RuntimeException("Could not add $table.$column: " . mysqli_error($conn));
    }

    public static function RoleScopes(string $role): array
    {
        $roles = [
            'reader' => ['read'],
            'developer' => ['read', 'write'],
            'maintainer' => ['read', 'write', 'checkpoint'],
            'owner' => ['read', 'write', 'checkpoint', 'restore', 'admin'],
        ];
        if (!isset($roles[$role])) throw new InvalidArgumentException('Invalid Card Code role');
        return $roles[$role];
    }

    public function createToken(string $rootName, string $name, string $role, int $expiresDays, ?int $actorUserId, ?string $actorName): array
    {
        $rootName = self::NormalizeRoot($rootName);
        $name = trim($name);
        if ($name === '' || strlen($name) > 128) throw new InvalidArgumentException('Token name must be between 1 and 128 characters');
        $scopes = self::RoleScopes($role);
        if ($expiresDays < 1 || $expiresDays > 365) throw new InvalidArgumentException('Token expiration must be between 1 and 365 days');
        $plain = 'tcc_' . rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
        $hash = hash('sha256', $plain, true);
        $prefix = substr($plain, 0, 12);
        $scopeText = implode(',', $scopes);
        // Let MySQL calculate expiration so it uses the same clock/time zone as authentication.
        $stmt = mysqli_prepare($this->conn, 'INSERT INTO card_code_api_tokens (token_name, token_hash, token_prefix, root_name, role, scopes, created_by_user_id, created_by_name, expires_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, DATE_ADD(CURRENT_TIMESTAMP, INTERVAL ? DAY))');
        mysqli_stmt_bind_param($stmt, 'ssssssisi', $name, $hash, $prefix, $rootName, $role, $scopeText, $actorUserId, $actorName, $expiresDays);
        if (!mysqli_stmt_execute($stmt)) throw new RuntimeException('Could not create Card Code token');
        $id = (int)mysqli_insert_id($this->conn);
        mysqli_stmt_close($stmt);
        $expiresResult = mysqli_query($this->conn, 'SELECT expires_at FROM card_code_api_tokens WHERE id = ' . $id);
        $expiresRow = $expiresResult ? mysqli_fetch_assoc($expiresResult) : null;
        $expiresAt = (string)($expiresRow['expires_at'] ?? '');
        if ($expiresResult) mysqli_free_result($expiresResult);
        $this->audit($rootName, $id, 'created', $actorUserId, $actorName, ['name' => $name, 'role' => $role, 'expiresAt' => $expiresAt]);
        return ['id' => $id, 'token' => $plain, 'prefix' => $prefix, 'name' => $name, 'root' => $rootName, 'role' => $role, 'scopes' => $scopes, 'expiresAt' => $expiresAt];
    }

    public function listTokens(string $rootName): array
    {
        $rootName = self::NormalizeRoot($rootName);
        $stmt = mysqli_prepare($this->conn, "SELECT id, token_name, token_prefix, root_name, role, scopes, created_by_user_id, created_by_name, created_at, expires_at, last_used_at, revoked_at,
            CASE WHEN revoked_at IS NOT NULL THEN 'revoked' WHEN expires_at IS NOT NULL AND expires_at <= CURRENT_TIMESTAMP THEN 'expired' ELSE 'active' END AS status
            FROM card_code_api_tokens WHERE root_name = ? ORDER BY created_at DESC, id DESC");
        mysqli_stmt_bind_param($stmt, 's', $rootName);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $rows = [];
        while ($result && ($row = mysqli_fetch_assoc($result))) {
            $row['id'] = (int)$row['id'];
            $row['created_by_user_id'] = $row['created_by_user_id'] === null ? null : (int)$row['created_by_user_id'];
            $rows[] = $row;
        }
        mysqli_stmt_close($stmt);
        return $rows;
    }

    public function revokeToken(string $rootName, int $tokenId, ?int $actorUserId, ?string $actorName, string $action = 'revoked'): bool
    {
        $rootName = self::NormalizeRoot($rootName);
        $stmt = mysqli_prepare($this->conn, 'UPDATE card_code_api_tokens SET revoked_at = CURRENT_TIMESTAMP WHERE id = ? AND root_name = ? AND revoked_at IS NULL');
        mysqli_stmt_bind_param($stmt, 'is', $tokenId, $rootName);
        mysqli_stmt_execute($stmt);
        $changed = mysqli_stmt_affected_rows($stmt) === 1;
        mysqli_stmt_close($stmt);
        if ($changed) $this->audit($rootName, $tokenId, $action, $actorUserId, $actorName);
        return $changed;
    }

    public function rotateToken(string $rootName, int $tokenId, int $expiresDays, ?int $actorUserId, ?string $actorName): array
    {
        $rootName = self::NormalizeRoot($rootName);
        $stmt = mysqli_prepare($this->conn, 'SELECT token_name, role FROM card_code_api_tokens WHERE id = ? AND root_name = ? AND revoked_at IS NULL LIMIT 1');
        mysqli_stmt_bind_param($stmt, 'is', $tokenId, $rootName);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $old = $result ? mysqli_fetch_assoc($result) : null;
        mysqli_stmt_close($stmt);
        if (!$old) throw new InvalidArgumentException('Active token not found');
        mysqli_begin_transaction($this->conn);
        try {
            if (!$this->revokeToken($rootName, $tokenId, $actorUserId, $actorName, 'rotated')) throw new RuntimeException('Could not revoke old token');
            $created = $this->createToken($rootName, (string)$old['token_name'], (string)$old['role'], $expiresDays, $actorUserId, $actorName);
            mysqli_commit($this->conn);
            return $created;
        } catch (Throwable $error) {
            mysqli_rollback($this->conn);
            throw $error;
        }
    }

    private function audit(string $rootName, ?int $tokenId, string $action, ?int $actorUserId, ?string $actorName, ?array $metadata = null): void
    {
        $json = $metadata === null ? null : json_encode($metadata, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $stmt = mysqli_prepare($this->conn, 'INSERT INTO card_code_token_audit (root_name, token_id, action, actor_user_id, actor_name, metadata_json) VALUES (?, ?, ?, ?, ?, ?)');
        mysqli_stmt_bind_param($stmt, 'sisiss', $rootName, $tokenId, $action, $actorUserId, $actorName, $json);
        if (!mysqli_stmt_execute($stmt)) throw new RuntimeException('Could not write token audit record');
        mysqli_stmt_close($stmt);
    }

    public static function NormalizeRoot($rootName): string
    {
        $rootName = trim((string)$rootName);
        if (!preg_match('/^[A-Za-z0-9_-]{1,64}$/', $rootName)) {
            throw new InvalidArgumentException('Invalid Card Code workspace');
        }
        return $rootName;
    }

    public function authenticate(string $plainToken, string $rootName, string $requiredScope): array
    {
        if ($plainToken === '') throw new RuntimeException('Missing bearer token');
        $rootName = self::NormalizeRoot($rootName);
        $hash = hash('sha256', $plainToken, true);
        $stmt = mysqli_prepare($this->conn, "
            SELECT id, token_name, root_name, role, scopes, expires_at
            FROM card_code_api_tokens
            WHERE token_hash = ? AND revoked_at IS NULL AND (expires_at IS NULL OR expires_at > CURRENT_TIMESTAMP)
            LIMIT 1
        ");
        mysqli_stmt_bind_param($stmt, 's', $hash);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $token = $result ? mysqli_fetch_assoc($result) : null;
        mysqli_stmt_close($stmt);
        if (!$token || ($token['root_name'] !== null && $token['root_name'] !== $rootName)) {
            throw new RuntimeException('Invalid token for this Card Code workspace');
        }
        $scopes = array_filter(array_map('trim', explode(',', (string)$token['scopes'])));
        if (!in_array($requiredScope, $scopes, true) && !in_array('admin', $scopes, true)) {
            throw new RuntimeException('Token does not grant the required ' . $requiredScope . ' scope');
        }
        $touch = mysqli_prepare($this->conn, 'UPDATE card_code_api_tokens SET last_used_at = CURRENT_TIMESTAMP WHERE id = ?');
        $id = (int)$token['id'];
        mysqli_stmt_bind_param($touch, 'i', $id);
        mysqli_stmt_execute($touch);
        mysqli_stmt_close($touch);
        return $token;
    }

    public function rows(string $rootName, ?string $cardId = null): array
    {
        $rootName = self::NormalizeRoot($rootName);
        $sql = "SELECT id, root_name, card_id, macro_name, ability_type, ability_code, prereq_code,
                       listener_zones, ability_name, is_implemented, created_at, updated_at
                FROM card_abilities WHERE root_name = ?";
        if ($cardId !== null) $sql .= ' AND card_id = ?';
        $sql .= ' ORDER BY card_id ASC, created_at ASC, id ASC';
        $stmt = mysqli_prepare($this->conn, $sql);
        if ($cardId === null) mysqli_stmt_bind_param($stmt, 's', $rootName);
        else mysqli_stmt_bind_param($stmt, 'ss', $rootName, $cardId);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $rows = [];
        while ($result && ($row = mysqli_fetch_assoc($result))) {
            $row['id'] = (int)$row['id'];
            $row['is_implemented'] = (int)$row['is_implemented'];
            $rows[] = $row;
        }
        mysqli_stmt_close($stmt);
        return $rows;
    }

    public static function RevisionForRows(array $rows): string
    {
        // Timestamps are deliberately excluded. Re-saving identical authored content should not
        // manufacture a conflict or make an unchanged daily checkpoint look different.
        $canonical = [];
        foreach ($rows as $row) {
            $canonical[] = [
                'card_id' => (string)($row['card_id'] ?? ''),
                'macro_name' => (string)($row['macro_name'] ?? ''),
                'ability_type' => (string)($row['ability_type'] ?? 'macro'),
                'ability_code' => (string)($row['ability_code'] ?? ''),
                'prereq_code' => $row['prereq_code'] ?? null,
                'listener_zones' => $row['listener_zones'] ?? null,
                'ability_name' => $row['ability_name'] ?? null,
                'is_implemented' => (int)($row['is_implemented'] ?? 0),
            ];
        }
        return hash('sha256', json_encode($canonical, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    }

    public function checkpoint(string $rootName, ?string $actor = null, ?string $date = null): array
    {
        $rootName = self::NormalizeRoot($rootName);
        $date = $date ?: gmdate('Y-m-d');
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) throw new InvalidArgumentException('Invalid checkpoint date');
        // One immutable daily checkpoint means writes throughout a busy day do not continually
        // rewrite history. The first scheduled run or first mutation of the day wins.
        $today = mysqli_prepare($this->conn, 'SELECT id, payload_sha256, ability_count FROM card_code_checkpoints WHERE root_name = ? AND checkpoint_date = ? LIMIT 1');
        mysqli_stmt_bind_param($today, 'ss', $rootName, $date);
        mysqli_stmt_execute($today);
        $todayResult = mysqli_stmt_get_result($today);
        $todayRow = $todayResult ? mysqli_fetch_assoc($todayResult) : null;
        mysqli_stmt_close($today);
        if ($todayRow) {
            return ['created' => false, 'unchanged' => false, 'id' => (int)$todayRow['id'], 'date' => $date, 'abilityCount' => (int)$todayRow['ability_count'], 'checksum' => (string)$todayRow['payload_sha256']];
        }
        $rows = $this->rows($rootName);
        $json = json_encode(['format' => 'tcg-card-code-checkpoint-1', 'root' => $rootName, 'abilities' => $rows], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        if ($json === false) throw new RuntimeException('Could not encode checkpoint');
        $checksum = hash('sha256', $json);

        $latest = mysqli_prepare($this->conn, 'SELECT id, payload_sha256 FROM card_code_checkpoints WHERE root_name = ? ORDER BY checkpoint_date DESC, id DESC LIMIT 1');
        mysqli_stmt_bind_param($latest, 's', $rootName);
        mysqli_stmt_execute($latest);
        $latestResult = mysqli_stmt_get_result($latest);
        $latestRow = $latestResult ? mysqli_fetch_assoc($latestResult) : null;
        mysqli_stmt_close($latest);
        if ($latestRow && hash_equals((string)$latestRow['payload_sha256'], $checksum)) {
            return ['created' => false, 'unchanged' => true, 'id' => (int)$latestRow['id'], 'date' => $date, 'abilityCount' => count($rows), 'checksum' => $checksum];
        }

        $payload = gzencode($json, 6);
        if ($payload === false) throw new RuntimeException('Could not compress checkpoint');
        $count = count($rows);
        $stmt = mysqli_prepare($this->conn, "
            INSERT INTO card_code_checkpoints (root_name, checkpoint_date, payload, payload_sha256, ability_count, created_by)
            VALUES (?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE id = LAST_INSERT_ID(id)
        ");
        mysqli_stmt_bind_param($stmt, 'ssssis', $rootName, $date, $payload, $checksum, $count, $actor);
        if (!mysqli_stmt_execute($stmt)) throw new RuntimeException('Could not create checkpoint');
        $id = (int)mysqli_insert_id($this->conn);
        mysqli_stmt_close($stmt);
        if ($id === 0) {
            $lookup = mysqli_prepare($this->conn, 'SELECT id FROM card_code_checkpoints WHERE root_name = ? AND checkpoint_date = ?');
            mysqli_stmt_bind_param($lookup, 'ss', $rootName, $date);
            mysqli_stmt_execute($lookup);
            $lookupResult = mysqli_stmt_get_result($lookup);
            $id = (int)(mysqli_fetch_assoc($lookupResult)['id'] ?? 0);
            mysqli_stmt_close($lookup);
        }
        return ['created' => true, 'unchanged' => false, 'id' => $id, 'date' => $date, 'abilityCount' => $count, 'checksum' => $checksum];
    }

    public function listCheckpoints(string $rootName): array
    {
        $rootName = self::NormalizeRoot($rootName);
        $stmt = mysqli_prepare($this->conn, 'SELECT id, checkpoint_date, payload_sha256, ability_count, created_by, created_at FROM card_code_checkpoints WHERE root_name = ? ORDER BY checkpoint_date DESC, id DESC');
        mysqli_stmt_bind_param($stmt, 's', $rootName);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $rows = [];
        while ($result && ($row = mysqli_fetch_assoc($result))) {
            $row['id'] = (int)$row['id'];
            $row['ability_count'] = (int)$row['ability_count'];
            $rows[] = $row;
        }
        mysqli_stmt_close($stmt);
        return $rows;
    }

    public function replaceCard(string $rootName, string $cardId, array $abilities, bool $cardImplemented, ?string $baseRevision): array
    {
        $rootName = self::NormalizeRoot($rootName);
        if ($cardId === '' || strlen($cardId) > 128) throw new InvalidArgumentException('Invalid card ID');
        mysqli_begin_transaction($this->conn);
        try {
            // Lock every current row for this card before comparing its content hash.
            $lock = mysqli_prepare($this->conn, 'SELECT id FROM card_abilities WHERE root_name = ? AND card_id = ? FOR UPDATE');
            mysqli_stmt_bind_param($lock, 'ss', $rootName, $cardId);
            mysqli_stmt_execute($lock);
            mysqli_stmt_store_result($lock);
            mysqli_stmt_close($lock);
            $current = $this->rows($rootName, $cardId);
            $currentRevision = self::RevisionForRows($current);
            if ($baseRevision !== null && $baseRevision !== '' && !hash_equals($currentRevision, $baseRevision)) {
                mysqli_rollback($this->conn);
                return ['conflict' => true, 'revision' => $currentRevision, 'abilities' => $current];
            }

            $delete = mysqli_prepare($this->conn, 'DELETE FROM card_abilities WHERE root_name = ? AND card_id = ?');
            mysqli_stmt_bind_param($delete, 'ss', $rootName, $cardId);
            mysqli_stmt_execute($delete);
            mysqli_stmt_close($delete);
            $insert = mysqli_prepare($this->conn, "INSERT INTO card_abilities
                (root_name, card_id, macro_name, ability_type, ability_code, prereq_code, listener_zones, ability_name, is_implemented)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            foreach ($abilities as $ability) {
                $macro = trim((string)($ability['macroName'] ?? $ability['macro_name'] ?? ''));
                $code = (string)($ability['abilityCode'] ?? $ability['ability_code'] ?? '');
                if ($macro === '' || $code === '') throw new InvalidArgumentException('Every ability requires a macro and code');
                $type = (($ability['abilityType'] ?? $ability['ability_type'] ?? 'macro') === 'listener') ? 'listener' : 'macro';
                $prereq = $ability['prereqCode'] ?? $ability['prereq_code'] ?? null;
                $zonesValue = $ability['listenerZones'] ?? $ability['listener_zones'] ?? null;
                $zones = is_array($zonesValue) ? implode(',', array_filter(array_map('trim', $zonesValue))) : trim((string)$zonesValue);
                if ($type !== 'listener') $zones = null;
                elseif ($zones === '') throw new InvalidArgumentException('Listener abilities require at least one active zone');
                $name = $ability['abilityName'] ?? $ability['ability_name'] ?? null;
                $implemented = !empty($ability['isImplemented'] ?? $ability['is_implemented'] ?? false) ? 1 : 0;
                mysqli_stmt_bind_param($insert, 'ssssssssi', $rootName, $cardId, $macro, $type, $code, $prereq, $zones, $name, $implemented);
                if (!mysqli_stmt_execute($insert)) throw new RuntimeException('Could not save ability');
            }
            if ($cardImplemented && count($abilities) === 0) {
                $macro = ''; $type = 'macro'; $code = ''; $prereq = null; $zones = null; $name = '[Card Implemented]'; $implemented = 1;
                mysqli_stmt_bind_param($insert, 'ssssssssi', $rootName, $cardId, $macro, $type, $code, $prereq, $zones, $name, $implemented);
                if (!mysqli_stmt_execute($insert)) throw new RuntimeException('Could not save implementation marker');
            }
            mysqli_stmt_close($insert);
            mysqli_commit($this->conn);
            $saved = $this->rows($rootName, $cardId);
            return ['conflict' => false, 'revision' => self::RevisionForRows($saved), 'abilities' => $saved];
        } catch (Throwable $error) {
            mysqli_rollback($this->conn);
            throw $error;
        }
    }

    public function restore(string $rootName, int $checkpointId, ?string $actor = null): array
    {
        $rootName = self::NormalizeRoot($rootName);
        $this->checkpoint($rootName, $actor);
        $stmt = mysqli_prepare($this->conn, 'SELECT payload FROM card_code_checkpoints WHERE id = ? AND root_name = ?');
        mysqli_stmt_bind_param($stmt, 'is', $checkpointId, $rootName);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $row = $result ? mysqli_fetch_assoc($result) : null;
        mysqli_stmt_close($stmt);
        if (!$row) throw new InvalidArgumentException('Checkpoint not found');
        $json = gzdecode($row['payload']);
        $decoded = $json === false ? null : json_decode($json, true);
        $abilities = is_array($decoded) ? ($decoded['abilities'] ?? null) : null;
        if (!is_array($abilities)) throw new RuntimeException('Checkpoint payload is invalid');

        mysqli_begin_transaction($this->conn);
        try {
            $delete = mysqli_prepare($this->conn, 'DELETE FROM card_abilities WHERE root_name = ?');
            mysqli_stmt_bind_param($delete, 's', $rootName);
            mysqli_stmt_execute($delete);
            mysqli_stmt_close($delete);
            $insert = mysqli_prepare($this->conn, "INSERT INTO card_abilities
                (root_name, card_id, macro_name, ability_type, ability_code, prereq_code, listener_zones, ability_name, is_implemented, created_at, updated_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            foreach ($abilities as $ability) {
                $cardId = (string)$ability['card_id']; $macro = (string)$ability['macro_name'];
                $type = (string)$ability['ability_type']; $code = (string)$ability['ability_code'];
                $prereq = $ability['prereq_code']; $zones = $ability['listener_zones']; $name = $ability['ability_name'];
                $implemented = (int)$ability['is_implemented']; $created = (string)$ability['created_at']; $updated = (string)$ability['updated_at'];
                mysqli_stmt_bind_param($insert, 'ssssssssiss', $rootName, $cardId, $macro, $type, $code, $prereq, $zones, $name, $implemented, $created, $updated);
                if (!mysqli_stmt_execute($insert)) throw new RuntimeException('Could not restore checkpoint row');
            }
            mysqli_stmt_close($insert);
            mysqli_commit($this->conn);
        } catch (Throwable $error) {
            mysqli_rollback($this->conn);
            throw $error;
        }
        return ['restored' => true, 'checkpointId' => $checkpointId, 'abilityCount' => count($abilities), 'revision' => self::RevisionForRows($this->rows($rootName))];
    }
}

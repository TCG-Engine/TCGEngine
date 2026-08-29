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
        $checked[$key] = true;
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
            SELECT id, token_name, root_name, scopes
            FROM card_code_api_tokens
            WHERE token_hash = ? AND revoked_at IS NULL
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

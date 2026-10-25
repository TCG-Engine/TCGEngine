<?php

// Separate from Card Code tokens: these delegate an editor user's existing permissions.
class CardEditingTokens {
    private $conn;

    public function __construct($conn) {
        $this->conn = $conn;
        if (!mysqli_query($conn, "CREATE TABLE IF NOT EXISTS ce_mcp_tokens (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            user_id BIGINT NOT NULL,
            name VARCHAR(100) NOT NULL,
            token_hash CHAR(64) NOT NULL UNIQUE,
            scope VARCHAR(10) NOT NULL,
            created_at DATETIME NOT NULL,
            expires_at DATETIME NOT NULL,
            revoked_at DATETIME NULL,
            last_used_at DATETIME NULL,
            KEY idx_ce_mcp_user (user_id)
        )")) throw new RuntimeException('Could not initialize card editing tokens');
    }

    private function query($sql, $types, $params) {
        $stmt = mysqli_prepare($this->conn, $sql);
        if (!$stmt) throw new RuntimeException('Token storage unavailable');
        if (!mysqli_stmt_bind_param($stmt, $types, ...$params) || !mysqli_stmt_execute($stmt)) {
            mysqli_stmt_close($stmt);
            throw new RuntimeException('Token storage unavailable');
        }
        return $stmt;
    }

    public function create(int $userId, string $name, string $scope, int $days): array {
        $name = trim($name);
        if ($userId <= 0 || $name === '' || strlen($name) > 100 || !in_array($scope, ['read', 'write'], true) || $days < 1 || $days > 365) {
            throw new InvalidArgumentException('Use a name, read/write scope, and expiration of 1–365 days');
        }
        $secret = 'tce_' . bin2hex(random_bytes(32));
        $stmt = $this->query('SELECT COUNT(*) AS count FROM ce_mcp_tokens WHERE user_id = ? AND created_at > DATE_SUB(UTC_TIMESTAMP(), INTERVAL 1 HOUR)', 'i', [$userId]);
        $count = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
        mysqli_stmt_close($stmt);
        if ((int)$count['count'] >= 20) throw new InvalidArgumentException('Token creation limit reached; try again in an hour');
        $expires = gmdate('Y-m-d H:i:s', time() + $days * 86400);
        $stmt = $this->query('INSERT INTO ce_mcp_tokens (user_id, name, token_hash, scope, created_at, expires_at) VALUES (?, ?, ?, ?, UTC_TIMESTAMP(), ?)',
            'issss', [$userId, $name, hash('sha256', $secret), $scope, $expires]);
        mysqli_stmt_close($stmt);
        return ['id' => (int)mysqli_insert_id($this->conn), 'token' => $secret, 'expiresAt' => $expires];
    }

    public function authenticate(string $secret): array {
        if (!preg_match('/^tce_[a-f0-9]{64}$/D', $secret)) throw new UnexpectedValueException('Invalid card editing token');
        // Join users so deleting an account invalidates all its tokens immediately.
        $stmt = $this->query('SELECT t.id, t.user_id, t.scope FROM ce_mcp_tokens t JOIN users u ON u.usersId = t.user_id WHERE t.token_hash = ? AND t.revoked_at IS NULL AND t.expires_at > UTC_TIMESTAMP()',
            's', [hash('sha256', $secret)]);
        $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
        mysqli_stmt_close($stmt);
        if (!$row) throw new UnexpectedValueException('Invalid or expired card editing token');
        $stmt = $this->query('UPDATE ce_mcp_tokens SET last_used_at = UTC_TIMESTAMP() WHERE id = ?', 'i', [(int)$row['id']]);
        mysqli_stmt_close($stmt);
        return ['userId' => (int)$row['user_id'], 'scope' => $row['scope']];
    }

    public function listForUser(int $userId): array {
        $stmt = $this->query('SELECT id, name, scope, created_at, expires_at, revoked_at, last_used_at FROM ce_mcp_tokens WHERE user_id = ? ORDER BY id DESC', 'i', [$userId]);
        $rows = mysqli_fetch_all(mysqli_stmt_get_result($stmt), MYSQLI_ASSOC);
        mysqli_stmt_close($stmt);
        return $rows;
    }

    public function revoke(int $userId, int $id): void {
        $stmt = $this->query('UPDATE ce_mcp_tokens SET revoked_at = UTC_TIMESTAMP() WHERE id = ? AND user_id = ? AND revoked_at IS NULL', 'ii', [$id, $userId]);
        mysqli_stmt_close($stmt);
    }
}

function CardEditingRequestToken(array $server): string {
    $custom = trim((string)($server['HTTP_X_CARD_EDITOR_TOKEN'] ?? ''));
    if ($custom !== '') return $custom;
    $header = trim((string)($server['HTTP_AUTHORIZATION'] ?? $server['REDIRECT_HTTP_AUTHORIZATION'] ?? ''));
    return preg_match('/^Bearer\s+(\S+)$/iD', $header, $m) ? $m[1] : '';
}

function CardEditingSecureRequest(array $server): bool {
    $https = strtolower((string)($server['HTTPS'] ?? ''));
    if ($https !== '' && $https !== 'off') return true;
    return in_array($server['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1'], true)
        && preg_match('/^(localhost|127\.0\.0\.1|\[::1\])(:\d+)?$/iD', (string)($server['HTTP_HOST'] ?? '')) === 1;
}

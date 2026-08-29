<?php

// Provision or revoke hosted Card Code API tokens. Tokens are displayed once and stored only as
// SHA-256 hashes. Run on the hosted server, never on a developer checkout pointed at that server.

if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
include_once __DIR__ . '/../Database/ConnectionManager.php';
include_once __DIR__ . '/../CardEditor/Database/CardCodeServiceDB.php';

$args = [];
foreach (array_slice($argv, 1) as $arg) {
    if (preg_match('/^--([^=]+)=(.*)$/', $arg, $matches)) $args[$matches[1]] = $matches[2];
    elseif (preg_match('/^--(.+)$/', $arg, $matches)) $args[$matches[1]] = true;
}

$conn = GetLocalMySQLConnection();
$service = new CardCodeServiceDB($conn);
if (!empty($args['revoke'])) {
    $id = (int)$args['revoke'];
    $stmt = mysqli_prepare($conn, 'UPDATE card_code_api_tokens SET revoked_at = CURRENT_TIMESTAMP WHERE id = ? AND revoked_at IS NULL');
    mysqli_stmt_bind_param($stmt, 'i', $id);
    mysqli_stmt_execute($stmt);
    echo mysqli_stmt_affected_rows($stmt) === 1 ? "Revoked token $id\n" : "No active token $id\n";
    mysqli_stmt_close($stmt); mysqli_close($conn); exit;
}

$root = CardCodeServiceDB::NormalizeRoot((string)($args['root'] ?? ''));
$name = trim((string)($args['name'] ?? ''));
if ($name === '' || strlen($name) > 128) throw new InvalidArgumentException('Pass --name=<developer-or-service>');
$scopes = array_values(array_unique(array_filter(array_map('trim', explode(',', (string)($args['scopes'] ?? 'read,write'))))));
$allowed = ['read', 'write', 'restore', 'admin'];
foreach ($scopes as $scope) if (!in_array($scope, $allowed, true)) throw new InvalidArgumentException('Invalid scope: ' . $scope);
$plain = 'tcc_' . rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
$hash = hash('sha256', $plain, true);
$scopeText = implode(',', $scopes);
$stmt = mysqli_prepare($conn, 'INSERT INTO card_code_api_tokens (token_name, token_hash, root_name, scopes) VALUES (?, ?, ?, ?)');
mysqli_stmt_bind_param($stmt, 'ssss', $name, $hash, $root, $scopeText);
if (!mysqli_stmt_execute($stmt)) throw new RuntimeException('Could not create token');
$id = (int)mysqli_insert_id($conn);
mysqli_stmt_close($stmt); mysqli_close($conn);

echo "Created Card Code token $id for $root ($scopeText).\n";
echo "Store this value now; it cannot be recovered:\n$plain\n";

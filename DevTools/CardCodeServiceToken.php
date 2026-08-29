<?php

// Emergency CLI fallback for hosted Card Code token administration. Normal administration is
// available in Generator Workspace. Secrets are displayed once and stored only as SHA-256 hashes.

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
    $root = CardCodeServiceDB::NormalizeRoot((string)($args['root'] ?? ''));
    $changed = $service->revokeToken($root, $id, null, 'cli-admin');
    echo $changed ? "Revoked token $id\n" : "No active token $id for $root\n";
    mysqli_close($conn); exit;
}

$root = CardCodeServiceDB::NormalizeRoot((string)($args['root'] ?? ''));
$name = trim((string)($args['name'] ?? ''));
if ($name === '' || strlen($name) > 128) throw new InvalidArgumentException('Pass --name=<developer-or-service>');
$role = strtolower(trim((string)($args['role'] ?? 'developer')));
$expiresDays = (int)($args['expires-days'] ?? 90);
$created = $service->createToken($root, $name, $role, $expiresDays, null, 'cli-admin');
mysqli_close($conn);

echo "Created Card Code token {$created['id']} for $root ({$created['role']}; expires {$created['expiresAt']} UTC).\n";
echo "Store this value now; it cannot be recovered:\n{$created['token']}\n";

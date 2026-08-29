<?php

// Integration guard for hosted Card Code auth, daily checkpoint immutability, optimistic
// concurrency, and restore. Uses a throwaway database and never touches real card data.

error_reporting(E_ALL & ~E_DEPRECATED); ini_set('display_errors', 1);
chdir(dirname(__DIR__, 2));
include_once './CardEditor/Database/CardCodeServiceDB.php';
include_once './CardEditor/Database/CardAbilityRepository.php';

$fails = 0;
$check = function ($ok, $message) use (&$fails) { echo ($ok ? 'PASS' : 'FAIL') . ": $message\n"; if (!$ok) ++$fails; };
$host = getenv('MYSQL_SERVER_NAME') ?: 'localhost';
$user = getenv('MYSQL_SERVER_USER_NAME') ?: 'root';
$password = getenv('MYSQL_ROOT_PASSWORD') ?: '';
$database = 'tcgengine_card_code_test_' . getmypid();
$server = mysqli_connect($host, $user, $password);
if (!$server) { echo "FAIL: could not reach MySQL at $host\n"; exit(1); }
mysqli_query($server, "DROP DATABASE IF EXISTS `$database`");
if (!mysqli_query($server, "CREATE DATABASE `$database`")) { echo "FAIL: could not create scratch database\n"; exit(1); }

try {
    $conn = mysqli_connect($host, $user, $password, $database);
    $service = new CardCodeServiceDB($conn);
    $plain = 'test-token'; $hash = hash('sha256', $plain, true); $name = 'test-developer'; $root = 'TestSim'; $scopes = 'read,write,restore';
    $token = mysqli_prepare($conn, 'INSERT INTO card_code_api_tokens (token_name, token_hash, root_name, scopes) VALUES (?, ?, ?, ?)');
    mysqli_stmt_bind_param($token, 'ssss', $name, $hash, $root, $scopes); mysqli_stmt_execute($token); mysqli_stmt_close($token);
    $authenticated = $service->authenticate($plain, $root, 'write');
    $check(($authenticated['token_name'] ?? '') === $name, 'root-scoped bearer token authenticates');
    try { $service->authenticate($plain, 'OtherSim', 'read'); $wrongRootRejected = false; } catch (RuntimeException $e) { $wrongRootRejected = true; }
    $check($wrongRootRejected, 'token cannot cross workspaces');

    mysqli_query($conn, "INSERT INTO card_abilities (root_name, card_id, macro_name, ability_code, is_implemented) VALUES ('TestSim','TST_001','Enter','return 1;',1)");
    $original = $service->rows($root, 'TST_001');
    $baseRevision = CardCodeServiceDB::RevisionForRows($original);
    $first = $service->checkpoint($root, $name, '2026-08-26');
    $check($first['created'] === true && $first['abilityCount'] === 1, 'first daily checkpoint is created');

    $saved = $service->replaceCard($root, 'TST_001', [[
        'macroName' => 'Enter', 'abilityCode' => 'return 2;', 'isImplemented' => true,
    ]], false, $baseRevision);
    $check(empty($saved['conflict']) && $saved['revision'] !== $baseRevision, 'fresh revision saves atomically');
    $sameDay = $service->checkpoint($root, $name, '2026-08-26');
    $check($sameDay['created'] === false && $sameDay['id'] === $first['id'] && $sameDay['checksum'] === $first['checksum'], 'later changes do not rewrite the daily checkpoint');

    $conflict = $service->replaceCard($root, 'TST_001', [[
        'macroName' => 'Enter', 'abilityCode' => 'return 3;', 'isImplemented' => true,
    ]], false, $baseRevision);
    $check(!empty($conflict['conflict']), 'stale revision is rejected without overwriting');

    $second = $service->checkpoint($root, $name, '2026-08-27');
    $check($second['created'] === true && $second['checksum'] !== $first['checksum'], 'next changed day receives a new checkpoint');
    $restored = $service->restore($root, (int)$first['id'], $name);
    $rows = $service->rows($root, 'TST_001');
    $check(!empty($restored['restored']) && ($rows[0]['ability_code'] ?? '') === 'return 1;', 'restore replaces current state with checkpoint contents');

    $local = new LocalCardAbilityRepository($conn);
    $localRevision = $local->revisionForCard($root, 'TST_001');
    $localSaved = $local->replaceCardAbilities($root, 'TST_001', [[
        'macroName' => 'Enter', 'abilityCode' => 'return 4;', 'isImplemented' => true,
    ]], false, $localRevision);
    $check(($localSaved['abilities'][0]['ability_code'] ?? '') === 'return 4;', 'local repository remains the default-compatible save backend');
    $local->close();
} finally {
    mysqli_query($server, "DROP DATABASE IF EXISTS `$database`");
    mysqli_close($server);
}

echo $fails === 0 ? "\nALL PASS\n" : "\n$fails FAILED\n";
exit($fails === 0 ? 0 : 1);

<?php
// TDD guard for card_abilities schema bootstrap.
//
// CardAbilityDB used to only ALTER columns, never CREATE the table, so card_abilities existed only
// where Database/*.sql ran as a docker initdb hook — i.e. never on the LAMPP prod box, where every
// query against it fataled with "Table 'hellbreaksim.card_abilities' doesn't exist".
//
// Runs entirely inside a throwaway database so it never touches real card data.
//
//   docker exec -w /var/www/html/TCGEngine otmtcge-hellbreaksim-web-server-1 \
//     php DevTools/tdd-regression/test_card_abilities_schema_bootstrap.php
error_reporting(E_ALL & ~E_DEPRECATED); ini_set('display_errors', 1);
chdir('/var/www/html/TCGEngine');
include_once './CardEditor/Database/CardAbilityDB.php';

$fails = 0;
$check = function ($ok, $msg) use (&$fails) { echo ($ok ? 'PASS' : 'FAIL') . ": $msg\n"; if (!$ok) $fails++; };

$hostname = getenv('MYSQL_SERVER_NAME') ?: 'localhost';
$username = getenv('MYSQL_SERVER_USER_NAME') ?: 'root';
$password = getenv('MYSQL_ROOT_PASSWORD') ?: '';
$scratchDatabase = 'tcgengine_schema_test_' . getmypid();

$server = mysqli_connect($hostname, $username, $password);
if (!$server) { echo "FAIL: could not reach MySQL at $hostname\n"; exit(1); }
mysqli_query($server, "DROP DATABASE IF EXISTS `$scratchDatabase`");
if (!mysqli_query($server, "CREATE DATABASE `$scratchDatabase`")) {
    echo 'FAIL: could not create scratch database: ' . mysqli_error($server) . "\n";
    exit(1);
}

$tableExists = function ($conn) {
    $result = mysqli_query($conn, "SHOW TABLES LIKE 'card_abilities'");
    $exists = $result && mysqli_num_rows($result) > 0;
    if ($result) mysqli_free_result($result);
    return $exists;
};
$columnType = function ($conn, $column) {
    $result = mysqli_query($conn, "SHOW COLUMNS FROM card_abilities LIKE '$column'");
    $row = $result ? mysqli_fetch_assoc($result) : null;
    if ($result) mysqli_free_result($result);
    return $row ? strtolower((string)$row['Type']) : '';
};

try {
    $conn = mysqli_connect($hostname, $username, $password, $scratchDatabase);
    $check(!$tableExists($conn), 'scratch database starts without card_abilities');

    // ── creates the table from nothing ───────────────────────────────────────
    CardAbilityDB::EnsureSchema($conn);
    $check($tableExists($conn), 'EnsureSchema creates card_abilities on a fresh database');

    $expectedColumns = ['id', 'root_name', 'card_id', 'macro_name', 'ability_type', 'ability_code',
        'prereq_code', 'listener_zones', 'ability_name', 'created_at', 'updated_at', 'is_implemented'];
    $missing = [];
    foreach ($expectedColumns as $column) {
        if ($columnType($conn, $column) === '') $missing[] = $column;
    }
    $check($missing === [], 'every expected column is present (missing: ' . implode(',', $missing) . ')');
    $check(strpos($columnType($conn, 'card_id'), 'varchar(128)') === 0, 'card_id is varchar(128)');

    // ── idempotent ───────────────────────────────────────────────────────────
    // The button can be pressed twice, and every CardAbilityDB construction calls this.
    CardAbilityDB::EnsureSchema($conn);
    $check($tableExists($conn), 'a second EnsureSchema call is a harmless no-op');

    // Real rows must survive a re-run — that is the whole risk of a "set up database" button.
    mysqli_query($conn, "INSERT INTO card_abilities (root_name, card_id, macro_name, ability_code) VALUES ('HellbreakSim','DOT_001','TestMacro','return 1;')");
    CardAbilityDB::EnsureSchema($conn);
    $result = mysqli_query($conn, "SELECT COUNT(*) AS total FROM card_abilities");
    $total = $result ? (int)mysqli_fetch_assoc($result)['total'] : 0;
    $check($total === 1, 'existing rows survive a re-run (got ' . $total . ')');

    // ── migrates a legacy table ──────────────────────────────────────────────
    // A pre-migration box has the table but not the newer columns; EnsureSchema must still upgrade it.
    mysqli_query($conn, 'DROP TABLE card_abilities');
    mysqli_query($conn, "CREATE TABLE card_abilities (
        id int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
        root_name varchar(64) NOT NULL,
        card_id varchar(64) NOT NULL,
        macro_name varchar(128) NOT NULL,
        ability_code longtext NOT NULL,
        ability_name varchar(128) NULL,
        created_at timestamp NOT NULL DEFAULT current_timestamp(),
        updated_at timestamp NOT NULL DEFAULT current_timestamp()
    ) ENGINE=InnoDB");
    // force: the cached "already checked" for this connection+database must not mask a schema that
    // changed underneath it, which is exactly what the operator-pressed button has to handle.
    CardAbilityDB::EnsureSchema($conn, true);
    $check(strpos($columnType($conn, 'card_id'), 'varchar(128)') === 0, 'a legacy varchar(64) card_id is widened to 128');
    foreach (['prereq_code', 'ability_type', 'listener_zones', 'is_implemented'] as $column) {
        $check($columnType($conn, $column) !== '', "legacy table gains the $column column");
    }

    mysqli_close($conn);
} finally {
    mysqli_query($server, "DROP DATABASE IF EXISTS `$scratchDatabase`");
    mysqli_close($server);
}

echo $fails === 0 ? "\nALL PASS\n" : "\n$fails FAILED\n";
exit($fails === 0 ? 0 : 1);

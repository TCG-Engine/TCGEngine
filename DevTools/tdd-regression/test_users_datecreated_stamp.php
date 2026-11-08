<?php
// New accounts record when they were created (users.dateCreated, migration 23), in UTC, and only where the column
// exists -- the signup INSERTs are shared by every sim and stay unchanged, so a sim whose DB never ran migration 23
// must keep signing people up exactly as before.
//
// Runs against a TEMPORARY `users` table on its own connection: it shadows the real table for this connection only,
// so no real account is read or written.
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php -d xdebug.mode=off DevTools/tdd-regression/test_users_datecreated_stamp.php
chdir(dirname(__DIR__, 2));
require_once './Database/ConnectionManager.php';
require_once './Database/functions.inc.php';
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$checks = [];
$conn = GetLocalMySQLConnection();
// A session clock 5h off UTC, so a stamp taken from NOW() instead of UTC_TIMESTAMP() is caught.
$conn->query("SET time_zone = '-05:00'");
$utcNow = fn() => strtotime($conn->query("SELECT UTC_TIMESTAMP() t")->fetch_assoc()['t'] . ' UTC');
$dateOf = fn(int $id) => $conn->query("SELECT dateCreated d FROM users WHERE usersId = $id")->fetch_assoc()['d'];

// ── Column present ────────────────────────────────────────────────────────────
$conn->query("CREATE TEMPORARY TABLE users (usersId int AUTO_INCREMENT PRIMARY KEY, usersUid varchar(128), dateCreated datetime(3) DEFAULT NULL)");
$GLOBALS['DB_COLUMN_EXISTS_FAKE'] = fn($t, $c) => $t === 'users' && $c === 'dateCreated';

$conn->query("INSERT INTO users (usersUid) VALUES ('new-account')");
$newId = (int)$conn->insert_id;
$checks['returns true when it stamps'] = StampUserDateCreated($conn, $newId) === true;
$stamp = $dateOf($newId);
$checks['a new account gets a creation date'] = $stamp !== null;
$checks['the date is UTC, to the second'] = $stamp !== null && abs(strtotime($stamp . ' UTC') - $utcNow()) <= 5;
$checks['the date keeps milliseconds'] = $stamp !== null && preg_match('/\.\d{3}$/', $stamp) === 1;

$conn->query("INSERT INTO users (usersUid, dateCreated) VALUES ('backfilled', '2026-11-08 00:00:00.000')");
$oldId = (int)$conn->insert_id;
StampUserDateCreated($conn, $oldId);
$checks['an existing date is never overwritten'] = $dateOf($oldId) === '2026-11-08 00:00:00.000';

$conn->query("INSERT INTO users (usersUid) VALUES ('someone-else')");
$otherId = (int)$conn->insert_id;
$checks['only the given account is stamped'] = $dateOf($otherId) === null;

// ── Column missing (a sim whose DB never ran migration 23) ────────────────────
$conn->query("DROP TEMPORARY TABLE users");
$conn->query("CREATE TEMPORARY TABLE users (usersId int AUTO_INCREMENT PRIMARY KEY, usersUid varchar(128))");
$GLOBALS['DB_COLUMN_EXISTS_FAKE'] = fn($t, $c) => false;
$conn->query("INSERT INTO users (usersUid) VALUES ('other-sim')");
try {
    $checks['no column: does nothing and says so'] = StampUserDateCreated($conn, (int)$conn->insert_id) === false;
} catch (Throwable $e) {
    $checks['no column: does nothing and says so'] = false;
    fwrite(STDERR, '  threw: ' . $e->getMessage() . "\n");
}
$conn->query("DROP TEMPORARY TABLE users");
$conn->close();

// ── Every signup path calls it (the three INSERT INTO users sites) ────────────
$body = function (string $file, string $fn) {
    $src = (string)file_get_contents($file);
    $at = strpos($src, "function $fn(");
    if ($at === false) return '';
    $next = strpos($src, "\nfunction ", $at + 1);
    return substr($src, $at, $next === false ? null : $next - $at);
};
foreach ([['Database/functions.inc.php', 'createUser'], ['Database/functions.inc.php', 'CreateUserAPI'],
          ['AccountFiles/DiscordOAuth.php', 'DiscordOAuthCreateUserFromPending']] as [$file, $fn]) {
    $checks["$fn stamps the new account"] = strpos($body($file, $fn), 'StampUserDateCreated(') !== false;
}

$fail = array_keys(array_filter($checks, fn($v) => !$v));
echo empty($fail) ? "PASS (" . count($checks) . " checks)\n" : "FAIL: " . implode(' | ', $fail) . "\n";
exit(empty($fail) ? 0 : 1);

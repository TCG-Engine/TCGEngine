<?php
// The last-deck helpers must SURVIVE A MISSING TABLE.
//
// Owner, 2026-09-25: "if we end up wanting this in other sims, it'd be best to make a helper that
// is aware — so it fails if the table does not exist but doesn't break the site."
//
// ⚠ THE `if (!$stmt)` GUARDS IN functions.inc.php WERE DEAD CODE. Since PHP 8.1 mysqli's default
// error mode is MYSQLI_REPORT_ERROR|MYSQLI_REPORT_STRICT, so $conn->prepare() on a missing table
// THROWS mysqli_sql_exception instead of returning false. Measured:
//     prepare("SELECT 1 FROM definitely_not_a_table") -> THREW mysqli_sql_exception
// So a sim that shipped this code without running the migration took an UNCAUGHT exception — a
// white page — on every menu load for a logged-in user. That is the failure this file pins.
//
// The table is RENAMED AWAY and back, never dropped: the rename is atomic, reversible and keeps
// every row, so running this against a database with real rows in it cannot lose them. It is
// restored in a finally block so a failure part-way through still puts the table back.
//
// Run: docker exec -w /var/www/html/TCGEngine <c> php DevTools/tdd-regression/test_lastdeck_missing_table.php
header('Content-Type: text/plain');
error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);

chdir(dirname(__DIR__, 2));
require_once './Database/ConnectionManager.php';
require_once './Database/functions.inc.php';

$PASS = 0; $FAIL = 0; $MSGS = [];
function check($name, $cond, $detail = '') {
    global $PASS, $FAIL, $MSGS;
    if ($cond) { $PASS++; return; }
    $FAIL++; $MSGS[] = "FAIL: $name" . ($detail !== '' ? "  [$detail]" : '');
}

const TBL  = 'lastdeck';
const PARK = 'lastdeck_parked_by_test';

$conn = GetLocalMySQLConnection();
$have = $conn->query("SHOW TABLES LIKE '" . TBL . "'")->num_rows > 0;
if (!$have) {
    echo "SKIP: `" . TBL . "` is not in this database, so there is nothing to take away.\n"
       . "      Run Database/migrations/15_last_deck.sql first.\n";
    exit(2);
}

// ─── the helper itself ───────────────────────────────────────────────────────
check('DBTableExists sees a table that is there', DBTableExists($conn, TBL) === true);
check('DBTableExists does not invent one', DBTableExists($conn, 'definitely_not_a_table_' . getmypid()) === false);
// SQL injection through a table name cannot be parameterised, so the helper must reject junk
// rather than interpolate it.
check('DBTableExists refuses a name that is not an identifier',
    DBTableExists($conn, 'x`; DROP TABLE users; --') === false);

// ─── and now, with the table genuinely gone ──────────────────────────────────
$rowsBefore = (int)$conn->query("SELECT COUNT(*) c FROM " . TBL)->fetch_assoc()['c'];
$conn->query("RENAME TABLE `" . TBL . "` TO `" . PARK . "`");
$parked = $conn->query("SHOW TABLES LIKE '" . PARK . "'")->num_rows > 0;

try {
    check('the table really was taken away', $parked && $conn->query("SHOW TABLES LIKE '" . TBL . "'")->num_rows === 0);

    // Each of the three must return its own "nothing here" value WITHOUT throwing. Before the fix
    // every one of these was an uncaught mysqli_sql_exception.
    //
    // ⚠ IN A SUBPROCESS, because DBTableExists() memoises per process and THIS process already
    // looked the table up while it still existed. A request never sees a table vanish underneath
    // it; the state being reproduced is "the migration was never run", which every request meets
    // fresh. Asserting it in-process tested the cache, not the guard — the first version of this
    // file did exactly that and went red against correct code.
    $php = escapeshellarg(PHP_BINARY);
    $root = escapeshellarg(getcwd());
    $script = <<<'CODE'
chdir($argv[1]);
require_once "./Database/ConnectionManager.php";
require_once "./Database/functions.inc.php";
$out = [];
foreach ([
    'LoadLastDeck'   => fn() => LoadLastDeck(2147483600),
    'SetLastDeck'    => fn() => SetLastDeck(2147483600, 'https://swudb.com/deck/LImIrpIS', 'premier', 1, 'x'),
    'DeleteLastDeck' => fn() => DeleteLastDeck(2147483600),
] as $fn => $call) {
    try { $out[$fn] = ['ok' => true, 'value' => $call()]; }
    catch (Throwable $e) { $out[$fn] = ['ok' => false, 'error' => get_class($e) . ': ' . $e->getMessage()]; }
}
echo json_encode($out);
CODE;
    $tmp = sys_get_temp_dir() . '/lastdeck_probe_' . getmypid() . '.php';
    file_put_contents($tmp, "<?php\n" . $script);
    $raw = shell_exec("$php " . escapeshellarg($tmp) . " $root 2>&1");
    @unlink($tmp);
    $res = json_decode((string)$raw, true);

    if (!is_array($res)) {
        check('the subprocess probe ran', false, 'raw output: ' . substr((string)$raw, 0, 200));
    } else {
        foreach (['LoadLastDeck' => null, 'SetLastDeck' => false, 'DeleteLastDeck' => false] as $fn => $want) {
            $r = $res[$fn] ?? null;
            if (!$r || empty($r['ok'])) {
                check("$fn must not throw when the table is missing", false, $r['error'] ?? 'no result');
            } else {
                check("$fn returns " . var_export($want, true) . " when the table is missing",
                    $r['value'] === $want, var_export($r['value'], true));
            }
        }
    }

    // The menu is what a real visitor hits, and it is the page that calls LoadLastDeck on load.
    // A helper that returns null is no use if the page around it still dies.
    $url = 'http://localhost/TCGEngine/SharedUI/MainMenu.php';
    $body = @file_get_contents($url);
    check('the main menu still renders with the table missing',
        $body !== false && stripos($body, 'Fatal error') === false && stripos($body, 'mysqli_sql_exception') === false,
        $body === false ? 'request failed' : substr(strip_tags($body), 0, 120));
} finally {
    // ⚠ restore in a finally: a failed assertion above must not leave the database without its table
    $conn->query("RENAME TABLE `" . PARK . "` TO `" . TBL . "`");
}

$back = $conn->query("SHOW TABLES LIKE '" . TBL . "'")->num_rows > 0;
check('the table was put back', $back);
$rowsAfter = $back ? (int)$conn->query("SELECT COUNT(*) c FROM " . TBL)->fetch_assoc()['c'] : -1;
check('every row survived the round trip', $rowsAfter === $rowsBefore, "before=$rowsBefore after=$rowsAfter");

echo implode("\n", $MSGS);
echo ($MSGS ? "\n\n" : '') . "PASS=$PASS FAIL=$FAIL\n" . ($FAIL ? "RED\n" : "ALL GREEN\n");
exit($FAIL ? 1 : 0);

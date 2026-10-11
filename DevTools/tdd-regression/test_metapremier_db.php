<?php
// Meta Premier rating DB layer — docs/superpowers/specs/2026-10-03-swusim-metapremier-ratings-design.md §3-§4.
// Uses test-only account ids (no FK to users) and removes every row it writes, at start and in finally.
// The missing-table case RENAMES glicko_results away and back (never drops), restored in finally.
//
// Run: docker exec -w /var/www/html/TCGEngine -e XDEBUG_MODE=off otmtcge-swusim-web-server-1 php DevTools/tdd-regression/test_metapremier_db.php
header('Content-Type: text/plain');
error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);

chdir(dirname(__DIR__, 2));
require_once './Database/ConnectionManager.php';
require_once './Database/functions.inc.php';
require_once './SWUSim/MetaPremier.php';

$PASS = 0; $FAIL = 0; $MSGS = [];
function check($name, $cond, $detail = '') {
    global $PASS, $FAIL, $MSGS;
    if ($cond) { $PASS++; return; }
    $FAIL++; $MSGS[] = "FAIL: $name" . ($detail !== '' ? "  [$detail]" : '');
}

const U_A = 990000001;
const U_B = 990000002;
$conn = GetLocalMySQLConnection();

// Child mode (the missing-table case): DBTableExists() caches per PROCESS, and this process has already seen the
// tables, so the parent renames glicko_results away and runs this file again as a fresh process.
if (($argv[1] ?? '') === 'missing') {
    $mk1 = ['matchId' => 'MPTEST6', 'matchCreatedAt' => 1700000000, 'queueType' => 'bo3', 'winnerSeat' => 1, 'loserSeat' => 2,
            'winnerUserId' => U_A, 'loserUserId' => U_B, 'outcome' => 'win', 'started' => true];
    try {
        $ok = SWUMetaPremierApply($conn, $mk1, 1800000000) === 'no_tables'
           && SWUMetaPremierGetRating($conn, U_A, 'bo3') === null
           && SWUMetaPremierCooldownLeft($conn, U_B, 1800000000) === 0;
        echo $ok ? "CHILD OK\n" : "CHILD WRONG\n";
    } catch (Throwable $e) { echo 'CHILD THREW ' . $e->getMessage() . "\n"; }
    exit(0);
}
if (!SWUMetaPremierTablesReady($conn)) {
    echo "SKIP: the metapremier tables are not in this database. Run Database/migrations/17_glicko_ratings.sql first.\n";
    exit(2);
}
$cleanup = function () use ($conn) {
    $ids = U_A . ',' . U_B;
    $conn->query("DELETE FROM glicko_ratings WHERE userId IN ($ids)");
    $conn->query("DELETE FROM glicko_penalties WHERE userId IN ($ids)");
    $conn->query("DELETE FROM glicko_results WHERE matchId LIKE 'MPTEST%'");
};
$mk = fn($id, $outcome = 'win', $started = true, $w = U_A, $l = U_B) => ['matchId' => "MPTEST$id", 'matchCreatedAt' => 1700000000,
    'format' => 'metapremier', 'queueType' => 'bo3', 'winnerSeat' => 1, 'loserSeat' => 2, 'winnerUserId' => $w, 'loserUserId' => $l, 'outcome' => $outcome, 'started' => $started];
$rating = fn($u, $qt = 'bo3') => SWUMetaPremierGetRating($conn, $u, $qt);
$strikes = fn($u) => intval(($conn->query("SELECT abandonStrikes FROM glicko_penalties WHERE userId=$u")->fetch_assoc() ?? ['abandonStrikes' => 0])['abandonStrikes']);

$cleanup();
try {
    $now = 1800000000;
    check('first apply rates', SWUMetaPremierApply($conn, $mk(1), $now) === 'rated');
    $w1 = $rating(U_A); $l1 = $rating(U_B);
    check('winner up, loser down', $w1 && $l1 && $w1['rating'] > 1500 && $l1['rating'] < 1500, json_encode([$w1, $l1]));
    check('W-L recorded', $w1 && intval($w1['wins']) === 1 && intval($l1['losses']) === 1 && intval($w1['games']) === 1);
    check('apply twice is a no-op', SWUMetaPremierApply($conn, $mk(1), $now) === 'duplicate' && intval($rating(U_A)['games']) === 1);
    check('bo1 rating untouched', $rating(U_A, 'bo1') === null);
    // glicko_* tables are shared by any rated format: each format keeps its own ladder.
    check('row is stamped with its format', $conn->query("SELECT format FROM glicko_ratings WHERE userId=" . U_A)->fetch_assoc()['format'] === 'metapremier');
    check('another format has no rating', SWUMetaPremierGetRating($conn, U_A, 'bo3', 'someotherformat') === null);
    // format is REQUIRED (owner, 2026-10-04): no default, so a row written without one is refused rather than filed
    // under some format by accident.
    $noFormat = function ($sql) use ($conn) { try { $conn->query($sql); return false; } catch (mysqli_sql_exception $e) { return true; } };
    check('a rating row without a format is refused', $noFormat("INSERT INTO glicko_ratings (userId, queueType, season, updatedAt) VALUES (" . U_A . ", 'bo1', 1, 0)"));
    check('a result row without a format is refused', $noFormat("INSERT INTO glicko_results (matchId, matchCreatedAt, queueType, season, winnerUserId, loserUserId, outcome,
        wRatingBefore, wRdBefore, wVolBefore, wRatingAfter, wRdAfter, wVolAfter, lRatingBefore, lRdBefore, lVolBefore, lRatingAfter, lRdAfter, lVolAfter, ratedAt)
        VALUES ('MPTESTNOFMT', 1, 'bo3', 1, " . U_A . ", " . U_B . ", 'win', 0,0,0,0,0,0, 0,0,0,0,0,0, 0)"));
    check('result row is stamped with its format', $conn->query("SELECT format FROM glicko_results WHERE matchId='MPTEST1'")->fetch_assoc()['format'] === 'metapremier');
    check('a clean loss is no strike', $strikes(U_B) === 0 && SWUMetaPremierCooldownLeft($conn, U_B, $now) === 0);

    // A matching pair at equal ratings: the winner's gain equals the loser's loss (no penalty involved).
    $row = $conn->query("SELECT * FROM glicko_results WHERE matchId='MPTEST1'")->fetch_assoc();
    check('result row before/after logged', abs($row['wRatingBefore'] - 1500) < 1e-9 && abs($row['wRatingAfter'] - $w1['rating']) < 1e-9
        && intval($row['rated']) === 1 && $row['outcome'] === 'win');

    check('abandon rates', SWUMetaPremierApply($conn, $mk(2, 'abandon'), $now) === 'rated');
    $row = $conn->query("SELECT penaltyApplied FROM glicko_results WHERE matchId='MPTEST2'")->fetch_assoc();
    check('abandon penalty logged as 25', abs($row['penaltyApplied'] - 25) < 1e-9, json_encode($row));
    check('cooldown 300s after strike 1', SWUMetaPremierCooldownLeft($conn, U_B, $now) === 300);
    check('abandon counter', intval($rating(U_B)['abandons']) === 1);

    check('pregame abandon = strike only', SWUMetaPremierApply($conn, $mk(3, 'abandon', false), $now + 10) === 'strike_only');
    check('strike 2 → 1800s', SWUMetaPremierCooldownLeft($conn, U_B, $now + 10) === 1800);
    check('pregame abandon did not rate', intval($rating(U_B)['games']) === 2);
    check('pregame abandon logged unrated', intval($conn->query("SELECT rated FROM glicko_results WHERE matchId='MPTEST3'")->fetch_assoc()['rated']) === 0);
    check('pregame abandon twice is a no-op', SWUMetaPremierApply($conn, $mk(3, 'abandon', false), $now + 10) === 'duplicate' && $strikes(U_B) === 2);
    check('pregame concede = nothing', SWUMetaPremierApply($conn, $mk(4, 'concede', false), $now) === 'skipped'
        && $conn->query("SELECT 1 FROM glicko_results WHERE matchId='MPTEST4'")->num_rows === 0);
    // Early concede (owner, 2026-10-05): punished like an abandon — but it is not counted as an abandon.
    $abBefore = intval($rating(U_B)['abandons']);
    check('early concede rates', SWUMetaPremierApply($conn, $mk(7, 'early'), $now + 20) === 'rated');
    $row = $conn->query("SELECT outcome, penaltyApplied FROM glicko_results WHERE matchId='MPTEST7'")->fetch_assoc();
    check('early concede logged as early with the 25 penalty', ($row['outcome'] ?? '') === 'early' && abs($row['penaltyApplied'] - 25) < 1e-9, json_encode($row));
    check('early concede is a strike (3rd → 24h)', SWUMetaPremierCooldownLeft($conn, U_B, $now + 20) === 86400);
    check('early concede is not counted as an abandon', intval($rating(U_B)['abandons']) === $abBefore);
    check('early concede during mulligans = strike only', SWUMetaPremierApply($conn, $mk(8, 'early', false), $now + 30) === 'strike_only'
        && $strikes(U_B) === 4);

    $s0 = $strikes(U_B);
    for ($i = 0; $i < 9; $i++) SWUMetaPremierApply($conn, $mk(100 + $i, 'win', true, U_B, U_A), $now + 100 + $i);
    check('9 clean matches: not yet forgiven', $strikes(U_B) === $s0);
    SWUMetaPremierApply($conn, $mk(109, 'win', true, U_B, U_A), $now + 109);
    check('10 clean matches forgive one strike', $strikes(U_B) === $s0 - 1);

    // Floor: drive a rating to the floor directly, then abandon.
    $conn->query("UPDATE glicko_ratings SET rating=105 WHERE userId=" . U_B . " AND queueType='bo3'");
    SWUMetaPremierApply($conn, $mk(5, 'abandon'), $now + 500);
    check('rating floor 100', $rating(U_B)['rating'] >= 100 - 1e-9, strval($rating(U_B)['rating']));
    $row = $conn->query("SELECT penaltyApplied FROM glicko_results WHERE matchId='MPTEST5'")->fetch_assoc();
    check('penalty logged is what was actually deducted at the floor', $row['penaltyApplied'] < 25, json_encode($row));

    // Missing tables: no exception, nothing rated (checked in a fresh child process — see child mode above).
    $conn->query("RENAME TABLE glicko_results TO glicko_results_parked_by_test");
    try {
        $child = shell_exec(escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg(__FILE__) . ' missing 2>&1');
        check('missing table → no_tables / no rating / no cooldown, no exception', strpos((string)$child, 'CHILD OK') !== false, trim((string)$child));
    } finally {
        $conn->query("RENAME TABLE glicko_results_parked_by_test TO glicko_results");
    }
    check('results table restored', $conn->query("SHOW TABLES LIKE 'glicko_results'")->num_rows === 1);
} catch (Throwable $e) {
    $FAIL++; $MSGS[] = 'FAIL: threw ' . get_class($e) . ': ' . $e->getMessage();
} finally {
    $cleanup();
}

echo implode("\n", $MSGS) . ($MSGS ? "\n" : '');
echo $FAIL === 0 ? "PASS ($PASS checks)\n" : "FAIL ($FAIL of " . ($PASS + $FAIL) . ")\n";
exit($FAIL === 0 ? 0 : 1);

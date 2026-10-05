<?php
// Profile "Meta Premier" panel — docs/superpowers/specs/2026-10-03-swusim-metapremier-ratings-design.md §5.3.
// Test-only account id; its rows are removed at start and in finally. The missing-table case runs in a fresh child
// process (DBTableExists caches per process) while glicko_ratings is renamed away, restored in finally.
// Run: docker exec -w /var/www/html/TCGEngine -e XDEBUG_MODE=off otmtcge-swusim-web-server-1 php DevTools/tdd-regression/test_metapremier_profile_render.php
header('Content-Type: text/plain');
error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);
chdir(dirname(__DIR__, 2));
require_once './Database/ConnectionManager.php';
require_once './Database/functions.inc.php';
require_once './SharedUI/Render/MetaPremierRating.php';

const U = 990000021;
$conn = GetLocalMySQLConnection();
if (($argv[1] ?? '') === 'missing') {
    try { $h = RenderMetaPremierRating(U, $conn); echo (strpos($h, 'No rated matches yet') !== false) ? "CHILD OK\n" : "CHILD WRONG $h\n"; }
    catch (Throwable $e) { echo 'CHILD THREW ' . $e->getMessage() . "\n"; }
    exit(0);
}
$PASS = 0; $FAIL = 0; $MSGS = [];
function check($name, $cond, $detail = '') { global $PASS, $FAIL, $MSGS; if ($cond) { $PASS++; return; } $FAIL++; $MSGS[] = "FAIL: $name" . ($detail !== '' ? "  [$detail]" : ''); }
$cleanup = fn() => $conn->query("DELETE FROM glicko_ratings WHERE userId=" . U);
$cleanup();
try {
    check('logged out renders nothing', RenderMetaPremierRating(0, $conn) === '');
    $html = RenderMetaPremierRating(U, $conn);
    check('pane + heading', strpos($html, "class='metaPremierRating") !== false && strpos($html, '<h2>Meta Premier</h2>') !== false);
    check('no matches yet message', strpos($html, 'No rated matches yet') !== false, $html);
    check('Best of 3 row shown', strpos($html, 'Best of 3') !== false);
    check('Best of 1 row shown (both ladders, owner 2026-10-05)', strpos($html, 'Best of 1') !== false);
    $conn->query("INSERT INTO glicko_ratings (userId, format, queueType, season, rating, rd, volatility, games, wins, losses, updatedAt)
                  VALUES (" . U . ",'metapremier','bo3',1,1612.4,200,0.06,5,3,2,UNIX_TIMESTAMP())");
    $html = RenderMetaPremierRating(U, $conn);
    check('provisional marker', strpos($html, '1612?') !== false, $html);
    $conn->query("UPDATE glicko_ratings SET rd=80 WHERE userId=" . U);
    $html = RenderMetaPremierRating(U, $conn);
    check('settled rating, W-L, matches', strpos($html, '1612') !== false && strpos($html, '1612?') === false
        && strpos($html, '3–2') !== false && strpos($html, '5 matches') !== false, $html);

    $conn->query("RENAME TABLE glicko_ratings TO glicko_ratings_parked_by_test");
    try {
        $child = shell_exec(escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg(__FILE__) . ' missing 2>&1');
        check('missing table renders the empty state, no exception', strpos((string)$child, 'CHILD OK') !== false, trim((string)$child));
    } finally {
        $conn->query("RENAME TABLE glicko_ratings_parked_by_test TO glicko_ratings");
    }
} catch (Throwable $e) { $FAIL++; $MSGS[] = 'FAIL: threw ' . $e->getMessage(); }
finally { $cleanup(); }
echo implode("\n", $MSGS) . ($MSGS ? "\n" : '');
echo $FAIL === 0 ? "PASS ($PASS checks)\n" : "FAIL ($FAIL of " . ($PASS + $FAIL) . ")\n";
exit($FAIL === 0 ? 0 : 1);

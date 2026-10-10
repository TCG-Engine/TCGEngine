<?php
// SWU-PGN replay viewer limits (anyone, guests included, may open a file): per-client and global caps on
// live viewer games, expiry through the index, and the once-a-day full folder sweep.
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php DevTools/tdd-regression/test_swupgn_viewer_limits.php
require_once __DIR__ . '/fixtures/swupgn_test_helpers.php';
require_once __DIR__ . '/../../SWUSim/Custom/SwuPgnViewerLimits.php';

$dir = sys_get_temp_dir() . '/swupgn-limits-' . bin2hex(random_bytes(4));
mkdir($dir);
$next = 1000;
$made = [];
$create = function () use ($dir, &$next, &$made): string {
    $g = (string)$next++;
    mkdir("$dir/$g"); file_put_contents("$dir/$g/Viewer.json", '{}');
    $made[] = $g;
    return $g;
};
$index = fn() => json_decode((string)@file_get_contents("$dir/" . SWUPGN_VIEWER_INDEX_FILE), true)['games'] ?? [];
$now = 2000000000;

$r = SwuPgnViewerAdmit($dir, 'a', $now, $create);
SwuPgnTestCheck($r['ok'] && $r['gameName'] === '1000' && isset($index()['1000']), 'under the caps: the game is created and registered');

for ($i = 1; $i < SWUPGN_VIEWER_MAX_PER_CLIENT; $i++) SwuPgnViewerAdmit($dir, 'a', $now, $create);
$before = count($made);
$r = SwuPgnViewerAdmit($dir, 'a', $now, $create);
SwuPgnTestCheck(!$r['ok'] && $r['status'] === 429 && count($made) === $before, 'one client over its cap is refused and nothing is created');
SwuPgnTestCheck(SwuPgnViewerAdmit($dir, 'b', $now, $create)['ok'], 'another client is still admitted');
SwuPgnTestCheck(SwuPgnViewerAdmit($dir, 'a', $now + SWUPGN_VIEWER_CLIENT_WINDOW + 1, $create)['ok'], 'the per-client cap counts only the recent window');

// Global cap: fill up to the live limit with distinct clients.
$live = count($index());
for ($i = $live; $i < SWUPGN_VIEWER_MAX_LIVE; $i++) SwuPgnViewerAdmit($dir, "c$i", $now + SWUPGN_VIEWER_CLIENT_WINDOW + 1, $create);
SwuPgnTestEq(count($index()), SWUPGN_VIEWER_MAX_LIVE, 'the index holds exactly the live limit');
$r = SwuPgnViewerAdmit($dir, 'fresh', $now + SWUPGN_VIEWER_CLIENT_WINDOW + 1, $create);
SwuPgnTestCheck(!$r['ok'] && $r['status'] === 503, 'over the live limit: refused');

// Expiry: a day later every game is old; the expired folders go, and a folder without Viewer.json is never touched.
unlink("$dir/1001/Viewer.json"); file_put_contents("$dir/1001/Gamestate.txt", 'a real game reused this number');
$later = $now + SWUPGN_VIEWER_MAX_AGE + SWUPGN_VIEWER_CLIENT_WINDOW + 2;
$r = SwuPgnViewerAdmit($dir, 'fresh', $later, $create);
SwuPgnTestCheck($r['ok'] && count($index()) === 1, 'expired games leave the index, so a new one is admitted');
SwuPgnTestCheck(!is_dir("$dir/1000") && !is_dir("$dir/1002"), 'expired viewer folders are deleted');
SwuPgnTestCheck(is_file("$dir/1001/Gamestate.txt"), 'a folder without Viewer.json is left alone');

// The full folder sweep (for folders the index never knew) runs at most once a day.
SwuPgnTestCheck(SwuPgnViewerSweepDue($dir, $later), 'the sweep is due when it never ran');
SwuPgnTestCheck(!SwuPgnViewerSweepDue($dir, $later + 60), 'and not again within the day');
SwuPgnTestCheck(SwuPgnViewerSweepDue($dir, $later + SWUPGN_VIEWER_MAX_AGE + 1), 'and due again a day later');

exec('rm -rf ' . escapeshellarg($dir));
SwuPgnTestFinish();

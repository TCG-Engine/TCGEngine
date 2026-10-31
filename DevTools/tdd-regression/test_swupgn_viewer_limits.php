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

// The sweep itself (SwuPgnViewerCleanup): an old viewer folder goes; a fresh viewer folder and an old normal game stay.
require_once __DIR__ . '/../../SWUSim/Custom/SwuPgnViewerStore.php';
$sweep = "$dir/sweep"; mkdir($sweep);
mkdir("$sweep/1"); file_put_contents("$sweep/1/Viewer.json", '{}'); file_put_contents("$sweep/1/Viewer.swupgn", 'x'); touch("$sweep/1/Viewer.json", time() - 90000);
mkdir("$sweep/2"); file_put_contents("$sweep/2/Viewer.json", '{}');
mkdir("$sweep/3"); file_put_contents("$sweep/3/Gamestate.txt", 'x'); touch("$sweep/3/Gamestate.txt", time() - 90000); touch("$sweep/3", time() - 90000);
SwuPgnTestEq(SwuPgnViewerCleanup($sweep), 1, 'the sweep removes one folder');
SwuPgnTestCheck(!is_dir("$sweep/1") && is_dir("$sweep/2") && is_file("$sweep/3/Gamestate.txt"), 'the old viewer folder goes; a fresh viewer folder and an old normal game stay');

// A viewer game only ever takes an EMPTY folder: a game number handed out again (a reset counter) can hold another
// game's files — a NextGame.json there makes every poll answer "1235MATCHADVANCE…" instead of the board.
$fresh = "$dir/fresh"; mkdir($fresh);
mkdir("$fresh/7"); file_put_contents("$fresh/7/NextGame.json", '{"nextGameName":"8"}');
mkdir("$fresh/8");
$numbers = [7, 8, 9]; $asked = 0;
$next = function () use (&$numbers, &$asked) { $asked++; return array_shift($numbers); };
SwuPgnTestEq([SwuPgnViewerNewGameFolder($fresh, $next), $asked], ['8', 2], 'a number whose folder holds files is skipped; an empty existing folder is fine');
$numbers = [7, 'x/../7', 10];
SwuPgnTestCheck(SwuPgnViewerNewGameFolder($fresh, function () use (&$numbers) { return array_shift($numbers); }) === '10' && is_dir("$fresh/10"), 'a folder that does not exist yet is made; a non-numeric name is never used');
SwuPgnTestCheck(is_file("$fresh/7/NextGame.json"), 'and the skipped folder is left alone');
SwuPgnTestEq(SwuPgnViewerNewGameFolder($fresh, fn() => 7, 3), null, 'no empty folder within the tries → none');
$r = SwuPgnViewerAdmit($fresh, 'z', $now, fn() => null);
SwuPgnTestCheck(!$r['ok'] && $r['status'] === 503 && (json_decode((string)@file_get_contents("$fresh/" . SWUPGN_VIEWER_INDEX_FILE), true)['games'] ?? []) === [], 'Admit refuses (and registers nothing) when no folder could be made');

exec('rm -rf ' . escapeshellarg($dir));
SwuPgnTestFinish();

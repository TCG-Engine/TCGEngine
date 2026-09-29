<?php
// Every self-play run must SAVE its decision trace (owner ruling 2026-09-29). Before this, SWUBOT_TRACE was
// opt-in per invocation, so a measurement script that forgot it produced a run nobody could reconstruct: the
// 2000-game creditbank canary and the 10x500 field run kept only their one-line SWUBOT_METRICS, and answering
// "what was the bot's gameplan" needed a fresh 200-game run on a DIFFERENT seed block.
//
// Two halves, and the SECOND is the load-bearing one:
//   A. SWUBotTracePathFor() composes a path that cannot collide across arms, decks, seeds or first players.
//   B. the harness turns tracing ON with no environment variable set — the actual requirement. A passes
//      happily if the harness never calls it, so B runs the real binary end to end.
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php -d apc.enable_cli=1 -d xdebug.mode=off SWUSim/DevTools/tests/bot_trace_saved_test.php
require __DIR__ . '/fixtures/bot_test_bootstrap.php';
include_once './SWUSim/BotLegalActions.php';
include_once './SWUSim/BotHeuristic.php';

// ── A. the path is collision-free in every dimension that makes a game ──────────────────────────────
$root = sys_get_temp_dir() . '/bottrace_' . getmypid();
putenv('SWUBOT_TRACE_DIR=' . $root);
$fix = 'SWUSim/Tests/BotFixtures/meta-2026-09';
$p = fn($d1, $d2, $c1, $c2, $seed, $fp) => SWUBotTracePathFor($d1, $d2, $c1, $c2, $seed, $fp);
$base = $p("$fix/krennic_splash.txt", "$fix/ahsoka_blue.txt", 'heuristic-softcontrol', 'heuristic-softaggro', 'kx0001', 1);

$check(strpos($base, $root . '/') === 0, 'SWUBOT_TRACE_DIR relocates the root');
$check(basename($base) === 'kx0001.fp1.jsonl', "seed and first player name the file — got " . basename($base));
$check(basename(dirname($base)) === 'krennic_splash__ahsoka_blue__heuristic-softcontrol__heuristic-softaggro',
    'the directory names both decks and both profiles — got ' . basename(dirname($base)));

// The collision that actually bit: two ARMS of one seed block. The arm rides the chooser profile, so if the
// profile were dropped from the path, baseline and @try-creditbank traces of kx0001 would be the same file —
// and since the harness truncates, the second arm would silently ERASE the first.
$arm = $p("$fix/krennic_splash.txt", "$fix/ahsoka_blue.txt", 'heuristic-softcontrol@try-creditbank', 'heuristic-softaggro', 'kx0001', 1);
$check($arm !== $base, 'a proposal arm does not collide with its baseline');
$check(strpos($arm, '@try-creditbank') !== false, 'the variant survives slugging (@ is kept, it IS the arm)');
// The field run's other collision: one Krennic profile against ten different opponents.
$check($p("$fix/krennic_splash.txt", "$fix/vader_yellow.txt", 'heuristic-softcontrol', 'heuristic-softaggro', 'kx0001', 1) !== $base,
    'a different opponent deck does not collide');
$check($p("$fix/krennic_splash.txt", "$fix/ahsoka_blue.txt", 'heuristic-softcontrol', 'heuristic-softaggro', 'kx0001', 2) !== $base,
    'the other first player does not collide');
$check($p("$fix/krennic_splash.txt", "$fix/ahsoka_blue.txt", 'heuristic-softcontrol', 'heuristic-softaggro', 'kx0002', 1) !== $base,
    'a different seed does not collide');
// A sweep runs WORKERS children at once; every one of them must own its own file.
$parallel = array_map(fn($s) => $p("$fix/a.txt", "$fix/b.txt", 'heuristic-midrange', 'heuristic-midrange', $s, 1),
    ['s01', 's02', 's03', 's04', 's05', 's06']);
$check(count(array_unique($parallel)) === 6, 'six concurrent sweep seeds get six distinct files');

// A mirror names one deck; deck2 must not become 'builtin' and split the mirror across two dirs.
$mirror = $p("$fix/krennic_splash.txt", null, 'heuristic-softcontrol', 'heuristic-softcontrol', 's01', 1);
$check(basename(dirname($mirror)) === 'krennic_splash__krennic_splash__heuristic-softcontrol__heuristic-softcontrol',
    'deck2 defaults to deck1, as the harness does — got ' . basename(dirname($mirror)));
// No deck at all (the harness's built-in decks) still yields a usable, non-empty path.
$none = $p(null, null, 'first-legal', 'first-legal', 'swusimbotselfplay00000000000000', 1);
$check(basename(dirname($none)) === 'builtin__builtin__first-legal__first-legal', 'no --deck still names a directory');

// Slugging must not let a component escape the root or split it into subdirectories.
$evil = $p('../../etc/passwd', '/tmp/x/../y.txt', 'a/b', 'c d', '../s', 1);
$check(substr_count(substr($evil, strlen($root)), '/') === 2, 'a traversal attempt stays two levels under the root');
$check(strpos($evil, '..') === false, 'no ".." survives into the path');

// ── B. the harness traces with NO environment variable ──────────────────────────────────────────────
// The requirement is "every run", so this drives the real harness instead of asserting on the helper. One
// FULL game (~1.5 s), not a --max-rounds=2 one: a capped game exits 1 by design ("capped":true), so capping
// would have forced this check to tolerate a nonzero exit and it could then no longer tell a capped run from
// a crashed one.
$runRoot = $root . '/live';
$cmd = 'cd /var/www/html/TCGEngine && env -u SWUBOT_TRACE -u SWUBOT_TRACE_BOARD SWUBOT_TRACE_DIR=' . escapeshellarg($runRoot)
     . ' timeout 180 php -d apc.enable_cli=1 -d xdebug.mode=off DevTools/SWUSimBotSelfPlayTest.php'
     . ' --games=1 --seed=tracetest --first-player=1 --memory-only'
     . ' --chooser=heuristic-softcontrol --chooser2=heuristic-softaggro'
     . ' --deck=' . escapeshellarg("$fix/krennic_splash.txt") . ' --deck2=' . escapeshellarg("$fix/ahsoka_blue.txt")
     . ' 2>&1';
exec($cmd, $out, $rc);
$outText = implode("\n", $out);
$check($rc === 0, "harness ran (exit {$rc})" . ($rc === 0 ? '' : ' — ' . substr($outText, -400)));

$files = glob($runRoot . '/*/*.jsonl') ?: [];
$check(count($files) === 1, 'ONE trace file was written with no SWUBOT_TRACE set — found ' . count($files)
    . ($files ? ' (' . implode(', ', array_map('basename', $files)) . ')' : ''));
// The harness must also SAY where it put it, or a finished run is material nobody can find.
$check(strpos($outText, '[TRACE] ') !== false, 'the run prints its [TRACE] path');

$lines = $files ? array_filter(explode("\n", trim(file_get_contents($files[0])))) : [];
$check(count($lines) > 10, 'the trace holds the run\'s decisions — ' . count($lines) . ' lines');
$recs = array_map(fn($l) => json_decode($l, true), $lines);
$check(!in_array(null, $recs, true), 'every line is valid JSON');
if ($recs) {
    $first = $recs[0];
    foreach (['round', 'seat', 'style', 'candidates', 'pick', 'layer'] as $k) {
        $check(array_key_exists($k, $first), "a record carries '{$k}'");
    }
    // The board snapshot defaults ON: without it a trace is a list of picks, not a position, and the
    // Koska/trade counts that motivated this change cannot be derived at all.
    $withBoard = array_filter($recs, fn($r) => isset($r['board']['me']['hpLeft']));
    $check(count($withBoard) === count($recs), 'SWUBOT_TRACE_BOARD defaults ON — '
        . count($withBoard) . '/' . count($recs) . ' records carry a board');
    $check(count(array_unique(array_map(fn($r) => intval($r['seat']), $recs))) === 2,
        'both seats are traced, not just seat 1');
}

// Re-running the same seed must REPLACE the trace, not append a second game to it (SWUBotTrace appends).
if ($files) {
    $firstRunLines = count($lines);
    exec($cmd, $out2, $rc2);
    $again = glob($runRoot . '/*/*.jsonl') ?: [];
    $check(count($again) === 1, 're-running the seed reuses the same file');
    $reLines = $again ? count(array_filter(explode("\n", trim(file_get_contents($again[0]))))) : 0;
    $check($reLines === $firstRunLines, "a re-run truncates rather than doubling — {$firstRunLines} then {$reLines}");
}

exec('rm -rf ' . escapeshellarg($root));
bot_test_finish();

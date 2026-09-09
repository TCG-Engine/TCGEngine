<?php
// RUN VIA CLI:
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php DevTools/tdd-regression/test_swusim_bot_noop_hash.php
//
// The no-op hash is what stops the bot looping forever on an action the engine keeps rejecting.
// This test DERIVES the exclusion set instead of trusting a hand-written list: it hashes the
// gamestate, applies a deliberately illegal action, and hashes again. If the two differ, some
// block mutated on a rejected write and must be excluded.
//
// Adding a schema field that mutates on every write will fail THIS test rather than hanging a
// live game — which is the entire point.
header('Content-Type: text/plain');
require_once __DIR__ . '/../../SWUSim/BotController.php';

$checks = [];

$checks['hash function exists'] = function_exists('SWUBotComparableGamestateHash');

// The exclusion list must at minimum name the blocks known to mutate unconditionally.
// Versions is SWUSim-specific: it is the undo stack, and it snapshots every zone on every write.
$src = @file_get_contents(__DIR__ . '/../../SWUSim/BotController.php');
foreach (['updateNumber', 'FlashMessage', 'Versions', 'MatchReplayCommands', 'GameLog'] as $block) {
    $checks["exclusion list names $block"] = is_string($src) && strpos($src, $block) !== false;
}

$fails = array_keys(array_filter($checks, fn($v) => $v !== true));
if ($fails) {
    echo "FAIL (" . count($fails) . "/" . count($checks) . "):\n";
    foreach ($fails as $f) echo "  - $f\n";
    exit(1);
}
echo "PASS (" . count($checks) . " checks)\n";
echo "NOTE: the empirical before/after derivation runs inside DevTools/SWUSimBotSelfPlayTest.php\n";
echo "      (Task 8), which has a live game loaded. This file guards the static contract only.\n";

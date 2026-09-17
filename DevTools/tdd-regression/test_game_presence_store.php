<?php
// RUN VIA CLI:
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php DevTools/tdd-regression/test_game_presence_store.php
//
// Inactivity clock stage 1 — the presence store (Core/GamePresence.php).
// Spec: docs/superpowers/specs/2026-09-17-swusim-inactivity-timer-and-kick-design.md
// ⚠ The CLI SAPI has NO APCu, so every function must degrade safely there. The APCu round-trip is
// exercised by running this same file over HTTP (see the plan, Task 1 Step 5).
header('Content-Type: text/plain');
require_once __DIR__ . '/../../Core/GamePresence.php';

$checks = [];
$hasApcu = extension_loaded('apcu') && function_exists('apcu_enabled') && apcu_enabled();

$checks['key is namespaced']              = PresenceCacheKey('4242') === 'presence_4242';
$d = PresenceDefault();
// 'pv' (vote version — the poll wake signal, bumped only by vote changes) and 'facts' (the evaluator's
// inputs cached per action, so the poll's cheap path needs no gamestate) were added with stage 2.
$checks['default has every field']        = array_keys($d) === ['v','pv','seen','acted','owes','since','votes','facts'];
$checks['default is empty']               = $d['seen'] === [] && $d['acted'] === [] && $d['votes'] === [] && $d['v'] === 0;

// Degrades safely with no APCu: read gives the default, writes report false, nothing throws.
$read = PresenceRead('no_such_game_' . getmypid());
$checks['read of a missing game = default'] = $read['seen'] === [] && $read['acted'] === [];
if (!$hasApcu) {
    $checks['write without APCu returns false']  = PresenceWrite('x' . getmypid(), PresenceDefault()) === false;
    $checks['touch without APCu returns false']  = PresenceTouchSeat('x' . getmypid(), 1, 1000) === false;
    $checks['stamp without APCu returns false']  = PresenceStampAction('x' . getmypid(), 1, 1000) === false;
    $checks['version without APCu is 0']         = PresenceVersion('x' . getmypid()) === 0;
} else {
    // Full round-trip when APCu is present (web SAPI).
    $g = 'ptest' . getmypid();
    apcu_delete(PresenceCacheKey($g));
    $checks['first write succeeds']        = PresenceWrite($g, PresenceDefault()) === true;
    $checks['version bumped on write']     = PresenceVersion($g) === 1;

    $checks['touch writes']                = PresenceTouchSeat($g, 1, 1000) === true;
    $checks['touch recorded']              = PresenceRead($g)['seen'][1] === 1000;
    $checks['touch throttled within 2s']   = PresenceTouchSeat($g, 1, 1001) === false;
    $checks['throttled touch kept old ts'] = PresenceRead($g)['seen'][1] === 1000;
    $checks['touch past throttle writes']  = PresenceTouchSeat($g, 1, 1003) === true;
    $checks['second seat independent']     = PresenceTouchSeat($g, 2, 1003) === true && PresenceRead($g)['seen'][2] === 1003;

    $checks['stamp writes']                = PresenceStampAction($g, 2, 1010, [1]) === true;
    $p = PresenceRead($g);
    $checks['stamp recorded acted']        = $p['acted'][2] === 1010;
    $checks['stamp also marks seen']       = $p['seen'][2] === 1010;
    $checks['stamp recorded owes']         = $p['owes'] === [1];
    $checks['stamp recorded since']        = $p['since'] === 1010;
    $checks['stamp bumped version']        = PresenceVersion($g) > 1;

    // A stamp by the vote's target clears that vote (the "acting cancels the vote" rule).
    $p = PresenceRead($g);
    $p['votes'] = [3 => ['reason' => 'stall', 'yes' => [1 => 1010], 'until' => 1099, 'waits' => 0]];
    PresenceWrite($g, $p);
    PresenceStampAction($g, 3, 1020, []);
    $checks['target acting clears its vote'] = PresenceRead($g)['votes'] === [];
    // Another seat acting does NOT clear an unrelated vote.
    $p = PresenceRead($g);
    $p['votes'] = [3 => ['reason' => 'stall', 'yes' => [], 'until' => 1099, 'waits' => 0]];
    PresenceWrite($g, $p);
    PresenceStampAction($g, 1, 1030, []);
    $checks['other seat keeps the vote']     = array_keys(PresenceRead($g)['votes']) === [3];

    // Corrupt entry heals into the default rather than fataling.
    apcu_store(PresenceCacheKey($g), 'not-an-array', 60);
    $checks['corrupt entry reads as default'] = PresenceRead($g)['seen'] === [];
    apcu_delete(PresenceCacheKey($g));
}

$fail = 0;
foreach ($checks as $name => $ok) { echo ($ok ? 'PASS' : 'FAIL') . "  $name\n"; if (!$ok) $fail++; }
echo 'apcu=' . ($hasApcu ? 'yes' : 'no') . "\n";
echo $fail === 0 ? "\nPASS (" . count($checks) . ")\n" : "\nFAIL ($fail of " . count($checks) . ")\n";
exit($fail === 0 ? 0 : 1);

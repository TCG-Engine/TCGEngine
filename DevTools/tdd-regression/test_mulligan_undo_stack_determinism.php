<?php
// Regression: the mulligan reshuffle must NOT depend on the undo-stack (Versions zone) contents.
// Bug (prod game 33): mulligan → undo → mulligan again gave a DIFFERENT hand. Cause: EngineSnapshotState
// (the deterministic-RNG hash material) included the Versions zone, which holds the undo stack. Undoing
// pops the inline begin-game snapshot, so the Versions zone differs between the first mulligan and the
// re-mulligan → different seed → different hand. The undo stack is bookkeeping and must not sway gameplay
// randomness. Here the ONLY thing that differs across the two shuffles is the undo-stack content.
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php DevTools/tdd-regression/test_mulligan_undo_stack_determinism.php
error_reporting(E_ALL & ~E_DEPRECATED); ini_set('display_errors', 1);
chdir('/var/www/html/TCGEngine');
if (!function_exists('ConvertMzIDToAbsolute'))      { function ConvertMzIDToAbsolute($m,$p):string{return '';} }
if (!function_exists('QueueDamageAnimation'))       { function QueueDamageAnimation($t,$a):void{} }
if (!function_exists('QueueRestoreAnimation'))      { function QueueRestoreAnimation($t,$a):void{} }
if (!function_exists('QueuePreventedDamageAnimation')) { function QueuePreventedDamageAnimation($t):void{} }
if (!function_exists('QueueShieldBreakAnimation'))  { function QueueShieldBreakAnimation($t):void{} }
foreach (['DeterministicRNG','CoreZoneModifiers','GameAuth'] as $f) include_once "./Core/$f.php";
include_once './SWUSim/ZoneClasses.php'; include_once './SWUSim/ZoneAccessors.php';
include_once './SWUSim/GeneratedCode/GeneratedCardDictionaries.php'; include_once './SWUSim/GamestateParser.php';
foreach (['Assertions','Cards','CommonSetup','GameStateBuilder','GameTestAdapter','SchemaTestRunner','TestRunner'] as $f) include_once "./SWUSim/Tests/Framework/$f.php";
global $gameName, $playerID, $customDQHandlers;
$gameName = 'mus_' . getmypid(); $playerID = 1;
@mkdir('./Games/' . $gameName, 0777, true);

$b = new GameStateBuilder(); CommonSetup($b, 'grw', 'brk', [], []); $b->WithActivePlayer(1);
$deckIDs = ['SOR_095','SOR_046','SOR_100','SOR_101','SOR_102','SOR_103','SOR_104','SOR_105',
            'SOR_106','SOR_107','SOR_108','SOR_109','SOR_110','SOR_111','SOR_112','SOR_113'];
foreach (['SOR_200','SOR_201','SOR_202','SOR_203','SOR_204','SOR_205'] as $h) $b->WithCardInHandForPlayer(1, $h);
foreach ($deckIDs as $d) $b->WithCardInDeckForPlayer(1, $d);
$g = new GameTestAdapter(); $g->loadState($b);
ob_start(); AutoAdvanceAndExecute(); ob_end_clean();
$GLOBALS['SWU_TEST_FORCE_PRIVATE'] = true;

$fails = 0;
$check = function ($ok, $msg) use (&$fails) { echo ($ok ? 'PASS' : 'FAIL') . ": $msg\n"; if (!$ok) $fails++; };
$handIDs = function () { $r = []; foreach (GetHand(1) as $c) { if (empty($c->removed)) $r[] = $c->CardID; } return $r; };

SetSWUVar('RNG_SEED', 'undo-stack-determinism-seed');
$mulligan = $customDQHandlers['MulliganDecision'] ?? null;
$check(is_callable($mulligan), 'MulliganDecision handler registered');

// ── Mulligan #1: undo stack has the inline begin-game snapshot (mirrors QueuePregameSetup). ──────────
UndoStackClear();
PushUndoSnapshot(1, 'pregame-step');          // Versions zone = [one snapshot]
$g->simulateRequestBoundary();                 // production canonicalization
$pre = $handIDs();
$mulligan(1, [1], 'YES');
$hand1 = $handIDs();
$check(count($hand1) === 6, 'mulligan #1 drew 6');

// ── Undo pops that snapshot → the Versions zone is now EMPTY (differs from mulligan #1's state). ─────
ob_start(); SWUDoUndo(1, 'step'); ob_end_clean();
$g->simulateRequestBoundary();
$check($handIDs() === $pre, 'undo restored the pre-mulligan hand');
// The log is APPEND-ONLY, so the undone entry is RETAINED and the CURSOR is what moved. That is still a
// real undo-subsystem state difference vs mulligan #1 (cursor 0 -> -1), which is the point: the
// reshuffle below must reproduce the same hand even though the subsystem is in a different state.
$check(UndoStackCount() === 1 && UndoCursor() === -1,
    'undo retained the entry and moved the cursor to -1 — the state difference vs mulligan #1 (got count '
    . UndoStackCount() . ', cursor ' . UndoCursor() . ')');

// ── Re-mulligan with the DIFFERENT (empty) undo stack: the reshuffle MUST reproduce the same hand. ──
$mulligan(1, [1], 'YES');
$hand2 = $handIDs();
$check($hand2 === $hand1, 'deterministic re-mulligan despite a different undo-stack (Versions) state'
    . "\n      first : " . implode(',', $hand1)
    . "\n      second: " . implode(',', $hand2));

$GLOBALS['SWU_TEST_FORCE_PRIVATE'] = false;
UndoStackClear();
array_map('unlink', glob('./Games/' . $gameName . '/*') ?: []); @rmdir('./Games/' . $gameName);
echo $fails === 0 ? "\nALL PASS\n" : "\n$fails FAILED\n";
exit($fails === 0 ? 0 : 1);

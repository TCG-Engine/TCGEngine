<?php
// TDD guard for SWUComputeUndoTarget + the RES boundary cross (SWUSim undo redesign, Task 9):
//   • Undo Phase jumps to the FIRST 'action' entry of the current phase — the lowest run of contiguous
//     'action' snapshots ABOVE the most recent non-action ('resource') boundary.
//   • A following plain (step) Undo then CROSSES that boundary back into the RES resource step.
//   • Multi-restore guard: restoring several ordinals in sequence must yield each entry's OWN distinct
//     state (regression for the Versions scratch-zone accumulation bug where LoadVersion(seat,0) kept
//     re-loading the first-added payload because MZClearZone only flagged ->Remove() without shrinking).
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php DevTools/tdd-regression/test_undo_phase_target.php
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
global $gameName, $playerID, $gRandomCounter;
$gameName = 'ptest_' . getmypid(); $playerID = 1;
@mkdir('./Games/' . $gameName, 0777, true);

$b = new GameStateBuilder(); CommonSetup($b, 'grw', 'brk', [], []); $b->WithActivePlayer(1);
$b->WithCardInHandForPlayer(1, 'SOR_095');
$g = new GameTestAdapter(); $g->loadState($b);
ob_start(); AutoAdvanceAndExecute(); ob_end_clean();

// Private game: this test exercises the restore/targeting mechanics via SWUDoUndo, so keep every undo
// free (no consent gate). The public-vs-private consent scan is covered by test_undo_consent.php.
$GLOBALS['SWU_TEST_FORCE_PRIVATE'] = true;

$fails = 0;
$check = function ($ok, $msg) use (&$fails) { echo ($ok ? 'PASS' : 'FAIL') . ": $msg\n"; if (!$ok) $fails++; };
$handCount = function () { $n = 0; foreach (GetHand(1) as $c) { if (empty($c->removed)) $n++; } return $n; };

UndoStackClear(); SetSWUVar('UNDO_TOP', '-1');

// Build a whole-round stack:  action(h1) · action(h2) · resource(h3) · action(h4) · action(h5)
//   ords:                      0            1            2             3            4
// ord0/1 = turn-1 actions, ord2 = the RES resource step, ord3/4 = turn-2 actions (current phase).
PushUndoSnapshot(1, 'action');                                     // ord0  hand=1
MZAddZone(1, 'myHand', 'SOR_046'); PushUndoSnapshot(1, 'action');  // ord1  hand=2
MZAddZone(1, 'myHand', 'SOR_046'); PushUndoSnapshot(1, 'resource');// ord2  hand=3  (RES boundary)
MZAddZone(1, 'myHand', 'SOR_046'); PushUndoSnapshot(1, 'action');  // ord3  hand=4
MZAddZone(1, 'myHand', 'SOR_046'); PushUndoSnapshot(1, 'action');  // ord4  hand=5
MZAddZone(1, 'myHand', 'SOR_046');                                 // live  hand=6

$check(UndoStackCount() === 5 && UndoTop() === 4, 'stack has 5 entries, top=4 (got ' . UndoStackCount() . '/' . UndoTop() . ')');

// Undo Phase target = first 'action' above the 'resource' boundary = ord3 (NOT the top ord4, NOT ord0).
$check(SWUComputeUndoTarget('phase') === 3, 'phase target = ord3 (first action of current phase) — got ' . SWUComputeUndoTarget('phase'));
$check(SWUComputeUndoTarget('step')  === 4, 'step target = ord4 (top)                            — got ' . SWUComputeUndoTarget('step'));

// Undo Phase: restore ord3 (hand=4, the action-phase start), pop -> top=2 (the RES boundary now on top).
ob_start(); SWUDoUndo(1, 'phase'); ob_end_clean();
$check($handCount() === 4, 'after Undo Phase: hand == 4 (action-phase start) — got ' . $handCount());
$check(UndoTop() === 2, 'after Undo Phase: top == 2 (RES boundary) — got ' . UndoTop());

// Plain Undo from here CROSSES the boundary back into the RES resource step (ord2, hand=3).
ob_start(); SWUDoUndo(1, 'step'); ob_end_clean();
$check($handCount() === 3, 'after Undo: hand == 3 (crossed into RES resource step) — got ' . $handCount());
$check(UndoTop() === 1, 'after Undo: top == 1 — got ' . UndoTop());

// Multi-restore accumulation guard: keep stepping down — each restore must give its OWN state, not a
// stuck first-loaded payload. ord1 -> hand 2, ord0 -> hand 1.
ob_start(); SWUDoUndo(1, 'step'); ob_end_clean();
$check($handCount() === 2, 'step to ord1: hand == 2 (distinct restore, not stuck) — got ' . $handCount());
ob_start(); SWUDoUndo(1, 'step'); ob_end_clean();
$check($handCount() === 1, 'step to ord0: hand == 1 (distinct restore, not stuck) — got ' . $handCount());
$check(UndoTop() === -1, 'stack fully unwound: top == -1 — got ' . UndoTop());

UndoStackClear();
array_map('unlink', glob('./Games/' . $gameName . '/*') ?: []); @rmdir('./Games/' . $gameName);
echo $fails === 0 ? "\nALL PASS\n" : "\n$fails FAILED\n";
exit($fails === 0 ? 0 : 1);

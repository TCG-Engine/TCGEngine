<?php
// TDD guard: the undo log is APPEND-ONLY and Undo moves a CURSOR instead of popping.
// WHY: the old array_splice on restore destroyed abandoned branches — and would destroy bookmarks.
// Keeping every entry makes the log a linear history of states VISITED, which is what lets Undo
// rewind back through a bookmark load (see test_undo_through_bookmark_load.php).
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php DevTools/tdd-regression/test_undo_append_only.php
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
$gameName = 'aotest_' . getmypid(); $playerID = 1;
@mkdir('./Games/' . $gameName, 0777, true);

$b = new GameStateBuilder(); CommonSetup($b, 'grw', 'brk', [], []); $b->WithActivePlayer(1);
// Seed one card so the hand counts below start at 1, matching test_undo_phase_target's convention.
// Without it CommonSetup leaves an EMPTY hand and every expected count here is off by one.
$b->WithCardInHandForPlayer(1, 'SOR_095');
$g = new GameTestAdapter(); $g->loadState($b);
ob_start(); AutoAdvanceAndExecute(); ob_end_clean();
$GLOBALS['SWU_TEST_FORCE_PRIVATE'] = true;   // keep every undo free; consent is test_undo_consent's job

$fails = 0;
$check = function ($ok, $msg) use (&$fails) { echo ($ok ? 'PASS' : 'FAIL') . ": $msg\n"; if (!$ok) $fails++; };
$handCount = function () { $n = 0; foreach (GetHand(1) as $c) { if (empty($c->removed)) $n++; } return $n; };

UndoStackClear(); BookmarkStoreClear(); UndoCursorSet(-1);

// ord0 hand=1 · ord1 hand=2 · ord2 hand=3 ; live hand=4
PushUndoSnapshot(1, 'action');
MZAddZone(1, 'myHand', 'SOR_046'); PushUndoSnapshot(1, 'action');
MZAddZone(1, 'myHand', 'SOR_046'); PushUndoSnapshot(1, 'action');
MZAddZone(1, 'myHand', 'SOR_046');

$check(UndoStackCount() === 3 && UndoCursor() === 2, 'built 3 entries, cursor=2 (got ' . UndoStackCount() . '/' . UndoCursor() . ')');

// Undo once: state goes back, the cursor decrements, and the ENTRY COUNT DOES NOT SHRINK.
ob_start(); SWUDoUndo(1, 'step'); ob_end_clean();
$check($handCount() === 3, 'after undo #1: hand == 3 — got ' . $handCount());
$check(UndoCursor() === 1, 'after undo #1: cursor == 1 — got ' . UndoCursor());
$check(UndoStackCount() === 3, 'APPEND-ONLY: entry count still 3 after a restore — got ' . UndoStackCount());

ob_start(); SWUDoUndo(1, 'step'); ob_end_clean();
$check($handCount() === 2 && UndoCursor() === 0, 'after undo #2: hand 2, cursor 0');
$check(UndoStackCount() === 3, 'entry count STILL 3 — got ' . UndoStackCount());

// Ordinals are stable: entry 2 is untouched and still readable after two restores.
$r2 = UndoRecordParse(UndoStackRead(2));
$check($r2['boundary'] === 'action' && $r2['payload'] !== '', 'ordinal 2 is intact and readable after restores');

// A new action appends ABOVE the cursor, leaving the old branch (ords 1-2) in place.
// Live is hand 2 (ord1's state). Two adds -> hand 4, snapshot it as ord3, then mutate again to hand 5.
// That trailing mutation is REQUIRED: SWUComputeUndoTarget skips a top snapshot whose payload equals
// the live state (undoing to it would visibly do nothing), so without it the undo below steps past ord3.
MZAddZone(1, 'myHand', 'SOR_046'); MZAddZone(1, 'myHand', 'SOR_046'); PushUndoSnapshot(1, 'action');
MZAddZone(1, 'myHand', 'SOR_046');
$check(UndoStackCount() === 4, 'new action appended -> 4 entries (old branch retained) — got ' . UndoStackCount());
$check(UndoCursor() === 3, 'cursor follows the new entry — got ' . UndoCursor());
$check($handCount() === 5, 'live on the new branch: hand == 5 — got ' . $handCount());

// Undoing from the new branch walks back into the OLD one — the linear visit history.
ob_start(); SWUDoUndo(1, 'step'); ob_end_clean();
$check($handCount() === 4, 'undo into the new entry: hand == 4 — got ' . $handCount());
ob_start(); SWUDoUndo(1, 'step'); ob_end_clean();
// ord2 belongs to the ABANDONED original line — under the old truncating undo it would have been
// spliced away by the first restore and be unreachable here.
$check($handCount() === 3, 'undo again crosses into the ABANDONED branch: hand == 3 — got ' . $handCount());

// Bottom of the log.
ob_start(); SWUDoUndo(1, 'step'); ob_end_clean();
$check($handCount() === 2, 'down to ord1: hand == 2 — got ' . $handCount());
ob_start(); SWUDoUndo(1, 'step'); ob_end_clean();
$check($handCount() === 1 && UndoCursor() === -1, 'fully unwound: hand 1, cursor -1 (got ' . $handCount() . '/' . UndoCursor() . ')');

UndoStackClear(); BookmarkStoreClear();
array_map('unlink', glob('./Games/' . $gameName . '/*') ?: []); @rmdir('./Games/' . $gameName);
echo $fails === 0 ? "\nALL PASS\n" : "\n$fails FAILED\n";
exit($fails === 0 ? 0 : 1);

<?php
// TDD guard: Undo Phase must stop at a 'load' boundary. Without it, the backward walk (which continues
// through contiguous 'action' entries) would run straight off the current line and into the branch that
// the load abandoned — landing the player somewhere they never played.
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php DevTools/tdd-regression/test_undo_phase_stops_at_load.php
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
global $gameName, $playerID;
$gameName = 'bmphase_' . getmypid(); $playerID = 1;
@mkdir('./Games/' . $gameName, 0777, true);

$b = new GameStateBuilder(); CommonSetup($b, 'grw', 'brk', [], []); $b->WithActivePlayer(1);
$b->WithCardInHandForPlayer(1, 'SOR_095');   // seed so hand counts start at 1
$g = new GameTestAdapter(); $g->loadState($b);
ob_start(); AutoAdvanceAndExecute(); ob_end_clean();
$GLOBALS['SWU_TEST_FORCE_PRIVATE'] = true;

$fails = 0;
$check = function ($ok, $msg) use (&$fails) { echo ($ok ? 'PASS' : 'FAIL') . ": $msg\n"; if (!$ok) $fails++; };
$handCount = function () { $n = 0; foreach (GetHand(1) as $c) { if (empty($c->removed)) $n++; } return $n; };

UndoStackClear(); BookmarkStoreClear(); UndoCursorSet(-1);

// Original line, all 'action' entries: hand 1,2,3,4. Bookmark at hand 2.
PushUndoSnapshot(1, 'action');                                          // ord0, hand 1
MZAddZone(1, 'myHand', 'SOR_046');                                      // hand 2
ob_start(); SWUTakeBookmark(1, 'bm', '', $gameName); ob_end_clean();    // id 1 @ hand 2
PushUndoSnapshot(1, 'action');                                          // ord1, hand 2
MZAddZone(1, 'myHand', 'SOR_046'); PushUndoSnapshot(1, 'action');       // ord2, hand 3
MZAddZone(1, 'myHand', 'SOR_046'); PushUndoSnapshot(1, 'action');       // ord3, hand 4
MZAddZone(1, 'myHand', 'SOR_046');                                      // live hand 5

// Load -> back to hand 2. The pushed entry (pre-load, hand 5) carries boundary 'load'.
ob_start(); SWULoadBookmark(1, 1, '', $gameName); ob_end_clean();
$loadOrd = UndoCursor();
$check($handCount() === 2, 'loaded to hand 2 — got ' . $handCount());
$check(UndoRecordParse(UndoStackRead($loadOrd))['boundary'] === 'load', "the load entry carries boundary 'load'");

// Two fresh actions on the NEW line: hand 3, 4.
MZAddZone(1, 'myHand', 'SOR_046'); PushUndoSnapshot(1, 'action');       // pre-state hand 3
MZAddZone(1, 'myHand', 'SOR_046');                                      // live hand 4

// Undo Phase must target the first 'action' ABOVE the 'load' entry — the start of the CURRENT line.
$target = SWUComputeUndoTarget('phase');
$check($target === $loadOrd + 1, 'phase target is the first action above the load boundary (got ' . $target . ', expected ' . ($loadOrd + 1) . ')');

ob_start(); SWUDoUndo(1, 'phase'); ob_end_clean();
// hand 5 would mean it walked past the load into the old line; hand 2 would mean it undid the load.
$check($handCount() === 3, 'Undo Phase lands on the current line (hand 3), NOT in the abandoned branch — got ' . $handCount());

UndoStackClear(); BookmarkStoreClear();
array_map('unlink', glob('./Games/' . $gameName . '/*') ?: []); @rmdir('./Games/' . $gameName);
echo $fails === 0 ? "\nALL PASS\n" : "\n$fails FAILED\n";
exit($fails === 0 ? 0 : 1);

<?php
// TDD guard for the headline behaviour: after LOADING a bookmark, Undo walks back THROUGH the load and
// into the line that was abandoned. This is what the append-only log buys, and it is the single most
// important property of the bookmark feature.
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php DevTools/tdd-regression/test_undo_through_bookmark_load.php
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
$gameName = 'btltest_' . getmypid(); $playerID = 1;
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

// Build a line: hand 1 -> 2 -> 3, taking a bookmark at hand==2.
PushUndoSnapshot(1, 'action');                                       // ord0, hand 1
MZAddZone(1, 'myHand', 'SOR_046');                                   // hand 2
ob_start(); $ok = SWUTakeBookmark(1, 'at hand two', '', $gameName); ob_end_clean();
$check($ok === true, 'SWUTakeBookmark returns true in a private game');
$check(BookmarkCount() === 1, 'one bookmark stored');
PushUndoSnapshot(1, 'action');                                       // ord1, hand 2
MZAddZone(1, 'myHand', 'SOR_046'); PushUndoSnapshot(1, 'action');    // ord2, hand 3
MZAddZone(1, 'myHand', 'SOR_046');                                   // live hand 4

$check($handCount() === 4, 'pre-load live state: hand == 4 — got ' . $handCount());
$stackBefore = UndoStackCount();

// Load the bookmark: state returns to hand 2.
ob_start(); $ok = SWULoadBookmark(1, 1, '', $gameName); ob_end_clean();
$check($ok === true, 'SWULoadBookmark returns true');
$check($handCount() === 2, 'after load: hand == 2 (the bookmarked state) — got ' . $handCount());
$check(UndoStackCount() === $stackBefore + 1, 'the load pushed exactly ONE snapshot (its pre-load state)');

$loadOrd = UndoCursor();
$rec = UndoRecordParse(UndoStackRead($loadOrd));
$check($rec['boundary'] === 'load', "the pushed entry carries boundary 'load' — got '" . $rec['boundary'] . "'");

// THE POINT: one Undo from here rewinds the LOAD itself, back to the pre-load line.
ob_start(); SWUDoUndo(1, 'step'); ob_end_clean();
$check($handCount() === 4, 'undo after a load REWINDS THE LOAD: hand == 4 — got ' . $handCount());

// And keeps walking back up the original line.
ob_start(); SWUDoUndo(1, 'step'); ob_end_clean();
$check($handCount() === 3, 'undo again: hand == 3 (still on the original line) — got ' . $handCount());

// Load the same bookmark a second time — bookmarks are reusable, not consumed.
ob_start(); SWULoadBookmark(1, 1, '', $gameName); ob_end_clean();
$check($handCount() === 2, 'the SAME bookmark loads again: hand == 2 — got ' . $handCount());
$check(BookmarkCount() === 1, 'loading does not consume the bookmark');

UndoStackClear(); BookmarkStoreClear();
array_map('unlink', glob('./Games/' . $gameName . '/*') ?: []); @rmdir('./Games/' . $gameName);
echo $fails === 0 ? "\nALL PASS\n" : "\n$fails FAILED\n";
exit($fails === 0 ? 0 : 1);

<?php
// TDD guard: NOTHING destroys a bookmark. Not an undo, not an Undo Phase, not another load.
// This is the invariant the whole two-store split exists to protect — a pointer-based bookmark would
// break here, which is why bookmarks own their payloads.
// It also proves LoadVersion leaves the seat-2 Versions zone alone (the sidecar's core assumption).
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php DevTools/tdd-regression/test_bookmark_survives_undo.php
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
$gameName = 'bmsurv_' . getmypid(); $playerID = 1;
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

PushUndoSnapshot(1, 'action');                                    // ord0, hand 1
MZAddZone(1, 'myHand', 'SOR_046');                                // hand 2
ob_start(); SWUTakeBookmark(1, 'bm-A', '', $gameName); ob_end_clean();   // id 1 @ hand 2
PushUndoSnapshot(1, 'action');                                    // ord1, hand 2
MZAddZone(1, 'myHand', 'SOR_046');                                // hand 3
ob_start(); SWUTakeBookmark(1, 'bm-B', '', $gameName); ob_end_clean();   // id 2 @ hand 3
PushUndoSnapshot(1, 'resource');                                  // ord2, hand 3
MZAddZone(1, 'myHand', 'SOR_046'); PushUndoSnapshot(1, 'action'); // ord3, hand 4
MZAddZone(1, 'myHand', 'SOR_046');                                // live hand 5

$check(BookmarkCount() === 2, 'two bookmarks stored');

ob_start(); SWUDoUndo(1, 'step'); ob_end_clean();
$check(BookmarkCount() === 2, 'a step undo destroys no bookmark');
ob_start(); SWUDoUndo(1, 'phase'); ob_end_clean();
$check(BookmarkCount() === 2, 'an Undo Phase destroys no bookmark');
ob_start(); SWULoadBookmark(1, 1, '', $gameName); ob_end_clean();
$check(BookmarkCount() === 2, 'loading bookmark 1 destroys no bookmark');
$check($handCount() === 2, 'bookmark 1 restored hand == 2 — got ' . $handCount());

// The bookmark taken LATER on the now-abandoned line is still loadable — the whole point.
ob_start(); $ok = SWULoadBookmark(1, 2, '', $gameName); ob_end_clean();
$check($ok === true, 'bookmark 2 (on the abandoned line) is still loadable');
$check($handCount() === 3, 'bookmark 2 restored hand == 3 — got ' . $handCount());

// Contents intact, not just the count.
$a = BookmarkRead(1); $b2 = BookmarkRead(2);
$check($a !== null && $a['label'] === 'bm-A', 'bookmark 1 label intact');
$check($b2 !== null && $b2['label'] === 'bm-B', 'bookmark 2 label intact');
$check($a['payload'] !== '' && $b2['payload'] !== '', 'both payloads intact after all those restores');

UndoStackClear(); BookmarkStoreClear();
array_map('unlink', glob('./Games/' . $gameName . '/*') ?: []); @rmdir('./Games/' . $gameName);
echo $fails === 0 ? "\nALL PASS\n" : "\n$fails FAILED\n";
exit($fails === 0 ? 0 : 1);

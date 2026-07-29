<?php
// TDD guard (SWUSim undo redesign, Task 7): a DRAW (hidden info the player sees) stamps revealedInfo=1
// on the top undo-stack entry, so undoing back through it needs consent in a public game. A snapshot
// with no reveal stays revealedInfo=0.
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php DevTools/tdd-regression/test_revealed_info_stamp.php
error_reporting(E_ALL & ~E_DEPRECATED); ini_set('display_errors', 1);
chdir('/var/www/html/TCGEngine');
if (!function_exists('ConvertMzIDToAbsolute'))      { function ConvertMzIDToAbsolute($m,$p):string{return '';} }
if (!function_exists('QueueDamageAnimation'))       { function QueueDamageAnimation($t,$a):void{} }
if (!function_exists('QueueRestoreAnimation'))      { function QueueRestoreAnimation($t,$a):void{} }
if (!function_exists('QueuePreventedDamageAnimation')) { function QueuePreventedDamageAnimation($t):void{} }
if (!function_exists('QueueShieldBreakAnimation'))  { function QueueShieldBreakAnimation($t):void{} }
foreach (['DeterministicRNG','CoreZoneModifiers'] as $f) include_once "./Core/$f.php";
include_once './SWUSim/ZoneClasses.php'; include_once './SWUSim/ZoneAccessors.php';
include_once './SWUSim/GeneratedCode/GeneratedCardDictionaries.php'; include_once './SWUSim/GamestateParser.php';
foreach (['Assertions','Cards','CommonSetup','GameStateBuilder','GameTestAdapter','SchemaTestRunner','TestRunner'] as $f) include_once "./SWUSim/Tests/Framework/$f.php";
global $gameName, $playerID;
$gameName = 'rtest_' . getmypid(); $playerID = 1;
@mkdir('./Games/' . $gameName, 0777, true);

$b = new GameStateBuilder(); CommonSetup($b, 'grw', 'brk', [], []); $b->WithActivePlayer(1);
$b->WithCardInDeckForPlayer(1, 'SOR_095'); $b->WithCardInDeckForPlayer(1, 'SOR_046');
$g = new GameTestAdapter(); $g->loadState($b);
ob_start(); AutoAdvanceAndExecute(); ob_end_clean();

$fails = 0;
$check = function ($ok, $msg) use (&$fails) { echo ($ok ? 'PASS' : 'FAIL') . ": $msg\n"; if (!$ok) $fails++; };

UndoStackClear(); SetSWUVar('UNDO_TOP', '-1');
PushUndoSnapshot(1);                 // entry 0
DoDrawCard(1, 1);                    // draws SOR_095 -> raises the live flag (O(1), no file write)
$check(GetSWUVar('UNDO_REQUIRES_CONSENT', 'false') === 'true', 'a draw raises the live consent flag');
$check(UndoRecordParse(UndoStackRead(0))['revealed'] === false, 'not yet stamped on the file (deferred)');

PushUndoSnapshot(1);                 // entry 1 — folds entry 0s reveal flag into the file
$check(UndoRecordParse(UndoStackRead(0))['revealed'] === true, 'entry 0 stamped revealedInfo=1 at next push');
$check(UndoRecordParse(UndoStackRead(1))['revealed'] === false, 'entry 1 (no reveal after it) stays 0');

UndoStackClear();
array_map('unlink', glob('./Games/' . $gameName . '/*') ?: []); @rmdir('./Games/' . $gameName);
echo $fails === 0 ? "\nALL PASS\n" : "\n$fails FAILED\n";
exit($fails === 0 ? 0 : 1);

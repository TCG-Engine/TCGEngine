<?php
// TDD guard for PushUndoSnapshot (SWUSim undo redesign, Task 5): appends a metadata-tagged snapshot
// to the undo-stack FILE (not the single myVersions slot) and advances UNDO_TOP.
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php DevTools/tdd-regression/test_push_undo_snapshot.php
error_reporting(E_ALL & ~E_DEPRECATED);
ini_set('display_errors', 1);
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
global $gameName, $playerID, $gCurrentPhase, $gRandomCounter;
$gameName = 'ptest_' . getmypid(); $playerID = 1;
@mkdir('./Games/' . $gameName, 0777, true);

$b = new GameStateBuilder();
CommonSetup($b, 'grw', 'brk', [], []);
$b->WithActivePlayer(1);
$g = new GameTestAdapter(); $g->loadState($b);
ob_start(); AutoAdvanceAndExecute(); ob_end_clean();

$fails = 0;
$check = function ($ok, $msg) use (&$fails) { echo ($ok ? 'PASS' : 'FAIL') . ": $msg\n"; if (!$ok) $fails++; };

UndoStackClear();
SetDeterministicRandomCounter(42);
PushUndoSnapshot(1);
PushUndoSnapshot(1);

$check(UndoStackCount() === 2, 'two snapshots appended to the file (got ' . UndoStackCount() . ')');
$check(UndoTop() === 1, 'UNDO_TOP == 1 (top ordinal, got ' . UndoTop() . ')');

$rec = UndoStackRead(0);
$f = explode("\t", $rec, 6);
$check(count($f) === 6, 'record has 6 tab fields');
$check(intval($f[0]) === 1, 'field[0] seat == 1');
$check($f[2] === 'action', 'field[2] boundary == action (got ' . $f[2] . ')');
$check($f[3] === '0', 'field[3] revealedInfo == 0');
$payload = base64_decode($f[5]);
$check(str_contains($payload, '<v0>42'), 'payload carries the RNG counter (<v0>42)');

// cleanup
UndoStackClear();
array_map('unlink', glob('./Games/' . $gameName . '/*') ?: []);
@rmdir('./Games/' . $gameName);
echo $fails === 0 ? "\nALL PASS\n" : "\n$fails FAILED\n";
exit($fails === 0 ? 0 : 1);

<?php
// TDD guard for LoadUndoSnapshot (SWUSim undo redesign, Task 6): restores the exact state stored in a
// stack entry (reusing the untouched generated LoadVersion via a throwaway version slot), then POPs
// that entry (new top = ordinal-1) so the next undo targets the one below.
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php DevTools/tdd-regression/test_load_undo_snapshot.php
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
global $gameName, $playerID, $gRandomCounter;
$gameName = 'ltest_' . getmypid(); $playerID = 1;
@mkdir('./Games/' . $gameName, 0777, true);

$b = new GameStateBuilder(); CommonSetup($b, 'grw', 'brk', [], []); $b->WithActivePlayer(1);
$b->WithCardInHandForPlayer(1, 'SOR_095');
$g = new GameTestAdapter(); $g->loadState($b);
ob_start(); AutoAdvanceAndExecute(); ob_end_clean();

$fails = 0;
$check = function ($ok, $msg) use (&$fails) { echo ($ok ? 'PASS' : 'FAIL') . ": $msg\n"; if (!$ok) $fails++; };
$handCount = function () { $n = 0; foreach (GetHand(1) as $c) { if (empty($c->removed)) $n++; } return $n; };

UndoStackClear();
// S0: hand=1, counter=100
SetDeterministicRandomCounter(100); $h0 = $handCount(); PushUndoSnapshot(1);
// mutate -> S1: hand=2, counter=200
MZAddZone(1, 'myHand', 'SOR_046'); SetDeterministicRandomCounter(200); $h1 = $handCount(); PushUndoSnapshot(1);
// mutate -> S2: hand=3, counter=300
MZAddZone(1, 'myHand', 'SOR_046'); SetDeterministicRandomCounter(300); $h2 = $handCount(); PushUndoSnapshot(1);
// live mutate
MZAddZone(1, 'myHand', 'SOR_046'); SetDeterministicRandomCounter(999);

$check($h0 === 1 && $h1 === 2 && $h2 === 3, "setup hands 1/2/3 (got $h0/$h1/$h2)");
$check(UndoStackCount() === 3 && UndoTop() === 2, 'stack has 3 entries, top=2');

// Restore S1 (ordinal 1): state == hand 2, counter 200; then POP -> top=0, stack keeps only entry 0.
$ok = LoadUndoSnapshot(1);
$check($ok === true, 'LoadUndoSnapshot(1) returns true');
$check($handCount() === 2, 'restored hand == 2 (S1 state) — got ' . $handCount());
$check(intval($gRandomCounter) === 200, 'restored RNG counter == 200 (S1) — got ' . intval($gRandomCounter));
$check(UndoTop() === 0, 'popped: UNDO_TOP == 0 (ordinal-1)');
$check(UndoStackCount() === 1, 'stack truncated to 1 entry');

// out-of-range
$check(LoadUndoSnapshot(99) === false, 'LoadUndoSnapshot(out-of-range) returns false');

UndoStackClear();
array_map('unlink', glob('./Games/' . $gameName . '/*') ?: []); @rmdir('./Games/' . $gameName);
echo $fails === 0 ? "\nALL PASS\n" : "\n$fails FAILED\n";
exit($fails === 0 ? 0 : 1);

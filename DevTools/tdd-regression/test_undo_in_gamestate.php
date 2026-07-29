<?php
// TDD guard: the multi-step undo stack now lives IN the serialized gamestate (player 1's Versions zone), so
// it survives a WriteGamestate -> ParseGamestate round-trip. This is what lets a bug report that captures
// the gamestate carry the whole undo history (begin-game / round / action snapshots) for later reload.
// Also covers the storage primitives (append/count/read/truncate/clear) on the Versions-zone backing.
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php DevTools/tdd-regression/test_undo_in_gamestate.php
error_reporting(E_ALL & ~E_DEPRECATED); ini_set('display_errors', 1);
chdir('/var/www/html/TCGEngine');
if (!function_exists('ConvertMzIDToAbsolute'))      { function ConvertMzIDToAbsolute($m,$p):string{return '';} }
if (!function_exists('QueueDamageAnimation'))       { function QueueDamageAnimation($t,$a):void{} }
if (!function_exists('QueueRestoreAnimation'))      { function QueueRestoreAnimation($t,$a):void{} }
if (!function_exists('QueuePreventedDamageAnimation')) { function QueuePreventedDamageAnimation($t):void{} }
if (!function_exists('QueueShieldBreakAnimation'))  { function QueueShieldBreakAnimation($t):void{} }
foreach (['DeterministicRNG','CoreZoneModifiers','GameAuth'] as $f) include_once "./Core/$f.php";
include_once './Core/RegressionTestFramework.php';
include_once './SWUSim/ZoneClasses.php'; include_once './SWUSim/ZoneAccessors.php';
include_once './SWUSim/GeneratedCode/GeneratedCardDictionaries.php'; include_once './SWUSim/GamestateParser.php';
foreach (['Assertions','Cards','CommonSetup','GameStateBuilder','GameTestAdapter','SchemaTestRunner','TestRunner'] as $f) include_once "./SWUSim/Tests/Framework/$f.php";
global $gameName, $playerID;
$gameName = 'uround_' . getmypid(); $playerID = 1;
@mkdir('./SWUSim/Games/' . $gameName, 0777, true);

$b = new GameStateBuilder(); CommonSetup($b, 'grw', 'brk', [], []); $b->WithActivePlayer(1);
$b->WithCardInHandForPlayer(1, 'SOR_095');
$g = new GameTestAdapter(); $g->loadState($b);
ob_start(); AutoAdvanceAndExecute(); ob_end_clean();

$fails = 0;
$check = function ($ok, $msg) use (&$fails) { echo ($ok ? 'PASS' : 'FAIL') . ": $msg\n"; if (!$ok) $fails++; };
$handCount = function () { $n = 0; foreach (GetHand(1) as $c) { if (empty($c->removed)) $n++; } return $n; };

// ── storage primitives on the Versions-zone backing ──
UndoStackClear();
$check(UndoStackCount() === 0 && UndoTop() === -1, 'empty after clear (count 0, top -1)');
$rec1 = "1\tMAIN\taction\t0\t" . base64_encode('name') . "\t" . base64_encode("payload-ONE\nwith\ttabs+\x00");
$rec2 = "2\tRES\tresource\t1\t" . base64_encode('') . "\t" . base64_encode(str_repeat('X', 400));
UndoStackAppend($rec1); UndoStackAppend($rec2);
$check(UndoStackCount() === 2 && UndoTop() === 1, 'two appends -> count 2, top 1');
$check(UndoStackRead(1) === $rec2, 'read(1) exact (tabs + base64 round-trip)');
$check(UndoStackRead(0) === $rec1, 'read(0) exact');
$check(UndoStackRead(5) === null, 'read out-of-range -> null');
UndoStackTruncateTo(0);
$check(UndoStackCount() === 1 && UndoStackRead(0) === $rec1, 'truncateTo(0) keeps only entry 0');

// ── the point: real snapshots survive a gamestate round-trip ──
UndoStackClear(); SetSWUVar('UNDO_TOP', '-1');
PushUndoSnapshot(1, 'pregame-step');                        // ord0 hand=1 (begin game)
MZAddZone(1, 'myHand', 'SOR_046'); PushUndoSnapshot(1, 'action');   // ord1 hand=2
MZAddZone(1, 'myHand', 'SOR_046'); PushUndoSnapshot(1, 'resource'); // ord2 hand=3 (round boundary)
MZAddZone(1, 'myHand', 'SOR_046');                          // live hand=4
$check(UndoStackCount() === 3, 'built a 3-entry stack');

ob_start(); WriteGamestate('./SWUSim/'); ob_end_clean();     // serialize (Versions zones included)
$vz = &GetVersions(1); $vz = [];                             // wipe the stack from memory
$check(UndoStackCount() === 0, 'stack wiped from memory');
if (function_exists('RegressionClearGamestateMemory')) RegressionClearGamestateMemory($gameName); // force file read
ob_start(); ParseGamestate('./SWUSim/'); ob_end_clean();     // reload the gamestate

$check(UndoStackCount() === 3, 'undo stack RESTORED from the gamestate (count 3) — rides in Gamestate.txt');
$r0 = UndoRecordParse(UndoStackRead(0)); $r2 = UndoRecordParse(UndoStackRead(2));
$check(($r0['boundary'] ?? '') === 'pregame-step', 'restored ord0 boundary == pregame-step');
$check(($r2['boundary'] ?? '') === 'resource', 'restored ord2 boundary == resource');

// And the restored stack still drives undo: rewind to begin game (ord0) -> hand 1.
ob_start(); LoadUndoSnapshot(0); ob_end_clean();
$check($handCount() === 1, 'LoadUndoSnapshot(0) on the RESTORED stack rewinds to begin-game hand==1 (got ' . $handCount() . ')');

UndoStackClear();
array_map('unlink', glob('./SWUSim/Games/' . $gameName . '/*') ?: []); @rmdir('./SWUSim/Games/' . $gameName);
echo $fails === 0 ? "\nALL PASS\n" : "\n$fails FAILED\n";
exit($fails === 0 ? 0 : 1);

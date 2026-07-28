<?php
// TDD guard for pregame-step snapshot wiring (SWUSim undo redesign, Task 12): QueuePregameSetup must queue
// a PushPregameSnapshot undo boundary before EACH mulligan decision and before EACH starting-resource pick,
// and the handler must actually append a 'pregame-step' entry to the undo stack. Together with
// test_mulligan_determinism.php (the reproducible-redraw invariant) this proves a real game records the
// pregame boundaries a player undoes back through.
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php DevTools/tdd-regression/test_pregame_snapshots.php
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
include_once './SWUSim/CreateGame.php';   // QueuePregameSetup — the pregame decision-queue builder
global $gameName, $playerID, $customDQHandlers;
$gameName = 'pgtest_' . getmypid(); $playerID = 1;
@mkdir('./Games/' . $gameName, 0777, true);

// A normal 2-player setup (not goldfish) so BOTH seats get a mulligan + two resource picks.
$b = new GameStateBuilder(); CommonSetup($b, 'grw', 'brk', [], []); $b->WithActivePlayer(1);
foreach (['SOR_200','SOR_201','SOR_202','SOR_203','SOR_204','SOR_205'] as $h) { $b->WithCardInHandForPlayer(1, $h); $b->WithCardInHandForPlayer(2, $h); }
foreach (['SOR_095','SOR_046','SOR_100','SOR_101','SOR_102','SOR_103'] as $d) { $b->WithCardInDeckForPlayer(1, $d); $b->WithCardInDeckForPlayer(2, $d); }
$g = new GameTestAdapter(); $g->loadState($b);
ob_start(); AutoAdvanceAndExecute(); ob_end_clean();

$fails = 0;
$check = function ($ok, $msg) use (&$fails) { echo ($ok ? 'PASS' : 'FAIL') . ": $msg\n"; if (!$ok) $fails++; };

$check(isset($customDQHandlers['PushPregameSnapshot']) && is_callable($customDQHandlers['PushPregameSnapshot']),
    'PushPregameSnapshot handler is registered');

// Queue the pregame decisions and count the snapshot boundaries across both players' queues.
SetSWUVar('RNG_SEED', 'seed'); UndoStackClear(); SetSWUVar('UNDO_TOP', '-1');
ob_start(); QueuePregameSetup(1); ob_end_clean();
$countSnapshots = 0;
foreach ([1, 2] as $p) {
    foreach (GetDecisionQueue($p) as $d) {
        if (empty($d->removed) && strpos(serialize($d), 'PushPregameSnapshot') !== false) $countSnapshots++;
    }
}
// Expected per non-goldfish player: 1 (mulligan) + 2 (resource picks) = 3; two players = 6.
$check($countSnapshots === 6, "queued 6 pregame-step boundaries (1 mulligan + 2 resource × 2 players), got $countSnapshots");

// The handler actually appends a 'pregame-step' entry.
$before = UndoStackCount();
$customDQHandlers['PushPregameSnapshot'](1, [1], '-');
$after = UndoStackCount();
$check($after === $before + 1, "handler appended one stack entry ($before -> $after)");
$line = UndoStackRead(UndoTop());
$rec  = $line === null ? [] : UndoRecordParse($line);
$check(($rec['boundary'] ?? '') === 'pregame-step', "appended entry has boundary 'pregame-step' (got '" . ($rec['boundary'] ?? '') . "')");

UndoStackClear();
array_map('unlink', glob('./Games/' . $gameName . '/*') ?: []); @rmdir('./Games/' . $gameName);
echo $fails === 0 ? "\nALL PASS\n" : "\n$fails FAILED\n";
exit($fails === 0 ? 0 : 1);

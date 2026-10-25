<?php
// Regression: after a mulligan, ONE Undo returns to the mulligan prompt.
// Bug (2026-10-10): the first Undo looked like it did nothing. The undo point before the starting-resource
// pick (PushPregameSnapshot) was taken while the queue still held the CUSTOM ChooseStartingResource step,
// which expands into the "choose 2 cards to resource" prompt a moment later in the same request. The saved
// state therefore never byte-matched the live one, so SWUComputeUndoTarget's no-op skip could not fire;
// undoing to it re-ran the step and rebuilt the same prompt, and only a SECOND Undo reached the mulligan.
// The fix re-takes that snapshot once ChooseStartingResource has built its prompt.
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php DevTools/tdd-regression/test_undo_after_mulligan_one_step.php
error_reporting(E_ALL & ~E_DEPRECATED); ini_set('display_errors', 1);
chdir('/var/www/html/TCGEngine');
if (!function_exists('ConvertMzIDToAbsolute'))      { function ConvertMzIDToAbsolute($m,$p):string{return '';} }
if (!function_exists('QueueDamageAnimation'))       { function QueueDamageAnimation($t,$a):void{} }
if (!function_exists('QueueRestoreAnimation'))      { function QueueRestoreAnimation($t,$a):void{} }
if (!function_exists('QueuePreventedDamageAnimation')) { function QueuePreventedDamageAnimation($t):void{} }
if (!function_exists('QueueShieldBreakAnimation'))  { function QueueShieldBreakAnimation($t):void{} }
foreach (['DeterministicRNG','CoreZoneModifiers','GameAuth','DecisionQueueController'] as $f) include_once "./Core/$f.php";
include_once './SWUSim/ZoneClasses.php'; include_once './SWUSim/ZoneAccessors.php';
include_once './SWUSim/GeneratedCode/GeneratedCardDictionaries.php'; include_once './SWUSim/GamestateParser.php';
foreach (['Assertions','Cards','CommonSetup','GameStateBuilder','GameTestAdapter','SchemaTestRunner','TestRunner'] as $f) include_once "./SWUSim/Tests/Framework/$f.php";
include_once './SWUSim/CreateGame.php';
global $gameName, $playerID;
$gameName = 'uamo_' . getmypid(); $playerID = 1;
@mkdir('./Games/' . $gameName, 0777, true);

// A normal 2-player setup, as test_pregame_snapshots builds it.
$b = new GameStateBuilder(); CommonSetup($b, 'grw', 'brk', [], []); $b->WithActivePlayer(1);
foreach (['SOR_200','SOR_201','SOR_202','SOR_203','SOR_204','SOR_205'] as $h) { $b->WithCardInHandForPlayer(1, $h); $b->WithCardInHandForPlayer(2, $h); }
foreach (['SOR_095','SOR_046','SOR_100','SOR_101','SOR_102','SOR_103','SOR_104','SOR_105'] as $d) { $b->WithCardInDeckForPlayer(1, $d); $b->WithCardInDeckForPlayer(2, $d); }
$g = new GameTestAdapter(); $g->loadState($b);
ob_start(); AutoAdvanceAndExecute(); ob_end_clean();
$GLOBALS['SWU_TEST_FORCE_PRIVATE'] = true;

$fails = 0;
$check = function ($ok, $msg) use (&$fails) { echo ($ok ? 'PASS' : 'FAIL') . ": $msg\n"; if (!$ok) $fails++; };
$front = function (int $p) { $q = array_values(array_filter((array)GetDecisionQueue($p), fn($d) => empty($d->removed))); return $q[0] ?? null; };
$handIDs = function () { $r = []; foreach (GetHand(1) as $c) { if (empty($c->removed)) $r[] = $c->CardID; } return $r; };

SetSWUVar('RNG_SEED', 'undo-after-mulligan'); UndoStackClear(); UndoCursorSet(-1);
// Mirror QueuePregameSetup's own caller: the hand is already dealt, so just queue the decisions.
ob_start(); QueuePregameSetup(1); ob_end_clean();
$g->simulateRequestBoundary();
$f = $front(1);
$check($f && $f->Type === 'YESNO' && str_contains((string)$f->Param, 'mulligan'), 'P1 is on the mulligan prompt');
$opening = $handIDs();

// P1 answers YES the way input mode 100 does: pop the YESNO, run the statics behind it.
ob_start();
$dq = new DecisionQueueController(); $dq->PopDecision(1); $dq->ExecuteStaticMethods(1, 'YES');
ob_end_clean();
$g->simulateRequestBoundary();
$f = $front(1);
$check($f && $f->Type === 'MZMULTICHOOSE', 'after the mulligan P1 is on the resource prompt');
$check($handIDs() !== $opening, 'the mulligan drew a new hand');

ob_start(); SWUDoUndo(1, 'step'); ob_end_clean();
$g->simulateRequestBoundary();
$f = $front(1);
$check($f && $f->Type === 'YESNO' && str_contains((string)$f->Param, 'mulligan') && $handIDs() === $opening,
    'ONE Undo is back on the mulligan prompt with the opening hand (front decision: ' . ($f ? $f->Type . ' ' . $f->Param : 'none') . ')');

// The undo point before the resource pick keeps its job: pick, Undo once → back on the resource prompt.
ob_start();
$dq = new DecisionQueueController(); $dq->PopDecision(1); $dq->ExecuteStaticMethods(1, 'YES');   // mulligan again
ob_end_clean();
$g->simulateRequestBoundary();
$picked = $handIDs();
ob_start();
$dq = new DecisionQueueController(); $dq->PopDecision(1); $dq->ExecuteStaticMethods(1, 'myHand-0&myHand-1');
ob_end_clean();
$g->simulateRequestBoundary();
$check(SWUResourceCount(1) === 2, 'P1 resourced two cards');
ob_start(); SWUDoUndo(1, 'step'); ob_end_clean();
$g->simulateRequestBoundary();
$f = $front(1);
$check($f && $f->Type === 'MZMULTICHOOSE' && SWUResourceCount(1) === 0 && $handIDs() === $picked,
    'ONE Undo after the resource pick is back on the resource prompt with the cards in hand (front: ' . ($f ? $f->Type : 'none') . ', resources ' . SWUResourceCount(1) . ')');

$GLOBALS['SWU_TEST_FORCE_PRIVATE'] = false;
UndoStackClear();
array_map('unlink', glob('./Games/' . $gameName . '/*') ?: []); @rmdir('./Games/' . $gameName);
echo $fails === 0 ? "\nALL PASS\n" : "\n$fails FAILED\n";
exit($fails === 0 ? 0 : 1);

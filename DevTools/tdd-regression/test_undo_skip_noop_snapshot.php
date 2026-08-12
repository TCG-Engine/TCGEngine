<?php
// Regression: an Undo must not "do nothing". A snapshot whose stored state equals the CURRENT state is a
// no-op undo point — e.g. the pregame pre-resource PushPregameSnapshot, captured right before the resource
// pick, matches the live pre-pick state. Bug (prod game 33 / #897): right after the mulligan, the FIRST
// Undo landed on that no-op snapshot (nothing visible) and only a SECOND Undo reached the mulligan prompt.
// SWUComputeUndoTarget must skip such no-op top snapshots so one Undo reverts to the first DIFFERING state.
// Verified across a request boundary (prod: each interactive decision ends the request).
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php DevTools/tdd-regression/test_undo_skip_noop_snapshot.php
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
$gameName = 'noop_' . getmypid(); $playerID = 1;
@mkdir('./Games/' . $gameName, 0777, true);

$b = new GameStateBuilder(); CommonSetup($b, 'grw', 'brk', [], []); $b->WithActivePlayer(1);
$b->WithCardInHandForPlayer(1, 'SOR_095');
$g = new GameTestAdapter(); $g->loadState($b);
ob_start(); AutoAdvanceAndExecute(); ob_end_clean();
$GLOBALS['SWU_TEST_FORCE_PRIVATE'] = true;

$fails = 0;
$check = function ($ok, $msg) use (&$fails) { echo ($ok ? 'PASS' : 'FAIL') . ": $msg\n"; if (!$ok) $fails++; };
$handCount = function () { $n = 0; foreach (GetHand(1) as $c) { if (empty($c->removed)) $n++; } return $n; };

UndoStackClear();
// ord0 = state S0 (hand 1). Then mutate to S1 (hand 2) and snapshot ord1 = S1. No action occurs after
// ord1, so the LIVE state == ord1's stored state (the no-op undo point — like pregame's pre-resource snap).
$h = &GetHand(1); array_splice($h, 0); MZAddZone(1, 'myHand', 'SOR_046');   // hand = 1  (S0)
PushUndoSnapshot(1, 'pregame-step');                                        // ord0 = S0
MZAddZone(1, 'myHand', 'SOR_046');                                          // hand = 2  (S1)
PushUndoSnapshot(1, 'pregame-step');                                        // ord1 = S1 (== current live state)

$check(UndoStackCount() === 2 && UndoCursor() === 1, 'stack = [ord0 S0, ord1 S1], cursor=1');
$g->simulateRequestBoundary();   // prod: the interactive decision that follows ends the request

// The top (ord1) equals the current state → it's a no-op; a 'step' undo must skip it and target ord0.
$check(SWUComputeUndoTarget('step') === 0, 'step target SKIPS the no-op top (ord1==current) → ord0 (got ' . SWUComputeUndoTarget('step') . ')');

// One Undo must actually change something: revert to S0 (hand 1), not sit on S1 (hand 2).
ob_start(); SWUDoUndo(1, 'step'); ob_end_clean();
$check($handCount() === 1, 'ONE undo reverts to S0 (hand 1) — not a no-op (got ' . $handCount() . ')');

$GLOBALS['SWU_TEST_FORCE_PRIVATE'] = false;
UndoStackClear();
array_map('unlink', glob('./Games/' . $gameName . '/*') ?: []); @rmdir('./Games/' . $gameName);
echo $fails === 0 ? "\nALL PASS\n" : "\n$fails FAILED\n";
exit($fails === 0 ? 0 : 1);

<?php
// TDD guard for SWUUndoNeedsConsent (SWUSim undo redesign, Task 10): the private-free / public-request
// consent gate. PRIVATE games never need consent. In PUBLIC games an undo needs an opponent request when
// it (a) reverts a revealed-info action, (b) reverts an opponent's action, (c) crosses a phase boundary,
// or is an Undo Phase (always). A public own-within-phase no-info undo stays free.
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php DevTools/tdd-regression/test_undo_consent.php
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
$gameName = 'ctest_' . getmypid(); $playerID = 1;
@mkdir('./Games/' . $gameName, 0777, true);

$b = new GameStateBuilder(); CommonSetup($b, 'grw', 'brk', [], []); $b->WithActivePlayer(1);
$b->WithCardInHandForPlayer(1, 'SOR_095');
$g = new GameTestAdapter(); $g->loadState($b);
ob_start(); AutoAdvanceAndExecute(); ob_end_clean();

$fails = 0;
$check = function ($ok, $msg) use (&$fails) { echo ($ok ? 'PASS' : 'FAIL') . ": $msg\n"; if (!$ok) $fails++; };

// Build a stack: P1 action(ord0) · P1 action(ord1) · resource(ord2) · P1 action(ord3) · P2 action(ord4).
// Seats/boundaries are the levers the scan reads; the payload contents don't matter for consent.
$push = function (int $seat, string $boundary, bool $revealed) {
    // Emulate a reveal on the action just taken (folded into the current top at the next push).
    if ($revealed) SetSWUVar('UNDO_REQUIRES_CONSENT', 'true');
    PushUndoSnapshot($seat, $boundary);
};
UndoStackClear(); UndoCursorSet(-1); SetSWUVar('UNDO_REQUIRES_CONSENT', 'false');
$push(1, 'action',   false); // ord0  P1
$push(1, 'action',   false); // ord1  P1
$push(1, 'resource', false); // ord2  RES boundary
$push(1, 'action',   false); // ord3  P1
$push(2, 'action',   false); // ord4  P2 acted (opponent) — its reveal flag folds at next push
$push(1, 'action',   false); // ord5  P1 (folds ord4's non-reveal); now cursor=5, all live flags clear

$top = UndoCursor();
$check($top === 5, "stack cursor == 5 (got $top)");

// ── PRIVATE: always free, whatever it crosses ──
$GLOBALS['SWU_TEST_FORCE_PRIVATE'] = true;
$check(SWUUndoNeedsConsent(1, 0, 'step')  === false, 'PRIVATE step across everything → free');
$check(SWUUndoNeedsConsent(1, 3, 'phase') === false, 'PRIVATE phase → free');

// ── PUBLIC ──
$GLOBALS['SWU_TEST_FORCE_PRIVATE'] = false;
// Own, within-phase (ords 5..5 = P1 action, no reveal, no boundary) → FREE.
$check(SWUUndoNeedsConsent(1, 5, 'step') === false, 'PUBLIC own within-phase no-info step → free');
// Reverting to ord4 pulls in P2's action (opponent) → REQUEST.
$check(SWUUndoNeedsConsent(1, 4, 'step') === true,  'PUBLIC undo crossing opponent action → request');
// Reverting to ord2 pulls in the resource boundary (crosses phase) → REQUEST.
$check(SWUUndoNeedsConsent(1, 2, 'step') === true,  'PUBLIC undo crossing phase boundary → request');
// Undo Phase is always a request in public.
$check(SWUUndoNeedsConsent(1, 3, 'phase') === true, 'PUBLIC Undo Phase → request');
// Live reveal flag (this turn's most recent action revealed info) → REQUEST even for own within-phase.
SetSWUVar('UNDO_REQUIRES_CONSENT', 'true');
$check(SWUUndoNeedsConsent(1, 5, 'step') === true,  'PUBLIC own step after revealing info → request');
SetSWUVar('UNDO_REQUIRES_CONSENT', 'false');

// A folded reveal on a reverted record → REQUEST. Stamp ord5's revealed bit and revert into it.
UndoStackSetRevealed(5);
$check(SWUUndoNeedsConsent(1, 5, 'step') === true,  'PUBLIC undo of a folded-revealed action → request');

$GLOBALS['SWU_TEST_FORCE_PRIVATE'] = false;
UndoStackClear();
array_map('unlink', glob('./Games/' . $gameName . '/*') ?: []); @rmdir('./Games/' . $gameName);
echo $fails === 0 ? "\nALL PASS\n" : "\n$fails FAILED\n";
exit($fails === 0 ? 0 : 1);

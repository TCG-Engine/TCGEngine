<?php
// Guard for BugReportLoadStateLib.php (behind zzBugReportViewer.php's Load Current/Last Round/Game Begin).
// Builds a known whole-round undo stack (mirrors test_undo_phase_target) and checks each load mode maps to
// the right ordinal and LoadUndoSnapshot restores that ordinal's state.
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php DevTools/tdd-regression/test_bugreport_load_state.php
error_reporting(E_ALL & ~E_DEPRECATED); ini_set('display_errors', 1);
chdir('/var/www/html/TCGEngine');
if (!function_exists('ConvertMzIDToAbsolute'))         { function ConvertMzIDToAbsolute($m,$p):string{return '';} }
if (!function_exists('QueueDamageAnimation'))          { function QueueDamageAnimation($t,$a):void{} }
if (!function_exists('QueueRestoreAnimation'))         { function QueueRestoreAnimation($t,$a):void{} }
if (!function_exists('QueuePreventedDamageAnimation')) { function QueuePreventedDamageAnimation($t):void{} }
if (!function_exists('QueueShieldBreakAnimation'))     { function QueueShieldBreakAnimation($t):void{} }
foreach (['DeterministicRNG','CoreZoneModifiers','GameAuth'] as $f) include_once "./Core/$f.php";
include_once './SWUSim/ZoneClasses.php'; include_once './SWUSim/ZoneAccessors.php';
include_once './SWUSim/GeneratedCode/GeneratedCardDictionaries.php'; include_once './SWUSim/GamestateParser.php';
foreach (['Assertions','Cards','CommonSetup','GameStateBuilder','GameTestAdapter','SchemaTestRunner','TestRunner'] as $f) include_once "./SWUSim/Tests/Framework/$f.php";
require_once './SWUSim/DevTools/BugReportLoadStateLib.php';

global $gameName, $playerID;
$gameName = 'brls_' . getmypid(); $playerID = 1;
@mkdir('./Games/' . $gameName, 0777, true);

$b = new GameStateBuilder(); CommonSetup($b, 'grw', 'brk', [], []); $b->WithActivePlayer(1);
$b->WithCardInHandForPlayer(1, 'SOR_095');
$g = new GameTestAdapter(); $g->loadState($b);
ob_start(); AutoAdvanceAndExecute(); ob_end_clean();

$fails = 0;
$check = function ($ok, $msg) use (&$fails) { echo ($ok ? 'PASS' : 'FAIL') . ": $msg\n"; if (!$ok) $fails++; };
$handCount = function () { $n = 0; foreach (GetHand(1) as $c) { if (empty($c->removed)) $n++; } return $n; };

// Rebuild the same whole-round stack test_undo_phase_target uses:
//   ord0 action(hand1) · ord1 action(hand2) · ord2 resource(hand3) · ord3 action(hand4) · ord4 action(hand5) · live(hand6)
// 'phase' target = ord3 (first action above the resource boundary) = start of the current round's action phase.
$build = function () {
    $h = &GetHand(1); array_splice($h, 0);          // empty the hand
    MZAddZone(1, 'myHand', 'SOR_046');               // hand=1
    UndoStackClear();
    PushUndoSnapshot(1, 'action');                                    // ord0 hand=1
    MZAddZone(1, 'myHand', 'SOR_046'); PushUndoSnapshot(1, 'action');  // ord1 hand=2
    MZAddZone(1, 'myHand', 'SOR_046'); PushUndoSnapshot(1, 'resource');// ord2 hand=3
    MZAddZone(1, 'myHand', 'SOR_046'); PushUndoSnapshot(1, 'action');  // ord3 hand=4
    MZAddZone(1, 'myHand', 'SOR_046'); PushUndoSnapshot(1, 'action');  // ord4 hand=5
    MZAddZone(1, 'myHand', 'SOR_046');                                 // live hand=6
};

// ── mode → ordinal mapping ──────────────────────────────────────────────────
$build();
$check(UndoStackCount() === 5, 'built a 5-entry whole-round stack (got ' . UndoStackCount() . ')');
$check(SWUBugReportUndoOrdinalForMode('current')    === -1, "mode current → -1 (no step)");
$check(SWUBugReportUndoOrdinalForMode('begin')      === 0,  "mode begin → ordinal 0 (game start)");
$check(SWUBugReportUndoOrdinalForMode('last-round') === 3,  "mode last-round → ordinal 3 (current round's action-phase start), got " . SWUBugReportUndoOrdinalForMode('last-round'));

// ── stepping restores the right state ───────────────────────────────────────
$build();
$r = SWUBugReportStepLoadedGamestate('current');
$check($r['stepped'] === false && $handCount() === 6, "current: no step, live state kept (hand 6) — got " . $handCount());

$build();
$r = SWUBugReportStepLoadedGamestate('last-round');
$check($r['stepped'] === true && $r['ordinal'] === 3 && $handCount() === 4, "last-round: restored ord3 (hand 4) — got " . $handCount());
$check($r['boundary'] === 'action', "last-round: boundary is 'action' (action-phase start) — got '" . $r['boundary'] . "'");

$build();
$r = SWUBugReportStepLoadedGamestate('begin');
$check($r['stepped'] === true && $r['ordinal'] === 0 && $handCount() === 1, "begin: restored ord0 (hand 1) — got " . $handCount());

// ── empty stack → every mode is a no-op (no fatal on reports without undo) ───
UndoStackClear();
$check(SWUBugReportUndoOrdinalForMode('begin') === -1 && SWUBugReportUndoOrdinalForMode('last-round') === -1, 'empty stack → no step for any mode');
$r = SWUBugReportStepLoadedGamestate('last-round');
$check($r['stepped'] === false, 'empty stack: step is a no-op, no fatal');

UndoStackClear();
array_map('unlink', glob('./Games/' . $gameName . '/*') ?: []); @rmdir('./Games/' . $gameName);
echo $fails === 0 ? "\nALL PASS\n" : "\n$fails FAILED\n";
exit($fails === 0 ? 0 : 1);

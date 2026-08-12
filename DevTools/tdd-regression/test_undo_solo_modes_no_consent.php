<?php
// TDD guard: ONE-PLAYER modes never require undo consent.
//
// Goldfish's seat 2 is a passive bot and Hotseat is one person playing both seats, so a consent
// request in either can NEVER be answered — it hangs Undo Phase forever.
//
// WHY THIS ISN'T ALREADY COVERED BY THE PRIVATE-GAME GATE: goldfish/hotseat lobbies DO set
// isPrivate=true (APIs/Lobbies/JoinQueue.php), but that flag lives ONLY in APCu with a one-hour TTL
// (SIM_GAME_RECORD_CACHE_TTL) and no disk fallback. Once it expires — or PHP restarts —
// SimGameReadAuthKeys falls back to SimGameDefaultAuthKeys(), whose isPrivate is FALSE, and the game
// silently reads as public. Verified against a real goldfish game on disk (2143 -> isPrivate:false).
// The game MODE, by contrast, is a GlobalEffect inside the gamestate: durable, and restored with it.
// So the consent gate must key on the mode, not on the privacy flag.
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php DevTools/tdd-regression/test_undo_solo_modes_no_consent.php
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
$gameName = 'solomode_' . getmypid(); $playerID = 1;
@mkdir('./Games/' . $gameName, 0777, true);

$b = new GameStateBuilder(); CommonSetup($b, 'grw', 'brk', [], []); $b->WithActivePlayer(1);
$b->WithCardInHandForPlayer(1, 'SOR_095');
$g = new GameTestAdapter(); $g->loadState($b);
ob_start(); AutoAdvanceAndExecute(); ob_end_clean();

$fails = 0;
$check = function ($ok, $msg) use (&$fails) { echo ($ok ? 'PASS' : 'FAIL') . ": $msg\n"; if (!$ok) $fails++; };
$handCount = function () { $n = 0; foreach (GetHand(1) as $c) { if (empty($c->removed)) $n++; } return $n; };

// PUBLIC — i.e. the APCu entry has expired and the game no longer reports as private. This is the
// exact state a goldfish game lands in after an hour.
$GLOBALS['SWU_TEST_FORCE_PRIVATE'] = false;

$clearModes = function () {
    foreach (['SWU_MODE_GOLDFISH', 'SWU_MODE_HOTSEAT'] as $m) { while (RemoveGlobalEffect(1, $m)) {} }
};

// ── control: a NORMAL public 2-player game still requires consent for Undo Phase ─────────────
$clearModes();
UndoStackClear(); BookmarkStoreClear(); UndoCursorSet(-1);
PushUndoSnapshot(1, 'action');
MZAddZone(1, 'myHand', 'SOR_046'); PushUndoSnapshot(1, 'action');
MZAddZone(1, 'myHand', 'SOR_046');
$check(SWUGameMode() === '', 'control: no solo mode set (got "' . SWUGameMode() . '")');
$check(SWUUndoNeedsConsent(1, 0, 'phase') === true,
    'CONTROL — a public 2-player game DOES still require consent for Undo Phase');
// Without this control the solo assertions below could pass because consent was disabled everywhere.

// ── goldfish: no consent, and the undo actually applies ──────────────────────────────────────
$clearModes(); AddGlobalEffects(1, 'SWU_MODE_GOLDFISH');
$check(SWUGameMode() === 'goldfish', 'goldfish mode active (got "' . SWUGameMode() . '")');
$check(SWUUndoNeedsConsent(1, 0, 'phase') === false, 'GOLDFISH: Undo Phase needs NO consent');
$check(SWUUndoNeedsConsent(1, 0, 'step')  === false, 'GOLDFISH: step Undo needs NO consent');

// End to end: the undo must actually apply, not queue a request nobody can answer.
$before = $handCount();
ob_start(); SWUDoUndo(1, 'phase'); ob_end_clean();
$check($handCount() < $before, 'GOLDFISH: Undo Phase actually applied (hand ' . $before . ' -> ' . $handCount() . ')');
$check(GetSWUVar('PENDING_UNDO_FROM', '') === '', 'GOLDFISH: no pending consent request was queued');

// ── hotseat: same ────────────────────────────────────────────────────────────────────────────
$clearModes(); AddGlobalEffects(1, 'SWU_MODE_HOTSEAT');
UndoStackClear(); BookmarkStoreClear(); UndoCursorSet(-1);
PushUndoSnapshot(1, 'action');
MZAddZone(1, 'myHand', 'SOR_046'); PushUndoSnapshot(1, 'action');
MZAddZone(1, 'myHand', 'SOR_046');
$check(SWUGameMode() === 'hotseat', 'hotseat mode active (got "' . SWUGameMode() . '")');
$check(SWUUndoNeedsConsent(1, 0, 'phase') === false, 'HOTSEAT: Undo Phase needs NO consent');
$before = $handCount();
ob_start(); SWUDoUndo(1, 'phase'); ob_end_clean();
$check($handCount() < $before, 'HOTSEAT: Undo Phase actually applied (hand ' . $before . ' -> ' . $handCount() . ')');
$check(GetSWUVar('PENDING_UNDO_FROM', '') === '', 'HOTSEAT: no pending consent request was queued');

// ── a revealed-info flag must NOT resurrect consent in a solo mode ───────────────────────────
// MarkUndoRequiresConsent fires on every draw/peek, and it is checked before the per-entry scan —
// so the solo short-circuit has to sit above it, not below.
$clearModes(); AddGlobalEffects(1, 'SWU_MODE_GOLDFISH');
MarkUndoRequiresConsent();
$check(GetSWUVar('UNDO_REQUIRES_CONSENT', 'false') === 'true', 'reveal flag is set');
$check(SWUUndoNeedsConsent(1, 0, 'step') === false, 'GOLDFISH: a revealed-info action still needs NO consent');

$clearModes();
UndoStackClear(); BookmarkStoreClear();
array_map('unlink', glob('./Games/' . $gameName . '/*') ?: []); @rmdir('./Games/' . $gameName);
echo $fails === 0 ? "\nALL PASS\n" : "\n$fails FAILED\n";
exit($fails === 0 ? 0 : 1);

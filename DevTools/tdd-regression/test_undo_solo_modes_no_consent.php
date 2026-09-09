<?php
// TDD guard: ONE-PLAYER modes never require undo consent.
//
// Goldfish's seat 2 is a passive bot, Hotseat is one person playing both seats, and Bot Practice's
// seat 2 is driven by Core/BotController.php — so a consent request in any of the three can NEVER be
// answered and hangs Undo Phase forever.
//
// ⚠ BOT PRACTICE IS THE SHARP ONE. Its seat 2 is a REAL seat (real deck, real mulligan, losable base)
// so it is tempting to treat it as a two-player game. It is not, for undo: "solo" here means "only one
// seat can ANSWER A PROMPT". The undo-approval popup is driven by a SWU var read in
// GameLayoutShared.php, NOT by a DecisionQueue entry, so the bot has no path to it at all — the
// request is not merely slow, it is unanswerable.
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
    foreach (['SWU_MODE_GOLDFISH', 'SWU_MODE_HOTSEAT', 'SWU_MODE_BOTPRACTICE'] as $m) { while (RemoveGlobalEffect(1, $m)) {} }
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

// ── bot practice: same, and for the same reason — the bot cannot answer ──────────────────────
// This is the case that was MISSING when SWUIsSoloMode() listed only goldfish and hotseat. It did not
// fail immediately: SWUGameIsPrivate() is `SWUIsSoloMode() || SimGameIsPrivateGame()`, and the lobby
// really is created with isPrivate=true — but that flag lives only in APCu (1h TTL, no disk fallback),
// so the bug surfaced an hour in, or on the next PHP restart. Forcing PUBLIC above is what makes the
// gap visible on the first run instead of the second hour.
$clearModes(); AddGlobalEffects(1, 'SWU_MODE_BOTPRACTICE');
UndoStackClear(); BookmarkStoreClear(); UndoCursorSet(-1);
SetSWUVar('UNDO_REQUIRES_CONSENT', 'false');
PushUndoSnapshot(1, 'action');
MZAddZone(1, 'myHand', 'SOR_046'); PushUndoSnapshot(1, 'action');
MZAddZone(1, 'myHand', 'SOR_046');
$check(SWUGameMode() === 'botpractice', 'botpractice mode active (got "' . SWUGameMode() . '")');
$check(SWUIsSoloMode() === true, 'BOTPRACTICE: counts as a solo (one-decision-maker) mode');
$check(SWUGameIsPrivate() === true, 'BOTPRACTICE: reads as private even with the APCu record gone');
$check(SWUUndoNeedsConsent(1, 0, 'phase') === false, 'BOTPRACTICE: Undo Phase needs NO consent');
$check(SWUUndoNeedsConsent(1, 0, 'step')  === false, 'BOTPRACTICE: step Undo needs NO consent');
$before = $handCount();
ob_start(); SWUDoUndo(1, 'phase'); ob_end_clean();
$check($handCount() < $before, 'BOTPRACTICE: Undo Phase actually applied (hand ' . $before . ' -> ' . $handCount() . ')');
// The popup this guards is aimed at the REQUESTER'S OPPONENT (GameLayoutShared.php). At seat 2 that
// is the bot, which never polls for it — so a queued request here is a permanent hang, not a delay.
$check(GetSWUVar('PENDING_UNDO_FROM', '') === '', 'BOTPRACTICE: no pending consent request was queued for the bot seat');

// Seat 2 (the BOT) asking for the undo must be just as consent-free: whichever seat requests, the
// approval would land on a party that cannot answer.
$check(SWUUndoNeedsConsent(2, 0, 'phase') === false, 'BOTPRACTICE: the bot seat also needs no consent');

// ── a revealed-info flag must NOT resurrect consent in a solo mode ───────────────────────────
// MarkUndoRequiresConsent fires on every draw/peek, and it is checked before the per-entry scan —
// so the solo short-circuit has to sit above it, not below.
$clearModes(); AddGlobalEffects(1, 'SWU_MODE_GOLDFISH');
MarkUndoRequiresConsent();
$check(GetSWUVar('UNDO_REQUIRES_CONSENT', 'false') === 'true', 'reveal flag is set');
$check(SWUUndoNeedsConsent(1, 0, 'step') === false, 'GOLDFISH: a revealed-info action still needs NO consent');

$clearModes(); AddGlobalEffects(1, 'SWU_MODE_BOTPRACTICE');
MarkUndoRequiresConsent();
$check(GetSWUVar('UNDO_REQUIRES_CONSENT', 'false') === 'true', 'reveal flag is set (botpractice)');
$check(SWUUndoNeedsConsent(1, 0, 'step') === false, 'BOTPRACTICE: a revealed-info action still needs NO consent');

$clearModes();
UndoStackClear(); BookmarkStoreClear();
array_map('unlink', glob('./Games/' . $gameName . '/*') ?: []); @rmdir('./Games/' . $gameName);
echo $fails === 0 ? "\nALL PASS\n" : "\n$fails FAILED\n";
exit($fails === 0 ? 0 : 1);

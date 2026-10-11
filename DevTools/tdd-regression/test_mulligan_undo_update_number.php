<?php
// Regression: mulligan → Undo → mulligan again must redraw the SAME hand in a real game.
// Bug (2026-10-10, reproduced end to end on a goldfish game): the two mulligans ran on identical state
// (hand, deck, decision queue, RNG counter all restored by Undo) yet drew different hands, because the
// deterministic-RNG material (EngineSnapshotState) included $updateNumber — the client sync counter,
// which every request bumps and which Undo can never roll back. test_mulligan_undo_stack_determinism
// missed it: it runs in one process, where $updateNumber never moves.
// The fix is versioned (RNG_V = 2, set when a SWUSim game is seeded) so games created before it — and
// their input replays, which re-run old shuffles — keep the old formula.
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php DevTools/tdd-regression/test_mulligan_undo_update_number.php
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
include_once './SWUSim/CreateGame.php';   // SWUEnsureRandomnessSeed — what LoadPlayerDeck calls for a new game
global $gameName, $playerID, $customDQHandlers, $updateNumber;
$gameName = 'muun_' . getmypid(); $playerID = 1;
@mkdir('./Games/' . $gameName, 0777, true);

$b = new GameStateBuilder(); CommonSetup($b, 'grw', 'brk', [], []); $b->WithActivePlayer(1);
$deckIDs = ['SOR_095','SOR_046','SOR_100','SOR_101','SOR_102','SOR_103','SOR_104','SOR_105',
            'SOR_106','SOR_107','SOR_108','SOR_109','SOR_110','SOR_111','SOR_112','SOR_113'];
foreach (['SOR_200','SOR_201','SOR_202','SOR_203','SOR_204','SOR_205'] as $h) $b->WithCardInHandForPlayer(1, $h);
foreach ($deckIDs as $d) $b->WithCardInDeckForPlayer(1, $d);
$g = new GameTestAdapter(); $g->loadState($b);
ob_start(); AutoAdvanceAndExecute(); ob_end_clean();
$GLOBALS['SWU_TEST_FORCE_PRIVATE'] = true;

$fails = 0;
$check = function ($ok, $msg) use (&$fails) { echo ($ok ? 'PASS' : 'FAIL') . ": $msg\n"; if (!$ok) $fails++; };
$handIDs = function () { $r = []; foreach (GetHand(1) as $c) { if (empty($c->removed)) $r[] = $c->CardID; } return $r; };
$mulligan = $customDQHandlers['MulliganDecision'] ?? null;
$check(is_callable($mulligan), 'MulliganDecision handler registered');

// ── An OLD game (seeded before the fix, no RNG_V): the formula is unchanged, update number included. ──
SetSWUVar('RNG_SEED', 'mulligan-update-number-seed');
$check(array_key_exists('updateNumber', EngineSnapshotState()), 'a game without RNG_V keeps $updateNumber in the RNG material (old games and their replays unchanged)');

// ── A NEW game: seeded the way LoadPlayerDeck seeds it. ──────────────────────────────────────────
SWUEnsureRandomnessSeed();
$check(GetSWUVar('RNG_SEED') === 'mulligan-update-number-seed', 'an existing seed is kept (a preset rngSeed still wins)');
$check(!array_key_exists('updateNumber', EngineSnapshotState()), 'a new game leaves $updateNumber out of the RNG material');

UndoStackClear();
PushUndoSnapshot(1, 'pregame-step');
$g->simulateRequestBoundary();
$pre = $handIDs();
$updateNumber = 1;                              // the request that answers the first mulligan
$mulligan(1, [1], 'YES');
$hand1 = $handIDs();
$check(count($hand1) === 6 && $hand1 !== $pre, 'mulligan #1 drew a new 6-card hand');

ob_start(); SWUDoUndo(1, 'step'); ob_end_clean();
$g->simulateRequestBoundary();
$check($handIDs() === $pre, 'undo restored the pre-mulligan hand');

$updateNumber = 4;                              // later requests (the undo, poll writes …) moved it on
$mulligan(1, [1], 'YES');
$hand2 = $handIDs();
$check($hand2 === $hand1, 'the re-mulligan after undo draws the same hand although $updateNumber moved'
    . "\n      first : " . implode(',', $hand1)
    . "\n      second: " . implode(',', $hand2));

$GLOBALS['SWU_TEST_FORCE_PRIVATE'] = false;
UndoStackClear();
array_map('unlink', glob('./Games/' . $gameName . '/*') ?: []); @rmdir('./Games/' . $gameName);
echo $fails === 0 ? "\nALL PASS\n" : "\n$fails FAILED\n";
exit($fails === 0 ? 0 : 1);

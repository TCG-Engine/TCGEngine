<?php
// Shared bootstrap for the Phase 1a bot tests (SWUSim/DevTools/tests/bot_*_test.php). Lives in fixtures/
// because the DevTools test runner executes only the top-level *.php files of the tests folder.
// Adapted from DevTools/tdd-regression/test_bookmark_survives_undo.php — that file's include list is the
// source of truth for loading the SWUSim engine into a CLI test.
//
// Provides: $gameName, $check(bool, string), $build(fn(GameStateBuilder)), $raiseAttack(int, string),
//           $act(int $seat, int $mode, string $cardID), $botCtx(string $style, int $seat = 1) and
//           bot_test_finish().
error_reporting(E_ALL & ~E_DEPRECATED); ini_set('display_errors', 1);
chdir('/var/www/html/TCGEngine');
// The REAL action runner, not stubs: the bot enumerator loads DevTools/TestAutomationBridge.php, which
// requires this file, and its helpers (ConvertMzIDToAbsolute, Queue*Animation) would then be redeclared
// over the stubs below. DevTools/SWUSimBotSelfPlayTest.php loads it the same way. The stubs stay as a
// guard for any helper a future runner version stops defining.
include_once './Core/EngineActionRunner.php';
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
$gameName = 'bottest_' . getmypid(); $playerID = 1;
@mkdir('./Games/' . $gameName, 0777, true);

$GLOBALS['BOT_TEST_FAILS'] = 0;
$check = function ($ok, $msg) { echo ($ok ? 'PASS' : 'FAIL') . ": $msg\n"; if (!$ok) $GLOBALS['BOT_TEST_FAILS']++; };

// ⚠ TESTS WRITE INTO THE PRODUCTION CORPUS. A botpractice fixture records to SWUSim/BotData/<gameName>
// — the same directory the owner's real Arenabot games land in. bot_test_finish() cleans up, but a run
// that FAILS partway or fatals never reaches it, and two such runs left `bottest_*` games sitting in a
// real 5-game corpus, where they showed up in the analysis report as a sixth game with an unknown bot
// style. A shutdown hook cleans up on every exit path, and it only ever removes a directory whose name
// starts with the harness prefix, so it can never touch a real game.
register_shutdown_function(function () {
    $g = preg_replace('/[^A-Za-z0-9_]/', '', strval($GLOBALS['gameName'] ?? ''));
    if ($g === '' || strpos($g, 'bottest_') !== 0) return;
    // Relative to the chdir() this bootstrap performs above — __DIR__ is three levels deep
    // (SWUSim/DevTools/tests/fixtures) and an off-by-one there silently cleans nothing.
    $dir = './SWUSim/BotData/' . $g;
    if (!is_dir($dir)) return;
    array_map('unlink', glob($dir . '/*') ?: []);
    @rmdir($dir);
});

// Build and load a board (seat 1 active, action phase), then settle the pregame the way the harness does.
$build = function (callable $setup) {
    $b = new GameStateBuilder(); CommonSetup($b, 'grw', 'brk', [], []);
    $b->WithActivePlayer(1); $b->WithGamePhase('MAIN');
    $setup($b);
    $g = new GameTestAdapter(); $g->loadState($b);
    ob_start(); AutoAdvanceAndExecute(); ob_end_clean();
    return $g;
};

// Raise a REAL prompt: declare an attack the way GameTestAdapter::declareAttack() does, but stop before it
// injects an answer, so the attack-target decision (or an On Attack choice) is left pending.
// Mirrors declareAttack() + its private _drainDQ().
$raiseAttack = function (int $player, string $attackerMz) {
    global $playerID;
    $saved = $playerID; $playerID = $player;
    ob_start();
    if (ActionMap($attackerMz) === 'ATTACK') (new DecisionQueueController())->ExecuteStaticMethods($player, '-');
    ob_end_clean();
    $playerID = $saved;
};

// Take a REAL action — a play (10002 "myHand-i!FSM!"), a CustomInput verb (10001) or a decision answer
// (100) — through the bot stack's own dispatcher, the one the lookahead uses, without the restore.
// Needs SWUSim/Custom/BotLookahead.php loaded by the caller.
$act = function (int $seat, int $mode, string $cardID) {
    global $playerID;
    $saved = $playerID;
    ob_start(); _SWUBotLookaheadDispatch($seat, ['mode' => $mode, 'cardID' => $cardID]); ob_end_clean();
    $playerID = $saved;
};

// The stack's context for $seat, built from the real enumerator (never hand-crafted). Needs
// SWUSim/BotLegalActions.php loaded by the caller.
$botCtx = function (string $style, int $seat = 1) {
    global $gameName;
    $l = SWUBotLegalActions($gameName, $seat);
    return ['style' => $style, 'seat' => $seat, 'opp' => $seat === 1 ? 2 : 1, 'kind' => strval($l['kind'] ?? ''),
            'type' => strval($l['decisionType'] ?? ''), 'param' => strval($l['decisionParam'] ?? ''),
            'tooltip' => strval($l['decisionTooltip'] ?? ''), 'following' => (array)($l['following'] ?? []),
            'actions' => (array)($l['actions'] ?? [])];
};

function bot_test_finish(): void {
    global $gameName;
    array_map('unlink', glob('./Games/' . $gameName . '/*') ?: []); @rmdir('./Games/' . $gameName);
    $fails = intval($GLOBALS['BOT_TEST_FAILS']);
    echo $fails === 0 ? "\nALL PASS\n" : "\n$fails FAILED\n";
    exit($fails === 0 ? 0 : 1);
}

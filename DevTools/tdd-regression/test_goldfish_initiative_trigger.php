<?php
// TDD guard: in a GOLDFISH game, claiming the initiative with a "When you take the initiative"
// trigger that asks the player a question must not hang the request.
//
// Bug report (2026-08-04): goldfish, ASH_014 The Mandalorian ("When you take the initiative: you may
// pay 1 resource. If you do, draw a card."), decline the mulligan, take the initiative as the first
// action -> "Mando's ability is broken."
//
// Chain: SWUTakeInitiative queues P1's YESNO, then SWUPassAction(1) -> SWUSwapTurnPlayer -> the
// goldfish auto-pass SWUPassAction(2). That second pass tries to end the action phase, but
// AdvanceAndExecute("PASS") returns false (P1's YESNO makes EvaluateTransition PENDING_DECISION), so
// it queues SWU_RETRY_ENDPHASE. The retry used to go on $player == 2 -- a seat whose queue holds
// nothing interactive -- so DecisionQueueController::ExecuteStaticMethods(2) popped the retry, ran
// it, had it re-queue itself, popped it again... forever. ExecuteStaticMethods has no cycle cap, so
// the web request spins until max_execution_time and the game looks frozen.
//
// A normal 2-player game never hit this: there the retry lands in the CLAIMING player's own queue,
// behind their own interactive prompt, so the drain blocks instead of spinning.
//
// Not Mandalorian-specific -- ASH_155 Grogu, SEC_168 Ziton Moj and HMW_168 Ezra Bridger all queue an
// interactive "when you take the initiative" decision and hang goldfish the same way.
//
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php -d xdebug.mode=off \
//     DevTools/tdd-regression/test_goldfish_initiative_trigger.php
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
include_once './SWUSim/CreateGame.php';   // QueuePregameSetup — the real pregame decision-queue builder
global $gameName, $playerID, $customDQHandlers, $gTurnPlayer;

$fails = 0;
$check = function ($ok, $msg) use (&$fails) { echo ($ok ? 'PASS' : 'FAIL') . ": $msg\n"; if (!$ok) $fails++; };

// Stand up a goldfish game the way SWUSetupGame does, run the real pregame, and stop at the first
// action of round 1. $leaderID is P1's leader.
$startGoldfish = function (string $leaderID) {
    global $gameName, $playerID, $gTurnPlayer;
    $gameName = 'gfinit_' . getmypid() . '_' . substr(md5($leaderID), 0, 6);
    $playerID = 1;
    @mkdir('./Games/' . $gameName, 0777, true);

    $b = new GameStateBuilder();
    CommonSetup($b, 'grw', 'brk', ['baseCardID' => 'JTL_021', 'leaderCardID' => $leaderID], []);
    $b->WithActivePlayer(1);
    for ($i = 0; $i < 20; $i++) $b->WithCardInDeckForPlayer(1, 'SOR_095');
    $g = new GameTestAdapter(); $g->loadState($b);
    ob_start(); AutoAdvanceAndExecute(); ob_end_clean();

    AddGlobalEffects(1, 'SWU_MODE_GOLDFISH');       // what CreateGame writes for a goldfish lobby
    SetInitiativeCounter('P1_UNCLAIMED');
    $gTurnPlayer = 1;
    $cp = &GetCurrentPhase(); $cp = 'APS'; unset($cp);
    SetPhaseParameters('-');
    SetSWUVar('RNG_SEED', 'goldfish-initiative-seed');
    $p2h = &GetHand(2); $p2h = []; unset($p2h);      // the passive seat loads no deck
    $p2d = &GetDeck(2); $p2d = []; unset($p2d);

    ob_start(); QueuePregameSetup(1); AdvanceAndExecute("PASS"); AutoAdvanceAndExecute(); ob_end_clean();
    $answer = function (string $ans) use ($g) {
        global $playerID; $playerID = 1;
        $g->answerDecision(1, $ans);
        ob_start();                                  // production drains both queues after every action
        $dq = new DecisionQueueController();
        foreach ([1, 2] as $q) $dq->ExecuteStaticMethods($q, '-');
        AutoAdvanceAndExecute();
        ob_end_clean();
    };
    $answer('NO');                                   // decline the mulligan
    $answer('myHand-0&myHand-1');                    // the two starting resources
    return [$g, $answer];
};

$liveHand = function (int $p) { $n = 0; foreach (GetHand($p) as $c) if (empty($c->removed)) $n++; return $n; };
$readyRes = function (int $p) { $n = 0; foreach (GetResources($p) as $r) if (empty($r->removed) && intval($r->Status ?? 0) === 1) $n++; return $n; };
$pending  = function (int $p) { return array_values(array_filter(GetDecisionQueue($p), fn($d) => empty($d->removed))); };

// Claim the initiative with the goldfish post-action drain, but abort instead of spinning if
// SWU_RETRY_ENDPHASE re-queues itself without bound. Returns the invocation count (-1 = runaway).
$claimWithRetryCap = function ($g) {
    global $customDQHandlers;
    $calls = 0;
    $orig  = $customDQHandlers['SWU_RETRY_ENDPHASE'];
    $customDQHandlers['SWU_RETRY_ENDPHASE'] = function ($p, $parts, $ld) use ($orig, &$calls) {
        if (++$calls > 25) throw new RuntimeException('runaway');
        $orig($p, $parts, $ld);
    };
    try {
        ob_start(); $g->takeInitiative(1); ProcessGoldfishAutomation(); ob_end_clean();
    } catch (Throwable $e) {
        ob_end_clean();
        $customDQHandlers['SWU_RETRY_ENDPHASE'] = $orig;
        return -1;
    }
    $customDQHandlers['SWU_RETRY_ENDPHASE'] = $orig;
    return $calls;
};

// ── THE BUG: a goldfish claim with an interactive initiative trigger must not spin ───────────
[$g, $answer] = $startGoldfish('ASH_014');
$handBefore = $liveHand(1);
$resBefore  = $readyRes(1);
$calls = $claimWithRetryCap($g);
$check($calls !== -1, 'goldfish claim with ASH_014 does not spin SWU_RETRY_ENDPHASE (ran ' .
    ($calls === -1 ? '25+, RUNAWAY' : $calls) . ' times)');

// The prompt survives the drain and is still P1's to answer.
$q = $pending(1);
$check(!empty($q) && ($q[0]->Type ?? '') === 'YESNO', "P1's pay-to-draw prompt is still pending after the drain");
$check(GetCurrentPhase() === 'MAIN', 'action phase has not ended while the prompt is unanswered (got ' . GetCurrentPhase() . ')');

// Answering YES pays 1 resource and draws 1 — and then the round actually ends.
$answer('YES');
$check($liveHand(1) === $handBefore + 1 + 2,
    'YES draws 1 (then the regroup draw of 2): hand ' . $handBefore . ' -> ' . $liveHand(1));
$check($readyRes(1) === $resBefore - 1 || GetCurrentPhase() !== 'MAIN',
    'YES exhausted a resource to pay for the draw');
$check(GetCurrentPhase() !== 'MAIN', 'the action phase ended once the prompt was answered (got ' . GetCurrentPhase() . ')');
array_map('unlink', glob('./Games/' . $gameName . '/*') ?: []); @rmdir('./Games/' . $gameName);

// ── Regression: a goldfish claim with NO initiative trigger still ends the round ─────────────
[$g2, $answer2] = $startGoldfish('SOR_005');
$calls2 = $claimWithRetryCap($g2);
$check($calls2 !== -1, 'goldfish claim without an initiative trigger does not spin');
$check(GetCurrentPhase() !== 'MAIN', 'plain goldfish claim ends the action phase immediately (got ' . GetCurrentPhase() . ')');
array_map('unlink', glob('./Games/' . $gameName . '/*') ?: []); @rmdir('./Games/' . $gameName);

echo ($fails === 0 ? "PASS (7 checks)\n" : "FAIL: $fails check(s)\n");

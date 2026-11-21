<?php
// 4P Always Two — the game-start votes (spec "## 4P Always Two" → "Votes at game start"; plan 2026-11-21-swusim-always-two-4p Task 3).
// The roll picks a TEAM; BOTH its teammates vote, blind and at the same time, to go first or second. Same answers → it is decided; different
// answers → both are told they disagree and vote again, until they match. Then the team that goes first votes, the same way, on WHICH of
// its boards starts with the initiative. Control: 1v1 Always Two keeps one person answering for the team.
//   docker exec -w /var/www/html/TCGEngine -e XDEBUG_MODE=off otmtcge-swusim-web-server-1 php -d apc.enable_cli=1 SWUSim/DevTools/tests/alwaystwo4p_start_votes_test.php
require __DIR__ . '/fixtures/bot_test_bootstrap.php';

$game = function (string $mode) use ($build) {
    return $build(function ($b) use ($mode) {
        $b->WithSeatOrder('1234'); $b->WithLiveSeats('1234');
        $b->WithGlobalEffectForPlayer(1, 'SWU_MODE_TEAMS');
        $b->WithGlobalEffectForPlayer(1, 'SWU_MODE_ALWAYSTWO');
        $b->WithGlobalEffectForPlayer(1, $mode === '4p' ? 'SWU_ALWAYSTWO_VOTE' : 'SWU_ALWAYSTWO_SHARED_SEAT');
    });
};
$head = function (int $p): ?object { foreach (GetDecisionQueue($p) as $d) if (empty($d->removed)) return $d; return null; };
$tip  = fn(int $p) => str_replace('_', ' ', strval(($GLOBALS['__h'] = $head($p)) ? $GLOBALS['__h']->Tooltip : ''));
$answer = function (int $p, string $v) {
    $dq = new DecisionQueueController();
    ob_start(); $dq->PopDecision($p); $dq->ExecuteStaticMethods($p, $v); ob_end_clean();
};
$roll = function () { ob_start(); QueueInitiativeChoice(1, 'roll'); ob_end_clean(); };   // the roll went to Red (board 1's team)

echo "BothAskedAtOnce:\n";
$game('4p'); $roll();
$check($head(1) && $head(1)->Type === 'OPTIONCHOOSE' && $head(3) && $head(3)->Type === 'OPTIONCHOOSE', 'both Red boards are asked at once');
$check($head(2) === null && $head(4) === null, 'Blue is not asked');
$check($tip(1) === 'Your team won the initiative roll. Go first or second?', 'the team prompt: "' . $tip(1) . '"');

echo "FirstAnswerWaits:\n";
$answer(1, 'Go_first');
$check($head(3) && $head(3)->Type === 'OPTIONCHOOSE' && $head(1) === null, "board 1's vote waits for board 3; nothing decided");
$check(strpos(strval(GetDecisionQueueVariables()), 'SWU_PRIVATE_A2_VOTE_initiative_1') !== false
       && strpos(strval(GetDecisionQueueVariables()), 'Go_first') !== false, 'the waiting vote is held under a SWU_PRIVATE_ key (stripped from every screen — Task 5)');

echo "Agree_GoFirst_ThenBoardVote:\n";
$answer(3, 'Go_first');
$check($head(1) && $head(3) && $head(1)->Type === 'OPTIONCHOOSE' && strpos(strval($head(1)->Param), 'Board_1') !== false
       && strpos(strval($head(3)->Param), 'Board_3') !== false, 'agreed → both Red boards vote which board starts');
$answer(1, strval(explode('&', strval($head(1) ? $head(1)->Param : ''))[1] ?? 'none'));
$answer(3, strval(explode('&', strval($head(3) ? $head(3)->Param : ''))[1] ?? 'none'));   // board 3's option label, exactly as offered
$check(GetTurnPlayer() === 3 && GetInitiativeCounter() === 'P3_UNCLAIMED', 'both picked board 3 → board 3 starts with the initiative (turn ' . GetTurnPlayer() . ', ' . GetInitiativeCounter() . ')');

echo "Disagree_BothReasked:\n";
$game('4p'); $roll();
$answer(1, 'Go_first'); $answer(3, 'Go_second');
$check($head(1) && $head(3) && $head(1)->Type === 'OPTIONCHOOSE' && $head(3)->Type === 'OPTIONCHOOSE', 'disagreed → both asked again');
$check($tip(1) === 'Your team disagreed. Go first or second?' && $tip(3) === 'Your team disagreed. Go first or second?', 'with the disagree prompt: "' . $tip(1) . '"');

echo "Disagree_ThenAgree_GoSecond_BlueVotesTheBoard:\n";
$answer(1, 'Go_second'); $answer(3, 'Go_second');
$check($head(2) && $head(4) && strpos(strval($head(2)->Param), 'Board_2') !== false && $head(1) === null && $head(3) === null,
       'Red agreed to go second → BOTH Blue boards vote which Blue board starts');

echo "BoardVote_Disagree_Reasked:\n";
$opt = fn(int $p, int $i) => strval(explode('&', strval($head($p) ? $head($p)->Param : ''))[$i] ?? 'none');
$answer(2, $opt(2, 0)); $answer(4, $opt(4, 1));
$check($head(2) && $head(4) && $tip(2) === 'Your team disagreed. Which board starts with the initiative?', 'a split board vote is asked again: "' . $tip(2) . '"');

echo "VotesStayBlind_InTheLog:\n";
// Production runs GameOnDecisionAnswered (the CHOICE log line) BEFORE popping the answer (Core/EngineActionRunner mode 100). A team
// vote must not be announced there — board 3 would read "P1 chose Go second" before casting its own vote (final review 2026-11-21).
$game('4p'); $roll();
ob_start(); GameOnDecisionAnswered(1, 'Go_second'); ob_end_clean();
$answer(1, 'Go_second');
global $gGameLog;
$seen3 = implode(' | ', array_map(fn($e) => explode('|', $e, 4)[3] ?? '', SWUFilterGameLogForViewer($gGameLog, 3, false)));
$check(strpos($seen3, 'chose') === false, 'board 3 cannot read board 1\'s vote in the log before voting: "' . substr($seen3, -120) . '"');

echo "Control_OneVsOne_OneAnswerDecides:\n";
$game('1v1'); $roll();
$check($head(1) && $head(1)->Type === 'OPTIONCHOOSE' && $head(3) === null, '1v1: only board 1 (the person) is asked');
$answer(1, 'Go_first');
$check($head(1) && strpos(strval($head(1)->Param), 'Board_3') !== false && $head(3) === null, '1v1: the same person picks the starting board');

$f = $GLOBALS['BOT_TEST_FAILS'];
echo $f === 0 ? "ALL PASS\n" : "$f FAILED\n";
exit($f === 0 ? 0 : 1);

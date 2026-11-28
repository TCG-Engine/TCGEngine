<?php
// 4P Always Two — the partner vote, the parts the schema cases (SWUSim/Tests/Cases/alwaystwo/PartnerVote.md) cannot reach:
// the prompt for every main-action kind, a Pass on the prompt (= Decline), and an accepted action that is no longer legal.
// Spec docs/superpowers/specs/2026-11-20-swusim-always-two-design.md "## 4P Always Two"; plan 2026-11-21-swusim-always-two-4p Task 2.
//   docker exec -w /var/www/html/TCGEngine -e XDEBUG_MODE=off otmtcge-swusim-web-server-1 php -d apc.enable_cli=1 SWUSim/DevTools/tests/alwaystwo4p_vote_test.php
require __DIR__ . '/fixtures/bot_test_bootstrap.php';

$fourP = function (callable $more) use ($build) {
    return $build(function ($b) use ($more) {
        $b->WithSeatOrder('1234'); $b->WithLiveSeats('1234');
        $b->WithGlobalEffectForPlayer(1, 'SWU_MODE_TEAMS');
        $b->WithGlobalEffectForPlayer(1, 'SWU_MODE_ALWAYSTWO');
        $b->WithGlobalEffectForPlayer(1, 'SWU_ALWAYSTWO_VOTE');
        $more($b);
    });
};
$name = fn(?object $o) => $o ? SWUPromptCardName(strval($o->CardID)) : '?';
global $playerID;

echo "PromptForEveryKind:\n";
$fourP(function ($b) {
    $b->WithGroundUnitForPlayer(1, 'SOR_095');
    $b->WithCardInHandForPlayer(1, 'SOR_120');                       // a non-unique upgrade; board 1's Marine is the only unit anywhere
    $b->WithCardInDiscardForPlayer(1, 'SOR_095');
    $b->WithCardInDiscardForPlayer(2, 'SOR_098');
    $b->WithControlledResourceForPlayer(1, 'SOR_128', 1);
});
$playerID = 1;
$leader = $name(GetLeader(1)[0] ?? null);
$unit = $name(GetZoneObject('myGroundArena-0'));
$cases = [
    ['widget', 'myLeader-0', 'DeployLeader',  "P1 wants to deploy {$leader}. Accept?"],
    ['widget', 'myLeader-0', 'LeaderAbility', "P1 wants to use {$leader}'s ability. Accept?"],
    ['widget', 'myGroundArena-0', 'Activate', "P1 wants to use {$unit}'s ability. Accept?"],
    ['widget', 'myHand-0', 'Activate:x',      'P1 wants to use ' . $name(GetZoneObject('myHand-0')) . "'s ability. Accept?"],
    ['widget', 'myBase-0', 'EpicAction',      'P1 wants to use ' . $name(GetZoneObject('myBase-0')) . "'s ability. Accept?"],
    ['widget', 'myResources-0', 'Smuggle',    'P1 wants to play ' . $name(GetZoneObject('myResources-0')) . ' with Smuggle. Accept?'],
    ['widget', 'PlayFromDiscard-0', '',       'P1 wants to play ' . $name(GetZoneObject('myDiscard-0')) . ' from the discard pile. Accept?'],
    ['widget', 'PlayFromOpponentDiscard-2-0', '', 'P1 wants to play ' . $name(GetZoneObject('p2Discard-0')) . " from an opponent's discard pile. Accept?"],
    ['widget', 'myHealth-0', 'Pass',          'P1 wants to pass. Accept?'],
    ['widget', 'InitiativeCounter-0', 'TakeInitiative', 'P1 wants to take the initiative. Accept?'],
    ['map', 'myHand-0', '',                   'P1 wants to play ' . $name(GetZoneObject('myHand-0')) . " on {$unit}. Accept?"],
];
foreach ($cases as [$entry, $card, $action, $want]) {
    $got = SWUVotePromptText(1, $entry, $card, $action);
    $check($got === $want, "$entry $card $action → \"$got\"" . ($got === $want ? '' : " (want \"$want\")"));
}

echo "PassOnThePrompt_IsADecline:\n";
$fourP(function ($b) {
    $b->WithCardInHandForPlayer(1, 'SOR_095');
    for ($i = 0; $i < 4; $i++) $b->WithControlledResourceForPlayer(1, 'SOR_128', 1);
});
$playerID = 1;
$hand0 = count(GetHand(1));
ob_start(); $r = ActionMap('myHand-0'); ob_end_clean();
$check($r === 'PROPOSED' && SWUVotePending(1) !== null, "board 1's play is proposed (ActionMap → $r)");
$dq = new DecisionQueueController();
ob_start(); $dq->PopDecision(3); $dq->ExecuteStaticMethods(3, 'PASS'); ob_end_clean();
$check(SWUVotePending(1) === null, 'a Pass on the prompt clears the proposal');
$check(count(GetHand(1)) === $hand0 && count(GetGroundArena(1)) === 0, 'a Pass on the prompt runs nothing (card still in hand)');
$check(GetTurnPlayer() === 1, 'Red still has its action');

echo "AcceptedButNowIllegal_NothingHappens_TeamKeepsItsAction:\n";
$fourP(function ($b) {
    $b->WithCardInHandForPlayer(1, 'SOR_095');
    for ($i = 0; $i < 4; $i++) $b->WithControlledResourceForPlayer(1, 'SOR_128', 1);
});
$playerID = 1;
$hand0 = count(GetHand(1));
ob_start(); ActionMap('myHand-0'); ob_end_clean();
foreach (GetResources(1) as $res) $res->Status = 0;             // the proposer can no longer pay
$dq = new DecisionQueueController();
ob_start(); $dq->PopDecision(3); $dq->ExecuteStaticMethods(3, 'YES'); ob_end_clean();
$check(count(GetHand(1)) === $hand0 && count(GetGroundArena(1)) === 0, 'the accepted play is refused: card still in hand, nothing in play');
$check(SWUVotePending(1) === null, 'the proposal is cleared');
$check(GetTurnPlayer() === 1, 'Red still has its action');
$check(empty($GLOBALS['SWU_A2_REPLAY']), 'the one-shot replay pass-through does not leak to the next input');

echo "AcceptedAttack_AttackerExhaustedMeanwhile_RefusedBeforeTheGate:\n";
// The replay is refused by ActionMap's own checks BEFORE it reaches the gate (the unit is exhausted), so the gate never consumes the
// one-shot pass-through — the handler itself must switch it off, or the team's NEXT main action would skip the vote.
$fourP(function ($b) {
    $b->WithGroundUnitForPlayer(1, 'SOR_095');
    $b->WithGroundUnitForPlayer(2, 'SOR_095');
    $b->WithCardInHandForPlayer(3, 'SOR_095');
    // 8, not 4: this fixture seats no leader/base for board 3, so its plays carry aspect penalties — and since 2026-11-21 only a card
    // the board would light up (affordable) can be proposed.
    for ($i = 0; $i < 8; $i++) $b->WithControlledResourceForPlayer(3, 'SOR_128', 3);
});
$playerID = 1;
ob_start(); ActionMap('myGroundArena-0'); ob_end_clean();
GetZoneObject('myGroundArena-0')->Status = 0;
$dq = new DecisionQueueController();
ob_start(); $dq->PopDecision(3); $dq->ExecuteStaticMethods(3, 'YES'); ob_end_clean();
$check(SWUVotePending(1) === null && GetTurnPlayer() === 1, 'the refused attack clears the proposal and Red keeps its action');
$check(empty($GLOBALS['SWU_A2_REPLAY']), 'the pass-through is off after a replay refused before the gate');
$playerID = 3;
ob_start(); $r = ActionMap('myHand-0'); ob_end_clean();
$check($r === 'PROPOSED' && SWUVotePending(1) !== null, "board 3's next play is still voted (ActionMap → $r)");

$f = $GLOBALS['BOT_TEST_FAILS'];
echo $f === 0 ? "ALL PASS\n" : "$f FAILED\n";
exit($f === 0 ? 0 : 1);

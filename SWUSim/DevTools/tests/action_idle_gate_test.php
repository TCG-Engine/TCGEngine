<?php
// No action — and in 4P Always Two no proposal — while effects are still resolving (owner, 2026-11-21: "we should block any actions
// or proposals while there are effects being resolved. similar to how we want to guard the "pass" action in between effects").
// The Pass button already refuses while the effect stack holds an entry ("Cannot pass while effects are pending.", CustomInput.php);
// every main action now does the same, and the board offers nothing (no glow, no action list) until the stack is empty.
// Plus (owner: "impossible actions ideally would be guarded against from the UI"): in 4P Always Two a click becomes a PROPOSAL only
// when the board would light that action up — an impossible one is refused before the partner is ever asked.
//   docker exec -w /var/www/html/TCGEngine -e XDEBUG_MODE=off otmtcge-swusim-web-server-1 php -d apc.enable_cli=1 SWUSim/DevTools/tests/action_idle_gate_test.php
require __DIR__ . '/fixtures/bot_test_bootstrap.php';
global $playerID, $gFlashMessage;

$game = function (string $mode) use ($build) {
    return $build(function ($b) use ($mode) {
        if ($mode !== '2p') {
            $b->WithSeatOrder('1234'); $b->WithLiveSeats('1234');
            $b->WithGlobalEffectForPlayer(1, 'SWU_MODE_TEAMS');
            $b->WithGlobalEffectForPlayer(1, 'SWU_MODE_ALWAYSTWO');
            $b->WithGlobalEffectForPlayer(1, 'SWU_ALWAYSTWO_VOTE');
        }
        $b->WithCardInHandForPlayer(1, 'SOR_095');
        $b->WithGroundUnitForPlayer(1, 'SOR_095');
        $b->WithGroundUnitForPlayer(2, 'SOR_095');
        for ($i = 0; $i < 4; $i++) $b->WithControlledResourceForPlayer(1, 'SOR_128', 1);
    });
};
$pushEffect = function () { $s = &GetEffectStack(); $s[] = new EffectStack('SOR_095 1 WhenPlayed -'); };
$flash = fn() => strval(GetFlashMessage());
$noProposal = fn() => !function_exists('SWUVotePending') || SWUVotePending(1) === null;

foreach (['2p', '4p'] as $mode) {
    echo "EffectsResolving_NothingRuns_$mode:\n";
    $game($mode); $playerID = 1; $pushEffect();
    $hand0 = count(GetHand(1));
    ob_start(); ActionMap('myHand-0'); ob_end_clean();
    $check(count(GetHand(1)) === $hand0 && $noProposal(), "$mode: a hand play is refused (no play, no proposal)");
    $check(str_contains($flash(), 'Cannot act while effects are pending.'), "$mode: and says why (\"{$flash()}\")");
    ob_start(); ActionMap('myGroundArena-0'); ob_end_clean();
    $check(intval(GetZoneObject('myGroundArena-0')->Status) === 1 && $noProposal(), "$mode: an attack is refused");
    ob_start(); CustomWidgetInput(1, 'InitiativeCounter-0', 'TakeInitiative'); ob_end_clean();
    $check(!str_ends_with(strval(GetInitiativeCounter()), '_CLAIMED') && $noProposal(), "$mode: taking the initiative is refused");
    ob_start(); CustomWidgetInput(1, 'myLeader-0', 'DeployLeader'); ob_end_clean();
    $check(count(GetGroundArena(1)) === 1 && $noProposal(), "$mode: deploying a leader is refused");
    $d = SWUComputeActionsData(1);
    $check(empty($d['attackers']) && empty($d['unitActions']) && !$d['leaderDeploy'] && !$d['leaderAbility'], "$mode: the board offers no action");
    $meta = json_decode(SelectionMetadata(GetHand(1)[0]), true);
    $check(!(isset($meta['color']) && $meta['color'] === 'rgba(0, 255, 0, 0.95)'), "$mode: no hand card glows");
    echo "Control_EffectsDone_PlayRuns_$mode:\n";
    $game($mode); $playerID = 1;
    $d = SWUComputeActionsData(1);
    $meta = json_decode(SelectionMetadata(GetHand(1)[0]), true);
    $check(!empty($d['attackers']) && isset($meta['color']) && $meta['color'] === 'rgba(0, 255, 0, 0.95)',
           "$mode: control — with nothing resolving the board offers the attack and the hand card glows");
    ob_start(); $r = ActionMap('myHand-0'); ob_end_clean();
    $check($r === ($mode === '2p' ? 'PLAY' : 'PROPOSED'), "$mode: with no effects pending the play goes ahead ($r)");
}

echo "FourP_ProposalPending_BoardOffersNothing:\n";
$game('4p'); $playerID = 1;
ob_start(); ActionMap('myHand-0'); ob_end_clean();
$d = SWUComputeActionsData(1);
$check(empty($d['attackers']) && !$d['leaderDeploy'], 'while a proposal waits, the proposer\'s board offers no action');
$meta = json_decode(SelectionMetadata(GetHand(1)[0]), true);
$check(!(isset($meta['color']) && $meta['color'] === 'rgba(0, 255, 0, 0.95)'), 'and no hand card glows');

echo "FourP_ImpossibleAction_NeverProposed:\n";
// The initiative already claimed this round: the board does not offer it, so the click is refused — the partner is never asked.
$game('4p'); $playerID = 1; SetInitiativeCounter('P2_CLAIMED');
ob_start(); CustomWidgetInput(1, 'InitiativeCounter-0', 'TakeInitiative'); ob_end_clean();
$check($noProposal() && count(array_filter(GetDecisionQueue(3), fn($x) => empty($x->removed))) === 0, 'taking a claimed initiative is not proposed');
// A card the board cannot pay for (every resource exhausted) is not proposed either.
$game('4p'); $playerID = 1;
foreach (GetResources(1) as $res) $res->Status = 0;
ob_start(); ActionMap('myHand-0'); ob_end_clean();
$check($noProposal() && count(array_filter(GetDecisionQueue(3), fn($x) => empty($x->removed))) === 0, 'an unaffordable card is not proposed');
// An exhausted unit cannot attack — no proposal.
$game('4p'); $playerID = 1; GetZoneObject('myGroundArena-0')->Status = 0;
ob_start(); ActionMap('myGroundArena-0'); ob_end_clean();
$check($noProposal(), 'an exhausted unit\'s attack is not proposed');
echo "FourP_LegalAction_StillProposed:\n";
$game('4p'); $playerID = 1;
ob_start(); ActionMap('myGroundArena-0'); ob_end_clean();
$check(!$noProposal(), 'a ready unit\'s attack is proposed as before');

$f = $GLOBALS['BOT_TEST_FAILS'];
echo $f === 0 ? "ALL PASS\n" : "$f FAILED\n";
exit($f === 0 ? 0 : 1);

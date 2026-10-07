<?php
// An attack into a pricier unit draws a card off a ready Wicket leader (feature 'leaderdraw', p41).
// FOUND 2026-10-07 in a human game (NininTCG Hemlock Red vs a Wicket Green guest, R6): C-3PO (cost 2) attacked the shielded Imperial
// Armored Commando (cost 4), popped its Shield and died — and the guest exhausted Wicket (HMW_014, undeployed: "When a friendly unit
// attacks a unit that costs more than it: You may exhaust this leader. If you do, draw a card.") for a card. The bot's attack
// value had no term for it. Worth one 'draw' weight while the leader is ready and undeployed (it exhausts, so once a round).
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php -d apc.enable_cli=1 -d xdebug.mode=off SWUSim/DevTools/tests/bot_leaderdraw_test.php
require __DIR__ . '/fixtures/bot_test_bootstrap.php';
include_once './SWUSim/BotLegalActions.php';
include_once './SWUSim/BotHeuristic.php';
$check(SWUBotVariantDisabled('no-leaderdraw') === ['leaderdraw'], 'leaderdraw is switchable');

// My C-3PO (HMW_255, 2 cost, 2/3) against two Sentinels (so the base is out of reach): Pyke Sentinel (SHD_029, 2 cost, 2/3) and
// Marrok (ASH_030, 3 cost, 2/6). It bounces off either for 2 chip and takes 2. Only Marrok costs more than C-3PO.
$board = function (bool $leaderReady, bool $deployed = false) {
    return function ($b) use ($leaderReady, $deployed) { $b->MyLeader('HMW_014', $leaderReady, $deployed); $b->FillResourcesForPlayer(1, 'SOR_095', 5);
        $b->WithGroundUnitForPlayer(1, 'HMW_255', true);
        $b->WithGroundUnitForPlayer(2, 'SHD_029', true); $b->WithGroundUnitForPlayer(2, 'ASH_030', true);
        $b->WithInitiativePlayerBeing(2); $b->WithInitiativeClaimed(); };
};
$diff = function (string $targetMz) {
    $W = SWUBotWeights('hyperaggro', 1);
    $att = SWUBotViewForMz(1, 'myGroundArena-0'); $def = SWUBotViewForMz(1, $targetMz);
    $GLOBALS['SWUBotDisabledFeatures'] = ['leaderdraw']; $a = SWUBotTargetValue($att, $def, $W);
    $GLOBALS['SWUBotDisabledFeatures'] = [];             $b = SWUBotTargetValue($att, $def, $W);
    return [$b - $a, $W['draw']];
};
$target = function (string $variant = '') use ($raiseAttack, $botCtx, &$gameName) {
    $raiseAttack(1, 'myGroundArena-0');
    $ctx = $botCtx('hyperaggro');
    $p = SWUBotHeuristicChoose('hyperaggro', $ctx['actions'], SWUBotLegalActions($gameName, 1), $variant);
    return strval($p['cardID'] ?? 'null');
};

$build($board(true));
[$d, $draw] = $diff('theirGroundArena-1');
$check(abs($d - $draw) < 1e-9, 'into the pricier Marrok, the ready Wicket draws: + one draw weight; got ' . round($d, 3));
[$d] = $diff('theirGroundArena-0');
$check(abs($d) < 1e-9, 'into the same-cost Pyke Sentinel: no draw');
$check(_SWUBotLeaderAttackDraws(1) === true, 'the ready, undeployed Wicket is a leader that draws on such attacks');

// End to end: C-3PO's target. The two bounces score alike; the draw decides it.
$build($board(true));
$check(($t = $target()) === 'theirGroundArena-1', '[hyperaggro] C-3PO attacks the pricier unit for the card; got ' . $t);
$build($board(true));
$check(($t = $target('no-leaderdraw')) === 'theirGroundArena-0', '[hyperaggro] @no-leaderdraw: the first-listed bounce was taken; got ' . $t);

// The leader exhausted (already drew this round): nothing.
$build($board(false));
[$d] = $diff('theirGroundArena-1');
$check(abs($d) < 1e-9, 'an exhausted Wicket draws nothing');
// Deployed: the leader card's undeployed text is not active.
$build($board(true, true));
$check(_SWUBotLeaderAttackDraws(1) === false, 'a deployed Wicket does not offer the undeployed draw');

bot_test_finish();

<?php
// "Attack with a unit" events go through the dud gate too (feature 'dudgate'). FOUND 2026-09-14 in a live Bot
// Practice game (183227, owner report): "P2 played Aggressive Negotiations / P2's Aggressive Negotiations had no
// effect". SEC_179 Aggressive Negotiations: "Attack with a unit. For this attack, it gets +1/+0 for each card in your
// hand." It carries no card tag, so _SWUBotIsEffectEvent() never sent it through the lookahead, and it was cast with
// no ready unit to attack with.
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php -d apc.enable_cli=1 -d xdebug.mode=off SWUSim/DevTools/tests/bot_attack_event_test.php
require __DIR__ . '/fixtures/bot_test_bootstrap.php';
include_once './SWUSim/BotLegalActions.php';
include_once './SWUSim/BotHeuristic.php';
$playScore = function (string $style) use ($botCtx) {
    $ctx = $botCtx($style);
    foreach ($ctx['actions'] as $i => $a) if (strval($a['cardID']) === 'myHand-0!FSM!') return SWUBotScoreAction($ctx, $a, $i);
    return null;
};
$check(_SWUBotIsEffectEvent('SEC_179'), 'an "Attack with a unit" event is an effect event');

// No ready unit: the event can do nothing.
$build(function ($b) { $b->MyLeader('SOR_014', false); $b->FillResourcesForPlayer(1, 'SOR_095', 8); $b->WithCardInHandForPlayer(1, 'SEC_179');
    $b->WithGroundUnitForPlayer(1, 'SOR_095', false); });
$s = $playScore('aggro');
$check($s === -0.5, 'with no ready unit it is held (a dud); scored ' . json_encode($s));
SWUBotSetDisabledFeatures(['dudgate']); $old = $playScore('aggro'); SWUBotSetDisabledFeatures([]);
$check($old !== null && $old > 0.0, '@no-dudgate: it was worth playing (the reported mistake); scored ' . json_encode($old));

// A ready unit and an open base: the attack lands, so it is played as before.
$build(function ($b) { $b->MyLeader('SOR_014', false); $b->FillResourcesForPlayer(1, 'SOR_095', 8); $b->WithCardInHandForPlayer(1, 'SEC_179');
    $b->WithGroundUnitForPlayer(1, 'SOR_095', true); });
$check(($playScore('aggro') ?? -1) > 0.0, 'with a ready unit to attack with it is not a dud');

bot_test_finish();

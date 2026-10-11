<?php
// The dud gate counts what a removal event heals on MY base (feature 'dudheal', p41).
// FOUND 2026-10-07 replaying a human game (NininTCG Hemlock Red vs a Wicket Green guest, R8): Ninin cast Lost and Forgotten
// (LAW_133, 6 cost: "Defeat a non-leader unit. If you do, heal 3 damage from your base.") on Logray (HMW_045, a 2-cost 1/5 pinger).
// On that board the bot HELD it at -0.5: the dud gate holds removal that takes less than half its printed cost in enemy value,
// and its board delta (_SWUBotBoardDelta) counts enemy value, enemy base damage and my lost units — never my own base. Half of
// Lost and Forgotten was invisible, however damaged the base. A heal counts only the HP actually restored (the damage there).
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php -d apc.enable_cli=1 -d xdebug.mode=off SWUSim/DevTools/tests/bot_dudheal_test.php
require __DIR__ . '/fixtures/bot_test_bootstrap.php';
include_once './SWUSim/BotLegalActions.php';
include_once './SWUSim/BotHeuristic.php';
include_once './SWUSim/Custom/BotLookahead.php';
$check(SWUBotVariantDisabled('no-dudheal') === ['dudheal'], 'dudheal is switchable');
$playScore = function () use ($botCtx) {
    $ctx = $botCtx('softcontrol');
    foreach ($ctx['actions'] as $i => $a) if (strval($a['cardID']) === 'myHand-0!FSM!') return SWUBotScoreAction($ctx, $a, $i);
    return null;
};
$off = function () { $GLOBALS['SWUBotDisabledFeatures'] = ['dudheal']; };
$on  = function () { $GLOBALS['SWUBotDisabledFeatures'] = []; };

// R8, approximately: my Pre Vizsla, Imperial Armored Commando and two Mandalorian tokens; their Teebo, Wicket, Logray and Chief
// Chirpa — four cheap units. Lost and Forgotten in hand, 8 resources. $baseDamage on my base (Bioweapons Lab, 30 HP).
$board = function (int $baseDamage) {
    return function ($b) use ($baseDamage) { $b->MyLeader('HMW_003', true); $b->MyBase('HMW_027', $baseDamage);
        $b->FillResourcesForPlayer(1, 'SOR_095', 8); $b->WithCardInHandForPlayer(1, 'LAW_133');
        $b->WithGroundUnitForPlayer(1, 'ASH_053', true); $b->WithGroundUnitForPlayer(1, 'ASH_048', true, 1);
        $b->WithGroundUnitForPlayer(1, 'ASH_T01', true); $b->WithGroundUnitForPlayer(1, 'ASH_T01', true);
        $b->WithGroundUnitForPlayer(2, 'HMW_162', true); $b->WithGroundUnitForPlayer(2, 'ASH_034', true);
        $b->WithGroundUnitForPlayer(2, 'HMW_045', true); $b->WithGroundUnitForPlayer(2, 'HMW_164', true);
        $b->WithInitiativePlayerBeing(1); };
};

$build($board(15));
$check(_SWUBotBoardRead(1)['myBase'] === 15, 'the board read carries my base remaining HP; got ' . json_encode(_SWUBotBoardRead(1)['myBase'] ?? null));
$off(); $sOff = $playScore(); $on(); $sOn = $playScore();
$check($sOff === -0.5, '@no-dudheal: Lost and Forgotten was held as a dud (the reported blind spot); got ' . json_encode($sOff));
$check($sOn > 0, 'with 15 damage on my base the heal makes it worth casting; got ' . json_encode($sOn));

// Nothing to heal: the heal is worth nothing and the gate holds it as before.
$build($board(0));
$on(); $check($playScore() === -0.5, 'an undamaged base: still held');
// Only the HP actually healed counts: 1 damage heals 1, not 3.
$build($board(1));
$on(); $check($playScore() === -0.5, '1 damage on my base heals 1, not 3: still held');

bot_test_finish();

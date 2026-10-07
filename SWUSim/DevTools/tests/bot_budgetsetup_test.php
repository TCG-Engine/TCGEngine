<?php
// A Weakness token that brings an enemy unit inside Pre Vizsla's kill budget is a setup for it (feature 'budgetsetup', p41).
// FOUND 2026-10-07 in a human game (NininTCG Hemlock Red vs a Wicket Green guest, R5-R7): Luminara Unduli (HMW_124, 7/7) took three
// Weakness tokens (Hemlock's On Attack, then two Nuvo Vindi triggers) and 3 combat damage — down to 1 HP — and Pre Vizsla
// (ASH_053: "When Played: Defeat any number of non-leader units with a total of 6 or less remaining HP. Create a Mandalorian token
// for each unit defeated this way.") then took Luminara, Teebo and Crix Madine on 4 of its 6 HP for three tokens.
// The bot's Weakness scoring knew per-unit finishers only ('setup' / 'ndsetup': _SWUBotFinisherHPFor); a SHARED HP budget is not
// one, so a token was spread by "soften the strongest body". Now: with a budget wipe in hand, castable within two rounds (the
// R5 token came two rounds before the R7 Pre Vizsla), the token is also worth part of what it adds to the wipe's best kill set.
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php -d apc.enable_cli=1 -d xdebug.mode=off SWUSim/DevTools/tests/bot_budgetsetup_test.php
require __DIR__ . '/fixtures/bot_test_bootstrap.php';
include_once './SWUSim/BotLegalActions.php';
include_once './SWUSim/BotHeuristic.php';
include_once './SWUSim/Custom/BotLookahead.php';
$check(SWUBotVariantDisabled('no-budgetsetup') === ['budgetsetup'], 'budgetsetup is switchable');

// I play Nuvo Vindi (HMW_062: "When Played: You may give a Weakness token to a unit"). Their Consular Security Force (SOR_046,
// vanilla 3/7) is one token from Pre Vizsla's 6; their Coastal Catamarans (HMW_093, vanilla 8/8) is the bigger body, but still
// 7 HP after a token — out of the budget either way.
$board = function (bool $pre, int $resources, int $csfDamage = 0) {
    return function ($b) use ($pre, $resources, $csfDamage) { $b->MyLeader('HMW_003', false); $b->FillResourcesForPlayer(1, 'SOR_095', $resources);
        $b->WithCardInHandForPlayer(1, 'HMW_062');
        if ($pre) $b->WithCardInHandForPlayer(1, 'ASH_053');
        $b->WithGroundUnitForPlayer(2, 'SOR_046', true, $csfDamage);
        $b->WithGroundUnitForPlayer(2, 'HMW_093', true); };
};
$pick = function (callable $board, string $variant = '') use ($build, $act, $botCtx, &$gameName) {
    $build($board);
    $act(1, 10002, 'myHand-0!FSM!');
    $ctx = $botCtx('softcontrol');
    $p = SWUBotHeuristicChoose('softcontrol', $ctx['actions'], SWUBotLegalActions($gameName, 1), $variant);
    return [strval($ctx['tooltip']), strval($p['cardID'] ?? 'null')];
};

[$tip, $p] = $pick($board(true, 6));
$check(stripos($tip, 'Weakness') !== false, 'fixture: Nuvo Vindi asks where its Weakness token goes; got ' . $tip);
$check($p === 'theirGroundArena-0', 'Pre Vizsla in hand (8 resources in two rounds): the token brings the Security Force inside its 6; got ' . $p);
[, $p] = $pick($board(true, 6), 'no-budgetsetup');
$check($p === 'theirGroundArena-1', '@no-budgetsetup: the token softened the biggest body instead (the reported blind spot); got ' . $p);

// No budget wipe in hand: the feature is inert.
[, $p] = $pick($board(false, 6));
$check($p === 'theirGroundArena-1', 'no Pre Vizsla: the biggest body is softened as before; got ' . $p);
// Pre Vizsla out of reach (3 resources: 5 in two rounds, it costs 8): inert.
[, $p] = $pick($board(true, 3));
$check($p === 'theirGroundArena-1', 'Pre Vizsla three rounds away: the biggest body is softened as before; got ' . $p);

// A unit already inside the budget gains nothing from the token (the Security Force with 1 damage: 6 left).
$build($board(true, 6, 1));
$W = SWUBotWeights('softcontrol', 1);
$csf = SWUBotViewForMz(1, 'theirGroundArena-0');
$GLOBALS['SWUBotDisabledFeatures'] = ['budgetsetup']; $a = _SWUBotWeaknessScore(1, $csf, true, 0, 'GIVE_WEAKNESS', 1, $W);
$GLOBALS['SWUBotDisabledFeatures'] = [];              $b = _SWUBotWeaknessScore(1, $csf, true, 0, 'GIVE_WEAKNESS', 1, $W);
$check(abs($a - $b) < 1e-9, 'a unit already inside the budget: the token adds nothing to the kill set');

// The budget is SHARED: with a 5-HP unit already filling it, a second unit's token that brings it to 6 does not fit beside it —
// only the better of the two is ever taken. The kill set's value is what the token can raise.
$check(_SWUBotBudgetKillValue([['uid' => 1, 'remaining' => 5, 'value' => 3.0], ['uid' => 2, 'remaining' => 6, 'value' => 4.0]], 6) === 4.0,
    'the kill set is the best subset within the budget, not the first units listed');
$check(_SWUBotBudgetKillValue([['uid' => 1, 'remaining' => 2, 'value' => 3.0], ['uid' => 2, 'remaining' => 4, 'value' => 4.0]], 6) === 7.0,
    'two units that fit together are both taken');

bot_test_finish();

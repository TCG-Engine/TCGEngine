<?php
// Feature 'dedra' (p42) — SEC_010 Dedra Meero, Not Wasting Time: "Action [1 resource, Exhaust]: Choose an enemy unit. Its controller may
// deal 2 damage to it. If they don't, draw a card." Leader audit 2026-10-08: (1) her pick "Choose_an_enemy_unit" read as neutral and took
// the FIRST listed enemy (3,636 of 3,636); (2) as the OPPONENT, the bot answered every "Deal 2 damage to your …?" YES (3,636 of 3,636) —
// 32% of the targets had 2 HP or less, so it killed its own unit rather than let her draw. Fixed: the pick is a 2-damage hostile target
// (a unit the 2 would defeat first — losing it or giving the card are then both bad); the answer is YES only when the unit survives the 2
// (or is a token — cost 0 — worth less than her card).
// Fixtures (dictionary-checked): SEC_010 Dedra (Aggression/Villainy) · SOR_095 Battlefield Marine (3 HP) · SOR_225 TIE/ln Fighter (1 HP)
//   · JTL_096 Blue Leader (3, 3/3) · JTL_T01 TIE Fighter token (cost 0, 1/1).
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php -d apc.enable_cli=1 -d xdebug.mode=off SWUSim/DevTools/tests/bot_dedra_test.php
require __DIR__ . '/fixtures/bot_test_bootstrap.php';
foreach (['BotLegalActions', 'Custom/BotLookahead', 'Custom/BotEvaluator', 'Rl/CardTags', 'Custom/BotStyles', 'Custom/BotResourcing',
          'Custom/BotGuides', 'Custom/BotFallback', 'Custom/BotRules', 'BotHeuristic'] as $f) include_once "./SWUSim/$f.php";
$check(SWUBotVariantDisabled('no-dedra') === ['dedra'], 'dedra is switchable');
$check(in_array('dedra', SWUBotFeatureGroups()['p42'] ?? [], true), 'dedra is in group p42');
$pickFor = function (int $seat, string $variant = '') use (&$gameName) {
    $l = SWUBotLegalActions($gameName, $seat);
    $p = SWUBotHeuristicChoose('midrange', (array)$l['actions'], $l, $variant);
    return strval($p['cardID'] ?? '');
};
// Dedra (seat 1) with a resource; their Marine (ground, listed first) and $space in space. Dedra's Action is started.
$board = function (string $theirSpace) use ($build, $act) {
    $build(function ($b) use ($theirSpace) {
        $b->MyLeader('SEC_010', true); $b->MyBase('SOR_020'); $b->FillResourcesForPlayer(1, 'SOR_095', 2); $b->WithCurrentRoundBeing(4);
        $b->WithGroundUnitForPlayer(2, 'SOR_095', false); $b->WithSpaceUnitForPlayer(2, $theirSpace, false);
        for ($k = 0; $k < 30; $k++) { $b->WithCardInDeckForPlayer(1, 'SOR_095'); $b->WithCardInDeckForPlayer(2, 'SOR_095'); }
    });
    $act(1, 10001, 'myLeader-0!CustomInput!LeaderAbility');
};

// A) Her pick: the Marine (3 HP, listed first) vs a TIE/ln (1 HP). Today the first listed; fixed, the TIE.
$board('SOR_225');
$check($botCtx('midrange')['tooltip'] === 'Choose_an_enemy_unit', 'A fixture: Dedra\'s pick is pending');
$check($pickFor(1, 'no-dedra') === 'theirGroundArena-0', 'A fixture: today the first-listed Marine');
$check($pickFor(1) === 'theirSpaceArena-0', 'A: the TIE — the 2 damage would defeat it; got ' . $pickFor(1));

// B) As the opponent: Dedra picked their Blue Leader (3/3 — survives 2) / their TIE (1 HP — dies to 2).
$answer = function (string $space, string $variant = '') use ($board, $act, $pickFor) {
    $board($space);
    $act(1, 100, 'theirSpaceArena-0');
    return $pickFor(2, $variant);
};
$check($answer('SOR_225', 'no-dedra') === 'YES', 'B fixture: today the bot kills its own TIE rather than let Dedra draw');
$check($answer('SOR_225') === 'NO', 'B: the TIE would die — let her draw instead; got ' . $answer('SOR_225'));
$check($answer('JTL_096') === 'YES', 'B: Blue Leader survives the 2 — take the damage; got ' . $answer('JTL_096'));
// …and a token (a TIE Fighter token, cost 0) may die rather than give her a card.
$check($answer('JTL_T01') === 'YES', 'B: a TIE Fighter token (cost 0) — take the damage; got ' . $answer('JTL_T01'));

bot_test_finish();

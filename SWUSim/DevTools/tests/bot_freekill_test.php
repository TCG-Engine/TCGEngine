<?php
// Feature 'freekill' (p42) — LAW_004 Aurra Sing, Assassin: "Action [Exhaust]: Defeat a non-leader unit with 1 or less remaining HP."
// Leader audit 2026-10-08: priced at the flat W['ability'] (0.40), the free kill waited behind other plays — used in 60-68% of the rounds
// that held a target — and 39 traced attacks went into a 1-HP unit while it was ready. Fixed: the Action is worth the best kill it makes
// (priced as the targeting pick prices a defeat), and nothing when there is none.
// Fixtures (dictionary-checked): LAW_004 Aurra Sing · SOR_020 base · SOR_225 TIE/ln Fighter (2/1) · SOR_095 Battlefield Marine (3/3)
//   · JTL_096 Blue Leader (3) · SOR_237 Alliance X-Wing · LAW_101 Lawbringer (8 power) · ASH_102 Ravager.
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php -d apc.enable_cli=1 -d xdebug.mode=off SWUSim/DevTools/tests/bot_freekill_test.php
require __DIR__ . '/fixtures/bot_test_bootstrap.php';
foreach (['BotLegalActions', 'Custom/BotLookahead', 'Custom/BotEvaluator', 'Rl/CardTags', 'Custom/BotStyles', 'Custom/BotResourcing',
          'Custom/BotGuides', 'Custom/BotFallback', 'Custom/BotRules', 'BotHeuristic'] as $f) include_once "./SWUSim/$f.php";
$check(SWUBotVariantDisabled('no-freekill') === ['freekill'], 'freekill is switchable');
$check(in_array('freekill', SWUBotFeatureGroups()['p42'] ?? [], true), 'freekill is in group p42');
$pick = function (string $variant = '') use (&$gameName) {
    $l = SWUBotLegalActions($gameName, 1);
    $p = SWUBotHeuristicChoose('softcontrol', (array)$l['actions'], $l, $variant);
    return strval($p['cardID'] ?? '');
};
$ability = 'myLeader-0!CustomInput!LeaderAbility';
// Aurra ready, 3 resources and Blue Leader (3) in hand, my ready X-Wing in space; their exhausted TIE/ln ($tie) and Marine.
$board = function (bool $tie) use ($build) {
    $build(function ($b) use ($tie) {
        $b->MyLeader('LAW_004', true); $b->MyBase('SOR_020'); $b->FillResourcesForPlayer(1, 'SOR_095', 3); $b->WithCurrentRoundBeing(5);
        $b->WithCardInHandForPlayer(1, 'JTL_096'); $b->WithSpaceUnitForPlayer(1, 'SOR_237', true);
        if ($tie) $b->WithSpaceUnitForPlayer(2, 'SOR_225', false);
        $b->WithGroundUnitForPlayer(2, 'SOR_095', false);
        for ($k = 0; $k < 30; $k++) { $b->WithCardInDeckForPlayer(1, 'SOR_095'); $b->WithCardInDeckForPlayer(2, 'SOR_095'); }
    });
};
// A) A 1-HP TIE/ln is there: the free kill goes first (today a play or an attack does).
$board(true);
$check($pick('no-freekill') !== $ability, 'A fixture: today the free kill waits; got ' . $pick('no-freekill'));
$check($pick() === $ability, 'A: Aurra takes the free kill first; got ' . $pick());
// B) No unit at 1 HP or less: the Action does nothing — never used.
$board(false);
$check($pick() !== $ability, 'B: nothing to kill — no Action; got ' . $pick());

// C) A big attacker (Lawbringer, 8 power) scores its attack above the kill's own price — the free kill still goes first (it costs no
// attack: Lawbringer swings afterwards).
$build(function ($b) {
    $b->MyLeader('LAW_004', true); $b->MyBase('SOR_020'); $b->FillResourcesForPlayer(1, 'SOR_095', 3); $b->WithCurrentRoundBeing(5);
    $b->WithSpaceUnitForPlayer(1, 'LAW_101', true);
    $b->WithSpaceUnitForPlayer(2, 'ASH_102', false); $b->WithSpaceUnitForPlayer(2, 'SOR_225', false);
    for ($k = 0; $k < 30; $k++) { $b->WithCardInDeckForPlayer(1, 'SOR_095'); $b->WithCardInDeckForPlayer(2, 'SOR_095'); }
});
$check($pick() === $ability, 'C: the free kill before the big attack; got ' . $pick());

bot_test_finish();

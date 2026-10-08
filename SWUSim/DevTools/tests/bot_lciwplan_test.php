<?php
// Feature 'lciwplan' (p42) — SEC_180 Let's Call It War: "Deal 3 damage to a unit. Then, if you have the initiative, you may deal 2 damage
// to another unit in the same arena." OWNER PLAN 2026-10-08 (Mando Colossus, step 3): "against space … use it to clear two low-health
// space units if possible". The first pick was scored alone, so with a TIE/ln (1 HP) and a Y-Wing (3 HP) both "killed by 3", the 3 went
// on the TIE and the follow-up 2 only chipped the Y-Wing.
// Fixed: while I hold the initiative, the first target is worth itself PLUS the best follow-up the 2 can make on another unit in its arena.
// Fixtures (dictionary-checked): ASH_014 The Mandalorian · JTL_021 Colossus · SEC_180 Let's Call It War · SOR_225 (TIE/ln, 1 HP) ·
//   JTL_212 Republic Y-Wing (1/3) · LOF_093 Gungi (2/5, ground) · SOR_095.
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php -d apc.enable_cli=1 -d xdebug.mode=off SWUSim/DevTools/tests/bot_lciwplan_test.php
require __DIR__ . '/fixtures/bot_test_bootstrap.php';
foreach (['BotLegalActions', 'Custom/BotLookahead', 'Custom/BotEvaluator', 'Rl/CardTags', 'Custom/BotStyles', 'Custom/BotResourcing',
          'Custom/BotGuides', 'Custom/BotFallback', 'Custom/BotRules', 'BotHeuristic'] as $f) include_once "./SWUSim/$f.php";
$check(SWUBotVariantDisabled('no-lciwplan') === ['lciwplan'], 'lciwplan is switchable');
$check(in_array('lciwplan', SWUBotFeatureGroups()['p42'] ?? [], true), 'lciwplan is in group p42');
$pick = function (string $variant = '') use (&$gameName) {
    $l = SWUBotLegalActions($gameName, 1);
    $p = SWUBotHeuristicChoose('hardcontrol', (array)$l['actions'], $l, $variant);
    return strval($p['cardID'] ?? '');
};
$nameOf = fn(string $mz) => strval((GetZoneObject($mz) ?? null)->CardID ?? '');
// Round 3, 4 resources, Let's Call It War in hand; their TIE/ln + Y-Wing in space and Gungi on the ground. $init: I hold the initiative.
$board = function (bool $init) use ($build) {
    $build(function ($b) use ($init) {
        $b->MyLeader('ASH_014', true); $b->MyBase('JTL_021'); $b->FillResourcesForPlayer(1, 'SOR_095', 4); $b->WithCurrentRoundBeing(3);
        $b->WithInitiativePlayerBeing($init ? 1 : 2);
        $b->TheirLeader('JTL_006'); $b->WithCardInHandForPlayer(1, 'SEC_180');
        $b->WithSpaceUnitForPlayer(2, 'SOR_225', true); $b->WithSpaceUnitForPlayer(2, 'JTL_212', true); $b->WithGroundUnitForPlayer(2, 'LOF_093', true);
        for ($k = 0; $k < 30; $k++) { $b->WithCardInDeckForPlayer(1, 'SOR_095'); $b->WithCardInDeckForPlayer(2, 'SOR_095'); }
    });
};
$cast = function () use ($act, $botCtx) {
    $act(1, 10002, 'myHand-0!FSM!');
    return strval($botCtx('hardcontrol')['tooltip'] ?? '');
};

// A) Initiative mine: today the 3 goes on the TIE (listed first; both die to 3).
$board(true);
$check(str_starts_with($cast(), 'Deal_3'), 'A fixture: the first-target prompt');
$check($nameOf($pick('no-lciwplan')) === 'SOR_225', 'A fixture: today the TIE/ln; got ' . $nameOf($pick('no-lciwplan')));
// …with the plan: the 3 on the Y-Wing, so the 2 still kills the TIE — both ships gone.
$first = $pick();
$check($nameOf($first) === 'JTL_212', 'A: the 3 on the Y-Wing (the 2 then kills the TIE); got ' . $nameOf($first));
$act(1, 100, $first);
$second = $pick();
$check($nameOf($second) === 'SOR_225', 'A: the follow-up 2 on the TIE/ln; got ' . $nameOf($second));
$act(1, 100, $second);
$left = array_filter(GetSpaceArena(2), fn($u) => $u !== null && empty($u->removed));
$check(count($left) === 0, 'A: both space units defeated; left ' . json_encode(array_map(fn($u) => $u->CardID, array_values($left))));

// B) The initiative is theirs: there is no follow-up, so the first pick is scored alone — unchanged.
$board(false);
$cast();
$ctx = $botCtx('hardcontrol');
foreach ($ctx['actions'] as $i => $a) {
    $on = SWUBotScoreAction($ctx, $a, $i); SWUBotSetDisabledFeatures(['lciwplan']); $off = SWUBotScoreAction($ctx, $a, $i); SWUBotSetDisabledFeatures([]);
    $check(abs($on - $off) < 1e-9, 'B: without the initiative ' . $a['cardID'] . ' is unchanged; got ' . json_encode([$on, $off]));
}

// C) The follow-up stays in the SAME arena: a lone Y-Wing in space gains nothing from the Outer Rim Constable (3/1) on the ground.
$build(function ($b) {
    $b->MyLeader('ASH_014', true); $b->MyBase('JTL_021'); $b->FillResourcesForPlayer(1, 'SOR_095', 4); $b->WithCurrentRoundBeing(3);
    $b->WithInitiativePlayerBeing(1); $b->TheirLeader('JTL_006'); $b->WithCardInHandForPlayer(1, 'SEC_180');
    $b->WithSpaceUnitForPlayer(2, 'JTL_212', true); $b->WithGroundUnitForPlayer(2, 'SEC_163', true);
    for ($k = 0; $k < 30; $k++) { $b->WithCardInDeckForPlayer(1, 'SOR_095'); $b->WithCardInDeckForPlayer(2, 'SOR_095'); }
});
$cast();
$ctx = $botCtx('hardcontrol');
foreach ($ctx['actions'] as $i => $a) {
    if ($nameOf(strval($a['cardID'])) !== 'JTL_212') continue;
    $on = SWUBotScoreAction($ctx, $a, $i); SWUBotSetDisabledFeatures(['lciwplan']); $off = SWUBotScoreAction($ctx, $a, $i); SWUBotSetDisabledFeatures([]);
    $check(abs($on - $off) < 1e-4, 'C: the lone Y-Wing gets no cross-arena follow-up; got ' . json_encode([$on, $off]));
}

bot_test_finish();

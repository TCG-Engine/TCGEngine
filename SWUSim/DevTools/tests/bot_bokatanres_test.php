<?php
// Feature 'bokatanres' (p42) — OWNER RULING 2026-10-08 (Mando Colossus): "as for Bo-Katan against non-aggro decks, resource her if she's in
// any opening hands. but don't resource more than 1 in a game. if there are weaker cards to resource by the 4R turn, do those instead. this
// deck thrives in the late game". SEC_051 Bo-Katan Kryze (9, "Give each enemy unit -3/-3"), for a HARD CONTROL seat:
//   - opening hand, against a non-aggro leader: she is one of the two cards resourced;
//   - any later regroup: she is resourced only when nothing else in hand is (every other card is "weaker" — the late game is the plan);
//   - never a second one in a game — against any opponent.
// The castable-soon rule priced a 9-drop on 5 resources at (soon − cost) = −1: the FIRST card resourced at every regroup.
// Fixtures (dictionary-checked): ASH_014 The Mandalorian · JTL_021 Colossus · SEC_051 Bo-Katan Kryze · LAW_044 Single Reactor Ignition ·
//   JTL_153 Rebellious Hammerhead · SEC_148 Karis Nemik · LAW_118 Droid Laser Turret · SEC_163 Outer Rim Constable · ASH_031 Hera ·
//   SEC_180 Let's Call It War · LAW_008 Krennic (not aggro) · ASH_009 (aggro) · SOR_095.
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php -d apc.enable_cli=1 -d xdebug.mode=off SWUSim/DevTools/tests/bot_bokatanres_test.php
require __DIR__ . '/fixtures/bot_test_bootstrap.php';
foreach (['BotLegalActions', 'Custom/BotLookahead', 'Custom/BotEvaluator', 'Rl/CardTags', 'Custom/BotStyles', 'Custom/BotResourcing',
          'Custom/BotGuides', 'Custom/BotFallback', 'Custom/BotRules', 'BotHeuristic'] as $f) include_once "./SWUSim/$f.php";
$check(SWUBotVariantDisabled('no-bokatanres') === ['bokatanres'], 'bokatanres is switchable');
$check(in_array('bokatanres', SWUBotFeatureGroups()['p42'] ?? [], true), 'bokatanres is in group p42');
// $res resources (of which $resBo are Bo-Katans), round $rnd, $hand; their leader $lead.
$board = function (string $lead, int $rnd, int $res, array $hand, int $resBo = 0) use ($build) {
    $build(function ($b) use ($lead, $rnd, $res, $hand, $resBo) {
        $b->MyLeader('ASH_014', true); $b->MyBase('JTL_021'); $b->WithCurrentRoundBeing($rnd); $b->TheirLeader($lead);
        if ($res - $resBo > 0) $b->FillResourcesForPlayer(1, 'SOR_095', $res - $resBo);
        if ($resBo > 0) $b->FillResourcesForPlayer(1, 'SEC_051', $resBo);
        foreach ($hand as $c) $b->WithCardInHandForPlayer(1, $c);
        for ($k = 0; $k < 30; $k++) { $b->WithCardInDeckForPlayer(1, 'SOR_095'); $b->WithCardInDeckForPlayer(2, 'SOR_095'); }
    });
};
// The cards resourced (by CardID): $n of them; $opening = the game's opening "Choose 2 cards to resource".
$resourced = function (int $n, bool $opening = false, array $disabled = [], string $style = 'hardcontrol') use ($botCtx) {
    SWUBotSetDisabledFeatures($disabled);
    $ctx = $botCtx($style);
    if ($opening) $ctx['tooltip'] = 'Choose_2_cards_to_resource';
    $out = [];
    foreach (SWUBotChooseResourceCards($ctx, $n) as $mz) $out[] = preg_match('/^myHand-(\d+)$/', strval($mz), $m) ? strval(GetHand(1)[intval($m[1])]->CardID ?? '') : "?$mz";
    SWUBotSetDisabledFeatures([]);
    sort($out);
    return $out;
};
$opener = ['SEC_051', 'SEC_148', 'LAW_118', 'SEC_163', 'ASH_031', 'SEC_180'];

// A) Opening hand vs Krennic (not aggro): Bo-Katan is one of the two.
$board('LAW_008', 1, 0, $opener);
$check(!in_array('SEC_051', $resourced(2, true, ['bokatanres']), true), 'A fixture: today she is kept in the opener; got ' . json_encode($resourced(2, true, ['bokatanres'])));
$check(in_array('SEC_051', $resourced(2, true), true), 'A: Bo-Katan is resourced from the opening hand; got ' . json_encode($resourced(2, true)));

// B) Two Bo-Katans in the opener: only ONE goes.
$board('LAW_008', 1, 0, ['SEC_051', 'SEC_051', 'SEC_148', 'LAW_118', 'SEC_163', 'ASH_031']);
$check(count(array_keys($resourced(2, true), 'SEC_051')) === 1, 'B: one Bo-Katan, not two; got ' . json_encode($resourced(2, true)));

// C) A regroup (round 5, 5 resources) vs Krennic, with weaker cards in hand: today Bo-Katan (9 on 5 = "furthest from castable") goes.
$board('LAW_008', 5, 5, ['SEC_051', 'LAW_044', 'SEC_148', 'LAW_118', 'SEC_163']);
$check($resourced(1, false, ['bokatanres']) === ['SEC_051'], 'C fixture: today Bo-Katan; got ' . json_encode($resourced(1, false, ['bokatanres'])));
$check($resourced(1) !== ['SEC_051'], 'C: a weaker card goes instead; got ' . json_encode($resourced(1)));

// D) One already resourced this game: even a hand of bombs (SRI, Hammerhead) keeps the second Bo-Katan.
$board('LAW_008', 5, 5, ['SEC_051', 'LAW_044', 'JTL_153'], 1);
$check($resourced(1) !== ['SEC_051'], 'D: never a second Bo-Katan; got ' . json_encode($resourced(1)));
// …against aggro too (the tiered resourcer would send a duplicate first).
$board('ASH_009', 5, 5, ['SEC_051', 'SEC_051', 'LAW_044', 'SEC_148'], 1);
$check(!in_array('SEC_051', $resourced(1), true), 'D: vs aggro — no second Bo-Katan either; got ' . json_encode($resourced(1)));

// E) Opening hand vs AGGRO: the opener rule is the non-aggro one — she is kept ('stabkeep').
$board('ASH_009', 1, 0, $opener);
$check(!in_array('SEC_051', $resourced(2, true), true), 'E: vs aggro the opener keeps her; got ' . json_encode($resourced(2, true)));


// F) Only HARD CONTROL: a midrange seat on board C resources as before.
$board('LAW_008', 5, 5, ['SEC_051', 'LAW_044', 'SEC_148', 'LAW_118', 'SEC_163']);
$check($resourced(1, false, [], 'midrange') === $resourced(1, false, ['bokatanres'], 'midrange'), 'F: midrange unchanged; got ' . json_encode([$resourced(1, false, [], 'midrange'), $resourced(1, false, ['bokatanres'], 'midrange')]));

bot_test_finish();

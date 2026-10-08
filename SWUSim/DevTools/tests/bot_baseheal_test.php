<?php
// Feature 'baseheal' (p42) — ASH_031 Hera Syndulla: "When Attack Ends: If this unit dealt combat damage to a base, heal that much damage
// from your base." OWNER PLAN 2026-10-08 (Mando Colossus): "setting up Hera + Aggressive Negotiations to swing big and heal big getting
// doubly ahead". The base hit's value never counted the heal, so Aggressive Negotiations (SEC_179, +1/+0 per card in hand) went on the
// bigger Bith Brute (4/3) — 9 to the base — instead of Hera (3/4): 8 to the base AND 8 healed.
// Fixed: a base hit by such a unit is also worth the damage it heals — W['heal'] a point, up to the damage on my base.
// Fixtures (dictionary-checked): ASH_014 The Mandalorian · JTL_021 Colossus · ASH_031 Hera Syndulla (3/4) · LAW_049 Bith Brute (4/3,
//   Sentinel, Saboteur) · SEC_179 Aggressive Negotiations · SEC_148 Karis Nemik · SOR_225 (their space unit) · SOR_095.
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php -d apc.enable_cli=1 -d xdebug.mode=off SWUSim/DevTools/tests/bot_baseheal_test.php
require __DIR__ . '/fixtures/bot_test_bootstrap.php';
foreach (['BotLegalActions', 'Custom/BotLookahead', 'Custom/BotEvaluator', 'Rl/CardTags', 'Custom/BotStyles', 'Custom/BotResourcing',
          'Custom/BotGuides', 'Custom/BotFallback', 'Custom/BotRules', 'BotHeuristic'] as $f) include_once "./SWUSim/$f.php";
$check(SWUBotVariantDisabled('no-baseheal') === ['baseheal'], 'baseheal is switchable');
$check(in_array('baseheal', SWUBotFeatureGroups()['p42'] ?? [], true), 'baseheal is in group p42');
$pick = function (string $variant = '') use (&$gameName) {
    $l = SWUBotLegalActions($gameName, 1);
    $p = SWUBotHeuristicChoose('hardcontrol', (array)$l['actions'], $l, $variant);
    return strval($p['cardID'] ?? '');
};
// Bith Brute (ground 0) and Hera (ground 1) ready, my base on $dmg damage, Aggressive Negotiations + 5 Karis in hand; nothing of theirs on the ground.
$board = function (int $dmg) use ($build) {
    $build(function ($b) use ($dmg) {
        $b->MyLeader('ASH_014', true); $b->MyBase('JTL_021', $dmg); $b->FillResourcesForPlayer(1, 'SOR_095', 3); $b->WithCurrentRoundBeing(5);
        $b->WithGroundUnitForPlayer(1, 'LAW_049', true); $b->WithGroundUnitForPlayer(1, 'ASH_031', true);
        $b->WithCardInHandForPlayer(1, 'SEC_179'); for ($k = 0; $k < 5; $k++) $b->WithCardInHandForPlayer(1, 'SEC_148');
        $b->WithSpaceUnitForPlayer(2, 'SOR_225', false);
        for ($k = 0; $k < 30; $k++) { $b->WithCardInDeckForPlayer(1, 'SOR_095'); $b->WithCardInDeckForPlayer(2, 'SOR_095'); }
    });
};
$W = SWUBotWeights('hardcontrol', 1);
$hera = 'myGroundArena-1'; $brute = 'myGroundArena-0';
$atkScore = function (string $mz, string $variant = '') use ($botCtx) {
    SWUBotSetDisabledFeatures($variant === '' ? [] : [substr($variant, 3)]);
    $c = $botCtx('hardcontrol'); $s = null;
    foreach ($c['actions'] as $i => $a) if (strval($a['cardID']) === "$mz!FSM!") $s = SWUBotScoreAction($c, $a, $i);
    SWUBotSetDisabledFeatures([]);
    return $s;
};

// A) Base on 12: Hera's swing (3) is worth the 3 it heals on top — exactly W['heal'] a point.
$board(12);
$on = $atkScore($hera); $off = $atkScore($hera, 'no-baseheal');
$check($on !== null && abs(($on - $off) - 3 * $W['heal']) < 1e-6, 'A: Hera\'s base swing gains 3 heals; got ' . json_encode([$on, $off, $W['heal']]));
$check(abs($atkScore($brute) - $atkScore($brute, 'no-baseheal')) < 1e-6, 'A: the Brute (no heal) is unchanged');

// B) Aggressive Negotiations' attacker: today the Brute (4+5 = 9 to the base); Hera's 8 heals 8 too.
$board(12);
$act(1, 10002, 'myHand-0!FSM!');
$check($botCtx('hardcontrol')['tooltip'] === 'Choose_a_unit_to_attack_with', 'B fixture: the AN attacker prompt; got ' . $botCtx('hardcontrol')['tooltip']);
$check($pick('no-baseheal') === $brute, 'B fixture: today the Brute; got ' . $pick('no-baseheal'));
$check($pick() === $hera, 'B: Hera takes Aggressive Negotiations — swing big, heal big; got ' . $pick());

// C) An undamaged base: nothing to heal — the bigger Brute, as before.
$board(0);
$act(1, 10002, 'myHand-0!FSM!');
$check($pick() === $brute, 'C: base undamaged — the Brute; got ' . $pick());

// D) The heal is capped by the damage on my base: base on 2 — Hera's 3 heals only 2.
$board(2);
$on = $atkScore($hera); $off = $atkScore($hera, 'no-baseheal');
$check(abs(($on - $off) - 2 * $W['heal']) < 1e-6, 'D: capped at the 2 damage on my base; got ' . json_encode([$on, $off]));

bot_test_finish();

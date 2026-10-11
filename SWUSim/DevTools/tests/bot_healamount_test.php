<?php
// Feature 'healamount' (p42) — ASH_005 Luke Skywalker, I Can Save Him (deployed): "When a friendly unit's attack ends: Heal 2 damage from
// that unit or from your base." (HEAL_TARGET|2: the attacker and/or my base, whichever carries damage.) Leader audit 2026-10-08: the
// unit was picked 2,202 of 2,202 times — HEAL_TARGET priced the unit by its VALUE against the base's flat weight, ignoring how much each
// heal restores: ~16% of those heals landed on a unit with 1 damage (half the heal wasted), 719 with my base at 15 HP or less. Fixed: each
// is worth the HP it actually restores; a base point a little less than a unit's — and more once the base is under pressure.
// Fixtures (dictionary-checked): ASH_005 Luke (deployed) · SOR_020 base (30 HP) · SOR_095 Battlefield Marine (3/3).
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php -d apc.enable_cli=1 -d xdebug.mode=off SWUSim/DevTools/tests/bot_healamount_test.php
require __DIR__ . '/fixtures/bot_test_bootstrap.php';
foreach (['BotLegalActions', 'Custom/BotLookahead', 'Custom/BotEvaluator', 'Rl/CardTags', 'Custom/BotStyles', 'Custom/BotResourcing',
          'Custom/BotGuides', 'Custom/BotFallback', 'Custom/BotRules', 'BotHeuristic'] as $f) include_once "./SWUSim/$f.php";
$check(SWUBotVariantDisabled('no-healamount') === ['healamount'], 'healamount is switchable');
$check(in_array('healamount', SWUBotFeatureGroups()['p42'] ?? [], true), 'healamount is in group p42');
$pick = function (string $variant = '') use (&$gameName) {
    $l = SWUBotLegalActions($gameName, 1);
    $p = SWUBotHeuristicChoose('midrange', (array)$l['actions'], $l, $variant);
    return strval($p['cardID'] ?? '');
};
// Deployed Luke; my Marine with $unitDmg damage attacks their base (no enemy unit); my base on $baseDmg damage. Returns the prompt.
$heal = function (int $unitDmg, int $baseDmg) use ($build, $raiseAttack, $botCtx, $act) {
    $build(function ($b) use ($unitDmg, $baseDmg) {
        $b->MyLeader('ASH_005', true, true, true, 'unit'); $b->MyBase('SOR_020', $baseDmg); $b->WithCurrentRoundBeing(7);
        $b->WithGroundUnitForPlayer(1, 'SOR_095', true, $unitDmg);
        for ($k = 0; $k < 30; $k++) { $b->WithCardInDeckForPlayer(1, 'SOR_095'); $b->WithCardInDeckForPlayer(2, 'SOR_095'); }
    });
    $mz = ''; foreach (GetZone('myGroundArena') as $i => $o) if (strval($o->CardID) === 'SOR_095') $mz = "myGroundArena-$i";
    $raiseAttack(1, $mz);
    for ($k = 0; $k < 3 && $botCtx('midrange')['kind'] === 'decision' && $botCtx('midrange')['tooltip'] !== 'Heal_2_from_that_unit_or_your_base'; $k++) $act(1, 100, 'PASS');
    return $botCtx('midrange')['tooltip'];
};

// A) The Marine has 1 damage, my base 10: healing the Marine restores 1, the base 2 — the base. Today the Marine.
$check($heal(1, 10) === 'Heal_2_from_that_unit_or_your_base', 'A fixture: the heal pick is pending');
$check(str_starts_with($pick('no-healamount'), 'myGroundArena-'), 'A fixture: today the unit (by value)');
$check($pick() === 'myBase-0', 'A: the base — 2 restored, not 1; got ' . $pick());
// B) The Marine has 2 damage, my base 10 (20 left): both restore 2 — the unit (a unit point is worth a little more).
$heal(2, 10);
$check(str_starts_with($pick(), 'myGroundArena-'), 'B: equal heals, base safe — the unit; got ' . $pick());
// …by score, not by the prompt listing the unit first.
$c = $botCtx('midrange'); $sc = [];
foreach ($c['actions'] as $i => $a) $sc[strval($a['cardID'])] = SWUBotScoreAction($c, $a, $i);
$u = null; foreach ($sc as $k => $v) if (str_starts_with($k, 'myGroundArena-')) $u = $v;
$check($u !== null && isset($sc['myBase-0']) && $u - $sc['myBase-0'] > 0.01, 'B: the unit outscores the base on equal heals (beyond the listing tiebreak); got ' . json_encode($sc));
// C) The Marine has 2 damage, my base 18 (12 left, under pressure): the base.
$heal(2, 18);
$check($pick() === 'myBase-0', 'C: equal heals, base under pressure — the base; got ' . $pick());

bot_test_finish();

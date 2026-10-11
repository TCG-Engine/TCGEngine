<?php
// Feature 'supportfirst' (p42) — a SUPPORT leader (ASH_014 The Mandalorian, deployed: "Support (When you deploy this leader, you may attack
// with another unit. It gains this unit's other abilities for this attack.) On Attack: If you have the initiative, you may draw a card.").
// OWNER PLAN 2026-10-08 (Mando Colossus, step 5): "6R deploy and use Mando's support on any unit that stuck". The deploy scored the flat
// W['deploy'] (1.5), so a ready unit that stuck (Zeb, 3.0 at the Constable) attacked FIRST — and the Support attack the deploy would have
// given it was lost (the unit is exhausted by then).
// Fixed: the deploy carries the Support attack — it is worth the planned Support attacker's best attack too (+ the lent "If you have the
// initiative, draw" while I hold it), so the deploy goes first and that unit attacks through it.
// Fixtures (dictionary-checked): ASH_014 The Mandalorian · JTL_021 Colossus · LAW_045 Zeb Orrelios (4/4 Sentinel) · SEC_163 Outer Rim
//   Constable (3/1) · SEC_148 Karis Nemik · LAW_118 Droid Laser Turret · SOR_095.
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php -d apc.enable_cli=1 -d xdebug.mode=off SWUSim/DevTools/tests/bot_supportfirst_test.php
require __DIR__ . '/fixtures/bot_test_bootstrap.php';
foreach (['BotLegalActions', 'Custom/BotLookahead', 'Custom/BotEvaluator', 'Rl/CardTags', 'Custom/BotStyles', 'Custom/BotResourcing',
          'Custom/BotGuides', 'Custom/BotFallback', 'Custom/BotRules', 'BotHeuristic'] as $f) include_once "./SWUSim/$f.php";
$check(SWUBotVariantDisabled('no-supportfirst') === ['supportfirst'], 'supportfirst is switchable');
$check(in_array('supportfirst', SWUBotFeatureGroups()['p42'] ?? [], true), 'supportfirst is in group p42');
$pick = function (string $variant = '') use (&$gameName) {
    $l = SWUBotLegalActions($gameName, 1);
    $p = SWUBotHeuristicChoose('hardcontrol', (array)$l['actions'], $l, $variant);
    return strval($p['cardID'] ?? '');
};
$deploy = 'myLeader-0!CustomInput!DeployLeader:Unit';
// Round 5, 6 resources (Mando can deploy), Karis + Turret in hand, their Outer Rim Constable; $zeb: my Zeb stuck and is ready.
$board = function (bool $zeb, bool $init = false) use ($build) {
    $build(function ($b) use ($zeb, $init) {
        $b->MyLeader('ASH_014', true); $b->MyBase('JTL_021', 4); $b->FillResourcesForPlayer(1, 'SOR_095', 6); $b->WithCurrentRoundBeing(5);
        if ($init) $b->WithInitiativePlayerBeing(1); else $b->WithInitiativePlayerBeing(2);
        if ($zeb) $b->WithGroundUnitForPlayer(1, 'LAW_045', true);
        $b->WithCardInHandForPlayer(1, 'SEC_148'); $b->WithCardInHandForPlayer(1, 'LAW_118');
        $b->WithGroundUnitForPlayer(2, 'SEC_163', true);
        for ($k = 0; $k < 30; $k++) { $b->WithCardInDeckForPlayer(1, 'SOR_095'); $b->WithCardInDeckForPlayer(2, 'SOR_095'); }
    });
};
$score = function (string $id, array $disabled = []) use ($botCtx) {
    SWUBotSetDisabledFeatures($disabled);
    $c = $botCtx('hardcontrol'); $s = null;
    foreach ($c['actions'] as $i => $a) if (strval($a['cardID']) === $id) $s = SWUBotScoreAction($c, $a, $i);
    SWUBotSetDisabledFeatures([]);
    return $s;
};

// A) Zeb stuck: today Zeb attacks first (the Support attack is then lost).
$board(true);
$check($pick('no-supportfirst') === 'myGroundArena-0!FSM!', 'A fixture: today Zeb attacks first; got ' . $pick('no-supportfirst'));
$check($pick() === $deploy, 'A: deploy first — Zeb attacks through Mando\'s Support; got ' . $pick());
$act(1, 10001, $deploy);
for ($k = 0; $k < 4 && $botCtx('hardcontrol')['kind'] === 'decision'; $k++) {
    $tip = strval($botCtx('hardcontrol')['tooltip'] ?? '');
    $p = $pick();
    if (str_contains(strtolower($tip), 'support') || str_contains($tip, 'attack_with')) $check(GetZoneObject(preg_replace('/!.*/', '', $p))->CardID === 'LAW_045', 'A: the Support attacker is Zeb; got ' . $p);
    $act(1, 100, $p);
}
$con = array_filter(GetGroundArena(2), fn($u) => $u !== null && empty($u->removed));
$check(count($con) === 0, 'A: Zeb\'s Support attack defeated the Constable');
$zebObj = null; foreach (GetGroundArena(1) as $u) if ($u !== null && empty($u->removed) && $u->CardID === 'LAW_045') $zebObj = $u;
$check($zebObj !== null, 'A: Zeb (4/4) survives the Constable\'s 3');

// B) No unit stuck: the deploy is scored as before.
$board(false);
$on = $score($deploy); $off = $score($deploy, ['supportfirst']);
$check($on !== null && abs($on - $off) < 1e-9, 'B: nothing to Support — the deploy is unchanged; got ' . json_encode([$on, $off]));

// C) Holding the initiative, the Supported attack also draws (Mando's On Attack, lent): worth a draw more than without it.
$board(true, true);
$withInit = $score($deploy) - $score($deploy, ['supportfirst']);
$board(true, false);
$noInit = $score($deploy) - $score($deploy, ['supportfirst']);
$W = SWUBotWeights('hardcontrol', 1);
$check(abs(($withInit - $noInit) - $W['draw'] * SWUBotDrawMultiplier(1)) < 1e-6, 'C: the lent draw is worth a draw; got ' . json_encode([$withInit, $noInit]));

// D) A leader WITHOUT Support (SOR_005 Luke Skywalker) and the same stuck Zeb: its deploy makes no attack for Zeb — unchanged.
$build(function ($b) {
    $b->MyLeader('SOR_005', true); $b->MyBase('JTL_021', 4); $b->FillResourcesForPlayer(1, 'SOR_095', 6); $b->WithCurrentRoundBeing(5);
    $b->WithGroundUnitForPlayer(1, 'LAW_045', true); $b->WithGroundUnitForPlayer(2, 'SEC_163', true);
    for ($k = 0; $k < 30; $k++) { $b->WithCardInDeckForPlayer(1, 'SOR_095'); $b->WithCardInDeckForPlayer(2, 'SOR_095'); }
});
$on = $score($deploy); $off = $score($deploy, ['supportfirst']);
$check($on !== null && abs($on - $off) < 1e-9, 'D: no Support — the deploy is unchanged; got ' . json_encode([$on, $off]));

// E) The Support attack is a "may": a stuck unit whose only attack loses it (a 3/3 into their 4/4 Sentinel Zeb; their initiative, so no lent draw) adds nothing — never less.
$build(function ($b) {
    $b->MyLeader('ASH_014', true); $b->MyBase('JTL_021', 4); $b->FillResourcesForPlayer(1, 'SOR_095', 6); $b->WithCurrentRoundBeing(5);
    $b->WithInitiativePlayerBeing(2); $b->WithGroundUnitForPlayer(1, 'SOR_095', true); $b->WithGroundUnitForPlayer(2, 'LAW_045', true);
    for ($k = 0; $k < 30; $k++) { $b->WithCardInDeckForPlayer(1, 'SOR_095'); $b->WithCardInDeckForPlayer(2, 'SOR_095'); }
});
$on = $score($deploy); $off = $score($deploy, ['supportfirst']);
$check($on !== null && abs($on - $off) < 1e-9, 'E: a losing Support attack is skipped, not charged; got ' . json_encode([$on, $off]));

bot_test_finish();

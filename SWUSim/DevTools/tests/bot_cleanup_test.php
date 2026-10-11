<?php
// Feature 'cleanup' (p42) — HARD CONTROL against an aggro deck clears the board before it hits the base. OWNER, 2026-10-08 (Mando Colossus):
// "The Mandalorian plays hard control, so prefer to clean up the board until you've stabilized against aggro's gameplan."
// The attack target was priced the same for every style's board read: a 9-power Rey took 9 off the base (5.4) over killing a 3/2 Karis
// (3.0) while that Karis and a Green Leader stood ready to hit back for 6.
// Fixed: a hard-control seat facing an aggro leader, while the enemy board can still hit my base, sends the attack at a unit it defeats
// (a clean kill, or a trade worth taking) ahead of the base — unless the base hit would finish the opponent.
// "Stabilised" here is that the aggro board has nothing left that can reach my base (SWUBotBasePotential = 0).
// Fixtures (dictionary-checked): ASH_014 The Mandalorian · JTL_021 Colossus · LAW_149 Rey (9/9) · SEC_148 Karis Nemik (3/2) · ASH_153
//   Green Leader (3/1) · LOF_093 Gungi (2/5) · ASH_009 (an aggro leader, SWU_BOT_AGGRO_LEADERS) · LAW_008 Krennic (not aggro) · SOR_095.
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php -d apc.enable_cli=1 -d xdebug.mode=off SWUSim/DevTools/tests/bot_cleanup_test.php
require __DIR__ . '/fixtures/bot_test_bootstrap.php';
foreach (['BotLegalActions', 'Custom/BotLookahead', 'Custom/BotEvaluator', 'Rl/CardTags', 'Custom/BotStyles', 'Custom/BotResourcing',
          'Custom/BotGuides', 'Custom/BotFallback', 'Custom/BotRules', 'BotHeuristic'] as $f) include_once "./SWUSim/$f.php";
$check(SWUBotVariantDisabled('no-cleanup') === ['cleanup'], 'cleanup is switchable');
$check(in_array('cleanup', SWUBotFeatureGroups()['p42'] ?? [], true), 'cleanup is in group p42');
$pick = function (string $variant = '', string $style = 'hardcontrol') use (&$gameName) {
    $l = SWUBotLegalActions($gameName, 1);
    $p = SWUBotHeuristicChoose($style, (array)$l['actions'], $l, $variant);
    return strval($p['cardID'] ?? '');
};
// My ready $mine attacks; their leader $lead with $theirs on the ground; their base on $theirDmg.
$board = function (string $lead, string $mine, array $theirs, int $theirDmg = 0) use ($build, $act) {
    $build(function ($b) use ($lead, $mine, $theirs, $theirDmg) {
        $b->MyLeader('ASH_014', true); $b->MyBase('JTL_021', 6); $b->FillResourcesForPlayer(1, 'SOR_095', 5); $b->WithCurrentRoundBeing(4);
        $b->TheirLeader($lead); $b->TheirBase('SOR_020', $theirDmg);
        $b->WithGroundUnitForPlayer(1, $mine, true);
        foreach ($theirs as $t) $b->WithGroundUnitForPlayer(2, $t, true);
        for ($k = 0; $k < 30; $k++) { $b->WithCardInDeckForPlayer(1, 'SOR_095'); $b->WithCardInDeckForPlayer(2, 'SOR_095'); }
    });
    $act(1, 10002, 'myGroundArena-0!FSM!');   // the attack: the target prompt is the decision under test
};
$base = 'theirBase-0';

// A) Rey (9/9) vs aggro Karis + Green Leader: today the base; clean-up kills a unit.
$board('ASH_009', 'LAW_149', ['SEC_148', 'ASH_153']);
$check($pick('no-cleanup') === $base, 'A fixture: today the base; got ' . $pick('no-cleanup'));
$check(str_starts_with($pick(), 'theirGroundArena-'), 'A: hard control clears a unit first; got ' . $pick());
// …only hard control: a midrange Rey still takes the base.
$check($pick('', 'midrange') === $base, 'A: midrange is unchanged — the base; got ' . $pick('', 'midrange'));

// B) Not an aggro leader (Krennic): the race is not aggro's — the base, as before.
$board('LAW_008', 'LAW_149', ['SEC_148', 'ASH_153']);
$check($pick() === $base, 'B: against a non-aggro leader — the base; got ' . $pick());

// C) The base hit finishes them (their base on 9 remaining: Rey's 9 is lethal): the base — the rule layer's lethal take, not this feature.
$board('ASH_009', 'LAW_149', ['SEC_148', 'ASH_153'], intval(CardHp('SOR_020')) - 9);
$check(SWUBaseRemainingHp(2) <= 9, 'C fixture: 9 is lethal; their base has ' . SWUBaseRemainingHp(2));
$check($pick() === $base, 'C: lethal — the base; got ' . $pick());

// D) Nothing to defeat (Karis 3/2 into a Gungi 2/5): no clean-up target, so nothing changes.
$board('ASH_009', 'SEC_148', ['LOF_093']);
$ctx = $botCtx('hardcontrol');
foreach ($ctx['actions'] as $i => $a) {
    $on = SWUBotScoreAction($ctx, $a, $i); SWUBotSetDisabledFeatures(['cleanup']); $off = SWUBotScoreAction($ctx, $a, $i); SWUBotSetDisabledFeatures([]);
    $check(abs($on - $off) < 1e-9, 'D: no kill on offer — ' . $a['cardID'] . ' unchanged; got ' . json_encode([$on, $off]));
}

// E) Stabilised: my Sentinel Zeb (4/4) holds the ground, so Karis and Green Leader cannot reach my base — Rey takes the base again.
$build(function ($b) {
    $b->MyLeader('ASH_014', true); $b->MyBase('JTL_021', 6); $b->FillResourcesForPlayer(1, 'SOR_095', 5); $b->WithCurrentRoundBeing(4);
    $b->TheirLeader('ASH_009'); $b->TheirBase('SOR_020');
    $b->WithGroundUnitForPlayer(1, 'LAW_149', true); $b->WithGroundUnitForPlayer(1, 'LAW_045', false);
    $b->WithGroundUnitForPlayer(2, 'SEC_148', true); $b->WithGroundUnitForPlayer(2, 'ASH_153', true);
    for ($k = 0; $k < 30; $k++) { $b->WithCardInDeckForPlayer(1, 'SOR_095'); $b->WithCardInDeckForPlayer(2, 'SOR_095'); }
});
$check(SWUBotBasePotential(2, 1, false) === 0, 'E fixture: nothing of theirs reaches my base; got ' . SWUBotBasePotential(2, 1, false));
$act(1, 10002, 'myGroundArena-0!FSM!');
$check($pick() === $base, 'E: stabilised — the base; got ' . $pick());

// F) An even trade is clean-up: Karis (3/2) into a Green Leader (3/1) trades two 2-drops rather than chip the base.
$board('ASH_009', 'SEC_148', ['ASH_153', 'SEC_148']);
$p = $pick();
$check(str_starts_with($p, 'theirGroundArena-'), 'F: Karis trades into a 2-drop rather than chip the base; got ' . $p);

// G) A LOSING trade is not: a Rey on 1 HP (8 damage) would die killing a Karis — the 9 goes to the base, as before.
$build(function ($b) {
    $b->MyLeader('ASH_014', true); $b->MyBase('JTL_021', 6); $b->FillResourcesForPlayer(1, 'SOR_095', 5); $b->WithCurrentRoundBeing(4);
    $b->TheirLeader('ASH_009'); $b->TheirBase('SOR_020');
    $b->WithGroundUnitForPlayer(1, 'LAW_149', true, 8); $b->WithGroundUnitForPlayer(2, 'SEC_148', true);
    for ($k = 0; $k < 30; $k++) { $b->WithCardInDeckForPlayer(1, 'SOR_095'); $b->WithCardInDeckForPlayer(2, 'SOR_095'); }
});
$act(1, 10002, 'myGroundArena-0!FSM!');
$check($pick('no-cleanup') === $base && $pick() === $base, 'G: a losing trade stays the base; got ' . $pick());

bot_test_finish();

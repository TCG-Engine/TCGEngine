<?php
// Feature 'stacklethal' (p36) — on a Support leader's flip turn, the unit that takes the stacked Plot buffs and makes the Support
// attack is one whose SINGLE attack is lethal, when one exists. Ninin (Ahsoka Yellow) vs RussellHoskins (Chewbacca HMW Blue),
// human game 2026-10-04, R5: Ahsoka deployed, three Jar Jar Binks Plays (each "give another friendly unit +2/+2", the uniqueness
// rule defeating the copy before) all onto Mando's N-1 Starfighter, whose own On Attack ("exhaust a friendly leader: +2/+0") and
// the Support attack finished the base in ONE action. Owner 2026-10-04: build it.
// 'buffspread' (p20) already stacks every Plot buff on its planned Support attacker, but plans by CURRENT attack power: it picked
// Open Circle Ace (2) over the N-1 (1), and 2 + 6 = 8 is one short where 1 + 6 + 2 = 9 is lethal. The plan now projects each
// candidate's single attack: attack power + this buff + the Plot buffs still in my resources (affordable) + its own On Attack
// "+N/+0 for this attack" (+ the Raid Support lends), and a candidate that reaches the base's remaining HP goes first.
// Fixtures (dictionary-checked): ASH_009 Ahsoka Tano · SEC_111 Jar Jar Binks (2, Plot, "+2/+2 to another friendly unit") · ASH_203
//   Mando's N-1 Starfighter 1/3 (Support; On Attack exhaust a friendly leader: +2/+0) · ASH_201 Open Circle Ace 2/2 · SEC_213 A-Wing
//   1/2 Raid 1 · SOR_095 Battlefield Marine · SHD_009 Hunter · SOR_020 (30 HP) · ASH_026 · LAW_097 (resources)
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php -d apc.enable_cli=1 -d xdebug.mode=off SWUSim/DevTools/tests/bot_stacklethal_test.php
require __DIR__ . '/fixtures/bot_test_bootstrap.php';
foreach (['BotLegalActions', 'Custom/BotLookahead', 'Custom/BotEvaluator', 'Rl/CardTags', 'Custom/BotStyles', 'Custom/BotResourcing',
          'Custom/BotGuides', 'Custom/BotFallback', 'Custom/BotRules', 'BotHeuristic'] as $f) include_once "./SWUSim/$f.php";
$check(SWUBotVariantDisabled('no-stacklethal') === ['stacklethal'], 'stacklethal is switchable');
$check(in_array('stacklethal', SWUBotFeatureGroups()['p36'] ?? [], true), 'stacklethal is in group p36');
// The R5 board: 3 Jar Jars + 3 plain cards in my resources, N-1 / Ace / A-Wing ready in space; their base on $hp. The bot plays
// the whole flip turn (deploy -> Plots -> buffs -> Support attack) until the turn leaves it. Returns [base HP left, Jar Jar hosts].
$flip = function (int $hp, string $variant) use ($build, $act, &$gameName) {
    $build(function ($b) use ($hp) {
        $b->MyLeader('ASH_009', true); $b->MyBase('ASH_026');
        $b->TheirLeader('SHD_009', false); $b->TheirBase('SOR_020', 30 - $hp);
        foreach (['SEC_111', 'SEC_111', 'SEC_111', 'LAW_097', 'LAW_097', 'LAW_097'] as $c) $b->WithControlledResourceForPlayer(1, $c, 1, true);
        foreach (['ASH_203', 'ASH_201', 'SEC_213'] as $c) $b->WithSpaceUnitForPlayer(1, $c, true);
        $b->WithGroundUnitForPlayer(2, 'SOR_095', true);
    });
    $hosts = [];
    for ($i = 0; $i < 40 && SWUBaseRemainingHp(2) > 0; $i++) {
        $l = SWUBotLegalActions($gameName, 1);
        if (empty($l['actions'])) break;
        $p = SWUBotHeuristicChoose('hyperaggro', (array)$l['actions'], $l, $variant);
        if ($p === null || str_contains(strval($p['cardID']), 'Pass')) break;
        if (str_starts_with(strval($l['decisionTooltip'] ?? ''), 'Give_another_friendly_unit')) $hosts[] = strval(SWUBotViewForMz(1, strval($p['cardID']))['cardID'] ?? '?');
        $act(1, intval($p['mode'] ?? 100), strval($p['cardID']));
    }
    return [SWUBaseRemainingHp(2), $hosts];
};

// A) 9 HP: N-1 + 6 + its own +2 = 9 in one attack. Today the Ace takes the buffs (8) and the base survives the Support attack.
[$hpOff, $hostsOff] = $flip(9, 'no-stacklethal');
$check($hpOff > 0 && in_array('ASH_201', $hostsOff, true), 'A fixture: today the Ace is the host and 9 HP survives; got ' . $hpOff . ' ' . json_encode($hostsOff));
[$hpOn, $hostsOn] = $flip(9, '');
$check($hpOn <= 0, 'A: one stacked N-1 attack is lethal; got HP ' . $hpOn . ' hosts ' . json_encode($hostsOn));
$check($hostsOn === ['ASH_203', 'ASH_203', 'ASH_203'], 'A: all three Jar Jars go on the N-1; got ' . json_encode($hostsOn));
// B) 20 HP: no single attack is lethal — the plan is the old one (the Ace, by attack power).
[, $hostsB] = $flip(20, '');
[, $hostsBoff] = $flip(20, 'no-stacklethal');
$check($hostsB === $hostsBoff, 'B: no lethal within reach — unchanged; got ' . json_encode($hostsB) . ' vs ' . json_encode($hostsBoff));

// C) Plot buffs count only as far as ready resources pay: 3 Jar Jars but 3 resources in all -> one more +2.
$build(function ($b) {
    $b->MyLeader('ASH_009', true); $b->MyBase('ASH_026'); $b->TheirBase('SOR_020');
    foreach (['SEC_111', 'SEC_111', 'SEC_111'] as $c) $b->WithControlledResourceForPlayer(1, $c, 1, true);
});
$check(_SWUBotPlotBuffsLeft(1) === 2, 'C: 3 resources pay for one Jar Jar (+2); got ' . _SWUBotPlotBuffsLeft(1));
// D) The N-1's self-boost needs a ready leader to exhaust: ready Ahsoka -> +2, exhausted -> 0.
foreach ([true => 2, false => 0] as $ready => $want) {
    $build(function ($b) use ($ready) { $b->MyLeader('ASH_009', (bool)$ready); $b->MyBase('ASH_026'); $b->WithSpaceUnitForPlayer(1, 'ASH_203', true); });
    $got = _SWUBotSelfAttackBoost(1, SWUBotUnits(1)[0]);
    $check($got === $want, 'D: leader ' . ($ready ? 'ready' : 'exhausted') . " -> N-1 self-boost $want; got $got");
}
// E) A space Sentinel (T-6 Shuttle 1974, 2/6) blocks the base: no space unit's attack is a lethal path — the plan is the old one.
$build(function ($b) {
    $b->MyLeader('ASH_009', true); $b->MyBase('ASH_026'); $b->TheirBase('SOR_020', 19);   // 11 HP: only the N-1 (1+2+6+2) would reach it
    foreach (['SEC_111', 'SEC_111', 'SEC_111', 'LAW_097', 'LAW_097', 'LAW_097'] as $c) $b->WithControlledResourceForPlayer(1, $c, 1, true);
    foreach (['ASH_203', 'ASH_201', 'SEC_213'] as $c) $b->WithSpaceUnitForPlayer(1, $c, true);
    $b->WithSpaceUnitForPlayer(2, 'ASH_109', true);
});
$GLOBALS['SWUBotDisabledFeatures'] = ['stacklethal']; $planOff = _SWUBotSupportPlanUid(1, 2);
$GLOBALS['SWUBotDisabledFeatures'] = []; $planOn = _SWUBotSupportPlanUid(1, 2);
$check($planOn === $planOff, "E: blocked by a Sentinel — the plan is unchanged; got $planOn vs $planOff");

bot_test_finish();

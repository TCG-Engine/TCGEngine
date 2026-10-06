<?php
// Proposals 'doomedtie' + 'wdability' (HELD 2026-10-06: measured harmful on Krennic Splash, 1 won / 9 lost paired, p=.02) — among DOOMED units (an enemy can kill it: 'doomedsac' prices it at a flat 0.5), the cheaper one is the
// sacrifice. With the flat price every doomed unit TIED and the first one listed went: traced 60 Krennic Splash vs Ahsoka Blue games
// (2026-10-06), Director Krennic's Credit Action ("Exhaust, defeat a friendly unit: Create a Credit") sacrificed the Director Krennic
// UNIT (JTL_032) 17 times, over a Spy token in ka001 and kc010. The owner never does: in all five of their recorded Krennic games vs aggro
// bots the Krennic unit comes down round 1 and attacks every round (it is the deck's engine — "the first unit you play each round that
// has a When Defeated ability costs 1 less"). The doomed price is now 0.5 + 0.01 x the unit's value: still below any healthy body,
// still above a payback When Defeated, but ordered.
// Fixtures (dictionary-checked): LAW_008 Director Krennic (leader) · LAW_020 · JTL_032 Director Krennic (unit, 2/2) · SEC_T01 Spy token
//   0/2 · ASH_116 Ant Droid (When Defeated: draw) · ASH_009 · SOR_095 Battlefield Marine 3/3
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php -d apc.enable_cli=1 -d xdebug.mode=off SWUSim/DevTools/tests/bot_doomedtie_test.php
require __DIR__ . '/fixtures/bot_test_bootstrap.php';
foreach (['BotLegalActions', 'Custom/BotLookahead', 'Custom/BotEvaluator', 'Rl/CardTags', 'Custom/BotStyles', 'Custom/BotResourcing',
          'Custom/BotGuides', 'Custom/BotFallback', 'Custom/BotRules', 'BotHeuristic'] as $f) include_once "./SWUSim/$f.php";
$check(SWUBotVariantDisabled('try-doomedtie') === ['try:doomedtie'] && SWUBotVariantDisabled('try-wdability') === ['try:wdability'], 'both are proposals (@try-)');
$check(!in_array('doomedtie', SWUBotFeatureList(), true) && !in_array('wdability', SWUBotFeatureList(), true), 'neither is shipped');
$ON = 'try-doomedtie'; $BOTH = ['try:doomedtie', 'try:wdability'];
// Krennic's leader Action raised; my units $mine (exhausted, both doomed by their ready Marine); returns the unit sacrificed.
$sac = function (array $mine, string $variant) use ($build, $act, &$gameName) {
    $build(function ($b) use ($mine) {
        $b->MyLeader('LAW_008', true); $b->MyBase('LAW_020'); $b->TheirLeader('ASH_009', false);
        foreach ($mine as $c) $b->WithGroundUnitForPlayer(1, $c, false);
        $b->WithGroundUnitForPlayer(2, 'SOR_095', true);
    });
    $act(1, 10001, 'myLeader-0!CustomInput!LeaderAbility');
    $l = SWUBotLegalActions($gameName, 1);
    if (($l['decisionTooltip'] ?? '') !== 'Defeat_a_friendly_unit_to_create_a_Credit') return 'no prompt: ' . ($l['decisionTooltip'] ?? '');
    $p = SWUBotHeuristicChoose('softcontrol', (array)$l['actions'], $l, $variant);
    $v = SWUBotViewForMz(1, strval($p['cardID'] ?? ''));
    return $v['cardID'] ?? '?';
};
// A) The traced shape (ka001 / kc010): the Krennic unit listed first, a Spy token second. Before 'shieldtrader' (p37, shipped; owner:
// "unshielded krennic vs a Spy, i'd sac the Spy") the Krennic unit went; fixed, the Spy.
$check($sac(['JTL_032', 'SEC_T01'], 'no-shieldtrader') === 'JTL_032', 'A fixture: without shieldtrader the bot sacrifices the Krennic unit over a Spy token');
$check($sac(['JTL_032', 'SEC_T01'], '') === 'SEC_T01', 'A: the shipped bot (shieldtrader) sacrifices the Spy token; got ' . $sac(['JTL_032', 'SEC_T01'], ''));
$check($sac(['JTL_032', 'SEC_T01'], 'try-krennicsac') === 'SEC_T01', 'A: with both proposals the Spy token goes; got ' . $sac(['JTL_032', 'SEC_T01'], 'try-krennicsac'));
// B) Order does not matter: Spy first, Krennic unit second — still the Spy.
$check($sac(['SEC_T01', 'JTL_032'], 'try-krennicsac') === 'SEC_T01', 'B: listed second, the Spy still goes');
// C) A payback When Defeated (Ant Droid draws) stays the cheapest of all, doomed or not.
$check($sac(['JTL_032', 'ASH_116'], 'try-krennicsac') === 'ASH_116', 'C: Ant Droid (draws on death) over the Krennic unit; got ' . $sac(['JTL_032', 'ASH_116'], ''));

// D) 'wdability': the Krennic unit's text MENTIONS "a 'When Defeated' ability" — it has none itself. Its sacrifice price is its value
// (2), not the When Defeated discount (0.5) it used to get; Ant Droid's real "When Defeated: Draw a card." keeps its discount.
$build(function ($b) { $b->MyLeader('LAW_008', true); $b->MyBase('LAW_020'); $b->WithGroundUnitForPlayer(1, 'JTL_032', false); $b->WithGroundUnitForPlayer(1, 'ASH_116', false); });
[$k, $ant] = SWUBotUnits(1);
$kOff = _SWUBotSacrificeCostByValue($k);
$check($kOff < SWUBotUnitValue($k), "D fixture: today the Krennic unit is discounted as When Defeated fodder ($kOff)");
SWUBotSetDisabledFeatures($BOTH);
$check(abs(_SWUBotSacrificeCostByValue($k) - SWUBotUnitValue($k)) < 1e-9, 'D: the Krennic unit is priced at its value; got ' . _SWUBotSacrificeCostByValue($k));
$check(_SWUBotSacrificeCostByValue($ant) < SWUBotUnitValue($ant), 'D: Ant Droid keeps its When Defeated discount');
$check(_SWUBotFodderRank('JTL_032', 2) === 3 && _SWUBotFodderRank('ASH_116', 1) === 2, 'D: fodder rank by cost <= 2 still holds for both');
$check(_SWUBotFodderRank('ASH_048', 4) === null, 'D: a 4-cost unit without a When Defeated ability is not fodder');
SWUBotSetDisabledFeatures([]);

bot_test_finish();

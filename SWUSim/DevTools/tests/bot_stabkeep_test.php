<?php
// Feature 'stabkeep' (p42) — a MASS DEBUFF ("Give each enemy unit -N/-N", SEC_051 Bo-Katan Kryze: -3/-3) is kept against aggro, like the
// wipes. OWNER PLAN 2026-10-08 (Mando Colossus, step 6): "you play leading up to Bo-Katan and SRI off-aspect for 10 resources to stabilize
// and swing the game in your favor"; owner (Krennic Splash, 2026-10-06): resource "late bombs vs aggro, never the wipes".
// 'wipekeepaggro' keeps Single Reactor Ignition (tagged 'wipe'), but Bo-Katan is tagged 'debuff-all-enemy-units', so pre-flip against an
// aggro leader she sat in the "7+ drops go first" tier and was the card resourced.
// Fixtures (dictionary-checked): ASH_014 The Mandalorian · JTL_021 Colossus · SEC_051 Bo-Katan Kryze · LAW_044 Single Reactor Ignition ·
//   SEC_148 Karis Nemik · LAW_118 Droid Laser Turret · SEC_163 Outer Rim Constable · ASH_009 (an aggro leader) · LAW_008 Krennic · SOR_095.
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php -d apc.enable_cli=1 -d xdebug.mode=off SWUSim/DevTools/tests/bot_stabkeep_test.php
require __DIR__ . '/fixtures/bot_test_bootstrap.php';
foreach (['BotLegalActions', 'Custom/BotLookahead', 'Custom/BotEvaluator', 'Rl/CardTags', 'Custom/BotStyles', 'Custom/BotResourcing',
          'Custom/BotGuides', 'Custom/BotFallback', 'Custom/BotRules', 'BotHeuristic'] as $f) include_once "./SWUSim/$f.php";
$check(SWUBotVariantDisabled('no-stabkeep') === ['stabkeep'], 'stabkeep is switchable');
$check(in_array('stabkeep', SWUBotFeatureGroups()['p42'] ?? [], true), 'stabkeep is in group p42');
// Round 5, 5 resources (Mando not yet flipped), $hand; their leader $lead with a Karis on the ground.
$board = function (string $lead, array $hand) use ($build) {
    $build(function ($b) use ($lead, $hand) {
        $b->MyLeader('ASH_014', true); $b->MyBase('JTL_021'); $b->FillResourcesForPlayer(1, 'SOR_095', 5); $b->WithCurrentRoundBeing(5);
        $b->TheirLeader($lead); $b->WithGroundUnitForPlayer(2, 'SEC_148', true);
        foreach ($hand as $c) $b->WithCardInHandForPlayer(1, $c);
        for ($k = 0; $k < 30; $k++) { $b->WithCardInDeckForPlayer(1, 'SOR_095'); $b->WithCardInDeckForPlayer(2, 'SOR_095'); }
    });
};
// The card the regroup would resource (one), by CardID.
$resourced = function (array $disabled = []) use ($botCtx) {
    SWUBotSetDisabledFeatures($disabled);
    $mz = strval(SWUBotChooseResourceCards($botCtx('hardcontrol'), 1)[0] ?? '');
    SWUBotSetDisabledFeatures([]);
    return preg_match('/^myHand-(\d+)$/', $mz, $m) ? strval(GetHand(1)[intval($m[1])]->CardID ?? '') : "?$mz";
};
$hand = ['SEC_051', 'LAW_044', 'SEC_148', 'LAW_118', 'SEC_163'];

// A) Against an aggro leader: today Bo-Katan is resourced; she is kept now (and so is SRI, as 'wipekeepaggro' already did).
$board('ASH_009', $hand);
$check($resourced(['stabkeep']) === 'SEC_051', 'A fixture: today Bo-Katan is resourced; got ' . $resourced(['stabkeep']));
$r = $resourced();
$check(!in_array($r, ['SEC_051', 'LAW_044'], true), 'A: neither Bo-Katan nor SRI is resourced vs aggro; got ' . $r);

// B) A second copy is still a spare: with two Bo-Katans in hand, one goes.
$board('ASH_009', ['SEC_051', 'SEC_051', 'LAW_044', 'SEC_148', 'LAW_118']);
$check($resourced() === 'SEC_051', 'B: the duplicate Bo-Katan is the spare; got ' . $resourced());

// C) Not an aggro matchup: unchanged.
$board('LAW_008', $hand);
$check($resourced() === $resourced(['stabkeep']), 'C: vs a non-aggro leader the pick is unchanged; got ' . json_encode([$resourced(), $resourced(['stabkeep'])]));

// D) Under '@try-resourcing2' the tiers run against ANY opponent, and "aggressive" is read off the board: one Karis (3 power) is not
//    aggressive, so the keep stays off there — Bo-Katan is judged as before.
$board('LAW_008', $hand);
$check($resourced(['try:resourcing2']) === $resourced(['try:resourcing2', 'stabkeep']),
    'D: resourcing2, a calm board — unchanged; got ' . json_encode([$resourced(['try:resourcing2']), $resourced(['try:resourcing2', 'stabkeep'])]));

bot_test_finish();

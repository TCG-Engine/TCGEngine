<?php
// Feature 'creditdeploy' (p42) — LAW_013 Chewbacca, Hero of Kessel: "Action [1 resource, Exhaust, defeat a friendly
// resource]: Deal 2 damage to a unit and create a Credit token. / Epic Action [4 resources]: Deploy this leader."
// OWNER's line (fixture header, chewbacca_law_alliance-outpost): "defeats a token on its first turn to create a Credit, to deploy Chewbacca on
// turn 2 with 3 resources + the Credit" — LAW_019 Alliance Outpost: "Epic Action [defeat a friendly token]: Give an Experience or Shield
// token to a unit, or create a Credit token." Leader audit 2026-10-08: the Outpost picked "Credit" in 14 of 513 traced uses (the board
// read behind the option pick has no Credits in it). Fixed: the Credit is taken when one more Credit makes the leader's resource-cost
// deploy affordable this round or next. (The audit's other Chewbacca finding — the mandatory 2 damage on his own unit with no enemy
// unit, 138 traces — is already refused on current code: case C pins it.)
// Fixtures (dictionary-checked): LAW_013 Chewbacca · LAW_019 Alliance Outpost · LOF_061 Secretive Sage (2, Shielded) · SOR_095.
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php -d apc.enable_cli=1 -d xdebug.mode=off SWUSim/DevTools/tests/bot_chewbacca_test.php
require __DIR__ . '/fixtures/bot_test_bootstrap.php';
foreach (['BotLegalActions', 'Custom/BotLookahead', 'Custom/BotEvaluator', 'Rl/CardTags', 'Custom/BotStyles', 'Custom/BotResourcing',
          'Custom/BotGuides', 'Custom/BotFallback', 'Custom/BotRules', 'BotHeuristic'] as $f) include_once "./SWUSim/$f.php";
$check(SWUBotVariantDisabled('no-creditdeploy') === ['creditdeploy'], 'creditdeploy is switchable');
$check(in_array('creditdeploy', SWUBotFeatureGroups()['p42'] ?? [], true), 'creditdeploy is in group p42');
$pick = function (string $variant = '') use (&$gameName) {
    $l = SWUBotLegalActions($gameName, 1);
    $p = SWUBotHeuristicChoose('hyperaggro', (array)$l['actions'], $l, $variant);
    return strval($p['cardID'] ?? '');
};
// Round $rnd, $res resources; a Secretive Sage played this round (its Shield is the Outpost's token); their Marine; then the Outpost.
$outpost = function (int $rnd, int $res) use ($build, $act, $botCtx) {
    $build(function ($b) use ($rnd, $res) {
        $b->MyLeader('LAW_013', true); $b->MyBase('LAW_019'); $b->FillResourcesForPlayer(1, 'SOR_095', $res); $b->WithCurrentRoundBeing($rnd);
        $b->WithCardInHandForPlayer(1, 'LOF_061'); $b->WithGroundUnitForPlayer(2, 'SOR_095', false);
        for ($k = 0; $k < 30; $k++) { $b->WithCardInDeckForPlayer(1, 'SOR_095'); $b->WithCardInDeckForPlayer(2, 'SOR_095'); }
    });
    $act(1, 10002, 'myHand-0!FSM!');
    for ($k = 0; $k < 3 && $botCtx('hyperaggro')['kind'] === 'decision'; $k++) $act(1, 100, 'PASS');
    $act(2, 10001, 'myHealth-0!CustomInput!Pass');
    $act(1, 10001, 'myBase-0!CustomInput!EpicAction');
    return $botCtx('hyperaggro')['tooltip'];
};

// A) Round 1, 2 resources: next round 3 + the Credit = Chewbacca's 4 — the Credit. Today a Shield.
$tip = $outpost(1, 2);
$check(str_starts_with($tip, 'Give_an_Experience_or_Shield_token'), 'A fixture: the Outpost option is pending; got ' . $tip);
$check($pick('no-creditdeploy') === 'Shield', 'A fixture: today the Shield');
$check($pick() === 'Credit', 'A: the Credit — Chewbacca deploys next round on 3 + 1; got ' . $pick());
// B) Round 4, 7 resources (5 left after the Sage): the deploy is affordable without it — the option is judged as before.
$outpost(4, 7);
$check($pick() === $pick('no-creditdeploy'), 'B: the deploy needs no Credit — unchanged; got ' . $pick());
// B2) Round 4, 5 resources: 3 left after the Sage — the Credit makes the 4 THIS round. The Credit.
$outpost(4, 5);
$check($pick() === 'Credit', 'B2: 3 left + the Credit = the deploy now; got ' . $pick());

// C) Chewbacca's Action with no enemy unit: the 2 damage could only hit my own Marine — not used (already so on current code).
$build(function ($b) {
    $b->MyLeader('LAW_013', true, false, true); $b->MyBase('SOR_020'); $b->FillResourcesForPlayer(1, 'SOR_095', 4); $b->WithCurrentRoundBeing(4);
    $b->WithGroundUnitForPlayer(1, 'SOR_095', false);
    for ($k = 0; $k < 30; $k++) { $b->WithCardInDeckForPlayer(1, 'SOR_095'); $b->WithCardInDeckForPlayer(2, 'SOR_095'); }
});
$check($pick() !== 'myLeader-0!CustomInput!LeaderAbility', 'C: only my own unit to hit — not used; got ' . $pick());

bot_test_finish();

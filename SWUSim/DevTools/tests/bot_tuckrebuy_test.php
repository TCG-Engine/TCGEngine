<?php
// SHIPPED 2026-10-09 (owner: "go with all three"): the 'tuck' re-buy share SWU_BOT_TUCK_REBUY_SHARE is 0.25 (was 0.5). A lower share
// measured better still but switches off the owner's loops; owner: keep 0.25 and fix the pricing instead. The share is what
// returning a unit with a "When Played" / Ambush / Shielded to hand is worth per resource of its cost (it can be cast again).
// Evidence: the overnight lever screen (2026-10-09, `lv` seeds) — share 0.25 / 0.5 / 1.0 → Qui-Gon 30.7 / 28.0 / 26.1%; confirmed on a
// fresh `cf` block: 0.25 vs 0.5 = 27.7 vs 22.5% (+5.2pp, paired 94 won / 41 lost, p < .0001), 8 of 11 opponents better, none worse;
// then (`pr` block) 0.1 / 0 vs 0.25: +1.9pp (35/16, p=.011) / +2.5pp (46/21, p=.003) — held back (see above).
// (Yoda's Force-heal re-buy at his whole cost — 'yodaloop' — is separate and unchanged.)
// Fixtures (dictionary-checked): LOF_016 Qui-Gon · LOF_023 Jedi Temple · SEC_101 Queen Amidala (5, When Played) · LAW_067 Jyn Erso (2) · SOR_095.
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php -d apc.enable_cli=1 -d xdebug.mode=off SWUSim/DevTools/tests/bot_tuckrebuy_test.php
require __DIR__ . '/fixtures/bot_test_bootstrap.php';
foreach (['BotLegalActions', 'Custom/BotLookahead', 'Custom/BotEvaluator', 'Rl/CardTags', 'Custom/BotStyles', 'Custom/BotResourcing',
          'Custom/BotGuides', 'Custom/BotFallback', 'Custom/BotRules', 'BotHeuristic'] as $f) include_once "./SWUSim/$f.php";
// Qui-Gon front with the Force; my exhausted Amidala (5, "When Played: Create 2 Spy tokens") and a Jyn Erso (2) in hand to play free.
$build(function ($b) {
    $b->MyLeader('LOF_016', true); $b->MyBase('LOF_023'); $b->WithForceForPlayer(1); $b->WithCurrentRoundBeing(4);
    $b->WithGroundUnitForPlayer(1, 'SEC_101', false); $b->WithCardInHandForPlayer(1, 'LAW_067');
    $b->WithGroundUnitForPlayer(2, 'SOR_095', false);
    for ($k = 0; $k < 30; $k++) { $b->WithCardInDeckForPlayer(1, 'SOR_095'); $b->WithCardInDeckForPlayer(2, 'SOR_095'); }
});
$v = null;
foreach (SWUBotUnits(1) as $u) if ($u['cardID'] === 'SEC_101') $v = $u;
$check($v !== null, 'fixture: Amidala on my board');
$gain = function (array $disabled) use ($v) {
    SWUBotSetDisabledFeatures($disabled); $g = _SWUBotTuckGainFor(1, $v, 'Villainy'); SWUBotSetDisabledFeatures([]);
    return $g;
};
$shipped = $gain([]);
$check($shipped !== null, 'fixture: the tuck pair Amidala -> Jyn is priced');
// The pair: re-casting Amidala's When Played (0.25 x her cost 5) + the free play (Jyn, 2) − the body that leaves.
$expect = 0.25 * 5 + 2 - SWUBotUnitValue($v) * max(0, intval($v['remaining'])) / max(1, intval($v['hp'])) - SWUBotUnusedSacPremium($v);
$check(abs($shipped - $expect) < 1e-9, 'the re-buy of a 5-cost When Played unit is worth 0.25 x 5; got ' . json_encode([$shipped, $expect]));

bot_test_finish();

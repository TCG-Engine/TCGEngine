<?php
// Features 'shipdiscount' + 'deployfirst' (p42) — JTL_005 Admiral Piett.
//   Front: "Action [Exhaust]: Play a Capital Ship unit from your hand. It costs 1 resource less."
//   Deployed: "Each Capital Ship unit you play costs 2 resources less."
// Leader audit 2026-10-08 (300 traced games per list): the Action was priced at what it UNLOCKS only, so an affordable ship read the -1
// as worth nothing and was hard-cast at full price while the Action was ready (367 Blue / 248 Red) — even when the saved resource paid for
// a second card. And 'blockerfirst' (behind on bodies, a unit before the attacks) hard-cast a 5-cost ship right before deploying Piett,
// whose -2 would have applied (75/300 Blue, 83/300 Red). ('piettcheat' — the 2026-09-22 proposal — measured +0 and stays a proposal.)
// Fixed: the Action is the ship's play plus the extra card the saved resource pays for (no extra card: just under the direct play — the
// same outcome); a play that a deploy on offer right now would discount waits for that deploy.
// Fixtures (dictionary-checked): JTL_005 Piett (Command/Villainy) · JTL_020 (Vigilance) · JTL_038 Corvus (5, Capital Ship)
//   · ASH_099 Gozanti Assault Carrier (5, Capital Ship) · SHD_116 Outlaw Corona (3, Capital Ship) · SEC_110 GNK Power Droid (2) · SOR_095 Battlefield Marine.
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php -d apc.enable_cli=1 -d xdebug.mode=off SWUSim/DevTools/tests/bot_shipdiscount_test.php
require __DIR__ . '/fixtures/bot_test_bootstrap.php';
foreach (['BotLegalActions', 'Custom/BotLookahead', 'Custom/BotEvaluator', 'Rl/CardTags', 'Custom/BotStyles', 'Custom/BotResourcing',
          'Custom/BotGuides', 'Custom/BotFallback', 'Custom/BotRules', 'BotHeuristic'] as $f) include_once "./SWUSim/$f.php";
foreach (['shipdiscount', 'deployfirst'] as $f) {
    $check(SWUBotVariantDisabled("no-$f") === [$f], "$f is switchable");
    $check(in_array($f, SWUBotFeatureGroups()['p42'] ?? [], true), "$f is in group p42");
}
$pick = function (string $variant = '') use (&$gameName) {
    $l = SWUBotLegalActions($gameName, 1);
    $p = SWUBotHeuristicChoose('softcontrol', (array)$l['actions'], $l, $variant);
    return strval($p['cardID'] ?? '');
};
$ability = 'myLeader-0!CustomInput!LeaderAbility';
$deploy = 'myLeader-0!CustomInput!DeployLeader:Unit';

// A) 6 resources, Corvus (5) + GNK Power Droid (2): full price, Corvus leaves 1 and the Droid waits; through Piett, Corvus costs 4 and
// the Droid fits too. Today Corvus is hard-cast; fixed, the Action (or the Droid first — either way both are played).
$build(function ($b) {
    $b->MyLeader('JTL_005', true, false, true); $b->MyBase('JTL_020'); $b->FillResourcesForPlayer(1, 'SOR_095', 6); $b->WithCurrentRoundBeing(6);
    $b->WithCardInHandForPlayer(1, 'JTL_038'); $b->WithCardInHandForPlayer(1, 'SEC_110');
    $b->WithGroundUnitForPlayer(2, 'SOR_095', false);
    for ($k = 0; $k < 30; $k++) { $b->WithCardInDeckForPlayer(1, 'SOR_095'); $b->WithCardInDeckForPlayer(2, 'SOR_095'); }
});
$check($pick('no-shipdiscount') === 'myHand-0!FSM!', 'A fixture: today Corvus is hard-cast for 5; got ' . $pick('no-shipdiscount'));
$check(in_array($pick(), [$ability, 'myHand-1!FSM!'], true), 'A: the discount pays for the Droid too — not a full-price Corvus; got ' . $pick());

// B) Behind on bodies (blockerfirst), Piett not yet deployed (6 resources), Gozanti (5) in hand: today Gozanti goes down at full price
// before the deploy; fixed, Piett deploys first and Gozanti then costs 3.
$build(function ($b) {
    $b->MyLeader('JTL_005', true, false, false); $b->MyBase('JTL_020'); $b->FillResourcesForPlayer(1, 'SOR_095', 6); $b->WithCurrentRoundBeing(6);
    $b->WithCardInHandForPlayer(1, 'ASH_099'); $b->WithGroundUnitForPlayer(1, 'SOR_095', true);
    $b->WithGroundUnitForPlayer(2, 'SOR_095', false); $b->WithGroundUnitForPlayer(2, 'SOR_095', false);
    for ($k = 0; $k < 30; $k++) { $b->WithCardInDeckForPlayer(1, 'SOR_095'); $b->WithCardInDeckForPlayer(2, 'SOR_095'); }
});
$check($pick('no-deployfirst') === 'myHand-0!FSM!', 'B fixture: today Gozanti is hard-cast before the deploy; got ' . $pick('no-deployfirst'));
$check($pick() === $deploy, 'B: Piett deploys first; got ' . $pick());
$act(1, 10001, $deploy);
for ($k = 0; $k < 4 && $botCtx('softcontrol')['kind'] === 'decision'; $k++) $act(1, 100, 'PASS');
$check(intval(SWUComputePlayCost(1, GetHand(1)[0])) === intval(CardCost('ASH_099')) - 2, 'B: after the deploy Gozanti costs 2 less');

// The hold applies only while Piett CAN deploy now. Behind on bodies again, an Outlaw Corona (3, Capital Ship, Command) in hand:
$corona = function (int $res, bool $deployed, bool $epicUsed) use ($build) {
    $build(function ($b) use ($res, $deployed, $epicUsed) {
        $b->MyLeader('JTL_005', true, $deployed, $epicUsed, $deployed ? 'unit' : ''); $b->MyBase('JTL_020'); $b->FillResourcesForPlayer(1, 'SOR_095', $res);
        $b->WithCurrentRoundBeing(6); $b->WithCardInHandForPlayer(1, 'SHD_116'); $b->WithGroundUnitForPlayer(1, 'SOR_095', true);
        $b->WithGroundUnitForPlayer(2, 'SOR_095', false); $b->WithGroundUnitForPlayer(2, 'SOR_095', false); $b->WithGroundUnitForPlayer(2, 'SOR_095', false);
        for ($k = 0; $k < 30; $k++) { $b->WithCardInDeckForPlayer(1, 'SOR_095'); $b->WithCardInDeckForPlayer(2, 'SOR_095'); }
    });
};
$corona(5, false, false);
$check($pick() === $deploy, 'C control: 5 resources, Piett can deploy — the deploy goes before the Corona; got ' . $pick());
$corona(4, false, false);
$check($pick() === 'myHand-0!FSM!', 'C: 4 resources — Piett cannot deploy yet, the Corona is played; got ' . $pick());
$corona(5, false, true);
$check($pick() === 'myHand-0!FSM!', 'C: his Epic Action is spent — the Corona is played; got ' . $pick());
$corona(5, true, false);   // deployed by an effect, Epic Action unspent — still nothing to wait for
$check($pick() === 'myHand-0!FSM!', 'C: Piett is already deployed — the Corona is played (at 2 less); got ' . $pick());

// D) Both cards fit at full price anyway (8 resources, Corvus 5 + the Droid 2): the discount buys nothing extra — no Action.
$build(function ($b) {
    $b->MyLeader('JTL_005', true, false, true); $b->MyBase('JTL_020'); $b->FillResourcesForPlayer(1, 'SOR_095', 8); $b->WithCurrentRoundBeing(8);
    $b->WithCardInHandForPlayer(1, 'JTL_038'); $b->WithCardInHandForPlayer(1, 'SEC_110');
    $b->WithGroundUnitForPlayer(2, 'SOR_095', false);
    for ($k = 0; $k < 30; $k++) { $b->WithCardInDeckForPlayer(1, 'SOR_095'); $b->WithCardInDeckForPlayer(2, 'SOR_095'); }
});
$check($pick() !== $ability, 'D: both fit at full price — the Action buys nothing extra; got ' . $pick());

bot_test_finish();

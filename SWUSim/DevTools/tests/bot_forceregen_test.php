<?php
// Feature 'forceregen' (p42) — FORCE SEQUENCING for the LOF_016 Qui-Gon deck (leader audit 2026-10-08: "Still NOT planned: Force regen
// sequencing"). The Force is ONE token: LOF_023 Jedi Temple ("When a friendly Force unit attacks: The Force is with you") refills it, and
// a refill while I already have it does nothing. So within a round:
//   - SPEND FIRST: holding the Force, with a spender worth using now (Qui-Gon's tuck — "Action [Exhaust, use the Force]", a Force-using
//     When Played), a Force unit's refilling attack waits for it — attacking first wasted the refill and the round ended with no Force;
//   - REFILL FIRST: without the Force, a play whose When Played uses the Force (LOF_101 Yoda: "You may use the Force. If you do, heal 5
//     damage from a base") waits for a Force unit's refilling attack — cast first, Yoda's heal was lost (today: Yoda 6.4 > Sol 1.2).
// Only a refilling attack worth making (> 0) is waited for / waits.
// Fixtures (dictionary-checked): LOF_016 Qui-Gon Jinn · LOF_023 Jedi Temple · LOF_101 Yoda (8) · LOF_100 Kelleran Beq (7) · LOF_199 Depa
//   Billaba (6) · HMW_210 Sol (2/2 Force) · SEC_101 Queen Amidala (5/3, not Force) · JTL_021 Colossus (no refill) · SOR_095.
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php -d apc.enable_cli=1 -d xdebug.mode=off SWUSim/DevTools/tests/bot_forceregen_test.php
require __DIR__ . '/fixtures/bot_test_bootstrap.php';
foreach (['BotLegalActions', 'Custom/BotLookahead', 'Custom/BotEvaluator', 'Rl/CardTags', 'Custom/BotStyles', 'Custom/BotResourcing',
          'Custom/BotGuides', 'Custom/BotFallback', 'Custom/BotRules', 'BotHeuristic'] as $f) include_once "./SWUSim/$f.php";
$check(SWUBotVariantDisabled('no-forceregen') === ['forceregen'], 'forceregen is switchable');
$check(in_array('forceregen', SWUBotFeatureGroups()['p42'] ?? [], true), 'forceregen is in group p42');
$pick = function (string $variant = '') use (&$gameName) {
    $l = SWUBotLegalActions($gameName, 1);
    $p = SWUBotHeuristicChoose('midrange', (array)$l['actions'], $l, $variant);
    return strval($p['cardID'] ?? '');
};
$nm = fn(string $mz) => strval((@GetZoneObject(preg_replace('/!.*/', '', $mz)))->CardID ?? '');
$deck = function ($b) { for ($k = 0; $k < 30; $k++) { $b->WithCardInDeckForPlayer(1, 'SOR_095'); $b->WithCardInDeckForPlayer(2, 'SOR_095'); } };
$tuck = 'myLeader-0!CustomInput!LeaderAbility';

// A) REFILL FIRST: no Force, Yoda castable (8 resources), my base on 8, Sol (a Force unit) ready to swing.
$a = function (string $base = 'LOF_023', string $attacker = 'HMW_210') use ($build, $deck) {
    $build(function ($b) use ($deck, $base, $attacker) {
        $b->MyLeader('LOF_016', true); $b->MyBase($base, 8); $b->FillResourcesForPlayer(1, 'SOR_095', 8); $b->WithCurrentRoundBeing(8);
        $b->WithGroundUnitForPlayer(1, $attacker, true); $b->WithCardInHandForPlayer(1, 'LOF_101'); $b->WithGroundUnitForPlayer(2, 'SOR_095', false); $deck($b);
    });
};
$a();
$check($nm($pick('no-forceregen')) === 'LOF_101', 'A fixture: today Yoda is cast without the Force; got ' . $nm($pick('no-forceregen')));
// Play the round out (the opponent passes): Yoda must come only once the Force is back — after a Force unit's refilling swing (Sol, or
// the Qui-Gon unit if he deploys first: also a Force unit).
$castWithForce = null; $order = [];
for ($step = 0; $step < 6 && $castWithForce === null; $step++) {
    $p = $pick();
    if ($p === '' || str_contains($p, 'TakeInitiative') || str_contains($p, 'Pass')) break;
    $order[] = $nm($p) ?: $p;
    if ($nm($p) === 'LOF_101') $castWithForce = PlayerHasTheForce(1);
    $act(1, str_contains($p, 'CustomInput') ? 10001 : 10002, $p);
    for ($k = 0; $k < 4 && $botCtx('midrange')['kind'] === 'decision'; $k++) $act(1, 100, $pick());
    if ($botCtx('midrange')['kind'] === 'waiting-on-other-seat') $act(2, 10001, 'myHealth-0!CustomInput!Pass');
}
$check($castWithForce === true, 'A: Yoda is cast WITH the Force (Sol refilled it first); order ' . json_encode($order));
// …no refill without Jedi Temple (Colossus): Yoda as before.
$a('JTL_021');
$check($pick() === $pick('no-forceregen'), 'A: no refilling base — as before; got ' . json_encode([$pick(), $pick('no-forceregen')]));
// …nor from a unit that is not a Force unit (Queen Amidala).
$a('LOF_023', 'SEC_101');
$check($pick() === $pick('no-forceregen'), 'A: a non-Force attacker refills nothing — as before; got ' . json_encode([$pick(), $pick('no-forceregen')]));

// …nor waits for a refilling swing NOT worth making: Sol (2/2) can only attack into their Sentinel Zeb (4/4) and die — Yoda as before.
$build(function ($b) use ($deck) {
    $b->MyLeader('LOF_016', true); $b->MyBase('LOF_023', 8); $b->FillResourcesForPlayer(1, 'SOR_095', 8); $b->WithCurrentRoundBeing(8);
    $b->WithGroundUnitForPlayer(1, 'HMW_210', true); $b->WithCardInHandForPlayer(1, 'LOF_101'); $b->WithGroundUnitForPlayer(2, 'LAW_045', false); $deck($b);
});
$check($pick() === $pick('no-forceregen'), 'A: a losing refill swing is not waited for — as before; got ' . json_encode([$pick(), $pick('no-forceregen')]));

// B) SPEND FIRST: the Force held, the tuck worth taking (an exhausted Kelleran returns, Depa from hand plays free — 2 resources, she is not
//    castable), and Sol ready: today Sol swings first (the refill is wasted) and the tuck then leaves no Force.
$b = function (bool $force = true) use ($build, $deck) {
    $build(function ($b) use ($deck, $force) {
        $b->MyLeader('LOF_016', true); $b->MyBase('LOF_023', 4); if ($force) $b->WithForceForPlayer(1);
        $b->FillResourcesForPlayer(1, 'SOR_095', 2); $b->WithCurrentRoundBeing(6);
        $b->WithGroundUnitForPlayer(1, 'LOF_100', false); $b->WithGroundUnitForPlayer(1, 'HMW_210', true);
        $b->WithCardInHandForPlayer(1, 'LOF_199'); $b->WithGroundUnitForPlayer(2, 'SOR_095', false); $deck($b);
    });
};
$b();
$check($nm($pick('no-forceregen')) === 'HMW_210', 'B fixture: today Sol swings first; got ' . $pick('no-forceregen'));
$check($pick() === $tuck, 'B: the tuck first — Sol\'s swing then refills the Force; got ' . $pick());

bot_test_finish();

<?php
// Features 'ndsetup' + 'onattackfinish' (p36) — a hit that leaves an enemy on 1 HP is a SETUP when my leader can finish a 1-HP unit
// this round; feature 'setup' then prices it at 0.8 of the kill. _SWUBotFinisherHP knew only the Aurra Sing shape ("Action
// [Exhaust]: Defeat a non-leader unit with N or less remaining HP").
//   ndsetup        — front-side leader Actions that finish 1 HP: HMW_003 Doctor Hemlock ("Action [1 resource, Exhaust]: Give a Weakness
//                    token to a unit without a Weakness token on it" — a ready resource, a target with no Weakness) and LOF_002
//                    Mother Talzin ("Action [Exhaust, use the Force]: Give a unit -1/-1 for this phase" — needs the Force). Owner
//                    2026-10-04: "No Disintegrations can set up not only Hemlock but … Mother Talzin (LOF)". (ASH Cad Bane is NOT a
//                    1-HP finisher: "deal 1 damage to a unit with 2 or more remaining HP".)
//   onattackfinish — a READY deployed Hemlock ("On Attack: You may give a Weakness token to a unit") or Talzin ("On Attack: You may give
//                    a unit -1/-1"): Reprint_Cad R6 split Ninth Sister 1/1/1, then Hemlock's attack token finished the Interceptor.
// Fixtures (dictionary-checked): HMW_003 · LOF_002 · SOR_095 Battlefield Marine 3/3 · HMW_T02 Weakness · HMW_027 · LAW_097
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php -d apc.enable_cli=1 -d xdebug.mode=off SWUSim/DevTools/tests/bot_finisher_test.php
require __DIR__ . '/fixtures/bot_test_bootstrap.php';
foreach (['BotLegalActions', 'Custom/BotLookahead', 'Custom/BotEvaluator', 'Rl/CardTags', 'Custom/BotStyles', 'Custom/BotResourcing',
          'Custom/BotGuides', 'Custom/BotFallback', 'Custom/BotRules', 'BotHeuristic'] as $f) include_once "./SWUSim/$f.php";
foreach (['ndsetup', 'onattackfinish'] as $x) {
    $check(SWUBotVariantDisabled("no-$x") === [$x], "$x is switchable");
    $check(in_array($x, SWUBotFeatureGroups()['p36'] ?? [], true), "$x is in group p36");
}
// Seat 1's leader $leader (ready? deployed?), $res resources, Force token if $force. Seat 2: a Battlefield Marine on 2 HP (1 damage),
// carrying a Weakness token if $weak. Returns the score of 1 damage to the Marine, with $off features disabled.
$hit = function (string $leader, bool $ready, bool $deployed, int $res, bool $force = false, bool $weak = false, array $off = []) use ($build) {
    $build(function ($b) use ($leader, $ready, $deployed, $res, $force, $weak) {
        $b->MyLeader($leader, $ready, $deployed, $deployed, $deployed ? 'unit' : '', 0); $b->MyBase('HMW_027');
        if ($res > 0) $b->FillResourcesForPlayer(1, 'LAW_097', $res);
        if ($force) $b->WithForceForPlayer(1);
        $b->WithGroundUnitForPlayer(2, 'SOR_095', true, $weak ? 0 : 1);
        if ($weak) $b->WithUpgradesOnGroundUnitForPlayer(2, 0, [GameStateBuilder::Upgrade('HMW_T02', 1)]);   // 3/3 -1/-1 = 2 HP
    });
    $mz = 'theirGroundArena-0';
    $GLOBALS['SWUBotDisabledFeatures'] = $off;
    $s = _SWUBotTargetScore(1, $mz, true, 1, 'DEAL_TARGET', SWUBotWeights('softcontrol', 1));
    $GLOBALS['SWUBotDisabledFeatures'] = [];
    return $s;
};
$setup = function () use ($build) {   // 0.8 x the Marine's kill value, read on the current board
    $v = SWUBotUnits(2)[0]; $W = SWUBotWeights('softcontrol', 1);
    return 0.8 * (SWUBotUnitValue($v) * (1.0 + $W['kill']) + 1.0);
};

// A) Hemlock's front side ready, a resource ready: 1 damage onto a 2-HP Marine is a setup.
$on = $hit('HMW_003', true, false, 1); $exp = $setup();
$off = $hit('HMW_003', true, false, 1, false, false, ['ndsetup']);
$check(abs($on - $exp) < 1e-6 && $off < $on, "A: Hemlock's Action finishes it — priced as a setup ($on; off $off)");
// B) No resource for the Action: plain chip.  C) Hemlock exhausted: plain chip.  D) the Marine already has a Weakness token: plain chip.
$check(abs($hit('HMW_003', true, false, 0) - $hit('HMW_003', true, false, 0, false, false, ['ndsetup'])) < 1e-9, 'B: no ready resource — unchanged');
$check(abs($hit('HMW_003', false, false, 1) - $hit('HMW_003', false, false, 1, false, false, ['ndsetup'])) < 1e-9, 'C: Hemlock exhausted — unchanged');
$check(abs($hit('HMW_003', true, false, 1, false, true) - $hit('HMW_003', true, false, 1, false, true, ['ndsetup'])) < 1e-9, 'D: target already Weakened — unchanged');
// E) Talzin's front side needs the Force.
$on = $hit('LOF_002', true, false, 0, true); $exp = $setup();
$check(abs($on - $exp) < 1e-6, "E: Talzin with the Force finishes it — setup ($on)");
$check(abs($hit('LOF_002', true, false, 0, false) - $hit('LOF_002', true, false, 0, false, false, ['ndsetup'])) < 1e-9, 'E: Talzin without the Force — unchanged');
// F) Deployed Hemlock, READY: its attack token finishes a 1-HP unit — even one already Weakened (no restriction on the On Attack).
$on = $hit('HMW_003', true, true, 0, false, true); $exp = $setup();
$check(abs($on - $exp) < 1e-6, "F: ready deployed Hemlock — setup, Weakened or not ($on)");
$check(abs($hit('HMW_003', true, true, 0, false, true, ['onattackfinish']) - $on) > 1e-6, 'F: off, the same hit is plain chip');
$check(abs($hit('HMW_003', false, true, 0) - $hit('HMW_003', false, true, 0, false, false, ['onattackfinish'])) < 1e-9, 'F: deployed Hemlock exhausted — unchanged');
// G) Deployed Talzin, ready: her On Attack -1/-1 finishes it.
$on = $hit('LOF_002', true, true, 0); $exp = $setup();
$check(abs($on - $exp) < 1e-6, "G: ready deployed Talzin — setup ($on)");

// H) The Weakness path (Ravage's split): 2 tokens leave a full 3-HP Marine on 1 — a setup for ready deployed Hemlock's attack token.
$build(function ($b) {
    $b->MyLeader('HMW_003', true, true, true, 'unit', 0); $b->MyBase('HMW_027');
    $b->WithGroundUnitForPlayer(2, 'SOR_095', true, 0);
});
$v = null; foreach (SWUBotUnits(2) as $u) $v = $u;
$W = SWUBotWeights('softcontrol', 1);
$GLOBALS['SWUBotDisabledFeatures'] = ['onattackfinish']; $wOff = _SWUBotWeaknessScore(1, $v, true, 0, 'GIVE_WEAKNESS', 2, $W);
$GLOBALS['SWUBotDisabledFeatures'] = []; $wOn = _SWUBotWeaknessScore(1, $v, true, 0, 'GIVE_WEAKNESS', 2, $W);
$check(abs($wOn - 0.8 * (SWUBotUnitValue($v) * (1.0 + $W['kill']) + 1.0)) < 1e-6 && $wOff < $wOn, "H: two tokens + Hemlock's attack token — setup ($wOff -> $wOn)");

bot_test_finish();

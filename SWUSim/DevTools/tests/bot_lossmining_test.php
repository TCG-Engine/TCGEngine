<?php
// The 2026-09-19 loss-mining proposals (all default OFF): sentinelpot, freekill, shrinkfirst, holdanswers, unitvalue.
// Source: 540 traced control-vs-aggro games; owner rulings on real lost positions (see BotFeatures.php).
// Fixtures (dictionary-checked): SOR_095 Battlefield Marine 3/3 (vanilla) · LOF_061 Secretive Sage 2/2 ·
// ASH_097 Moff Gideon (Sentinel) · SEC_075 Knowledge and Defense (-2/-2, draw) · LAW_038 Lepi Lookout 3/1 ·
// SEC_078 Hyperspace Disaster · ASH_052 Chimaera — A Frightening Reality (7) · JTL_041 Annihilator (11) ·
// JTL_147 Black One / ASH_153 Green Leader (space) · ASH_044 Barriss Offee (heal a unit, Advantage tokens) ·
// LOF_207 Loth-Cat (When Played/When Defeated: you may exhaust a ground unit).
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php -d apc.enable_cli=1 -d xdebug.mode=off SWUSim/DevTools/tests/bot_lossmining_test.php
require __DIR__ . '/fixtures/bot_test_bootstrap.php';
include_once './SWUSim/BotLegalActions.php';
include_once './SWUSim/Custom/BotLookahead.php';
include_once './SWUSim/BotHeuristic.php';

$on = fn(string $p) => SWUBotSetDisabledFeatures(["try:$p"]);
$off = fn() => SWUBotSetDisabledFeatures([]);
$rules = SWUBotRulesAfterFilter();
$rule = function (string $name, string $prop) use ($rules, $botCtx, $on, $off) {
    $on($prop); $p = ($rules[$name])($botCtx('control')); $off();
    return $p === null ? null : strval($p['cardID']);
};
foreach (['sentinelpot', 'freekill', 'holdanswers', 'unitvalue', 'unitvalue2', 'shrinkfirst2'] as $p) {
    $check(SWUBotVariantDisabled("try-$p") === ["try:$p"], "proposal $p is registered");
}
// 'shrinkfirst' SHIPPED 2026-09-20 (group p6): default ON, switchable off.
$check(SWUBotVariantDisabled('no-shrinkfirst') === ['shrinkfirst'] && SWUBotVariantDisabled('no-p6') === ['shrinkfirst'],
    'shrinkfirst is a shipped feature, off via @no-shrinkfirst / @no-p6');
$quiet = fn($b) => $b->MyLeader('SOR_014', false, false, true);   // no leader actions / deploy on offer

// ── sentinelpot ────────────────────────────────────────────────────────────────────────────────────
$build(function ($b) use ($quiet) {
    $quiet($b);
    for ($i = 0; $i < 3; $i++) $b->WithGroundUnitForPlayer(1, 'SOR_095', true);   // 3 + 3 + 3
    $b->WithGroundUnitForPlayer(2, 'ASH_097', true);                               // Moff Gideon 2/5, Sentinel
});
$gid = SWUBotUnits(2)[0];
$check($gid['sentinel'] && $gid['remaining'] === 5, 'fixture: an enemy 5-HP Sentinel (Moff Gideon 2/5)');
$check(SWUBotBasePotential(1, 2, true) === 0, 'default model: one Sentinel blocks the whole arena (potential 0)');
$on('sentinelpot');
$check(SWUBotBasePotential(1, 2, true) === 3, 'sentinelpot: two Marines (6) spend themselves on the 5-HP Sentinel, the third reaches the base (3)');
$off();

// ── freekill ───────────────────────────────────────────────────────────────────────────────────────
$fk = function (bool $targetReady) use ($quiet) {
    return function ($b) use ($quiet, $targetReady) {
        $quiet($b);
        $b->WithGroundUnitForPlayer(1, 'SOR_095', true);
        $b->WithGroundUnitForPlayer(2, 'LOF_061', $targetReady);
    };
};
$build($fk(true)); $raiseAttack(1, 'myGroundArena-0');
$check($botCtx('control')['tooltip'] === 'Choose_an_attack_target', 'fixture: the attack-target prompt is up');
$check($rule('free-kill', 'freekill') === 'theirGroundArena-0', 'freekill: a READY unit it kills and survives is attacked, not the base');
$check($rule('free-kill', 'none') === null, 'freekill: inert without the proposal');
$build($fk(false)); $raiseAttack(1, 'myGroundArena-0');
$check($rule('free-kill', 'freekill') === null, 'freekill: an EXHAUSTED target is left to the scorer (owner: think about other threats)');

// ── shrinkfirst ────────────────────────────────────────────────────────────────────────────────────
$sf = function (bool $lepiReady) use ($quiet) {
    return function ($b) use ($quiet, $lepiReady) {
        $quiet($b);
        $b->FillResourcesForPlayer(1, 'SOR_095', 8);
        $b->WithGroundUnitForPlayer(1, 'SOR_095', true);
        $b->WithCardInHandForPlayer(1, 'SEC_075');
        $b->WithGroundUnitForPlayer(2, 'LAW_038', $lepiReady);
    };
};
$build($sf(true));
$check($rule('shrink-first', 'none') === 'myHand-0!FSM!', 'shrinkfirst (shipped, default ON): Knowledge and Defense on a READY 3-power Lepi before anything else');
SWUBotSetDisabledFeatures(['shrinkfirst']);
$checkOff = ($rules['shrink-first'])($botCtx('control'));
SWUBotSetDisabledFeatures([]);
$check($checkOff === null, 'shrinkfirst: @no-shrinkfirst switches it off');
$check(($GLOBALS['SWUBotPlan'][1][0]['answer'] ?? '') === 'theirGroundArena-0', 'shrinkfirst: the plan targets the Lepi');
$build($sf(false));
$check($rule('shrink-first', 'none') === null, 'shrinkfirst: an exhausted Lepi is not a threat this phase');

// ── holdanswers ────────────────────────────────────────────────────────────────────────────────────
$ha = function ($b) use ($quiet) {
    $quiet($b);
    $b->FillResourcesForPlayer(1, 'SOR_095', 2);
    foreach (['JTL_041', 'ASH_052', 'SOR_095'] as $c) $b->WithCardInHandForPlayer(1, $c);   // bomb, Chimaera, filler
    $b->WithSpaceUnitForPlayer(2, 'JTL_147', true); $b->WithSpaceUnitForPlayer(2, 'ASH_153', true);
};
$build($ha);
$first = function () use ($botCtx) { $mz = SWUBotChooseResourceCards($botCtx('control'), 1)[0]; return strval(GetHand(1)[intval(substr($mz, 7))]->CardID); };
$base = $first();
$on('holdanswers'); $held = $first(); $off();
$check($base === 'ASH_052' && $held === 'SOR_095', "holdanswers: vs a space-heavy board Chimaera is kept, the filler resourced (default resources $base)");
$build(function ($b) use ($quiet) {
    $quiet($b); $b->FillResourcesForPlayer(1, 'SOR_095', 8);   // Barriss costs 7 with the aspect penalty
    $b->WithGroundUnitForPlayer(1, 'SOR_095', true);   // undamaged
    $b->WithCardInHandForPlayer(1, 'ASH_044');
});
$ctx = $botCtx('control'); $play = null;
foreach ($ctx['actions'] as $i => $a) { if (strval($a['cardID']) === 'myHand-0!FSM!') $play = [$a, $i]; }
$s0 = SWUBotScoreAction($ctx, $play[0], $play[1]);
$on('holdanswers'); $s1 = SWUBotScoreAction($ctx, $play[0], $play[1]); $off();
$check($play !== null && $s1 < $s0, "holdanswers: Barriss Offee is worth less with nothing damaged and no race ($s0 -> $s1)");

// ── unitvalue ──────────────────────────────────────────────────────────────────────────────────────
$view = fn(int $pow, int $hp, array $x = []) => array_merge(['cardID' => 'SOR_095', 'controller' => 2, 'arena' => 'Ground',
    'power' => $pow, 'attackPower' => $pow, 'hp' => $hp, 'remaining' => $hp, 'ready' => true, 'sentinel' => false,
    'saboteur' => false, 'overwhelm' => false, 'grit' => false, 'shields' => 0, 'upgrades' => 0, 'cost' => 2, 'isLeader' => false], $x);
$build(function ($b) use ($quiet) { $quiet($b); });
$on('unitvalue');
$check(abs(SWUBotUnitValue($view(3, 3)) - (0.584 * 3 + 0.409 * 3 - 0.676)) < 1e-6, 'unitvalue: a vanilla 3/3 is priced by the fitted stat line');
$check(SWUBotUnitValue($view(4, 2)) > SWUBotUnitValue($view(2, 4)), 'unitvalue: power is worth more than HP (owner)');
$check(abs(SWUBotUnitValue($view(2, 2, ['sentinel' => true])) - SWUBotUnitValue($view(2, 2)) - 1.05) < 1e-6, 'unitvalue: Sentinel adds its fitted premium');
$check(SWUBotUnitValue($view(2, 2, ['saboteur' => true])) > SWUBotUnitValue($view(2, 2)), 'unitvalue: Saboteur adds value');
$check(abs(SWUBotUnitValue($view(5, 4, ['upgrades' => 3])) - SWUBotUnitValue($view(5, 4)) - 1.5) < 1e-6, 'unitvalue: each upgrade that dies with it adds 0.5 (Karis with 3)');
$check(abs(SWUBotUnitValue($view(3, 1, ['shields' => 1, 'upgrades' => 1])) - SWUBotUnitValue($view(3, 1)) - 0.67) < 1e-6, 'unitvalue: a Shield token adds 0.67');
$off();
// Loth-Cat's When Defeated (exhaust a ground unit) against MY board: worth nothing when my only ready ground unit is
// the one attacking it (owner's example), worth ~1 while I have another ready ground unit it could stop.
$lc = function (int $myReady) use ($quiet) {
    return function ($b) use ($quiet, $myReady) {
        $quiet($b);
        for ($i = 0; $i < $myReady; $i++) $b->WithGroundUnitForPlayer(1, 'SOR_095', true);
        $b->WithGroundUnitForPlayer(2, 'LOF_207', true);
    };
};
// The payout reads the CURRENT board, so each value is taken right after its own board is built.
$measure = function (int $n) use ($build, $lc, $on, $off) {
    $build($lc($n)); $cat = SWUBotUnits(2)[0];
    $pay = _SWUBotWhenDefeatedPayout($cat);
    $on('unitvalue'); $val = SWUBotUnitValue($cat); $off();
    return [$pay, $val];
};
[$pay1, $val1] = $measure(1); [$pay2, $val2] = $measure(2);
$check($pay1 == 0.0 && $pay2 == 1.0, "Loth-Cat: no payout vs my last ready ground unit ($pay1), 1.0 while another could be exhausted ($pay2)");
$check($val1 > $val2, "unitvalue: killing Loth-Cat with my last ground attacker is worth more ($val1 vs $val2)");
// Without the proposal nothing changes: the cost-based value.
$check(SWUBotUnitValue($view(3, 3)) == 2.0, 'unitvalue off: the old cost-based value (cost 2)');

// ── the follow-up arms: unitvalue2 (+ printed ability premium) and shrinkfirst2 (bar at 2 power) ───────────────
// LOF_207 Loth-Cat: cost 2, printed 2/1 → body = 0.584*2 + 0.409*1 - 0.676 = 0.905, so its text is worth ~1.1.
$build($lc(1)); $cat = SWUBotUnits(2)[0];
$on('unitvalue'); $v1 = SWUBotUnitValue($cat); $off();
$on('unitvalue2'); $v2 = SWUBotUnitValue($cat); $off();
$check(abs(($v2 - $v1) - (2 - (0.584 * 2 + 0.409 * 1 - 0.676))) < 1e-6, "unitvalue2: adds the printed ability premium ($v1 -> $v2)");
$build(function ($b) use ($quiet) { $quiet($b); $b->WithGroundUnitForPlayer(2, 'SOR_095', true); });   // vanilla 3/3, cost 2
$plain = SWUBotUnits(2)[0];
$on('unitvalue'); $p1 = SWUBotUnitValue($plain); $off();
$on('unitvalue2'); $p2 = SWUBotUnitValue($plain); $off();
$check(abs($p1 - $p2) < 1e-6, 'unitvalue2: a vanilla unit gets no premium (its cost IS its body)');
// shrinkfirst2: a READY 2-power threat is below the 3-power bar but above the 2-power one.
$sf2 = function ($b) use ($quiet) {
    $quiet($b);
    $b->FillResourcesForPlayer(1, 'SOR_095', 8);
    $b->WithCardInHandForPlayer(1, 'SEC_075');                 // Knowledge and Defense, -2/-2
    $b->WithGroundUnitForPlayer(2, 'LOF_061', true);           // Secretive Sage 2/2: -2/-2 kills it
};
$build($sf2);
$check(SWUBotUnits(2)[0]['attackPower'] === 2, 'fixture: a ready 2-power threat');
SWUBotSetDisabledFeatures([]);
$check(($rules['shrink-first'])($botCtx('control')) === null, 'shrinkfirst: 2 power is below its 3-power bar');
$check($rule('shrink-first', 'shrinkfirst2') === 'myHand-0!FSM!', 'shrinkfirst2: the same rule fires at 2 power');

bot_test_finish();

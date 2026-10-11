<?php
// Indirect damage on my own unit that leaves it in reach of an enemy attacker is a likely LOSS, not chip (proposal 'indirectsoak',
// 2026-10-07 gap screen). ⚠ Needs an owner ruling before it ships: base or units against indirect damage while the base is healthy?
// FOUND 2026-10-07 diagnosing Krennic vs Boba Fett (JTL) Blue (.claude/tmp/diag_boba): in Krennic Blue's losses 665 indirect points went
// on units and 296 on the base (R3: 174 vs 61, with the base on 4.7 damage); then a Sentinel died in combat to a hit smaller than its
// printed HP — 33 units in 26 of 82 Blue losses (Moff Gideon 19). s001 R2: "TIE Bomber dealt 3 damage to P1's Moff Gideon", R3 "Marrok
// attacked P1's Moff Gideon — dealt 2 … defeated". _SWUBotSplitScore prices a non-lethal point on my unit at W['chip'] (0.45) and on my
// base at 0.5, so every point that does not kill goes on a unit.
// The lever: a share that takes my unit from OUT of the reach of the strongest enemy unit in its arena to within it (remaining <= that
// power) costs SWU_BOT_SOAK_LOSS x the unit's loss value instead of chip.
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php -d apc.enable_cli=1 -d xdebug.mode=off SWUSim/DevTools/tests/bot_indirectsoak_test.php
require __DIR__ . '/fixtures/bot_test_bootstrap.php';
include_once './SWUSim/BotLegalActions.php';
include_once './SWUSim/BotHeuristic.php';
$check(in_array('indirectsoak', SWUBotProposalList(), true) && SWUBotVariantDisabled('try-indirectsoak') === ['try:indirectsoak'], 'indirectsoak is a switchable proposal');
// My Moff Gideon (ASH_097, 2/5 Sentinel) against their Marrok (ASH_030, 2/6): 3 indirect to assign between my base and Gideon.
$build(function ($b) { $b->WithGroundUnitForPlayer(1, 'ASH_097', true); $b->WithGroundUnitForPlayer(2, 'ASH_030', true); });
$W = SWUBotWeights('softcontrol', 1);
$cands = ['myBase-0:3', 'myGroundArena-0:3', 'myBase-0:2,myGroundArena-0:1', 'myBase-0:1,myGroundArena-0:2'];
$best = function (array $variant) use ($cands, $W) {
    SWUBotSetDisabledFeatures($variant);
    $b = null; $bs = -INF;
    foreach ($cands as $c) { $s = _SWUBotSplitScore(1, $c, $W, true); if ($s > $bs) { $bs = $s; $b = $c; } }
    SWUBotSetDisabledFeatures([]);
    return $b;
};
$check($best([]) === 'myGroundArena-0:3', 'premise: today all 3 go on Gideon (5 -> 2 HP, inside Marrok\'s 2 power); got ' . $best([]));
$on = $best(['try:indirectsoak']);
$check($on !== 'myGroundArena-0:3', '@try-indirectsoak: Gideon is not soaked into Marrok\'s reach; got ' . $on);
$gid = 0; foreach (explode(',', $on) as $p) { [$mz, $n] = explode(':', $p); if ($mz === 'myGroundArena-0') $gid += intval($n); }
$check(5 - $gid > 2, '@try-indirectsoak: Gideon keeps more HP than Marrok\'s power; took ' . $gid);

// No enemy unit in Gideon's arena: nothing can finish him, so chip is still the cheaper place (unchanged).
$build(function ($b) { $b->WithGroundUnitForPlayer(1, 'ASH_097', true); $b->WithSpaceUnitForPlayer(2, 'JTL_237', true); });
$W = SWUBotWeights('softcontrol', 1);
$check($best(['try:indirectsoak']) === $best([]), '@try-indirectsoak: no enemy ground attacker — unchanged; got ' . $best(['try:indirectsoak']));
// Already in reach (Gideon at 2 HP left): another point changes nothing about Marrok's kill — chip as before.
$build(function ($b) { $b->WithGroundUnitForPlayer(1, 'ASH_097', true, 3); $b->WithGroundUnitForPlayer(2, 'ASH_030', true); });
$W = SWUBotWeights('softcontrol', 1);
SWUBotSetDisabledFeatures(['try:indirectsoak']); $a = _SWUBotSplitScore(1, 'myGroundArena-0:1', $W, true); SWUBotSetDisabledFeatures([]);
$b0 = _SWUBotSplitScore(1, 'myGroundArena-0:1', $W, true);
$check(abs($a - $b0) < 1e-9, '@try-indirectsoak: a unit already in reach is chipped as before');

bot_test_finish();

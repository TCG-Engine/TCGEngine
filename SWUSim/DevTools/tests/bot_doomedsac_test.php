<?php
// Feature 'doomedsac' (p18) — owner rulings 2026-10-01, from the Karabast Krennic games:
//   2a  a deployed leader is Chimaera's friendly pick when it is Condemned, or has 2 or less HP left and the opponent
//       can deal with it — only when no other fodder is available.
//   2b  a unit with 2 or less HP left that an enemy unit can finish (a high-HP Sentinel on HP-1/HP-2) is cheap fodder
//       for Krennic: the Credit outvalues the base damage it would have blocked.
// Driven through the real play path (hand/leader -> prompt -> the heuristic stack's own choice), each case also run
// with the feature OFF to show the ruling is what moves the pick.
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php -d apc.enable_cli=1 -d xdebug.mode=off SWUSim/DevTools/tests/bot_doomedsac_test.php
require __DIR__ . '/fixtures/bot_test_bootstrap.php';
include_once './SWUSim/BotLegalActions.php';
include_once './SWUSim/BotHeuristic.php';

const KRENNIC = 'LAW_008';   // leader, 4/9 deployed; "Action [Exhaust, defeat a friendly unit]: Create a Credit token"
const CHIMAERA = 'ASH_052';
const MARINE = 'SOR_095';    // 3/3 vanilla, 2-cost
const CONSULAR = 'SOR_046';  // 3/7 vanilla, 4-cost
const TROOPER = 'SOR_128';   // 3/1 vanilla, 1-cost
const CONDEMN = 'SEC_038';
$off = function (callable $f) { $GLOBALS['SWUBotDisabledFeatures'] = ['doomedsac']; try { return $f(); } finally { $GLOBALS['SWUBotDisabledFeatures'] = []; } };
$cardOf = fn(string $mz) => $mz === 'PASS' ? 'PASS' : (($v = SWUBotViewForMz(1, $mz)) === null ? $mz : ($v['isLeader'] ? 'LEADER:' . $v['cardID'] : $v['cardID']));

// Plays Chimaera with Krennic deployed (on $leaderDamage) and returns the bot's answer to "Choose a friendly unit".
$chimaeraPick = function (int $leaderDamage, array $mine, array $theirs, string $variant = '') use (&$gameName, $build, $act, $cardOf) {
    $build(function ($b) use ($leaderDamage, $mine, $theirs) {
        $b->MyLeader(KRENNIC, true, true, true, 'unit', $leaderDamage); $b->MyBase('JTL_020'); $b->TheirBase('SOR_020');
        $b->FillResourcesForPlayer(1, MARINE, 10);
        $b->WithCardInHandForPlayer(1, CHIMAERA);
        foreach ($mine as $c) $b->WithGroundUnitForPlayer(1, $c, true);
        foreach ($theirs as $c) $b->WithGroundUnitForPlayer(2, $c, true);
    });
    $play = null;
    foreach ((array)SWUBotLegalActions($gameName, 1)['actions'] as $a) if (str_starts_with(strval($a['cardID'] ?? ''), 'myHand-0')) { $play = $a; break; }
    if ($play === null) return 'Chimaera was not playable';
    $act(1, intval($play['mode'] ?? 10002), strval($play['cardID']));
    $legal = SWUBotLegalActions($gameName, 1);
    if (stripos(strval($legal['decisionTooltip'] ?? ''), 'friendly') === false) return 'unexpected prompt: ' . strval($legal['decisionTooltip'] ?? '');
    return $cardOf(strval(SWUBotHeuristicChoose('midrange', (array)$legal['actions'], $legal, $variant)['cardID'] ?? 'PASS'));
};

// 2a — Krennic on 2 HP (7 of 9), their Consular hits for 3: no other fodder, so Krennic goes for the Consular.
$krennicLow = fn(string $variant = '') => $chimaeraPick(7, [], [CONSULAR], $variant);
$view = function () { foreach (SWUBotUnits(1) as $v) if ($v['isLeader']) return $v; return null; };
$got = $krennicLow();
$lv = $view();
$check($lv !== null && $lv['remaining'] === 2 && SWUBotUnitIsDoomed($lv), 'fixture: deployed Krennic has 2 HP left and their Consular can finish it');
$check($got === 'LEADER:' . KRENNIC, '2a: Krennic on 2 HP, no other fodder: Chimaera sends Krennic back for their Consular (got ' . $got . ')');
$got = $krennicLow('no-doomedsac');
$check($got === 'PASS', '2a control, feature OFF: the leader is priced at full value and the trade is declined (got ' . $got . ')');

// 2a — "assumes no other fodder unit is available": with a 2-cost Marine out, the Marine goes, not the leader.
$got = $chimaeraPick(7, [MARINE], [CONSULAR]);
$check($got === MARINE, '2a: with a Marine on board the Marine is the fodder, Krennic stays (got ' . $got . ')');

// 2a — a healthy leader is never fodder.
$got = $chimaeraPick(0, [], [CONSULAR]);
$check($got === 'PASS', '2a: Krennic at full HP is not sent back (got ' . $got . ')');

// 2a — on 2 HP but nothing across can deal 2: not doomed.
$got = $chimaeraPick(7, [], ['TWI_057']);   // Warrior Drone, 1/4
$enemyMax = max(array_map(fn($v) => $v['power'], SWUBotUnits(2)) ?: [0]);
$check($enemyMax < 2, 'fixture: the only enemy unit has power ' . $enemyMax . ' (< 2)');
$check($got === 'PASS', '2a: on 2 HP but no enemy unit can finish it: Krennic is not sent back (got ' . $got . ')');

// …and the boundary: on 1 HP, that same 1-power Drone DOES finish it (power == remaining HP).
// (Read directly: the Drone itself is worth less than the leader's 2.5, so Chimaera rightly declines that trade.)
$chimaeraPick(8, [], ['TWI_057']);
$lv = $view();
$check($lv !== null && $lv['remaining'] === 1 && SWUBotUnitIsDoomed($lv), '2a: Krennic on 1 HP vs a 1-power Drone is doomed');

// 2a — Condemned leader: priced as fodder (its front side comes back), healthy or not.
$build(function ($b) {
    $b->MyLeader(KRENNIC, true, true, true, 'unit', 0); $b->MyBase('JTL_020'); $b->TheirBase('SOR_020');
});
$healthyCost = SWUBotSacrificeCost($view());
$build(function ($b) {
    $b->MyLeader(KRENNIC, true, true, true, 'unit', 0); $b->MyBase('JTL_020'); $b->TheirBase('SOR_020');
    $b->WithUpgradesOnGroundUnitForPlayer(1, 0, [GameStateBuilder::Upgrade(CONDEMN, 2)]);   // the leader unit is ground index 0
});
$lv = $view();
$check(_SWUUnitHasUpgrade($lv['obj'], CONDEMN), 'fixture: Krennic carries Condemn');
$check(abs(SWUBotSacrificeCost($lv) - SWU_BOT_DOOMED_LEADER_SAC_COST) < 1e-9 && $healthyCost > SWU_BOT_DOOMED_LEADER_SAC_COST,
    sprintf('2a: a Condemned leader costs %.2f to send back (healthy and un-Condemned: %.2f)', SWUBotSacrificeCost($lv), $healthyCost));
$offCost = $off(fn() => SWUBotSacrificeCost($lv));
$check(abs($offCost - SWUBotUnitValue($lv)) < 1e-9 && $offCost > $healthyCost,
    sprintf('2a control, feature OFF: a Condemned leader is priced at its full value, %.2f (Condemn is one more card that dies with it)', $offCost));

// 2b — Krennic's own Action. My Consular (3/7) on 2 HP facing their Trooper (3/1), and a healthy Marine: the Consular,
// which dies anyway, is the one cashed in for the Credit.
$krennicSac = function (int $consularDamage, array $theirs, string $variant = '') use (&$gameName, $build, $act, $cardOf) {
    $build(function ($b) use ($consularDamage, $theirs) {
        $b->MyLeader(KRENNIC); $b->MyBase('JTL_020'); $b->TheirBase('SOR_020');
        $b->WithGroundUnitForPlayer(1, MARINE, true);
        $b->WithGroundUnitForPlayer(1, CONSULAR, true, $consularDamage);
        foreach ($theirs as $c) $b->WithGroundUnitForPlayer(2, $c, true);
    });
    $ab = null;
    foreach ((array)SWUBotLegalActions($gameName, 1)['actions'] as $a) if (SWUBotActionKind($a) === 'leader-ability') { $ab = $a; break; }
    if ($ab === null) return 'no Krennic Action on offer';
    $act(1, intval($ab['mode'] ?? 10002), strval($ab['cardID']));
    $legal = SWUBotLegalActions($gameName, 1);
    if (($legal['kind'] ?? '') !== 'decision') return 'no prompt';
    return $cardOf(strval(SWUBotHeuristicChoose('midrange', (array)$legal['actions'], $legal, $variant)['cardID'] ?? 'PASS'));
};
$got = $krennicSac(5, [TROOPER]);
$check($got === CONSULAR, '2b: the Consular on 2 HP (their Trooper finishes it) is sacrificed, not the healthy Marine (got ' . $got . ')');
$got = $krennicSac(5, [TROOPER], 'no-doomedsac');
$check($got === MARINE, '2b control, feature OFF: the cheaper Marine goes (got ' . $got . ')');
// "Can deal with it" is read in the unit's OWN arena, and a Shield takes the next hit.
$build(function ($b) {
    $b->MyLeader(KRENNIC); $b->MyBase('JTL_020'); $b->TheirBase('SOR_020');
    $b->WithGroundUnitForPlayer(1, CONSULAR, true, 5);
    $b->WithSpaceUnitForPlayer(2, 'SOR_237', true);   // a ship: hits hard enough, but not in the ground arena
});
$cv = SWUBotUnits(1)[0]; $ship = SWUBotUnits(2)[0];
$check($ship['arena'] === 'Space' && $ship['power'] >= 2, 'fixture: their only unit is a ship with power ' . $ship['power']);
$check(!SWUBotUnitIsDoomed($cv), '2b: an enemy SHIP cannot finish my ground Consular on 2 HP: not doomed');
$build(function ($b) {
    $b->MyLeader(KRENNIC); $b->MyBase('JTL_020'); $b->TheirBase('SOR_020');
    $b->WithGroundUnitForPlayer(1, CONSULAR, true, 5);
    $b->WithUpgradesOnGroundUnitForPlayer(1, 0, [GameStateBuilder::Upgrade('SOR_T02', 1)]);
    $b->WithGroundUnitForPlayer(2, TROOPER, true);
});
$cv = SWUBotUnits(1)[0];
$check($cv['shields'] === 1 && $cv['remaining'] === 2, 'fixture: my Consular on 2 HP carries a Shield');
$check(!SWUBotUnitIsDoomed($cv), '2b: a Shielded unit on 2 HP is not doomed (the Shield takes the hit)');

$got = $krennicSac(0, [TROOPER]);
$check($got === MARINE, '2b: a healthy Consular is kept; the Marine goes (got ' . $got . ')');

// 2b — and the Action is USED for it. My only unit is an exhausted Consular (it already attacked) on 2 HP, their Trooper
// finishes it next round: cash it in for the Credit. Healthy, or with the feature off, the Action is not worth a 4-drop.
$mainPick = function (int $consularDamage, string $variant = '') use (&$gameName, $build) {
    $build(function ($b) use ($consularDamage) {
        $b->MyLeader(KRENNIC); $b->MyBase('JTL_020'); $b->TheirBase('SOR_020');
        $b->WithGroundUnitForPlayer(1, CONSULAR, false, $consularDamage);
        $b->WithGroundUnitForPlayer(2, TROOPER, true);
    });
    $legal = SWUBotLegalActions($gameName, 1);
    return SWUBotActionKind(SWUBotHeuristicChoose('softcontrol', (array)$legal['actions'], $legal, $variant) ?? []);
};
$got = $mainPick(5);
$check($got === 'leader-ability', '2b: the doomed Consular is cashed in with Krennic\'s Action (got ' . $got . ')');
$got = $mainPick(5, 'no-doomedsac');
$check($got !== 'leader-ability', '2b control, feature OFF: the Action is not used (got ' . $got . ')');
$got = $mainPick(0);
$check($got !== 'leader-ability', '2b: a healthy Consular is not cashed in (got ' . $got . ')');

bot_test_finish();

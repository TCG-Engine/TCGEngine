<?php
// Phase 1a Task 3 — the board evaluator (SWUSim/Custom/BotEvaluator.php): clocks, lethal, Normal's race
// test, combat outcomes and "can the opponent get a Sentinel". Every block builds a real board.
// Fixtures (stats from the generated dictionary): SOR_095 Battlefield Marine 3/3 · SOR_046 Consular
// Security Force 3/7 · LOF_084 Knight of Ren 4/4 · SOR_063 Cloud City Wing Guard 2/4 Sentinel ·
// SOR_239 Rebel Pathfinder 2/3 Saboteur · SOR_164 Wampa 4/5 Overwhelm · SOR_225 TIE/ln Fighter 2/1 ·
// SOR_T02 Shield token. Bases: CommonSetup 'grw'/'brk' → SOR_024 / SOR_020 (30 HP each); damage is set with
// MyBase()/TheirBase() — WithBaseForPlayer() is the Twin Suns FAR-seat API and adds a second base object
// that SWUBaseRemainingHp() never reads (measured: seat 1's base stayed at 30).
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php -d apc.enable_cli=1 -d xdebug.mode=off SWUSim/DevTools/tests/bot_evaluator_test.php
require __DIR__ . '/fixtures/bot_test_bootstrap.php';
include_once './SWUSim/Custom/BotEvaluator.php';

// ── Clock and lethal ─────────────────────────────────────────────────────────────────────────────
$build(function ($b) {
    $b->WithGroundUnitForPlayer(1, 'SOR_095', true);    // ready 3/3
    $b->WithGroundUnitForPlayer(1, 'SOR_046', false);   // EXHAUSTED 3/7 — readies at the regroup
    $b->TheirBase('SOR_020', 24);            // 30 HP, 24 damage → 6 remaining
});
$check(SWUBaseRemainingHp(2) === 6, 'fixture: P2 base has 6 remaining');
$check(SWUBotBasePotential(1, 2, false) === 6, 'potential counts every unit (3 + 3)');
$check(SWUBotBasePotential(1, 2, true) === 3, 'ready-only potential counts the ready unit only');
$check(SWUBotClock(1, 2) === 1, 'clock = ceil(6 / 6) = 1 round');
$check(SWUBotLethalNow(1, 2) === false, 'lethal NOW needs ready units: 3 < 6');
$check(SWUBotLethalNextRound(1, 2) === true, 'lethal next round counts exhausted units too: 6 >= 6');

// ── A Sentinel blocks its arena; Saboteur ignores it ─────────────────────────────────────────────
$build(function ($b) {
    $b->WithGroundUnitForPlayer(1, 'SOR_095', true);
    $b->WithGroundUnitForPlayer(2, 'SOR_063', true);    // enemy ground Sentinel
});
$check(SWUBotArenaHasSentinel(2, 'Ground') === true, 'P2 has a ground Sentinel');
$check(SWUBotArenaHasSentinel(2, 'Space') === false, '...and no space Sentinel');
$check(SWUBotBasePotential(1, 2, true) === 0, 'an enemy ground Sentinel blocks ground base damage');
$check(SWUBotClock(1, 2) === SWU_BOT_NO_CLOCK, 'no reachable damage → no clock');

$build(function ($b) {
    $b->WithGroundUnitForPlayer(1, 'SOR_239', true);    // Saboteur 2/3
    $b->WithGroundUnitForPlayer(2, 'SOR_063', true);
});
$check(SWUBotBasePotential(1, 2, true) === 2, 'a Saboteur still reaches the base past a Sentinel');

// ── Combat outcomes ──────────────────────────────────────────────────────────────────────────────
$build(function ($b) {
    $b->WithGroundUnitForPlayer(1, 'LOF_084', true);    // 4/4
    $b->WithGroundUnitForPlayer(1, 'SOR_095', true);    // 3/3
    $b->WithGroundUnitForPlayer(2, 'SOR_095', true);    // 3/3
    $b->WithGroundUnitForPlayer(2, 'SOR_046', true);    // 3/7
    $b->WithGroundUnitForPlayer(2, 'LOF_084', true);    // 4/4
});
$k = SWUBotViewForMz(1, 'myGroundArena-0'); $m = SWUBotViewForMz(1, 'myGroundArena-1');
$check($k !== null && $k['power'] === 4 && $k['remaining'] === 4 && $k['arena'] === 'Ground', 'unit view reads 4/4 ground');
$check(SWUBotCombatOutcome($k, SWUBotViewForMz(1, 'theirGroundArena-0')) === 'kill-survive', '4/4 into 3/3 kills and survives');
$check(SWUBotCombatOutcome($k, SWUBotViewForMz(1, 'theirGroundArena-1')) === 'bounce', '4/4 into 3/7: neither dies');
$check(SWUBotCombatOutcome($k, SWUBotViewForMz(1, 'theirGroundArena-2')) === 'trade', '4/4 into 4/4 trades');
$check(SWUBotCombatOutcome($m, SWUBotViewForMz(1, 'theirGroundArena-2')) === 'die', '3/3 into 4/4 dies without killing');
$check(SWUBotViewForMz(1, 'theirGroundArena-9') === null, 'a gone mzID has no view');

// A Shield on the defender stops the kill; Saboteur strips it.
$build(function ($b) {
    $b->WithGroundUnitForPlayer(1, 'LOF_084', true);
    $b->WithGroundUnitForPlayer(1, 'SOR_239', true);    // Saboteur 2/3
    $b->WithGroundUnitForPlayer(2, 'SOR_095', true, 2); // 3/3 with 2 damage → 1 remaining, and Shielded:
    // only a hit that gets PAST the Shield kills it — that is what makes the Saboteur case discriminate.
    // Upgrades are GameStateBuilder::Upgrade() arrays, never bare CardID strings: a bare string is a
    // subcard shape no real game produces, and engine code that iterates subcards (e.g. the Raid helper)
    // TypeErrors on it.
    $b->WithUpgradesOnGroundUnitForPlayer(2, 0, [GameStateBuilder::Upgrade('SOR_T02', 2)]);
});
$def = SWUBotViewForMz(1, 'theirGroundArena-0');
$check($def !== null && $def['shields'] === 1, 'defender view counts its Shield token');
$check(SWUBotCombatOutcome(SWUBotViewForMz(1, 'myGroundArena-0'), $def) === 'bounce', 'a Shield absorbs the 4/4\'s hit');
$check(SWUBotCombatOutcome(SWUBotViewForMz(1, 'myGroundArena-1'), SWUBotViewForMz(1, 'theirGroundArena-0')) === 'trade',
    'Saboteur strips the Shield, so its 2 kills the 1-remaining Marine (and the Marine\'s 3 kills the 2/3)');
$check(SWUBotUnitValue($def) === floatval(CardCost('SOR_095')) + 1.0 + 0.5, 'unit value = cost + upgrades + 0.5 × shields');

// Overwhelm kill.
$build(function ($b) {
    $b->WithGroundUnitForPlayer(1, 'SOR_164', true);    // Wampa 4/5 Overwhelm
    $b->WithGroundUnitForPlayer(2, 'SOR_095', true, 2); // 3/3 with 2 damage → 1 remaining
});
$w = SWUBotViewForMz(1, 'myGroundArena-0'); $t = SWUBotViewForMz(1, 'theirGroundArena-0');
$check($w['overwhelm'] === true && $t['remaining'] === 1, 'fixture: Overwhelm attacker vs a 1-HP defender');
$check(SWUBotOverwhelmKills($w, $t) === true, 'an Overwhelm attacker that kills pushes excess');
$check(SWUBotOverwhelmKills(SWUBotViewForMz(1, 'theirGroundArena-0'), $w) === false, 'a non-Overwhelm unit never does');

// ── Normal's race test ───────────────────────────────────────────────────────────────────────────
$build(function ($b) {
    $b->WithInitiativePlayerBeing(1);
    $b->WithGroundUnitForPlayer(1, 'SOR_046', true); $b->TheirBase('SOR_020', 21);   // 9 left / 3 → 3 rounds
    $b->WithGroundUnitForPlayer(2, 'SOR_095', true); $b->MyBase('SOR_024', 21);   // 9 left / 3 → 3 rounds
});
$check(SWUBotClock(1, 2) === 3 && SWUBotClock(2, 1) === 3, 'fixture: both clocks are 3');
$check(SWUBotIsRacing(1, 2) === true, 'a tied 3-round clock races when I hold the initiative');
$check(SWUBotIsRacing(2, 1) === false, '...and does not for the other seat');

$build(function ($b) {
    $b->WithGroundUnitForPlayer(1, 'SOR_095', true); $b->TheirBase('SOR_020', 18);   // 12 / 3 → 4 rounds
    $b->WithGroundUnitForPlayer(2, 'SOR_095', true); $b->MyBase('SOR_024', 0);    // 30 / 3 → 10 rounds
});
$check(SWUBotIsRacing(1, 2) === false, 'faster but more than 3 rounds away is not a race');

// ── Can the opponent get a Sentinel this round? ──────────────────────────────────────────────────
$build(function ($b) { $b->WithControlledResourceForPlayer(2, 'SOR_095', 2, true); });
$check(SWUBotOpponentCanGetSentinel(2) === true, 'one ready resource → they could play a Sentinel');
$build(function ($b) { $b->WithGroundUnitForPlayer(2, 'SOR_095', true); });
$check(SWUTotalPaymentCapacity(2) === 0, 'fixture: P2 has no payment capacity');
$check(SWUBotOpponentCanGetSentinel(2) === false, 'no capacity, no Sentinel-granting leader, no unit Actions → no Sentinel');

// ── Rule 5's "stabilises" (spec Section 2): their clock afterwards ≥ 3 AND longer than before ──────────
$check(SWUBotStabilises(2, 3) === true, 'stabilises: 2 → 3 rounds');
$check(SWUBotStabilises(3, 3) === false, 'not longer than before → does not stabilise');
$check(SWUBotStabilises(1, 2) === false, 'longer but under 3 rounds → does not stabilise');
$check(SWUBotStabilises(4, SWU_BOT_NO_CLOCK) === true, 'no reachable damage left counts as stabilised');
$check(SWUBotStabilises(SWU_BOT_NO_CLOCK, SWU_BOT_NO_CLOCK) === false, 'no clock before either → nothing to stabilise');

bot_test_finish();

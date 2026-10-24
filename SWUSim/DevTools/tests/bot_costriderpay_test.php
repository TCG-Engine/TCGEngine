<?php
// The "costs less for each one you pick" riders must leave the play PAYABLE. HMW meta run 2026-10-24: every game with the Sabé Data
// Vault list (melee 480819) on seat 1 hit the 3,000-step cap in round 2. It held HMW_049 Greater Sarlacc with 3 ready resources; the
// card is offered at its best-case price (defeat all three, 3 less each), but at "Defeat_any_number_of_ready_resources_for_3_resources_
// less_each" the bot answered '-', HMW_049#0's abort refused the play ("nothing was defeated"), the card stayed in hand, and the next
// step played it again — ~1,500 times. HMW_125 The Marauder has the same shape (damage friendly units, 1 less each).
// Fix: _SWUBotCostRiderPayableAnswers (BotLegalActions.php) keeps only the answers each handler's own gate would accept; the choosers
// still pick among those. Siblings: bot_exploitpay_test.php, bot_droidpay_test.php.
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php -d apc.enable_cli=1 -d xdebug.mode=off SWUSim/DevTools/tests/bot_costriderpay_test.php
require __DIR__ . '/fixtures/bot_test_bootstrap.php';
include_once './SWUSim/BotLegalActions.php';
include_once './SWUSim/BotHeuristic.php';
include_once './SWUSim/Custom/BotLookahead.php';

global $playerID;
$picks = fn(string $c) => ($c === '-' || $c === 'PASS' || $c === '') ? 0 : count(explode('&', $c));
$answers = fn(array $legal) => array_map(fn($a) => strval($a['cardID'] ?? ''), (array)$legal['actions']);
$openPicker = function () use (&$gameName, $act) {
    $act(1, 10002, 'myHand-0!FSM!');
    return SWUBotLegalActions($gameName, 1);
};
$finish = function (array $legal, string $style) use ($act) {
    $pick = SWUBotHeuristicChoose($style, (array)$legal['actions'], $legal, '');
    $act(1, intval($pick['mode'] ?? 100), strval($pick['cardID'] ?? ''));
    return strval($pick['cardID'] ?? '');
};

// ── HMW_049 Greater Sarlacc ──────────────────────────────────────────────────────────────────────────
$sarlacc = function (int $resources) use ($build) {
    $build(function ($b) use ($resources) {
        $b->FillResourcesForPlayer(1, 'SOR_095', $resources);
        $b->WithCardInHandForPlayer(1, 'HMW_049');
    });
};
// The price on this fixture (aspect penalty included) and the smallest ready pool that can play it — derived, never literals.
$sarlacc(1);
$playerID = 1;
$cost = SWUComputePlayCost(1, GetZoneObject('myHand-0'));
$minR = 0;
for ($r = 1; $r <= $cost; $r++) { if (_SWUHmw049MinOutlay(1, $cost, $r) <= $r) { $minR = $r; break; } }
$check($minR > 0 && $minR < $cost, "fixture: Greater Sarlacc costs $cost here; the smallest pool that plays it is $minR ready resources");

// 1. The minimum pool: only answers that defeat enough resources stay, and the Sarlacc is PLAYED.
foreach (['aggro', 'midrange', 'control'] as $style) {
    $sarlacc($minR);
    $playerID = 1;
    $legal = $openPicker();
    $check(($legal['decisionTooltip'] ?? '') === 'Defeat_any_number_of_ready_resources_for_3_resources_less_each', "[$style] the Sarlacc picker is pending: " . ($legal['decisionTooltip'] ?? ''));
    $got = $answers($legal);
    $check(!empty($got) && !in_array('-', $got, true) && !in_array('PASS', $got, true), "[$style] the doomed decline is gone: " . implode(' , ', $got));
    $finish($legal, $style);
    $check(count(GetHand(1)) === 0, "[$style] the Sarlacc was PLAYED");
    $check(strpos(strval(GetFlashMessage()), 'Not enough') === false, "[$style] no 'Not enough' flash: " . GetFlashMessage());
}

// 2. Payable with resources alone: the filter must NOT force a defeat — the decline stays.
$sarlacc($cost);
$legal = $openPicker();
$check(in_array('-', $answers($legal), true) || in_array('PASS', $answers($legal), true), 'full resources: the decline stays (' . count($answers($legal)) . ' answers)');

// ── HMW_125 The Marauder ─────────────────────────────────────────────────────────────────────────────
const MARAUDER_UNITS = 2;
$marauder = function (int $resources) use ($build) {
    $build(function ($b) use ($resources) {
        if ($resources > 0) $b->FillResourcesForPlayer(1, 'SOR_095', $resources);
        for ($i = 0; $i < MARAUDER_UNITS; $i++) $b->WithGroundUnitForPlayer(1, 'SOR_095', true);
        $b->WithCardInHandForPlayer(1, 'HMW_125');
    });
};
$marauder(0);
$playerID = 1;
$mCost = SWUComputePlayCost(1, GetZoneObject('myHand-0'));
$check($mCost > MARAUDER_UNITS, "fixture: The Marauder costs $mCost here, more than the " . MARAUDER_UNITS . " friendly units");

// 3. Short by every unit: only answers choosing all of them stay, and the Marauder is PLAYED.
$marauder($mCost - MARAUDER_UNITS);
$legal = $openPicker();
$got = $answers($legal);
$check(!empty($got) && min(array_map($picks, $got)) >= MARAUDER_UNITS, 'Marauder: only answers choosing all ' . MARAUDER_UNITS . ' units stay: ' . implode(' , ', $got));
$finish($legal, 'midrange');
$check(count(GetHand(1)) === 0, 'Marauder: played');

bot_test_finish();

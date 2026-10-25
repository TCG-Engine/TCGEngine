<?php
// The Droid-pay picker must leave the play PAYABLE. Game 1647080 (Twin Suns, 2026-10-19): Arenabot Beta controlled SEC_122
// Vuutun Palaa ("each friendly Droid unit may be exhausted to pay costs as if it were a resource"), held 1 ready resource +
// 3 ready Droids, and played a 4-cost card. The card is offered because SWUTotalPaymentCapacity counts the Droids, but at
// "Pick_any_number_of_Droids_to_exhaust_for_this_card" the bot answered '-', DROID_PAY refused the play ("Not enough ready
// resources (need 4)"), the card stayed in hand, and the next step played it again — forever, since each failed attempt
// spans three bot steps and every step changes the gamestate, so the controller's no-op exclusion never trips.
// Fix: _SWUBotDroidPayableAnswers (BotLegalActions.php) keeps only the answers that cover the shortfall the ready resources
// leave; the choosers still pick among those. Sibling of bot_exploitpay_test.php.
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php -d apc.enable_cli=1 -d xdebug.mode=off SWUSim/DevTools/tests/bot_droidpay_test.php
require __DIR__ . '/fixtures/bot_test_bootstrap.php';
include_once './SWUSim/BotLegalActions.php';
include_once './SWUSim/BotHeuristic.php';
include_once './SWUSim/Custom/BotLookahead.php';

const DROIDS = 3;
// SEC_122 Vuutun Palaa + DROIDS ready Battle Droid tokens; SOR_095 Battlefield Marine in hand.
$board = function (int $resources) use ($build) {
    $build(function ($b) use ($resources) {
        if ($resources > 0) $b->FillResourcesForPlayer(1, 'SOR_095', $resources);
        $b->WithSpaceUnitForPlayer(1, 'SEC_122', true);
        for ($i = 0; $i < DROIDS; $i++) $b->WithGroundUnitForPlayer(1, 'TS26_T01', true);
        $b->WithCardInHandForPlayer(1, 'SOR_095');
    });
};
// The price on this fixture (aspect penalty included) — derived, never a literal.
$board(0);
global $playerID; $playerID = 1;
$cost = SWUComputePlayCost(1, GetZoneObject('myHand-0'));
$check($cost >= 1 && $cost <= DROIDS + 1, "fixture: SOR_095 costs $cost here, within reach of the Droids");

$openPicker = function () use (&$gameName, $act) {
    $act(1, 10002, 'myHand-0!FSM!');
    return SWUBotLegalActions($gameName, 1);
};
$picks = fn(string $c) => ($c === '-' || $c === 'PASS' || $c === '') ? 0 : count(explode('&', $c));
$answers = fn(array $legal) => array_map(fn($a) => strval($a['cardID'] ?? ''), (array)$legal['actions']);
$finish = function (array $legal, string $style) use ($act) {
    $pick = SWUBotHeuristicChoose($style, (array)$legal['actions'], $legal, '');
    $act(1, intval($pick['mode'] ?? 100), strval($pick['cardID'] ?? ''));
    return strval($pick['cardID'] ?? '');
};

// 1. Payable ONLY with every Droid (no resources): the decline and the short answers are doomed and must be gone.
foreach (['aggro', 'midrange', 'control'] as $style) {
    $board(0);
    $playerID = 1;
    $legal = $openPicker();
    $check(($legal['decisionTooltip'] ?? '') === 'Pick_any_number_of_Droids_to_exhaust_for_this_card', "[$style] the Droid-pay picker is pending: " . ($legal['decisionTooltip'] ?? ''));
    $got = $answers($legal);
    $short = array_filter($got, fn($c) => $picks($c) < $cost);
    $check(!empty($got) && empty($short), "[$style] only answers with >= $cost Droids stay: " . implode(' , ', $got));
    $finish($legal, $style);
    $check(count(GetHand(1)) === 0, "[$style] the Marine was PLAYED");
    $check(strpos(strval(GetFlashMessage()), 'Not enough') === false, "[$style] no 'Not enough' flash: " . GetFlashMessage());
}

// 2. One resource short: the decline goes, answers with at least one Droid stay.
$board($cost - 1);
$legal = $openPicker();
$got = $answers($legal);
$check(!in_array('-', $got, true) && !empty($got) && min(array_map($picks, $got)) === 1, 'one short: the decline is dropped, every answer has >= 1 Droid: ' . implode(' , ', $got));
$finish($legal, 'midrange');
$check(count(GetHand(1)) === 0, 'one short: played');

// 3. Payable with resources alone: the filter must NOT force a Droid exhaust — the decline stays.
$board($cost);
$legal = $openPicker();
$got = $answers($legal);
$check(in_array('-', $got, true), 'full resources: the decline stays: ' . implode(' , ', $got));
$finish($legal, 'midrange');
$check(count(GetHand(1)) === 0, 'full resources: played');

bot_test_finish();

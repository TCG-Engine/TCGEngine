<?php
// Three fixes to the curve-value mulligan ('curvemull', p38), screened as proposals (2026-10-07 gap screen).
// FOUND 2026-10-07 bisecting The Mandalorian (ASH) Colossus vs Darth Vader (JTL) Yellow: Mando won 9/20 on 2026-10-01 and 2/20 on the
// same seeds today; 7 of the 8 flipped games start at a Mando MULLIGAN under curvemull, which now throws away 84 of 100 Mando hands
// (0 of 20 on 10-01). _SWUBotCurveMulligan sets two cards aside to resource and needs 2 castable (cost <= 4) with a summed surplus
// >= 0 among the rest — sized for a 6-card hand, while the Colossus base (JTL_021: "Draw 1 less card in your starting hand") deals 5;
// a board-less event prices below its cost (Let's Call It War -1.0, Charged with Treason -4.0); and wipes and bombs never count.
//   mullhandsize   — the castable requirement scales with the hand: max(1, hand size - 4) (6 cards -> 2, Colossus 5 -> 1)
//   mulleventclamp — at the mulligan an Event's surplus counts max(0, s): removal has nothing to price against yet
//   mullanswer     — the control wing (rank >= 3) keeps a hand that holds an answer (removal / wipe) and one castable card
//                    (owner ruling, 2026-09-20 'mullstyle': "control needs an answer AND a curve")
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php -d apc.enable_cli=1 -d xdebug.mode=off SWUSim/DevTools/tests/bot_mullfix_test.php
require __DIR__ . '/fixtures/bot_test_bootstrap.php';
include_once './SWUSim/BotLegalActions.php';
include_once './SWUSim/BotHeuristic.php';
foreach (['mullhandsize', 'mulleventclamp', 'mullanswer'] as $p)
    $check(in_array($p, SWUBotProposalList(), true) && SWUBotVariantDisabled("try-$p") === ["try:$p"], "$p is a switchable proposal");

// Mando (ASH_014) on Colossus (JTL_021) against Vader Yellow (JTL_006 on ASH_026), as in the sweep.
$check(SWUBotVariantDisabled('try-mullfix') === ['try:mullhandsize', 'try:mulleventclamp', 'try:mullanswer'], "'try-mullfix' switches all three on");
$mull = function (array $hand, string $style = 'hardcontrol', array $variant = []) use ($build) {
    $b = new GameStateBuilder(); CommonSetup($b, 'grw', 'brk', ['leaderCardID' => 'ASH_014', 'baseCardID' => 'JTL_021'],
        ['leaderCardID' => 'JTL_006', 'baseCardID' => 'ASH_026']);
    $b->WithActivePlayer(1); $b->WithGamePhase('MAIN');
    foreach ($hand as $c) $b->WithCardInHandForPlayer(1, $c);
    $g = new GameTestAdapter(); $g->loadState($b); ob_start(); AutoAdvanceAndExecute(); ob_end_clean();
    SWUBotSetDisabledFeatures($variant); $r = _SWUBotShouldMulligan(1, $style); SWUBotSetDisabledFeatures([]);
    return $r;
};
// s005 (Vader first): Droid Laser Turret, Hyperspace Disaster, Crushing Blow, Single Reactor Ignition, Let's Call It War — two wipes,
// cheap removal and a Sentinel against space aggro. On 10-01 Mando kept it and won in R13.
$s005 = ['LAW_118', 'SEC_078', 'LOF_077', 'LAW_044', 'SEC_180'];
// s007 (Vader first): a 1-2-3-4-6 curve.
$s007 = ['SEC_148', 'LAW_097', 'LAW_132', 'LAW_118', 'JTL_153'];
$check($mull($s005) === true && $mull($s007) === true, 'premise: today both recorded Mando hands are mulliganed');

// mullhandsize, isolated: a 5-card hand that fails ONLY the castable count — three 7-drops (two set aside) and the 1-drop
// Imperial Door Technician (surplus +0.33). One castable card is enough when the hand is 5. (s007 fails on surplus too — the
// Turret prices at -0.45 — so this lever alone does not rescue it.)
$five = ['LAW_097', 'HMW_093', 'HMW_093', 'HMW_093', 'HMW_093'];
$check($mull($five) === true, 'premise: the 5-card hand with one castable card is mulliganed today');
$check($mull($five, 'hardcontrol', ['try:mullhandsize']) === false, '@try-mullhandsize: a 5-card hand needs only 1 castable — kept');
$check($mull($s005, 'hardcontrol', ['try:mullanswer']) === false, '@try-mullanswer: two wipes + removal + a Sentinel is kept');

// mulleventclamp, isolated: a 6-card hand whose only flaw is a board-less event pricing below cost. Two 7-drops set aside, then
// Let's Call It War (SEC_180, an event: surplus -1 with no board) beside three on-curve Battlefield Marines (surplus 0).
$check(SWUBotCurveSurplus(1, 'SEC_180', SWUBotHorizon('midrange', 1), false) < 0, 'premise: a board-less Let\'s Call It War prices below cost');
$clampHand = ['HMW_093', 'HMW_093', 'SOR_095', 'SOR_095', 'SOR_095', 'SEC_180'];
$plain = $mull($clampHand, 'midrange');
$clamp = $mull($clampHand, 'midrange', ['try:mulleventclamp']);
$check($plain === true, 'premise: the board-less event sinks the surplus — mulligan; got ' . json_encode($plain));
$check($clamp === false, '@try-mulleventclamp: the event counts 0, the hand is kept; got ' . json_encode($clamp));

// The fixes stay narrow:
// mullhandsize leaves a 6-card hand needing 2 castable — one castable card after the set-aside is still a mulligan.
$check($mull(['HMW_093', 'HMW_093', 'HMW_093', 'HMW_093', 'HMW_093', 'SOR_095'], 'midrange', ['try:mullhandsize']) === true,
    '@try-mullhandsize: a 6-card hand with one castable card is still mulliganed');
// mullanswer is control-only: the same s005 hand on the aggro wing is decided as before.
$check($mull($s005, 'hyperaggro', ['try:mullanswer']) === $mull($s005, 'hyperaggro'), '@try-mullanswer: the aggro wing is unchanged');
// mullanswer needs a castable card: an answer-only hand of 7+-cost wipes is still a mulligan.
$check($mull(['LAW_044', 'LAW_044', 'LAW_044', 'HMW_093', 'HMW_093'], 'hardcontrol', ['try:mullanswer']) === true,
    '@try-mullanswer: answers but nothing castable by round 3 — still a mulligan');

bot_test_finish();

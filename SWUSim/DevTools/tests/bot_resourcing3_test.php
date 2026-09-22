<?php
// FEATURE 'resourcing3' (group p8, SHIPPED 2026-09-22, default ON) and proposal 'piettcheat' (default OFF). resourcing2 was confirmed on Thrawn DV vs Vader
// (+104 / 2,000) but failed safety: vs Dedra −142 (it read "aggressive" off the board) and Piett vs Vader −46 (it
// resourced the Capital Ships the deck cheats out). Owner: resource high-cost early "unless you can ramp or the matchup
// is slow"; Piett "should cheat out capital ships to be able to trade and stall against Vader".
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php -d apc.enable_cli=1 -d xdebug.mode=off SWUSim/DevTools/tests/bot_resourcing3_test.php
require __DIR__ . '/fixtures/bot_test_bootstrap.php';
include_once './SWUSim/BotLegalActions.php';
include_once './SWUSim/Custom/BotLookahead.php';
include_once './SWUSim/BotHeuristic.php';

$check(SWUBotVariantDisabled('try-piettcheat') === ['try:piettcheat'], 'proposal piettcheat is registered');
$check(in_array('resourcing3', SWUBotFeatureList(), true) && SWUBotFeatureGroups()['p8'] === ['resourcing3'], 'resourcing3 is a shipped feature, group p8');
$check(SWUBotVariantDisabled('no-resourcing3') === ['resourcing3'] && SWUBotVariantDisabled('try-resourcing3') === null, '@no-resourcing3 switches it off; it is no longer a proposal');
$ON = []; $OFF = ['resourcing3']; $R2 = ['try:resourcing2'];   // shipped default · the pre-p8 stack · the measured forerunner

$resourced = function (int $n, array $on) use ($botCtx) {
    SWUBotSetDisabledFeatures($on);
    $out = [];
    foreach (SWUBotChooseResourceCards($botCtx('softcontrol'), $n) as $mz) $out[] = strval(GetHand(1)[intval(substr($mz, strlen('myHand-')))]->CardID ?? '?');
    SWUBotSetDisabledFeatures([]);
    sort($out);
    return $out;
};
$Q1HAND = ['SEC_078', 'ASH_133', 'LAW_133', 'ASH_048', 'ASH_053'];

// ── vs Vader (an aggro leader) resourcing3 is resourcing2: the owner's Q1 position still resources Trask Walker ──
$build(function ($b) use ($Q1HAND) {
    $b->MyLeader('JTL_002', false, false, true); $b->FillResourcesForPlayer(1, 'SOR_095', 4, false);
    foreach ($Q1HAND as $c) $b->WithCardInHandForPlayer(1, $c);
    $b->TheirLeader('JTL_006', true);
    foreach (['SEC_215', 'JTL_085', 'JTL_221', 'JTL_081', 'JTL_081'] as $s) $b->WithSpaceUnitForPlayer(2, $s, false);
});
$check($resourced(1, $ON) === ['ASH_133'], 'vs Vader: the shipped default resources Trask Walker, as resourcing2 does');
$check($resourced(1, $ON) === $resourced(1, $R2), 'vs Vader: the shipped default == resourcing2');
$check($resourced(1, $OFF) !== $resourced(1, $ON), '@no-resourcing3 restores the pre-p8 pick vs Vader (' . implode(',', $resourced(1, $OFF)) . ')');

// ── vs Dedra (hard control) with 3 units out: resourcing2 called it "aggressive"; resourcing3 leaves it to the default ──
$build(function ($b) use ($Q1HAND) {
    $b->MyLeader('JTL_002', false, false, true); $b->FillResourcesForPlayer(1, 'SOR_095', 4, false);
    foreach ($Q1HAND as $c) $b->WithCardInHandForPlayer(1, $c);
    $b->TheirLeader('SEC_010', true);
    foreach (['SOR_095', 'SOR_095', 'LOF_084'] as $u) $b->WithGroundUnitForPlayer(2, $u, true);
});
$r2 = $resourced(1, $R2); $r3 = $resourced(1, $ON); $def = $resourced(1, $OFF);
$check($r2 === ['ASH_133'], 'fixture: resourcing2 treats the Dedra board as aggressive and resources the 7-drop (' . implode(',', $r2) . ')');
$check($r3 === $def, "vs Dedra: resourcing3 resources what the shipped resourcer does (" . implode(',', $def) . ')');
$check($r3 !== $r2, 'vs Dedra: resourcing3 no longer follows resourcing2');

// ── Piett Blue vs Vader: a capital-ship deck keeps its Capital Ships ─────────────────────────────────────────────
// Lawbringer (8) and Cantwell (7) are 7+ non-answers: resourcing2 puts them in tier 1 before the flip.
$build(function ($b) {
    $b->MyLeader('JTL_005', false, false, true); $b->FillResourcesForPlayer(1, 'SOR_095', 3, false);
    foreach (['LAW_101', 'SEC_037', 'SEC_080', 'LAW_118', 'SEC_110'] as $c) $b->WithCardInHandForPlayer(1, $c);
    $b->TheirLeader('JTL_006', true);
    foreach (['JTL_081', 'JTL_081', 'SEC_215'] as $s) $b->WithSpaceUnitForPlayer(2, $s, false);
});
$p2 = $resourced(1, $R2); $p3 = $resourced(1, $ON);
$check(in_array($p2[0], ['LAW_101', 'SEC_037'], true), 'fixture: resourcing2 resources a Capital Ship (' . $p2[0] . ')');
$check(!in_array($p3[0], ['LAW_101', 'SEC_037'], true), 'Piett: resourcing3 keeps both Capital Ships (resourced ' . $p3[0] . ')');

// ── piettcheat: the discounted play beats the full-price play of the same ship ─────────────────────────────────────
$build(function ($b) {
    $b->MyLeader('JTL_005', true, false, false); $b->FillResourcesForPlayer(1, 'SOR_095', 8);
    $b->WithCardInHandForPlayer(1, 'JTL_038');                   // Corvus (5), a Capital Ship
    $b->TheirLeader('JTL_006', true);
    $b->WithSpaceUnitForPlayer(2, 'JTL_081', true);
});
$score = function (string $id, array $on) use ($botCtx) {
    SWUBotSetDisabledFeatures($on); $ctx = $botCtx('softcontrol'); $out = null;
    foreach ($ctx['actions'] as $i => $a) { if (strval($a['cardID']) === $id) $out = SWUBotScoreAction($ctx, $a, $i); }
    SWUBotSetDisabledFeatures([]);
    return $out;
};
$ab = 'myLeader-0!CustomInput!LeaderAbility';
$check($score($ab, []) !== null && $score($ab, []) <= $score('myHand-0!FSM!', []), 'fixture: by default the ability is worth no more than the full-price play');
$check($score($ab, ['try:piettcheat']) > $score('myHand-0!FSM!', ['try:piettcheat']), 'piettcheat: the discounted play outranks the full-price play');

// ...and at the ship prompt it plays the best ship, not the first legal one.
$build(function ($b) {
    $b->MyLeader('JTL_005', true, false, false); $b->FillResourcesForPlayer(1, 'SOR_095', 12);
    $b->WithCardInHandForPlayer(1, 'ASH_099');                   // Gozanti (5) first in hand
    $b->WithCardInHandForPlayer(1, 'LAW_101');                   // Lawbringer (8)
    $b->TheirLeader('JTL_006', true);
    $b->WithSpaceUnitForPlayer(2, 'JTL_081', true);
});
$act(1, 10001, $ab);
$ctx = $botCtx('softcontrol');
$check(str_starts_with($ctx['tooltip'], 'Play_a_Capital_Ship'), 'fixture: the Piett ship prompt is pending (' . $ctx['tooltip'] . ')');
$legal = SWUBotLegalActions($gameName, 1);
SWUBotSetDisabledFeatures(['try:piettcheat']); $pick = SWUBotHeuristicChoose('softcontrol', (array)$legal['actions'], $legal, 'try-piettcheat'); SWUBotSetDisabledFeatures([]);
$picked = strval(GetHand(1)[intval(substr(strval($pick['cardID'] ?? ''), strlen('myHand-')))]->CardID ?? '?');
$check($picked === 'LAW_101', "piettcheat: the prompt plays the bigger ship (picked $picked)");

bot_test_finish();

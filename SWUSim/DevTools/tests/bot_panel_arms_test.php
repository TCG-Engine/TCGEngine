<?php
// The 2026-09-20 overnight screen's new switches (all default OFF): placebo, shrinkfirstall, threatholdall,
// sentinelkeepall, keepequal, and the weight probes @w-initiative-up / @w-maxunits-on / @w-develop-up.
// Everything shipped so far was measured on CONTROL seats only; these lift those gates so a 5-archetype panel can
// ask whether they were right. See SWUSim/Custom/BotFeatures.php.
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php -d apc.enable_cli=1 -d xdebug.mode=off SWUSim/DevTools/tests/bot_panel_arms_test.php
require __DIR__ . '/fixtures/bot_test_bootstrap.php';
include_once './SWUSim/BotLegalActions.php';
include_once './SWUSim/Custom/BotLookahead.php';
include_once './SWUSim/BotHeuristic.php';

$on = fn(string $p) => SWUBotSetDisabledFeatures(["try:$p"]);
$off = fn() => SWUBotSetDisabledFeatures([]);
$rules = SWUBotRulesAfterFilter();
$quiet = fn($b) => $b->MyLeader('SOR_014', false, false, true);

foreach (['placebo', 'shrinkfirstall', 'threatholdall', 'sentinelkeepall', 'keepequal'] as $p) {
    $check(SWUBotVariantDisabled("try-$p") === ["try:$p"], "proposal $p is registered");
}
foreach (['initiative-up', 'maxunits-on', 'develop-up'] as $w) {
    $check(SWUBotVariantDisabled("w-$w") === ["w:$w"], "weight probe @w-$w is registered");
    $check(isset($GLOBALS['SWUBotChoosers']["heuristic-midrange@w-$w"]), "profile heuristic-midrange@w-$w exists");
}

// ── placebo: switches NOTHING off, so the stack is the default one ─────────────────────────────────
$check(!array_intersect(SWUBotVariantDisabled('try-placebo'), SWUBotFeatureList()),
    'placebo disables no feature');
$build(function ($b) use ($quiet) {
    $quiet($b); $b->FillResourcesForPlayer(1, 'SOR_095', 6);
    $b->WithGroundUnitForPlayer(1, 'SOR_095', true); $b->WithGroundUnitForPlayer(2, 'LOF_084', true);
    $b->WithCardInHandForPlayer(1, 'SOR_095'); $b->WithCardInHandForPlayer(1, 'JTL_043');
});
$legal = SWUBotLegalActions($gameName, 1);
$plain = SWUBotHeuristicChoose('softcontrol', (array)$legal['actions'], $legal, '');
$plac  = SWUBotHeuristicChoose('softcontrol', (array)$legal['actions'], $legal, 'try-placebo');
$check(strval($plain['cardID']) === strval($plac['cardID']), 'placebo picks exactly what the default picks');

// ── weight probes ──────────────────────────────────────────────────────────────────────────────────
$w = fn(string $style, string $variant = '') => (function () use ($style, $variant) {
    SWUBotSetDisabledFeatures(SWUBotVariantDisabled($variant) ?? []);
    $out = SWUBotWeights($style, 1);
    SWUBotSetDisabledFeatures([]);
    return $out;
})();
$check($w('midrange')['maxUnits'] == 0.0 && $w('midrange', 'w-maxunits-on')['maxUnits'] == 2.0,
    'maxunits-on FLOORS a zero weight (a multiplier could not)');
$check($w('hyperaggro')['maxUnits'] == 4.0 && $w('hyperaggro', 'w-maxunits-on')['maxUnits'] == 4.0,
    'maxunits-on never LOWERS a weight (hyper aggro keeps 4.00)');
$check($w('softcontrol')['initiative'] == 0.05 && $w('softcontrol', 'w-initiative-up')['initiative'] == 0.60,
    'initiative-up lifts 0.05 to 0.60');
$check($w('midrange', 'w-develop-up')['develop'] == 2 * $w('midrange')['develop'], 'develop-up doubles develop');
$check($w('midrange', 'w-develop-up')['base'] == $w('midrange')['base'], 'develop-up touches nothing else');

// ── shrinkfirstall: the p6 rule for a MIDRANGE seat ────────────────────────────────────────────────
$build(function ($b) use ($quiet) {
    $quiet($b);
    $b->FillResourcesForPlayer(1, 'SOR_095', 8);
    $b->WithCardInHandForPlayer(1, 'SEC_075');                  // Knowledge and Defense, -2/-2 + draw
    $b->WithGroundUnitForPlayer(1, 'SOR_095', true);
    $b->WithGroundUnitForPlayer(2, 'LAW_038', true);            // ready Lepi Lookout 3/1
});
$mid = fn() => ($rules['shrink-first'])($botCtx('midrange'));
$ctrl = fn() => ($rules['shrink-first'])($botCtx('softcontrol'));
$check($ctrl() !== null, 'fixture: the shipped rule fires for a control seat');
$check($mid() === null, 'shrinkfirst is control-only by default: a midrange seat abstains');
$on('shrinkfirstall'); $midOn = $mid(); $off();
$check($midOn !== null && strval($midOn['cardID']) === 'myHand-0!FSM!', 'shrinkfirstall: the midrange seat plays it too');

// ── threatholdall: the p5 hold for a MIDRANGE seat ─────────────────────────────────────────────────
// JTL_043 No Glory (bomb-killer) with only a cheap, low-threat enemy on board → the hold applies.
$build(function ($b) use ($quiet) {
    $quiet($b);
    $b->FillResourcesForPlayer(1, 'SOR_095', 9);
    $b->WithCardInHandForPlayer(1, 'JTL_043');
    $b->WithGroundUnitForPlayer(2, 'LOF_061', true);            // Secretive Sage 2/2, cost 2: cheap and under the 3-power threat bar
});
// 'dudgate' (a shipped p3 feature) independently scores this play -0.5 for EVERY style — it judges No Glory on a
// 2-cost 2/2 as too small a gain — so it is held out here to leave 'threathold' as the only thing under test.
$score = function (string $style, array $disabled = []) use ($botCtx) {
    SWUBotSetDisabledFeatures(array_merge(['dudgate'], $disabled));
    $ctx = $botCtx($style); $out = null;
    foreach ($ctx['actions'] as $i => $a) { if (strval($a['cardID']) === 'myHand-0!FSM!') $out = SWUBotScoreAction($ctx, $a, $i); }
    SWUBotSetDisabledFeatures([]);
    return $out;
};
$check($score('softcontrol') == -0.5, 'fixture: the control seat HOLDS No Glory (threathold, shipped)');
$check($score('midrange') > -0.5, 'threathold is control-only by default: the midrange seat does not hold');
$check($score('midrange', ['try:threatholdall']) == -0.5, 'threatholdall: the midrange seat holds it too');

// ── sentinelkeepall + keepequal (resourcing) ───────────────────────────────────────────────────────
// SOR_063 Wing Guard is a printed Sentinel; JTL_043 No Glory is a key card (removal).
// $prePl2 pins the seat to the PRE-p12 resourcer. 'mgbomb' shipped 2026-09-24 and gave midrange the
// castable-soon + protect-one-bomb keep rule, which changes the baseline pick in these fixtures.
$resourceFirst = function (string $style, string $prop = '', bool $prePl2 = false) use ($botCtx, $on, $off) {
    SWUBotSetDisabledFeatures($prePl2 ? ['mgbomb'] : []);
    if ($prop !== '') $on($prop);
    $mz = SWUBotChooseResourceCards($botCtx($style), 1)[0];
    if ($prop !== '') $off();
    SWUBotSetDisabledFeatures([]);
    return strval(GetHand(1)[intval(substr($mz, strlen('myHand-')))]->CardID ?? '?');
};
$build(function ($b) use ($quiet) {
    $quiet($b); $b->FillResourcesForPlayer(1, 'SOR_095', 2);
    foreach (['JTL_041', 'SOR_063', 'SOR_095'] as $c) $b->WithCardInHandForPlayer(1, $c);   // bomb, Sentinel, filler
});
// ⚠ PINNED PRE-p12. On the SHIPPED stack this arm is now a NO-OP in this position: p12's castable-soon rule
// already resources the filler (SOR_095) rather than the Sentinel, so base and arm both return SOR_095 and the
// assertion would prove nothing. Measured against the fallback it still shows the arm doing its job.
// DEBT: 'sentinelkeepall' needs re-measuring on top of p12 — p12 may have subsumed it for midrange entirely.
$check($resourceFirst('midrange', '', true) === 'SOR_063'
    && $resourceFirst('midrange', 'sentinelkeepall', true) === 'SOR_095',
    'sentinelkeepall (pre-p12): a midrange seat stops resourcing its Sentinel');
$check($resourceFirst('midrange') === $resourceFirst('midrange', 'sentinelkeepall'),
    '… and on the SHIPPED p12 stack the arm is inert here — p12 already keeps the Sentinel');
$build(function ($b) use ($quiet) {
    $quiet($b); $b->FillResourcesForPlayer(1, 'SOR_095', 2);
    // LAW_133 Lost and Forgotten (6, removal): a key card too dear to be 'castable soon' at 2 resources, and NOT a
    // wipe — the shipped 'wipekeep' feature protects wipes, which would mask what 'keepequal' does.
    foreach (['JTL_041', 'LAW_133', 'SOR_095'] as $c) $b->WithCardInHandForPlayer(1, $c);
});
$base = $resourceFirst('softcontrol'); $eq = $resourceFirst('softcontrol', 'keepequal');
$check($base === 'LAW_133' && $eq === 'SOR_095', "keepequal: control keeps its removal and resources filler instead (was $base, now $eq)");
$check($resourceFirst('midrange') === $resourceFirst('midrange', 'keepequal'),
    'keepequal changes nothing for midrange (it already keeps key cards)');

bot_test_finish();

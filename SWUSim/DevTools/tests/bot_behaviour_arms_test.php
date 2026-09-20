<?php
// The 2026-09-20 BEHAVIOUR screen's arms (all default OFF): three mulligan rules, seven behaviour arms and the two
// jitter nulls. See SWUSim/Custom/BotFeatures.php. ⚠ The shipped bot has NEVER mulliganed — BotFallback's YESNO
// branch always scored "keep" — so the mulligan arms are the first opening-hand decision the bot has ever made.
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php -d apc.enable_cli=1 -d xdebug.mode=off SWUSim/DevTools/tests/bot_behaviour_arms_test.php
require __DIR__ . '/fixtures/bot_test_bootstrap.php';
include_once './SWUSim/BotLegalActions.php';
include_once './SWUSim/Custom/BotLookahead.php';
include_once './SWUSim/BotHeuristic.php';

$on = fn(string $p) => SWUBotSetDisabledFeatures(["try:$p"]);
$off = fn() => SWUBotSetDisabledFeatures([]);
$rules = SWUBotRulesAfterFilter();
$quiet = fn($b) => $b->MyLeader('SOR_014', false, false, true);
$ARMS = ['mullnocast', 'mullcurve', 'mullstyle', 'killfirst', 'blockerfirst', 'tradewhenbehind', 'leaderrisk',
         'removalready', 'playsurvivor', 'sentineltiming'];
foreach ($ARMS as $p) $check(SWUBotVariantDisabled("try-$p") === ["try:$p"], "proposal $p is registered");
foreach (['jitter-up', 'jitter-down'] as $w) $check(SWUBotVariantDisabled("w-$w") === ["w:$w"], "null probe @w-$w is registered");

// ── the jitter nulls move ONE weight by 3% and nothing else ────────────────────────────────────────
$w = function (string $variant) { SWUBotSetDisabledFeatures(SWUBotVariantDisabled($variant) ?? []); $o = SWUBotWeights('midrange', 1); SWUBotSetDisabledFeatures([]); return $o; };
$b0 = $w('');
$check(abs($w('w-jitter-up')['develop'] - 1.03 * $b0['develop']) < 1e-9 && abs($w('w-jitter-down')['develop'] - 0.97 * $b0['develop']) < 1e-9,
    'jitter moves develop by ±3%');
$check(count(array_diff_assoc($w('w-jitter-up'), $b0)) === 1, 'jitter-up changes exactly one weight');

// ── mulligan: the shipped bot always keeps; each rule decides from the PRINTED costs of a 6-card hand ──
$hand = function (array $cards) use ($build, $quiet) { $build(function ($b) use ($quiet, $cards) { $quiet($b); foreach ($cards as $c) $b->WithCardInHandForPlayer(1, $c); }); };
$mull = function (string $style, string $prop) use ($botCtx, $on, $off) {
    if ($prop !== '') $on($prop);
    $r = _SWUBotShouldMulligan(1, $style);
    if ($prop !== '') $off();
    return $r;
};
// LOF_057 (1) · SOR_095 (2) · ASH_044 (3) · SOR_164 (4) · LAW_133 (6, removal) · SEC_078 (7, wipe) · JTL_041 (11)
$hand(['SOR_095', 'ASH_044', 'SOR_164', 'LAW_133', 'SEC_078', 'JTL_041']);
$check($mull('midrange', '') === null, 'no mulligan proposal: the decision is left alone (the bot keeps)');
$check($mull('midrange', 'mullnocast') === false, 'mullnocast keeps a hand with two cards castable by round 2');
$hand(['SOR_164', 'LAW_133', 'SEC_078', 'JTL_041', 'SEC_078', 'JTL_041']);
$check($mull('midrange', 'mullnocast') === true, 'mullnocast mulligans a hand with none castable by round 2');
$check($mull('midrange', 'mullcurve') === true, 'mullcurve mulligans: no 2-drop and three 6+ cards');
$hand(['SOR_095', 'SOR_095', 'SOR_164', 'JTL_041', 'SEC_078', 'ASH_044']);   // LAW_133 costs 6: three 6+ would mulligan
$check($mull('midrange', 'mullcurve') === false, 'mullcurve keeps once a 2-drop is there and only two 6+ cards');
// mullstyle: the aggro wing needs an early drop; control needs an ANSWER as well as a curve.
$hand(['SOR_164', 'SOR_164', 'ASH_044', 'ASH_044', 'SOR_164', 'ASH_044']);       // 3s and 4s, no 2-drop, no removal
$check($mull('hyperaggro', 'mullstyle') === true, 'mullstyle: the aggro wing mulligans without a 1-2 drop');
$check($mull('softcontrol', 'mullstyle') === true, 'mullstyle: control mulligans a hand with no answer');
$hand(['SOR_095', 'ASH_044', 'LAW_133', 'SOR_164', 'JTL_041', 'SEC_078']);       // curve + removal
$check($mull('softcontrol', 'mullstyle') === false, 'mullstyle: control keeps a hand with answers and a curve');
$check($mull('hyperaggro', 'mullstyle') === false, 'mullstyle: the aggro wing keeps once it has a 2-drop');

// ── killfirst: a kill-and-survive attack is taken before the base ──────────────────────────────────
$build(function ($b) use ($quiet) {
    $quiet($b);
    $b->WithGroundUnitForPlayer(1, 'SOR_095', true);              // Marine 3/3
    $b->WithSpaceUnitForPlayer(1, 'JTL_095', true);               // a ship, nothing to kill in space
    $b->WithGroundUnitForPlayer(2, 'LOF_061', true);              // Sage 2/2: the Marine kills it and lives
});
$kf = fn(string $prop) => ($prop ? ($rules['kill-first'])($botCtx('midrange')) : null);
$on('killfirst'); $pick = ($rules['kill-first'])($botCtx('midrange')); $off();
$check($pick !== null && strval($pick['cardID']) === 'myGroundArena-0!FSM!', 'killfirst: the attack with a kill-and-survive target goes first');
$off(); $check(($rules['kill-first'])($botCtx('midrange')) === null, 'killfirst is inert by default');

// ── blockerfirst: behind on units, a body before the attack ────────────────────────────────────────
$behind = function (int $mine, int $theirs) use ($quiet) {
    return function ($b) use ($quiet, $mine, $theirs) {
        $quiet($b); $b->FillResourcesForPlayer(1, 'SOR_095', 6);
        for ($i = 0; $i < $mine; $i++) $b->WithGroundUnitForPlayer(1, 'SOR_095', true);
        for ($i = 0; $i < $theirs; $i++) $b->WithGroundUnitForPlayer(2, 'LOF_084', true);
        $b->WithCardInHandForPlayer(1, 'SOR_095');
    };
};
$build($behind(1, 3));
$on('blockerfirst'); $pick = ($rules['blocker-first'])($botCtx('softcontrol')); $off();
$check($pick !== null && strval($pick['cardID']) === 'myHand-0!FSM!', 'blockerfirst: behind on units → play the body first');
$build($behind(3, 1));
$on('blockerfirst'); $ahead = ($rules['blocker-first'])($botCtx('softcontrol')); $off();
$check($ahead === null, 'blockerfirst abstains while ahead on units');

// ── tradewhenbehind / leaderrisk: the loss side of a trade ─────────────────────────────────────────
$W = SWUBotWeights('midrange', 1);
$mk = fn(array $x = []) => array_merge(['cardID' => 'SOR_095', 'controller' => 1, 'arena' => 'Ground', 'power' => 3,
    'attackPower' => 3, 'hp' => 3, 'remaining' => 3, 'ready' => true, 'sentinel' => false, 'saboteur' => false,
    'overwhelm' => false, 'grit' => false, 'shields' => 0, 'upgrades' => 0, 'cost' => 2, 'isLeader' => false], $x);
$build($behind(1, 3));                                            // seat 1 is behind on units
$att = $mk(); $def = $mk(['controller' => 2, 'power' => 3, 'remaining' => 3]);
$base = SWUBotTargetValue($att, $def, $W);
$on('tradewhenbehind'); $behindV = SWUBotTargetValue($att, $def, $W); $off();
$check($behindV > $base, "tradewhenbehind: an even trade is worth more while behind ($base -> $behindV)");
$on('leaderrisk'); $ldr = SWUBotTargetValue($mk(['isLeader' => true]), $def, $W); $off();
$check($ldr > $base, 'leaderrisk: losing a deployed leader unit costs less (it returns)');
$off(); $check(SWUBotTargetValue($mk(['isLeader' => true]), $def, $W) == $base, 'leaderrisk is inert by default');

// ── playsurvivor / sentineltiming: the play branch ─────────────────────────────────────────────────
$playScore = function (string $style, string $prop = '') use ($botCtx, $on, $off) {
    if ($prop !== '') $on($prop);
    $ctx = $botCtx($style); $out = null;
    foreach ($ctx['actions'] as $i => $a) { if (strval($a['cardID']) === 'myHand-0!FSM!') $out = SWUBotScoreAction($ctx, $a, $i); }
    if ($prop !== '') $off();
    return $out;
};
$build(function ($b) use ($quiet) {
    $quiet($b); $b->FillResourcesForPlayer(1, 'SOR_095', 6);
    $b->WithCardInHandForPlayer(1, 'SOR_095');                    // a 3/3 …
    $b->WithGroundUnitForPlayer(2, 'SOR_164', true);              // … against a 4-power Wampa: it dies
});
$check($playScore('midrange', 'playsurvivor') < $playScore('midrange'), 'playsurvivor: a body that dies to their best attacker is worth less');
$build(function ($b) use ($quiet) {
    $quiet($b); $b->FillResourcesForPlayer(1, 'SOR_095', 6);
    $b->WithCardInHandForPlayer(1, 'SOR_063');                    // Wing Guard (printed Sentinel)
    $b->WithGroundUnitForPlayer(1, 'SOR_095', true);              // an attack is available
    $b->WithGroundUnitForPlayer(2, 'LOF_084', true);
});
$check($playScore('softcontrol', 'sentineltiming') < $playScore('softcontrol'), 'sentineltiming: the Sentinel waits while an attack is still on offer');

bot_test_finish();

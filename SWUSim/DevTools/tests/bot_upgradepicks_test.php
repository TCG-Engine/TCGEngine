<?php
// Feature 'upgradepicks' (group p14) — a decision whose candidates are UPGRADES or TOKENS is scored by what the
// upgrade is worth and WHOSE it is, instead of by enumeration order.
//
// FOUND in two bot reports, 2026-09-28, which turned out to be ONE root cause:
//   1. "Bot used Alliance Outpost to defeat shield on Secretive Sage and then gave it another shield."
//   2. "Bot defeated its own shield with Outer Rim Constable."
//
// ROOT CAUSE. An upgrade candidate is addressed as a SUBCARD mzID — "myGroundArena-0.u0" (Core/CoreZoneModifiers.php).
// SWUBotViewForMz() resolves with GetZoneObject(), which returns null for that form BY DESIGN ("an un-taught caller
// gets a clean miss instead of silent corruption"); the generic resolver is MZResolveObject(). The bot was exactly
// that un-taught caller, so _SWUBotTargetScore returned null for EVERY upgrade candidate and the scorer fell through
// to `$s ?? -$index * 1e-6` — the stable-order tiebreak. The enumerator lists "my*" before "their*", so the first
// candidate is always the bot's own: it defeated its own Shield every single time, deterministically, and preferred
// doing so over PASS on a "you may".
// Measured before the fix (probe, normal style): my shield score 0, ENEMY shield -1.0e-6, PASS 0 → pick = my own.
//
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php -d apc.enable_cli=1 -d xdebug.mode=off SWUSim/DevTools/tests/bot_upgradepicks_test.php
require __DIR__ . '/fixtures/bot_test_bootstrap.php';
include_once './SWUSim/BotLegalActions.php';
include_once './SWUSim/BotHeuristic.php';

$stack = function (string $style = 'normal', int $seat = 1, string $variant = '') use (&$gameName) {
    SWUBotResetCoverage();
    $legal = SWUBotLegalActions($gameName, $seat);
    $p = SWUBotHeuristicChoose($style, (array)$legal['actions'], $legal, $variant);
    return $p === null ? null : strval($p['cardID']);
};
$scoreOf = function (string $cardID, array $disabled = []) use ($botCtx) {
    SWUBotSetDisabledFeatures($disabled);
    $ctx = $botCtx('normal');
    $s = null;
    foreach ($ctx['actions'] as $i => $a) if (strval($a['cardID']) === $cardID) { $s = SWUBotScoreAction($ctx, $a, $i); break; }
    SWUBotSetDisabledFeatures([]);
    return $s;
};

// ── Registry: shipped on the reports (like p7/p9/p10), switchable, and '@no-p14' is the stack before it ──────────
$check(in_array('upgradepicks', SWUBotFeatureList(), true), 'upgradepicks is shipped behaviour (defaults on)');
$check(SWUBotVariantDisabled('no-upgradepicks') === ['upgradepicks'], 'upgradepicks is switchable');
$check(SWUBotFeatureGroups()['p14'] === ['upgradepicks'], 'group p14 is the stack before it');

// ── A) REPORT 2 — SEC_163 Outer Rim Constable, "When Played: You may defeat an upgrade" ─────────────────────────
// Both sides hold a Shield token, so there IS a correct answer and a wrong one. The enemy's is the only pick that
// is not self-harm.
$buildConstable = function (bool $enemyShield) use ($build) {
    $build(function ($b) use ($enemyShield) {
        $b->MyLeader('SOR_014', false, false, true);
        $b->FillResourcesForPlayer(1, 'SOR_095', 6);
        $b->WithCardInHandForPlayer(1, 'SEC_163');
        $b->WithGroundUnitForPlayer(1, 'SOR_095', true, 0);
        $b->WithUpgradesOnGroundUnitForPlayer(1, 0, [GameStateBuilder::Upgrade('SOR_T02', 1)]);   // MY Shield
        $b->WithGroundUnitForPlayer(2, 'SOR_046', true, 0);
        if ($enemyShield) $b->WithUpgradesOnGroundUnitForPlayer(2, 0, [GameStateBuilder::Upgrade('SOR_T02', 2)]);
    });
};

$buildConstable(true);
$act(1, 10002, 'myHand-0!FSM!');                     // play the Constable → "Defeat_an_upgrade"
$ctxA = $botCtx('normal');
$check($ctxA['tooltip'] === 'Defeat_an_upgrade', "A fixture: the upgrade prompt is up; got '{$ctxA['tooltip']}'");
$check($ctxA['param'] === 'myGroundArena-0.u0&theirGroundArena-0.u0',
    "A fixture: both shields are candidates, mine FIRST (that ordering is the bug); got '{$ctxA['param']}'");
$mine = 'myGroundArena-0.u0'; $theirs = 'theirGroundArena-0.u0';
$sMine = $scoreOf($mine); $sTheirs = $scoreOf($theirs);
$check($sMine !== null && $sMine < 0, "A: defeating MY OWN Shield scores below zero; got " . json_encode($sMine));
$check($sTheirs !== null && $sTheirs > 0, "A: defeating the ENEMY Shield scores above zero; got " . json_encode($sTheirs));
$check($stack() === $theirs, 'A: the stack defeats the ENEMY Shield');
// The reported mistake, still reproducible with the feature off — so A cannot pass vacuously.
$check($stack('normal', 1, 'no-upgradepicks') === $mine, 'A @no-upgradepicks: the stack defeats its OWN Shield (the report)');

// ── B) The same card with ONLY my own Shield on the board — a "may", so it must be DECLINED ─────────────────────
$buildConstable(false);
$act(1, 10002, 'myHand-0!FSM!');
$ctxB = $botCtx('normal');
$check($ctxB['param'] === 'myGroundArena-0.u0', "B fixture: my Shield is the only candidate; got '{$ctxB['param']}'");
$check(in_array('PASS', array_map(fn($a) => strval($a['cardID']), $ctxB['actions']), true), 'B fixture: declining is offered');
$check($stack() === 'PASS', 'B: with nothing but its own Shield to hit, the stack declines');
$check($stack('normal', 1, 'no-upgradepicks') === 'myGroundArena-0.u0', 'B @no-upgradepicks: it blows up its own Shield');

// ── C) REPORT 1 — LAW_019 Alliance Outpost, "Epic Action [defeat a friendly token]" ─────────────────────────────
// The COST is a friendly token, and the bot holds two: a Shield (worth 1.5 by the engine's own unit-value
// accounting, +1 upgrade +0.5 shield) and an Experience token (worth 1.0). It must pay the CHEAPER one.
// ⚠ The Shield is on ground index 0 so the WRONG answer is also the FIRST candidate — without that, enumeration
// order would give the right answer by luck and this section would measure nothing.
$build(function ($b) {
    $b->MyBase('LAW_019');
    $b->MyLeader('SOR_014', false, false, true);
    $b->FillResourcesForPlayer(1, 'SOR_095', 6);
    $b->WithGroundUnitForPlayer(1, 'LOF_061', true, 0);                                           // Secretive Sage
    $b->WithUpgradesOnGroundUnitForPlayer(1, 0, [GameStateBuilder::Upgrade('SOR_T02', 1)]);        // its Shield
    $b->WithGroundUnitForPlayer(1, 'SOR_095', true, 0);
    $b->WithUpgradesOnGroundUnitForPlayer(1, 1, [GameStateBuilder::Upgrade('SOR_T01', 1)]);        // an Experience token
    $b->WithGroundUnitForPlayer(2, 'SOR_046', true, 0);
});
$act(1, 10001, 'myBase-0!CustomInput!EpicAction');
$ctxC = $botCtx('normal');
$check($ctxC['tooltip'] === 'Defeat_a_friendly_token_(cost)', "C fixture: the cost prompt is up; got '{$ctxC['tooltip']}'");
$check($ctxC['param'] === 'myGroundArena-0.u0&myGroundArena-1.u0',
    "C fixture: Shield first, Experience second; got '{$ctxC['param']}'");
$shieldCost = $scoreOf('myGroundArena-0.u0'); $expCost = $scoreOf('myGroundArena-1.u0');
$check($shieldCost !== null && $expCost !== null && $expCost > $shieldCost,
    'C: paying the Experience token outranks paying the Shield; got ' . json_encode([$expCost, $shieldCost]));
$check($stack() === 'myGroundArena-1.u0', 'C: the stack pays the cheaper Experience token');
$check($stack('normal', 1, 'no-upgradepicks') === 'myGroundArena-0.u0',
    'C @no-upgradepicks: it pays the Shield on the Sage (the report)');

bot_test_finish();

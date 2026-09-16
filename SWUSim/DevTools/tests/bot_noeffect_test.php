<?php
// Feature 'noeffect' — an event the ENGINE says would do nothing is held. The dud gate ('dudgate') only checks
// events that act on the enemy (removal, damage, exhaust, bounce, burn…), so a self-targeting event slipped through:
// the Armorer fixture (normal_armorer_nabat, 0–8 in the 2026-09-15 smoke) opened round 1 with ASH_090 Reforge
// ("Defeat an upgrade on a friendly unit. If you do, …") and no units — "P1's Reforge had no effect" — instead of
// playing one of three affordable units. The engine logs "had no effect" only when the WHOLE gamestate is unchanged
// by the ability (SWUSim/Custom/GameLogEvents.php), so the event is played in the lookahead and that line is read.
// Events only: an upgrade's When Played can be a no-op while the upgrade itself still matters (ASH_228 Preparation
// on an exhausted unit).
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php -d apc.enable_cli=1 -d xdebug.mode=off SWUSim/DevTools/tests/bot_noeffect_test.php
require __DIR__ . '/fixtures/bot_test_bootstrap.php';
include_once './SWUSim/BotLegalActions.php';
include_once './SWUSim/BotHeuristic.php';
$ids = fn($acts) => array_map(fn($a) => strval($a['cardID']), $acts);
$stack = function (string $style, int $seat = 1, string $variant = '') use (&$gameName) {
    SWUBotResetCoverage(); $legal = SWUBotLegalActions($gameName, $seat);
    $p = SWUBotHeuristicChoose($style, (array)$legal['actions'], $legal, $variant);
    return $p === null ? null : strval($p['cardID']);
};
$playScore = function (string $style, string $cardID) use ($botCtx) {
    SWUBotSetDisabledFeatures([]);
    $ctx = $botCtx($style);
    foreach ($ctx['actions'] as $i => $a) if (strval($a['cardID']) === $cardID) return SWUBotScoreAction($ctx, $a, $i);
    return null;
};
$check(SWUBotVariantDisabled('no-noeffect') === ['noeffect'] && in_array('noeffect', (array)SWUBotVariantDisabled('no-p3'), true),
    'noeffect is switchable, alone and in the part-3 group');

// A — Reforge with no friendly unit: the engine would log "had no effect". Held; @no-noeffect plays it.
// (the leader's Epic Action is spent, so its deploy is not the better pick — Reforge is the old stack's top choice)
$reforgeAlone = function () use ($build) { $build(function ($b) { $b->MyLeader('SOR_014', false, false, true); $b->FillResourcesForPlayer(1, 'SOR_095', 6);
    $b->WithCardInHandForPlayer(1, 'ASH_090'); }); };
$reforgeAlone();
$check(in_array('myHand-0!FSM!', $ids($botCtx('normal')['actions']), true), 'fixture: Reforge is on offer');
$check($playScore('normal', 'myHand-0!FSM!') === -0.5, 'Reforge with no friendly upgrade to defeat is held (-0.5)');
$check($stack('normal') !== 'myHand-0!FSM!', 'the stack does not play it');
$reforgeAlone();
$check($stack('normal', 1, 'no-noeffect') === 'myHand-0!FSM!', '@no-noeffect: the old pick — Reforge into nothing');

// B — the same Reforge with a Shield or an Experience token on my Marine (owner: players Reforge tokens to gamble for
// Mastery / The Darksaber): it defeats the token and searches. Not held.
foreach (['SOR_T02' => 'Shield', 'SOR_T01' => 'Experience'] as $tok => $name) {
    $build(function ($b) use ($tok) { $b->MyLeader('SOR_014', false); $b->FillResourcesForPlayer(1, 'SOR_095', 6); $b->WithCardInHandForPlayer(1, 'ASH_090');
        $b->WithGroundUnitForPlayer(1, 'SOR_095', true); $b->WithUpgradesOnGroundUnitForPlayer(1, 0, [GameStateBuilder::Upgrade($tok, 1)]); });
    $check($playScore('normal', 'myHand-0!FSM!') !== -0.5, "Reforge with a $name token on a friendly unit is not held");
}

// C — an enemy-facing event keeps its own gate: Vanquish on a Wampa is still worth playing.
$build(function ($b) { $b->MyLeader('SOR_014', false); $b->FillResourcesForPlayer(1, 'SOR_095', 7); $b->WithCardInHandForPlayer(1, 'SOR_078'); $b->WithGroundUnitForPlayer(2, 'SOR_164', true); });
$check($playScore('normal', 'myHand-0!FSM!') > 0.0, 'Vanquish on a Wampa is still played');

// D — the lookahead leaves the real game untouched: the hand and the log are as they were.
$reforgeAlone();
global $gGameLog;
$logBefore = is_object($gGameLog) ? strval($gGameLog->Value ?? '') : strval($gGameLog);
$playScore('normal', 'myHand-0!FSM!');
$logAfter = is_object($gGameLog) ? strval($gGameLog->Value ?? '') : strval($gGameLog);
$check(count(GetHand(1)) === 1 && $logAfter === $logBefore, 'scoring the play leaves the hand and the game log unchanged');

bot_test_finish();

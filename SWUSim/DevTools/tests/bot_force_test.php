<?php
// Phase 1b part 3, Task 5 — the Force (feature 'force'). Diagnosis 2026-09-14 (Talzin): her Action "[Exhaust, use the
// Force]: Give a unit -1/-1" was used at almost every round's end — 953 uses, 146 kills, 55 on her own units — so she
// began round 3 with the Force in 1 of 400 games; Force cards were valued as if the Force were there; "Use the Force
// to give a unit -3/-3?" was answered YES with no enemy unit, landing on her own.
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php -d apc.enable_cli=1 -d xdebug.mode=off SWUSim/DevTools/tests/bot_force_test.php
require __DIR__ . '/fixtures/bot_test_bootstrap.php';
include_once './SWUSim/BotLegalActions.php';
include_once './SWUSim/BotHeuristic.php';
$ids = fn($acts) => array_map(fn($a) => strval($a['cardID']), $acts);
$stack = function (string $style, int $seat = 1, string $variant = '') use (&$gameName) {
    SWUBotResetCoverage(); $legal = SWUBotLegalActions($gameName, $seat);
    $p = SWUBotHeuristicChoose($style, (array)$legal['actions'], $legal, $variant);
    return [$p === null ? null : strval($p['cardID']), array_keys($GLOBALS['SWUBotCoverage'][$seat] ?? [])];
};
$kind = function (array $acts, string $k) { foreach ($acts as $a) if (SWUBotActionKind($a) === $k) return strval($a['cardID']); return null; };
$check(SWUBotVariantDisabled('no-force') === ['force'], 'force is switchable');
// "Use the Force … If you do" gates the effect; "the Force is with you" CREATES the token; a "Choose one" with a
// Force-free mode, "If you do either" and "If you do not" still do something without it.
$check(function_exists('_SWUBotNeedsTheForce') && _SWUBotNeedsTheForce('LOF_035') && _SWUBotNeedsTheForce('LOF_172'), 'the Assassin (you may) and Sorcerous Blast (must) need the Force');
$check(function_exists('_SWUBotNeedsTheForce') && !_SWUBotNeedsTheForce('LOF_041') && !_SWUBotNeedsTheForce('LOF_079') && !_SWUBotNeedsTheForce('LOF_175')
    && !_SWUBotNeedsTheForce('LOF_218') && !_SWUBotNeedsTheForce('SOR_095'),
    'Drain Essence (creates it), Shatterpoint (a Force-free mode), Do or Do Not, Impossible Escape and a Marine do not');

// (a) Talzin (undeployed, with the Force), an Assassin in hand (another use for the Force, unaffordable now), and a
// Wampa (5 HP): the -1/-1 kills nothing → the Action waits.
$talzin = function (callable $enemy) {
    return function ($b) use ($enemy) { $b->MyLeader('LOF_002'); $b->WithForceForPlayer(1); $b->WithCardInHandForPlayer(1, 'LOF_035'); $enemy($b); };
};
$build($talzin(fn($b) => $b->WithGroundUnitForPlayer(2, 'SOR_164', true)));
$ability = $kind($botCtx('normal')['actions'], 'leader-ability');
$check($ability !== null, 'fixture: Talzin\'s Action is on offer');
$check($stack('normal')[0] !== $ability, 'the Force is kept when the -1/-1 kills nothing and another card needs it');
$check($stack('normal', 1, 'no-force')[0] === $ability, '@no-force: the Action was used anyway');
$build($talzin(fn($b) => $b->WithSpaceUnitForPlayer(2, 'SOR_225', true)));   // TIE/ln, 1 HP: the -1/-1 kills
$check($stack('normal')[0] === $kind($botCtx('normal')['actions'], 'leader-ability'), 'the Action is used when it kills (a 1-HP TIE)');

// (b) A Force card without the Force loses its effect value.
$assassin = function (bool $force) {
    return function ($b) use ($force) { $b->MyLeader('SOR_014', false); $b->FillResourcesForPlayer(1, 'SOR_095', 8); if ($force) $b->WithForceForPlayer(1);
        $b->WithCardInHandForPlayer(1, 'LOF_035'); $b->WithGroundUnitForPlayer(2, 'SOR_164', true); };
};
$score = function () use ($botCtx) { $c = $botCtx('normal'); foreach ($c['actions'] as $i => $a) if (strval($a['cardID']) === 'myHand-0!FSM!') return SWUBotScoreAction($c, $a, $i); return null; };
$build($assassin(true)); $with = $score();
$build($assassin(false)); $without = $score();
$check($with !== null && $without !== null && $without < $with, 'the Assassin is worth less without the Force (its -3/-3 cannot happen); got ' . json_encode([$with, $without]));
// …and a Force event cannot improve an attack without it, so the attack-first guide applies (Sorcerous Blast).
$blast = function (bool $force) {
    return function ($b) use ($force) { $b->MyLeader('SOR_014', false); $b->FillResourcesForPlayer(1, 'SOR_095', 8); if ($force) $b->WithForceForPlayer(1);
        $b->WithCardInHandForPlayer(1, 'LOF_172'); $b->WithGroundUnitForPlayer(1, 'SOR_095', true); $b->WithGroundUnitForPlayer(2, 'SOR_164', true); };
};
$play = ['cardID' => 'myHand-0!FSM!', 'mode' => 10002];
$build($blast(true));  $check(_SWUBotPlayCanImproveAttack(1, $play) === true, 'with the Force, Sorcerous Blast can improve an attack');
$build($blast(false)); $check(_SWUBotPlayCanImproveAttack(1, $play) === false, 'without it, it cannot');
SWUBotSetDisabledFeatures(['force']); $check(_SWUBotPlayCanImproveAttack(1, $play) === true, '@no-force: it could, as before'); SWUBotSetDisabledFeatures([]);

// (c) "Use the Force to give a unit -3/-3?" with no enemy unit → NO.
$build(function ($b) { $b->MyLeader('SOR_014', false); $b->FillResourcesForPlayer(1, 'SOR_095', 8); $b->WithForceForPlayer(1); $b->WithCardInHandForPlayer(1, 'LOF_035'); });
$act(1, 10002, 'myHand-0!FSM!');
$check($botCtx('normal')['tooltip'] === 'Use_the_Force_to_give_a_unit_-3/-3?', 'fixture: the Assassin asks to use the Force; got ' . $botCtx('normal')['tooltip']);
$check($stack('normal')[0] === 'NO', 'no enemy unit: the Force is not spent on my own units');
$check($stack('normal', 1, 'no-force')[0] === 'YES', '@no-force: YES, as before');

bot_test_finish();

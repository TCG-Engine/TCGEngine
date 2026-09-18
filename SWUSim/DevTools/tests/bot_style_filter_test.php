<?php
// Phase 1a Task 5 — layer 1 (the style filter), the shared action helpers, and the weight tables
// (SWUSim/Custom/BotStyles.php). Every attack-target board raises the REAL prompt via $raiseAttack and
// takes its context from SWUBotLegalActions — never a hand-crafted decision.
// Fixtures: LOF_084 Knight of Ren 4/4 · SOR_095 Battlefield Marine 3/3 · SOR_046 Consular Security Force
// 3/7 · SOR_164 Wampa 4/5 Overwhelm. Bases 30 HP (SOR_024 / SOR_020).
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php -d apc.enable_cli=1 -d xdebug.mode=off SWUSim/DevTools/tests/bot_style_filter_test.php
require __DIR__ . '/fixtures/bot_test_bootstrap.php';
include_once './SWUSim/BotLegalActions.php';
include_once './SWUSim/Custom/BotEvaluator.php';
include_once './SWUSim/Custom/BotStyles.php';
$ids = fn($acts) => array_map(fn($a) => strval($a['cardID']), $acts);
$sorted = function ($a) { sort($a); return $a; };

// ── Aggro vs Control on the same prompt ──────────────────────────────────────────────────────────
$build(function ($b) { $b->WithGroundUnitForPlayer(1, 'LOF_084', true); $b->WithGroundUnitForPlayer(2, 'SOR_095', true); });
$raiseAttack(1, 'myGroundArena-0');
$check($sorted($ids($botCtx('aggro')['actions'])) === ['theirBase-0', 'theirGroundArena-0'], 'fixture: two candidates, base and the Marine');
$check($ids(SWUBotStyleFilter($botCtx('aggro'))) === ['theirBase-0'], 'Aggro: base only');
// The base is now a CANDIDATE for control too (spec: "The attack filter stops excluding the base"); the weights
// decide. The old expectation survives behind the feature switch.
$check($sorted($ids(SWUBotStyleFilter($botCtx('control')))) === ['theirBase-0', 'theirGroundArena-0'],
    'Control: the base is a candidate alongside the unit');
SWUBotSetDisabledFeatures(['baserace']);
$check($sorted($ids(SWUBotStyleFilter($botCtx('control')))) === ['theirBase-0', 'theirGroundArena-0'],
    'Control: the base stays a candidate even with the racing shift off — the filter no longer excludes it');
SWUBotSetDisabledFeatures([]);
$check(SWUBotAttackerMz($botCtx('aggro')) === 'myGroundArena-0', 'the attacker is read from the following SWUResolveAttack param');

// ── Aggro keeps an Overwhelm kill alongside the base ─────────────────────────────────────────────
$build(function ($b) { $b->WithGroundUnitForPlayer(1, 'SOR_164', true); $b->WithGroundUnitForPlayer(2, 'SOR_095', true, 2); });
$raiseAttack(1, 'myGroundArena-0');
$check($sorted($ids(SWUBotStyleFilter($botCtx('aggro')))) === ['theirBase-0', 'theirGroundArena-0'],
    'Aggro: an Overwhelm attack that kills counts as a base attack');

// ── Normal: racing → Aggro's rule; not racing → favourable trades only, else unchanged ───────────
$build(function ($b) {
    $b->WithGroundUnitForPlayer(1, 'LOF_084', true); $b->TheirBase('SOR_020', 22);   // 8 left / 4 → 2 rounds
    $b->WithGroundUnitForPlayer(2, 'SOR_095', true);                                  // their clock 30/3 → 10
});
$raiseAttack(1, 'myGroundArena-0');
$check(SWUBotIsRacing(1, 2) === true, 'fixture: seat 1 is racing');
$check($ids(SWUBotStyleFilter($botCtx('normal'))) === ['theirBase-0'], 'Normal racing: base only (Aggro\'s rule)');

$build(function ($b) { $b->WithGroundUnitForPlayer(1, 'LOF_084', true); $b->WithGroundUnitForPlayer(2, 'SOR_095', true); });
$raiseAttack(1, 'myGroundArena-0');
$check(SWUBotIsRacing(1, 2) === false, 'fixture: seat 1 is not racing (30 / 4 → 8 rounds)');
$check($sorted($ids(SWUBotStyleFilter($botCtx('normal')))) === ['theirBase-0', 'theirGroundArena-0'],
    'Normal not racing: the favourable trade, and the base is always a candidate');

$build(function ($b) { $b->WithGroundUnitForPlayer(1, 'SOR_095', true); $b->WithGroundUnitForPlayer(2, 'SOR_046', true); });
$raiseAttack(1, 'myGroundArena-0');
$check($ids(SWUBotStyleFilter($botCtx('normal'))) === ['theirBase-0'],
    'Normal not racing, no favourable trade (3/3 into 3/7 bounces): the base only — it no longer falls back to offering the pointless bounce');

// A trade is favourable only into something that COST more: Marine (2) into a damaged Knight of Ren (3)
// trades up and is kept; Marine into Marine is an even trade and leaves the choice open.
$build(function ($b) { $b->WithGroundUnitForPlayer(1, 'SOR_095', true); $b->WithGroundUnitForPlayer(2, 'LOF_084', true, 1); });
$raiseAttack(1, 'myGroundArena-0');
$check($sorted($ids(SWUBotStyleFilter($botCtx('normal')))) === ['theirBase-0', 'theirGroundArena-0'],
    'Normal: a trade into a costlier unit is favourable, alongside the base');
$build(function ($b) { $b->WithGroundUnitForPlayer(1, 'SOR_095', true); $b->WithGroundUnitForPlayer(2, 'SOR_095', true); });
$raiseAttack(1, 'myGroundArena-0');
$check($ids(SWUBotStyleFilter($botCtx('normal'))) === ['theirBase-0'],
    'Normal: an even-cost trade is not favourable → the base only');

// ── A non-attack-target decision, and free play, are left alone ──────────────────────────────────
$build(function ($b) { $b->WithGroundUnitForPlayer(1, 'SOR_095', true); $b->WithGroundUnitForPlayer(2, 'LOF_084', true); });
$free = $botCtx('control');
$check($free['kind'] === 'free-play' && SWUBotStyleFilter($free) === $free['actions'], 'free play: unchanged');

// ── Action helpers ───────────────────────────────────────────────────────────────────────────────
$kinds = [];
foreach ($free['actions'] as $a) $kinds[strval($a['cardID'])] = SWUBotActionKind($a);
$check(($kinds['myGroundArena-0!FSM!'] ?? '') === 'attack', 'kind: attack');
$check(($kinds['InitiativeCounter-0!CustomInput!TakeInitiative'] ?? '') === 'initiative', 'kind: initiative');
$check(($kinds['myHealth-0!CustomInput!Pass'] ?? '') === 'pass', 'kind: pass');
$check(SWUBotActionKind(['mode' => 10002, 'cardID' => 'myHand-3!FSM!']) === 'play', 'kind: play');
$check(SWUBotActionKind(['mode' => 10001, 'cardID' => 'myLeader-0!CustomInput!DeployLeader:Unit']) === 'deploy', 'kind: deploy');
$check(SWUBotActionKind(['mode' => 10001, 'cardID' => 'myLeader-0!CustomInput!LeaderAbility']) === 'leader-ability', 'kind: leader-ability');
$check(SWUBotActionKind(['mode' => 10001, 'cardID' => 'myBase-0!CustomInput!EpicAction']) === 'base-epic', 'kind: base-epic');
$check(SWUBotActionKind(['mode' => 10001, 'cardID' => 'myGroundArena-2!CustomInput!Activate']) === 'unit-action', 'kind: unit-action');
$check(SWUBotActionKind(['mode' => 100, 'cardID' => 'theirBase-0']) === 'answer', 'kind: answer');
$check(SWUBotActionMz(['mode' => 10002, 'cardID' => 'myHand-3!FSM!']) === 'myHand-3', 'mz of a free-play action');
$check(SWUBotSelectionCount(['cardID' => 'PASS']) === 0 && SWUBotSelectionCount(['cardID' => 'a-1&b-2']) === 2,
    'selection count: PASS = 0, two picks = 2');

// Free attacks: the Marine (3/3) against a 4/4 — Aggro may hit the base (free); Control may only attack
// the 4/4 and would die doing it (not free).
$check($ids(SWUBotFreeAttacks($botCtx('aggro'))) === ['myGroundArena-0!FSM!'], 'Aggro: the base makes the attack free');
// ⚠ A control unit that could only lose a trade now has a free attack, because the base is never a losing trade.
// This feeds the attackFirst guide at weight 6.0 — a larger lever than any target value (spec, "Consequence to expect").
$check($ids(SWUBotFreeAttacks($botCtx('control'))) === ['myGroundArena-0!FSM!'],
    'Control: the base makes the attack free');

// ── Weights: Normal switches to Aggro's column while racing ──────────────────────────────────────
$check(SWUBotWeights('softaggro', 1)['base'] === 0.6 && SWUBotWeights('hardcontrol', 1)['wipe'] === 1.8, 'weight table columns');
$build(function ($b) {
    $b->WithGroundUnitForPlayer(1, 'LOF_084', true); $b->TheirBase('SOR_020', 22);
    $b->WithGroundUnitForPlayer(2, 'SOR_095', true);
});
$check(SWUBotWeights('hardcontrol', 1)['kill'] === 0.90,
    'hard control racing shifts exactly 2 ranks, to midrange\'s kill 0.90 (a shift of 1 would give 1.30, a shift of 3 would give 0.60)');
$build(function ($b) { $b->WithGroundUnitForPlayer(1, 'LOF_084', true); });
$check(SWUBotWeights('normal', 1)['kill'] === 0.90, 'Normal not racing → its own weights (kill 0.90)');

// ── The base is a candidate for every archetype; only RULES remove it ─────────────────────────────
$build(function ($b) { $b->WithGroundUnitForPlayer(1, 'LOF_084', true); $b->WithGroundUnitForPlayer(2, 'SOR_095', true); });
$raiseAttack(1, 'myGroundArena-0');
foreach (['hyperaggro', 'softaggro', 'midrange', 'softcontrol', 'hardcontrol'] as $s) {
    $check(in_array('theirBase-0', $ids(SWUBotStyleFilter($botCtx($s))), true), "$s: the base is a candidate");
}
// Sentinel is a RULE, not a preference: it makes the base an illegal target for everyone.
// NOTE: ASH_079 Koska Reeves' Sentinel is CONDITIONAL ("While you control a token unit") — with no token
// she never grants it, so she does not exercise this path. And a single active Sentinel is the arena's only
// legal target, which the engine auto-resolves without ever raising Choose_an_attack_target at all (verified
// empirically), so this rule can only be observed at a live decision with 2+ Sentinels forcing a real choice.
// SOR_063 Cloud City Wing Guard carries unconditional Sentinel, so two of them do that.
$build(function ($b) {
    $b->WithGroundUnitForPlayer(1, 'LOF_084', true);
    $b->WithGroundUnitForPlayer(2, 'SOR_063', true);
    $b->WithGroundUnitForPlayer(2, 'SOR_063', true);
});
$raiseAttack(1, 'myGroundArena-0');
foreach (['hyperaggro', 'hardcontrol'] as $s) {
    $check($sorted($ids(SWUBotStyleFilter($botCtx($s)))) === ['theirGroundArena-0', 'theirGroundArena-1'],
        "$s: Sentinel (SOR_063 Cloud City Wing Guard) still forces a unit, never the base — the filter keeps enforcing rules");
}

bot_test_finish();

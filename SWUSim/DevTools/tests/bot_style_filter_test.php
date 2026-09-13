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
$check($ids(SWUBotStyleFilter($botCtx('control'))) === ['theirGroundArena-0'], 'Control: the unit only');
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
$check($ids(SWUBotStyleFilter($botCtx('normal'))) === ['theirGroundArena-0'], 'Normal not racing: the favourable trade only');

$build(function ($b) { $b->WithGroundUnitForPlayer(1, 'SOR_095', true); $b->WithGroundUnitForPlayer(2, 'SOR_046', true); });
$raiseAttack(1, 'myGroundArena-0');
$check($sorted($ids(SWUBotStyleFilter($botCtx('normal')))) === ['theirBase-0', 'theirGroundArena-0'],
    'Normal not racing, no favourable trade (3/3 into 3/7 bounces): unchanged');

// A trade is favourable only into something that COST more: Marine (2) into a damaged Knight of Ren (3)
// trades up and is kept; Marine into Marine is an even trade and leaves the choice open.
$build(function ($b) { $b->WithGroundUnitForPlayer(1, 'SOR_095', true); $b->WithGroundUnitForPlayer(2, 'LOF_084', true, 1); });
$raiseAttack(1, 'myGroundArena-0');
$check($ids(SWUBotStyleFilter($botCtx('normal'))) === ['theirGroundArena-0'], 'Normal: a trade into a costlier unit is favourable');
$build(function ($b) { $b->WithGroundUnitForPlayer(1, 'SOR_095', true); $b->WithGroundUnitForPlayer(2, 'SOR_095', true); });
$raiseAttack(1, 'myGroundArena-0');
$check($sorted($ids(SWUBotStyleFilter($botCtx('normal')))) === ['theirBase-0', 'theirGroundArena-0'],
    'Normal: an even-cost trade is not favourable → unchanged');

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
$check(SWUBotFreeAttacks($botCtx('control')) === [], 'Control: its only allowed target is a losing trade → no free attack');

// ── Weights: Normal switches to Aggro's column while racing ──────────────────────────────────────
$check(SWUBotWeights('aggro', 1)['base'] === 1.0 && SWUBotWeights('control', 1)['wipe'] === 1.5, 'weight table columns');
$build(function ($b) {
    $b->WithGroundUnitForPlayer(1, 'LOF_084', true); $b->TheirBase('SOR_020', 22);
    $b->WithGroundUnitForPlayer(2, 'SOR_095', true);
});
$check(SWUBotWeights('normal', 1)['base'] === 1.0, 'Normal racing → Aggro weights');
$build(function ($b) { $b->WithGroundUnitForPlayer(1, 'LOF_084', true); });
$check(SWUBotWeights('normal', 1)['base'] === 0.6, 'Normal not racing → its own weights');

bot_test_finish();

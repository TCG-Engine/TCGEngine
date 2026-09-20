<?php
// PROPOSAL 'initiative' — rule 'initiative-for-answer' (SWUSim/Custom/BotRules.php). Owner ruling 2026-09-18
// (Q16 / 5.2): taking the initiative can be worth MORE than a card play or an attack when it lets control remove a
// threat BEFORE it swings. Both owner scenarios are encoded, plus one abstain per condition.
// Fixtures (dictionary-checked): LAW_039 Latts Razzi 2/1 (3; When Played: Shield or Experience, then damage equal
// to her power to an enemy ground unit) · JTL_133 Allegiant General Pryde 2/3 (2) · SEC_080 Imperial Dark Trooper
// 3/3 (2, vanilla) · JTL_043 No Glory, Only Results (5; take control of a non-leader unit, then defeat it) ·
// SOR_164 Wampa 4/5 (4) · SOR_095 Battlefield Marine 3/3 (2) · SOR_063 Wing Guard (Sentinel).
// Play costs are READ from the engine (aspect penalties), never hardcoded.
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php -d apc.enable_cli=1 -d xdebug.mode=off SWUSim/DevTools/tests/bot_initiative_test.php
require __DIR__ . '/fixtures/bot_test_bootstrap.php';
include_once './SWUSim/BotLegalActions.php';
include_once './SWUSim/Custom/BotLookahead.php';
include_once './SWUSim/BotHeuristic.php';

const INIT = 'InitiativeCounter-0!CustomInput!TakeInitiative';
$rules = SWUBotRulesAfterFilter();
$check(isset($rules['initiative-for-answer']) && SWUBotVariantDisabled('try-initiative') === ['try:initiative'],
    'the rule is registered and "@try-initiative" switches it on');

$rule = function (string $style, bool $on = true) use ($rules, $botCtx) {
    SWUBotSetDisabledFeatures($on ? ['try:initiative'] : []);
    $p = ($rules['initiative-for-answer'])($botCtx($style));
    SWUBotSetDisabledFeatures([]);
    return $p === null ? null : strval($p['cardID']);
};
$stack = function (string $style, string $variant) use (&$gameName) {
    $legal = SWUBotLegalActions($gameName, 1);
    $p = SWUBotHeuristicChoose($style, (array)$legal['actions'], $legal, $variant);
    return $p === null ? null : strval($p['cardID']);
};
// Seat 1's play cost for $cid (aspect penalties included).
$costOf = function (string $cid) use ($build) {
    $build(function ($b) use ($cid) { $b->WithCardInHandForPlayer(1, $cid); });
    return intval(SWUComputePlayCost(1, GetHand(1)[0]));
};
$latts = $costOf('LAW_039'); $trooper = $costOf('SEC_080'); $noGlory = $costOf('JTL_043');

// ── Scenario (a): they hold the initiative and just played Pryde; I hold Latts Razzi + a Dark Trooper ─────────────
// One resource short of Latts: the Trooper is castable now, Latts only next round.
$sceneA = function (array $o = []) use ($latts) {
    return function ($b) use ($o, $latts) {
        $b->MyLeader('SOR_014', false, false, true);   // Epic Action used: no deploy on offer
        $b->FillResourcesForPlayer(1, 'SOR_095', $o['res'] ?? $latts - 1);
        $b->WithCardInHandForPlayer(1, 'LAW_039'); $b->WithCardInHandForPlayer(1, 'SEC_080');
        $b->WithGroundUnitForPlayer(2, 'JTL_133', false);                      // Pryde, just played
        if (!empty($o['sentinel'])) $b->WithGroundUnitForPlayer(1, 'SOR_063', false);
        $b->WithInitiativePlayerBeing($o['init'] ?? 2);
    };
};
$build($sceneA());
$check(strval(GetInitiativeCounter()) === 'P2_UNCLAIMED', 'fixture (a): seat 2 holds the initiative, unclaimed');
$check($trooper <= $latts - 1 && in_array(INIT, array_map(fn($a) => strval($a['cardID']), $botCtx('control')['actions']), true),
    "fixture (a): the Trooper ($trooper) is castable, Latts ($latts) is not, the initiative is on offer");
$check(SWUBotHandCardKills('LAW_039', SWUBotUnits(2)[0]), 'Latts Razzi kills Pryde (2 + an Experience token vs 3 HP)');
$check($rule('control') === INIT, '(a): take the initiative, then Latts Razzi their Pryde first thing next round');
$check($rule('control', false) === null, '(a): inert without "@try-initiative"');
$check($rule('aggro') === null, '(a): the aggro wing never does it');
$check($stack('control', 'try-initiative') === INIT, '(a): the whole stack takes the initiative with the proposal on');
$check($stack('control', '') !== INIT, '(a): …and does not without it (the shipped stack is unchanged)');

$build($sceneA(['res' => $latts]));
$check($rule('control') === null, '(a) abstains when Latts is castable NOW — the fallback just plays her');
$build($sceneA(['init' => 1]));
$check($rule('control') === null, '(a) abstains when I already hold the initiative');
$build($sceneA(['sentinel' => true]));
$check($rule('control') === null, '(a) abstains when my Sentinel already guards the arena (owner caveat)');

// ── Scenario (b): resources spent, one ready attacker, their fresh threat, a removal castable next round ─────────
// $mine attacks for 3 (Marine); their Wampa swings for 4 → prevent 4 > gain 3. Swapped, the attack is worth more.
$sceneB = function (string $mine, string $theirs) use ($noGlory) {
    return function ($b) use ($mine, $theirs, $noGlory) {
        $b->MyLeader('SOR_014', false, false, true);   // Epic Action used: no deploy on offer
        $b->FillResourcesForPlayer(1, 'SOR_095', $noGlory - 1, false);        // all spent
        $b->WithCardInHandForPlayer(1, 'JTL_043');
        $b->WithGroundUnitForPlayer(1, $mine, true);
        $b->WithGroundUnitForPlayer(2, $theirs, false);
        $b->WithInitiativePlayerBeing(2);
    };
};
$build($sceneB('SOR_095', 'SOR_164'));
$kinds = array_map('SWUBotActionKind', $botCtx('control')['actions']); sort($kinds);
$check($kinds === ['attack', 'initiative', 'pass'], 'fixture (b): only attack / pass / initiative — rule 8 territory');
$check($rule('control') === INIT, '(b): take the initiative rather than swing for 3 into a 4-power threat');
$check($stack('control', 'try-initiative') === INIT, '(b): the whole stack takes it — the rule runs ahead of rule 8');
$check($stack('control', '') === 'myGroundArena-0!FSM!', '(b): without the proposal rule 8 makes the free attack');
$build($sceneB('SOR_164', 'SOR_095'));
$check($rule('control') === null, '(b) abstains when my swing (4) is worth more than the threat it prevents (3)');

bot_test_finish();

<?php
// Owner ruling 2026-10-06: "weakness tokens are considered 'bad' upgrades or 'downgrades'. so you always want to put it on
// an enemy unit. only if there are no enemy units would you pass on this opportunity to shrink". Enforced as a hard
// constraint on the bot's legal answers (_SWUBotWeaknessEnemyOnly, BotLegalActions.php), so no layer — a rule, a lookahead
// plan, the learned layer — can give a Weakness to the bot's own unit. Game 1647080: Hemlock's On Attack went to the bot's
// own Anakin through the planned-answer rule.
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php -d apc.enable_cli=1 -d xdebug.mode=off SWUSim/DevTools/tests/bot_weaknessenemy_test.php
require __DIR__ . '/fixtures/bot_test_bootstrap.php';
include_once './SWUSim/BotLegalActions.php';
include_once './SWUSim/BotHeuristic.php';
include_once './SWUSim/Custom/BotLookahead.php';

// Deployed Hemlock (seat 1) attacks; its On Attack "You may give a Weakness token to a unit" is left pending.
$hemlockOnAttack = function (bool $enemyUnits) use (&$gameName, $build, $act) {
    $build(function ($b) use ($enemyUnits) {
        $b->MyLeader('HMW_003', true, true, true, 'unit');
        $b->WithGroundUnitForPlayer(1, 'LOF_070', false);
        $b->WithSpaceUnitForPlayer(1, 'ASH_194', false);
        if ($enemyUnits) { $b->WithGroundUnitForPlayer(2, 'SOR_095', true); $b->WithGroundUnitForPlayer(2, 'SEC_082', true); }
    });
    global $playerID; $playerID = 1;
    $hem = null; foreach (GetGroundArena(1) as $i => $u) if ($u->CardID === 'HMW_003') $hem = "myGroundArena-$i";
    $act(1, 10002, "$hem!FSM!");
    $l = SWUBotLegalActions($gameName, 1);
    if (($l['decisionTooltip'] ?? '') === 'Choose_an_attack_target') {      // with enemy units there is a target prompt
        $act(1, 100, 'theirBase-0');
        $l = SWUBotLegalActions($gameName, 1);
    }
    return $l;
};
$ids = fn(array $l) => array_map(fn($a) => strval($a['cardID']), (array)$l['actions']);

// 1. Enemy units on the board: only they are legal — no friendly host, no decline.
$l = $hemlockOnAttack(true);
$check(($l['decisionTooltip'] ?? '') === 'Give_a_Weakness_token_to_a_unit', 'Hemlock On Attack prompt is pending');
$got = $ids($l);
$check(!empty($got) && empty(array_filter($got, fn($c) => !str_starts_with($c, 'theirGroundArena-'))),
    'with enemy units, the answers are enemy units only: ' . implode(' , ', $got));
// The raw prompt DID offer my units and the decline — the filter is what removed them (fixture check).
$raw = BridgeEnumerateLegalActionsLoaded('SWUSim', $gameName);
$rawIds = array_map(fn($a) => strval($a['cardID']), (array)($raw['actions'] ?? []));
$check(in_array('PASS', $rawIds, true) && count(array_filter($rawIds, fn($c) => str_starts_with($c, 'my'))) >= 2,
    'fixture: the engine offered my units and PASS too: ' . implode(' , ', $rawIds));
foreach (['softcontrol', 'midrange', 'hyperaggro'] as $style) {
    $p = SWUBotHeuristicChoose($style, (array)$l['actions'], $l, '');
    $check(str_starts_with(strval($p['cardID'] ?? ''), 'their'), "[$style] the Weakness goes to an enemy: " . strval($p['cardID'] ?? ''));
}

// 2. No enemy units: decline — never shrink my own.
$l = $hemlockOnAttack(false);
$check(($l['decisionTooltip'] ?? '') === 'Give_a_Weakness_token_to_a_unit', 'no enemies: the prompt is still raised (my units are legal targets)');
$check($ids($l) === ['PASS'], 'no enemies: the only answer is to decline: ' . implode(' , ', $ids($l)));

// 3. A non-Weakness "give" prompt is untouched: the filter keys on Weakness only. (Clean board: no queued continuation.)
$build(function ($b) {});
$check(_SWUBotWeaknessEnemyOnly('MZCHOOSE', 'Give_a_Shield_token_to_a_unit',
        [['cardID' => 'myGroundArena-0'], ['cardID' => 'theirGroundArena-0']], 1) === [['cardID' => 'myGroundArena-0'], ['cardID' => 'theirGroundArena-0']],
    'a Shield give keeps every answer');
// 4. A MANDATORY Weakness give with only friendly hosts is left alone (refusing would stall the prompt).
$only = [['cardID' => 'myGroundArena-0'], ['cardID' => 'mySpaceArena-0']];
$check(_SWUBotWeaknessEnemyOnly('MZCHOOSE', 'Give_a_Weakness_token_to_a_unit_without_one', $only, 1) === $only,
    'a mandatory give with no enemy and no decline keeps its answers');

bot_test_finish();

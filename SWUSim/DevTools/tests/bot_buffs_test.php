<?php
// Buffs go on my units, and an Action whose only targets would help the enemy is not used (feature 'buffs').
// FOUND 2026-09-14 in a live Bot Practice game (183227, owner report): the bot "wasted an action using their
// Ahsoka leader ability to buff my unit. no player would actually do this". ASH_009 Ahsoka Tano: "Action
// [Exhaust]: Choose a unit with less power than a friendly unit. It gets +2/+0 for this phase." Two gaps:
//  • the target prompt's continuation (APPLY_PHASE_BUFF) was not a known beneficial one, so the pick fell to
//    first-legal — an enemy unit as readily as my own;
//  • _SWUBotAbilityValue scored any Action that raised a prompt at W['ability'] (0.4, above the initiative)
//    whatever the prompt's targets were — here, only an enemy unit.
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php -d apc.enable_cli=1 -d xdebug.mode=off SWUSim/DevTools/tests/bot_buffs_test.php
require __DIR__ . '/fixtures/bot_test_bootstrap.php';
include_once './SWUSim/BotLegalActions.php';
include_once './SWUSim/BotHeuristic.php';
$ids = fn($acts) => array_map(fn($a) => strval($a['cardID']), $acts);
$stack = function (string $style, int $seat = 1, string $variant = '') use (&$gameName) {
    SWUBotResetCoverage(); $legal = SWUBotLegalActions($gameName, $seat);
    $p = SWUBotHeuristicChoose($style, (array)$legal['actions'], $legal, $variant);
    return [$p === null ? null : strval($p['cardID']), array_keys($GLOBALS['SWUBotCoverage'][$seat] ?? [])];
};
$ability = 'myLeader-0!CustomInput!LeaderAbility';
$check(SWUBotVariantDisabled('no-buffs') === ['buffs'], 'buffs is switchable');

// A) The live shape: my only unit (Marine, 3 power, exhausted) is the "friendly unit"; the only unit with less
// power is the enemy's TIE/ln (2). Using Ahsoka would only buff the enemy.
$build(function ($b) { $b->MyLeader('ASH_009'); $b->WithGroundUnitForPlayer(1, 'SOR_095', false); $b->WithSpaceUnitForPlayer(2, 'SOR_225', true); });
$check(in_array($ability, $ids($botCtx('normal')['actions']), true), 'fixture: Ahsoka\'s Action is on offer');
$check($stack('normal')[0] !== $ability, 'an Action whose only target is an enemy unit (a buff) is not used');
$check($stack('normal', 1, 'no-buffs')[0] === $ability, '@no-buffs: it was used (the reported mistake)');

// B) With a unit of my own to buff (a 0-power Spy token, ready) the Action is worth using …
$build(function ($b) { $b->MyLeader('ASH_009'); $b->WithGroundUnitForPlayer(1, 'SOR_095', false); $b->WithGroundUnitForPlayer(1, 'SEC_T01', true);
    $b->WithSpaceUnitForPlayer(2, 'SOR_225', true); });
$act(1, 10001, $ability);
$got = $ids($botCtx('normal')['actions']);
$check(in_array('myGroundArena-1', $got, true) && in_array('theirSpaceArena-0', $got, true), 'fixture: the prompt offers my Spy and their TIE; got ' . json_encode($got));
// … and the +2/+0 goes on MY unit.
$check($stack('normal')[0] === 'myGroundArena-1', 'the buff goes on my own unit, never the enemy\'s');

// (The prompt lists my units before the enemy's, so first-legal already put the buff on my Spy here; the
// continuation is now a known beneficial one either way, so the pick no longer depends on that order.)

// C) DEPLOYED Ahsoka's On Attack — "You may give a unit with less power than this unit +2/+0 for this phase" — when
// the only unit that qualifies is the ENEMY's. Owner 2026-10-01, from Ninin's online games: a human ending the game
// by attacking with Ahsoka and buffing the opponent's unit is a TAUNT; the bot must never do it. Declined (PASS).
$build(function ($b) { $b->MyLeader('ASH_009', true, true, true, 'unit'); $b->TheirBase('SOR_020');
    $b->WithGroundUnitForPlayer(2, 'SOR_095', false); });   // their exhausted Marine (3 < Ahsoka's 5): attacks go to the base
$attack = null;
foreach ((array)SWUBotLegalActions($gameName, 1)['actions'] as $a) if (SWUBotActionKind($a) === 'attack') { $attack = $a; break; }
$check($attack !== null, 'fixture: deployed Ahsoka can attack');
$prompts = [];
for ($i = 0; $i < 6 && $attack !== null; $i++) {
    $legal = SWUBotLegalActions($gameName, 1);
    if ($i === 0) { $act(1, intval($attack['mode'] ?? 10001), strval($attack['cardID'])); continue; }
    if (($legal['kind'] ?? '') !== 'decision') break;
    $tip = strval($legal['decisionTooltip'] ?? '');
    $pick = SWUBotHeuristicChoose('hyperaggro', (array)$legal['actions'], $legal, '');
    $prompts[] = [$tip, $ids((array)$legal['actions']), strval($pick['cardID'] ?? 'PASS')];
    $act(1, intval($pick['mode'] ?? 10001), strval($pick['cardID'] ?? 'PASS'));
}
$buff = array_values(array_filter($prompts, fn($p) => in_array('theirGroundArena-0', $p[1], true) && !str_contains($p[0], 'attack_target')));
$check(count($buff) === 1, 'fixture: the On Attack buff prompt was offered, with their Marine as a candidate; prompts ' . json_encode($prompts));
$check(!empty($buff) && $buff[0][2] !== 'theirGroundArena-0', 'deployed Ahsoka never buffs the ENEMY unit on attack (got ' . json_encode($buff[0][2] ?? null) . ')');
$check(intval(ObjectCurrentPower(GetUnitsInArena(2, 'Ground')[0])) === 3, 'and their Marine is still 3 power after the attack');

bot_test_finish();

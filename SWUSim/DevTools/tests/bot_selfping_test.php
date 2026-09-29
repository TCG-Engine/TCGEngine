<?php
// A hostile Action whose ONLY legal targets are my own units must not be used (feature 'buffs').
//
// FOUND 2026-09-29 in prod, Bug Report #1100 (game 1402804, Arenabot): "bot pinged their own ship and killed
// it for no reason". Game log lines 68-70:
//   ACTION  P2 used [[JTL_012|Luke Skywalker]]'s Action
//   DAMAGE  P2's Luke Skywalker dealt 1 damage to P2's [[JTL_149|Red Squadron Y-Wing]]
//   DEFEAT  P2's Luke Skywalker defeated P2's Red Squadron Y-Wing
// P1 had NO units at that moment (their last one died in round 3), and JTL_012's Action —
// "Action [Exhaust]: If you attacked with a Fighter unit this phase, deal 1 damage to a unit" — is MANDATORY
// once activated and its target is unqualified, so the only legal targets were the bot's own ships.
//
// ROOT CAUSE: _SWUBotAbilityValue's refusal guard (BotFallback.php) already implements exactly this rule —
// "a hostile effect whose every candidate is mine: don't use it" — but it classifies hostility from the
// SWU_BOT_HOSTILE_CONTINUATIONS constant ALONE, and 'DEAL_UNIT_DAMAGE' was missing from that list. It is the
// most common continuation in the tree (141 sites, vs 102 across all five that WERE listed), so the guard was
// blind to more call sites than it covered. The TARGET PICKER was never blind: its classifier falls back to
// _SWUBotTooltipEffect(), which reads "Deal_1_damage_to_a_unit" as hostile. Only the ACTIVATION decision
// lacked that fallback — the bot knew the effect was hostile once it was choosing a victim, but never
// declined to start.
//
// bot_buffs_test.php covers the mirror image (a BENEFICIAL effect whose every candidate is the ENEMY's) and
// passes; this file is the hostile/all-mine direction it never exercised.
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php -d apc.enable_cli=1 -d xdebug.mode=off SWUSim/DevTools/tests/bot_selfping_test.php
require __DIR__ . '/fixtures/bot_test_bootstrap.php';
include_once './SWUSim/BotLegalActions.php';
include_once './SWUSim/BotHeuristic.php';

$ids = fn($acts) => array_map(fn($a) => strval($a['cardID']), $acts);
$stack = function (string $style, int $seat = 1, string $variant = '') use (&$gameName) {
    SWUBotResetCoverage(); $legal = SWUBotLegalActions($gameName, $seat);
    $p = SWUBotHeuristicChoose($style, (array)$legal['actions'], $legal, $variant);
    return $p === null ? null : strval($p['cardID']);
};
$ability = 'myLeader-0!CustomInput!LeaderAbility';
// JTL_149 Red Squadron Y-Wing — Rebel/Vehicle/FIGHTER, space, 3 HP. The prod board's victim, and a Fighter,
// so attacking with it is what satisfied Luke's own precondition.
$FIGHTER = 'JTL_149';

// The precondition is the SWU_ATTACKED_FIGHTER global effect, set by CombatLogic.php:2401 when a Fighter
// attacks. Set it through that same production function rather than faking a flag.
$attackedWithFighter = function (int $seat) { AddGlobalEffects($seat, 'SWU_ATTACKED_FIGHTER'); };

// ── A. the reported board: the enemy has NO units, so every candidate is mine ────────────────────────
// Two of my own ships, one already at 2 damage (3 HP) so a 1-damage ping KILLS it — the prod outcome.
$build(function ($b) use ($FIGHTER) {
    $b->MyLeader('JTL_012');
    $b->WithSpaceUnitForPlayer(1, $FIGHTER, false, 2);   // 1 HP left — dies to the ping
    $b->WithSpaceUnitForPlayer(1, 'JTL_147', false, 0);  // healthy
});
$attackedWithFighter(1);
$check(GlobalEffectCount(1, 'SWU_ATTACKED_FIGHTER') > 0, 'fixture: the Fighter-attacked precondition is set');
$check(count(SWUBotUnits(2)) === 0, 'fixture: the enemy has NO units — every legal target is mine');
$check(in_array($ability, $ids($botCtx('normal')['actions']), true), "fixture: Luke's Action is on offer");
// THE BUG: with only friendly targets, a mandatory 1-damage ability can do nothing but hurt me.
$check($stack('normal') !== $ability, 'an Action whose only targets are MY units is not used (normal)');
$check($stack('softaggro') !== $ability, 'same refusal on the prod seat\'s style (softaggro)');
$check($stack('normal', 1, 'no-buffs') === $ability, '@no-buffs: it IS used — the reported mistake');

// ── B. control: an enemy unit exists, so the Action is worth using ───────────────────────────────────
// Without this the fix could be "never use Luke", which would be a worse bug than the one reported.
$build(function ($b) use ($FIGHTER) {
    $b->MyLeader('JTL_012');
    $b->WithSpaceUnitForPlayer(1, $FIGHTER, false, 2);
    $b->WithSpaceUnitForPlayer(2, 'JTL_147', true, 0);   // an enemy ship to shoot instead
});
$attackedWithFighter(1);
$check(count(SWUBotUnits(2)) === 1, 'fixture: the enemy has a unit');
$check($stack('normal') === $ability, 'with an enemy target the Action IS used — the fix is not "never use Luke"');

// ── C. and the victim is chosen sanely once the prompt is up ─────────────────────────────────────────
// Section A refuses to START, but a mandatory ability can still be reached (an engine effect, a forced
// activation, or a board where the refusal is outweighed). When it is, the ping must not pick the unit it
// KILLS over one it merely chips.
$build(function ($b) use ($FIGHTER) {
    $b->MyLeader('JTL_012');
    $b->WithSpaceUnitForPlayer(1, $FIGHTER, false, 2);   // mySpaceArena-0, 1 HP left — dies
    $b->WithSpaceUnitForPlayer(1, 'JTL_147', false, 0);  // mySpaceArena-1, healthy — survives a chip
});
$attackedWithFighter(1);
$act(1, 10001, $ability);
$got = $ids($botCtx('normal')['actions']);
$check(in_array('mySpaceArena-0', $got, true) && in_array('mySpaceArena-1', $got, true),
    'fixture: the prompt offers both of my ships; got ' . json_encode($got));
$check($stack('normal') === 'mySpaceArena-1', 'forced to self-damage, it chips the healthy ship rather than killing the hurt one');

// ── D. the carve-out: self-damage that is a declared COST must still be paid ─────────────────────────
// Adding DEAL_UNIT_DAMAGE to SWU_BOT_HOSTILE_CONTINUATIONS makes every friendly-only damage prompt look like
// section A's misfire. SHD_028 Doctor Pershing is a UNIT ACTION — "Action [Exhaust, deal 1 damage to a
// friendly unit]: Draw a card" — so _SWUBotAbilityValue sees it, and its candidates are ALWAYS all-mine by
// design. Without the $declaredFriendly carve-out the bot would never draw with him again: a regression
// strictly worse than the reported bug, and a silent one. The prompts are what separate them —
// "Deal_1_damage_to_a_friendly_unit" vs Luke's unqualified "Deal_1_damage_to_a_unit".
// ⚠ The Marine is EXHAUSTED on purpose. With it ready, attacking the base scores 1.8 against the Action's
// 0.4 and the bot correctly attacks — so the pick could not show whether the Action was REFUSED (-0.5) or
// merely outranked, and the section would have passed under a broken carve-out for the wrong reason.
$pershing = 'myGroundArena-0!CustomInput!Activate';
$build(function ($b) {
    $b->MyLeader('SOR_014', false, false, true);         // a quiet leader: no Action, no deploy on offer
    $b->WithGroundUnitForPlayer(1, 'SHD_028', true);     // Doctor Pershing 0/5, READY — needs to use its Action
    $b->WithGroundUnitForPlayer(1, 'SOR_095', false);    // a Marine to take the 1 damage; exhausted, cannot attack
});
$offered = $ids($botCtx('normal')['actions']);
$check(in_array($pershing, $offered, true), "fixture: Pershing's Action is on offer; got " . json_encode($offered));
$check(count(SWUBotUnits(2)) === 0, 'fixture: the enemy has no units — Pershing is all-mine, as always');
$check(SWUBotScoreAction($botCtx('normal'), ['playerID' => 1, 'mode' => 10001, 'cardID' => $pershing], 0) > 0.0,
    'a DECLARED friendly cost is NOT refused (scores above PASS, not the -0.5 refusal)');
$check($stack('normal') === $pershing, 'and with no attack available the bot still draws with Pershing');

bot_test_finish();

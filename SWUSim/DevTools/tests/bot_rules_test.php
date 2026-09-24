<?php
// Phase 1a Task 8 — layer 2, the confident rules (SWUSim/Custom/BotRules.php). One block per rule: a board
// where it fires (asserting the returned cardID) and one where it must abstain (null). Every decision is a
// REAL prompt raised by the engine; the opening-resources pick is raised by queueing the same
// "ChooseStartingResource" entry CreateGame.php:324 queues (that file cannot be loaded in a test).
// Seat 1 defaults: SOR_014 Sabine Wren (threshold 4) on a green 30-HP base; seat 2: SOR_010 Darth Vader.
// Fixtures (dictionary-checked):
//   SOR_095 Battlefield Marine 3/3 (2) · LOF_084 Knight of Ren 4/4 (3) · SOR_164 Wampa 4/5 (4) ·
//   SOR_046 Consular Security Force 3/7 (4) · SOR_098 Echo Base Defender 4/3 (3) · SOR_145 K-2SO 4/4 (4) ·
//   SOR_120 Academy Training (upgrade, 2) · SOR_115 Agent Kallus 4/4 Ambush · SHD_258 Mandalorian Warrior
//   (may give Experience to another Mandalorian) · SHD_040 / SHD_047 Mandalorian units · SOR_218 Asteroid
//   Sanctuary (exhaust an enemy unit) · LAW_T01 Credit · LAW_191 Arvel Skeen · SEC_122 · LOF_007 Avar Kriss (9)
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php -d apc.enable_cli=1 -d xdebug.mode=off SWUSim/DevTools/tests/bot_rules_test.php
require __DIR__ . '/fixtures/bot_test_bootstrap.php';
include_once './SWUSim/BotLegalActions.php';
include_once './SWUSim/Custom/BotLookahead.php';
include_once './SWUSim/Custom/BotEvaluator.php';
include_once './SWUSim/Rl/CardTags.php';
include_once './SWUSim/Custom/BotStyles.php';
include_once './SWUSim/Custom/BotResourcing.php';
include_once './SWUSim/Custom/BotGuides.php';
include_once './SWUSim/Custom/BotFallback.php';
include_once './SWUSim/Custom/BotRules.php';
include_once './SWUSim/BotHeuristic.php';
$rules = array_merge(SWUBotRulesBeforeFilter(), SWUBotRulesAfterFilter());
$rule = function (string $name, string $style, int $seat = 1) use ($rules, $botCtx) {
    $pick = ($rules[$name])($botCtx($style, $seat));
    return $pick === null ? null : strval($pick['cardID']);
};
$ids = fn($acts) => array_map(fn($a) => strval($a['cardID']), $acts);
$sorted = function ($a) { sort($a); return $a; };
// The whole stack, for the GUIDES (owner ruling 2026-09-13: moved out of layer 2): returns [pick, coverage keys].
$stack = function (string $style, int $seat = 1, string $variant = '') use (&$gameName) {
    SWUBotResetCoverage(); $legal = SWUBotLegalActions($gameName, $seat);
    $p = SWUBotHeuristicChoose($style, (array)$legal['actions'], $legal, $variant);
    return [$p === null ? null : strval($p['cardID']), array_keys($GLOBALS['SWUBotCoverage'][$seat] ?? [])];
};
$check(!array_intersect(['opening-resources', 'attack-before-developing', 'aggro-max-units', 'resource-floor', 'aggro-ceiling'], array_keys($rules)),
    'the five guides are no longer layer-2 rules (the model can learn past them)');
$quietLeader = fn($b) => $b->MyLeader('SOR_014', false);   // exhausted: no leader Action on offer
$decks = function ($b) { for ($i = 0; $i < 6; $i++) { $b->WithCardInDeckForPlayer(1, 'SOR_095'); $b->WithCardInDeckForPlayer(2, 'SOR_095'); } };
$toRegroup = function () use ($act) { $act(1, 10001, 'myHealth-0!CustomInput!Pass'); $act(2, 10001, 'myHealth-0!CustomInput!Pass'); };

// ── Rule 1 — single ──────────────────────────────────────────────────────────────────────────────
$build(function ($b) use ($quietLeader) { $quietLeader($b); $b->WithInitiativePlayerBeing(2); $b->WithInitiativeClaimed(); });
$check($ids($botCtx('normal')['actions']) === ['myHealth-0!CustomInput!Pass'], 'fixture: only Pass is legal');
$check($rule('single', 'normal') === 'myHealth-0!CustomInput!Pass', 'rule 1: the only legal move');
$build(function ($b) use ($quietLeader) { $quietLeader($b); });
$check($rule('single', 'normal') === null, 'rule 1 abstains with two candidates');

// ── Rule 2 — lethal now ──────────────────────────────────────────────────────────────────────────
$build(function ($b) use ($quietLeader) {
    $quietLeader($b); $b->TheirBase('SOR_020', 25);                                     // 5 left
    $b->WithGroundUnitForPlayer(1, 'SOR_095', true); $b->WithGroundUnitForPlayer(1, 'LOF_084', true);   // 3 + 4
});
$check($rule('lethal-now', 'control') === 'myGroundArena-1!FSM!', 'rule 2 (free play): lethal on board → the strongest attacker first');
$build(function ($b) use ($quietLeader) {
    $quietLeader($b); $b->TheirBase('SOR_020', 22);                                     // 8 left > 7
    $b->WithGroundUnitForPlayer(1, 'SOR_095', true); $b->WithGroundUnitForPlayer(1, 'LOF_084', true);
});
$check($rule('lethal-now', 'control') === null, 'rule 2 abstains one point short');
// The target prompt, as Control: the style filter would drop the base, but rule 2 runs first.
$build(function ($b) use ($quietLeader) {
    $quietLeader($b); $b->TheirBase('SOR_020', 25);
    $b->WithGroundUnitForPlayer(1, 'LOF_084', true); $b->WithGroundUnitForPlayer(1, 'SOR_095', true);
    $b->WithGroundUnitForPlayer(2, 'SOR_095', true);
});
$raiseAttack(1, 'myGroundArena-0');
$c = $botCtx('control');
// Owner ruling 2026-09-14 (feature 'baserace'): this board is a race Control wins, so its filter now keeps the base
// too. The premise below is about the unit-only filter, so it is pinned to @no-baserace.
SWUBotSetDisabledFeatures(['baserace']);
$check($sorted($ids(SWUBotStyleFilter($c))) === ['theirBase-0', 'theirGroundArena-0'],
    'fixture: with the racing shift off, Control keeps the unit AND the base (it no longer drops the base)');
SWUBotSetDisabledFeatures([]);
$check($ids(SWUBotStyleFilter($c)) === ['theirBase-0'],
    'baserace: racing, Control shifts to the aggro wing — the base ONLY');
$check($rule('lethal-now', 'control') === 'theirBase-0', 'rule 2 (target prompt): the exhausted attacker\'s 4 still counts → the base');
$build(function ($b) use ($quietLeader) {
    $quietLeader($b); $b->TheirBase('SOR_020', 22);
    $b->WithGroundUnitForPlayer(1, 'LOF_084', true); $b->WithGroundUnitForPlayer(1, 'SOR_095', true);
    $b->WithGroundUnitForPlayer(2, 'SOR_095', true);
});
$raiseAttack(1, 'myGroundArena-0');
$check($rule('lethal-now', 'control') === null, 'rule 2 (target prompt) abstains one point short');

// ── Opening resources ────────────────────────────────────────────────────────────────────────────
$build(function ($b) { foreach (['SOR_095', 'SOR_046', 'LOF_084', 'SOR_164'] as $c) $b->WithCardInHandForPlayer(1, $c); });   // 2, 4, 3, 4
DecisionQueueController::AddDecision(1, "CUSTOM", "ChooseStartingResource", 50);
ob_start(); (new DecisionQueueController())->ExecuteStaticMethods(1, '-'); ob_end_clean();
$check($botCtx('aggro')['tooltip'] === 'Choose_2_cards_to_resource', 'fixture: the opening two-card resource prompt');
$check($stack('aggro') === ['myHand-1&myHand-3', ['fallback']], 'opening (guide): Aggro resources its two most expensive');
$check($stack('control')[0] === 'myHand-0&myHand-2', 'opening (guide): Control — every card is castable soon (≤ 4), so the two cheapest go; the bomb and the other 4 stay');

// ── Rule 3 — initiative for lethal next round ────────────────────────────────────────────────────
$lethalNext = function ($b) use ($quietLeader) {
    $quietLeader($b); $b->TheirBase('SOR_020', 22);                                     // 8 left
    $b->WithGroundUnitForPlayer(1, 'LOF_084', false); $b->WithGroundUnitForPlayer(1, 'SOR_164', false);   // 4 + 4, exhausted
};
$build($lethalNext);
$check($rule('initiative-for-lethal', 'normal') === 'InitiativeCounter-0!CustomInput!TakeInitiative',
    'rule 3: exhausted units reach lethal next round, the opponent cannot pay for a Sentinel → take the initiative');
$build(function ($b) use ($lethalNext) { $lethalNext($b); $b->FillResourcesForPlayer(2, 'SOR_095', 1); });
$check($rule('initiative-for-lethal', 'normal') === null, 'rule 3 abstains when the opponent has one ready resource');
$build(function ($b) use ($quietLeader) {
    $quietLeader($b); $b->TheirBase('SOR_020', 21);                                     // 9 left > 8
    $b->WithGroundUnitForPlayer(1, 'LOF_084', false); $b->WithGroundUnitForPlayer(1, 'SOR_164', false);
});
$check($rule('initiative-for-lethal', 'normal') === null, 'rule 3 abstains one point short of lethal next round');
$build(function ($b) use ($quietLeader) {
    $quietLeader($b); $b->TheirBase('SOR_020', 22);
    $b->WithGroundUnitForPlayer(1, 'LOF_084', true); $b->WithGroundUnitForPlayer(1, 'SOR_164', true);   // ready: lethal NOW
});
$check($rule('initiative-for-lethal', 'normal') === null, 'rule 3 abstains when lethal is on the board now (rule 2 takes it)');

// ── Rule 4 — break lethal (all styles) ───────────────────────────────────────────────────────────────
// Seat 1's base is SOR_024; MyBase() sets its damage. Seat 1 pays +2 per missing aspect: Wing Guard (Vigilance,
// 3) costs 5, Vanquish (Vigilance, 5) costs 7, Smuggler's Aid (Heroism, 1) costs 1, the Marine (Command/Heroism) 2.
$facing = function (int $baseDamage, array $theirs, array $hand, int $resources) use ($quietLeader) {
    return function ($b) use ($quietLeader, $baseDamage, $theirs, $hand, $resources) {
        $quietLeader($b); $b->MyBase('SOR_024', $baseDamage); $b->FillResourcesForPlayer(1, 'SOR_095', $resources);
        foreach ($theirs as [$arena, $card]) {
            if ($arena === 'space') $b->WithSpaceUnitForPlayer(2, $card, true); else $b->WithGroundUnitForPlayer(2, $card, true);
        }
        foreach ($hand as $c) $b->WithCardInHandForPlayer(1, $c);
    };
};
// A Sentinel in the threatened arena: 4 left, a Wampa (4) on the ground.
$build($facing(26, [['ground', 'SOR_164']], ['SOR_063'], 5));
$check(SWUBotClock(2, 1) === 1 && in_array('myHand-0!FSM!', $ids($botCtx('normal')['actions']), true), 'fixture: facing lethal, the Wing Guard is playable');
$check($rule('break-lethal', 'normal') === 'myHand-0!FSM!', 'rule 4: a Sentinel in the threatened arena breaks lethal → play it');
$check($stack('aggro') === ['myHand-0!FSM!', ['rule:break-lethal']], 'rule 4 applies to every style (Aggro too)');
// The threat is in SPACE: a ground Sentinel breaks nothing.
$build($facing(28, [['space', 'SOR_237']], ['SOR_063'], 5));
$check(SWUBotClock(2, 1) === 1, 'fixture: facing lethal from a space unit (2 vs 2)');
$check($rule('break-lethal', 'normal') === null, 'rule 4 abstains when no move breaks lethal');
// Not facing lethal: 10 left against 4 power is a 3-round clock.
$build($facing(20, [['ground', 'SOR_164']], ['SOR_063'], 5));
$check($rule('break-lethal', 'normal') === null, 'rule 4 abstains when their clock is above 1');
// Removal — needs the SEQUENCE: Vanquish only removes once its target prompt is answered. A TIE (2) keeps
// the potential at 6 ≥ 4, so only the Wampa's removal breaks it.
$build($facing(26, [['ground', 'SOR_164'], ['space', 'SOR_225']], ['SOR_078'], 7));
$check($stack('normal') === ['myHand-0!FSM!', ['rule:break-lethal']], 'rule 4: removal that kills the threat breaks lethal → play Vanquish');
$act(1, 10002, 'myHand-0!FSM!');
$check(count($botCtx('normal')['actions']) === 2, 'fixture: Vanquish asks for a target (the Wampa or the TIE)');
$check($stack('normal') === ['theirGroundArena-0', ['rule:planned-answer']], 'planned answer: the line rule 4 chose is followed — the Wampa, not the TIE');
$check($rule('break-lethal', 'normal') === 'theirGroundArena-0', 'rule 4 at the target prompt itself also picks the Wampa');
// Healing that lifts the base out of range; a Marine in hand breaks nothing.
$build($facing(27, [['ground', 'SOR_095']], ['SOR_095', 'SHD_252'], 3));
$check($rule('break-lethal', 'normal') === 'myHand-1!FSM!', 'rule 4: healing 3 (3 → 6 left) breaks lethal; the Marine would not');
// Two breaking moves: the one that leaves their clock longest wins. 6 left; Wampa 4 + Marine 3 (ground),
// TIE 2 (space). Vanquish on the Wampa → 5 potential → clock 2; Wing Guard → only the TIE → clock 3.
$build($facing(24, [['ground', 'SOR_164'], ['ground', 'SOR_095'], ['space', 'SOR_225']], ['SOR_078', 'SOR_063'], 12));
$check($rule('break-lethal', 'normal') === 'myHand-1!FSM!', 'rule 4 prefers the move that leaves their clock longest (the Sentinel)');
// Priority: rule 2 (lethal now) beats rule 4 — winning now beats defending.
$build(function ($b) use ($facing) {
    ($facing(26, [['ground', 'SOR_164']], ['SOR_063'], 5))($b);
    $b->TheirBase('SOR_020', 27); $b->WithGroundUnitForPlayer(1, 'SOR_095', true);   // 3 left, a ready 3
});
$check($stack('normal') === ['myGroundArena-0!FSM!', ['rule:lethal-now']], 'priority: rule 2 beats rule 4');
// Priority: rule 3 (initiative for lethal next round) beats rule 4 — acting first next round wins the race.
$build(function ($b) use ($facing) {
    ($facing(26, [['ground', 'SOR_164']], ['SOR_063'], 5))($b);
    $b->TheirBase('SOR_020', 22);                                                        // 8 left
    $b->WithGroundUnitForPlayer(1, 'LOF_084', false); $b->WithGroundUnitForPlayer(1, 'SOR_164', false);
});
$check($stack('normal')[1] === ['rule:initiative-for-lethal'], 'priority: rule 3 beats rule 4');
// Cost bound — sweep run 3 timed out twice (games ahsoka_blue.vader_yellow s032 and s072): facing
// lethal, rule 4 searched every candidate's every answer three prompts deep before abstaining (62 s at a Name-a-card
// prompt, 17 s at a 4-target attack prompt). Eight ready Marines against five: no single attack breaks 15 power
// into 4 left, and unbounded this board costs 8 × (the attack + 6 target answers) = 56 lookaheads.
$build(function ($b) use ($quietLeader) {
    $quietLeader($b); $b->MyBase('SOR_024', 26);
    for ($i = 0; $i < 8; $i++) $b->WithGroundUnitForPlayer(1, 'SOR_095', true);
    for ($i = 0; $i < 5; $i++) $b->WithGroundUnitForPlayer(2, 'SOR_095', true);
});
$check(SWUBotClock(2, 1) === 1 && count($botCtx('normal')['actions']) >= 8, 'fixture: facing lethal with eight attacks on offer');
$GLOBALS['SWUBotLookaheadCalls'] = 0;
$check($rule('break-lethal', 'normal') === null, 'rule 4 abstains: no single attack breaks lethal here');
$check(intval($GLOBALS['SWUBotLookaheadCalls'] ?? 0) > 0 && $GLOBALS['SWUBotLookaheadCalls'] <= SWU_BOT_LOOKAHEAD_BUDGET,
    'rule 4 spends at most SWU_BOT_LOOKAHEAD_BUDGET lookaheads on one decision; got ' . ($GLOBALS['SWUBotLookaheadCalls'] ?? 0));
// A stale plan is discarded, never followed.
$build($facing(26, [['ground', 'SOR_164'], ['space', 'SOR_225']], ['SOR_078'], 7));
$act(1, 10002, 'myHand-0!FSM!');
$GLOBALS['SWUBotPlan'][1] = [['tooltip' => 'Some_other_prompt', 'answer' => 'theirSpaceArena-0']];
$check($rule('planned-answer', 'normal') === null && empty($GLOBALS['SWUBotPlan'][1] ?? []), 'planned answer: a plan whose prompt does not match is dropped');

// ── Rule 5 — Control plays board wipes that stabilise ────────────────────────────────────────────────
// A WIPE defeats two or more units, at least one of Control's own; it STABILISES when their clock afterwards
// is ≥ 3 and longer than before. LAW_044 (defeat all units) costs seat 1 8 +4 = 12.
$wipeBoard = function (bool $ownUnit) use ($quietLeader) {
    return function ($b) use ($quietLeader, $ownUnit) {
        $quietLeader($b); $b->FillResourcesForPlayer(1, 'SOR_095', 12); $b->WithCardInHandForPlayer(1, 'LAW_044');
        if ($ownUnit) $b->WithGroundUnitForPlayer(1, 'SOR_095', true);
        $b->WithGroundUnitForPlayer(2, 'SOR_164', true); $b->WithGroundUnitForPlayer(2, 'LOF_084', true);   // 8 power: clock 4
    };
};
$build($wipeBoard(true));
$check(SWUBotClock(2, 1) === 4 && in_array('myHand-0!FSM!', $ids($botCtx('control')['actions']), true), 'fixture: their clock is 4, LAW_044 is playable');
$check($stack('control') === ['myHand-0!FSM!', ['rule:control-wipe']], 'rule 5: defeating all three (one ours) takes their clock 4 → none → play the wipe');
$check($rule('control-wipe', 'normal') === null, 'rule 5 is Control only');
// Owner ruling 2026-09-14: a wipe qualifies when the enemy loses at least as much VALUE as Control does (unless
// Control is facing lethal soon); "at least one of Control's own units" is gone — one-sided sweeps qualify.
$build($wipeBoard(false));
$check($rule('control-wipe', 'control') === 'myHand-0!FSM!', 'rule 5: a one-sided sweep (theirs only) qualifies');
SWUBotSetDisabledFeatures(['wipegate']);
$check($rule('control-wipe', 'control') === null, '@no-wipegate: the old definition — Control must lose a unit');
SWUBotSetDisabledFeatures([]);
// A wipe that costs more than it removes: my two Wampas (8) for their two Marines (4). Their clock on me is 5.
$costly = function (int $baseDamage) use ($quietLeader) {
    return function ($b) use ($quietLeader, $baseDamage) {
        $quietLeader($b); $b->MyBase('SOR_024', $baseDamage); $b->FillResourcesForPlayer(1, 'SOR_095', 12); $b->WithCardInHandForPlayer(1, 'LAW_044');
        $b->WithGroundUnitForPlayer(1, 'SOR_164', true); $b->WithGroundUnitForPlayer(1, 'SOR_164', true);
        $b->WithGroundUnitForPlayer(2, 'SOR_095', true); $b->WithGroundUnitForPlayer(2, 'SOR_095', true);
    };
};
$build($costly(0));
$check(SWUBotClock(2, 1) === 5 && $rule('control-wipe', 'control') === null, 'rule 5 abstains when the wipe costs Control more value than the enemy');
SWUBotSetDisabledFeatures(['wipegate']);
$check($rule('control-wipe', 'control') === 'myHand-0!FSM!', '@no-wipegate: the old rule fired on it (own units lost, stabilises)');
SWUBotSetDisabledFeatures([]);
// …unless Control is facing lethal soon: 12 left against 6 power is a 2-round clock.
$build($costly(18));
$check(SWUBotClock(2, 1) === 2 && $rule('control-wipe', 'control') === 'myHand-0!FSM!', 'under pressure (their clock 2) the costly wipe qualifies');
// A wipe with a CHOICE: Bombing Run's arena. 12 left; Wampa 4 (ground, survives 3) + TIE 2 + X-Wing 2 (space)
// → clock 2. Space: our X-Wing, their TIE and X-Wing die → clock 3 (stabilises). Ground: nothing dies.
$bomb = function (int $theirGroundExtra) use ($quietLeader) {
    return function ($b) use ($quietLeader, $theirGroundExtra) {
        $quietLeader($b); $b->MyBase('SOR_024', 18); $b->FillResourcesForPlayer(1, 'SOR_095', 5);
        $b->WithCardInHandForPlayer(1, 'SOR_173'); $b->WithSpaceUnitForPlayer(1, 'SOR_237', true);
        $b->WithGroundUnitForPlayer(2, 'SOR_164', true);
        if ($theirGroundExtra) $b->WithGroundUnitForPlayer(2, 'SOR_095', true);
        $b->WithSpaceUnitForPlayer(2, 'SOR_225', true); $b->WithSpaceUnitForPlayer(2, 'SOR_237', true);
    };
};
$build($bomb(0));
$check(SWUBotClock(2, 1) === 2, 'fixture: Bombing Run board, their clock is 2');
$check($stack('control') === ['myHand-0!FSM!', ['rule:control-wipe']], 'rule 5: the SPACE line of Bombing Run stabilises → play it');
$act(1, 10002, 'myHand-0!FSM!');
$check($botCtx('control')['tooltip'] === 'Choose_an_arena_to_bomb', 'fixture: the arena prompt');
$check($stack('control') === ['Space', ['rule:planned-answer']], 'planned answer: the stabilising arena (Space) is chosen');
// The same board plus a Marine on their ground side (11 potential): the space line leaves 7 → still clock 2.
$build($bomb(1));
$check($rule('control-wipe', 'control') === null, 'rule 5 abstains when no line stabilises (their clock stays 2)');
// Priority: rule 4 beats rule 5 — facing lethal, the break-lethal rule answers (whichever move it picks).
$build(function ($b) use ($wipeBoard) {
    ($wipeBoard(true))($b); $b->MyBase('SOR_024', 26);                                    // 4 left vs 8 → lethal
});
$check($stack('control')[1] === ['rule:break-lethal'], 'priority: rule 4 beats rule 5');

// ── Rule 6 — attack before developing ────────────────────────────────────────────────────────────
$develop = function (string $inHand) use ($quietLeader) {
    return function ($b) use ($quietLeader, $inHand) {
        // 3 resources: enough for the play, one short of Sabine's deploy (a deploy would make rule 6 abstain)
        $quietLeader($b); $b->FillResourcesForPlayer(1, 'SOR_095', 3);
        $b->WithCardInHandForPlayer(1, $inHand); $b->WithGroundUnitForPlayer(1, 'SOR_095', true);
    };
};
$build($develop('SOR_095'));
$check($ids($botCtx('normal')['actions'])[0] === 'myHand-0!FSM!', 'fixture: the play is listed before the attack');
$check($stack('normal') === ['myGroundArena-0!FSM!', ['fallback', 'guide:attack-first']], 'attack first (guide): a vanilla unit in hand → attack first');
// Both guides apply: a 1-power attacker (Vanguard Infantry, 1 to the base) vs Echo Base Defender (3, Command,
// no attack-improving text) in hand. The unit play scores ~5.4 with the max-units weight; the attack only
// wins because attackFirst (6) outranks it. 3 resources: one short of Sabine's deploy (a deploy would switch
// attack-first off).
$build(function ($b) use ($quietLeader) { $quietLeader($b); $b->FillResourcesForPlayer(1, 'SOR_095', 3);
    $b->WithCardInHandForPlayer(1, 'SOR_098'); $b->WithGroundUnitForPlayer(1, 'SOR_108', true); });
$c = $botCtx('aggro');
$check(SWUBotAttackFirstAttacks($c) !== [] && SWUBotAggroMaxUnitsPick($c) !== null, 'fixture: both guides apply');
$check($stack('aggro')[0] === 'myGroundArena-0!FSM!', 'attack first outranks Aggro\'s max-units when both apply (the old rule 6 > rule 7 order)');
$build($develop('SOR_120'));
$check(SWUBotAttackFirstAttacks($botCtx('normal')) === [], 'attack first is off with an upgrade in hand (it could go on the attacker)');
$build(function ($b) use ($develop) { ($develop('SOR_095'))($b); $b->WithInitiativePlayerBeing(2); $b->WithInitiativeClaimed(); });
$check(SWUBotAttackFirstAttacks($botCtx('normal')) === [], 'attack first is off once the opponent has claimed the initiative');
$build(function ($b) use ($quietLeader) { $quietLeader($b); $b->WithGroundUnitForPlayer(1, 'SOR_095', true); });
$check(SWUBotAttackFirstAttacks($botCtx('normal')) === [], 'attack first is off with nothing to develop (rule 8\'s case)');

// ── Rule 7 — Aggro maximises units played ────────────────────────────────────────────────────────
$units = function ($b) use ($quietLeader) {
    $quietLeader($b); $b->FillResourcesForPlayer(1, 'SOR_095', 6);
    foreach (['SOR_095', 'SOR_098', 'SOR_145'] as $c) $b->WithCardInHandForPlayer(1, $c);   // 2, 3, 4
};
$build($units);
$check($stack('aggro') === ['myHand-2!FSM!', ['fallback', 'guide:max-units']], 'max units (guide): 6 resources, units 2/3/4 → 2 + 4 → play the 4');
$check(SWUBotAggroMaxUnitsPick($botCtx('normal')) === null, 'max units is Aggro only');
$build(function ($b) use ($quietLeader) {
    $quietLeader($b); $b->FillResourcesForPlayer(1, 'SOR_095', 5);
    foreach (['SOR_095', 'SOR_098', 'SOR_145'] as $c) $b->WithCardInHandForPlayer(1, $c);
});
$check($stack('aggro')[0] === 'myHand-1!FSM!', 'max units (guide): 5 resources → 2 + 3 is the only two-unit set that fits → play the 3');
$build(function ($b) use ($units) { $units($b); $b->WithCardInHandForPlayer(1, 'SOR_120'); $b->WithGroundUnitForPlayer(1, 'SOR_095', true); });
$check(in_array('myHand-3!FSM!', $ids($botCtx('aggro')['actions']), true), 'fixture: the upgrade is playable (a unit to attach to)');
$check(SWUBotAggroMaxUnitsPick($botCtx('aggro')) === null, 'max units is off when a non-unit play is on offer');

// ── Rule 8 — no unused attacks ───────────────────────────────────────────────────────────────────
$build(function ($b) use ($quietLeader) { $quietLeader($b); $b->WithGroundUnitForPlayer(1, 'SOR_095', true); });
$check($rule('no-unused-attacks', 'normal') === 'myGroundArena-0!FSM!', 'rule 8: only pass / initiative left → make the free attack');
$build($develop('SOR_095'));
$check($rule('no-unused-attacks', 'normal') === null, 'rule 8 abstains while a play is on offer');
$build(function ($b) use ($quietLeader) { $quietLeader($b); $b->WithGroundUnitForPlayer(1, 'SOR_095', true); $b->WithGroundUnitForPlayer(2, 'LOF_084', true); });
$check($rule('no-unused-attacks', 'control') !== null,
    'rule 8 now attacks the BASE when the only unit attack would be a losing trade (owner, 2026-09-18)');

// ── Rule 9 — nothing left ────────────────────────────────────────────────────────────────────────
$build(function ($b) use ($quietLeader) { $quietLeader($b); });
$check($rule('nothing-left', 'normal') === 'InitiativeCounter-0!CustomInput!TakeInitiative', 'rule 9: only pass / initiative → the initiative');
$build(function ($b) use ($quietLeader) { $quietLeader($b); $b->WithGroundUnitForPlayer(1, 'SOR_095', true); });
$check($rule('nothing-left', 'normal') === null, 'rule 9 abstains with an attack available');

// ── Rules 10 and 11 — the floor and Aggro's ceiling ──────────────────────────────────────────────
$regroup = function (string $leader, int $resources) use ($decks, $toRegroup) {
    return function ($b) use ($decks, $leader, $resources) {
        $b->MyLeader($leader); $b->FillResourcesForPlayer(1, 'SOR_095', $resources);
        foreach (['SOR_095', 'SOR_046'] as $c) $b->WithCardInHandForPlayer(1, $c);
        $decks($b);
    };
};
$build($regroup('SOR_014', 3)); $toRegroup();
$check($botCtx('aggro')['tooltip'] === 'Resource_up_to_1_card', 'fixture: the regroup resource prompt');
$check(!in_array('PASS', $ids(SWUBotResourceFloorFilter($botCtx('aggro'))), true), 'rule 10 (constraint): 3 resources < Sabine\'s 4 → PASS removed');
$check($stack('aggro') === ['myHand-1', ['filter:resource-floor', 'fallback']], '… and the card is the guide\'s keep-value pick (Aggro: the most expensive)');
$build($regroup('SOR_014', 4)); $toRegroup();
$check(in_array('PASS', $ids(SWUBotResourceFloorFilter($botCtx('aggro'))), true), 'rule 10 lets PASS stay at the threshold');
// Resource stops as GUIDES (spec: the five archetypes stop at 6 / 7 / 8 / 9 / 11 — hyper aggro stops
// earliest, hard control banks for its bombs and its answer pair); the deck decides within its range
// (SWUBotResourceStop). This deck tops out at 4.
$build($regroup('SOR_014', 6)); $toRegroup();
$check([SWUBotResourceStop(1, 'hyperaggro'), SWUBotResourceStop(1, 'softaggro'), SWUBotResourceStop(1, 'midrange'),
        SWUBotResourceStop(1, 'softcontrol'), SWUBotResourceStop(1, 'hardcontrol')] === [6, 7, 8, 9, 11],
    'a deck topping out at 4: stops 6 / 7 / 8 / 9 / 11 across the five archetypes');
$check($stack('hyperaggro') === ['PASS', ['fallback', 'guide:stop']], 'stop (guide): hyper aggro at 6 resources passes');
$check($stack('softaggro')[0] === 'myHand-1', 'soft aggro at 6 keeps resourcing — its stop is 7');
$check($stack('aggro', 1, 'no-stop')[0] === 'myHand-1', '@no-stop: Aggro keeps resourcing');
// The point here is that Normal does NOT pass at 6 (its stop is 8). The INDEX moved 1 -> 0 with p12
// 'mgbomb', which resources the cheapest card rather than the most expensive.
$check($stack('normal')[0] !== 'PASS', 'Normal at 6 keeps resourcing — its stop is 8');
$check($stack('normal')[0] === 'myHand-0', '… and p12 makes that pick the cheapest card, not the dearest');
$build($regroup('SOR_014', 5)); $toRegroup();
$check($stack('aggro')[0] === 'myHand-1', 'Aggro at 5 still resources');
$build($regroup('SOR_014', 8)); $toRegroup();
$check($stack('normal')[0] === 'PASS' && $stack('control')[0] !== 'PASS', 'Normal stops at 8; Control (9) does not');
$build(function ($b) use ($regroup) { ($regroup('SOR_014', 10))($b); $b->WithCardInDeckForPlayer(1, 'JTL_041'); }); $toRegroup();
$check(SWUBotResourceStop(1, 'control') === 11 && $stack('control')[0] !== 'PASS', 'an 11-cost card in the deck: Control resources on toward 11');
$build(function ($b) use ($regroup) { ($regroup('SOR_014', 9))($b); $b->WithCardInDeckForPlayer(1, 'LAW_133'); $b->WithCardInDeckForPlayer(1, 'JTL_043'); }); $toRegroup();
$check(SWUBotResourceStop(1, 'control') === 11, 'Lost and Forgotten (6) + No Glory (5) in one round: Control stops at 11');
$build($regroup('LOF_007', 7)); $toRegroup();
$check($stack('aggro')[0] === 'myHand-1', 'the floor beats the stop: Avar Kriss (9) at 7 resources keeps resourcing');

// ── Rule 12 — decline a losing Ambush ────────────────────────────────────────────────────────────
$ambush = function (string $enemy) {
    return function ($b) use ($enemy) {
        $b->FillResourcesForPlayer(1, 'SOR_095', 8); $b->WithCardInHandForPlayer(1, 'SOR_115'); $b->WithGroundUnitForPlayer(2, $enemy, true);
    };
};
$build($ambush('SOR_164')); $act(1, 10002, 'myHand-0!FSM!');
$check($botCtx('normal')['tooltip'] === 'Ambush_attack?', 'fixture: the Ambush prompt');
$check($rule('decline-losing-ambush', 'normal') === 'NO', 'rule 12: Kallus 4/4 into a Wampa 4/5 dies without a kill → NO');
$build($ambush('SOR_095')); $act(1, 10002, 'myHand-0!FSM!');
$check($rule('decline-losing-ambush', 'normal') === null, 'rule 12 abstains when the Ambush kills and survives');

// ── Rule 13 — a free benefit ─────────────────────────────────────────────────────────────────────
$mando = function (bool $enemyMando) {
    return function ($b) use ($enemyMando) {
        $b->FillResourcesForPlayer(1, 'SOR_095', 8); $b->WithCardInHandForPlayer(1, 'SHD_258');
        $b->WithGroundUnitForPlayer(1, 'SHD_040', false); $b->WithGroundUnitForPlayer(1, 'SHD_047', true);
        if ($enemyMando) $b->WithGroundUnitForPlayer(2, 'SHD_040', true);
    };
};
$build($mando(false)); $act(1, 10002, 'myHand-0!FSM!');
$check($botCtx('normal')['type'] === 'MZMAYCHOOSE' && in_array('PASS', $ids($botCtx('normal')['actions']), true), 'fixture: an optional Experience grant');
$check($rule('free-benefit', 'normal') === 'myGroundArena-1', 'rule 13: all targets friendly → take it (the ready unit)');
$build($mando(true)); $act(1, 10002, 'myHand-0!FSM!');
$check($rule('free-benefit', 'normal') === null, 'rule 13 abstains when an enemy is among the targets');

// ── Rule 14 — exhaust a ready enemy ──────────────────────────────────────────────────────────────
$exhaust = function (bool $firstReady) {
    return function ($b) use ($firstReady) {
        $b->FillResourcesForPlayer(1, 'SOR_095', 8); $b->WithCardInHandForPlayer(1, 'SOR_218');
        $b->WithGroundUnitForPlayer(2, 'SOR_095', $firstReady); $b->WithGroundUnitForPlayer(2, 'SOR_095', true);
    };
};
$build($exhaust(false)); $act(1, 10002, 'myHand-0!FSM!');
$check(($botCtx('normal')['following'][0] ?? '') === 'EXHAUST_UNIT', 'fixture: Asteroid Sanctuary offers EXHAUST_UNIT');
$check($rule('exhaust-ready-enemies', 'normal') === 'theirGroundArena-1', 'rule 14: skip the already-exhausted enemy');
$build($exhaust(true)); $act(1, 10002, 'myHand-0!FSM!');
$check($rule('exhaust-ready-enemies', 'normal') === null, 'rule 14 abstains when every enemy is ready');

// ── Rule 15 — bank Credits ───────────────────────────────────────────────────────────────────────
$credits = function (int $ready, array $extra = []) {
    return function ($b) use ($ready, $extra) {
        $b->FillResourcesForPlayer(1, 'SOR_095', $ready); $b->FillResourcesForPlayer(1, 'LAW_T01', 2);
        $b->WithCardInHandForPlayer(1, 'SOR_095');                                        // costs 2
        foreach ($extra as [$seat, $card]) $b->WithGroundUnitForPlayer($seat, $card, true);
    };
};
$build($credits(4)); $act(1, 10002, 'myHand-0!FSM!');
$check($botCtx('normal')['tooltip'] === 'Defeat_any_number_of_Credit_tokens_to_pay_1_resource_less_each', 'fixture: the Credit prompt');
$check($rule('bank-credits', 'normal') === '-', 'rule 15: 4 ready resources cover the 2 → spend no Credits');
$build($credits(1)); $act(1, 10002, 'myHand-0!FSM!');
$check(SWUBotSelectionCount(['cardID' => $rule('bank-credits', 'normal') ?? 'PASS']) === 1, 'rule 15: 1 ready resource → one Credit for the shortfall');
$build($credits(4, [[2, 'LAW_191']])); $act(1, 10002, 'myHand-0!FSM!');
$check($rule('bank-credits', 'normal') === 'myTempZone-0&myTempZone-1', 'rule 15: an enemy Arvel Skeen → spend the Credits first');
$build($credits(4, [[1, 'SEC_122']])); $act(1, 10002, 'myHand-0!FSM!');
$check($botCtx('normal')['tooltip'] === 'Defeat_any_number_of_Credit_tokens_to_pay_1_resource_less_each' && $rule('bank-credits', 'normal') === null,
    'rule 15 abstains under SEC_122 (its Droids join the payment)');

bot_test_finish();

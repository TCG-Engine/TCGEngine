<?php
// BOT INVARIANTS — the "never do" rules, each from a real production bug or an owner ruling. Owner 2026-10-12: bot FEATURES are verified
// by runs, not unit tests (memory `bot-features-no-unit-tests`); what stays tested is what must NEVER happen, written as a rule ("never
// targets its own unit while an enemy is legal"), not as a pinned pick. Folded from nine files (git history has them): bot_selfping_test,
// bot_nogift_test, bot_buffs_test, bot_weakness_test, bot_weaknessenemy_test, bot_splitlethal_test, bot_practice_game_over_test,
// bot_plantie_test, bot_plantruncate_test. Dropped in the fold: the "@no-<feature> reproduces the bug" pins and the switch/group checks.
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php -d apc.enable_cli=1 -d xdebug.mode=off SWUSim/DevTools/tests/bot_invariants_test.php
require __DIR__ . '/fixtures/bot_test_bootstrap.php';
foreach (['BotLegalActions', 'Custom/BotLookahead', 'Custom/BotEvaluator', 'Rl/CardTags', 'Custom/BotStyles', 'Custom/BotResourcing',
          'Custom/BotGuides', 'Custom/BotFallback', 'Custom/BotRules', 'BotHeuristic'] as $f) include_once "./SWUSim/$f.php";
// A bot step that runs ends in the engine's frame-animation cache write, which the CLI lacks.
if (!function_exists('WriteCache')) { function WriteCache(...$a) {} }
if (!function_exists('GamestateUpdated')) { function GamestateUpdated(...$a) {} }

$ids = fn($acts) => array_map(fn($a) => strval($a['cardID']), (array)$acts);
$stack = function (string $style, int $seat = 1) use (&$gameName) {
    SWUBotResetCoverage(); $legal = SWUBotLegalActions($gameName, $seat);
    $p = SWUBotHeuristicChoose($style, (array)$legal['actions'], $legal, '');
    return $p === null ? null : strval($p['cardID']);
};
$scoreOf = function (string $style, string $cardID) use ($botCtx) {
    $ctx = $botCtx($style);
    foreach ($ctx['actions'] as $i => $a) if (strval($a['cardID']) === $cardID) return SWUBotScoreAction($ctx, $a, $i);
    return null;
};
$ability = 'myLeader-0!CustomInput!LeaderAbility';
$play = 'myHand-0!FSM!';

// ══ 1. NEVER USE A HOSTILE EFFECT WHOSE ONLY TARGETS ARE MINE (Bug Report #1100, game 1402804) ═══════════════════════════════════════
// JTL_012 Luke: "Action [Exhaust]: If you attacked with a Fighter unit this phase, deal 1 damage to a unit" — mandatory once used; with no
// enemy unit the bot shot its own Y-Wing. (Precondition set through the production flag SWU_ATTACKED_FIGHTER.)
$luke = function (array $mine, array $theirs) use ($build) {
    $build(function ($b) use ($mine, $theirs) {
        $b->MyLeader('JTL_012');
        foreach ($mine as [$c, $dmg]) $b->WithSpaceUnitForPlayer(1, $c, false, $dmg);
        foreach ($theirs as $c) $b->WithSpaceUnitForPlayer(2, $c, true, 0);
    });
    AddGlobalEffects(1, 'SWU_ATTACKED_FIGHTER');
};
$luke([['JTL_149', 2], ['JTL_147', 0]], []);
$check(in_array($ability, $ids($botCtx('normal')['actions']), true), "1 fixture: Luke's Action is on offer with only my ships to hit");
$check($stack('normal') !== $ability && $stack('softaggro') !== $ability, '1A: an Action whose only targets are MY units is not used');
$luke([['JTL_149', 2]], ['JTL_147']);
$check($stack('normal') === $ability, '1B: with an enemy target it IS used (the rule is not "never use Luke")');
// Forced to self-damage anyway (the prompt is up), it chips the healthy ship rather than killing the hurt one.
$luke([['JTL_149', 2], ['JTL_147', 0]], []);
$act(1, 10001, $ability);
$check($stack('normal') === 'mySpaceArena-1', '1C: forced self-damage chips the healthy ship, never kills the hurt one');
// A DECLARED friendly cost is paid: SHD_028 Doctor Pershing "Action [Exhaust, deal 1 damage to a friendly unit]: Draw a card".
$pershing = 'myGroundArena-0!CustomInput!Activate';
$build(function ($b) {
    $b->MyLeader('SOR_014', false, false, true);
    $b->WithGroundUnitForPlayer(1, 'SHD_028', true); $b->WithGroundUnitForPlayer(1, 'SOR_095', false);
    $b->WithCardInDeckForPlayer(1, 'SOR_095'); $b->WithCardInDeckForPlayer(1, 'SOR_095');
});
$check(($scoreOf('normal', $pershing) ?? -1) > 0.0 && $stack('normal') === $pershing, '1D: a declared friendly COST is paid (Pershing draws)');
// The single-target hole (owner report 2026-10-07): SOR_025 Tarkintown "Epic Action: Deal 3 damage to a damaged non-leader unit" with the
// only damaged unit MINE auto-resolves (no prompt) — refused; with the only damaged unit THEIRS — used.
$EPIC = 'myBase-0!CustomInput!EpicAction';
$tarkintown = function (array $mine, array $theirs) use ($build) {
    $build(function ($b) use ($mine, $theirs) {
        $b->MyLeader('SOR_014', false, false, true); $b->MyBase('SOR_025', 0);
        foreach ($mine as [$c, $dmg]) $b->WithGroundUnitForPlayer(1, $c, false, $dmg);
        foreach ($theirs as [$c, $dmg]) $b->WithGroundUnitForPlayer(2, $c, true, $dmg);
    });
};
$tarkintown([['SOR_037', 1]], [['SOR_095', 0]]);
$check(($scoreOf('normal', $EPIC) ?? 1) < 0.0 && $stack('normal') !== $EPIC && $stack('control') !== $EPIC, '1E: a lone OWN target (survives) — not used');
$tarkintown([['SOR_095', 1]], [['SOR_095', 0]]);
$check(($scoreOf('normal', $EPIC) ?? 1) < 0.0 && $stack('normal') !== $EPIC, '1E: a lone OWN target (dies) — not used');
$tarkintown([['SOR_037', 0]], [['SOR_095', 1]]);
$check($stack('normal') === $EPIC, '1E: a lone ENEMY target — used');

// ══ 2. NEVER GIFT THE OPPONENT (Bug Report #1066 game 1097180; owner: "the bot should not give any advantageous plays to the opponent") ══
$giftBoard = function (string $card, array $mine, array $theirs) use ($build) {
    $build(function ($b) use ($card, $mine, $theirs) {
        $b->MyLeader('SOR_014', false, false, true); $b->FillResourcesForPlayer(1, 'SOR_095', 6);
        $b->WithCardInHandForPlayer(1, $card);
        foreach ($mine as [$c, $dmg]) $b->WithGroundUnitForPlayer(1, $c, true, $dmg);
        foreach ($theirs as [$c, $dmg]) $b->WithGroundUnitForPlayer(2, $c, true, $dmg);
    });
};
// ASH_089 Perseverance ("Heal 3 damage from a unit and give a Shield token to it") with only an enemy unit to take it.
$giftBoard('ASH_089', [], [['LAW_113', 2]]);
$check(($scoreOf('normal', $play) ?? 1) < 0.0 && $stack('normal') !== $play, '2A: healing + shielding the ENEMY is never played');
$giftBoard('ASH_089', [], [['LAW_113', 0]]);
$check(($scoreOf('normal', $play) ?? 1) < 0.0, '2B: a Shield alone on the enemy is a gift too');
$giftBoard('ASH_089', [['LAW_113', 2]], [['LAW_113', 2]]);
$check(($scoreOf('normal', $play) ?? -1) > 0.0, '2C: with my own damaged unit to help, it is played');
$giftBoard('LAW_113', [], [['LAW_113', 0]]);
$check(($scoreOf('normal', $play) ?? -1) > -0.4, '2D: a unit whose When Played can shield ITSELF is no gift');
// LAW_129 Mastery (+3/+3) with only enemy hosts (game 1438045); with a friendly host it is fine; a -2/-2 upgrade is MEANT for an enemy.
$giftBoard('LAW_129', [], [['ASH_056', 0], ['LAW_039', 0]]);
$check(($scoreOf('normal', $play) ?? 1) < 0.0 && $stack('normal') !== $play, '2E: a +3/+3 upgrade with only ENEMY hosts is never played');
$giftBoard('LAW_129', [['SOR_095', 0]], [['ASH_056', 0]]);
$check(($scoreOf('normal', $play) ?? -1) > 0.0, '2F: with a friendly host the upgrade is played');
$giftBoard('TWI_070', [], [['ASH_056', 0]]);
$check(($scoreOf('normal', $play) ?? -1) > 0.0, '2G: a -2/-2 upgrade on the only (enemy) unit is not a gift');

// ══ 3. BUFFS GO ON MY UNITS (live game 183227: "wasted an action using their Ahsoka leader ability to buff my unit") ════════════════════
// ASH_009 Ahsoka: "Choose a unit with less power than a friendly unit. It gets +2/+0 for this phase."
$build(function ($b) { $b->MyLeader('ASH_009'); $b->WithGroundUnitForPlayer(1, 'SOR_095', false); $b->WithSpaceUnitForPlayer(2, 'SOR_225', true); });
$check(in_array($ability, $ids($botCtx('normal')['actions']), true) && $stack('normal') !== $ability, "3A: an Action whose only target is an enemy (a buff) is not used");
$build(function ($b) { $b->MyLeader('ASH_009'); $b->WithGroundUnitForPlayer(1, 'SOR_095', false); $b->WithGroundUnitForPlayer(1, 'SEC_T01', true);
    $b->WithSpaceUnitForPlayer(2, 'SOR_225', true); });
$act(1, 10001, $ability);
$check(str_starts_with(strval($stack('normal')), 'my'), '3B: the +2/+0 goes on MY unit, never the enemy');
// Deployed Ahsoka's On Attack "+2/+0 to a unit with less power" when only the ENEMY's unit qualifies (owner: buffing it is a TAUNT): declined.
$build(function ($b) { $b->MyLeader('ASH_009', true, true, true, 'unit'); $b->TheirBase('SOR_020'); $b->WithGroundUnitForPlayer(2, 'SOR_095', false); });
$attack = null;
foreach ((array)SWUBotLegalActions($gameName, 1)['actions'] as $a) if (SWUBotActionKind($a) === 'attack') { $attack = $a; break; }
$check($attack !== null, '3 fixture: deployed Ahsoka can attack');
$prompts = [];
for ($i = 0; $i < 6 && $attack !== null; $i++) {
    $legal = SWUBotLegalActions($gameName, 1);
    if ($i === 0) { $act(1, intval($attack['mode'] ?? 10001), strval($attack['cardID'])); continue; }
    if (($legal['kind'] ?? '') !== 'decision') break;
    $pick = SWUBotHeuristicChoose('hyperaggro', (array)$legal['actions'], $legal, '');
    $prompts[] = [strval($legal['decisionTooltip'] ?? ''), $ids((array)$legal['actions']), strval($pick['cardID'] ?? 'PASS')];
    $act(1, intval($pick['mode'] ?? 10001), strval($pick['cardID'] ?? 'PASS'));
}
$buff = array_values(array_filter($prompts, fn($p) => in_array('theirGroundArena-0', $p[1], true) && !str_contains($p[0], 'attack_target')));
$check(!empty($buff) && $buff[0][2] !== 'theirGroundArena-0', '3C: deployed Ahsoka never buffs the ENEMY on attack; prompts ' . json_encode($prompts));

// ══ 4. WEAKNESS NEVER GOES ON MY OWN UNITS (live game 189011; owner 2026-10-06: "always put it on an enemy unit") ═══════════════════
// HMW_071 Ravage: "Distribute up to 3 Weakness tokens among any number of units."
$build(function ($b) { $b->MyLeader('SOR_014', false); $b->FillResourcesForPlayer(1, 'SOR_095', 8); $b->WithCardInHandForPlayer(1, 'HMW_071');
    $b->WithGroundUnitForPlayer(1, 'LAW_174', true); $b->WithGroundUnitForPlayer(2, 'SOR_095', true); $b->WithSpaceUnitForPlayer(2, 'SOR_225', true); });
$act(1, 10002, $play);
$mine = 0; $theirs = 0;
foreach (explode(',', strval($stack('normal'))) as $pair) {
    [$mz, $n] = array_pad(explode(':', $pair), 2, 0);
    if (str_starts_with(trim($mz), 'my')) $mine += intval($n); elseif (str_starts_with(trim($mz), 'their')) $theirs += intval($n);
}
$check($mine === 0 && $theirs > 0, "4A: Ravage's tokens go on enemy units, none on mine");
$build(function ($b) { $b->MyLeader('SOR_014', false); $b->FillResourcesForPlayer(1, 'SOR_095', 8); $b->WithCardInHandForPlayer(1, 'HMW_071');
    $b->WithGroundUnitForPlayer(1, 'LAW_174', true); });
$check(($scoreOf('normal', $play) ?? 1) < 0.0, '4B: with no enemy unit, Ravage is not played');
// HMW_100 Torrent (game 1438045 — the bot Torrented its own Turret): every style puts the tokens on an ENEMY.
$build(function ($b) { $b->MyLeader('HMW_003', true); $b->MyBase('HMW_033', 15); $b->FillResourcesForPlayer(1, 'SOR_095', 5);
    $b->WithCardInHandForPlayer(1, 'HMW_100');
    $b->WithGroundUnitForPlayer(1, 'LAW_097', true); $b->WithGroundUnitForPlayer(1, 'LAW_118', true); $b->WithSpaceUnitForPlayer(1, 'JTL_033', true);
    $b->WithGroundUnitForPlayer(2, 'HMW_213', false); $b->WithGroundUnitForPlayer(2, 'LOF_096', true); $b->WithSpaceUnitForPlayer(2, 'ASH_203', true); });
$act(1, 10002, $play);
foreach (['normal', 'aggro', 'control'] as $style) $check(str_starts_with(strval($stack($style)), 'their'), "4C [$style]: Torrent's Weakness goes on an ENEMY unit");
// SEC_163 Outer Rim Constable ("you may defeat an upgrade"): the Weakness removed is MINE; with only an enemy Weakness it declines.
$constable = function (bool $mineWeak) use ($build, $act, $stack, $play) {
    $build(function ($b) use ($mineWeak) { $b->MyLeader('SOR_014', false); $b->FillResourcesForPlayer(1, 'SOR_095', 5); $b->WithCardInHandForPlayer(1, 'SEC_163');
        $b->WithGroundUnitForPlayer(1, 'LAW_174', true);
        if ($mineWeak) $b->WithUpgradesOnGroundUnitForPlayer(1, 0, [GameStateBuilder::Upgrade('HMW_T02', 1)]);
        $b->WithGroundUnitForPlayer(2, 'SOR_095', true); $b->WithUpgradesOnGroundUnitForPlayer(2, 0, [GameStateBuilder::Upgrade('HMW_T02', 1)]); });
    $act(1, 10002, $play);
    return strval($stack('normal'));
};
$check(str_starts_with($constable(true), 'myGroundArena-0') && $constable(false) === 'PASS', "4D: Constable removes MY Weakness, never the enemy's");
// The hard constraint (_SWUBotWeaknessEnemyOnly, BotLegalActions.php): deployed Hemlock's On Attack Weakness — enemy units only, else decline.
$hemlockOnAttack = function (bool $enemyUnits) use (&$gameName, $build, $act) {
    $build(function ($b) use ($enemyUnits) {
        $b->MyLeader('HMW_003', true, true, true, 'unit');
        $b->WithGroundUnitForPlayer(1, 'LOF_070', false); $b->WithSpaceUnitForPlayer(1, 'ASH_194', false);
        if ($enemyUnits) { $b->WithGroundUnitForPlayer(2, 'SOR_095', true); $b->WithGroundUnitForPlayer(2, 'SEC_082', true); }
    });
    global $playerID; $playerID = 1;
    $hem = null; foreach (GetGroundArena(1) as $i => $u) if ($u->CardID === 'HMW_003') $hem = "myGroundArena-$i";
    $act(1, 10002, "$hem!FSM!");
    $l = SWUBotLegalActions($gameName, 1);
    if (($l['decisionTooltip'] ?? '') === 'Choose_an_attack_target') { $act(1, 100, 'theirBase-0'); $l = SWUBotLegalActions($gameName, 1); }
    return $l;
};
$l = $hemlockOnAttack(true);
$got = $ids($l['actions']);
$check(($l['decisionTooltip'] ?? '') === 'Give_a_Weakness_token_to_a_unit' && !empty($got) && empty(array_filter($got, fn($c) => !str_starts_with($c, 'their'))),
    '4E: with enemy units, the only legal Weakness answers are enemy units: ' . implode(',', $got));
$l = $hemlockOnAttack(false);
$check($ids($l['actions']) === ['PASS'], '4E: with no enemy unit, the only answer is to decline');
$only = [['cardID' => 'myGroundArena-0'], ['cardID' => 'mySpaceArena-0']];
$check(_SWUBotWeaknessEnemyOnly('MZCHOOSE', 'Give_a_Weakness_token_to_a_unit_without_one', $only, 1) === $only,
    '4E: a MANDATORY give with no enemy and no decline keeps its answers (no stall)');

// ══ 5. NEVER MISS A WINNING SPLIT (Bug #1126, game 1485163): JTL_143 Devastator's 4 indirect vs a base on 4 HP — all 4 to the base ════
$build(function ($b) {
    $b->MyLeader('HMW_003', false, false, true); $b->MyBase('HMW_027'); $b->FillResourcesForPlayer(1, 'LAW_097', 8);
    $b->WithCardInHandForPlayer(1, 'JTL_143'); $b->TheirBase('LAW_021', 23);
    $b->WithGroundUnitForPlayer(2, 'LAW_039'); $b->WithGroundUnitForPlayer(2, 'LAW_038');
});
$act(1, 10002, $play);
for ($i = 0; $i < 4; $i++) {
    $l = SWUBotLegalActions($gameName, 1);
    if (($l['kind'] ?? '') !== 'decision') break;
    $p = SWUBotHeuristicChoose('softcontrol', (array)$l['actions'], $l, '');
    $act(1, intval($p['mode'] ?? 100), strval($p['cardID'] ?? ''));
}
$check(SWUBaseRemainingHp(2) <= 0, '5: a split that can finish the base takes the win; their base has ' . SWUBaseRemainingHp(2));

// ══ 6. THE BOT STOPS WHEN THE GAME IS OVER (live game 183227: it kept attacking after "P1 wins the game") ═════════════════════════════
$build(function ($b) { $b->WithActivePlayer(2); $b->WithGroundUnitForPlayer(2, 'SOR_095', true); });
AddGlobalEffects(1, 'SWU_MODE_BOTPRACTICE');
SetSWUBotPlayers([2]);
$check(SWUGameMode() === 'botpractice' && BotControllerPendingPlayerForClient() === 2, '6 fixture: the bot owes a move while the game is on');
SWUDeclareGameWinner(1);
$r = ProcessBotControllerStep(1, 'SWUSim', $gameName);
$check(BotControllerPendingPlayerForClient() === 0 && empty($r['applied']) && ($r['retryable'] ?? true) === false, '6: after a winner, the bot owes nothing and a poll applies nothing');

// ══ 7. A LOOKAHEAD PLAN NEVER AIMS A HOSTILE ANSWER AT MY OWN UNIT FOR NO REASON ══════════════════════════════════════════════════════
// 7A (game 1647080): break-lethal planned deployed Hemlock's attack; every Weakness answer TIED on the clocks and the plan kept the first
// listed — the bot's own Anakin. A tied step lets the fallback choose among the tied answers.
$hemBoard = function () use ($build) {
    $build(function ($b) {
        $b->MyLeader('HMW_003', true, true, true, 'unit', 2); $b->MyBase('HMW_033'); $b->FillResourcesForPlayer(1, 'SOR_095', 6);
        $b->WithGroundUnitForPlayer(1, 'LOF_070', false); $b->WithSpaceUnitForPlayer(1, 'ASH_194', false, 1);
        $b->TheirLeader('HMW_008', false, true, true, 'unit');
        $b->WithGroundUnitForPlayer(2, 'SEC_T01', false); $b->WithGroundUnitForPlayer(2, 'SEC_082', true);
        $b->WithGroundUnitForPlayer(2, 'SEC_T01', true, 0, 0, 'SENTINEL^SEC_082'); $b->WithGroundUnitForPlayer(2, 'SEC_T01', true, 0, 0, 'SENTINEL^SEC_082');
    });
    $b = &GetBase(1); $b[0]->Damage = 16;
};
foreach (['softcontrol', 'midrange', 'hyperaggro'] as $style) {
    $hemBoard();
    global $playerID; $playerID = 1;
    for ($i = 0; $i < 3; $i++) {
        $l = SWUBotLegalActions($gameName, 1);
        $p = SWUBotHeuristicChoose($style, (array)$l['actions'], $l, '');
        $act(1, intval($p['mode'] ?? 100), strval($p['cardID'] ?? ''));
    }
    $own = 0;
    foreach (array_merge(GetGroundArena(1), GetSpaceArena(1)) as $u) if (empty($u->removed)) $own += count(array_filter((array)($u->Subcards ?? []), fn($s) => ($s->CardID ?? '') === 'HMW_T02'));
    $check($own === 0, "7A [$style]: no friendly unit carries a Weakness token after the planned line ($own)");
}
// 7B (leader audit 2026-10-08): a plan step whose answers were CUT SHORT (only my own unit tried — the bridge lists mine first) is not
// followed blindly: LOF_009 Darth Maul's ping goes to an enemy.
$maulAbility = ['playerID' => 1, 'mode' => 10001, 'cardID' => $ability];
$maulBoard = function () use ($build) {
    $build(function ($b) {
        $b->MyLeader('LOF_009', true); $b->MyBase('LOF_020'); $b->WithForceForPlayer(1); $b->WithCurrentRoundBeing(4);
        for ($k = 0; $k < 3; $k++) $b->WithGroundUnitForPlayer(1, 'SOR_095', false);
        $b->WithSpaceUnitForPlayer(2, 'SOR_225', true); $b->WithGroundUnitForPlayer(2, 'SOR_095', false);
        for ($k = 0; $k < 30; $k++) { $b->WithCardInDeckForPlayer(1, 'SOR_095'); $b->WithCardInDeckForPlayer(2, 'SOR_095'); }
    });
};
$read = fn() => ['oppClock' => SWUBotClock(2, 1), 'myClock' => SWUBotClock(1, 2)];
$score = fn(array $r) => $r['oppClock'] * 1000 + ($r['oppClock'] - $r['myClock']);
$maulBoard();
$line = SWUBotLookaheadBest(1, $maulAbility, $read, $score, SWU_BOT_LOOKAHEAD_DEPTH, 2);
$check(!empty(($line['_path'][0] ?? [])['truncated']), '7B fixture: the cut-short plan step is marked truncated');
$maulBoard();
$act(1, 10001, $ability);
$GLOBALS['SWUBotPlan'][1] = $line['_path'];
$check(str_starts_with(strval($stack('midrange')), 'their'), '7B: the truncated plan is overruled — the ping goes to an enemy');

bot_test_finish();

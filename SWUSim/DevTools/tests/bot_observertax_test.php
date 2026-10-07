<?php
// An attack that loses my unit while the opponent controls an HK-47 costs my base the ping (feature 'observertax', p41).
// FOUND 2026-10-07 in a human game (NininTCG Hemlock Red vs a Wicket Green guest, R11): the guest's Wicket attacked HK-47
// (LOF_130 — "When an enemy unit is defeated: Deal 1 damage to its controller's base") and died, and that ping was the
// game-winning damage. The bot's 'trade' / 'die' branches priced only the lost body; 'observerfirst' (p31) covers the HK-47
// OWNER's side, nothing covered the victim's. A death that pings my base to 0 loses the game: never.
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php -d apc.enable_cli=1 -d xdebug.mode=off SWUSim/DevTools/tests/bot_observertax_test.php
require __DIR__ . '/fixtures/bot_test_bootstrap.php';
include_once './SWUSim/BotLegalActions.php';
include_once './SWUSim/BotHeuristic.php';
$check(SWUBotVariantDisabled('no-observertax') === ['observertax'], 'observertax is switchable');
$off = function () { $GLOBALS['SWUBotDisabledFeatures'] = ['observertax']; };
$on  = function () { $GLOBALS['SWUBotDisabledFeatures'] = []; };

// The log's attacker: Wicket (ASH_034, 3/3 Saboteur, "can't attack bases") can trade with their Pre Vizsla (TS26_74, 4/3) — a
// 1-drop for a 3-drop. Their HK-47 sits beside him. $myBaseLeft = my base's remaining HP (LAW_022 has 27).
$board = function (int $myBaseLeft, bool $hk = true, string $mine = 'ASH_034') {
    return function ($b) use ($myBaseLeft, $hk, $mine) { $b->MyLeader('HMW_014', false); $b->MyBase('LAW_022', 27 - $myBaseLeft);
        $b->FillResourcesForPlayer(1, 'SOR_095', 6);
        $b->WithGroundUnitForPlayer(1, $mine, true);
        $b->WithGroundUnitForPlayer(2, 'TS26_74', false);
        if ($hk) $b->WithGroundUnitForPlayer(2, 'LOF_130', false);
        $b->WithInitiativePlayerBeing(2); $b->WithInitiativeClaimed(); };
};
$value = function () {
    $W = SWUBotWeights('aggro', 1);
    return SWUBotTargetValue(SWUBotViewForMz(1, 'myGroundArena-0'), SWUBotViewForMz(1, 'theirGroundArena-0'), $W);
};
// Wicket's attack-target prompt: which enemy unit the bot sends him into.
$target = function (string $variant = '') use ($raiseAttack, $botCtx, &$gameName) {
    $raiseAttack(1, 'myGroundArena-0');
    $ctx = $botCtx('aggro');
    $p = SWUBotHeuristicChoose('aggro', $ctx['actions'], SWUBotLegalActions($gameName, 1), $variant);
    return strval($p['cardID'] ?? 'null');
};

$build($board(1));
$check(SWUBotCombatOutcome(SWUBotViewForMz(1, 'myGroundArena-0'), SWUBotViewForMz(1, 'theirGroundArena-0')) === 'trade',
    'fixture: Wicket and Pre Vizsla trade');
$check(_SWUBotObserverPings(1) === 1, 'their HK-47 pings my base 1 for each of my units defeated');
$off(); $vOff = $value(); $on(); $vOn = $value();
$check($vOff > 0, '@no-observertax: the trade looked good (the reported blind spot); got ' . round($vOff, 3));
$check($vOn < -10, 'at 1 base HP the trade loses the game: never; got ' . round($vOn, 3));
$build($board(1));
$check(($t = $target()) !== 'theirGroundArena-0', '[aggro] at 1 base HP Wicket is not sent into the trade; got ' . $t);
$build($board(1));
$check(($t = $target('no-observertax')) === 'theirGroundArena-0', '[aggro] @no-observertax: Wicket traded into the ping (the reported mistake); got ' . $t);

// Out of lethal range the ping is just a point of base damage: the trade is still made, a point cheaper.
$build($board(15));
$W = SWUBotWeights('aggro', 1);
$off(); $vOff = $value(); $on(); $vOn = $value();
$check(abs(($vOff - $vOn) - $W['base']) < 1e-9, 'at 15 base HP the ping costs one point of base damage; got ' . round($vOff - $vOn, 3));
$check($vOn > 0, 'at 15 base HP the trade is still worth making (not "never"); got ' . round($vOn, 3));

// A 'die' attack pays the ping too: a Wicket with 2 damage (1 HP left) into HK-47 dies and leaves it standing.
$build(function ($b) { $b->MyLeader('HMW_014', false); $b->MyBase('LAW_022', 12); $b->FillResourcesForPlayer(1, 'SOR_095', 6);
    $b->WithGroundUnitForPlayer(1, 'ASH_034', true, 2); $b->WithGroundUnitForPlayer(2, 'LOF_130', false); });
$W = SWUBotWeights('aggro', 1);
$wk = SWUBotViewForMz(1, 'myGroundArena-0'); $hk = SWUBotViewForMz(1, 'theirGroundArena-0');
$check(SWUBotCombatOutcome($wk, $hk) === 'die', 'fixture: the damaged Wicket dies into HK-47');
$off(); $a = SWUBotTargetValue($wk, $hk, $W); $on(); $b = SWUBotTargetValue($wk, $hk, $W);
$check(abs(($a - $b) - $W['base']) < 1e-9, 'a losing attack pays the ping as well; got ' . round($a - $b, 3));

// No HK-47: no tax.
$build($board(1, false));
$check(_SWUBotObserverPings(1) === 0, 'no observer in play: no ping');
$off(); $vOff = $value(); $on(); $vOn = $value();
$check(abs($vOff - $vOn) < 1e-9, 'no observer: the feature changes nothing');

// A kill that keeps my unit alive feeds nothing: Cham Syndulla (JTL_164, 5/4) into HK-47 itself (HK-47 deals 2 of his 4).
$build($board(1, true, 'JTL_164'));
$W = SWUBotWeights('aggro', 1);
$cham = SWUBotViewForMz(1, 'myGroundArena-0'); $hk = SWUBotViewForMz(1, 'theirGroundArena-1');
$check(SWUBotCombatOutcome($cham, $hk) === 'kill-survive', 'fixture: Cham kills HK-47 and survives');
$off(); $a = SWUBotTargetValue($cham, $hk, $W); $on(); $b = SWUBotTargetValue($cham, $hk, $W);
$check(abs($a - $b) < 1e-9, 'a kill-survive costs no ping');

bot_test_finish();

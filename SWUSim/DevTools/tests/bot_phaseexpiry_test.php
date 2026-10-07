<?php
// A "+N/+N for this phase" buff expires at the end of the phase, and the damage stays (feature 'phaseexpiry', p41).
// FOUND 2026-10-07 in a human game (NininTCG Hemlock Red vs a Wicket Green guest, R4): Cassian Andor (LAW_056, 4/4) carried two
// Weakness tokens (2/2), then C-3PO (HMW_255) gave him +2/+2 for this phase (4/4). Ninin's HK-47 (2/4) attacked him anyway: HK
// died, Cassian took 2 — and when the buff expired Cassian was a 2/2 with 2 damage and died. A 2-drop for a 4-drop and his
// "deal 2 damage to a base" engine. The bot's combat outcome read the CURRENT HP (buff included), called it 'die', and priced
// the attack as a pure loss.
// The mirror: MY buffed unit that survives the combat dies at the phase end too, so its "kill-survive" is really a trade.
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php -d apc.enable_cli=1 -d xdebug.mode=off SWUSim/DevTools/tests/bot_phaseexpiry_test.php
require __DIR__ . '/fixtures/bot_test_bootstrap.php';
include_once './SWUSim/BotLegalActions.php';
include_once './SWUSim/BotHeuristic.php';
$check(SWUBotVariantDisabled('no-phaseexpiry') === ['phaseexpiry'], 'phaseexpiry is switchable');
$off = function () { $GLOBALS['SWUBotDisabledFeatures'] = ['phaseexpiry']; };
$on  = function () { $GLOBALS['SWUBotDisabledFeatures'] = []; };

// The live shape (R4): my HK-47 against their double-Weakened Cassian under C-3PO's +2/+2.
$cassian = function (string $effects) {
    return function ($b) use ($effects) { $b->MyLeader('HMW_003', false); $b->FillResourcesForPlayer(1, 'SOR_095', 5);
        $b->WithGroundUnitForPlayer(1, 'LOF_130', true);
        $b->WithGroundUnitForPlayer(2, 'LAW_056', true, 0, 0, $effects);
        $b->WithUpgradesOnGroundUnitForPlayer(2, 0, [GameStateBuilder::Upgrade('HMW_T02', 2), GameStateBuilder::Upgrade('HMW_T02', 2)]); };
};
$build($cassian('HMW_255-2-2^0'));
$W = SWUBotWeights('softcontrol', 1);   // after a board is loaded (it reads the arenas)
$hk = SWUBotViewForMz(1, 'myGroundArena-0'); $cas = SWUBotViewForMz(1, 'theirGroundArena-0');
$check($cas['power'] === 4 && $cas['hp'] === 4 && $cas['remaining'] === 4, 'fixture: the buffed, double-Weakened Cassian reads 4/4; got '
    . $cas['power'] . '/' . $cas['hp']);
$check(SWUBotCombatOutcome($hk, $cas) === 'die', 'fixture: in combat alone HK-47 dies and Cassian survives');
$check(_SWUBotPhaseExpiryHp($cas['obj']) === 2, 'the +2/+2 for this phase takes 2 HP away at the phase end');
$on();  $v = SWUBotTargetValue($hk, $cas, $W);
$off(); $vOff = SWUBotTargetValue($hk, $cas, $W); $on();
$check($vOff < 0, '@no-phaseexpiry: the attack was priced as a pure loss (the reported blind spot); got ' . round($vOff, 3));
$check($v > 0, 'the 2 damage kills Cassian when the buff expires: the attack is a trade worth making; got ' . round($v, 3));

// The same attack with no buff on Cassian is an ordinary kill: unchanged by the feature.
$build($cassian('-'));
$hk = SWUBotViewForMz(1, 'myGroundArena-0'); $cas = SWUBotViewForMz(1, 'theirGroundArena-0');
$check(_SWUBotPhaseExpiryHp($cas['obj']) === 0, 'no phase buff: nothing expires');
$on(); $a = SWUBotTargetValue($hk, $cas, $W); $off(); $b = SWUBotTargetValue($hk, $cas, $W); $on();
$check(abs($a - $b) < 1e-9, 'no phase buff: the feature changes nothing');

// A buff that lasts the ROUND (survives the phase-end expiry) is not counted.
$build($cassian('HMW_255-2-2@round^0'));
$check(_SWUBotPhaseExpiryHp(SWUBotViewForMz(1, 'theirGroundArena-0')['obj']) === 0, 'a round-duration buff does not expire at the phase end');

// Too little damage for the expiry to kill (a 1-power attacker leaves Cassian at 1 HP after the buff goes): still a bounce/die.
$build(function ($b) { $b->MyLeader('HMW_003', false); $b->FillResourcesForPlayer(1, 'SOR_095', 5);
    $b->WithGroundUnitForPlayer(1, 'HMW_062', true);   // Nuvo Vindi 1/4
    $b->WithGroundUnitForPlayer(2, 'LAW_056', true, 0, 0, 'HMW_255-2-2^0');
    $b->WithUpgradesOnGroundUnitForPlayer(2, 0, [GameStateBuilder::Upgrade('HMW_T02', 2), GameStateBuilder::Upgrade('HMW_T02', 2)]); });
$nv = SWUBotViewForMz(1, 'myGroundArena-0'); $cas = SWUBotViewForMz(1, 'theirGroundArena-0');
$on(); $a = SWUBotTargetValue($nv, $cas, $W); $off(); $b = SWUBotTargetValue($nv, $cas, $W); $on();
$check(abs($a - $b) < 1e-9, '1 damage on a 2-HP-after-expiry unit kills nothing: unchanged');

// The mirror: MY attacker under a +2/+2 for this phase survives the combat but dies when the buff expires.
// My Cassian (2 Weakness, +2/+2 = 4/4) attacks their HK-47 (2/4): kills it, takes 2 — and is a 2/2 with 2 damage at the phase end.
$build(function ($b) { $b->MyLeader('HMW_003', false); $b->FillResourcesForPlayer(1, 'SOR_095', 5);
    $b->WithGroundUnitForPlayer(1, 'LAW_056', true, 0, 0, 'HMW_255-2-2^0');
    $b->WithUpgradesOnGroundUnitForPlayer(1, 0, [GameStateBuilder::Upgrade('HMW_T02', 1), GameStateBuilder::Upgrade('HMW_T02', 1)]);
    $b->WithGroundUnitForPlayer(2, 'LOF_130', true); });
$cas = SWUBotViewForMz(1, 'myGroundArena-0'); $hk = SWUBotViewForMz(1, 'theirGroundArena-0');
$check(SWUBotCombatOutcome($cas, $hk) === 'kill-survive', 'fixture: in combat alone my Cassian kills HK-47 and survives');
$on(); $a = SWUBotTargetValue($cas, $hk, $W); $off(); $b = SWUBotTargetValue($cas, $hk, $W); $on();
$check($a < $b, 'my buffed attacker dies at the phase end: priced as a trade, below the clean kill; got ' . round($a, 3) . ' vs ' . round($b, 3));

// End to end: HK-47's attack-target prompt on the live board (their base is the other choice).
$build($cassian('HMW_255-2-2^0'));
$raiseAttack(1, 'myGroundArena-0');
$ctx = $botCtx('softcontrol');
$targets = array_map(fn($a) => strval($a['cardID']), $ctx['actions']);
$check(in_array('theirGroundArena-0', $targets, true), 'fixture: the attack asks for a target, Cassian among them; got ' . implode(' ', $targets));
$pick = SWUBotHeuristicChoose('softcontrol', $ctx['actions'], SWUBotLegalActions($gameName, 1), '');
$check(strval($pick['cardID'] ?? '') === 'theirGroundArena-0', '[softcontrol] HK-47 attacks the buffed Cassian; got ' . strval($pick['cardID'] ?? 'null'));

bot_test_finish();

<?php
// Feature 'shieldtrader' (p37) — a cheap unit whose worth is its Shield (printed Shielded, cost <= 2: JTL_032 Director Krennic's unit)
// is a FIGHTER while it carries the Shield and FODDER once it is gone. Owner, Krennic Splash follow-up 2026-10-06:
//   "while it is shielded, the Krennic (JTL) unit is more valuable. so use it to either soften up sentinels, kill weenies, trade shields
//    (eg. Imperial Armored Commando), etc."
//   "[without the Shield] it is better as fodder. if it has no shield token on it, and it is safe to hit base, then hit base to chip for 2.
//    then sac it"
// Traced (60 Krennic Splash vs Ahsoka Blue games): the shielded unit attacked in 42 of 110 chances and hit the BASE in 25 of the 42; and
// Krennic's Credit Action sacrificed it 17 times, shielded or not. The owner's five recorded games: it comes down R1 and attacks every round.
//   · attack: while it carries a Shield token, popping an enemy Shield is priced as the Shield is worth ('splitpop'), not one chip;
//   · sacrifice: while shielded it costs its full value, Shield included (kept); unshielded, its value − 0.5 (below a same-value body) — but a 0-power token
//     still goes first. Owner, same day: "unshielded krennic vs a Spy, i'd sac the Spy since it is weaker on defense (0 power)".
//     ('unusedsac' already prefers a unit that has attacked, so it chips first, then goes.)
// The Shield a trade pops is priced by the owner's 'splitpop' rule (+P per Shield; a 1-HP unit's Shield is worth one chip): a shielded
// 3/3 is worth trading into, a shielded 3/1 (Lepi Lookout) is not — "1 indirect ping or 1 Weakness token can clear that unit".
// Fixtures (dictionary-checked): JTL_032 Director Krennic (unit, 2/2, Shielded) · SOR_T02 Shield · LAW_038 Lepi Lookout 3/1 Shielded ·
//   LAW_037 Han Solo 1/1 · SEC_T01 Spy 0/2 · ASH_116 Ant Droid · LAW_008 Director Krennic (leader) · LAW_020 · ASH_009 · SOR_095 Battlefield Marine 3/3
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php -d apc.enable_cli=1 -d xdebug.mode=off SWUSim/DevTools/tests/bot_shieldtrader_test.php
require __DIR__ . '/fixtures/bot_test_bootstrap.php';
foreach (['BotLegalActions', 'Custom/BotLookahead', 'Custom/BotEvaluator', 'Rl/CardTags', 'Custom/BotStyles', 'Custom/BotResourcing',
          'Custom/BotGuides', 'Custom/BotFallback', 'Custom/BotRules', 'BotHeuristic'] as $f) include_once "./SWUSim/$f.php";
$check(SWUBotVariantDisabled('no-shieldtrader') === ['shieldtrader'], 'shieldtrader is switchable');
$check(in_array('shieldtrader', SWUBotFeatureGroups()['p37'] ?? [], true), 'shieldtrader is in group p37');
// Seat 1: Krennic leader, the Krennic unit (ready; Shield token if $shield) + $mine; seat 2: Ahsoka with $theirs ([card, shielded]).
$board = function (bool $shield, array $theirs, array $mine = [], bool $leaderReady = false, bool $kReady = true) use ($build) {
    $build(function ($b) use ($shield, $theirs, $mine, $leaderReady, $kReady) {
        $b->MyLeader('LAW_008', $leaderReady); $b->MyBase('LAW_020'); $b->TheirLeader('ASH_009', false);
        $b->WithGroundUnitForPlayer(1, 'JTL_032', $kReady);
        if ($shield) $b->WithUpgradesOnGroundUnitForPlayer(1, 0, [GameStateBuilder::Upgrade('SOR_T02', 1)]);
        foreach ($mine as $c) $b->WithGroundUnitForPlayer(1, $c, false);
        foreach ($theirs as $i => [$c, $sh]) {
            $b->WithGroundUnitForPlayer(2, $c, false);
            if ($sh) $b->WithUpgradesOnGroundUnitForPlayer(2, $i, [GameStateBuilder::Upgrade('SOR_T02', 2)]);
        }
    });
};
// The Krennic unit's attack target, chosen by the whole stack at the real prompt.
$target = function (string $variant) use ($raiseAttack, &$gameName) {
    $raiseAttack(1, 'myGroundArena-0');
    $l = SWUBotLegalActions($gameName, 1);
    $p = SWUBotHeuristicChoose('softcontrol', (array)$l['actions'], $l, $variant);
    return strval($p['cardID'] ?? '');
};
// A) SHIELD TRADE: shielded Krennic unit vs a shielded Battlefield Marine (3/3). Today: the base. Fixed: the Marine (both Shields go).
$board(true, [['SOR_095', true]]);
$check(SWUBotUnits(1)[0]['shields'] === 1 && SWUBotUnits(2)[0]['shields'] === 1, 'A fixture: both carry a Shield');
$check($target('no-shieldtrader') === 'theirBase-0', 'A fixture: today the shielded unit chips the base; got ' . $target('no-shieldtrader'));
$board(true, [['SOR_095', true]]);
$check($target('') === 'theirGroundArena-0', 'A: it trades Shields with the Marine; got ' . $target(''));
// A2) A shielded 3/1 (Lepi Lookout): its Shield is one chip by 'splitpop' — not worth the Krennic unit's Shield; still the base.
$board(true, [['LAW_038', true]]);
$check($target('') === 'theirBase-0', 'A2: a 1-HP unit\'s Shield is not a trade; got ' . $target(''));
// A3) KILL A WEENIE: an unshielded Han Solo (1/1) dies to it (the shipped bot already takes this; pinned so the chip price can't undo it).
$board(true, [['LAW_037', false]]);
$check($target('') === 'theirGroundArena-0', 'A3: it kills the 1/1; got ' . $target(''));
// B) WITHOUT its Shield: the base (owner: "hit base to chip for 2") — exactly as before.
$board(false, [['SOR_095', true]]);
$bOff = $target('no-shieldtrader');
$board(false, [['SOR_095', true]]);
$check($bOff === 'theirBase-0' && $target('') === $bOff, 'B: unshielded — unchanged; got ' . $target('') . ' vs ' . $bOff);
// C) SACRIFICE to Krennic's Credit Action, after its attack (exhausted). $theirs: their units (a ready Marine dooms 2-HP bodies).
$sac = function (bool $shield, string $other, string $variant, array $theirs = []) use ($board, $act, &$gameName) {
    $board($shield, $theirs, [$other], true, false);
    $act(1, 10001, 'myLeader-0!CustomInput!LeaderAbility');
    $l = SWUBotLegalActions($gameName, 1);
    if (($l['decisionTooltip'] ?? '') !== 'Defeat_a_friendly_unit_to_create_a_Credit') return 'no prompt: ' . ($l['decisionTooltip'] ?? '');
    $p = SWUBotHeuristicChoose('softcontrol', (array)$l['actions'], $l, $variant);
    return strval(SWUBotViewForMz(1, strval($p['cardID'] ?? ''))['cardID'] ?? '?');
};
// C1) Shielded, beside a Battlefield Marine (3/3): today the Krennic unit goes; fixed, it is kept ("more valuable").
$check($sac(true, 'SOR_095', 'no-shieldtrader') === 'JTL_032', 'C1 fixture: today the shielded Krennic unit is the Credit; got ' . $sac(true, 'SOR_095', 'no-shieldtrader'));
$check($sac(true, 'SOR_095', '') === 'SOR_095', 'C1: shielded, the Krennic unit is kept; got ' . $sac(true, 'SOR_095', ''));
// C2) Unshielded, beside a Spy token (0/2): today the Krennic unit goes ("When Defeated" read off its text); fixed, the 0-power Spy.
$check($sac(false, 'SEC_T01', 'no-shieldtrader') === 'JTL_032', 'C2 fixture: today the bare Krennic unit is the Credit; got ' . $sac(false, 'SEC_T01', 'no-shieldtrader'));
$check($sac(false, 'SEC_T01', '') === 'SEC_T01', 'C2: unshielded vs a Spy — the Spy goes; got ' . $sac(false, 'SEC_T01', ''));
// C3) …the same with both DOOMED by their ready Marine (both priced 0.5 today: a tie, the first listed went).
$M = [['SOR_095', false]];
$check($sac(false, 'SEC_T01', 'no-shieldtrader', $M) === 'JTL_032', 'C3 fixture: today, both doomed, the Krennic unit goes; got ' . $sac(false, 'SEC_T01', 'no-shieldtrader', $M));
$check($sac(false, 'SEC_T01', '', $M) === 'SEC_T01', 'C3: both doomed — the Spy goes; got ' . $sac(false, 'SEC_T01', '', $M));
// C4) Unshielded, beside a Battlefield Marine: the Krennic unit is the fodder ("then sac it").
$check($sac(false, 'SOR_095', '') === 'JTL_032', 'C4: unshielded vs a Marine — the Krennic unit goes; got ' . $sac(false, 'SOR_095', ''));
// C5) An Ant Droid (When Defeated: draw) is still cheaper fodder than the bare Krennic unit.
$check($sac(false, 'ASH_116', '') === 'ASH_116', 'C5: Ant Droid still goes first; got ' . $sac(false, 'ASH_116', ''));

// D) The prices themselves (list order breaks a tie in the prompts above): shielded > a same-value Marine > bare > a Spy token.
foreach ([true, false] as $sh) {
    $board($sh, [], ['SOR_095', 'SEC_T01'], false, false);
    [$k, $m, $spy] = SWUBotUnits(1);
    $px[$sh ? 'shielded' : 'bare'] = SWUBotSacrificeCost($k); $px['marine'] = SWUBotSacrificeCost($m); $px['spy'] = SWUBotSacrificeCost($spy);
}
$check($px['shielded'] > $px['marine'] && $px['marine'] > $px['bare'] && $px['bare'] > $px['spy'], 'D: shielded > Marine > bare > Spy; got ' . json_encode($px));

bot_test_finish();

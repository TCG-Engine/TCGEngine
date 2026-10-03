<?php
// Feature 'breach' (p26, shipped 2026-10-03; '@no-breach' = the stack before it) — a Sentinel kill is worth the base damage it UNLOCKS for my other units. Owner ruling
// 2026-10-03: "if the opponent has a high HP sentinel, then try to buff something low-value so that it can crash in and
// make way for the other units to attack. however, if that sentinel can be cleared with a unit post-buff that survives,
// take that line if and only if it enables more damage from the other units". Ties go to the survive line (owner).
// Spec: docs/superpowers/specs/2026-10-03-swusim-bot-sentinel-breach-design.md
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php -d apc.enable_cli=1 -d xdebug.mode=off SWUSim/DevTools/tests/bot_breach_test.php
require __DIR__ . '/fixtures/bot_test_bootstrap.php';
include_once './SWUSim/BotLegalActions.php';
include_once './SWUSim/BotHeuristic.php';
$check(SWUBotVariantDisabled('no-breach') === ['breach'], 'breach is switchable; got ' . json_encode(SWUBotVariantDisabled('no-breach')));
$check(in_array('breach', SWUBotFeatureGroups()['p26'], true) && !in_array('breach', SWU_BOT_PROPOSALS, true), 'breach is in group p26 and no longer a proposal');

// Play my turn on $board under $variant: the bot acts, the opponent passes whenever it is up. Returns
// [attack order as cardIDs, base damage dealt].
$turn = function (callable $board, string $variant) use (&$gameName, $act, $build) {
    $build($board);
    $base0 = SWUBaseRemainingHp(2);
    $order = [];
    for ($i = 0; $i < 12; $i++) {
        $legal = SWUBotLegalActions($gameName, 1);
        if (($legal['kind'] ?? '') === 'waiting-on-other-seat') { $act(2, 10001, 'myHealth-0!CustomInput!Pass'); continue; }
        $p = SWUBotHeuristicChoose('hyperaggro', (array)$legal['actions'], $legal, $variant);
        $id = strval($p['cardID'] ?? '');
        if (($legal['kind'] ?? '') !== 'decision') {
            if ($id === '' || str_contains($id, '!Pass') || str_contains($id, 'Initiative')) break;
            if (preg_match('/^(my(Ground|Space)Arena-\d+)!FSM!$/', $id, $m)) { $v = SWUBotViewForMz(1, $m[1]); $order[] = $v['cardID'] ?? '?'; }
        }
        $act(1, intval($p['mode'] ?? 10001), $id);   // a pick carries its own mode (decisions included), as bot_buffspread_test does
    }
    return [$order, $base0 - SWUBaseRemainingHp(2)];
};
$falchion = fn($b, int $dmg = 2) => $b->WithGroundUnitForPlayer(2, 'TWI_065', false, $dmg);   // 4/6 Sentinel, exhausted (still guards)

// A) CRASH. The Bandit (4/1) kills the Falchion (4 left) and dies; the two Marines then reach the base: 6.
$boardA = function ($b) use ($falchion) { $falchion($b); $b->WithGroundUnitForPlayer(1, 'ASH_190', true);
    $b->WithGroundUnitForPlayer(1, 'SOR_095', true); $b->WithGroundUnitForPlayer(1, 'SOR_095', true); };
[$order, $dmg] = $turn($boardA, '');
// ⚠ Base damage is checked as AT LEAST the units' total: CommonSetup's leader adds its own point with its ability.
$check($order === ['ASH_190', 'SOR_095', 'SOR_095'], 'A: the cheap Bandit crashes first, then both Marines attack; got ' . json_encode($order));
$check($dmg >= 6, "A: both Marines reach the base after the breach (>= 3 + 3); got $dmg");

// B) TIE → SURVIVE. Specters (4/5) kills and survives, opening Bandit 4 + Marine 3 = 7; the Bandit's crash opens Specters
// 4 + Marine 3 = 7. Equal → the survive line (no loss term). Then Bandit + Marine hit the base: 7.
$boardB = function ($b) use ($falchion) { $falchion($b); $b->WithGroundUnitForPlayer(1, 'LOF_066', true);
    $b->WithGroundUnitForPlayer(1, 'ASH_190', true); $b->WithGroundUnitForPlayer(1, 'SOR_095', true); };
[$order, $dmg] = $turn($boardB, '');
$check(($order[0] ?? '') === 'LOF_066', 'B: on a tie the survivor clears the Sentinel; got ' . json_encode($order));
$check($dmg >= 7, "B: Bandit + Marine reach the base (>= 4 + 3); got $dmg");

// C) THE FLIP (also spec test F). The Rathtar (8/5) would kill and survive, opening only the Bandit's 4; the Bandit's crash
// opens the Rathtar's 8. Today (no breach) the Rathtar clears it and 4 reaches the base; with breach the Bandit crashes
// and 8 does.
$boardC = function ($b) use ($falchion) { $falchion($b); $b->WithGroundUnitForPlayer(1, 'LOF_168', true); $b->WithGroundUnitForPlayer(1, 'ASH_190', true); };
[$orderOff, $dmgOff] = $turn($boardC, 'no-breach');
$check($orderOff === ['LOF_168', 'ASH_190'], 'C fixture: today the Rathtar clears it, then the Bandit swings; got ' . json_encode($orderOff));
[$order, $dmg] = $turn($boardC, '');
$check(($order[0] ?? '') === 'ASH_190', 'C: the crash opens more (8 > 4), so the Bandit crashes; got ' . json_encode($order));
$check($dmg - $dmgOff === 4, "C: the Rathtar's 8 reaches the base instead of the Bandit's 4 (+4); got $dmg vs $dmgOff");

// D) NO BREACH. A second Sentinel (an undamaged Falchion) still guards; a Shielded Falchion survives one hit. Both play
// exactly as today.
$boardD1 = function ($b) use ($boardC) { $boardC($b); $b->WithGroundUnitForPlayer(2, 'TWI_065', false); };
$boardD2 = function ($b) use ($boardC) { $boardC($b); $b->WithUpgradesOnGroundUnitForPlayer(2, 0, [GameStateBuilder::Upgrade('SOR_T02', 2)]); };
foreach (['D1 two Sentinels' => $boardD1, 'D2 a Shielded Sentinel' => $boardD2] as $name => $bd) {
    [$off] = $turn($bd, 'no-breach'); [$on] = $turn($bd, '');
    $check($on === $off, "$name: unchanged by breach; got " . json_encode([$on, $off]));
}

// E) The term itself, read off the views on board C's variants (the function production calls).
$val = function (callable $board, string $attCard, bool $on) use ($build) {
    $build($board);
    $GLOBALS['SWUBotDisabledFeatures'] = $on ? [] : ['breach'];
    $att = null; foreach (SWUBotUnits(1) as $v) if ($v['cardID'] === $attCard) $att = $v;
    $def = null; foreach (SWUBotUnits(2) as $v) if ($v['cardID'] === 'TWI_065') $def = $v;
    $out = SWUBotTargetValue($att, $def, SWUBotWeights('hyperaggro', 1));
    $GLOBALS['SWUBotDisabledFeatures'] = [];
    return $out;
};
$delta = fn(callable $board, string $attCard) => round($val($board, $attCard, true) - $val($board, $attCard, false), 4);
// E1 a Saboteur behind (SHD_218 Resourceful Pursuers 5/6) already ignores Sentinel: nothing is opened for it.
$check($delta(function ($b) use ($falchion) { $falchion($b); $b->WithGroundUnitForPlayer(1, 'ASH_190', true); $b->WithGroundUnitForPlayer(1, 'SHD_218', true); }, 'ASH_190') == 0.0,
    'E1: a Saboteur is not counted as opened damage');
// E2 an EXHAUSTED Rathtar cannot attack this round: nothing opened.
$check($delta(function ($b) use ($falchion) { $falchion($b); $b->WithGroundUnitForPlayer(1, 'ASH_190', true); $b->WithGroundUnitForPlayer(1, 'LOF_168', false); }, 'ASH_190') == 0.0,
    'E2: an exhausted unit is not counted as opened damage');
// E3 an enemy SPACE Sentinel (ASH_109 T-6 Shuttle 1974) neither blocks the ground breach nor changes it: 0.60 x 8.
$check($delta(function ($b) use ($boardC) { $boardC($b); $b->WithSpaceUnitForPlayer(2, 'ASH_109', false); }, 'ASH_190') == round(SWUBotWeights('hyperaggro', 1)['base'] * 8, 4),
    'E3: a Sentinel in the other arena does not block the breach (base x the Rathtar\'s 8)');
// E4 the attacker is not counted in its own opened damage: the Rathtar attacking opens only the Bandit's 4 → 0.60 x 4.
$check($delta($boardC, 'LOF_168') == round(SWUBotWeights('hyperaggro', 1)['base'] * 4, 4), 'E4: the attacker is excluded (base x the Bandit\'s 4)');
// E5 an opened unit attacks with its Raid: Saw Gerrera (TWI_150, 4 power, Raid 2) behind the crash opens 4 + 2 = 6.
$check($delta(function ($b) use ($falchion) { $falchion($b); $b->WithGroundUnitForPlayer(1, 'ASH_190', true); $b->WithGroundUnitForPlayer(1, 'TWI_150', true); }, 'ASH_190')
    == round(SWUBotWeights('hyperaggro', 1)['base'] * 6, 4), 'E5: an opened unit counts its Raid (base x (4 + 2))');

// H) Switch off = today: board A/B/C picks match the probed numbers' order.
foreach (['A' => [$boardA, 'ASH_190'], 'B' => [$boardB, 'LOF_066'], 'C' => [$boardC, 'LOF_168']] as $name => [$bd, $first]) {
    [$off] = $turn($bd, 'no-breach');
    $check(($off[0] ?? '') === $first, "H $name: with the switch off the first attacker is today's ($first); got " . json_encode($off));
}
// G) buffspread under breach — Ahsoka's flip turn vs a space Sentinel (Luke ASH DV's T-6 Shuttle 1974, ASH_109 2/6) with
// only ships beside her. Today the whole space arena is written off. With breach, a Sentinel that one buffed ship can
// defeat is no longer written off, so the flip turn may open space. Floor: never less base damage than without breach,
// and no "+N for this phase" on an exhausted unit.
$plots = function ($b) { $b->MyLeader('ASH_009'); $b->FillResourcesForPlayer(1, 'SOR_095', 4);
    $b->WithControlledResourceForPlayer(1, 'SEC_099', 1); $b->WithControlledResourceForPlayer(1, 'SEC_111', 1);
    $b->FillResourcesForPlayer(2, 'SOR_095', 3); };
$boardG = function ($b) use ($plots) { $plots($b);
    $b->WithSpaceUnitForPlayer(1, 'JTL_095', true); $b->WithSpaceUnitForPlayer(1, 'ASH_201', true);
    $b->WithSpaceUnitForPlayer(2, 'ASH_109', false); $b->WithGroundUnitForPlayer(2, 'SOR_095', false); };
$flip = function (callable $board, string $variant) use (&$gameName, $act, $build) {
    $build($board);
    $base0 = SWUBaseRemainingHp(2); $buffs = [];
    $act(1, 10001, 'myLeader-0!CustomInput!DeployLeader:Unit');
    for ($i = 0; $i < 40; $i++) {
        $legal = SWUBotLegalActions($gameName, 1);
        if (($legal['kind'] ?? '') === 'waiting-on-other-seat') { $act(2, 10001, 'myHealth-0!CustomInput!Pass'); continue; }
        $p = SWUBotHeuristicChoose('hyperaggro', (array)$legal['actions'], $legal, $variant);
        $id = strval($p['cardID'] ?? '');
        if (($legal['kind'] ?? '') === 'decision') {
            if (str_starts_with(strval($legal['decisionTooltip'] ?? ''), 'Give_') && str_contains($id, 'Arena-')) {
                $v = SWUBotViewForMz(1, $id); $buffs[] = [$v['cardID'] ?? '?', !empty($v['ready'])];
            }
            $act(1, intval($p['mode'] ?? 10001), $id); continue;
        }
        if ($id === '' || str_contains($id, '!Pass') || str_contains($id, 'Initiative')) break;
        $act(1, intval($p['mode'] ?? 10001), $id);
    }
    return [$base0 - SWUBaseRemainingHp(2), $buffs];
};
[$dmgOff] = $flip($boardG, 'no-breach');
[$dmgOn, $buffsOn] = $flip($boardG, '');
$check($dmgOn >= $dmgOff, "G: the flip turn with breach deals no less than without ($dmgOn vs $dmgOff)");
$check($dmgOn > $dmgOff, "G: …and breaching space deals MORE ($dmgOn vs $dmgOff)");
$check(empty(array_filter($buffsOn, fn($b) => !$b[1] && $b[0] !== 'ASH_009')), 'G: no phase buff on an exhausted unit; got ' . json_encode($buffsOn));

// G2) The PLAN and the SUPPORT ATTACKER under breach. A Warzone Lieutenant (ground 2/2, clear arena) competes with the
// A-Wing (space 3/2, behind the T-6). Without breach the space ship is written off, so the weaker ground unit is planned.
// With breach the A-Wing (Jar Jar's +2 + the lent Raid 2 = 7 >= the T-6's 6) is a valid plan and the stronger one.
$boardG2 = function ($b) use ($boardG) { $boardG($b); $b->WithGroundUnitForPlayer(1, 'SHD_110', true); };
$flipPicks = function (callable $board, string $variant) use (&$gameName, $act, $build) {
    $build($board);
    $act(1, 10001, 'myLeader-0!CustomInput!DeployLeader:Unit');
    $picks = [];
    for ($i = 0; $i < 12; $i++) {
        $legal = SWUBotLegalActions($gameName, 1);
        if (($legal['kind'] ?? '') !== 'decision') break;
        $p = SWUBotHeuristicChoose('hyperaggro', (array)$legal['actions'], $legal, $variant);
        $id = strval($p['cardID'] ?? '');
        $v = str_contains($id, 'Arena-') ? SWUBotViewForMz(1, $id) : null;
        $picks[strval($legal['decisionTooltip'] ?? '')] ??= $v['cardID'] ?? $id;
        $act(1, intval($p['mode'] ?? 10001), $id);
    }
    return $picks;
};
$off = $flipPicks($boardG2, 'no-breach'); $on = $flipPicks($boardG2, '');
$check(($off['Give_another_friendly_unit_+2/+2?'] ?? '') === 'SHD_110', 'G2 fixture: without breach Jar Jar buffs the clear-arena Lieutenant; got ' . json_encode($off));
$check(($on['Give_another_friendly_unit_+2/+2?'] ?? '') === 'JTL_095', 'G2: with breach Jar Jar buffs the A-Wing that can breach; got ' . json_encode($on));
$check(($on['Choose_a_unit_to_attack_with'] ?? '') === 'JTL_095', 'G2: …and the A-Wing makes the Support attack; got ' . json_encode($on));

// G3) NOTHING TO OPEN (review, Important #1). Board G2 without the Open Circle Ace: the A-Wing can still kill the T-6, but
// no other ship of mine would then reach the base — the ruling's "if and only if it enables more damage from the other
// units" fails, so the space Sentinel stays a write-off and the clear-arena Lieutenant is the plan, exactly as without breach.
$boardG3 = function ($b) use ($plots) { $plots($b);
    $b->WithSpaceUnitForPlayer(1, 'JTL_095', true); $b->WithGroundUnitForPlayer(1, 'SHD_110', true);
    $b->WithSpaceUnitForPlayer(2, 'ASH_109', false); $b->WithGroundUnitForPlayer(2, 'SOR_095', false); };
$off = $flipPicks($boardG3, 'no-breach'); $on = $flipPicks($boardG3, '');
$check(($off['Give_another_friendly_unit_+2/+2?'] ?? '') === 'SHD_110', 'G3 fixture: without breach Jar Jar buffs the Lieutenant; got ' . json_encode($off));
$check(($on['Give_another_friendly_unit_+2/+2?'] ?? '') === 'SHD_110', 'G3: a breach that opens nothing is no plan — Jar Jar still buffs the Lieutenant; got ' . json_encode($on));
$check(($on['Choose_a_unit_to_attack_with'] ?? '') === 'SHD_110', 'G3: …and the Lieutenant makes the Support attack; got ' . json_encode($on));

// TS) TWIN SUNS (review, Important #2). Killing seat 2's only ground Sentinel opens nothing when another live opponent's
// ground already lets my units reach ITS base. 3 and 4 seats (memory: Twin Suns tests need both).
$tsDelta = function (string $order, array $sentinelSeats) use ($build) {
    $board = function ($b) use ($order, $sentinelSeats) {
        $b->WithSeatOrder($order); $b->WithLiveSeats($order);
        $b->WithGroundUnitForPlayer(2, 'TWI_065', false, 2);
        foreach ($sentinelSeats as $s) $b->WithGroundUnitForPlayer($s, 'TWI_065', false);
        $b->WithGroundUnitForPlayer(1, 'ASH_190', true); $b->WithGroundUnitForPlayer(1, 'LOF_168', true); };
    $d = [];
    foreach ([true, false] as $on) {
        $build($board);
        $GLOBALS['SWUBotDisabledFeatures'] = $on ? [] : ['breach'];
        $att = null; foreach (SWUBotUnits(1) as $v) if ($v['cardID'] === 'ASH_190') $att = $v;
        $def = null; foreach (SWUBotUnits(2) as $v) if ($v['cardID'] === 'TWI_065') $def = $v;
        $d[] = SWUBotTargetValue($att, $def, SWUBotWeights('hyperaggro', 1));
        $GLOBALS['SWUBotDisabledFeatures'] = [];
    }
    return round($d[0] - $d[1], 4);
};
$full = round(SWUBotWeights('hyperaggro', 1)['base'] * 8, 4);
$check($tsDelta('123', []) == 0.0, 'TS1 3P: seat 3 has no ground Sentinel — its base is already reachable, nothing opened');
$check($tsDelta('123', [3]) == $full, 'TS2 3P: seat 3 is guarded too — breaching seat 2 opens the Rathtar\'s 8');
$check($tsDelta('1234', [3]) == 0.0, 'TS3 4P: seat 4 has no ground Sentinel — nothing opened');
$check($tsDelta('1234', [3, 4]) == $full, 'TS4 4P: seats 3 and 4 guarded — breaching seat 2 opens the Rathtar\'s 8');

// TS5) The Raid that Support LENDS comes from the unit GIVING Support, not from whichever leader of mine has the most (a Twin
// Suns seat has two). Ahsoka deploys (Support) beside a deployed Cad Bane (SHD_014, Raid 2) with the Royal Starship in play
// (each friendly leader unit gains Raid 2): Ahsoka lends 2, not Cad Bane's 4.
$build(function ($b) {
    $b->WithSeatOrder('123'); $b->WithLiveSeats('123');
    $b->MyLeader('ASH_009'); $b->MyLeader2('SHD_014', true, true);
    $b->FillResourcesForPlayer(1, 'SOR_095', 4);
    $b->WithControlledResourceForPlayer(1, 'SEC_099', 1); $b->WithControlledResourceForPlayer(1, 'SEC_111', 1);
    $b->FillResourcesForPlayer(2, 'SOR_095', 3); $b->FillResourcesForPlayer(3, 'SOR_095', 3);
    $b->WithSpaceUnitForPlayer(1, 'JTL_095', true); $b->WithGroundUnitForPlayer(2, 'SOR_095', false); });
$act(1, 10001, 'myLeader-0!CustomInput!DeployLeader:Unit');
$lentAtJarJar = null; $lentAtAttacker = null; $cadRaid = null;
for ($i = 0; $i < 12; $i++) {
    $legal = SWUBotLegalActions($gameName, 1);
    if (($legal['kind'] ?? '') !== 'decision') break;
    $tip = strval($legal['decisionTooltip'] ?? '');
    if ($tip === 'Give_another_friendly_unit_+2/+2?') {
        $lentAtJarJar = _SWUBotSupportLentRaid(1);
        foreach (SWUBotUnits(1) as $v) if ($v['cardID'] === 'SHD_014') $cadRaid = $v['attackPower'] - $v['power'];
    }
    if ($tip === 'Choose_a_unit_to_attack_with') {
        $src = strval(explode('|', strval(($legal['following'] ?? [])[0] ?? ''))[1] ?? '');
        $lentAtAttacker = _SWUBotSupportLentRaid(1, $src);
    }
    $p = SWUBotHeuristicChoose('hyperaggro', (array)$legal['actions'], $legal, '');
    $act(1, intval($p['mode'] ?? 10001), strval($p['cardID'] ?? ''));
}
$check($cadRaid === 4, 'TS5 fixture: the deployed Cad Bane attacks with Raid 4 (his 2 + the Starship\'s 2); got ' . json_encode($cadRaid));
$check($lentAtJarJar === 2, 'TS5: at Jar Jar\'s prompt the lent Raid is Ahsoka\'s 2, not Cad Bane\'s 4; got ' . json_encode($lentAtJarJar));
$check($lentAtAttacker === 2, 'TS5: at the Support-attacker prompt, read from the Support source, it is 2; got ' . json_encode($lentAtAttacker));

// G4) Only the unit that MAKES the Support attack borrows the leader's Raid. At Jar Jar's prompt the plan is a ready ground
// Battlefield Marine (3/3, clear arena; it out-HPs the A-Wing on the tie at 3 power), so the space A-Wing (3/2) behind the
// T-6 (6 HP) gets only Jar Jar's +2 = 5: it cannot breach, and its buff is a write-off. Crediting it the lent Raid 2 (= 7)
// read it as a clear-arena breach.
$build(function ($b) use ($plots) { $plots($b);
    $b->WithGroundUnitForPlayer(1, 'SOR_095', true); $b->WithSpaceUnitForPlayer(1, 'JTL_095', true); $b->WithSpaceUnitForPlayer(1, 'ASH_201', true);
    $b->WithSpaceUnitForPlayer(2, 'ASH_109', false); $b->WithGroundUnitForPlayer(2, 'SOR_095', false); });
$act(1, 10001, 'myLeader-0!CustomInput!DeployLeader:Unit');
$scores = null; $planCard = null;
for ($i = 0; $i < 8; $i++) {
    $legal = SWUBotLegalActions($gameName, 1);
    if (($legal['kind'] ?? '') !== 'decision') break;
    if (strval($legal['decisionTooltip'] ?? '') === 'Give_another_friendly_unit_+2/+2?') {
        $ctx = $botCtx('hyperaggro');
        $GLOBALS['SWUBotDisabledFeatures'] = [];
        $planUid = _SWUBotSupportPlanUid(1, 2);
        foreach (SWUBotUnits(1) as $v) if ($v['uid'] === $planUid) $planCard = $v['cardID'];
        foreach (['myGroundArena-1', 'mySpaceArena-0'] as $mz) $scores[SWUBotViewForMz(1, $mz)['cardID'] ?? $mz] = _SWUBotBuffSpreadScore($ctx, 1, $mz);
        break;
    }
    $p = SWUBotHeuristicChoose('hyperaggro', (array)$legal['actions'], $legal, '');
    $act(1, intval($p['mode'] ?? 10001), strval($p['cardID'] ?? ''));
}
$check($planCard === 'SOR_095', 'G4 fixture: the Support plan is the ground Marine; got ' . json_encode($planCard));
$check(($scores['JTL_095'] ?? 1.0) < 0.1, 'G4: the non-planned A-Wing is not credited the lent Raid — its buff stays a write-off; got ' . json_encode($scores));
bot_test_finish();

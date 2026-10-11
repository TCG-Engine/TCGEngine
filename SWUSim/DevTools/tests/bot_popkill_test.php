<?php
// Feature 'popkill' (p27, shipped 2026-10-03; '@no-popkill' = the stack before it) — popping a Shield is worth the KILL it sets up. Research 2026-10-03 (traces of 1,670
// games): an upgraded Sentinel walls the bot's ready units in 18% of games, nearly always a cheap printed Sentinel +
// Shielded (ASH_048 Imperial Armored Commando 4/3, Krennic Blue; LAW_118 Droid Laser Turret 2/1, Mando Colossus). A pop
// was priced chip − 0.05 × power (≤ 0 for any 3+ power hyperaggro attacker) or as a plain loss when the popper dies, with
// the follow-up kill never credited — so in game e071 a ready Ahsoka (5/6) and Jar Jar (2/1) left an exhausted Commando
// standing and ended the round unused. Owner: "go" on the research's recommendation.
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php -d apc.enable_cli=1 -d xdebug.mode=off SWUSim/DevTools/tests/bot_popkill_test.php
require __DIR__ . '/fixtures/bot_test_bootstrap.php';
include_once './SWUSim/BotLegalActions.php';
include_once './SWUSim/BotHeuristic.php';
$check(SWUBotVariantDisabled('no-popkill') === ['popkill'], 'popkill is switchable; got ' . json_encode(SWUBotVariantDisabled('no-popkill')));
$check(in_array('popkill', SWUBotFeatureGroups()['p27'], true) && !in_array('popkill', SWU_BOT_PROPOSALS, true), 'popkill is in group p27 and no longer a proposal');

// My turn under $variant (the opponent passes): [the units I attacked with, in order; the enemy ground units left].
$turn = function (callable $board, string $variant) use (&$gameName, $act, $build) {
    $build($board);
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
        $act(1, intval($p['mode'] ?? 10001), $id);
    }
    return [$order, array_map(fn($v) => $v['cardID'], array_values(array_filter(SWUBotUnits(2), fn($v) => $v['arena'] === 'Ground')))];
};
// The wall: an exhausted Imperial Armored Commando (4/3, Sentinel) with its Shield (the builder skips the Shielded trigger).
$wall = function ($b, int $shields = 1) {
    $b->WithGroundUnitForPlayer(2, 'ASH_048', false);
    $b->WithUpgradesOnGroundUnitForPlayer(2, 0, array_fill(0, $shields, GameStateBuilder::Upgrade('SOR_T02', 2)));
};

// A) THE e071 SHAPE. A Supreme Council Aide (2/2) dies popping the Shield (4 power vs 2 HP); the Ravenous Rathtar (8/5) then
// kills the Commando and survives. Today the WRONG unit pops: the Aide's pop is a pure loss, so only rule 8 ("don't end the
// round with a free attack unused") sends the Rathtar in — it pops the Shield, the Aide (2) cannot finish 3 HP, the wall stands.
$boardA = function ($b) use ($wall) { $wall($b); $b->WithGroundUnitForPlayer(1, 'SEC_237', true); $b->WithGroundUnitForPlayer(1, 'LOF_168', true); };
[$orderOff, $leftOff] = $turn($boardA, 'no-popkill');
$check($orderOff === ['LOF_168'] && in_array('ASH_048', $leftOff, true), 'A fixture: today the Rathtar pops and the wall stands; got ' . json_encode([$orderOff, $leftOff]));
[$order, $left] = $turn($boardA, '');
$check($order === ['SEC_237', 'LOF_168'], 'A: the cheap Aide pops the Shield first, then the Rathtar attacks; got ' . json_encode($order));
$check(!in_array('ASH_048', $left, true), 'A: …and the Commando is defeated; got ' . json_encode($left));

// B) NO FOLLOW-UP KILL: two Aides (2 power each) cannot finish a 3-HP Commando after the pop — nothing to credit.
$boardB = function ($b) use ($wall) { $wall($b); $b->WithGroundUnitForPlayer(1, 'SEC_237', true); $b->WithGroundUnitForPlayer(1, 'SEC_237', true); };
[$off] = $turn($boardB, 'no-popkill'); [$on] = $turn($boardB, '');
$check($on === $off, 'B: with no unit able to kill after the pop, nothing changes; got ' . json_encode([$on, $off]));

// C) TWO SHIELDS: one pop leaves a Shield — no kill follows this attack, nothing to credit.
$boardC = function ($b) use ($wall) { $wall($b, 2); $b->WithGroundUnitForPlayer(1, 'SEC_237', true); $b->WithGroundUnitForPlayer(1, 'LOF_168', true); };
[$off] = $turn($boardC, 'no-popkill'); [$on] = $turn($boardC, '');
$check($on === $off, 'C: two Shields — unchanged; got ' . json_encode([$on, $off]));

// D) The follow-up must be READY: an exhausted Rathtar cannot kill this round.
$boardD = function ($b) use ($wall) { $wall($b); $b->WithGroundUnitForPlayer(1, 'SEC_237', true); $b->WithGroundUnitForPlayer(1, 'LOF_168', false); };
[$off] = $turn($boardD, 'no-popkill'); [$on] = $turn($boardD, '');
$check($on === $off, 'D: an exhausted follow-up earns nothing; got ' . json_encode([$on, $off]));

// E) The follow-up must be in the wall's ARENA: a space ship cannot attack a ground unit.
$boardE = function ($b) use ($wall) { $wall($b); $b->WithGroundUnitForPlayer(1, 'SEC_237', true); $b->WithSpaceUnitForPlayer(1, 'LOF_120', true); };
$GLOBALS['SWUBotDisabledFeatures'] = [];
$val = function (callable $board, bool $on) use ($build) {
    $build($board);
    $GLOBALS['SWUBotDisabledFeatures'] = $on ? [] : ['popkill'];
    $att = null; foreach (SWUBotUnits(1) as $v) if ($v['cardID'] === 'SEC_237') $att = $v;
    $def = null; foreach (SWUBotUnits(2) as $v) if ($v['cardID'] === 'ASH_048') $def = $v;
    $out = SWUBotTargetValue($att, $def, SWUBotWeights('hyperaggro', 1));
    $GLOBALS['SWUBotDisabledFeatures'] = [];
    return $out;
};
$check(round($val($boardE, true) - $val($boardE, false), 6) == 0.0, 'E: a follow-up in the other arena earns nothing');
// F) The credit is the follow-up's own value on the popped unit (Rathtar on an unshielded Commando), nothing more.
$build($boardA);
$W = SWUBotWeights('hyperaggro', 1);
$rath = null; foreach (SWUBotUnits(1) as $v) if ($v['cardID'] === 'LOF_168') $rath = $v;
$cmd = null; foreach (SWUBotUnits(2) as $v) if ($v['cardID'] === 'ASH_048') $cmd = $v;
$aide = null; foreach (SWUBotUnits(1) as $v) if ($v['cardID'] === 'SEC_237') $aide = $v;
$popped = $cmd; $popped['shields'] = 0; $popped['_popper'] = $aide['uid'];   // the popper is not opened by its own follow-up (I)
$expected = round(SWUBotTargetValue($rath, $popped, $W), 6);
$check(round($val($boardA, true) - $val($boardA, false), 6) == $expected, "F: the pop is credited exactly the Rathtar's kill on the popped Commando ($expected)");

// G) A popper that SURVIVES ('bounce'): a Battlefield Marine (3/3) pops a Shielded Droid Laser Turret (LAW_118, 2/1 Sentinel)
// and lives; the Rathtar then kills it. The Marine's pop is credited exactly the Rathtar's kill on the popped Turret.
$turret = function ($b) { $b->WithGroundUnitForPlayer(2, 'LAW_118', false); $b->WithUpgradesOnGroundUnitForPlayer(2, 0, [GameStateBuilder::Upgrade('SOR_T02', 2)]); };
$boardG = function ($b) use ($turret) { $turret($b); $b->WithGroundUnitForPlayer(1, 'SOR_095', true); $b->WithGroundUnitForPlayer(1, 'LOF_168', true); };
$valFor = function (callable $board, string $attCard, string $defCard, bool $on) use ($build) {
    $build($board);
    $GLOBALS['SWUBotDisabledFeatures'] = $on ? [] : ['popkill'];
    $att = null; foreach (SWUBotUnits(1) as $v) if ($v['cardID'] === $attCard) $att = $v;
    $def = null; foreach (SWUBotUnits(2) as $v) if ($v['cardID'] === $defCard) $def = $v;
    $out = [SWUBotCombatOutcome($att, $def), SWUBotTargetValue($att, $def, SWUBotWeights('hyperaggro', 1))];
    $GLOBALS['SWUBotDisabledFeatures'] = [];
    return $out;
};
$build($boardG);
$rath = null; foreach (SWUBotUnits(1) as $v) if ($v['cardID'] === 'LOF_168') $rath = $v;
$tur = null; foreach (SWUBotUnits(2) as $v) if ($v['cardID'] === 'LAW_118') $tur = $v;
$tur['shields'] = 0;
foreach (SWUBotUnits(1) as $v) if ($v['cardID'] === 'SOR_095') $tur['_popper'] = $v['uid'];   // see I
$expectG = round(SWUBotTargetValue($rath, $tur, SWUBotWeights('hyperaggro', 1)), 6);
[$outcome, $on] = $valFor($boardG, 'SOR_095', 'LAW_118', true); [, $off] = $valFor($boardG, 'SOR_095', 'LAW_118', false);
$check($outcome === 'bounce', "G fixture: the Marine's attack on the Shielded Turret is a bounce; got $outcome");
$check(round($on - $off, 6) == $expectG, "G: the surviving popper is credited the Rathtar's kill ($expectG); got " . round($on - $off, 6));

// H) The follow-up must be able to TARGET it: a Shielded NON-Sentinel (a Battlefield Marine) behind the Commando cannot be
// attacked while the Sentinel stands, so popping its Shield sets up nothing.
$boardH = function ($b) use ($wall) { $wall($b);
    $b->WithGroundUnitForPlayer(2, 'SOR_095', false); $b->WithUpgradesOnGroundUnitForPlayer(2, 1, [GameStateBuilder::Upgrade('SOR_T02', 2)]);
    $b->WithGroundUnitForPlayer(1, 'SEC_237', true); $b->WithGroundUnitForPlayer(1, 'LOF_168', true); };
[, $on] = $valFor($boardH, 'SEC_237', 'SOR_095', true); [, $off] = $valFor($boardH, 'SEC_237', 'SOR_095', false);
$check(round($on - $off, 6) == 0.0, 'H: a Shielded non-Sentinel behind a Sentinel earns no pop credit');

// B2) A follow-up that SURVIVES but does not kill (Gungi, LOF_093 2/5, vs the popped 3-HP Commando) chips for a positive
// value — but sets up no kill, so the Aide's pop earns nothing for it.
$boardB2 = function ($b) use ($wall) { $wall($b); $b->WithGroundUnitForPlayer(1, 'SEC_237', true); $b->WithGroundUnitForPlayer(1, 'LOF_093', true); };
[, $on] = $valFor($boardB2, 'SEC_237', 'ASH_048', true); [, $off] = $valFor($boardB2, 'SEC_237', 'ASH_048', false);
$check(round($on - $off, 6) == 0.0, 'B2: a follow-up that only chips earns no pop credit; got ' . round($on - $off, 6));

// I) The POPPER is not "opened" by its own follow-up. Breach (p26) values a Sentinel kill by the ready units it lets through;
// the Bandit that pops the Falchion's Shield has attacked (or died) by then, so the Rathtar's follow-up kill opens nothing
// for it. Found when p26 + p27 met: bot_breach_test D2 (a Shielded Sentinel) changed its attack order.
$boardI = function ($b) { $b->WithGroundUnitForPlayer(2, 'TWI_065', false, 2); $b->WithUpgradesOnGroundUnitForPlayer(2, 0, [GameStateBuilder::Upgrade('SOR_T02', 2)]);
    $b->WithGroundUnitForPlayer(1, 'ASH_190', true); $b->WithGroundUnitForPlayer(1, 'LOF_168', true); };
$build($boardI);
$rath = null; foreach (SWUBotUnits(1) as $v) if ($v['cardID'] === 'LOF_168') $rath = $v;
$fal = null; foreach (SWUBotUnits(2) as $v) if ($v['cardID'] === 'TWI_065') $fal = $v;
$fal['shields'] = 0;
$GLOBALS['SWUBotDisabledFeatures'] = ['breach'];   // the follow-up's value with nothing opened
$expectI = round(SWUBotTargetValue($rath, $fal, SWUBotWeights('hyperaggro', 1)), 6);
$GLOBALS['SWUBotDisabledFeatures'] = [];
[, $on] = $valFor($boardI, 'ASH_190', 'TWI_065', true); [, $off] = $valFor($boardI, 'ASH_190', 'TWI_065', false);
$check(round($on - $off, 6) == $expectI, "I: the Bandit's pop is credited the Rathtar's kill WITHOUT counting the Bandit as opened ($expectI); got " . round($on - $off, 6));
bot_test_finish();

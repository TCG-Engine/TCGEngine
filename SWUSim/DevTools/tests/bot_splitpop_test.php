<?php
// Feature 'splitpop' (p32, 2026-10-04; '@no-splitpop' = the stack before it) — in a damage SPLIT, popping a Shield is worth what
// the Shield was worth (_SWUBotShieldPopValue). FOUND 2026-10-04, game 1485163 (owner report: "overdid the damage on the Spy when
// it could have killed the Spy and popped shield"). ASH_148 Ninth Sister: "An opponent discards a card from their hand. You may
// deal damage equal to its cost divided as you choose among any number of units." P1 discarded JTL_032 Director Krennic (cost
// 2). P1 had a SEC_T01 Spy (0/2) with a Weakness token (1 HP left) and an ASH_048 Imperial Armored Commando holding a Shield.
// Best: 1 to the Spy (dies) + 1 to the Commando (Shield popped). The bot put 2 on the Spy: _SWUBotSplitScore skipped a Shielded
// target outright, so both splits scored the Spy kill alone and enumeration order broke the tie.
// Owner rule of thumb for the value (2026-10-04): a Shield ≈ +P (the unit's current power), more on a Sentinel; little on a
// 1-HP unit (a Weakness token / an indirect point clears it anyway) or when a Saboteur — in play, or Ambush in hand — ignores it.
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php -d apc.enable_cli=1 -d xdebug.mode=off SWUSim/DevTools/tests/bot_splitpop_test.php
require __DIR__ . '/fixtures/bot_test_bootstrap.php';
include_once './SWUSim/BotLegalActions.php';
include_once './SWUSim/BotHeuristic.php';
include_once './SWUSim/Custom/BotLookahead.php';
$check(SWUBotVariantDisabled('no-splitpop') === ['splitpop'], 'splitpop is switchable; got ' . json_encode(SWUBotVariantDisabled('no-splitpop')));
$check(in_array('splitpop', SWUBotFeatureGroups()['p32'] ?? [], true), 'splitpop is in group p32');

// The game's board from the BOT's seat (1): the opponent (2) holds only Krennic (cost 2 → a 2-point split) and has a Weakened
// Spy (1 HP left) + an Imperial Armored Commando (4/3 Sentinel), Shielded unless $shield is false. $extra adds to the board.
$board = function (bool $shield = true, ?callable $extra = null) {
    return function ($b) use ($shield, $extra) {
        $b->FillResourcesForPlayer(1, 'LOF_059', 10);
        $b->WithCardInHandForPlayer(1, 'ASH_148');
        $b->WithCardInHandForPlayer(2, 'JTL_032');
        $b->WithGroundUnitForPlayer(2, 'SEC_T01', false);
        $b->WithUpgradesOnGroundUnitForPlayer(2, 0, [GameStateBuilder::Upgrade('HMW_T02', 1)]);
        $b->WithGroundUnitForPlayer(2, 'ASH_048', false);
        if ($shield) $b->WithUpgradesOnGroundUnitForPlayer(2, 1, [GameStateBuilder::Upgrade('SOR_T02', 2)]);
        if ($extra) $extra($b);
    };
};
// Play Ninth Sister through the bot stack and return its answer to the split: [cardID(@mine) => amount], or null.
$split = function (callable $setup, string $variant = '') use ($build, $act, &$gameName) {
    $build($setup);
    $act(1, 10002, 'myHand-0!FSM!');
    for ($i = 0; $i < 6; $i++) {
        $legal = SWUBotLegalActions($gameName, 1);
        if (($legal['kind'] ?? '') !== 'decision') return null;
        $p = SWUBotHeuristicChoose('midrange', (array)$legal['actions'], $legal, $variant);
        if (strval($legal['decisionType'] ?? '') === 'MZSPLITASSIGN') {
            $out = [];
            foreach (explode(',', strval($p['cardID'] ?? '')) as $pair) {
                $b = explode(':', $pair);
                if (count($b) !== 2) continue;
                $v = SWUBotViewForMz(1, $b[0]);
                $out[($v['cardID'] ?? $b[0]) . (SWUBotIsEnemyMz(1, $b[0]) ? '' : '@mine')] = intval($b[1]);
            }
            ksort($out);
            return $out;
        }
        $act(1, intval($p['mode'] ?? 100), strval($p['cardID'] ?? ''));
    }
    return null;
};

// A) THE GAME: before splitpop, 2 on the Spy; with it, 1 kills the Spy and 1 pops the Commando's Shield.
$off = $split($board(), 'no-splitpop');
$check($off === ['SEC_T01' => 2], 'A fixture: the stack before splitpop overkills the Spy (2); got ' . json_encode($off));
$on = $split($board());
$check($on === ['ASH_048' => 1, 'SEC_T01' => 1], 'A: 1 kills the Spy, 1 pops the Commando\'s Shield; got ' . json_encode($on));

// B) MY OWN Shield costs what it is worth. The bot's own Ravenous Rathtar (8 power) carries a Shield and is a legal target
// ("any number of units"): popping it is the biggest number on the board, so a wrong sign would pick it first.
$mine = $split($board(true, fn($b) => $b->WithGroundUnitForPlayer(1, 'LOF_168', true)
                                         ->WithUpgradesOnGroundUnitForPlayer(1, 0, [GameStateBuilder::Upgrade('SOR_T02', 1)])));
$check($mine === ['ASH_048' => 1, 'SEC_T01' => 1], 'B: my own Shielded Rathtar is never hit; got ' . json_encode($mine));

// C) CONTROL — no Shield: still 1 + 1 (the kill, then a chip on the Commando), never 2 on the Spy.
$c = $split($board(false));
$check($c === ['ASH_048' => 1, 'SEC_T01' => 1], 'C: no Shield — 1 + 1; got ' . json_encode($c));

// D) THE VALUE — the owner's tiers, read straight off _SWUBotShieldPopValue on real views.
$W = SWUBotWeights('midrange', 1);
$val = function (callable $setup, string $cardID, int $seat = 2) use ($build, $W) {
    $build($setup);
    foreach (SWUBotUnits($seat) as $v) if ($v['cardID'] === $cardID) return _SWUBotShieldPopValue(1, $v, $W, $seat !== 1);
    return null;
};
$enemyShield = function (string $cid, ?callable $more = null) {
    return function ($b) use ($cid, $more) {
        $b->WithGroundUnitForPlayer(2, $cid, false);
        $b->WithUpgradesOnGroundUnitForPlayer(2, 0, [GameStateBuilder::Upgrade('SOR_T02', 2)]);
        if ($more) $more($b);
    };
};
$commando = $val($enemyShield('ASH_048'), 'ASH_048');   // 4/3 Sentinel
$marine   = $val($enemyShield('SOR_095'), 'SOR_095');   // 3/3, no Sentinel
$check(abs($commando - $W['chip'] * 4 * 1.5) < 1e-9, 'D1: a Shield on a 4-power Sentinel = chip × 4 × 1.5; got ' . $commando);
$check(abs($marine - $W['chip'] * 3) < 1e-9, 'D2: a Shield on a 3-power unit = chip × 3; got ' . $marine);
// The owner's example: a 3-POWER unit on 1 HP (a Marine with 2 damage) — power stays 3, so only the floor makes it one chip.
$oneHp = $val(function ($b) {
    $b->WithGroundUnitForPlayer(2, 'SOR_095', false, 2);
    $b->WithUpgradesOnGroundUnitForPlayer(2, 0, [GameStateBuilder::Upgrade('SOR_T02', 2)]);
}, 'SOR_095');
$check(abs($oneHp - $W['chip']) < 1e-9, 'D3: a 3-power, 1-HP unit\'s Shield (a Weakness/indirect point clears it) = one chip; got ' . $oneHp);
$sabIn = $val($enemyShield('ASH_048', fn($b) => $b->WithGroundUnitForPlayer(1, 'ASH_192', true)), 'ASH_048');
$check(abs($sabIn - $W['chip']) < 1e-9, 'D4: my Saboteur (Fennec Shand) in that arena ignores the Shield = one chip; got ' . $sabIn);
$sabHand = $val($enemyShield('ASH_048', fn($b) => $b->WithCardInHandForPlayer(1, 'ASH_192')), 'ASH_048');
$check(abs($sabHand - $W['chip']) < 1e-9, 'D5: an Ambush Saboteur (Fennec Shand) in my hand = one chip; got ' . $sabHand);
// A Saboteur in the OTHER arena cannot attack a ground unit: SHD_151 Valiant Assault Ship (space, Saboteur).
$build(fn($b) => $b->WithSpaceUnitForPlayer(1, 'SHD_151', true));
$ship = SWUBotUnits(1)[0] ?? [];
$check(!empty($ship['saboteur']) && ($ship['arena'] ?? '') === 'Space', 'D6 fixture: SHD_151 is a space Saboteur to the bot');
$sabSpace = $val($enemyShield('ASH_048', fn($b) => $b->WithSpaceUnitForPlayer(1, 'SHD_151', true)), 'ASH_048');
$check(abs($sabSpace - $commando) < 1e-9, 'D6: a Saboteur in the OTHER arena changes nothing; got ' . $sabSpace);
$myShield = function ($b) {
    $b->WithGroundUnitForPlayer(1, 'ASH_048', true);
    $b->WithUpgradesOnGroundUnitForPlayer(1, 0, [GameStateBuilder::Upgrade('SOR_T02', 1)]);
    $b->WithGroundUnitForPlayer(2, 'ASH_192', true);
};
$mineVsSab = $val($myShield, 'ASH_048', 1);
$check(abs($mineVsSab - $W['chip']) < 1e-9, 'D7: MY Shield vs an enemy Saboteur in play = one chip; got ' . $mineVsSab);

// E) TWO SHIELDS: one point pops ONE Shield (a target's share is one damage instance), so a second point on the same unit
// is wasted (owner: "don't waste 2 of the split on it. 1 ping is enough to pop one of the shields"). The split is "up to"
// (UPTO), so 1 is a legal answer; the only enemy is the double-Shielded Commando.
$two = $split(function ($b) {
    $b->FillResourcesForPlayer(1, 'LOF_059', 10);
    $b->WithCardInHandForPlayer(1, 'ASH_148');
    $b->WithCardInHandForPlayer(2, 'JTL_032');
    $b->WithGroundUnitForPlayer(2, 'ASH_048', false);
    $b->WithUpgradesOnGroundUnitForPlayer(2, 0, [GameStateBuilder::Upgrade('SOR_T02', 2), GameStateBuilder::Upgrade('SOR_T02', 2)]);
});
$check($two === ['ASH_048' => 1], 'E: two Shields — 1 point pops one, the second point is not wasted on it; got ' . json_encode($two));

// F) THE SAME PRICE IN A MULTI-PICK (owner, 2026-10-04): the opponent's ASH_053 Pre Vizsla (6/6) made three Mandalorian tokens
// (ASH_T01, 2/2, Shielded). JTL_140 IG-2000: "When Played: Deal 1 damage to each of up to 3 units" (MZMULTICHOOSE, not a split).
// "It would actually be better to pop 3 shields than to only pop 2 and ping 1 on Pre Vizsla. That doesn't do much good into a
// high hp unit." _SWUBotTargetScore priced a Shielded enemy at 0, so the 1-damage chip on Vizsla won the third slot.
$picks = function (string $variant) use ($build, $act, &$gameName) {
    $build(function ($b) {
        $b->FillResourcesForPlayer(1, 'LOF_059', 10);
        $b->WithCardInHandForPlayer(1, 'JTL_140');
        $b->WithGroundUnitForPlayer(2, 'ASH_053', false);
        for ($i = 1; $i <= 3; $i++) {
            $b->WithGroundUnitForPlayer(2, 'ASH_T01', false);
            $b->WithUpgradesOnGroundUnitForPlayer(2, $i, [GameStateBuilder::Upgrade('SOR_T02', 2)]);
        }
    });
    $act(1, 10002, 'myHand-0!FSM!');
    for ($i = 0; $i < 6; $i++) {
        $legal = SWUBotLegalActions($gameName, 1);
        if (($legal['kind'] ?? '') !== 'decision') return null;
        $p = SWUBotHeuristicChoose('midrange', (array)$legal['actions'], $legal, $variant);
        if (strval($legal['decisionType'] ?? '') === 'MZMULTICHOOSE') {
            $out = [];
            foreach (explode('&', strval($p['cardID'] ?? '')) as $mz) {
                if ($mz === '') continue;
                $v = SWUBotViewForMz(1, $mz);
                $out[] = ($v['cardID'] ?? $mz) . (SWUBotIsEnemyMz(1, $mz) ? '' : '@mine');
            }
            sort($out);
            return $out;
        }
        $act(1, intval($p['mode'] ?? 100), strval($p['cardID'] ?? ''));
    }
    return null;
};
$fOff = $picks('no-splitpop');
$check($fOff !== null && in_array('ASH_053', $fOff, true), 'F fixture: before splitpop the third ping goes on Pre Vizsla; got ' . json_encode($fOff));
$fOn = $picks('');
$check($fOn === ['ASH_T01', 'ASH_T01', 'ASH_T01'], 'F: all three Mandalorian Shields are popped; got ' . json_encode($fOn));

bot_test_finish();

<?php
// Part 20 'buffspread' — a Support leader's flip turn spreads its "+N for this phase" buffs so every one lands on a unit
// that still attacks. Owner rulings 2026-10-02, from Ninin's Ahsoka Yellow (ASH_009 Ahsoka Tano, Otoh Gunga) Karabast games:
//   "it's best to spread the buffs to get a +10 that turn. +2 Raid from Naboo ship onto the Supported unit, +2 from Jar Jar
//    to this or another unit, +2 Supported attack buffs someone else (or Ahsoka), then +2 Raid from Ahsoka attacking and
//    another +2 buff on her own On Attack"
//   "you also need to account for a potential Naboo ship Plot. the Raid 2 would put a 2 power unit + 2 jar jar + Raid 2 at 6,
//    so enough to buff Ahsoka after attack"
//   "if there are ground Sentinels, then don't waste a buff on the ground units, including Ahsoka leader. you will have most
//    likely gone wide in space, and can spread at least +8 in that arena"
// The cards: ASH_009 deployed = Support + "On Attack: you may give a unit with less power than this unit +2/+0 for this
// phase" (measured IN the attack, Raid included); SEC_111 Jar Jar Binks (Plot) "When Played: you may give another friendly
// unit +2/+2 for this phase"; SEC_099 Naboo Royal Starship (Plot) "Each friendly leader unit gains Raid 2 and Overwhelm" —
// lent to the Supported unit (Support lends gained abilities, owner ruling).
// The whole turn is played: the deploy (both Plots, the Support attack), the opponent passes, then the bot keeps acting.
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php -d apc.enable_cli=1 -d xdebug.mode=off SWUSim/DevTools/tests/bot_buffspread_test.php
require __DIR__ . '/fixtures/bot_test_bootstrap.php';
include_once './SWUSim/BotLegalActions.php';
include_once './SWUSim/BotHeuristic.php';
$check(SWUBotVariantDisabled('no-buffspread') === ['buffspread'], 'buffspread is switchable');
$check(in_array('buffspread', SWUBotFeatureGroups()['p20'], true), 'buffspread is in group p20');

// Plays the flip turn on $board under $variant. Returns [picks: [[tooltip, pick, cardID of the pick]], base damage, ok].
$flipTurn = function (callable $board, string $variant = '') use (&$gameName, $act, $build) {
    $build($board);
    $base0 = SWUBaseRemainingHp(2);
    $picks = [];
    $cardOf = function (string $mz) { global $playerID; $saved = $playerID; $playerID = 1; $o = str_contains($mz, 'Arena-') ? GetZoneObject($mz) : null; $playerID = $saved; return $o ? strval($o->CardID) : ''; };
    $drain = function () use (&$gameName, $act, &$picks, $variant, $cardOf) {
        for ($i = 0; $i < 20; $i++) {
            $legal = SWUBotLegalActions($gameName, 1);
            if (($legal['kind'] ?? '') !== 'decision') return;
            $p = SWUBotHeuristicChoose('hyperaggro', (array)$legal['actions'], $legal, $variant);
            $id = strval($p['cardID'] ?? 'PASS');
            $picks[] = [strval($legal['decisionTooltip'] ?? ''), $id, $cardOf($id)];
            $act(1, intval($p['mode'] ?? 10001), $id);
        }
    };
    $act(1, 10001, 'myLeader-0!CustomInput!DeployLeader:Unit');
    $drain();
    for ($i = 0; $i < 12; $i++) {   // the bot's remaining actions: attacks until it passes. The opponent passes each time
        $legal = SWUBotLegalActions($gameName, 1);   // it is up (an action of mine gives them another).
        if (($legal['kind'] ?? '') === 'waiting-on-other-seat') { $act(2, 10001, 'myHealth-0!CustomInput!Pass'); continue; }
        if (($legal['kind'] ?? '') === 'decision') { $drain(); continue; }
        $p = SWUBotHeuristicChoose('hyperaggro', (array)$legal['actions'], $legal, $variant);
        $id = strval($p['cardID'] ?? '');
        if ($id === '' || str_contains($id, '!Pass') || str_contains($id, 'Initiative')) break;
        $act(1, intval($p['mode'] ?? 10001), $id);
        $drain();
    }
    return [$picks, $base0 - SWUBaseRemainingHp(2)];
};
$pickFor = fn($picks, $tip) => array_values(array_filter($picks, fn($p) => $p[0] === $tip));
$JARJAR = 'Give_another_friendly_unit_+2/+2?';
$SUPPORT = 'Choose_a_unit_to_attack_with';
$LEND = 'Give_+2/+0_to_a_unit_with_less_power_than_this_unit';
// ⚠ The opponent gets resources, as in a real game: with none, rule 3 'initiative-for-lethal' reads "they cannot get a
// Sentinel" and takes the initiative mid-turn instead of attacking with Ahsoka — a separate question, not this rule's.
$plots = function ($b) { $b->MyLeader('ASH_009'); $b->FillResourcesForPlayer(1, 'SOR_095', 4);
    $b->WithControlledResourceForPlayer(1, 'SEC_099', 1); $b->WithControlledResourceForPlayer(1, 'SEC_111', 1);
    $b->FillResourcesForPlayer(2, 'SOR_095', 3); };

// A) THE +10 TURN. Typho (4) and Obi-Wan (3) beside Ahsoka (5): printed power 12, and five +2s that all land on attackers —
// Typho: Jar Jar +2 and the lent Raid 2 → 8, and his borrowed buff → Ahsoka; Ahsoka: +2 and Raid 2 → 9, her buff → Obi-Wan
// → 5. The enemy's lone unit is exhausted and no Sentinel: every attack goes to the base. 12 + 10 = 22.
$boardA = function ($b) use ($plots) { $plots($b);
    $b->WithGroundUnitForPlayer(1, 'SEC_098', true); $b->WithGroundUnitForPlayer(1, 'LOF_096', true); $b->WithGroundUnitForPlayer(2, 'SOR_095', false); };
[$picks, $dmg] = $flipTurn($boardA);
$check(($pickFor($picks, $JARJAR)[0][2] ?? '') === 'SEC_098', 'A: Jar Jar buffs the planned Support attacker (Typho), not Ahsoka; got ' . json_encode($pickFor($picks, $JARJAR)));
$check(($pickFor($picks, $SUPPORT)[0][2] ?? '') === 'SEC_098', 'A: Typho makes the Support attack; got ' . json_encode($pickFor($picks, $SUPPORT)));
$lend = $pickFor($picks, $LEND);
$check(($lend[0][2] ?? '') === 'ASH_009', 'A: the Supported attack\'s borrowed +2 goes on Ahsoka; got ' . json_encode($lend));
$check(($lend[1][2] ?? '') === 'LOF_096', 'A: Ahsoka\'s own +2 goes on the unit still to attack (Obi-Wan); got ' . json_encode($lend));
$check($dmg === 22, "A: the +10 turn — printed 12 + five +2s = 22 base damage; got $dmg");
[, $dmgOff] = $flipTurn($boardA, 'no-buffspread');
$check($dmgOff <= $dmg, "A @no-buffspread: never more ($dmgOff vs $dmg)");

// B) THE THIN BOARD — one other unit. @no-buffspread Jar Jar buffs Ahsoka, Typho (4 + Raid 2 = 6) cannot reach her at 7,
// and his +2 falls on the just-played, EXHAUSTED Jar Jar: a +2 lost. With the spread: Typho 8, Ahsoka 9 = 17.
$boardB = function ($b) use ($plots) { $plots($b); $b->WithGroundUnitForPlayer(1, 'SEC_098', true); $b->WithGroundUnitForPlayer(2, 'SOR_095', false); };
[$picks, $dmg] = $flipTurn($boardB);
[$picksOff, $dmgOff] = $flipTurn($boardB, 'no-buffspread');
$check(($pickFor($picks, $LEND)[0][2] ?? '') === 'ASH_009', 'B: the borrowed +2 reaches Ahsoka; got ' . json_encode($pickFor($picks, $LEND)));
$check($dmg === 17 && $dmg > $dmgOff, "B: the thin board deals 17, more than @no-buffspread; got $dmg vs $dmgOff");
$check(($pickFor($picksOff, $JARJAR)[0][2] ?? '') === 'ASH_009', 'B @no-buffspread: Jar Jar buffed Ahsoka (the reported line); got ' . json_encode($pickFor($picksOff, $JARJAR)));

// C) "a 2 power unit + 2 jar jar + Raid 2 at 6, so enough to buff Ahsoka": only an Open Circle Ace (space 2/2) beside her.
$boardC = function ($b) use ($plots) { $plots($b); $b->WithSpaceUnitForPlayer(1, 'ASH_201', true); $b->WithGroundUnitForPlayer(2, 'SOR_095', false); };
[$picks] = $flipTurn($boardC);
$check(($pickFor($picks, $JARJAR)[0][2] ?? '') === 'ASH_201', 'C: Jar Jar buffs the 2-power Ace (the Support attacker); got ' . json_encode($pickFor($picks, $JARJAR)));
$lend = $pickFor($picks, $LEND);
$check(($lend[0][2] ?? '') === 'ASH_009', 'C: the Ace at 2 + 2 + Raid 2 = 6 reaches Ahsoka (5); got ' . json_encode($lend));

// D) A GROUND SENTINEL (their Captain Typho, SEC_098): ground attacks cannot reach the base, so the plan and every buff go to
// space — Ahsoka included. Mine: Obi-Wan (ground 3), Phoenix Squadron A-Wing (space 3), Open Circle Ace (space 2).
$boardD = function ($b) use ($plots) { $plots($b);
    $b->WithGroundUnitForPlayer(1, 'LOF_096', true); $b->WithSpaceUnitForPlayer(1, 'JTL_095', true); $b->WithSpaceUnitForPlayer(1, 'ASH_201', true);
    $b->WithGroundUnitForPlayer(2, 'SEC_098', false); };
[$picks] = $flipTurn($boardD);
$check(($pickFor($picks, $JARJAR)[0][2] ?? '') === 'JTL_095', 'D: Jar Jar buffs the strongest SPACE unit; got ' . json_encode($pickFor($picks, $JARJAR)));
$check(($pickFor($picks, $SUPPORT)[0][2] ?? '') === 'JTL_095', 'D: the Support attack is made in space; got ' . json_encode($pickFor($picks, $SUPPORT)));
$groundBuffs = array_filter($pickFor($picks, $LEND), fn($p) => in_array($p[2], ['ASH_009', 'LOF_096'], true));
$spaceBuffs = array_filter($pickFor($picks, $LEND), fn($p) => in_array($p[2], ['JTL_095', 'ASH_201'], true));
$check(empty($groundBuffs) && !empty($spaceBuffs), 'D: no buff on a ground unit (Ahsoka included) — they go to space; got ' . json_encode($pickFor($picks, $LEND)));

// F) Jar Jar goes on the planned attacker even when it is NOT my most valuable unit: Phoenix Squadron A-Wing (3/2, value 2)
// over a T-6 Shuttle 1974 (2/6, value 4) — value alone would buff the T-6. The plan reads the power each unit ATTACKS with
// (a first draft used a Tribubble Bongo Sub as the "weaker" unit: it has Raid 3, attacks for 4, and was rightly picked).
$boardF = function ($b) use ($plots) { $plots($b);
    $b->WithSpaceUnitForPlayer(1, 'ASH_109', true); $b->WithSpaceUnitForPlayer(1, 'JTL_095', true); $b->WithGroundUnitForPlayer(2, 'SOR_095', false); };
[$picks] = $flipTurn($boardF);
$check(($pickFor($picks, $JARJAR)[0][2] ?? '') === 'JTL_095', 'F: Jar Jar buffs the strongest attacker (A-Wing), not the most valuable unit (T-6); got ' . json_encode($pickFor($picks, $JARJAR)));
$check(($pickFor($picks, $LEND)[0][2] ?? '') === 'ASH_009', 'F: …so the borrowed +2 reaches Ahsoka; got ' . json_encode($pickFor($picks, $LEND)));

// G) The plan looks in the CLEAR arena first: their ground Sentinel blocks my strongest unit (Admiral Ackbar, 6), so Jar
// Jar goes on the strongest SPACE unit — the A-Wing (3, value 2), not the T-6 Shuttle (2, value 4).
$boardG = function ($b) use ($plots) { $plots($b);
    $b->WithGroundUnitForPlayer(1, 'ASH_110', true); $b->WithSpaceUnitForPlayer(1, 'JTL_095', true); $b->WithSpaceUnitForPlayer(1, 'ASH_109', true);
    $b->WithGroundUnitForPlayer(2, 'SEC_098', false); };
[$picks] = $flipTurn($boardG);
$check(($pickFor($picks, $JARJAR)[0][2] ?? '') === 'JTL_095', 'G: with a ground Sentinel the plan is the strongest SPACE unit; got ' . json_encode($pickFor($picks, $JARJAR)));
$check(($pickFor($picks, $SUPPORT)[0][2] ?? '') === 'JTL_095', 'G: …and it makes the Support attack, not the blocked Ackbar; got ' . json_encode($pickFor($picks, $SUPPORT)));

// H) The Supported unit's borrowed +2 goes on Ahsoka even when a more valuable unit qualifies: a Shielded Chewbacca (5/6,
// value above hers) is ready and below Ackbar's 10. She is the one sure to attack next and use it.
$boardH = function ($b) use ($plots) { $plots($b);
    $b->WithGroundUnitForPlayer(1, 'ASH_110', true); $b->WithGroundUnitForPlayer(1, 'JTL_103', true);
    $b->WithUpgradesOnGroundUnitForPlayer(1, 1, [GameStateBuilder::Upgrade('SOR_T02', 1)]);
    $b->WithGroundUnitForPlayer(2, 'SOR_095', false); };
[$picks] = $flipTurn($boardH);
$check(($pickFor($picks, $SUPPORT)[0][2] ?? '') === 'ASH_110', 'H: fixture — Ackbar makes the Support attack; got ' . json_encode($pickFor($picks, $SUPPORT)));
$check(($pickFor($picks, $LEND)[0][2] ?? '') === 'ASH_009', 'H: the borrowed +2 goes on Ahsoka over the more valuable Chewbacca; got ' . json_encode($pickFor($picks, $LEND)));

// E) Not a flip turn: Jar Jar from hand with no Support pending keeps the ordinary scoring (the rule is inert).
$build(function ($b) { $b->MyLeader('ASH_009', true, true, true, 'unit'); $b->FillResourcesForPlayer(1, 'SOR_095', 6); $b->WithCardInHandForPlayer(1, 'SEC_111');
    $b->WithGroundUnitForPlayer(1, 'SEC_098', true); $b->WithGroundUnitForPlayer(2, 'SOR_095', true); });
$act(1, 10002, 'myHand-0!FSM!');
$legal = SWUBotLegalActions($gameName, 1);
$on = SWUBotHeuristicChoose('hyperaggro', (array)$legal['actions'], $legal, '');
$off = SWUBotHeuristicChoose('hyperaggro', (array)$legal['actions'], $legal, 'no-buffspread');
$check(strval($on['cardID'] ?? '') === strval($off['cardID'] ?? ''), 'E: outside the flip turn the Jar Jar pick is unchanged; got ' . json_encode([$on['cardID'] ?? null, $off['cardID'] ?? null]));

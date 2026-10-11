<?php
// Features 'wipeinit' + 'wipedraw' (p36) — claim the initiative a round early so next round's wipe is the FIRST action.
// Owner 2026-10-04: "this can be for both HSD or SRI. sometimes it's best not to play anything if you are setting up SRI. or it
// might be best to not let the opponent take initiative and get another 5+ damage in if you claim and wipe a space aggro deck."
// Human games: Ninin (Hemlock Red) claimed R5 -> Hyperspace Disaster R6 vs Vader Yellow; claimed R6 -> Single Reactor Ignition R7
// vs Luke (ASH) Data Vault. And on Reprint_Cad (Hemlock Red) claiming every round vs Ahsoka Yellow with no wipe in hand: "the
// Hemlock red players claim in hopes of drawing a wipe" — 'wipedraw' prices that chance (2 regroup draws from the deck).
// Fixtures (dictionary-checked): HMW_003 Doctor Hemlock · HMW_027 Bioweapons Lab · SEC_078 Hyperspace Disaster (7) · LAW_044 Single
//   Reactor Ignition (8) · SEC_215 Emissary's Sheathipede 2/4 (space) · LAW_135 Pirate Snub Fighter 2/3 (space) · JTL_006 Darth
//   Vader · JTL_140 IG-2000 3/4 (space, 4) · LOF_130 HK-47 2/4 (ground, 2) · LAW_097 (resources) · SOR_095 Battlefield Marine (deck filler)
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php -d apc.enable_cli=1 -d xdebug.mode=off SWUSim/DevTools/tests/bot_wipeinit_test.php
require __DIR__ . '/fixtures/bot_test_bootstrap.php';
foreach (['BotLegalActions', 'Custom/BotLookahead', 'Custom/BotEvaluator', 'Rl/CardTags', 'Custom/BotStyles', 'Custom/BotResourcing',
          'Custom/BotGuides', 'Custom/BotFallback', 'Custom/BotRules', 'BotHeuristic'] as $f) include_once "./SWUSim/$f.php";
foreach (['wipeinit', 'wipedraw'] as $x) {
    $check(SWUBotVariantDisabled("no-$x") === [$x], "$x is switchable");
    $check(in_array($x, SWUBotFeatureGroups()['p36'] ?? [], true), "$x is in group p36");
}
$stack = function (string $variant = '') use (&$gameName) {
    SWUBotResetCoverage(); $l = SWUBotLegalActions($gameName, 1);
    $p = SWUBotHeuristicChoose('softcontrol', (array)$l['actions'], $l, $variant);
    return [$p === null ? null : strval($p['cardID']), array_keys($GLOBALS['SWUBotCoverage'][1] ?? [])];
};
// Seat 1 Hemlock (leader exhausted), $res ready resources, $hand, $deck; seat 2 Vader holds the UNCLAIMED initiative with $ships ready.
$board = function (int $res, array $hand, array $ships, array $mine = [], array $deck = []) {
    return function ($b) use ($res, $hand, $ships, $mine, $deck) {
        $b->MyLeader('HMW_003', false, false, true); $b->MyBase('HMW_027', 10);   // Epic Action spent: no deploy competing with the claim
        $b->TheirLeader('JTL_006', false); $b->WithInitiativePlayerBeing(2);
        $b->FillResourcesForPlayer(1, 'LAW_097', $res);
        foreach ($hand as $c) $b->WithCardInHandForPlayer(1, $c);
        foreach ($deck as $c) $b->WithCardInDeckForPlayer(1, $c);
        foreach ($ships as $c) $b->WithSpaceUnitForPlayer(2, $c, true);
        foreach ($mine as [$arena, $c]) { if ($arena === 'space') $b->WithSpaceUnitForPlayer(1, $c, false); else $b->WithGroundUnitForPlayer(1, $c, false); }
    };
};
$take = 'InitiativeCounter-0!CustomInput!TakeInitiative';
$fleet = ['SEC_215', 'LAW_135', 'LAW_135'];
$filler = array_fill(0, 8, 'SOR_095');   // a deck with no wipe in it
// HK-47 (ground, 2) is a real alternative play on every claim board: with nothing else to do the 'nothing-left' rule claims the
// initiative anyway, so a board without one could not tell the feature from the baseline.

// A) 6 resources, Disaster (7) in hand, three ships: castable NEXT round only -> claim the initiative.
$build($board(6, ['SEC_078', 'LOF_130'], $fleet, [], $filler));
[$offPick] = $stack('no-wipeinit');
[$pick, $cov] = $stack();
$check($offPick !== $take, 'A fixture: today the bot does not claim; got ' . var_export($offPick, true));
$check($pick === $take && in_array('rule:initiative-for-wipe', $cov, true), 'A: claim now, wipe first next round; got ' . var_export($pick, true) . ' ' . json_encode($cov));
// …and HK-47 is a GROUND unit: Disaster (space) won't kill it, so its play is not held.
$l = SWUBotLegalActions($gameName, 1); $ctxA = $botCtx('softcontrol');
$hk = null; foreach ((array)$l['actions'] as $a) if (str_starts_with(strval($a['cardID']), 'myHand-1!')) $hk = $a;
$GLOBALS['SWUBotDisabledFeatures'] = ['wipeinit']; $hkOff = SWUBotScoreAction($ctxA, $hk, 0);
$GLOBALS['SWUBotDisabledFeatures'] = []; $hkOn = SWUBotScoreAction($ctxA, $hk, 0);
$check(abs($hkOn - $hkOff) < 1e-9 && $hkOn > 0, "A: HK-47 (ground) is not held for a SPACE wipe; got $hkOff -> $hkOn");
// B) 7 resources: castable NOW — the rule stays out (the fallback / control-wipe decide).
$build($board(7, ['SEC_078'], $fleet, [], $filler));
[, $covB] = $stack();
$check(!in_array('rule:initiative-for-wipe', $covB, true), 'B: castable now — no claim rule');
// …even with more Disasters in the deck (the draw branch would otherwise claim): cast it, don't set it up.
$build($board(7, ['SEC_078', 'LOF_130'], $fleet, [], ['SEC_078', 'SEC_078', 'SOR_095', 'SOR_095', 'SOR_095', 'SOR_095']));
[, $covB2] = $stack();
$check(!in_array('rule:initiative-for-wipe', $covB2, true), 'B: castable now, wipes in the deck too — still no claim rule');
// C) 5 resources: not castable next round either (5 + 1 < 7) — no claim.
$build($board(5, ['SEC_078'], $fleet, [], $filler));
[, $covC] = $stack();
$check(!in_array('rule:initiative-for-wipe', $covC, true), 'C: not castable next round — no claim');
// D) The wipe costs me more than them (my two IG-2000s vs their one ship): not worth setting up — no claim.
$build($board(6, ['SEC_078'], ['LAW_135'], [['space', 'JTL_140'], ['space', 'JTL_140']], $filler));
[, $covD] = $stack();
$check(!in_array('rule:initiative-for-wipe', $covD, true), 'D: a wipe that costs me more than them — no claim');
// E) Single Reactor Ignition (8) set up with 7 resources: claims too (both wipes, owner ruling).
$build($board(7, ['LAW_044'], $fleet, [], $filler));
[$pickE] = $stack();
$check($pickE === $take, 'E: Single Reactor Ignition next round — claim; got ' . var_export($pickE, true));
// F) Don't develop into it: with SRI set up for next round, HK-47 (ground) played now would die to my own wipe.
$build($board(7, ['LAW_044', 'LOF_130'], $fleet, [], $filler));
$l = SWUBotLegalActions($gameName, 1); $ctx = $botCtx('softcontrol');
$play = null; foreach ((array)$l['actions'] as $a) if (str_starts_with(strval($a['cardID']), 'myHand-1!')) $play = $a;
$GLOBALS['SWUBotDisabledFeatures'] = ['wipeinit']; $sOff = SWUBotScoreAction($ctx, $play, 0);
$GLOBALS['SWUBotDisabledFeatures'] = []; $sOn = SWUBotScoreAction($ctx, $play, 0);
$check($play !== null && $sOff > 0 && $sOn < 0, "F: HK-47 waits for the wipe; got $sOff -> $sOn");

// G) 'wipedraw': no wipe in hand, but 2 Disasters in a 6-card deck (P(draw >=1 in 2) = 1 - C(4,2)/C(6,2) = 0.6), 6 resources
// (7 next round), three ships threatening 6 -> claim on the chance.
$build($board(6, ['LOF_130'], $fleet, [], ['SEC_078', 'SEC_078', 'SOR_095', 'SOR_095', 'SOR_095', 'SOR_095']));
$check(abs(_SWUBotWipeDrawChance(1)['p'] - 0.6) < 1e-9, 'G fixture: the draw chance is 0.6; got ' . json_encode(_SWUBotWipeDrawChance(1)));
[$offG] = $stack('no-wipedraw');
[$pickG, $covG] = $stack();
$check($offG !== $take, 'G fixture: today no claim; got ' . var_export($offG, true));
$check($pickG === $take && in_array('rule:initiative-for-wipe', $covG, true), 'G: claim in hope of drawing the wipe; got ' . var_export($pickG, true));
// H) No wipe left in the deck: nothing to hope for — no claim.
$build($board(6, ['LOF_130'], $fleet, [], $filler));
[, $covH] = $stack();
$check(!in_array('rule:initiative-for-wipe', $covH, true), 'H: no wipe in the deck — no claim');
// I) Wipes in the deck but too expensive for next round (4 resources -> 5 < 7): no claim.
$build($board(4, ['LOF_130'], $fleet, [], ['SEC_078', 'SEC_078', 'SOR_095', 'SOR_095', 'SOR_095', 'SOR_095']));
[, $covI] = $stack();
$check(!in_array('rule:initiative-for-wipe', $covI, true), 'I: a drawn wipe could not be cast next round — no claim');
// J) A thin chance (1 Disaster in a 40-card deck, P = 0.05) against a threat of 6: not worth the round — no claim.
$build($board(6, ['LOF_130'], $fleet, [], array_merge(['SEC_078'], array_fill(0, 39, 'SOR_095'))));
[, $covJ] = $stack();
$check(!in_array('rule:initiative-for-wipe', $covJ, true), 'J: a 5% draw chance loses to playing HK-47');

// K) The control wing only: the same board as A, piloted as hyperaggro — no claim rule.
$build($board(6, ['SEC_078', 'LOF_130'], $fleet, [], $filler));
SWUBotResetCoverage(); $l = SWUBotLegalActions($gameName, 1); SWUBotHeuristicChoose('hyperaggro', (array)$l['actions'], $l, '');
$check(!in_array('rule:initiative-for-wipe', array_keys($GLOBALS['SWUBotCoverage'][1] ?? []), true), 'K: hyperaggro never runs the claim rule');
// L) I already hold the initiative: nothing to claim — the rule stays out.
$build(function ($b) use ($board, $fleet, $filler) { ($board(6, ['SEC_078', 'LOF_130'], $fleet, [], $filler))($b); $b->WithInitiativePlayerBeing(1); });
[, $covL] = $stack();
$check(!in_array('rule:initiative-for-wipe', $covL, true), 'L: I hold the initiative already — no claim rule');

bot_test_finish();

<?php
// Keep the Credit the wipe needs next round (proposal 'wipecredit', 2026-10-07 gap screen). ⚠ Needs an owner ruling before it ships:
// Lando's Credits are deliberately a tempo engine (flavour 'tempo' exempts him from 'bigcredit' banking).
// FOUND 2026-10-07 diagnosing Vader (JTL) Yellow vs Lando (LAW) Blue (.claude/tmp/diag_vader): Lando's hinge is Hyperspace Disaster (with it
// in hand by R5: 38W-25L; without: 3W-34L). In 7 losses Lando began R4 with Disaster, 5 resources and 1 Credit and spent the Credit on
// Anakin (LOF_070, 6) — in R5 it had 6 resources, no Credit, and Disaster (7) was one short; six of the seven died that round.
// s018 R4: "P2 defeated 1 Credit token… played Anakin Skywalker". The lever: a play that SPENDS a Credit is held when a relevant wipe in
// hand is out of reach now, in reach next round, and out of reach next round without that Credit.
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php -d apc.enable_cli=1 -d xdebug.mode=off SWUSim/DevTools/tests/bot_wipecredit_test.php
require __DIR__ . '/fixtures/bot_test_bootstrap.php';
include_once './SWUSim/BotLegalActions.php';
include_once './SWUSim/BotHeuristic.php';
// p42 'landoflip' (2026-10-08, owner-approved pin): a pre-flip Lando now keeps his last Credit for the flip ("do not deploy Lando with 0
// Credits"); this file uses that Lando only as a Credit deck for another spend rule, so the flip rule is held off here (bot_landoflip_test).
$GLOBALS['SWUBotPinnedDisabled'] = array_merge($GLOBALS['SWUBotPinnedDisabled'] ?? [], ['landoflip']);
$check(in_array('wipecredit', SWUBotProposalList(), true) && SWUBotVariantDisabled('try-wipecredit') === ['try:wipecredit'], 'wipecredit is a switchable proposal');
// Lando (LAW_018 on ASH_019), round 4, $res ready resources + 1 Credit; Hyperspace Disaster (SEC_078, 7) and Anakin (LOF_070, 6) in hand;
// Vader (JTL_006 on ASH_026) with four ships.
$pick = function (int $res, string $variant, array $hand = ['SEC_078', 'LOF_070'], int $spent = 0) use (&$gameName, $build) {
    $build(function ($b) use ($res, $hand, $spent) {
        $b->MyLeader('LAW_018', false); $b->MyBase('ASH_019'); $b->WithCurrentRoundBeing(4); $b->FillResourcesForPlayer(1, 'SOR_095', $res);
        if ($spent > 0) $b->FillResourcesForPlayer(1, 'SOR_095', $spent, false);   // already spent this round
        foreach ($hand as $c) $b->WithCardInHandForPlayer(1, $c);
        $b->TheirLeader('JTL_006', false); $b->TheirBase('ASH_026');
        foreach (['JTL_085', 'LAW_135', 'JTL_217', 'JTL_T01'] as $s) $b->WithSpaceUnitForPlayer(2, $s, true);
    });
    global $playerID; $playerID = 1;
    SWUCreateCreditToken(1, 1);
    $l = SWUBotLegalActions($gameName, 1);
    $p = SWUBotHeuristicChoose('hardcontrol', (array)$l['actions'], $l, $variant);
    $id = strval($p['cardID'] ?? '');
    return str_starts_with($id, 'myHand-') ? strval(GetHand(1)[intval(substr(explode('!', $id)[0], 7))]->CardID ?? '') : $id;
};
$check($pick(5, '') === 'LOF_070', 'premise: today the Credit pays for Anakin; got ' . $pick(5, ''));
$check($pick(5, 'try-wipecredit') !== 'LOF_070', '@try-wipecredit: Anakin is held — the Credit is Disaster\'s next round; got ' . $pick(5, 'try-wipecredit'));
// 6 resources: Anakin needs no Credit — the play does not spend it, nothing to hold.
$check($pick(6, 'try-wipecredit') === $pick(6, ''), '@try-wipecredit: with 6 resources Anakin spends no Credit — unchanged; got ' . $pick(6, 'try-wipecredit'));
// No wipe in hand: nothing to keep the Credit for.
$check($pick(5, 'try-wipecredit', ['LOF_070', 'SOR_095']) === 'LOF_070', '@try-wipecredit: no wipe in hand — the Credit pays for Anakin');

// Mid-round, 2 of 6 resources already spent: Bo-Katan's Gauntlet (ASH_063, 5) needs the Credit now (4 ready + 1), but next round 7 resources
// pay for Disaster without it — nothing to hold. (Scored directly: on this board Lando's deploy outranks every play.)
$build(function ($b) { $b->MyLeader('LAW_018', false); $b->MyBase('ASH_019'); $b->WithCurrentRoundBeing(4);
    $b->FillResourcesForPlayer(1, 'SOR_095', 4); $b->FillResourcesForPlayer(1, 'SOR_095', 2, false);
    foreach (['SEC_078', 'ASH_063'] as $c) $b->WithCardInHandForPlayer(1, $c);
    $b->TheirLeader('JTL_006', false); $b->TheirBase('ASH_026');
    foreach (['JTL_085', 'LAW_135', 'JTL_217', 'JTL_T01'] as $s) $b->WithSpaceUnitForPlayer(2, $s, true); });
global $playerID; $playerID = 1; SWUCreateCreditToken(1, 1);
$ctx = $botCtx('hardcontrol'); $gs = null;
foreach ($ctx['actions'] as $i => $a) if (strval($a['cardID']) === 'myHand-1!FSM!') $gs = [$a, $i];
$check($gs !== null && SWUBotCreditSpendFor(1, 'ASH_063') > 0, 'fixture: the Gauntlet is playable and needs the Credit');
SWUBotSetDisabledFeatures([]); $off = SWUBotScoreAction($ctx, $gs[0], $gs[1]);
SWUBotSetDisabledFeatures(['try:wipecredit']); $on = SWUBotScoreAction($ctx, $gs[0], $gs[1]); SWUBotSetDisabledFeatures([]);
$check(abs($on - $off) < 1e-9, "@try-wipecredit: next round's 7 resources pay for Disaster anyway — the Gauntlet is not held; got $off -> $on");

bot_test_finish();

<?php
// Feature 'earlycredits' (p40) — in rounds 1-3 against an AGGRO leader, Krennic's banked Credits may pay for a cheap body ('bigcredit',
// p23, holds them for a big play). Autopsy 2026-10-06: R1-5 the owner makes 2.2 Credits and spends 1.8; the bot spends 1.38 and holds
// 1.0-1.3 every round while falling behind on board (R2: 0.9 units vs the owner's 2.2). Owner ruling, same day: "Spend by R3, then bank"
// — Credits pay for board in R1-R3; from R4 they are banked for the wipe / big bomb.
// The 2026-10-03 'bigcredit' report (game 1438045, a Credit spent on Onyx Squadron Brute in R1) was vs HMW_008, not an aggro leader:
// it stays held (bot_bigcredit_test A).
// Fixtures (dictionary-checked): LAW_008 Director Krennic · LAW_021 Coaxium Mine (credit-ramp flavour, as bot_bigcredit_test) · JTL_033
//   Onyx Squadron Brute (2) · LAW_044 · LAW_159 · ASH_009 + SOR_030 (Ahsoka, aggro) · HMW_008 + HMW_021 (the 2026-10-03 opponent) · SOR_095 · SEC_079
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php -d apc.enable_cli=1 -d xdebug.mode=off SWUSim/DevTools/tests/bot_earlycredits_test.php
require __DIR__ . '/fixtures/bot_test_bootstrap.php';
include_once './SWUSim/BotLegalActions.php';
include_once './SWUSim/BotHeuristic.php';
$check(SWUBotVariantDisabled('no-earlycredits') === ['earlycredits'], 'earlycredits is switchable');
$check(in_array('earlycredits', SWUBotFeatureGroups()['p40'] ?? [], true), 'earlycredits is in group p40');
// Round $rnd; 2 resources, 1 ready; 1 Credit; their $leader with one unit. The card the bot plays (or its pick).
$pick = function (int $rnd, string $leader, string $base, string $variant) use (&$gameName, $build) {
    $build(function ($b) use ($rnd, $leader, $base) {
        $b->MyLeader('LAW_008', false); $b->MyBase('LAW_021'); $b->WithCurrentRoundBeing($rnd);
        for ($i = 0; $i < 2; $i++) $b->WithControlledResourceForPlayer(1, 'SOR_095', 1, $i < 1);
        foreach (['LAW_044', 'JTL_033', 'LAW_159'] as $c) $b->WithCardInHandForPlayer(1, $c);
        $b->TheirLeader($leader); $b->TheirBase($base); $b->WithGroundUnitForPlayer(2, 'SEC_079', false); $b->FillResourcesForPlayer(2, 'SOR_095', 2);
    });
    global $playerID; $playerID = 1;
    SWUCreateCreditToken(1, 1);
    $l = SWUBotLegalActions($gameName, 1);
    $p = SWUBotHeuristicChoose('softcontrol', (array)$l['actions'], $l, $variant);
    $id = strval($p['cardID'] ?? '');
    return str_starts_with($id, 'myHand-') ? strval(GetHand(1)[intval(substr($id, 7))]->CardID ?? '') : $id;
};
// A) R2 vs Ahsoka: today the Brute (needs the Credit) is held; fixed, it is played.
$check($pick(2, 'ASH_009', 'SOR_030', 'no-earlycredits') !== 'JTL_033', 'A fixture: today the Brute is held for a big play; got ' . $pick(2, 'ASH_009', 'SOR_030', 'no-earlycredits'));
$check($pick(2, 'ASH_009', 'SOR_030', '') === 'JTL_033', 'A: R2 vs aggro the Credit pays for the Brute; got ' . $pick(2, 'ASH_009', 'SOR_030', ''));
// B) R4 vs Ahsoka: banked again ("then bank").
$check($pick(4, 'ASH_009', 'SOR_030', '') !== 'JTL_033', 'B: R4 — the Credit is banked; got ' . $pick(4, 'ASH_009', 'SOR_030', ''));
// C) R1 vs HMW_008 (the 2026-10-03 report, not aggro): still held.
$check($pick(1, 'HMW_008', 'HMW_021', '') !== 'JTL_033', 'C: vs a non-aggro leader — still held; got ' . $pick(1, 'HMW_008', 'HMW_021', ''));

bot_test_finish();

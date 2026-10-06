<?php
// Part 23 'bigcredit' — a credit-ramp deck spends a banked Credit only on a big play. Owner report 2026-10-03, game
// 1438045: "krennic didn't bank credits. wasted them right away on Onyx Squad Brute". Round 1: the bot played Ant Droid,
// sacrificed it to LAW_008 Director Krennic's Action for a Credit, and — once the human took the initiative — spent that
// Credit at once to cover the 1-resource shortfall on JTL_033 Onyx Squadron Brute (cost 2). Owner ruling: Credits are
// for big plays (a bomb, removal, a wipe), never a cheap body.
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php -d apc.enable_cli=1 -d xdebug.mode=off SWUSim/DevTools/tests/bot_bigcredit_test.php
require __DIR__ . '/fixtures/bot_test_bootstrap.php';
include_once './SWUSim/BotLegalActions.php';
include_once './SWUSim/BotHeuristic.php';
$check(SWUBotVariantDisabled('no-bigcredit') === ['bigcredit'], 'bigcredit is switchable');

// The reported moment (seats swapped: the bot is seat 1). Krennic on Coaxium Mine, exhausted from its Action; 2 resources,
// 1 still ready; 1 Credit; the human holds the initiative. $leader/$extra vary the deck for the controls below.
$pick = function (array $hand, int $ready, int $credits, string $leader = 'LAW_008', string $variant = '') use (&$gameName, $build) {
    $build(function ($b) use ($hand, $ready, $leader) { $b->MyLeader($leader, false); $b->MyBase('LAW_021');
        for ($i = 0; $i < 2; $i++) $b->WithControlledResourceForPlayer(1, 'SOR_095', 1, $i < $ready);
        foreach ($hand as $c) $b->WithCardInHandForPlayer(1, $c);
        $b->TheirLeader('HMW_008'); $b->TheirBase('HMW_021'); $b->WithGroundUnitForPlayer(2, 'SEC_079', false); $b->FillResourcesForPlayer(2, 'SOR_095', 2); });
    global $playerID; $playerID = 1;
    if ($credits > 0) SWUCreateCreditToken(1, $credits);
    $l = SWUBotLegalActions($gameName, 1);
    $p = SWUBotHeuristicChoose('softcontrol', (array)$l['actions'], $l, $variant);
    $id = strval($p['cardID'] ?? '');
    return str_starts_with($id, 'myHand-') ? strval(GetHand(1)[intval(substr($id, 7))]->CardID ?? '') : $id;
};
$hand = ['LAW_044', 'JTL_032', 'LAW_159', 'JTL_033', 'LAW_133'];   // the reported hand

// A) THE REPORT: the Brute needs the Credit — it is held.
$got = $pick($hand, 1, 1);
$check(SWUBotBanksCredits(1), 'A fixture: Krennic is a credit-RAMP deck (banks Credits)');
$check(!in_array($got, ['JTL_033', 'JTL_032'], true), "A: the Credit is not spent on a cheap body (Brute / Krennic JTL_032); got $got");
$got = $pick($hand, 1, 1, 'LAW_008', 'no-bigcredit');
$check($got === 'JTL_033', "A @no-bigcredit: the Brute (the reported line); got $got");

// B) A play the READY RESOURCES pay for is unaffected: with both resources ready the Brute needs no Credit.
$got = $pick($hand, 2, 1);
$check(in_array($got, ['JTL_033', 'JTL_032'], true), "B: a cheap play paid from resources alone is still made; got $got");

// C) A BIG play may use the Credit: HMW_105 Nute Gunray (Command/Villainy, cost 2, damages an enemy unit) — on-aspect, so
// 1 ready resource + the Credit pays for it, and its damage tag makes it worthy.
$check(SWUBotCreditWorthy('HMW_105') && !SWUBotCreditWorthy('JTL_033'), 'C fixture: Nute Gunray is credit-worthy, the Brute is not');
$got = $pick(['HMW_105'], 1, 1);
$check(SWUBotCreditSpendFor(1, 'HMW_105') > 0, 'C fixture: Nute Gunray needs the Credit');
$check($got === 'HMW_105', "C: a big play (removal/damage) may spend the Credit; got $got");

// D) Lando's Credits are TEMPO ('credit-ramp' + 'tempo'): a cheap play still spends them. ASH_203 Mando's N-1 (Cunning/
// Heroism, cost 2) is on-aspect for Lando (LAW_018) on Coaxium Mine.
$got = $pick(['ASH_203'], 1, 1, 'LAW_018');
$check(!SWUBotBanksCredits(1) && SWUBotCreditSpendFor(1, 'ASH_203') > 0, 'D fixture: Lando does not bank, and the N-1 needs the Credit');
$check($got === 'ASH_203', "D: Lando (tempo Credits) still spends a Credit on a cheap unit; got $got");

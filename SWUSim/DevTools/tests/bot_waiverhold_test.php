<?php
// Part 24 'waiverhold' — a LAW waiver base's Epic Action that unlocks NOTHING is not spent. Owner report 2026-10-03, game
// 1438045: "the bot wasted its epic action and it wasted a Credit". Round 1, the human had taken the initiative; the bot
// (Krennic on LAW_021 Coaxium Mine: "Epic Action: Play a card from your hand, ignoring 1 of its … aspect penalties") had 1
// ready resource and a banked Credit, and every card it could reach was ON-aspect. The Epic scored exactly 0 — p16
// 'aspectwaiver' prices it at what it unlocks, with no floor — which TIED Pass (0), so it went by enumeration order. Its
// "play a card" prompt is mandatory, so it then played JTL_032 Director Krennic with the Credit ('bigcredit' had just held
// that play at -0.5).
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php -d apc.enable_cli=1 -d xdebug.mode=off SWUSim/DevTools/tests/bot_waiverhold_test.php
require __DIR__ . '/fixtures/bot_test_bootstrap.php';
include_once './SWUSim/BotLegalActions.php';
include_once './SWUSim/BotHeuristic.php';
$check(SWUBotVariantDisabled('no-waiverhold') === ['waiverhold'], 'waiverhold is switchable');
$check(SWU_BOT_WAIVER_UNLOCKS_NOTHING < 0.0 && SWU_BOT_WAIVER_UNLOCKS_NOTHING < floatval(SWUBotWeights('softcontrol', 1)['initiative'] ?? 0.05),
    'an unlocks-nothing waiver scores below PASS and below taking the initiative');

// The reported moment (seats swapped: the bot is seat 1), with the INITIATIVE ALREADY TAKEN by the human.
$board = function (array $hand, int $ready, int $credits, int $resources = 2) use ($build) {
    $build(function ($b) use ($hand, $ready, $resources) { $b->MyLeader('LAW_008', false); $b->MyBase('LAW_021');
        $b->WithInitiativePlayerBeing(2); $b->WithInitiativeClaimed();
        for ($i = 0; $i < $resources; $i++) $b->WithControlledResourceForPlayer(1, 'SOR_095', 1, $i < $ready);
        foreach ($hand as $c) $b->WithCardInHandForPlayer(1, $c);
        $b->TheirLeader('HMW_008'); $b->TheirBase('HMW_021'); $b->WithGroundUnitForPlayer(2, 'SEC_079', false); $b->FillResourcesForPlayer(2, 'SOR_095', 2); });
    global $playerID; $playerID = 1;
    if ($credits > 0) SWUCreateCreditToken(1, $credits);
};
$turn = function (string $variant = '') use (&$gameName, $act) {
    for ($i = 0; $i < 4; $i++) {
        $l = SWUBotLegalActions($gameName, 1);
        if (!in_array($l['kind'] ?? '', ['free-play', 'decision'], true)) break;
        $p = SWUBotHeuristicChoose('softcontrol', (array)$l['actions'], $l, $variant);
        $id = strval($p['cardID'] ?? '');
        if ($id === '') break;
        $act(1, intval($p['mode'] ?? 10001), $id);
        if (str_contains($id, '!Pass') || str_contains($id, 'TakeInitiative')) break;
    }
};
$epicUsed = fn() => !empty(GetBase(1)[0]->EpicActionUsed) && strval(GetBase(1)[0]->EpicActionUsed) !== 'false';
$hand = ['LAW_044', 'JTL_032', 'LAW_159', 'JTL_033', 'LAW_133'];   // the reported hand

// A) THE REPORT: the waiver unlocks nothing (every reachable card is on-aspect) — it is kept, and so is the Credit.
$board($hand, 1, 1);
$turn();
$check(!$epicUsed(), 'A: Coaxium Mine\'s waiver is NOT spent when it unlocks nothing');
$check(count(SWUUsableCreditTokenMzIDs(1)) === 1, 'A: …and the banked Credit is kept; got ' . count(SWUUsableCreditTokenMzIDs(1)));
$board($hand, 1, 1);
$turn('no-waiverhold');
$check($epicUsed() && count(SWUUsableCreditTokenMzIDs(1)) === 0, 'A @no-waiverhold: the waiver AND the Credit are spent (the reported line)');

// B) A waiver that UNLOCKS a card is still used: 6 ready resources, 2 Credits, LAW_044 Single Reactor Ignition costs 10
// unwaived (8 + the Aggression penalty) — 8 with the waiver, exactly 6 + 2 (the owner's line).
$board(['LAW_044'], 6, 2, 6);
$check(!_SWUBotCardAlreadyPlayable(1, GetHand(1)[0]), 'B fixture: LAW_044 is not castable without the waiver');
$turn();
$check($epicUsed() && count(GetHand(1)) === 0, 'B: the waiver that UNLOCKS Single Reactor Ignition is used, and it is cast');

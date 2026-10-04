<?php
// Feature 'fodderfirst' (Part 30, 2026-10-03) — a PAIRED-DEFEAT card ("choose a friendly unit and an enemy non-leader unit.
// If you do, defeat those units": ASH_052 Chimaera) cast with no other friendly unit in play has only ITSELF to give.
// Owner's Arenabot games with Hemlock Red (BotData 2026-10-03, games 1483356 R6 and 1483359 R16): the bot cast Chimaera,
// defeated Chimaera to take one enemy, then played 0-0-0 the same round — played the other way round, 0-0-0 is the price
// and the 6/6 that heals 2 per enemy defeat stays. So when a cheaper unit is castable now AND the paired card is still
// affordable after it, the cheap unit goes first.
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php -d apc.enable_cli=1 -d xdebug.mode=off SWUSim/DevTools/tests/bot_fodderfirst_test.php
require __DIR__ . '/fixtures/bot_test_bootstrap.php';
include_once './SWUSim/BotLegalActions.php';
include_once './SWUSim/BotHeuristic.php';
$check(SWUBotVariantDisabled('no-fodderfirst') === ['fodderfirst'], 'fodderfirst is switchable; got ' . json_encode(SWUBotVariantDisabled('no-fodderfirst')));
$check(in_array('fodderfirst', SWUBotFeatureGroups()['p30'] ?? [], true), 'fodderfirst is in group p30');

// Hemlock Red's leader (Epic Action spent, so deploying is not the alternative) and base; $res resources, $hand, plus
// $more($b). Their side: a Talzin's Assassin (4/4) to take.
$board = function (array $hand, int $res, ?callable $more = null) {
    return function ($b) use ($hand, $res, $more) {
        $b->MyLeader('HMW_003', true, false, true); $b->MyBase('HMW_027');
        $b->FillResourcesForPlayer(1, 'LAW_097', $res);
        foreach ($hand as $c) $b->WithCardInHandForPlayer(1, $c);
        $b->WithGroundUnitForPlayer(2, 'LOF_035');
        if ($more !== null) $more($b);
    };
};
$first = function (callable $b, string $variant) use ($build) {
    $build($b);
    $l = SWUBotLegalActions($GLOBALS['gameName'], 1);
    return strval(SWUBotHeuristicChoose('softcontrol', (array)$l['actions'], $l, $variant)['cardID'] ?? '');
};
$chim = 'myHand-0!FSM!'; $fodder = 'myHand-1!FSM!';

// A) The 1483359 shape: 10 resources, Chimaera (7) + 0-0-0 (3), no unit of mine in play. Today Chimaera goes first and eats
// itself. 0-0-0 first, then Chimaera with 0-0-0 to give.
$boardA = $board(['ASH_052', 'LAW_174'], 10);
$check($first($boardA, 'no-fodderfirst') === $chim, 'A fixture: today Chimaera is cast first; got ' . $first($boardA, 'no-fodderfirst'));
$check($first($boardA, '') === $fodder, 'A: the cheap unit is played first; got ' . $first($boardA, ''));
// B) Not enough for both this round (8 resources: 0-0-0 then 5 left, short of 7): Chimaera now, as before.
$check($first($board(['ASH_052', 'LAW_174'], 8), '') === $chim, 'B: both are not affordable — Chimaera now; got ' . $first($board(['ASH_052', 'LAW_174'], 8), ''));
// C) A unit of mine is already in play to give: nothing to set up.
$boardC = $board(['ASH_052', 'LAW_174'], 10, fn($b) => $b->WithGroundUnitForPlayer(1, 'LAW_097'));
$check($first($boardC, '') === $first($boardC, 'no-fodderfirst'), 'C: a friendly unit already in play — unchanged; got ' . $first($boardC, '') . ' vs ' . $first($boardC, 'no-fodderfirst'));
// D) No enemy non-leader unit to take: the pairing does nothing, so there is nothing to protect — unchanged.
$boardD = function ($b) { $b->MyLeader('HMW_003', true, false, true); $b->MyBase('HMW_027'); $b->FillResourcesForPlayer(1, 'LAW_097', 10);
    foreach (['ASH_052', 'LAW_174'] as $c) $b->WithCardInHandForPlayer(1, $c); };
$check($first($boardD, '') === $first($boardD, 'no-fodderfirst'), 'D: no enemy target — unchanged; got ' . $first($boardD, '') . ' vs ' . $first($boardD, 'no-fodderfirst'));
// D2) Their only unit is a deployed LEADER: Chimaera takes "an enemy non-leader unit", so there is still nothing to take.
$boardD2 = function ($b) { $b->MyLeader('HMW_003', true, false, true); $b->MyBase('HMW_027'); $b->FillResourcesForPlayer(1, 'LAW_097', 10);
    foreach (['ASH_052', 'LAW_174'] as $c) $b->WithCardInHandForPlayer(1, $c);
    $b->TheirLeader('SOR_010', true, true, false, 'unit'); };
$check($first($boardD2, '') === $first($boardD2, 'no-fodderfirst'), 'D2: only a leader to take — unchanged; got ' . $first($boardD2, '') . ' vs ' . $first($boardD2, 'no-fodderfirst'));
// E) The cheap card is not a UNIT (No Disintegrations, an event): it cannot be the price — unchanged.
$boardE = $board(['ASH_052', 'JTL_144'], 10);
$check($first($boardE, '') === $first($boardE, 'no-fodderfirst'), 'E: an event is no fodder — unchanged; got ' . $first($boardE, '') . ' vs ' . $first($boardE, 'no-fodderfirst'));

// G) WIDENED 2026-10-04 (Ninin vs Maul Blue, R16: she rolled Chimaera back to play Storm Raider first, so the 1-cost Raider was
// the price, not a Mandalorian or her Pre Vizsla). A unit IS in play — Marrok (3, 2/6 Sentinel) — but the hand's Storm Raider
// (1) is the cheaper price: it goes first. Before the widening the rule only fired on an empty board.
// (Leader exhausted: Hemlock's Weakness Action — p31 'weaknessaction' — would otherwise come first on this board.)
$boardG = function ($b) { $b->MyLeader('HMW_003', false, false, true); $b->MyBase('HMW_027'); $b->FillResourcesForPlayer(1, 'LAW_097', 8);
    foreach (['ASH_052', 'LAW_172'] as $c) $b->WithCardInHandForPlayer(1, $c);
    $b->WithGroundUnitForPlayer(2, 'LOF_035'); $b->WithGroundUnitForPlayer(1, 'ASH_030', false); };
$check($first($boardG, 'no-fodderfirst') === $chim, 'G fixture: Chimaera first with Marrok in play; got ' . $first($boardG, 'no-fodderfirst'));
$check($first($boardG, '') === $fodder, 'G: the cheaper Storm Raider is played first; got ' . $first($boardG, ''));

bot_test_finish();

<?php
// Feature 'wipeaware' (Part 30, 2026-10-03) — respect a wipe the opponent has SHOWN. LAW_044 Single Reactor Ignition: "Defeat
// all units. For each enemy unit defeated this way, deal 1 damage to its controller's base." Owner's Arenabot game 1483356 R19
// (Hemlock Red vs his Krennic Blue Splash): on ~7 HP the bot played a second Pre Vizsla, went to 7 units, and the next SRI
// dealt exactly lethal — the owner had cast one SRI already (R10). Public information only: the wipe is in their DISCARD and
// they control enough resources to cast another. Then a play that leaves me as many units (tokens included) as my base has HP
// left is held.
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php -d apc.enable_cli=1 -d xdebug.mode=off SWUSim/DevTools/tests/bot_wipeaware_test.php
require __DIR__ . '/fixtures/bot_test_bootstrap.php';
include_once './SWUSim/BotLegalActions.php';
include_once './SWUSim/BotHeuristic.php';
$check(SWUBotVariantDisabled('no-wipeaware') === ['wipeaware'], 'wipeaware is switchable; got ' . json_encode(SWUBotVariantDisabled('no-wipeaware')));
$check(in_array('wipeaware', SWUBotFeatureGroups()['p30'] ?? [], true), 'wipeaware is in group p30');

// Hemlock Red (Epic Action spent) on Bioweapons Lab (30 HP) with $hpLeft left; $myUnits Imperial Door Technicians in play;
// $hand with $res resources. Their side: Krennic's base, $theirRes resources, and SRI in their discard unless $seen is false.
$board = function (int $hpLeft, int $myUnits, array $hand, int $res, int $theirRes = 8, bool $seen = true, ?callable $more = null) {
    return function ($b) use ($hpLeft, $myUnits, $hand, $res, $theirRes, $seen, $more) {
        $b->MyLeader('HMW_003', true, false, true); $b->MyBase('HMW_027', 30 - $hpLeft);
        $b->FillResourcesForPlayer(1, 'LAW_097', $res);
        for ($i = 0; $i < $myUnits; $i++) $b->WithGroundUnitForPlayer(1, 'LAW_097');
        foreach ($hand as $c) $b->WithCardInHandForPlayer(1, $c);
        $b->FillResourcesForPlayer(2, 'LAW_097', $theirRes);
        if ($seen) $b->WithCardInDiscardForPlayer(2, 'LAW_044');
        if ($more !== null) $more($b);
    };
};
$first = function (callable $b, string $variant) use ($build) {
    $build($b);
    $l = SWUBotLegalActions($GLOBALS['gameName'], 1);
    return strval(SWUBotHeuristicChoose('softcontrol', (array)$l['actions'], $l, $variant)['cardID'] ?? '');
};
$play = 'myHand-0!FSM!';

// A) 3 HP left, 2 units in play, 0-0-0 in hand: a third unit puts the base in SRI range. Held.
$boardA = $board(3, 2, ['LAW_174'], 3);
$check($first($boardA, 'no-wipeaware') === $play, 'A fixture: today the third unit is played; got ' . $first($boardA, 'no-wipeaware'));
$check($first($boardA, '') !== $play, 'A: the play that puts the base in SRI range is held; got ' . $first($boardA, ''));
// B) The wipe was never SHOWN: no reason to fear it.
$check($first($board(3, 2, ['LAW_174'], 3, 8, false), '') === $play, 'B: no SRI seen — played; got ' . $first($board(3, 2, ['LAW_174'], 3, 8, false), ''));
// C) They cannot cast it (5 resources for an 8-cost card): played.
$check($first($board(3, 2, ['LAW_174'], 3, 5), '') === $play, 'C: SRI not castable for them — played; got ' . $first($board(3, 2, ['LAW_174'], 3, 5), ''));
// D) Out of range: 4 HP left, 3 units after the play.
$check($first($board(4, 2, ['LAW_174'], 3), '') === $play, 'D: 3 units on 4 HP — played; got ' . $first($board(4, 2, ['LAW_174'], 3), ''));
// E) TOKENS COUNT: Pre Vizsla (8) defeats two enemy Owen Lars (LOF_057, 0/3 — 6 HP in all) and makes a Mandalorian token for
// each — one card, three units. 3 HP left, no units in play: that is lethal range. (0-power targets: with real attackers on 3 HP, rule 4
// "break lethal" rightly plays Pre Vizsla first — surviving this round outranks the wipe risk.)
$boardE = $board(3, 0, ['ASH_053'], 8, 8, true, function ($b) { $b->WithGroundUnitForPlayer(2, 'LOF_057'); $b->WithGroundUnitForPlayer(2, 'LOF_057'); });
$check($first($boardE, 'no-wipeaware') === $play, 'E fixture: today Pre Vizsla is played; got ' . $first($boardE, 'no-wipeaware'));
$check($first($boardE, '') !== $play, 'E: Pre Vizsla + 2 tokens on 3 HP is held; got ' . $first($boardE, ''));

// F) THROUGH RULE 5: the control wing's wipe rule plays a qualifying wipe before the fallback scores anything, and Pre Vizsla
// is tagged 'wipe' — the 1483356 R19 play. 4 HP left, one unit of mine; theirs: an Imperial Door Technician (2/2) and an Owen
// Lars (0/3). Pre Vizsla takes both (it stabilises: their clock goes away) and leaves me 4 units on 4 HP. Held.
$boardF = $board(4, 1, ['ASH_053'], 8, 8, true, function ($b) { $b->WithGroundUnitForPlayer(2, 'LAW_097'); $b->WithGroundUnitForPlayer(2, 'LOF_057'); });
$check($first($boardF, 'no-wipeaware') === $play, 'F fixture: today rule 5 plays Pre Vizsla; got ' . $first($boardF, 'no-wipeaware'));
$check($first($boardF, '') !== $play, 'F: rule 5 respects the shown wipe too; got ' . $first($boardF, ''));

bot_test_finish();

<?php
// A second copy of a unique unit is not played over a healthy first copy (feature 'unique').
// FOUND 2026-09-14 in a live Bot Practice game (183227, owner report): "after i take initiative, the bot plays a unit
// that is the same as the unique unit in play. there is no reason to do so because it defeats its own unit that was in
// no danger". The bot had Sabine's Masterpiece (JTL_250, 3/3, unique, no When Played) at full HP and played the copy
// from hand: the uniqueness rule defeated the first. Part 2's 'picks' docks the clash by develop × cost + 1, but the
// play's other value (the guides) still beat Pass (+0.30 vs 0.00). Replacing an UNDAMAGED copy gains nothing.
// A damaged copy is a different case — the fresh one is a heal — and keeps the part-2 scoring.
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php -d apc.enable_cli=1 -d xdebug.mode=off SWUSim/DevTools/tests/bot_unique_test.php
require __DIR__ . '/fixtures/bot_test_bootstrap.php';
include_once './SWUSim/BotLegalActions.php';
include_once './SWUSim/BotHeuristic.php';
$stack = function (string $style, int $seat = 1, string $variant = '') use (&$gameName) {
    SWUBotResetCoverage(); $legal = SWUBotLegalActions($gameName, $seat);
    $p = SWUBotHeuristicChoose($style, (array)$legal['actions'], $legal, $variant);
    return [$p === null ? null : strval($p['cardID']), array_keys($GLOBALS['SWUBotCoverage'][$seat] ?? [])];
};
$playScore = function (string $style) use ($botCtx) {
    $ctx = $botCtx($style);
    foreach ($ctx['actions'] as $i => $a) if (strval($a['cardID']) === 'myHand-0!FSM!') return SWUBotScoreAction($ctx, $a, $i);
    return null;
};
$check(SWUBotVariantDisabled('no-unique') === ['unique'], 'unique is switchable');

// The live shape: my Masterpiece (exhausted, full HP), the second copy in hand, nothing else worth doing.
$board = function (int $damage) {
    // The opponent has already taken the initiative (as in the live game), so Pass is the only alternative.
    return function ($b) use ($damage) { $b->MyLeader('SOR_014', false, false, true); $b->FillResourcesForPlayer(1, 'SOR_095', 6); $b->WithCardInHandForPlayer(1, 'JTL_250');
        $b->WithSpaceUnitForPlayer(1, 'JTL_250', false, $damage); $b->WithInitiativePlayerBeing(2); $b->WithInitiativeClaimed(); };
};
$build($board(0));
$check(_SWUBotUniqueClash(1, 'JTL_250'), 'fixture: the copy in hand clashes with the one in play');
SWUBotSetDisabledFeatures(['unique']); $old = $playScore('normal'); SWUBotSetDisabledFeatures([]);
$check($old !== null && $old > 0.0, 'fixture: without the rule the duplicate beats Pass; scored ' . json_encode($old));
$check($stack('normal')[0] !== 'myHand-0!FSM!', 'the second copy is not played over a healthy first copy');
$check($stack('normal', 1, 'no-unique')[0] === 'myHand-0!FSM!', '@no-unique: it was played (the reported mistake)');
$check($playScore('normal') === -0.5, 'the duplicate play scores below Pass');

// A damaged first copy (2 of 3 HP gone): the fresh copy is a heal, so the new rule does not hold it.
$build($board(2));
$check($playScore('normal') !== -0.5, 'a duplicate over a DAMAGED copy is left to the part-2 scoring');

bot_test_finish();

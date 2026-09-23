<?php
// PROPOSAL 'landomill' (default OFF) — the owner's ruling for Lando Calrissian, Full Sabacc (LAW_018), 2026-09-23.
// "Lando names my own deck early on to ramp up to bombs early. after flip turn (and after my leader unit is killed
// and returned to the leader zone), i either don't use his ability, or i will mill the opponent based on their deck
// and what is left in their deck" — and, on when to skip it: with a spare resource left after the plays you wanted;
// when their deck is low, milling IS the win condition.
//
// The shipped bot mills its OWN deck 50 times out of 50 (20 traced games), which is right before the flip and wrong
// after it. ⚠ The Action is a FRONT-side ability: it cannot be used while the leader is deployed, so the phases are
// pre-flip (EpicActionUsed false) and post-flip-and-returned (EpicActionUsed true, Deployed false).
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php -d apc.enable_cli=1 -d xdebug.mode=off SWUSim/DevTools/tests/bot_landomill_test.php
require __DIR__ . '/fixtures/bot_test_bootstrap.php';
include_once './SWUSim/BotLegalActions.php';
include_once './SWUSim/Custom/BotLookahead.php';
include_once './SWUSim/BotHeuristic.php';

$check(SWUBotVariantDisabled('try-landomill') === ['try:landomill'], 'proposal landomill is registered');
$ON = ['try:landomill'];

// A Lando board: $flipped = the leader has been deployed once and has returned (EpicActionUsed, not deployed).
// Their discard carries $theirDiscard, which is the only read on "their deck" the bot is allowed in live play.
$lando = function (bool $flipped, array $theirDiscard = [], int $res = 5, array $hand = [], int $theirDeck = 30) use ($build) {
    $build(function ($b) use ($flipped, $theirDiscard, $res, $hand, $theirDeck) {
        $b->MyLeader('LAW_018', true, false, $flipped);          // ready, NOT deployed, EpicActionUsed = $flipped
        $b->MyBase('ASH_019');
        $b->FillResourcesForPlayer(1, 'SOR_095', $res);
        foreach ($hand as $c) $b->WithCardInHandForPlayer(1, $c);
        foreach (['ASH_053', 'LAW_133', 'SEC_078'] as $c) $b->WithCardInDeckForPlayer(1, $c);   // my deck: all Vigilance
        $b->TheirLeader('JTL_006', true);
        for ($i = 0; $i < $theirDeck; $i++) $b->WithCardInDeckForPlayer(2, 'JTL_081');
        foreach ($theirDiscard as $c) $b->WithCardInDiscardForPlayer(2, $c);
    });
};
$ability = 'myLeader-0!CustomInput!LeaderAbility';
// Answer the pending decision with the stack. ⚠ The VARIANT is how a proposal reaches a decision:
// SWUBotHeuristicChoose() re-applies SWUBotSetDisabledFeatures() from its variant argument, so setting the features
// around the call does nothing (cost 20 minutes on 2026-09-23).
$answer = function (string $variant) use (&$gameName) {
    $legal = SWUBotLegalActions($gameName, 1);
    $p = SWUBotHeuristicChoose('softcontrol', (array)$legal['actions'], $legal, $variant);
    return strval($p['cardID'] ?? '');
};
$scoreOf = function (string $id, array $on) use ($botCtx) {
    SWUBotSetDisabledFeatures($on); $ctx = $botCtx('softcontrol'); $out = null;
    foreach ($ctx['actions'] as $i => $a) { if (strval($a['cardID']) === $id) $out = SWUBotScoreAction($ctx, $a, $i); }
    SWUBotSetDisabledFeatures([]);
    return $out;
};

// ── PRE-FLIP: own deck, and the aspect every card in my deck carries ────────────────────────────────
$lando(false);
$check(in_array($ability, array_map(fn($a) => strval($a['cardID']), $botCtx('softcontrol')['actions']), true), 'fixture: the leader Action is on offer');
$act(1, 10001, $ability);
$ctx = $botCtx('softcontrol');
$check($ctx['tooltip'] === 'Choose_an_aspect', 'fixture: the aspect prompt is pending (' . $ctx['tooltip'] . ')');
$check($answer('try-landomill') === 'Vigilance', 'pre-flip: names the aspect my own deck is built on');
$act(1, 100, 'Vigilance');
$ctx = $botCtx('softcontrol');
$check($ctx['tooltip'] === 'Discard_from_which_deck?', 'fixture: the deck prompt is pending (' . $ctx['tooltip'] . ')');
$check($answer('try-landomill') === 'Your_deck', 'pre-flip: mills MY deck — the Credit is guaranteed, and it ramps');

// ── POST-FLIP: their deck, and the aspect their DISCARD shows most ─────────────────────────────────
// Their discard is 4 Command/Villainy ships and 1 Vigilance card, so Command (and Villainy) beat Vigilance.
$lando(true, ['JTL_081', 'JTL_081', 'JTL_085', 'SEC_213', 'SEC_078']);
$act(1, 10001, $ability);
$pick = $answer('try-landomill');
$check(in_array($pick, ['Command', 'Villainy'], true), 'post-flip: names the aspect their DISCARD shows most (got ' . $pick . ')');
$act(1, 100, $pick);
$check($answer('try-landomill') === "Opponent's_deck", 'post-flip: mills THEIR deck');

// ── the proposal is what changes it: default still mills my own deck after the flip ────────────────
$lando(true, ['JTL_081', 'JTL_081', 'JTL_085', 'SEC_213', 'SEC_078']);
$act(1, 10001, $ability);
$act(1, 100, $answer(''));
$check($answer('') === 'Your_deck', 'default (shipped): still mills MY deck after the flip — the behaviour being fixed');

// ── WHEN to use it post-flip: after the plays, and not when the big card is already payable ────────
// A 5-cost card in hand with 5 resources: capacity already covers it, so the Credit buys nothing.
$lando(true, [], 5, ['JTL_043']);
$check($scoreOf($ability, $ON) < 0, 'post-flip: skipped while capacity already covers the biggest card in hand', strval($scoreOf($ability, $ON)));
// An 8-cost card with 5 resources: the Credit still ramps, so it is used — but ONLY after the round's real plays,
// so it must score below the play it would otherwise crowd out.
// ⚠ Staging this needs BOTH halves live at once: a ramp target too big to cast (so the ability is not skipped) and
// an affordable play the bot actually wants. Removal does not work as the play — the shipped 'threathold' holds a
// bomb-killer against a low-threat board, and 'dudgate' refuses an event that gains too little — so it is a BODY.
$lando2 = function () use ($build) {
    $build(function ($b) {
        $b->MyLeader('LAW_018', true, false, true); $b->MyBase('ASH_019'); $b->FillResourcesForPlayer(1, 'SOR_095', 7);
        $b->WithCardInHandForPlayer(1, 'ASH_102');          // Ravager (9): the ramp target, NOT castable on 7
        $b->WithCardInHandForPlayer(1, 'ASH_048');          // Commando (4, +2 off-aspect = 6): a body worth playing
        foreach (['ASH_053', 'LAW_133', 'SEC_078'] as $c) $b->WithCardInDeckForPlayer(1, $c);
        $b->TheirLeader('JTL_006', true);
        for ($i = 0; $i < 30; $i++) $b->WithCardInDeckForPlayer(2, 'JTL_081');
        $b->WithGroundUnitForPlayer(2, 'SOR_164', true);
    });
};
$lando2();
$s = $scoreOf($ability, $ON);
$check($s !== null && $s > 0, 'post-flip: used when the Credit still ramps toward the big card', strval($s));
$check($s < $scoreOf('myHand-1!FSM!', $ON), 'post-flip: ranks below the round\'s real plays (spare resource only)', $s . ' vs ' . $scoreOf('myHand-1!FSM!', $ON));
// Their deck nearly empty: milling IS the win condition, so it outranks even the plays. Same board as above — the
// SAME body worth playing — with their deck at 3, so the comparison is against a play the bot really wants (or the
// check would pass on any positive score and miss a lost branch entirely).
$build(function ($b) {
    $b->MyLeader('LAW_018', true, false, true); $b->MyBase('ASH_019'); $b->FillResourcesForPlayer(1, 'SOR_095', 7);
    $b->WithCardInHandForPlayer(1, 'ASH_102');
    $b->WithCardInHandForPlayer(1, 'ASH_048');
    foreach (['ASH_053', 'LAW_133'] as $c) $b->WithCardInDeckForPlayer(1, $c);
    $b->TheirLeader('JTL_006', true);
    for ($i = 0; $i < 3; $i++) $b->WithCardInDeckForPlayer(2, 'JTL_081');   // nearly out
    $b->WithGroundUnitForPlayer(2, 'SOR_164', true);
});
$play = $scoreOf('myHand-1!FSM!', $ON);
$check($play > 0.5, 'fixture: the body play is one the bot wants (' . $play . ')');
$check($scoreOf($ability, $ON) > $play, 'post-flip: with their deck nearly out, the mill comes first',
    $scoreOf($ability, $ON) . ' vs ' . $play);

// ── inert by default, and inert for a leader without the ability ───────────────────────────────────
$lando(true, ['JTL_081']);
$check($scoreOf($ability, []) === $scoreOf($ability, []), 'the default scoring is deterministic');
$build(function ($b) {
    $b->MyLeader('LAW_008', true, false, true); $b->MyBase('ASH_019'); $b->FillResourcesForPlayer(1, 'SOR_095', 5);
    $b->WithGroundUnitForPlayer(1, 'ASH_116', false);
    $b->TheirLeader('JTL_006', true);
});
$check($scoreOf($ability, $ON) === $scoreOf($ability, []), 'another leader (Krennic) is untouched by landomill');

bot_test_finish();

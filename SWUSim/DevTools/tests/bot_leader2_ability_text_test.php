<?php
// Bug #1127 (game 1485163) — a Twin Suns bot froze on its turn. Every bot step for seat 4 (Boba Fett SHD_008 + The
// Warrior HMW_018) died with a PHP fatal inside the scorer, so the controller never moved.
//   1. _SWUBotActionSourceText read GetLeader($seat)[0] for EVERY leader Action. A Twin Suns seat has two leaders, and
//      The Warrior's Action ('myLeader-1') was priced with Boba's text — "give a friendly unit +1/+0 for this phase".
//   2. That text sent 'mgbuff' into the pending decision, which is The Warrior's HAND pick ('myHand-N'). Its "my*" filter
//      let the hand card through to SWUBotUnitView, whose Raid read calls GetUnitsInPlay(null) → TypeError.
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php -d apc.enable_cli=1 -d xdebug.mode=off SWUSim/DevTools/tests/bot_leader2_ability_text_test.php
require __DIR__ . '/fixtures/bot_test_bootstrap.php';
include_once './SWUSim/BotLegalActions.php';
include_once './SWUSim/Custom/BotLookahead.php';
include_once './SWUSim/BotHeuristic.php';

$build(function ($b) {
    $b->MyLeader('SHD_008'); $b->MyLeader2('HMW_018');        // Boba (slot 0), The Warrior (slot 1) — both ready
    $b->FillResourcesForPlayer(1, 'SOR_095', 6);
    $b->WithCardInHandForPlayer(1, 'SHD_100');                 // Modded Cohort — power 2, a legal Warrior pick
    $b->WithCardInHandForPlayer(1, 'SOR_194');                 // Rogue Operative — power 2, a second pick (no auto-resolve)
    $b->WithGroundUnitForPlayer(1, 'SOR_095', true);           // a ready unit, so a phase buff has something to improve
    $b->TheirLeader('ASH_009', true);
    $b->WithGroundUnitForPlayer(2, 'SOR_095', true);
});

$ctx = $botCtx('normal');
$warrior = null; $boba = null;
foreach ($ctx['actions'] as $i => $a) {
    if (strval($a['cardID']) === 'myLeader-1!CustomInput!LeaderAbility') $warrior = [$i, $a];
    if (strval($a['cardID']) === 'myLeader-0!CustomInput!LeaderAbility') $boba = [$i, $a];
}
$check($warrior !== null, "fixture: The Warrior's Action (myLeader-1) is offered");

// 1. The text the scorer reads is the text of the leader whose Action it is.
if ($warrior !== null) {
    $check(str_contains(_SWUBotActionSourceText(1, $warrior[1]), 'Play a unit with 3 or less power'),
        "myLeader-1's Action is read from The Warrior's text, not Boba's");
}

// 2. A hand pick has no unit view (null), rather than a fatal — every decision-param reader is guarded at once,
//    not only mgbuff. Fix 1 alone stops mgbuff reaching the hand, so this is asserted directly.
$check(SWUBotViewForMz(1, 'myHand-0') === null, 'SWUBotViewForMz on a hand card is null, not a fatal');
$check(SWUBotViewForMz(1, 'myGroundArena-0') !== null, 'control: a unit in play still has a view');

// 3. Scoring it — and choosing a move — completes instead of fataling (the freeze).
if ($warrior !== null) {
    $s = SWUBotScoreAction($ctx, $warrior[1], $warrior[0]);
    $check(is_float($s) || is_int($s), "The Warrior's Action is scored (" . var_export($s, true) . ')');
}
$legal = SWUBotLegalActions($gameName, 1);
$pick = SWUBotHeuristicChoose('normal', (array)$legal['actions'], $legal, '');
$check(strval($pick['cardID'] ?? '') !== '', 'the bot picks a move: ' . strval($pick['cardID'] ?? ''));

// ── The same slot-0 read in the leader-specific proposals: Lando ('landomill') and Piett ('piettcheat') ─────────────
// Each in BOTH slots beside Krennic (LAW_008, an Action of his own): the Action is priced as ITS leader's, and the other
// slot's Action is left exactly as it scores with the proposal off.
$scoreOf = function (string $id, array $on) use ($botCtx) {
    SWUBotSetDisabledFeatures($on); $ctx = $botCtx('softcontrol'); $out = null;
    foreach ($ctx['actions'] as $i => $a) { if (strval($a['cardID']) === $id) $out = SWUBotScoreAction($ctx, $a, $i); }
    SWUBotSetDisabledFeatures([]);
    return $out;
};
$slot = fn(int $i) => "myLeader-$i!CustomInput!LeaderAbility";
$LANDO = ['try:landomill']; $PIETT = ['try:piettcheat'];

// Lando flipped and back (EpicActionUsed, not deployed) with their deck at 3: the mill is the win condition → 10.0.
$landoBoard = function (int $landoSlot) use ($build) {
    $build(function ($b) use ($landoSlot) {
        if ($landoSlot === 1) { $b->MyLeader('LAW_008', true, false, true); $b->MyLeader2('LAW_018', true, false, true); }
        else                  { $b->MyLeader('LAW_018', true, false, true); $b->MyLeader2('LAW_008', true, false, true); }
        $b->MyBase('ASH_019'); $b->FillResourcesForPlayer(1, 'SOR_095', 5);
        $b->WithGroundUnitForPlayer(1, 'ASH_116', false);           // Krennic's Action needs a friendly unit
        foreach (['ASH_053', 'LAW_133'] as $c) $b->WithCardInDeckForPlayer(1, $c);
        $b->TheirLeader('JTL_006', true);
        for ($i = 0; $i < 3; $i++) $b->WithCardInDeckForPlayer(2, 'JTL_081');
        $b->WithCardInDiscardForPlayer(2, 'JTL_081');
    });
};
foreach ([1, 0] as $ls) {
    $landoBoard($ls);
    $ks = 1 - $ls;
    $check($scoreOf($slot($ls), $LANDO) === 10.0, "landomill, Lando in slot $ls: his Action is the mill-out (10.0); got " . var_export($scoreOf($slot($ls), $LANDO), true));
    $k = $scoreOf($slot($ks), []);
    $check($k !== null && $scoreOf($slot($ks), $LANDO) === $k, "landomill, Lando in slot $ls: Krennic's Action (slot $ks) is untouched ($k)");
}
// …and the prompts his Action raises are answered as Lando's from slot 1: post-flip, mill THEIR deck.
$landoBoard(1);
$act(1, 10001, $slot(1));
$answer = function () use (&$gameName) {
    $legal = SWUBotLegalActions($gameName, 1);
    return strval(SWUBotHeuristicChoose('softcontrol', (array)$legal['actions'], $legal, 'try-landomill')['cardID'] ?? '');
};
$act(1, 100, $answer());                                            // the aspect
$check($answer() === "Opponent's_deck", "landomill, Lando in slot 1: post-flip the mill targets THEIR deck");

// Piett (JTL_005): "Play a Capital Ship unit from your hand. It costs 1 resource less." — a castable Corvus in hand.
$piettBoard = function (int $piettSlot) use ($build) {
    $build(function ($b) use ($piettSlot) {
        if ($piettSlot === 1) { $b->MyLeader('LAW_008', true, false, true); $b->MyLeader2('JTL_005'); }
        else                  { $b->MyLeader('JTL_005'); $b->MyLeader2('LAW_008', true, false, true); }
        $b->FillResourcesForPlayer(1, 'SOR_095', 8);
        $b->WithGroundUnitForPlayer(1, 'ASH_116', false);
        $b->WithCardInHandForPlayer(1, 'JTL_038');                  // Corvus (5), a Capital Ship
        $b->TheirLeader('JTL_006', true);
        $b->WithSpaceUnitForPlayer(2, 'JTL_081', true);
    });
};
foreach ([1, 0] as $ps) {
    $piettBoard($ps);
    $ks = 1 - $ps;
    $p = $scoreOf($slot($ps), $PIETT); $full = $scoreOf('myHand-0!FSM!', $PIETT);
    $check($p !== null && $p > $full, "piettcheat, Piett in slot $ps: the discounted play outranks the full-price one ($p vs $full)");
    $k = $scoreOf($slot($ks), []);
    $check($k !== null && $scoreOf($slot($ks), $PIETT) === $k, "piettcheat, Piett in slot $ps: Krennic's Action (slot $ks) is untouched ($k)");
}

bot_test_finish();

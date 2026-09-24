<?php
// PROPOSAL 'ctxpower' (default OFF, "@try-ctxpower") — value a HAND card by what it is worth ON THE CURRENT
// BOARD, not by its printed stats. Resourcing AND play scoring (owner choice 2026-09-24).
//
// WHY. Block 3 of the owner's run (25 games, hyperaggro Vader JTL vs Ahsoka ASH Blue): the bot buried
// JTL_115 Clone Combat Squadron in resources **17 times** and played it 3. Its effective power when buried
// averaged 5.9 and peaked at 9; when played it averaged 4.3. It buries the card when it is BIG and plays it
// when it is SMALL, because the resourcer reads printed cost 4 / power 3 and the aggro wing's whole rule is
// `-$cost` ("resource the most expensive"). The 3 copies that did get cast dealt 35 base damage between
// them, against a bot average of 17.9 damage per WHOLE GAME.
//
// Owner, 2026-09-24:
//   · JTL_115 "a 4/3/3 would be weak and easy to resource if i have no board. but when my board has 3+
//     units, this is a big unit especially with Victor Leader on the board."
//   · SEC_179 Aggressive Negotiations "its value increases in hyper aggro if they hold onto cards after the
//     6R turn ... each round increases its value by +2 damage to base with a unit that is able to hit base
//     (eg. no Sentinels)."
// The round-6 rule is deliberately NOT coded: if the seat stops resourcing, hand size grows by 2 a round on
// its own, so pricing the hand size reproduces the owner's +2/round without a round-number special case.
//
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php -d apc.enable_cli=1 -d xdebug.mode=off SWUSim/DevTools/tests/bot_ctxpower_test.php
require __DIR__ . '/fixtures/bot_test_bootstrap.php';
include_once './SWUSim/BotLegalActions.php';
include_once './SWUSim/Custom/BotLookahead.php';
include_once './SWUSim/BotHeuristic.php';

$check(SWUBotVariantDisabled('try-ctxpower') === ['try:ctxpower'], "proposal ctxpower is registered");

$ON  = ['try:ctxpower'];
$OFF = [];

// ── 1. JTL_115 Clone Combat Squadron: printed 3/3, "+1/+1 for each other friendly space unit" ──────────
// Vader JTL hyperaggro seat. $space = the friendly space board it is being judged against.
$board = function (array $space, array $hand = ['JTL_115'], array $theirGround = []) use ($build) {
    $build(function ($b) use ($space, $hand, $theirGround) {
        $b->MyLeader('JTL_006', false, false, true); $b->MyBase('ASH_026');
        $b->FillResourcesForPlayer(1, 'SOR_095', 6, false);
        foreach ($space as $c) $b->WithSpaceUnitForPlayer(1, $c);
        foreach ($hand as $c) $b->WithCardInHandForPlayer(1, $c);
        $b->TheirLeader('ASH_009', true);
        foreach ($theirGround as $c) $b->WithGroundUnitForPlayer(2, $c);
    });
};
// ⚠ SWUBotContextSurplus returns 0.0 unless the proposal is ON, so the flag must be set for every direct
// call — without it the empty-board and Sentinel assertions below pass for the WRONG reason (0 == 0).
$surplus = function (string $cid) {
    SWUBotSetDisabledFeatures(['try:ctxpower']);
    $v = SWUBotContextSurplus(1, $cid);
    SWUBotSetDisabledFeatures([]);
    return $v;
};

$board([]);
$check($surplus('JTL_115') == 0.0, 'JTL_115 on an EMPTY space board has no surplus — a 4-cost 3/3, correctly resourceable');

$board(['JTL_T01', 'JTL_T01', 'JTL_T01']);
$check($surplus('JTL_115') == 3.0, 'JTL_115 with 3 other friendly space units is +3 (a 6/6)');

// Victor Leader counts TWICE and both are real: it is another friendly space unit (+1/+1 from JTL_115's own
// text) AND it grants "each other friendly space unit gets +1/+1", which JTL_115 receives.
$board(['JTL_085', 'JTL_T01', 'JTL_T01']);
$check($surplus('JTL_115') == 4.0, 'Victor Leader + 2 TIEs is +4, not +3 — VL is a body AND a buff (an 7/7)');

// ── 2. SEC_179 Aggressive Negotiations: "+1/+0 for each card in your hand", needs a lane to the base ────
// The event is no longer in hand when it resolves, so the scaling counts the OTHER cards.
$board(['JTL_T01'], ['SEC_179', 'JTL_115', 'JTL_085', 'ASH_194']);
$check($surplus('SEC_179') == 3.0, 'SEC_179 counts the 3 OTHER cards in hand, not itself');

// A Sentinel wall means no unit can reach the base, so the scaling is worth nothing this turn.
// ASH_109 T-6 Shuttle 1974 has printed Sentinel and sits in the GROUND arena, where the attacker is.
$build(function ($b) {
    $b->MyLeader('JTL_006', false, false, true); $b->MyBase('ASH_026');
    $b->FillResourcesForPlayer(1, 'SOR_095', 6, false);
    $b->WithGroundUnitForPlayer(1, 'SOR_095');
    foreach (['SEC_179', 'JTL_115', 'JTL_085', 'ASH_194'] as $c) $b->WithCardInHandForPlayer(1, $c);
    $b->TheirLeader('ASH_009', true);
    $b->WithGroundUnitForPlayer(2, 'ASH_109');
});
$check($surplus('SEC_179') == 0.0, 'SEC_179 behind an enemy Sentinel wall has NO surplus — nothing can hit the base');

// ── 3. a card with no context scaling is untouched ─────────────────────────────────────────────────────
$board(['JTL_T01', 'JTL_T01', 'JTL_T01']);
$check($surplus('ASH_194') == 0.0, 'a plain unit (ASH_194 Snub Fighter Squadron) has no surplus');

// ── 4. RESOURCING: the surplus must actually rescue the card ───────────────────────────────────────────
$picks = function (array $flags) use ($botCtx) {
    SWUBotSetDisabledFeatures($flags);
    $mz = SWUBotChooseResourceCards($botCtx('hyperaggro'), 1);
    SWUBotSetDisabledFeatures([]);
    $out = [];
    foreach ((array)$mz as $m) {
        $o = GetHand(1)[intval(substr($m, strlen('myHand-')))] ?? null;
        if ($o !== null) $out[] = strval($o->CardID ?? '');
    }
    return $out;
};
// A wide space board. JTL_115 (cost 4) is the most expensive card in hand, so `-$cost` picks it today.
$board(['JTL_085', 'JTL_T01', 'JTL_T01'], ['JTL_115', 'SEC_189', 'JTL_T01']);
$check(in_array('JTL_115', $picks($OFF), true), 'BASELINE: the aggro wing resources JTL_115 — the most expensive card — even as a 7/7');
$check(!in_array('JTL_115', $picks($ON), true), 'ctxpower ON: a 7/7 JTL_115 is NO LONGER the resource pick');

// ...but on an EMPTY board it still is, which is the owner's distinction.
$board([], ['JTL_115', 'SEC_189', 'JTL_T01']);
$check(in_array('JTL_115', $picks($ON), true), 'ctxpower ON: with NO space board JTL_115 is still resourced (a 4-cost 3/3)');

// ── 5. PLAY scoring: the same surplus lifts the play value ─────────────────────────────────────────────
$playValue = function (string $cid, array $flags) use ($botCtx) {
    SWUBotSetDisabledFeatures($flags);
    $W = SWUBotWeights('hyperaggro', 1);
    $v = _SWUBotPlayValue(1, $cid, $W);
    SWUBotSetDisabledFeatures([]);
    return $v;
};
$board(['JTL_085', 'JTL_T01', 'JTL_T01']);
$check($playValue('JTL_115', $ON) > $playValue('JTL_115', $OFF), 'ctxpower ON raises the PLAY value of a 7/7 JTL_115');
$check($playValue('ASH_194', $ON) == $playValue('ASH_194', $OFF), 'a card with no scaling has an unchanged play value');
$board([]);
$check($playValue('JTL_115', $ON) == $playValue('JTL_115', $OFF), 'on an empty board ctxpower changes nothing — the proposal is a SURPLUS, not a bonus');

bot_test_finish();

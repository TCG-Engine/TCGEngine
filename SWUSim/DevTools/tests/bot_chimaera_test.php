<?php
// ASH_052 Chimaera — "When Played: you MAY choose a friendly unit AND an enemy non-leader unit; defeat those."
// The card asks for the FRIENDLY unit first, and declining it cancels the enemy kill too. The bot priced that first pick
// as a pure sacrifice (PASS -1.0 vs -SacrificeCost(friendly)) and never counted the enemy unit it buys, so across the
// 2026-10-01 baseline (7,020 games) Chimaera's When Played resolved in only 19% of 3,895 plays — found from the owner's
// Karabast logs, where it resolved 3 of 3 and decided games. A PAIRED defeat must weigh what it kills.
// Driven through the real play path (hand -> When Played prompt -> the heuristic stack's own choice).
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php -d apc.enable_cli=1 -d xdebug.mode=off SWUSim/DevTools/tests/bot_chimaera_test.php
require __DIR__ . '/fixtures/bot_test_bootstrap.php';
include_once './SWUSim/BotLegalActions.php';
include_once './SWUSim/BotHeuristic.php';

const CHIMAERA = 'ASH_052';  // 7, Vigilance/Villainy, 6/6 space
const MARINE = 'SOR_095';    // 3/3 vanilla, 2-cost
const CONSULAR = 'SOR_046';  // 3/7 vanilla, 4-cost
const TROOPER = 'SOR_128';   // 3/1 vanilla, 1-cost

// Plays Chimaera from hand and returns what the bot answers to "Choose a friendly unit": the friendly unit's CardID,
// 'PASS', or a diagnostic.
$friendlyPick = function (array $mine, array $theirs, string $variant = '') use (&$gameName, $build, $act) {
    $build(function ($b) use ($mine, $theirs) {
        $b->MyLeader('LAW_008', true, false, true); $b->MyBase('JTL_020'); $b->TheirBase('SOR_020');   // Vigilance base: on-aspect
        $b->FillResourcesForPlayer(1, MARINE, 10);
        $b->WithCardInHandForPlayer(1, CHIMAERA);
        foreach ($mine as $c) $b->WithGroundUnitForPlayer(1, $c, true);
        foreach ($theirs as $c) $b->WithGroundUnitForPlayer(2, $c, true);
    });
    $legal = SWUBotLegalActions($gameName, 1);
    $play = null;
    foreach ((array)$legal['actions'] as $a) if (str_starts_with(strval($a['cardID'] ?? ''), 'myHand-0')) { $play = $a; break; }
    if ($play === null) return 'Chimaera was not playable';
    $act(1, intval($play['mode'] ?? 10002), strval($play['cardID']));
    $legal2 = SWUBotLegalActions($gameName, 1);
    if (($legal2['kind'] ?? '') !== 'decision') return 'no prompt (kind=' . strval($legal2['kind'] ?? '') . ')';
    if (stripos(strval($legal2['decisionTooltip'] ?? ''), 'friendly') === false) return 'unexpected prompt: ' . strval($legal2['decisionTooltip'] ?? '');
    $pick = SWUBotHeuristicChoose('midrange', (array)$legal2['actions'], $legal2, $variant);
    $mz = strval($pick['cardID'] ?? '');
    if ($mz === 'PASS' || $mz === '-' || $mz === '') return 'PASS';
    $v = SWUBotViewForMz(1, $mz);
    return $v === null ? $mz : strval($v['cardID']);
};

// A: my vanilla 3/3 for THEIR 3/7 — a good trade the bot used to refuse.
$got = $friendlyPick([MARINE], [CONSULAR]);
$check($got === MARINE, 'my 2-cost Marine for their 4-cost Consular: Chimaera TAKES the deal (got ' . $got . ')');

// B: my 3/7 for their 1-cost 3/1 — trading my better unit down is still refused.
$got = $friendlyPick([CONSULAR], [TROOPER]);
$check($got === 'PASS', 'my 4-cost Consular for their 1-cost Trooper: Chimaera DECLINES (got ' . $got . ')');

// C: the trade is priced at the BEST enemy kill, so the enemy pick that follows must go to it. Their Consular (4) and
// Trooper (1): take the deal, then defeat the Consular — and the board shows both defeats.
$got = $friendlyPick([MARINE], [CONSULAR, TROOPER]);
$check($got === MARINE, 'with a Consular and a Trooper across: the deal is taken (got ' . $got . ')');
// $friendlyPick only READS the choice — apply it now, so the next prompt is the enemy pick.
$legal = SWUBotLegalActions($gameName, 1);
$first = SWUBotHeuristicChoose('midrange', (array)$legal['actions'], $legal, '');
$act(1, intval($first['mode'] ?? 10001), strval($first['cardID']));
$legal = SWUBotLegalActions($gameName, 1);
$check(stripos(strval($legal['decisionTooltip'] ?? ''), 'enemy') !== false, 'the second prompt is the enemy pick: ' . strval($legal['decisionTooltip'] ?? ''));
$theirs = fn() => array_map(fn($v) => $v['cardID'], SWUBotUnits(2));
if (($legal['kind'] ?? '') === 'decision') {
    $pick = SWUBotHeuristicChoose('midrange', (array)$legal['actions'], $legal, '');
    $pv = SWUBotViewForMz(1, strval($pick['cardID'] ?? ''));
    $check($pv !== null && $pv['cardID'] === CONSULAR, 'the enemy pick goes to the Consular, the kill the trade was priced on (got ' . json_encode($pv['cardID'] ?? $pick['cardID'] ?? null) . ')');
    $act(1, intval($pick['mode'] ?? 10001), strval($pick['cardID']));
}
$check($theirs() === [TROOPER] && !in_array(MARINE, array_map(fn($v) => $v['cardID'], SWUBotUnits(1)), true),
    'after it resolves: their Consular and my Marine are gone, their Trooper is left (theirs: ' . json_encode($theirs()) . ')');

bot_test_finish();

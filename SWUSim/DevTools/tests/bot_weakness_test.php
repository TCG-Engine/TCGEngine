<?php
// Weakness tokens are hostile: a distribution puts them on enemy units, and a Weakness event with nothing to hit is
// held. FOUND 2026-09-15 in a live Bot Practice game (189011, owner report): "P2's Ravage gave a Weakness token to P2's
// 0-0-0" ×3, "P2's Ravage defeated P2's 0-0-0 — there was no reason to defeat their own unit. Weakness tokens are a
// disadvantage". HMW_071 Ravage: "Distribute up to 3 Weakness tokens among any number of units." (Weakness HMW_T02 =
// -1/-1, unpreventable.) Its MZSPLITASSIGN prompt does not say "damage", so the split scorer ('splits') never read it
// and the first enumerated split — all three on my own unit — was taken; the card is untagged, so the dud gate never
// saw it either.
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php -d apc.enable_cli=1 -d xdebug.mode=off SWUSim/DevTools/tests/bot_weakness_test.php
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
$check(_SWUBotIsEffectEvent('HMW_071'), 'a Weakness-token event is an effect event (the dud gate reads it)');

// The live shape: my 0-0-0 (4/4) and their Marine (3/3) + TIE/ln (2/1). Ravage's three tokens go on THEIR units.
$build(function ($b) { $b->MyLeader('SOR_014', false); $b->FillResourcesForPlayer(1, 'SOR_095', 8); $b->WithCardInHandForPlayer(1, 'HMW_071');
    $b->WithGroundUnitForPlayer(1, 'LAW_174', true); $b->WithGroundUnitForPlayer(2, 'SOR_095', true); $b->WithSpaceUnitForPlayer(2, 'SOR_225', true); });
$act(1, 10002, 'myHand-0!FSM!');
$ctx = $botCtx('normal');
$check($ctx['type'] === 'MZSPLITASSIGN', 'fixture: Ravage asks for a split; got ' . $ctx['type'] . ' ' . $ctx['tooltip']);
$pick = strval($stack('normal')[0]);
$mine = 0; $theirs = 0;
foreach (explode(',', $pick) as $pair) {
    [$mz, $n] = array_pad(explode(':', $pair), 2, 0);
    if (str_starts_with(trim($mz), 'my')) $mine += intval($n); elseif (str_starts_with(trim($mz), 'their')) $theirs += intval($n);
}
$check($mine === 0 && $theirs > 0, 'the tokens go on enemy units, none on mine; got ' . $pick);

// No enemy unit: Ravage can only hurt me — it is held.
$build(function ($b) { $b->MyLeader('SOR_014', false); $b->FillResourcesForPlayer(1, 'SOR_095', 8); $b->WithCardInHandForPlayer(1, 'HMW_071');
    $b->WithGroundUnitForPlayer(1, 'LAW_174', true); });
$check($playScore('normal') === -0.5, 'with no enemy unit Ravage is held; scored ' . json_encode($playScore('normal')));

bot_test_finish();

<?php
// Feature 'nogift' (group p9) — the bot never makes a play whose best line still makes the opponent stronger.
// FOUND in Bug Report #1066 (game 1097180, 2026-09-22): "Used Shield Drive Outfitter and he got 2 shield tokens with
// his 1 cost effect". The engine was right — the second Shield was Arenabot's ASH_089 Perseverance ("Heal 3 damage
// from a unit and give a Shield token to it"), played with no friendly unit, so its only legal target was the
// reporter's LAW_113. Owner ruling: "the bot should not give any advantageous plays to the opponent".
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php -d apc.enable_cli=1 -d xdebug.mode=off SWUSim/DevTools/tests/bot_nogift_test.php
require __DIR__ . '/fixtures/bot_test_bootstrap.php';
include_once './SWUSim/BotLegalActions.php';
include_once './SWUSim/BotHeuristic.php';
$ids = fn($acts) => array_map(fn($a) => strval($a['cardID']), $acts);
$stack = function (string $style, int $seat = 1, string $variant = '') use (&$gameName) {
    SWUBotResetCoverage(); $legal = SWUBotLegalActions($gameName, $seat);
    $p = SWUBotHeuristicChoose($style, (array)$legal['actions'], $legal, $variant);
    return $p === null ? null : strval($p['cardID']);
};
$playScore = function (string $style, string $cardID, array $disabled = []) use ($botCtx) {
    SWUBotSetDisabledFeatures($disabled);
    $ctx = $botCtx($style);
    $s = null;
    foreach ($ctx['actions'] as $i => $a) if (strval($a['cardID']) === $cardID) { $s = SWUBotScoreAction($ctx, $a, $i); break; }
    SWUBotSetDisabledFeatures([]);
    return $s;
};
$play = 'myHand-0!FSM!';

// SHIPPED on the owner's ruling (like p7): default ON, switchable, and '@no-p9' is the stack before it.
$check(in_array('nogift', SWUBotFeatureList(), true), 'nogift is shipped behaviour (defaults on)');
$check(SWUBotVariantDisabled('no-nogift') === ['nogift'], 'nogift is switchable');
$check(SWUBotFeatureGroups()['p9'] === ['nogift'], 'group p9 is the stack before it');

// The bot's leader is spent (exhausted, Epic Action used), so the hand card is its only real play.
$board = function (callable $extra) use ($build) {
    $build(function ($b) use ($extra) {
        $b->MyLeader('SOR_014', false, false, true);
        $b->FillResourcesForPlayer(1, 'SOR_095', 6);
        $extra($b);
    });
};

// ── A) THE REPORTED SHAPE — Perseverance, and the only unit to heal is the opponent's damaged one ────────────────
$board(function ($b) {
    $b->WithCardInHandForPlayer(1, 'ASH_089');
    $b->WithGroundUnitForPlayer(2, 'LAW_113', true, 2);       // enemy Shield Drive Outfitter, 2 damage
});
$check(in_array($play, $ids($botCtx('normal')['actions']), true), 'fixture A: Perseverance is on offer');
$check($playScore('normal', $play) === -0.5, 'A: healing + shielding the ENEMY unit is held (-0.5)');
$check($stack('normal') !== $play, 'A: the stack does not play it');
// The reported play, still reproducible with the feature off — so A cannot pass vacuously.
$off = $playScore('normal', $play, ['nogift']);
$check($off !== null && $off > 0.0, "A @no-nogift: the gift scores positive (the reported mistake); got " . json_encode($off));
$check($stack('normal', 1, 'no-nogift') === $play, 'A @no-nogift: the stack plays it, as in game 1097180');

// ── B) A Shield alone is a gift too — the enemy unit is UNDAMAGED, so only the Shield lands ──────────────────────
// _SWUBotSideValue ignores Shields; the gift read must not.
$board(function ($b) {
    $b->WithCardInHandForPlayer(1, 'ASH_089');
    $b->WithGroundUnitForPlayer(2, 'LAW_113', true, 0);
});
$check($playScore('normal', $play) === -0.5, 'B: a Shield on an undamaged enemy unit is held (-0.5)');

// ── C) CONTROL — with a damaged friendly unit the best line helps ME, so Perseverance is played ─────────────────
$board(function ($b) {
    $b->WithCardInHandForPlayer(1, 'ASH_089');
    $b->WithGroundUnitForPlayer(1, 'LAW_113', true, 2);       // my damaged Outfitter
    $b->WithGroundUnitForPlayer(2, 'LAW_113', true, 2);       // and the enemy's
});
$c = $playScore('normal', $play);
$check($c !== null && $c > 0.0, "C: Perseverance on my own damaged unit is still valued; got " . json_encode($c));

// ── D) CONTROL — a UNIT whose When Played shields "a unit" can shield ITSELF, so it is no gift ─────────────────
$board(function ($b) {
    $b->WithCardInHandForPlayer(1, 'LAW_113');
    $b->WithGroundUnitForPlayer(2, 'LAW_113', true, 0);
});
$d = $playScore('normal', $play);
$check($d !== null && $d !== -0.5, "D: playing Shield Drive Outfitter next to an enemy unit is not held; got " . json_encode($d));

// ── E) A STAT-ONLY UPGRADE with only enemy hosts (game 1438045, 2026-10-01) ─────────────────────────────────────
// The owner killed Arenabot's last unit (Lobot) and the bot played LAW_129 Mastery (+3/+3) on the owner's Huyang.
// Mastery's text is only its cost reduction — its whole benefit is the PRINTED +3/+3 — so the text pre-filter
// ("heal|give|ready|attach|gets +") never ran the gift lookahead and the play scored as ordinary development.
$board(function ($b) {
    $b->WithCardInHandForPlayer(1, 'LAW_129');
    $b->WithGroundUnitForPlayer(2, 'ASH_056', true, 0);       // enemy Huyang
    $b->WithGroundUnitForPlayer(2, 'LAW_039', true, 0);       // enemy Latts Razzi
});
$check(in_array($play, $ids($botCtx('normal')['actions']), true), 'fixture E: Mastery is on offer');
$check($playScore('normal', $play) === -0.5, 'E: +3/+3 on an ENEMY unit (no friendly host) is held (-0.5)');
$check($stack('normal') !== $play, 'E: the stack does not play it');
$offE = $playScore('normal', $play, ['nogift']);
$check($offE !== null && $offE > 0.0, "E @no-nogift: the gift scores positive (the reported mistake); got " . json_encode($offE));

// ── F) CONTROL — the same Mastery with a FRIENDLY host goes on my unit, so it is no gift ───────────────────────
$board(function ($b) {
    $b->WithCardInHandForPlayer(1, 'LAW_129');
    $b->WithGroundUnitForPlayer(1, 'SOR_095', true, 0);       // my Battlefield Marine
    $b->WithGroundUnitForPlayer(2, 'ASH_056', true, 0);
});
$f = $playScore('normal', $play);
$check($f !== null && $f > 0.0, "F: Mastery with a friendly host is still played; got " . json_encode($f));

// ── G) CONTROL — a DEBUFF upgrade is MEANT for an enemy unit: TWI_070 Perilous Position (-2/-2, exhaust it) ─────
$board(function ($b) {
    $b->WithCardInHandForPlayer(1, 'TWI_070');
    $b->WithGroundUnitForPlayer(2, 'ASH_056', true, 0);
});
$g = $playScore('normal', $play);
$check($g !== null && $g > 0.0, "G: a -2/-2 upgrade on the only (enemy) unit is not held as a gift; got " . json_encode($g));

bot_test_finish();

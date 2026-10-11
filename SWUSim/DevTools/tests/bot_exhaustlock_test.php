<?php
// 2026-10-10 human-game lever screen. 'exhaustlock': an exhaust effect denies the opponent's best ready attacker its swing
// this round, so it is worth ~the base damage that attack threatened — a board-scaled bonus on top of the flat 'exhaust' weight,
// zero against an empty/exhausted enemy board. From the Vane (Hold For Questioning / Interrogation Droid / Preparation) and
// Hemlock tempo decks in Ninin's logs — the exhaust-lock plan the deferred tempo layer is meant to value.
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php -d apc.enable_cli=1 -d xdebug.mode=off SWUSim/DevTools/tests/bot_exhaustlock_test.php
require __DIR__ . '/fixtures/bot_test_bootstrap.php';
include_once './SWUSim/BotLegalActions.php';
include_once './SWUSim/BotHeuristic.php';

$check(in_array('exhaustlock', SWUBotProposalList(), true) && SWUBotVariantDisabled('try-exhaustlock') === ['try:exhaustlock'], 'exhaustlock is a switchable proposal');
$check(in_array('exhaust', SWUBotCardTags('SOR_186'), true), 'premise: SOR_186 (Exhaust a unit) carries the exhaust tag');

$build(function ($b) {});   // SWUBotWeights reads the board (racing rank): build one first, or it walks null arenas
$W = SWUBotWeights('midrange', 1);
$delta = function (string $cid, array $variant) use ($build, $W) {
    SWUBotSetDisabledFeatures([]);      $off = _SWUBotPlayValue(1, $cid, $W);
    SWUBotSetDisabledFeatures($variant); $on = _SWUBotPlayValue(1, $cid, $W); SWUBotSetDisabledFeatures([]);
    return $on - $off;
};

// Opponent has a ready 5-power attacker: exhausting it is worth W['base'] x 5.
$build(function ($b) { $b->WithGroundUnitForPlayer(2, 'SOR_038', true); });   // SOR_038 = 5 power, ready
$check(abs($delta('SOR_186', ['try:exhaustlock']) - ($W['base'] ?? 0.6) * 5) < 1e-9, 'exhaustlock: exhaust is worth base x the enemy best ready attacker (5)');
$check(abs($delta('SOR_038', ['try:exhaustlock'])) < 1e-9, 'exhaustlock: a non-exhaust card is unchanged');

// An exhausted enemy attacker denies nothing this round -> no bonus.
$build(function ($b) { $b->WithGroundUnitForPlayer(2, 'SOR_038', false); });  // not ready
$check(abs($delta('SOR_186', ['try:exhaustlock'])) < 1e-9, 'exhaustlock: no bonus when the enemy has no READY attacker');

// Empty enemy board -> no bonus.
$build(function ($b) {});
$check(abs($delta('SOR_186', ['try:exhaustlock'])) < 1e-9, 'exhaustlock: no bonus against an empty enemy board');

bot_test_finish();

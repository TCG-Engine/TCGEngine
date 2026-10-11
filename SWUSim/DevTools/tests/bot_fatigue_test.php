<?php
// 2026-10-10 human-game lever screen. Fatigue / deck-out family (proposals 'fatiguewin', 'fatiguehorizon3',
// 'fatiguehorizon8', 'fatigueself', 'millcredit'). Derived from Ninin's Maul vs Hemlock log, which was decided by a
// deck-out finish (CR §6.1: a player who cannot draw takes 3 damage per undrawable card; a regroup draws 2) — nothing
// modelled the opponent's (or one's own) deck-out as a clock.
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php -d apc.enable_cli=1 -d xdebug.mode=off SWUSim/DevTools/tests/bot_fatigue_test.php
require __DIR__ . '/fixtures/bot_test_bootstrap.php';
include_once './SWUSim/BotLegalActions.php';
include_once './SWUSim/BotHeuristic.php';

foreach (['fatiguewin', 'fatiguehorizon3', 'fatiguehorizon8', 'fatigueself', 'millcredit'] as $p) {
    $check(in_array($p, SWUBotProposalList(), true) && SWUBotVariantDisabled("try-$p") === ["try:$p"], "$p is a switchable proposal");
}

// ── SWUBotFatigueDamageSoon: deck-out damage projected over horizon/2 regroups, 0 while the deck is above the horizon.
$fat = function (int $deck, array $variant) use ($build, $check) {
    $build(function ($b) use ($deck) { for ($i = 0; $i < $deck; $i++) $b->WithCardInDeckForPlayer(2, 'SOR_038'); });
    SWUBotSetDisabledFeatures($variant); $v = SWUBotFatigueDamageSoon(2); SWUBotSetDisabledFeatures([]); return $v;
};
$check($fat(0, ['try:fatiguewin']) === 18, 'fatiguewin (h5): empty deck projects 18 deck-out damage (3 regroups x 6)');
$check($fat(2, ['try:fatiguewin']) === 12, 'fatiguewin (h5): deck 2 -> 12');
$check($fat(6, ['try:fatiguewin']) === 0,  'fatiguewin (h5): deck 6 is above the horizon -> 0');
$check($fat(0, ['try:fatiguehorizon3']) === 12, 'horizon3: empty deck -> 12 (2 regroups)');
$check($fat(4, ['try:fatiguehorizon3']) === 0,  'horizon3: deck 4 is above the horizon -> 0');
$check($fat(0, ['try:fatiguehorizon8']) === 24, 'horizon8: empty deck -> 24 (4 regroups)');
$check($fat(6, ['try:fatiguehorizon8']) === 6,  'horizon8: deck 6 -> 6');

// ── SWUBotEffectiveBaseHp: gated — printed HP when off, printed minus deck-out when on (floored at 1).
$build(function ($b) {});   // seat 2 deck empty, base undamaged
$hp = SWUBaseRemainingHp(2);
SWUBotSetDisabledFeatures([]);                  $check(SWUBotEffectiveBaseHp(2) === $hp, 'off: effective base HP == printed remaining HP');
SWUBotSetDisabledFeatures(['try:fatiguewin']);  $check(SWUBotEffectiveBaseHp(2) === max(1, $hp - 18), 'fatiguewin: effective base HP reduced by imminent deck-out'); SWUBotSetDisabledFeatures([]);

// ── SWUBotLethalNextRound reads effective HP: a board short of the printed base is lethal once deck-out is counted.
$build(function ($b) { for ($i = 0; $i < 4; $i++) $b->WithGroundUnitForPlayer(1, 'SOR_038', true); });   // 4 x 5 power = 20, vs a 30 HP base
$check(SWUBotBasePotential(1, 2, false) === 20, 'premise: my board potential is 20 against a 30 HP base');
SWUBotSetDisabledFeatures([]);                  $check(SWUBotLethalNextRound(1, 2) === false, 'off: 20 < 30, not lethal next round');
SWUBotSetDisabledFeatures(['try:fatiguewin']);  $check(SWUBotLethalNextRound(1, 2) === true,  'fatiguewin: 20 >= 30-18 effective HP, lethal next round'); SWUBotSetDisabledFeatures([]);

// ── fatigueself: once a draw would empty my deck and I have a board, draw is suppressed below its normal low-deck value.
$build(function ($b) { for ($i = 0; $i < 2; $i++) $b->WithGroundUnitForPlayer(1, 'SOR_038', true); });   // my deck empty, I have a board
SWUBotSetDisabledFeatures([]);                   $check(SWUBotDrawMultiplier(1) === -1.0, 'off: an empty deck already makes draw -1.0');
SWUBotSetDisabledFeatures(['try:fatigueself']);  $check(SWUBotDrawMultiplier(1) === -2.0, 'fatigueself: empty deck + a board suppresses draw to -2.0'); SWUBotSetDisabledFeatures([]);

// ── millcredit: milling the opponent's deck is worth its deck-out damage, but only while their deck is low.
$check(_SWUBotOppMillAmount('SOR_058') === 6, 'oppMill: SOR_058 discards 6 cards from an opponent deck');
$check(_SWUBotOppMillAmount('SOR_038') === 0, 'oppMill: a vanilla unit mills nothing');
$W = SWUBotWeights('midrange', 1);
$build(function ($b) { for ($i = 0; $i < 3; $i++) $b->WithCardInDeckForPlayer(2, 'SOR_038'); });   // opp deck 3 (<= horizon 5)
SWUBotSetDisabledFeatures([]);                   $base = _SWUBotPlayValue(1, 'SOR_058', $W);
SWUBotSetDisabledFeatures(['try:millcredit']);   $low  = _SWUBotPlayValue(1, 'SOR_058', $W); SWUBotSetDisabledFeatures([]);
$check(abs(($low - $base) - ($W['base'] ?? 0.6) * 3 * 6) < 1e-9, 'millcredit: play value rises by base*3*6 when the opponent deck is low');
$build(function ($b) { for ($i = 0; $i < 20; $i++) $b->WithCardInDeckForPlayer(2, 'SOR_038'); });   // opp deck 20 (deep)
SWUBotSetDisabledFeatures([]);                   $deepBase = _SWUBotPlayValue(1, 'SOR_058', $W);
SWUBotSetDisabledFeatures(['try:millcredit']);   $deep     = _SWUBotPlayValue(1, 'SOR_058', $W); SWUBotSetDisabledFeatures([]);
$check(abs($deep - $deepBase) < 1e-9, 'millcredit: no bonus while the opponent deck is deep');

bot_test_finish();

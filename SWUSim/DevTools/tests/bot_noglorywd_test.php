<?php
// 2026-10-10 human-game lever screen. 'noglorywd': "take control of a unit, then defeat it" (JTL_043 No Glory, Only Results) — the unit dies
// under MY control, so its When Defeated fires for me. From Ninin's Maul vs Hemlock log: Kyzzy No Glory'd Ninin's Annihilator (JTL_041) and
// its When Defeated killed Ninin's Anakin and stripped the other Anakin from hand. The take-control picker ranked targets by unit value only.
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php -d apc.enable_cli=1 -d xdebug.mode=off SWUSim/DevTools/tests/bot_noglorywd_test.php
require __DIR__ . '/fixtures/bot_test_bootstrap.php';
include_once './SWUSim/BotLegalActions.php';
include_once './SWUSim/BotHeuristic.php';

$check(in_array('noglorywd', SWUBotProposalList(), true) && SWUBotVariantDisabled('try-noglorywd') === ['try:noglorywd'], 'noglorywd is a switchable proposal');

$build(function ($b) {});   // SWUBotWeights reads the board (racing rank): build one first, or it walks null arenas
$W = SWUBotWeights('hardcontrol', 1);
$NOGLORY = 'Take_control_of_and_defeat_a_non-leader_unit';   // JTL_043's prompt
$pick = function (string $mz, array $variant, string $tip) use ($W) {
    SWUBotSetDisabledFeatures($variant); $s = _SWUBotTSUnitPick(1, $mz, $tip, 'JTL_043#0', '', $W); SWUBotSetDisabledFeatures([]); return $s;
};

// ── A: a When Defeated "draw a card" unit overtakes a slightly more valuable vanilla unit.
$build(function ($b) { $b->WithGroundUnitForPlayer(2, 'SOR_063', true); $b->WithGroundUnitForPlayer(2, 'LOF_059', true); });   // 3-cost vanilla, 2-cost "When Defeated: Draw a card"
$check(_SWUBotStolenWdValue(1, SWUBotViewForMz(1, 'theirGroundArena-1')) === 2.5, 'stolen WD: a draw is worth 2.5');
$check(_SWUBotStolenWdValue(1, SWUBotViewForMz(1, 'theirGroundArena-0')) === 0.0, 'stolen WD: a vanilla unit has none');
$check($pick('theirGroundArena-0', [], $NOGLORY) > $pick('theirGroundArena-1', [], $NOGLORY), 'off: No Glory prefers the more valuable vanilla 3-drop');
$check($pick('theirGroundArena-1', ['try:noglorywd'], $NOGLORY) > $pick('theirGroundArena-0', ['try:noglorywd'], $NOGLORY), 'noglorywd: the 2-drop whose When Defeated draws for me wins');
$check($pick('theirGroundArena-0', ['try:noglorywd'], $NOGLORY) === $pick('theirGroundArena-0', [], $NOGLORY), 'noglorywd: a unit with no When Defeated scores the same');

// ── B: a damage When Defeated is worth only what it can KILL.
$build(function ($b) { $b->WithSpaceUnitForPlayer(2, 'ASH_153', true); $b->WithGroundUnitForPlayer(2, 'ASH_116', true); $b->WithGroundUnitForPlayer(2, 'SOR_038', true); });
$check(_SWUBotStolenWdValue(1, SWUBotViewForMz(1, 'theirSpaceArena-0')) === 1.5, 'stolen WD "deal 2": an undamaged 5/4 is out of reach -> the 1.5 floor');
$build(function ($b) { $b->WithSpaceUnitForPlayer(2, 'ASH_153', true); $b->WithGroundUnitForPlayer(2, 'ASH_116', true); $b->WithGroundUnitForPlayer(2, 'SOR_038', true, 2); });   // 5/4 on 2 HP
$big = SWUBotUnitValue(SWUBotViewForMz(1, 'theirGroundArena-1'));
$check($big > 1.5 && _SWUBotStolenWdValue(1, SWUBotViewForMz(1, 'theirSpaceArena-0')) === $big, 'stolen WD "deal 2": the 5/4 on 2 HP is now a kill, worth its value');

// ── C: Annihilator's "defeat an enemy unit" reaches any other enemy unit.
$build(function ($b) { $b->WithSpaceUnitForPlayer(2, 'JTL_041', true); $b->WithGroundUnitForPlayer(2, 'SOR_038', true); });
$check(_SWUBotStolenWdValue(1, SWUBotViewForMz(1, 'theirSpaceArena-0')) === SWUBotUnitValue(SWUBotViewForMz(1, 'theirGroundArena-0')), 'stolen WD: Annihilator is worth the best OTHER enemy unit it defeats (not itself)');

// ── D: my own units and a plain capture are untouched.
$build(function ($b) { $b->WithGroundUnitForPlayer(1, 'LOF_059', true); $b->WithGroundUnitForPlayer(2, 'LOF_059', true); });
$check($pick('myGroundArena-0', ['try:noglorywd'], $NOGLORY) === $pick('myGroundArena-0', [], $NOGLORY), 'noglorywd: a friendly target scores the same');
$CAPTURE = 'Capture_an_enemy_non-leader_unit';
$check($pick('theirGroundArena-0', ['try:noglorywd'], $CAPTURE) === $pick('theirGroundArena-0', [], $CAPTURE), 'noglorywd: a plain capture (no "then defeat") scores the same');

bot_test_finish();

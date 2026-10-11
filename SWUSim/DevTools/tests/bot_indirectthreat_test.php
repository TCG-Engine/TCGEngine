<?php
// A unit's base threat includes the damage its On Attack deals the defending player (proposal 'indirectthreat', 2026-10-07 gap screen).
// FOUND 2026-10-07 diagnosing Krennic vs Boba Fett (JTL) Blue (.claude/tmp/diag_boba): SWUBotUnitBaseThreat counts attack POWER only, so
// TIE Bomber (JTL_237, 0/4: "On Attack: Deal 3 indirect damage to the defending player") reads as no threat — spot removal never took
// one in Krennic Blue's losses (0 of 119, 40 of them with a Bomber out), yet Bombers were the second-most common pilot host (23 of 80).
// The blind threat also feeds 'threathold', SWUBotClock and the wipe "stabilises" check. Indirect damage ignores Sentinels (the attack
// may be forced into one; its On Attack still lands), so it counts whatever guards the arena.
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php -d apc.enable_cli=1 -d xdebug.mode=off SWUSim/DevTools/tests/bot_indirectthreat_test.php
require __DIR__ . '/fixtures/bot_test_bootstrap.php';
include_once './SWUSim/BotLegalActions.php';
include_once './SWUSim/BotHeuristic.php';
$check(in_array('indirectthreat', SWUBotProposalList(), true) && SWUBotVariantDisabled('try-indirectthreat') === ['try:indirectthreat'], 'indirectthreat is a switchable proposal');
$threat = function (string $mz, array $variant) { SWUBotSetDisabledFeatures($variant); $t = SWUBotUnitBaseThreat(1, SWUBotViewForMz(1, $mz)); SWUBotSetDisabledFeatures([]); return $t; };

$build(function ($b) { $b->WithSpaceUnitForPlayer(2, 'JTL_237', true); $b->WithSpaceUnitForPlayer(2, 'LAW_135', true); });
$check($threat('theirSpaceArena-0', []) === 0, 'premise: today a TIE Bomber threatens nothing');
$check($threat('theirSpaceArena-0', ['try:indirectthreat']) === 3, '@try-indirectthreat: a TIE Bomber threatens its 3 indirect damage');
$check($threat('theirSpaceArena-1', ['try:indirectthreat']) === $threat('theirSpaceArena-1', []), 'a plain 2/3 fighter is unchanged');

// My space Sentinel guards the arena: the Bomber's attack must go into it, but the 3 indirect still lands.
$build(function ($b) { $b->WithSpaceUnitForPlayer(1, 'SOR_066', true); $b->WithSpaceUnitForPlayer(2, 'JTL_237', true); });
$check(SWUBotViewForMz(1, 'mySpaceArena-0')['sentinel'] === true, 'fixture: my ship is a Sentinel');
$check($threat('theirSpaceArena-0', ['try:indirectthreat']) === 3, '@try-indirectthreat: a Sentinel does not stop the indirect damage');

bot_test_finish();

<?php
// 2026-10-10 human-game lever screen. 'healrace': a seat's recurring base-heal (Restore N — "When this unit attacks, heal N
// damage from your base") makes its base effectively tankier in the race/lethal math. From Ninin's Grievous and Maul logs,
// where Chimaera/Owen Lars Restore out-races aggro; the bot "cannot close against a healing deck" (block 4). Symmetric, so it
// covers both attacking a healer (don't count healed-back damage as progress) and racing while I heal (survival equity).
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php -d apc.enable_cli=1 -d xdebug.mode=off SWUSim/DevTools/tests/bot_healrace_test.php
require __DIR__ . '/fixtures/bot_test_bootstrap.php';
include_once './SWUSim/BotLegalActions.php';
include_once './SWUSim/BotHeuristic.php';

$check(in_array('healrace', SWUBotProposalList(), true) && SWUBotVariantDisabled('try-healrace') === ['try:healrace'], 'healrace is a switchable proposal');

// _SWUBotRecurringBaseHeal sums the Restore keyword over a seat's units.
$build(function ($b) { $b->WithGroundUnitForPlayer(2, 'SOR_045', true); $b->WithGroundUnitForPlayer(2, 'SOR_045', true); $b->WithGroundUnitForPlayer(2, 'SOR_038', true); });
$check(_SWUBotRecurringBaseHeal(2) === 4, 'recurring heal: two Restore-2 units heal 4, a vanilla unit adds nothing');
$check(_SWUBotRecurringBaseHeal(1) === 0, 'recurring heal: a seat with no units heals nothing');

// Effective base HP: gated — printed when off, printed + ~2 race-rounds of Restore when on.
$build(function ($b) { $b->WithGroundUnitForPlayer(2, 'SOR_045', true); });   // seat 2 has one Restore-2 unit
$hp = SWUBaseRemainingHp(2);
SWUBotSetDisabledFeatures([]);                $check(SWUBotEffectiveBaseHp(2) === $hp, 'off: effective base HP == printed remaining HP');
SWUBotSetDisabledFeatures(['try:healrace']);  $check(SWUBotEffectiveBaseHp(2) === $hp + 4, 'healrace: effective base HP += 2 x Restore (2)'); SWUBotSetDisabledFeatures([]);

// Lethal-next-round: a board that just reaches the printed base is NOT lethal once the defender's heal is counted.
$build(function ($b) {
    for ($i = 0; $i < 4; $i++) $b->WithGroundUnitForPlayer(1, 'SOR_038', true);   // 4 x 5 power = 20
    $b->TheirBase('SOR_020', 10);                                                 // 30 HP base damaged to 20 remaining
    $b->WithSpaceUnitForPlayer(2, 'SOR_045', true);                              // a Restore-2 healer (space: does not wall my ground)
});
$check(SWUBotBasePotential(1, 2, false) === 20, 'premise: my ground potential is 20, their base is at 20 remaining');
SWUBotSetDisabledFeatures([]);                $check(SWUBotLethalNextRound(1, 2) === true,  'off: 20 >= 20, lethal next round');
SWUBotSetDisabledFeatures(['try:healrace']);  $check(SWUBotLethalNextRound(1, 2) === false, 'healrace: 20 < 24 effective HP (healer), not lethal next round'); SWUBotSetDisabledFeatures([]);

bot_test_finish();

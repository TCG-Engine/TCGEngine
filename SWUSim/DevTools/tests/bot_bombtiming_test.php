<?php
// Feature 'bombtiming' — hard control commits a big unit when it ADVANCES THE CLOCK (owner, 2026-09-17).
// Diagnosis: hard control draws ~7 cards costing 6+ per game, plays 2.33, and dealt only 21-24% of a kill.
// Spec: docs/superpowers/specs/2026-09-17-swusim-bot-archetypes-design.md, "Bomb timing".
// Fixtures (stats read from the dictionary 2026-09-18): JTL_041 Annihilator cost 11, 12/12, Space ·
// SOR_095 Battlefield Marine cost 2, 3/3, Ground · ASH_079 Koska Reeves cost 4, 4/4, Ground ·
// SOR_063 Cloud City Wing Guard cost 3, 2/4, Ground, UNCONDITIONAL Sentinel · SOR_020 Capital City base, 30 HP.
//
// DEVIATIONS from the task-9 brief (verified against the dictionary/engine, not just reasoned about):
// 1. The brief's Sentinel fixture used ASH_079 Koska Reeves, but its Sentinel is CONDITIONAL — card text reads
//    "While you control a token unit, this unit gains Sentinel." (confirmed in
//    SWUSim/Custom/KeywordEffects.php:974, case 'ASH_079') — and HasKeyword_Sentinel() checks that condition live
//    off the board (SWUSim/GeneratedCode/GeneratedKeywordCode.php:508-518). Alone on an empty board (no token
//    unit controlled) it never actually gains Sentinel, so _SWUBotSentinelArenas() would not guard the arena and
//    the "withheld" assertion would not hold. Swapped for SOR_063, whose Sentinel is UNCONDITIONAL (innate,
//    checked first via the static $Sentinel_Cards list — GeneratedKeywordCode.php:395). ASH_079 stays in the
//    file only for the "below bomb cost" cost-4 check, which does not depend on its Sentinel at all.
// 2. GameStateBuilder::TheirBase($cardID, $damage)'s 2nd argument is DAMAGE TAKEN, not remaining HP (confirmed
//    against bot_evaluator_test.php's own comments, e.g. "TheirBase('SOR_020', 24); // 30 HP, 24 damage → 6
//    remaining"). The brief's `TheirBase('SOR_020', 30)` would leave the base at 0 HP, not 30 as its own comments
//    describe. Changed to `TheirBase('SOR_020', 0)` so the base is actually at its full 30 HP. This does not change
//    any PASS/FAIL below: every fixture here has seat 1 with zero units before the bomb is hypothetically played,
//    so SWUBotBasePotential() is 0 and SWUBotClock's pot<=0 shortcut makes clockNow SWU_BOT_NO_CLOCK regardless of
//    the base's HP either way — this only makes the inline arithmetic comments (e.g. "ceil(30/12) = 3") literally
//    true instead of accidentally-still-passing.
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php -d apc.enable_cli=1 -d xdebug.mode=off SWUSim/DevTools/tests/bot_bombtiming_test.php
require __DIR__ . '/fixtures/bot_test_bootstrap.php';
include_once './SWUSim/BotLegalActions.php';
include_once './SWUSim/Custom/BotEvaluator.php';
include_once './SWUSim/Custom/BotStyles.php';
include_once './SWUSim/Custom/BotFallback.php';

// Their base at 30, nothing on either board: with no units the clock is SWU_BOT_NO_CLOCK (99); the 12-power
// Annihilator takes it to ceil(30/12) = 3, so it shortens the clock and nothing can kill it.
$build(function ($b) { $b->TheirBase('SOR_020', 0); });
$W = SWUBotWeights('hardcontrol', 1);
$safe = _SWUBotBombTimingValue(1, 'JTL_041', $W);
$check($safe > 0.0, "a cost-11 bomb that survives and shortens the clock earns a bonus (got $safe)");
$check($safe === $W['base'] * 12.0, 'the bonus is base x the bomb power, so it stays style-scaled');
$check(_SWUBotBombTimingValue(1, 'SOR_095', $W) === 0.0, 'a 2-cost unit is not a bomb — no bonus');
$check(_SWUBotBombTimingValue(1, 'ASH_079', $W) === 0.0, 'a 4-cost unit is below SWU_BOT_BOMB_COST — no bonus');

// The feature switch must be load-bearing.
SWUBotSetDisabledFeatures(['bombtiming']);
$check(_SWUBotBombTimingValue(1, 'JTL_041', $W) === 0.0, '@no-bombtiming: no bonus');
SWUBotSetDisabledFeatures([]);

// Condition 1 — it must SURVIVE. An enemy Annihilator (12/12) in the same arena trades with ours: 12 power
// defeats 12 remaining both ways, so SWUBotCombatOutcome is 'trade' and the bomb dies. Bonus withheld.
$build(function ($b) { $b->TheirBase('SOR_020', 0); $b->WithSpaceUnitForPlayer(2, 'JTL_041', true); });
$check(_SWUBotBombTimingValue(1, 'JTL_041', SWUBotWeights('hardcontrol', 1)) === 0.0,
    'an enemy that can defeat the bomb in one attack removes the bonus');

// Condition 1 control — the same enemy in the OTHER arena cannot reach it, so the bonus returns. This is what
// stops condition 1 degenerating into "any big enemy anywhere".
$build(function ($b) { $b->TheirBase('SOR_020', 0); $b->WithGroundUnitForPlayer(2, 'SOR_095', true); });
$check(_SWUBotBombTimingValue(1, 'JTL_041', SWUBotWeights('hardcontrol', 1)) > 0.0,
    'an enemy in a DIFFERENT arena does not threaten the bomb — bonus still earned');

// Condition 2 — it must SHORTEN THE CLOCK. A Sentinel in the bomb's arena means its power never reaches the base
// (SWUBotBasePotential skips guarded arenas), so the clock is unchanged and the bonus is withheld even though the
// bomb survives. SOR_063 Cloud City Wing Guard is a 2/4 Ground UNCONDITIONAL Sentinel (see deviation 1 above), so
// this needs a GROUND bomb: SEC_183-class cards are not in the fixture set, so assert the helper directly with a
// ground 6+ unit from the pool.
$build(function ($b) { $b->TheirBase('SOR_020', 0); $b->WithGroundUnitForPlayer(2, 'SOR_063', true); });
// LOF_070 Anakin Skywalker (cost 6, 5/7, Ground) is picked here, and it is the very card the diagnosed Mando
// Colossus game drew in round 5 and never played.
$groundBomb = null;
foreach (['LOF_070', 'SEC_046', 'ASH_097'] as $cid) {
    if (intval(CardCost($cid)) >= 6 && !str_contains(strval(CardArena($cid)), 'Space')
        && str_contains(strval(CardType($cid)), 'Unit')) { $groundBomb = $cid; break; }
}
$check($groundBomb !== null, 'fixture: found a ground unit costing 6+ for the Sentinel case');
if ($groundBomb !== null) {
    $check(_SWUBotBombTimingValue(1, $groundBomb, SWUBotWeights('hardcontrol', 1)) === 0.0,
        "$groundBomb: a Sentinel-guarded arena means no clock change — bonus withheld even though the bomb survives");
}

bot_test_finish();

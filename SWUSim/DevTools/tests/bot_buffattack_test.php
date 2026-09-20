<?php
// A power buff is used BEFORE the attack it improves, not after it (feature 'buffattack', group p7).
// FOUND 2026-09-20 in a live game (690588, owner report #1052): "after i claimed initiative, the bot attacked
// with Gungi and then buffed him with Ahsoka's ability. they should have buffed first and then attacked".
// ASH_009 Ahsoka Tano: "Action [Exhaust]: Choose a unit with less power than a friendly unit. It gets +2/+0 for
// this phase." LOF_093 Gungi is 2/5. The bot attacked the base for 2, then spent the Action on a unit that had
// already attacked — the +2/+0 expires at end of phase, so it did nothing at all.
//
// Root cause: an Action scores a FLAT W['ability'] (0.40) in _SWUBotAbilityValue, with nothing for the attack it
// would improve, while the attack itself scores W['base'] x power (0.60 x 2 = 1.20). The attack therefore always
// outranks the buff, and the buff is taken afterwards because 0.40 still beats passing.
//
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php -d apc.enable_cli=1 -d xdebug.mode=off SWUSim/DevTools/tests/bot_buffattack_test.php
require __DIR__ . '/fixtures/bot_test_bootstrap.php';
include_once './SWUSim/BotLegalActions.php';
include_once './SWUSim/BotHeuristic.php';

$ability = 'myLeader-0!CustomInput!LeaderAbility';
$ids = fn($acts) => array_map(fn($a) => strval($a['cardID']), $acts);
$pickOf = function (string $style, int $seat = 1, string $variant = '') use (&$gameName) {
    SWUBotResetCoverage();
    $legal = SWUBotLegalActions($gameName, $seat);
    $p = SWUBotHeuristicChoose($style, (array)$legal['actions'], $legal, $variant);
    return $p === null ? [null, null] : [strval($p['cardID']), SWUBotActionKind($p)];
};

// SHIPPED behaviour (group p7, owner decision 2026-09-20): default ON, "@no-buffattack" turns it off. It ships
// on the ruling rather than on an A/B because buffing a unit that has ALREADY attacked is strictly zero value,
// so the floor is "no worse"; '@no-p7' is the stack before it if anyone measures later.
$check(in_array('buffattack', SWUBotFeatureList(), true), 'buffattack is shipped behaviour (defaults on)');
$check(SWUBotVariantDisabled('no-buffattack') === ['buffattack'], 'buffattack is switchable');
$check(SWUBotFeatureGroups()['p7'] === ['buffattack'], "group p7 is the stack before it");

// ── A) THE REPORTED SHAPE ────────────────────────────────────────────────────────────────────────────
// Seat 1 (the bot): Ahsoka undeployed, Gungi 2/5 READY on the ground, and an exhausted 3/3 in space — the
// "friendly unit" whose 3 power makes Gungi (2) a legal target for the buff. The opponent has CLAIMED the
// initiative (as in the report) and holds no units, so Gungi's attack goes at the base.
// Buffing first turns a 2-damage swing into a 4-damage one; buffing afterwards does nothing whatsoever.
$build(function ($b) {
    $b->MyLeader('ASH_009');
    $b->WithGroundUnitForPlayer(1, 'LOF_093', true);     // Gungi 2/5, ready
    $b->WithSpaceUnitForPlayer(1, 'JTL_250', false);     // 3/3, exhausted — the power reference, not an attacker
    $b->WithInitiativePlayerBeing(2);
    $b->WithInitiativeClaimed();
});
$offered = $ids($botCtx('normal')['actions']);
$check(in_array($ability, $offered, true), "fixture: Ahsoka's Action is on offer; got " . json_encode($offered));
$check(in_array('myGroundArena-0!FSM!', $offered, true), 'fixture: attacking with Gungi is on offer');

[$pick, $kind] = $pickOf('normal');
$check($pick === $ability, "the buff is used BEFORE the attack; picked " . json_encode($pick) . " ($kind)");

// The reported mistake, still reproducible with the feature off — so the guard above cannot pass vacuously.
[$pickOff, $kindOff] = $pickOf('normal', 1, 'no-buffattack');
$check($kindOff === 'attack', "@no-buffattack: the bot attacks first, the reported mistake; picked " . json_encode($pickOff));

// ── B) CONTROL — a buff that improves NO attack must not jump the queue ──────────────────────────────
// Same cards, but Gungi (the only legal buff target: 2 power, below the 3-power friendly) is EXHAUSTED and
// the 3/3 is READY. The buff cannot add damage to anything this phase, so the ready attacker's swing at the
// base (0.60 x 3 = 1.80) must still outrank the Action (0.40). Without this section a blanket
// "prefer Actions over attacks" would pass A and look correct.
$build(function ($b) {
    $b->MyLeader('ASH_009');
    $b->WithGroundUnitForPlayer(1, 'LOF_093', false);    // Gungi exhausted — buffable, but it cannot attack
    $b->WithSpaceUnitForPlayer(1, 'JTL_250', true);      // 3/3 ready — the attacker
    $b->WithInitiativePlayerBeing(2);
    $b->WithInitiativeClaimed();
});
$offeredB = $ids($botCtx('normal')['actions']);
$check(in_array($ability, $offeredB, true) && in_array('mySpaceArena-0!FSM!', $offeredB, true),
    'fixture: both the Action and the ready attacker are on offer; got ' . json_encode($offeredB));
[$pickB, $kindB] = $pickOf('normal');
$check($kindB === 'attack', "a buff that improves no attack does not delay the attack; picked " . json_encode($pickB) . " ($kindB)");

bot_test_finish();

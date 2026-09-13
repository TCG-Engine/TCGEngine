<?php
// Phase 1a Task 9 — the decision stack, the three heuristic profiles and per-seat coverage
// (SWUSim/BotHeuristic.php). Drives the REAL chooser seam, SWUBotChooseAction($actions, $legal), with
// $legal from SWUBotLegalActions — the same call ProcessBotControllerStep() makes.
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php -d apc.enable_cli=1 -d xdebug.mode=off SWUSim/DevTools/tests/bot_stack_test.php
require __DIR__ . '/fixtures/bot_test_bootstrap.php';
include_once './SWUSim/BotLegalActions.php';
include_once './SWUSim/BotHeuristic.php';
include_once './SWUSim/Custom/BotLookahead.php';
$choose = function (int $seat = 1) use (&$gameName) {
    $legal = SWUBotLegalActions($gameName, $seat);
    $pick = SWUBotChooseAction((array)($legal['actions'] ?? []), $legal);
    return $pick === null ? null : strval($pick['cardID']);
};
$cov = fn(int $seat, string $key) => intval($GLOBALS['SWUBotCoverage'][$seat][$key] ?? 0);

foreach (['heuristic-aggro', 'heuristic-normal', 'heuristic-control'] as $p) {
    $check(is_callable($GLOBALS['SWUBotChoosers'][$p] ?? null), "profile $p is registered");
}

// ── A lethal board: the rule answers and is recorded ────────────────────────────────────────────
SWUBotSetForcedChooserProfileForSeat(1, 'heuristic-aggro');
SWUBotResetCoverage();
$build(function ($b) {
    $b->MyLeader('SOR_014', false); $b->TheirBase('SOR_020', 25);
    $b->WithGroundUnitForPlayer(1, 'SOR_095', true); $b->WithGroundUnitForPlayer(1, 'LOF_084', true);
});
$check($choose() === 'myGroundArena-1!FSM!', 'heuristic-aggro takes lethal with the strongest attacker');
$check($cov(1, 'rule:lethal-now') === 1 && $cov(1, 'fallback') === 0, 'coverage: rule:lethal-now recorded for seat 1');

// ── A quiet decision falls through to the scorer ─────────────────────────────────────────────────
// Kallus's Ambush kills a Marine and survives: rule 12 abstains, no other rule applies.
SWUBotResetCoverage();
$build(function ($b) { $b->FillResourcesForPlayer(1, 'SOR_095', 8); $b->WithCardInHandForPlayer(1, 'SOR_115'); $b->WithGroundUnitForPlayer(2, 'SOR_095', true); });
$act(1, 10002, 'myHand-0!FSM!');
$check($choose() === 'YES' && $cov(1, 'fallback') === 1, 'a quiet decision: the fallback answers (YES) and is recorded');
$check(SWUBotResetCoverage() === null && ($GLOBALS['SWUBotCoverage'] ?? null) === [], 'SWUBotResetCoverage clears every seat');

// ── Profiles resolve per seat ────────────────────────────────────────────────────────────────────
SWUBotSetForcedChooserProfile(null);
SWUBotSetForcedChooserProfileForSeat(1, 'heuristic-aggro');
SWUBotSetForcedChooserProfileForSeat(2, 'heuristic-control');
$check(SWUBotActiveChooserProfile(1) === 'heuristic-aggro' && SWUBotActiveChooserProfile(2) === 'heuristic-control',
    'a seat-2 override leaves seat 1 on its own profile');
SWUBotSetForcedChooserProfile('random');
$check(SWUBotActiveChooserProfile(1) === 'heuristic-aggro', 'the per-seat override outranks the process-wide one');
SWUBotSetForcedChooserProfileForSeat(1, null);
$check(SWUBotActiveChooserProfile(1) === 'random', 'with no per-seat override, the process-wide one applies');
SWUBotSetForcedChooserProfile(null);
SWUBotSetForcedChooserProfileForSeat(2, null);
$check(SWUBotActiveChooserProfile(1) === 'first-legal' && SWUBotActiveChooserProfile() === 'first-legal',
    'with no override at all, the DQ variable / first-legal default applies');

// ── A rule that answers outside the candidate set is ignored ─────────────────────────────────────
// Test-only hook: $GLOBALS['SWUBotTestExtraRules'] is appended after the layer-2 rules.
SWUBotSetForcedChooserProfileForSeat(1, 'heuristic-aggro');
SWUBotResetCoverage();
$GLOBALS['SWUBotTestExtraRules'] = ['bogus' => fn(array $ctx) => ['mode' => 100, 'cardID' => 'NOT_A_CANDIDATE']];
$build(function ($b) { $b->FillResourcesForPlayer(1, 'SOR_095', 8); $b->WithCardInHandForPlayer(1, 'SOR_115'); $b->WithGroundUnitForPlayer(2, 'SOR_095', true); });
$act(1, 10002, 'myHand-0!FSM!');
$check($choose() === 'YES', 'the bogus answer is not returned; the stack falls through to the scorer');
$check($cov(1, 'invalid:bogus') === 1 && $cov(1, 'fallback') === 1, 'coverage: invalid:bogus and fallback recorded');
unset($GLOBALS['SWUBotTestExtraRules']);
SWUBotSetForcedChooserProfileForSeat(1, null);

bot_test_finish();

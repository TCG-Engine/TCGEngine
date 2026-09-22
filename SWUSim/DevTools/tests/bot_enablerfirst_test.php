<?php
// An ENABLER is played BEFORE the card it improves (feature 'enablerfirst', group p10).
// Owner report 2026-09-22, Bug Report game 1105765: "Ahsoka played a 0 power unit before Neel. it should be the
// other way around to be able to 1) ready Tarpals 2) buff him and start the game strong with 4 damage to base".
// ASH_248 Neel (1 cost, 1/4): "When Played/On Attack: The next unit you play this phase with 1 or less power enters
// play ready." HMW_254 Captain Tarpals (1 cost, 0/2, Shielded, Raid 2). With 2 resources the line is Neel → Tarpals
// (enters READY) → Ahsoka's Action makes him 2 power → attack for 2+2 = 4 at the base. The bot played Tarpals first,
// so Neel's grant had nothing left to ready and the turn ended with two exhausted units and no attack.
//
// Root cause: _SWUBotPlayValue is ORDER-BLIND — develop x cost + tags + unitPlay. Both cards cost 1, so the two
// plays score the same and the order is whatever the enumerator hands over first.
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php -d apc.enable_cli=1 -d xdebug.mode=off SWUSim/DevTools/tests/bot_enablerfirst_test.php
require __DIR__ . '/fixtures/bot_test_bootstrap.php';
include_once './SWUSim/BotLegalActions.php';
include_once './SWUSim/BotHeuristic.php';
include_once './SWUSim/Custom/BotLookahead.php';

$ids = fn($acts) => array_map(fn($a) => strval($a['cardID']), $acts);
$pickOf = function (string $style, int $seat = 1, string $variant = '') use (&$gameName) {
    SWUBotResetCoverage();
    $legal = SWUBotLegalActions($gameName, $seat);
    $p = SWUBotHeuristicChoose($style, (array)$legal['actions'], $legal, $variant);
    return $p === null ? [null, null] : [strval($p['cardID']), SWUBotActionKind($p)];
};
$playScore = function (string $style, string $cardID, array $disabled = []) use ($botCtx) {
    SWUBotSetDisabledFeatures($disabled);
    $ctx = $botCtx($style);
    $s = null;
    foreach ($ctx['actions'] as $i => $a) if (strval($a['cardID']) === $cardID) { $s = SWUBotScoreAction($ctx, $a, $i); break; }
    SWUBotSetDisabledFeatures([]);
    return $s;
};
$TARPALS = 'myHand-0!FSM!';   // HMW_254, 0 power — the payoff
$NEEL    = 'myHand-1!FSM!';   // ASH_248 — the enabler

$check(in_array('enablerfirst', SWUBotFeatureList(), true), 'enablerfirst is shipped behaviour (defaults on)');
$check(SWUBotVariantDisabled('no-enablerfirst') === ['enablerfirst'], 'enablerfirst is switchable');
$check(SWUBotFeatureGroups()['p10'] === ['enablerfirst'], 'group p10 is the stack before it');

// ── A) THE REPORTED SHAPE — 2 resources, Tarpals and Neel in hand ────────────────────────────────────
$reported = function () use ($build) {
    $build(function ($b) {
        $b->MyLeader('ASH_009');
        $b->FillResourcesForPlayer(1, 'SOR_095', 2);
        $b->WithCardInHandForPlayer(1, 'HMW_254');   // myHand-0: the 0-power payoff
        $b->WithCardInHandForPlayer(1, 'ASH_248');   // myHand-1: the enabler
        // The opponent has CLAIMED the initiative, so they auto-pass and seat 1 keeps acting — the whole line
        // (section E) runs in one fixture instead of needing an opponent script between each action.
        $b->WithInitiativePlayerBeing(2);
        $b->WithInitiativeClaimed();
    });
};
$reported();
$offered = $ids($botCtx('normal')['actions']);
$check(in_array($TARPALS, $offered, true) && in_array($NEEL, $offered, true),
    'fixture: both plays are on offer; got ' . json_encode($offered));
[$pick, $kind] = $pickOf('normal');
$check($pick === $NEEL, "the ENABLER (Neel) is played first; picked " . json_encode($pick) . " ($kind)");
$check($playScore('normal', $NEEL) > $playScore('normal', $TARPALS),
    'the enabler outscores the payoff: ' . json_encode([$playScore('normal', $NEEL), $playScore('normal', $TARPALS)]));

// The reported mistake, still reproducible with the feature off — so the guard cannot pass vacuously.
$reported();
[$pickOff] = $pickOf('normal', 1, 'no-enablerfirst');
$check($pickOff === $TARPALS, "@no-enablerfirst: the 0-power unit goes first, the reported mistake; picked " . json_encode($pickOff));

// ── B) THE REST OF THE LINE — after Neel, the payoff is played ──────────────────────────────────────
// Neel is already down (its grant is live), Tarpals is the only card left: he must still be played.
$build(function ($b) {
    $b->MyLeader('ASH_009');
    $b->FillResourcesForPlayer(1, 'SOR_095', 1);
    $b->WithGroundUnitForPlayer(1, 'ASH_248', false);
    $b->WithCardInHandForPlayer(1, 'HMW_254');
});
[$pickB, $kindB] = $pickOf('normal');
$check($pickB === 'myHand-0!FSM!' && $kindB === 'play', "after the enabler, the payoff is played; picked " . json_encode($pickB) . " ($kindB)");

// ── C) CONTROL — no card the enabler can help ───────────────────────────────────────────────────────
// Neel plus a 2-POWER unit (LOF_093 Gungi, 2/5): "1 or less power" does not cover it, so there is nothing to
// order around and the enabler must get no bonus. Without this a blanket "play Neel first" would pass A.
$build(function ($b) {
    $b->MyLeader('ASH_009');
    $b->FillResourcesForPlayer(1, 'SOR_095', 4);
    $b->WithCardInHandForPlayer(1, 'LOF_093');   // myHand-0: 2 power — not eligible
    $b->WithCardInHandForPlayer(1, 'ASH_248');   // myHand-1: the enabler
});
$neelPlain = $playScore('normal', $NEEL);
$neelOff   = $playScore('normal', $NEEL, ['enablerfirst']);
$check($neelPlain !== null && abs($neelPlain - $neelOff) < 1e-9,
    'no eligible payoff -> the enabler scores exactly as before: ' . json_encode([$neelPlain, $neelOff]));

// ── D) CONTROL — the payoff is unaffordable after the enabler ───────────────────────────────────────
// One resource: playing Neel leaves nothing to play Tarpals with this turn, so the grant would expire unused
// and the ordering bonus must not apply.
$build(function ($b) {
    $b->MyLeader('ASH_009');
    $b->FillResourcesForPlayer(1, 'SOR_095', 1);
    $b->WithCardInHandForPlayer(1, 'HMW_254');
    $b->WithCardInHandForPlayer(1, 'ASH_248');
});
$dPlain = $playScore('normal', $NEEL);
$dOff   = $playScore('normal', $NEEL, ['enablerfirst']);
$check($dPlain !== null && abs($dPlain - $dOff) < 1e-9,
    'payoff unaffordable this turn -> no ordering bonus: ' . json_encode([$dPlain, $dOff]));

// ── E) THE WHOLE LINE — the 4 damage the report asks for ────────────────────────────────────────────
// Drive the bot's own choices through the real dispatcher: Neel, then Tarpals (who must enter READY), then
// Ahsoka's Action on him (+2/+0), then the attack. Without the ordering this line does not exist at all.
$reported();
[$e1] = $pickOf('normal');
$check($e1 === $NEEL, 'E: turn starts with the enabler; got ' . json_encode($e1));
$act(1, 10002, $NEEL);
[$e2] = $pickOf('normal');
$check($e2 === $TARPALS, 'E: then the payoff; got ' . json_encode($e2));
$act(1, 10002, $TARPALS);
$tarpals = null;
foreach (SWUBotUnits(1) as $u) if ($u['cardID'] === 'HMW_254') $tarpals = $u;
$check($tarpals !== null && $tarpals['ready'], 'E: Tarpals entered play READY (Neel\'s grant); got ' . json_encode($tarpals ? $tarpals['ready'] : null));
[$e3] = $pickOf('normal');
$check($e3 === 'myLeader-0!CustomInput!LeaderAbility', "E: Ahsoka's Action is used next; got " . json_encode($e3));
$act(1, 10001, 'myLeader-0!CustomInput!LeaderAbility');
$buffed = null;
foreach (SWUBotUnits(1) as $u) if ($u['cardID'] === 'HMW_254') $buffed = $u;
$check($buffed !== null && $buffed['power'] === 2, 'E: the buff landed on Tarpals (0 -> 2 power); got ' . json_encode($buffed ? $buffed['power'] : null));
$check($buffed !== null && $buffed['attackPower'] === 4, 'E: he swings for 4 with Raid 2; got ' . json_encode($buffed ? $buffed['attackPower'] : null));
[$e4, $k4] = $pickOf('normal');
$check($k4 === 'attack', 'E: and the bot attacks with him; got ' . json_encode([$e4, $k4]));

bot_test_finish();

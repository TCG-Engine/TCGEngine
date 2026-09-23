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

// ══ Bug Report #1071 (game 1139599) — THE SAME MISTAKE ON THE AGGRO WING ════════════════════════════
// "it played Han Solo before Neel. ideally, it should play Neel first, then Han Solo. then buff Han Solo
// with Ahsoka's ability. for maximum damage." Round 2, seat 2 = ASH_009 Ahsoka / ASH_019 Fortress of the
// Great Mothers (Vigilance), profile heuristic-SOFTAGGRO, LOF_093 Gungi (2/5) already down, 3 resources,
// LAW_037 Han Solo (1 cost, 1/1, Shielded, "On Attack: Give an Experience token to this unit") and Neel
// both in hand. Han Solo has 1 power, so Neel readies him: Neel -> Han (READY) -> Ahsoka's Action makes
// him 3 power (Gungi is the friendly with more power) -> attack, On Attack adds an Experience token -> 4
// damage at the base. The bot played Han first and swung for nothing.
//
// Sections A-E above already cover this shape and still PASS, because they run on 'normal'. On the AGGRO
// WING the layer-4 go-wide guide (SWUBotAggroMaxUnitsPick, BotGuides.php) decides the order instead: it
// finds the affordable subset with the most units — {Han, Neel}, BOTH of them — and then picks "the most
// expensive card" of that subset, which ties at cost 1 and falls through to the lowest HAND INDEX. Its
// weight is 3.0 for soft aggro and 4.0 for hyper aggro, five to seven times the 0.6 the enabler bonus
// adds, so section A's ordering is inert for every aggro seat and the turn is decided by where the two
// cards happen to sit in hand: measured on this board, Han at the lower index -> Han first (the report);
// Neel at the lower index -> Neel first, right by luck.
$MAXUNITS_STYLES = ['softaggro', 'hyperaggro'];

// F) THE REPORTED SHAPE — Han Solo at the LOWER hand index, so the index tiebreak points the wrong way.
$reported1071 = function () use ($build) {
    $build(function ($b) {
        $b->MyLeader('ASH_009');                       // Ahsoka Tano (Command/Heroism)
        $b->MyBase('ASH_019');                         // Vigilance — Han Solo (Vigilance/Command) costs 1, not 3
        $b->FillResourcesForPlayer(1, 'SOR_095', 3);
        $b->WithGroundUnitForPlayer(1, 'LOF_093', false);   // Gungi 2/5 — the friendly with MORE power Ahsoka needs
        $b->WithCardInHandForPlayer(1, 'LAW_037');     // myHand-0: Han Solo, the payoff
        $b->WithCardInHandForPlayer(1, 'ASH_248');     // myHand-1: Neel, the enabler
        $b->WithInitiativePlayerBeing(2);
        $b->WithInitiativeClaimed();
    });
};
$HAN = 'myHand-0!FSM!';
foreach ($MAXUNITS_STYLES as $st) {
    $reported1071();
    $ctxF = $botCtx($st);
    $check(_SWUBotGuides($ctxF)['maxUnits'] === $NEEL,
        "F/$st: the go-wide guide names the ENABLER, not the payoff; got " . json_encode(_SWUBotGuides($ctxF)['maxUnits']));
    [$pickF, $kindF] = $pickOf($st);
    $check($pickF === $NEEL, "F/$st: Neel is played before Han Solo; picked " . json_encode($pickF) . " ($kindF)");
}

// G) the reported mistake is still there with the feature off — so F cannot pass vacuously.
foreach ($MAXUNITS_STYLES as $st) {
    $reported1071();
    [$pickG] = $pickOf($st, 1, 'no-enablerfirst');
    $check($pickG === $HAN, "G/$st: @no-enablerfirst still plays Han Solo first, the reported mistake; picked " . json_encode($pickG));
}

// H) CONTROL — with no enabler in the subset the guide's "most expensive" tiebreak is UNCHANGED.
// HMW_254 Captain Tarpals (1 cost) at index 0 and LOF_093 Gungi (2 cost) at index 1: neither improves "the
// next unit you play this phase", both fit in 3 resources, so the guide must still name the 2-drop — which
// is also the card the index tiebreak would NOT have chosen.
foreach ($MAXUNITS_STYLES as $st) {
    $build(function ($b) {
        $b->MyLeader('ASH_009');
        $b->MyBase('ASH_019');
        $b->FillResourcesForPlayer(1, 'SOR_095', 3);
        $b->WithCardInHandForPlayer(1, 'HMW_254');     // myHand-0: 1 cost
        $b->WithCardInHandForPlayer(1, 'LOF_093');     // myHand-1: 2 cost
        $b->WithInitiativePlayerBeing(2);
        $b->WithInitiativeClaimed();
    });
    $ctxH = $botCtx($st);
    $check(_SWUBotGuides($ctxH)['maxUnits'] === 'myHand-1!FSM!',
        "H/$st: no enabler -> the guide still names the most expensive unit; got " . json_encode(_SWUBotGuides($ctxH)['maxUnits']));
}

// I) CONTROL — an enabler the chosen subset does NOT contain must NOT be promoted, however good its grant is.
// The ordering is only safe inside the subset, which is affordable as a whole; pulling a card INTO the turn
// from outside it would overrule the guide's own "most units, then most spend" choice and could leave the
// subset unaffordable. 3 resources against Gungi (2 cost) / Han Solo (1) / Neel (1): every 2-unit subset ties
// on count, so the biggest spend wins and it is {Gungi, Han} at 3 — Neel is out, even though his grant WOULD
// pay off on Han. The guide must still name Gungi. (Mutation-checked: scanning the whole hand instead of the
// subset turns this red and leaves every other section green.)
foreach ($MAXUNITS_STYLES as $st) {
    $build(function ($b) {
        $b->MyLeader('ASH_009');
        $b->MyBase('ASH_019');
        $b->FillResourcesForPlayer(1, 'SOR_095', 3);
        $b->WithCardInHandForPlayer(1, 'LOF_093');     // myHand-0: Gungi, 2 cost
        $b->WithCardInHandForPlayer(1, 'LAW_037');     // myHand-1: Han Solo, 1 cost — the eligible payoff
        $b->WithCardInHandForPlayer(1, 'ASH_248');     // myHand-2: Neel, the enabler, priced OUT of the subset
        $b->WithInitiativePlayerBeing(2);
        $b->WithInitiativeClaimed();
    });
    $ctxI = $botCtx($st);
    $check(_SWUBotGuides($ctxI)['maxUnits'] === 'myHand-0!FSM!',
        "I/$st: an enabler outside the subset is not pulled in; got " . json_encode(_SWUBotGuides($ctxI)['maxUnits']));
}

// J) THE WHOLE LINE on soft aggro — the 4 damage the report asks for.
$reported1071();
[$j1] = $pickOf('softaggro');
$check($j1 === $NEEL, 'J: turn starts with Neel; got ' . json_encode($j1));
$act(1, 10002, $NEEL);
[$j2] = $pickOf('softaggro');
$check($j2 === $HAN, 'J: then Han Solo; got ' . json_encode($j2));
$act(1, 10002, $HAN);
$han = null;
foreach (SWUBotUnits(1) as $u) if ($u['cardID'] === 'LAW_037') $han = $u;
$check($han !== null && $han['ready'], "J: Han Solo entered play READY (Neel's grant); got " . json_encode($han['ready'] ?? null));
[$j3] = $pickOf('softaggro');
$check($j3 === 'myLeader-0!CustomInput!LeaderAbility', "J: Ahsoka's Action is used next; got " . json_encode($j3));
$act(1, 10001, 'myLeader-0!CustomInput!LeaderAbility');
// Unlike section E, TWO units are legal targets here (Neel and Han Solo are both 1 power, both under Gungi's
// 2), so the Action raises a real "choose a unit" prompt instead of auto-resolving. The bot answers it itself.
[$j3b, $k3b] = $pickOf('softaggro');
$check($k3b === 'answer', 'J: the buff raises a target prompt; got ' . json_encode([$j3b, $k3b]));
$act(1, 100, $j3b);
$buffedHan = null;
foreach (SWUBotUnits(1) as $u) if ($u['cardID'] === 'LAW_037') $buffedHan = $u;
$check($buffedHan !== null && $buffedHan['power'] === 3,
    'J: the buff went on Han Solo, the one that can still attack (1 -> 3 power); got ' . json_encode($buffedHan['power'] ?? null));
[$j4, $k4j] = $pickOf('softaggro');
$check($k4j === 'attack' && SWUBotActionMz(['cardID' => $j4]) === 'myGroundArena-2',
    'J: and the bot attacks WITH HAN SOLO; got ' . json_encode([$j4, $k4j]));
$check($buffedHan !== null && $buffedHan['attackPower'] === 3,
    'J: he swings for 3 before his On Attack trigger; got ' . json_encode($buffedHan['attackPower'] ?? null));
// …and the trigger's Experience token makes it 4 at the base — "maximum damage", the report's ask. The board
// is empty on the other side, so the attack needs no target prompt.
$act(1, 10002, $j4);
$dealt = 30 - SWUBaseRemainingHp(2);
$check($dealt === 4, 'J: 4 damage at the enemy base (1 printed +2 Ahsoka +1 Experience); got ' . json_encode($dealt));

bot_test_finish();

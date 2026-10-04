<?php
// Part 31 (2026-10-04) — three plays from Ninin's human-vs-human Premier game, Hemlock Red vs Lando Blue (owner: "this went
// about as i expected"). Folded in WITHOUT a measurement, on the owner's instruction.
//   etbsetup       — a When Played gated on "If you control a unit that costs N or less" waits for the cheap unit that turns
//                    it on: R1 Imperial Door Technician, R2 HMW_154 Dooku's Solar Sailer → Lando discarded his Chimaera. The
//                    bot played the Sailer first (1.0 vs 0.7), its discard dead.
//   weaknessaction — "Action [1 resource, Exhaust]: Give a Weakness token to a unit…" (HMW_003 Hemlock) is worth its best
//                    target — a kill (Droid Laser Turret, Home One after No Disintegrations) or a soften — less its resource;
//                    it was a flat W['ability'], blind to whether a kill was on the table.
//   spentetb       — a unit whose ONLY text is a When Played it has already used is its BODY when sacrificed: Chimaera took
//                    the Solar Sailer, not a unit with an ongoing ability. Priced by printed cost, the spent ETB still counted.
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php -d apc.enable_cli=1 -d xdebug.mode=off SWUSim/DevTools/tests/bot_hemlockplays_test.php
require __DIR__ . '/fixtures/bot_test_bootstrap.php';
include_once './SWUSim/BotLegalActions.php';
include_once './SWUSim/BotHeuristic.php';
foreach (['etbsetup', 'weaknessaction', 'spentetb', 'observerfirst', 'uniquereplay'] as $f) {
    $check(SWUBotVariantDisabled('no-' . $f) === [$f], "$f is switchable; got " . json_encode(SWUBotVariantDisabled('no-' . $f)));
    $check(in_array($f, SWUBotFeatureGroups()['p31'] ?? [], true), "$f is in group p31");
}
$legal = fn() => SWUBotLegalActions($GLOBALS['gameName'], 1);
$first = function (callable $b, string $variant) use ($build, $legal) {
    $build($b);
    $l = $legal();
    return strval(SWUBotHeuristicChoose('midrange', (array)$l['actions'], $l, $variant)['cardID'] ?? '');
};
// Hemlock Red, Epic Action spent (deploying is not the alternative), $res resources, $hand, plus $more($b).
$hem = function (array $hand, int $res, ?callable $more = null) {
    return function ($b) use ($hand, $res, $more) {
        $b->MyLeader('HMW_003', true, false, true); $b->MyBase('HMW_027');
        $b->FillResourcesForPlayer(1, 'LAW_097', $res);
        foreach ($hand as $c) $b->WithCardInHandForPlayer(1, $c);
        foreach (['ASH_052', 'LAW_133'] as $c) $b->WithCardInHandForPlayer(2, $c);   // something for the Sailer to take
        if ($more !== null) $more($b);
    };
};

// ── etbsetup ──
// A) The game's round 2: Solar Sailer (3) + Imperial Door Technician (1), 4 resources, nothing in play. IDT first.
$boardA = $hem(['HMW_154', 'LAW_097'], 4);
$check($first($boardA, 'no-etbsetup') === 'myHand-0!FSM!', 'A fixture: today the Sailer goes first; got ' . $first($boardA, 'no-etbsetup'));
$check($first($boardA, '') === 'myHand-1!FSM!', 'A: the 1-cost unit goes first; got ' . $first($boardA, ''));
// A2) Only 3 resources: both do not fit this round — the Sailer now, as before.
$check($first($hem(['HMW_154', 'LAW_097'], 3), '') === 'myHand-0!FSM!', 'A2: both are not affordable — unchanged; got ' . $first($hem(['HMW_154', 'LAW_097'], 3), ''));
// A3) The condition is already ON (a 1-cost unit in play): nothing to set up.
$boardA3 = $hem(['HMW_154', 'LAW_097'], 4, fn($b) => $b->WithGroundUnitForPlayer(1, 'LAW_172', false));   // exhausted: no attack first
$check($first($boardA3, '') === $first($boardA3, 'no-etbsetup'), 'A3: condition already met — unchanged');
// A4) The cheap card is too expensive for the condition (Nightsister Warrior costs 2): it cannot switch it on.
$boardA4 = $hem(['HMW_154', 'LOF_059'], 5);
$check($first($boardA4, '') === $first($boardA4, 'no-etbsetup'), 'A4: a 2-cost unit does not enable "1 or less" — unchanged; got ' . $first($boardA4, '') . ' vs ' . $first($boardA4, 'no-etbsetup'));

// ── weaknessaction ──
// B) A 1-HP enemy Outer Rim Constable (3/1) and Karis castable (2 resources... 3 here, 1 for the Action). The Weakness kills
// the Constable — the Action is worth a kill, so it comes before Karis. Today the flat 0.40 loses to Karis.
$boardB = $hem(['LOF_031'], 3, fn($b) => $b->WithGroundUnitForPlayer(2, 'SEC_163'));
$check($first($boardB, 'no-weaknessaction') === 'myHand-0!FSM!', 'B fixture: today Karis is played over the kill; got ' . $first($boardB, 'no-weaknessaction'));
$check($first($boardB, '') === 'myLeader-0!CustomInput!LeaderAbility', 'B: the Weakness kill comes first; got ' . $first($boardB, ''));
// C) Nothing to kill and nothing worth shrinking for a resource: their only unit is a 0-power Owen Lars (0/3). Karis.
$boardC = $hem(['LOF_031'], 3, fn($b) => $b->WithGroundUnitForPlayer(2, 'LOF_057'));
$check($first($boardC, '') === 'myHand-0!FSM!', 'C: no kill, a 0-power target — Karis; got ' . $first($boardC, ''));
// D) The value is the best target's, on the Weakness scale: a kill is worth the kill, less the resource.
$build($boardB);
$W = SWUBotWeights('midrange', 1);
$ability = null; foreach ((array)$legal()['actions'] as $a) if (str_contains(strval($a['cardID']), 'LeaderAbility')) $ability = $a;
$con = null; foreach (SWUBotUnits(2) as $v) if ($v['cardID'] === 'SEC_163') $con = $v;
$want = _SWUBotWeaknessScore(1, $con, true, 0, 'GIVE_WEAKNESS', 1, $W) - $W['develop'] * 1;
$check($ability !== null && abs(_SWUBotAbilityValue($botCtx('midrange'), $ability, $W) - $want) < 1e-9, 'D: the Action is worth the kill less 1 resource');

// ── spentetb ──
// E) Chimaera's paired pick between my Marrok (3, 2/6 Sentinel — an ongoing ability) and my Solar Sailer (3, 3/3, its When
// Played already used). Both exhausted (no unused-attack premium). Marrok sits first. The Sailer is the price.
$pairPick = function (string $variant, string $other = 'ASH_030') use ($build, $act, $legal) {
    $build(function ($b) use ($other) {
        $b->MyLeader('HMW_003', true, false, true); $b->MyBase('HMW_027');
        $b->WithGroundUnitForPlayer(1, $other, false); $b->WithSpaceUnitForPlayer(1, 'HMW_154', false);
        $b->FillResourcesForPlayer(1, 'LAW_097', 7); $b->WithCardInHandForPlayer(1, 'ASH_052');
        $b->WithGroundUnitForPlayer(2, 'LOF_070');
    });
    $l = $legal(); $play = null;
    foreach ((array)$l['actions'] as $a) if (strval($a['cardID']) === 'myHand-0!FSM!') $play = $a;
    if ($play === null) return 'NO-PLAY';
    $act(1, intval($play['mode'] ?? 10001), 'myHand-0!FSM!');
    $l = $legal();
    if (($l['kind'] ?? '') !== 'decision') return 'NO-PROMPT';
    $p = SWUBotHeuristicChoose('midrange', (array)$l['actions'], $l, $variant);
    $v = SWUBotViewForMz(1, strval($p['cardID'] ?? ''));
    return $v['cardID'] ?? strval($p['cardID'] ?? '');
};
$check($pairPick('no-spentetb') === 'ASH_030', 'E fixture: today Marrok (first, same printed cost) is the price; got ' . $pairPick('no-spentetb'));
$check($pairPick('') === 'HMW_154', 'E: the spent Solar Sailer is the price; got ' . $pairPick(''));

// F) Only a SPENT When Played is discounted: LOF_033 Nameless Terror is the Sailer's twin (3-cost 3/3, a When Played) but also has
// an On Attack it keeps using. It keeps its full price; the Sailer is still the one to give.
$check($pairPick('', 'LOF_033') === 'HMW_154', 'F: a When Played + On Attack unit is not discounted — the Sailer goes; got ' . $pairPick('', 'LOF_033'));

// ── observerfirst ── (Ninin vs Maul Blue, R16: HK-47 went down before Chimaera's kill and pinged the base for it)
// G) HK-47 (2, "When an enemy unit is defeated: Deal 1 damage to its controller's base") + Lost and Forgotten (6, a kill),
// 8 resources, an enemy Talzin's Assassin to defeat. HK-47 first, so the kill pings.
$boardG = $hem(['LAW_133', 'LOF_130'], 8, fn($b) => $b->WithGroundUnitForPlayer(2, 'LOF_035'));
$check($first($boardG, 'no-observerfirst') === 'myHand-0!FSM!', 'G fixture: today the removal goes first; got ' . $first($boardG, 'no-observerfirst'));
$check($first($boardG, '') === 'myHand-1!FSM!', 'G: HK-47 goes down before the kill; got ' . $first($boardG, ''));
// G2) Not both this round (7 resources: HK-47 leaves 5, short of 6): unchanged.
$boardG2 = $hem(['LAW_133', 'LOF_130'], 7, fn($b) => $b->WithGroundUnitForPlayer(2, 'LOF_035'));
$check($first($boardG2, '') === $first($boardG2, 'no-observerfirst'), 'G2: both are not affordable — unchanged');
// G3) An HK-47 is already in play: the kill pings anyway — unchanged.
$boardG3 = $hem(['LAW_133', 'LOF_130'], 8, function ($b) { $b->WithGroundUnitForPlayer(2, 'LOF_035'); $b->WithGroundUnitForPlayer(1, 'LOF_130', false); });
$check($first($boardG3, '') === $first($boardG3, 'no-observerfirst'), 'G3: an observer already in play — unchanged');
// G4) Only a KILL waits: General Grievous (7, "Deal 4 damage to a base") kills no unit, so it does not wait for HK-47.
$boardG4 = $hem(['HMW_159', 'LOF_130'], 9, fn($b) => $b->WithGroundUnitForPlayer(2, 'LOF_035'));
$check($first($boardG4, '') === $first($boardG4, 'no-observerfirst'), 'G4: a play that kills nothing does not wait — unchanged; got ' . $first($boardG4, '') . ' vs ' . $first($boardG4, 'no-observerfirst'));

// ── uniquereplay ── (Ninin vs Ackbar Data Vault, R3: a second Nuvo Vindi on purpose — the uniqueness rule defeated the
// exhausted first copy, and the fresh one's When Played gave the X-Wing another Weakness token. Owner: "this is usually true
// for cheap units with When Played abilities.") Leader exhausted so Hemlock's own Weakness Action is not the alternative.
$uniq = function (string $cid, bool $readyCopy, int $res) {
    return function ($b) use ($cid, $readyCopy, $res) {
        $b->MyLeader('HMW_003', false, false, true); $b->MyBase('HMW_027');
        $b->FillResourcesForPlayer(1, 'LAW_097', $res);
        $b->WithGroundUnitForPlayer(1, $cid, $readyCopy);
        $b->WithCardInHandForPlayer(1, $cid);
        $b->WithGroundUnitForPlayer(2, 'LOF_035');
    };
};
// H) Nuvo Vindi (3, When Played: give a Weakness token) in play EXHAUSTED, a second in hand: replay it.
$check($first($uniq('HMW_062', false, 3), 'no-uniquereplay') !== 'myHand-0!FSM!', 'H fixture: today the second copy is held; got ' . $first($uniq('HMW_062', false, 3), 'no-uniquereplay'));
$check($first($uniq('HMW_062', false, 3), '') === 'myHand-0!FSM!', 'H: the spent Nuvo Vindi is replaced to use its When Played again; got ' . $first($uniq('HMW_062', false, 3), ''));
// H2) The copy in play is READY and undamaged: replacing it trades a ready body for an exhausted one — held, as before.
$check($first($uniq('HMW_062', true, 3), '') !== 'myHand-0!FSM!', 'H2: a ready, healthy copy is not replaced; got ' . $first($uniq('HMW_062', true, 3), ''));
$build($uniq('HMW_062', true, 3));
$check(!_SWUBotUniqueReplayWorthIt(1, 'HMW_062'), 'H2: a ready, undamaged copy does not qualify for a replay');
// H3) Not a CHEAP unit: a second Chimaera (7) over an exhausted one — held, as before.
$check($first($uniq('ASH_052', false, 7), '') !== 'myHand-0!FSM!', 'H3: an expensive unique is not replayed; got ' . $first($uniq('ASH_052', false, 7), ''));
// H4) Cheap but NO When Played (ASH_030 Marrok, 3, a Sentinel): a second copy refreshes nothing — held, as before.
$check($first($uniq('ASH_030', false, 3), '') !== 'myHand-0!FSM!', 'H4: a unique with no When Played is not replayed; got ' . $first($uniq('ASH_030', false, 3), ''));

bot_test_finish();

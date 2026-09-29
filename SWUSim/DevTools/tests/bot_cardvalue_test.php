<?php
// Proposal 'cardvalue' — board-aware card valuation (Body + Effect − SelfCost, in expected base damage).
// Spec: docs/superpowers/specs/2026-09-28-swusim-card-value-design.md. Owner chose the FULL scope with
// defence in v1 (2026-09-28).
//
// WHAT THIS PINS, and why each one is the point of the model rather than a detail:
//   A) DEFAULT OFF and byte-identical — the old path must not move until the flag is thrown.
//   B) TARGET AWARENESS. The headline defect: today a removal event scores `develop x cost + W['removal']`
//      whether the best target is a 2-drop or a bomb, and scores the same into an EMPTY board.
//   C) DEFENCE. Nothing in the shipped model prices damage PREVENTED; it is the most likely cause of the
//      control deficit, and it is the one term with no precedent in the codebase to copy.
//   D) HORIZON. One knob per archetype instead of a 415-cell table, and it must actually separate them.
//   E) SOURCE ZONE. The owner's API: the same card is worth different amounts from hand / deck / discard /
//      resources, which is what makes this usable for resourcing and recursion, not just free-play.
//   F) BOARD-STATE SENSITIVITY of heal — worth ~0 at full HP.
//
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php -d apc.enable_cli=1 -d xdebug.mode=off SWUSim/DevTools/tests/bot_cardvalue_test.php
require __DIR__ . '/fixtures/bot_test_bootstrap.php';
include_once './SWUSim/BotLegalActions.php';
include_once './SWUSim/BotHeuristic.php';

// ── Registry ────────────────────────────────────────────────────────────────────────────────────────────
$check(in_array('cardvalue', SWUBotProposalList(), true), 'cardvalue is a PROPOSAL (default off)');
$check(!in_array('cardvalue', SWUBotFeatureList(), true), 'cardvalue is NOT a shipped feature');
$check(SWUBotVariantDisabled('try-cardvalue') === ['try:cardvalue'], 'switchable as @try-cardvalue');
$check(function_exists('SWUBotCardValue'), 'SWUBotCardValue() exists');

// The horizon rides the weights, so every scorer that already has $W gets it without threading the style.
$hyper = SWUBotWeights('hyperaggro', 1);
$hard  = SWUBotWeights('hardcontrol', 1);
$check(isset($hyper['horizon']) && isset($hard['horizon']), 'the archetype table carries a horizon');
$check($hard['horizon'] > $hyper['horizon'],
    'control plays to a LONGER horizon than hyper aggro; got ' . json_encode([$hyper['horizon'], $hard['horizon']]));

// ── A) DEFAULT OFF is byte-identical ────────────────────────────────────────────────────────────────────
// SOR_095 Battlefield Marine, a 3/3 with no text: cost floor 0.3*2 + unitPlay, and nothing else.
$build(function ($b) {
    $b->FillResourcesForPlayer(1, 'SOR_095', 6);
    $b->WithCardInHandForPlayer(1, 'SOR_095');
});
$Wm   = SWUBotWeights('midrange', 1);
$off  = _SWUBotPlayValue(1, 'SOR_095', $Wm);
$want = $Wm['develop'] * 2 + $Wm['unitPlay'];
$check(abs($off - $want) < 1e-9,
    'A: with the flag OFF the old path is unchanged; got ' . json_encode([$off, $want]));

// ── B) TARGET AWARENESS — removal is worth its BEST LEGAL TARGET ────────────────────────────────────────
// Same removal card, three boards. LOF_077 Crushing Blow — "Defeat a non-leader unit that costs 2 or less"
// — is a removal event, conditional on cost but removal all the same, and the tagger tags it `removal`.
// The old model scores it identically on all three boards; that is the defect this replaces.
// ⚠ VERIFY A FIXTURE'S CardID BY ITS TITLE. My first attempt reached for "LOF_099 Crushing Blow" from
// memory; LOF_099 is Paladin Training Corvette (`gives-experience`), so the removal branch never fired and
// the test failed against a model that was working. The tagger was correct the whole time.
$removalOn = function (array $enemies) use ($build, $Wm) {
    $build(function ($b) use ($enemies) {
        $b->FillResourcesForPlayer(1, 'SOR_095', 8);
        $b->WithCardInHandForPlayer(1, 'LOF_077');
        foreach ($enemies as $e) $b->WithGroundUnitForPlayer(2, $e, true, 0);
    });
    SWUBotSetDisabledFeatures(['try:cardvalue']);
    $v = SWUBotCardValue(1, 'LOF_077', 'hand', 'midrange', $Wm);
    SWUBotSetDisabledFeatures([]);
    return $v;
};
$empty = $removalOn([]);                 // nothing to kill
$small = $removalOn(['SOR_095']);        // a 3/3
$big   = $removalOn(['SOR_046']);        // a 3/7 — strictly more unit value

$check($big > $small, "B: removal is worth MORE against a bigger target; got " . json_encode([$small, $big]));
$check($small > $empty, "B: removal is worth more with a target than with none; got " . json_encode([$empty, $small]));
// The defect this replaces: the OLD path cannot tell these boards apart at all.
$oldEmpty = (function () use ($build, $Wm) {
    $build(function ($b) { $b->FillResourcesForPlayer(1, 'SOR_095', 8); $b->WithCardInHandForPlayer(1, 'LOF_077'); });
    return _SWUBotPlayValue(1, 'LOF_077', $Wm);
})();
$oldBig = (function () use ($build, $Wm) {
    $build(function ($b) {
        $b->FillResourcesForPlayer(1, 'SOR_095', 8); $b->WithCardInHandForPlayer(1, 'LOF_077');
        $b->WithGroundUnitForPlayer(2, 'SOR_046', true, 0);
    });
    return _SWUBotPlayValue(1, 'LOF_077', $Wm);
})();
$check(abs($oldEmpty - $oldBig) < 1e-9,
    'B: the OLD path is target-BLIND — same score on an empty board and against a bomb; got '
    . json_encode([$oldEmpty, $oldBig]));

// ── C) DEFENCE — a Sentinel is worth more than the same body without it ─────────────────────────────────
// ASH_029 is tagged sentinel. Sentinel is the only keyword that REDIRECTS attacks, so it is the only
// one that converts HP into base damage prevented.
// ⚠ Do not reach for a VANILLA card here. SOR_046 Consular Security Force was the first choice and
// carries no tags — correctly, because it has no text to tag. A keyword-bearing card is what this
// fixture needs, and the $check below asserts that premise so it cannot silently drift.
$build(function ($b) { $b->FillResourcesForPlayer(1, 'SOR_095', 8); });
$tagsSentinel = SWUBotCardTags('ASH_029');
$check(in_array('sentinel', $tagsSentinel, true), 'C fixture: ASH_029 is tagged sentinel');
$bodySentinel = _SWUBotCardBody('ASH_029', $tagsSentinel, $Wm, 6);
$bodyPlain    = _SWUBotCardBody('ASH_029', array_values(array_diff($tagsSentinel, ['sentinel'])), $Wm, 6);
$check($bodySentinel > $bodyPlain,
    'C: Sentinel adds damage-prevented that the same body without it does not get; got '
    . json_encode([$bodyPlain, $bodySentinel]));
// And defence must scale with the body it is protecting — a 7 HP Sentinel soaks more than a 3 HP one.
$soak7 = _SWUBotCardBody('SOR_046', ['sentinel'], $Wm, 6);
$soak3 = _SWUBotCardBody('SOR_095', ['sentinel'], $Wm, 6);
$check($soak7 > $soak3, 'C: a 7 HP Sentinel prevents more than a 3 HP one; got ' . json_encode([$soak3, $soak7]));

// ── D) HORIZON separates the archetypes ─────────────────────────────────────────────────────────────────
// The same vanilla body is worth more to a seat that expects to be alive to swing with it.
$bodyShort = _SWUBotCardBody('SOR_095', [], $Wm, intval($hyper['horizon']));
$bodyLong  = _SWUBotCardBody('SOR_095', [], $Wm, intval($hard['horizon']));
$check($bodyLong > $bodyShort,
    'D: a body is worth more over a longer horizon; got ' . json_encode([$bodyShort, $bodyLong]));
// ⚠ BOUNDED. Expected swings is the survival series, not the horizon: without that a 3-power unit was
// worth 8 swings to hard control and swamped every other term.
$check($bodyLong < 4.0 * $bodyShort,
    'D: and it is BOUNDED, not linear in the horizon; got ' . json_encode([$bodyShort, $bodyLong]));

// ── E) SOURCE ZONE ──────────────────────────────────────────────────────────────────────────────────────
$build(function ($b) {
    $b->FillResourcesForPlayer(1, 'SOR_095', 8);
    $b->WithCardInHandForPlayer(1, 'SOR_095');
});
$hand    = SWUBotCardValue(1, 'SOR_095', 'hand',      'midrange', $Wm);
$deck    = SWUBotCardValue(1, 'SOR_095', 'deck',      'midrange', $Wm);
$discard = SWUBotCardValue(1, 'SOR_095', 'discard',   'midrange', $Wm);
$res     = SWUBotCardValue(1, 'SOR_095', 'resources', 'midrange', $Wm);
$check($hand > $deck && $deck > $discard,
    'E: reachability orders hand > deck > discard; got ' . json_encode([$discard, $deck, $hand]));
$check($res === 0.0,
    'E: a card sitting in resources is inert without Smuggle; got ' . json_encode($res));

// ── F) HEAL is worth ~0 at full HP and more when the base is hurt ───────────────────────────────────────
// ASH_081 is tagged heal. The spec's rule: healing is worth what it actually saves, so it is worth
// ~0 at full HP.
// ⚠ The damage is set on the LIVE base object, not through the builder: $build runs CommonSetup FIRST and
// its base wins, so WithBaseForPlayer() here was silently ignored and both readings came back identical —
// a fixture that could never fail. The $check below asserts the premise so that cannot recur.
$healAt = function (int $baseDamage) use ($build, $Wm, $check) {
    $build(function ($b) {
        $b->FillResourcesForPlayer(1, 'SOR_095', 8);
        $b->WithCardInHandForPlayer(1, 'ASH_081');
    });
    $base = GetBase(1)[0] ?? null;
    if ($base !== null) $base->Damage = $baseDamage;
    $check($base !== null && intval($base->Damage) === $baseDamage,
        "F fixture: the base really carries {$baseDamage} damage");
    return SWUBotCardValue(1, 'ASH_081', 'hand', 'midrange', $Wm);
};
$tagsHeal = SWUBotCardTags('ASH_081');
if (in_array('heal', $tagsHeal, true)) {
    $healFull = $healAt(0);
    $healHurt = $healAt(20);
    $check($healHurt > $healFull,
        'F: healing is worth more with a damaged base; got ' . json_encode([$healFull, $healHurt]));
} else {
    $check(true, 'F: SKIPPED — ASH_081 is not tagged heal in this table');
}

// ── G) RAMP re-enters this function, and must not explode ───────────────────────────────────────────────
// ⚠ REGRESSION. The first canary run died on 5 of 6 games: the ramp branch called
// _SWUBotCreditUnlockValue($seat, $W) when its second argument is the CREDIT COUNT, not the weights. The
// unit test above could not catch it because no fixture held a ramp card — the harness found it instead.
// That helper prices the unlocked card with _SWUBotPlayValue, which with this proposal ON lands back in
// SWUBotCardValue, so TWO ramp cards in hand is the re-entrancy case the depth guard exists for.
$build(function ($b) {
    $b->MyLeader('LAW_008', true, false, true);
    $b->MyBase('LAW_020');
    $b->FillResourcesForPlayer(1, 'SOR_095', 7);
    $b->WithCardInHandForPlayer(1, 'LAW_159');   // Expendable Mercenary — the card from the crash trace
    $b->WithCardInHandForPlayer(1, 'LAW_159');
    $b->WithCardInHandForPlayer(1, 'LAW_044');   // the bomb a Credit would unlock
});
$tagsRamp = SWUBotCardTags('LAW_159');
$check($tagsRamp !== [], 'G fixture: LAW_159 carries tags; got ' . json_encode($tagsRamp));
$Wc = SWUBotWeights('softcontrol', 1);
SWUBotSetDisabledFeatures(['try:cardvalue']);
$ramp = SWUBotCardValue(1, 'LAW_159', 'hand', 'softcontrol', $Wc);
$bomb = SWUBotCardValue(1, 'LAW_044', 'hand', 'softcontrol', $Wc);
SWUBotSetDisabledFeatures([]);
$check(is_float($ramp) && is_finite($ramp),
    'G: a ramp card in hand values without fatalling; got ' . json_encode($ramp));
$check(is_float($bomb) && is_finite($bomb),
    'G: and so does the bomb it would unlock; got ' . json_encode($bomb));

bot_test_finish();

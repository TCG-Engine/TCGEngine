<?php
// The three curve-value DECISIONS (spec docs/superpowers/specs/2026-10-05-swusim-curve-value-design.md §4): play choice
// (curveplay), resourcing (curveresource), mulligan (curvemull). SHIPPED 2026-10-06 as feature group 'p38' (each measured
// alone, 14,040 games, no deck hurt: docs/superpowers/research/curve-value/2026-10-06-measurement.md); '@no-<name>' turns one off.
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php -d apc.enable_cli=1 -d xdebug.mode=off SWUSim/DevTools/tests/bot_curvedecisions_test.php
require __DIR__ . '/fixtures/bot_test_bootstrap.php';
include_once './SWUSim/BotLegalActions.php';
include_once './SWUSim/BotHeuristic.php';

// ── curveplay ─────────────────────────────────────────────────────────────────────────────────────────────────────
$check(in_array('curveplay', SWUBotFeatureList(), true) && !in_array('curveplay', SWUBotProposalList(), true), 'curveplay is a shipped FEATURE');
$check(SWUBotVariantDisabled('no-curveplay') === ['curveplay'], 'switchable off as @no-curveplay');
$check((SWUBotFeatureGroups()['p38'] ?? null) === ['curveplay', 'curveresource', 'curvemull'], "the three ship together as group 'p38'");
foreach (SWU_BOT_ARCHETYPES as $style) {
    $check(abs((SWUBotWeights($style, 1)['curve'] ?? -1) - 0.30) < 1e-9, "W['curve'] = 0.30 for $style (flat in v1, = develop)");
}
// Mae on an empty board: OFF is unchanged; ON adds exactly 0.30 × her surplus.
$build(function ($b) { $b->FillResourcesForPlayer(1, 'SOR_095', 6); $b->WithCardInHandForPlayer(1, 'HMW_055'); });
$W = SWUBotWeights('midrange', 1);
SWUBotSetDisabledFeatures(['curveplay']);      $off = _SWUBotPlayValue(1, 'HMW_055', $W);
SWUBotSetDisabledFeatures([]);                 $on  = _SWUBotPlayValue(1, 'HMW_055', $W);
SWUBotSetDisabledFeatures([]);
$sur = SWUBotCurveSurplus(1, 'HMW_055', intval(round($W['horizon'])));
// With a board, the surplus carries the test seat's aspect penalty, so its SIGN is not fixed — only that it is non-zero.
$check($sur !== null && abs($sur) > 1e-6, 'premise: Mae has a non-zero surplus on this board; got ' . json_encode($sur));
$check(abs(($on - $off) - 0.30 * $sur) < 1e-9, 'curveplay ON adds exactly W[curve] × surplus; got ' . json_encode([$off, $on, $sur]));
// An unpriced card adds nothing (Condemn, SEC_038: an attack-time downgrade the parser cannot read).
$check(SWUBotCurveValue(1, 'SEC_038') === null, 'premise: Condemn is unpriced');
$build(function ($b) { $b->FillResourcesForPlayer(1, 'SOR_095', 6); $b->WithCardInHandForPlayer(1, 'SEC_038'); });
SWUBotSetDisabledFeatures(['curveplay']);      $off = _SWUBotPlayValue(1, 'SEC_038', $W);
SWUBotSetDisabledFeatures([]);                 $on  = _SWUBotPlayValue(1, 'SEC_038', $W);
SWUBotSetDisabledFeatures([]);
$check(abs($on - $off) < 1e-12, 'an unpriced card scores the same ON and OFF');

// A test that guards a DIFFERENT feature pins p38 off with one line ($GLOBALS['SWUBotPinnedDisabled']), so its numbers
// keep isolating its own feature. SWUBotSetDisabledFeatures() calls inside that test cannot switch a pinned feature back on.
$GLOBALS['SWUBotPinnedDisabled'] = SWU_BOT_PART38_FEATURES;
SWUBotSetDisabledFeatures([]);
$pinned = [SWUBotFeatureOn('curveplay'), SWUBotFeatureOn('curveresource'), SWUBotFeatureOn('curvemull'), SWUBotFeatureOn('actionclock')];
unset($GLOBALS['SWUBotPinnedDisabled']);
$check($pinned === [false, false, false, true], 'the pin turns off exactly the p38 features, even under SWUBotSetDisabledFeatures([]); got ' . json_encode($pinned));
$check(SWUBotFeatureOn('curveplay'), 'with no pin, curveplay is on again');

// ── Final-review fixes (2026-10-06) ───────────────────────────────────────────────────────────────────────────────
// Review #2: only a prompt that IGNORES an aspect penalty is 'waived'; "for free" is 'free'; a mere discount stays 'paid'.
$check(_SWUBotPlayPromptRoute('Play_a_card_(ignore_1_of_its_aspect_penalties)') === 'waived', 'the LAW base waiver prompt is waived');
$check(_SWUBotPlayPromptRoute('Play_a_unit_from_your_discard_pile_for_free') === 'free', 'a for-free prompt is free');
$check(_SWUBotPlayPromptRoute('Play_a_Capital_Ship_unit_(costs_1_less)') === 'paid', 'a discount prompt still pays its penalty');
// Review #3: a search is FREE only when it plays within a combined-cost budget (DoTopDeckPlay, "cost:N"); a discounted
// "Play" search (Kelleran Beq, "count:1") and a "Take" search are paid.
$check(_SWUBotSearchRoute('A,B,C|A,B|cost:5|A:2,B:3|space units|Play|top') === 'free', 'Ackbar (cost budget, Play) is free');
$check(_SWUBotSearchRoute('A,B,C|A,B|count:1|A:2,B:3|units|Play|top') === 'paid', 'Kelleran Beq (count, Play — 3 less) is paid');
$check(_SWUBotSearchRoute('A,B,C|A,B|count:1|A:2,B:3|units|Take|top') === 'paid', 'a Take search is paid');

// ── curveresource ─────────────────────────────────────────────────────────────────────────────────────────────────
$check(in_array('curveresource', SWUBotFeatureList(), true) && !in_array('curveresource', SWUBotProposalList(), true), 'curveresource is a shipped FEATURE');
// Two 2-cost Command/Heroism cards with no text: Battlefield Marine (3/3, on curve) and Liberated Wookiee (2/4, 0.1
// under). SAME cost and aspects, so the shipped keep score (−cost, with the aspect penalty under 'mgcost') ties them;
// only the curve surplus can separate them. The hand is ordered from the board surpluses (higher at index 0). OFF, the
// tie falls back to hand order → index 0 goes. ON, the lower-surplus card (index 1) goes. Soft aggro: no midrange keeps.
$check(CardTitle('LAW_143') === 'Liberated Wookiee' && CardTitle('SOR_095') === 'Battlefield Marine'
    && CardAspect('LAW_143') === CardAspect('SOR_095') && CardCost('LAW_143') === CardCost('SOR_095'), 'premise: two 2-drops with the same cost and aspects');
$build(function ($b) { $b->FillResourcesForPlayer(1, 'SOR_095', 2); });
$sM = SWUBotCurveSurplus(1, 'SOR_095', 4); $sB = SWUBotCurveSurplus(1, 'LAW_143', 4);
$check($sM !== null && $sB !== null && abs($sM - $sB) > 1e-6, 'premise: the two have different surpluses on this board; got ' . json_encode([$sM, $sB]));
[$hi, $lo] = $sM > $sB ? ['SOR_095', 'LAW_143'] : ['LAW_143', 'SOR_095'];
$hand2 = function () use ($build, $hi, $lo) { $build(function ($b) use ($hi, $lo) {
    $b->FillResourcesForPlayer(1, 'SOR_095', 2);
    $b->WithCardInHandForPlayer(1, $hi); $b->WithCardInHandForPlayer(1, $lo);
}); };
$hand2(); SWUBotSetDisabledFeatures(['curveresource']);     $off = SWUBotChooseResourceCards(['seat' => 1, 'style' => 'softaggro'], 1);
$hand2(); SWUBotSetDisabledFeatures([]);                    $on  = SWUBotChooseResourceCards(['seat' => 1, 'style' => 'softaggro'], 1);
SWUBotSetDisabledFeatures([]);
$check($off === ['myHand-0'], 'OFF: equal keeps → hand order; got ' . json_encode($off));
$check($on === ['myHand-1'], "ON: the lower-surplus card ($lo) is resourced first; got " . json_encode($on));
// Cost still leads: an OVER-curve 7-drop (Kelleran Beq) is still resourced before an under-curve 2-drop (Liberated
// Wookiee) — the 0.5 tiebreak cannot overturn 5 points of cost. Same aspects, so no penalty skews it.
$build(function ($b) { $b->FillResourcesForPlayer(1, 'SOR_095', 2); $b->WithCardInHandForPlayer(1, 'LAW_143'); $b->WithCardInHandForPlayer(1, 'LOF_100'); });
$check(CardAspect('LOF_100') === CardAspect('LAW_143') && SWUBotCurveSurplus(1, 'LOF_100', 4) > SWUBotCurveSurplus(1, 'LAW_143', 4) + 0.5,
    'premise: Kelleran Beq out-surpluses the Wookiee by more than half a resource, same aspects');
SWUBotSetDisabledFeatures([]);
$pick = SWUBotChooseResourceCards(['seat' => 1, 'style' => 'softaggro'], 1);
SWUBotSetDisabledFeatures([]);
$check($pick === ['myHand-1'], 'ON, soft aggro still resources the 7-drop over the under-curve 2-drop; got ' . json_encode($pick));

// ── curvemull ─────────────────────────────────────────────────────────────────────────────────────────────────────
$check(in_array('curvemull', SWUBotFeatureList(), true) && !in_array('curvemull', SWUBotProposalList(), true), 'curvemull is a shipped FEATURE');
$hand6 = function (array $cards) use ($build) { $build(function ($b) use ($cards) { foreach ($cards as $c) $b->WithCardInHandForPlayer(1, $c); }); };
$mull = function (array $cards, string $style, bool $on = true) use ($hand6) {
    $hand6($cards);
    SWUBotSetDisabledFeatures($on ? [] : ['curvemull']);
    $r = _SWUBotShouldMulligan(1, $style);
    SWUBotSetDisabledFeatures([]);
    return $r;
};
$check(CardTitle('LOF_084') === 'Knight of Ren' && CardTitle('HMW_104') === 'Garnac', 'premise: LOF_084 Knight of Ren, HMW_104 Garnac');
$check($mull(['SOR_095', 'SOR_095', 'SOR_095', 'SOR_095', 'HMW_093', 'HMW_093'], 'midrange', false) === null,
    '@no-curvemull: the bot has no mulligan opinion (null → keep)');
$check($mull(['SOR_095', 'SOR_095', 'SOR_095', 'SOR_095', 'HMW_093', 'HMW_093'], 'midrange') === false,
    'KEEP: two 7-drops set aside, four on-curve 2-drops castable (surplus 0)');
$check($mull(['HMW_093', 'HMW_093', 'HMW_093', 'HMW_093', 'HMW_093', 'SOR_095'], 'midrange') === true,
    'MULLIGAN: after setting two 7-drops aside, only one card is castable by round 3');
$check($mull(['ASH_190', 'ASH_190', 'ASH_190', 'ASH_190', 'HMW_093', 'HMW_093'], 'midrange') === true,
    'MULLIGAN: four castable cards, but all under curve (summed surplus < 0)');
$check($mull(['LOF_084', 'LOF_084', 'LOF_084', 'LOF_084', 'HMW_093', 'HMW_093'], 'hyperaggro') === true,
    'MULLIGAN (aggro wing): castable and on curve, but no play at cost ≤ 2');
$check($mull(['LOF_084', 'LOF_084', 'LOF_084', 'LOF_084', 'HMW_093', 'HMW_093'], 'midrange') === false,
    'KEEP (midrange): the same hand — only the aggro wing needs the ≤ 2 drop');
// An EXPLICIT mulligan proposal still decides while curvemull is on by default: the four under-curve 2-drops are a
// curvemull mulligan, but '@try-mullnocast' (keep with 2+ castable by round 2) must win and keep.
$hand6(['ASH_190', 'ASH_190', 'ASH_190', 'ASH_190', 'HMW_093', 'HMW_093']);
SWUBotSetDisabledFeatures(['try:mullnocast']);
$explicit = _SWUBotShouldMulligan(1, 'midrange');
SWUBotSetDisabledFeatures([]);
$check($explicit === false, 'an explicit @try-mullnocast overrides the default curvemull (it keeps); got ' . json_encode($explicit));
// Review Focus 4: an all-UNPRICED castable hand counts as surplus 0 and keeps.
$check(SWUBotCurveValue(0, 'HMW_104', false) === null, 'premise: Garnac is unpriced');
$check($mull(['HMW_104', 'HMW_104', 'HMW_104', 'HMW_104', 'HMW_093', 'HMW_093'], 'midrange') === false,
    'KEEP: four castable but unpriced cards are not a mulligan by themselves');

bot_test_finish();

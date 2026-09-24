<?php
// THE TWO MIDRANGE LEVERS from the owner's 99-game human-vs-bot run.
// 'mgbomb' SHIPPED 2026-09-24 as feature group p12 (default ON). 'mgkill' is still a default-OFF proposal.
//
// 'mgkill'  — scale MIDRANGE's kill weight by 0.6 (0.90 -> 0.54 against a flat base of 0.60, so the
//   kill:base ratio inverts from 1.5:1 to 0.9:1 and the seat prefers the swing to the trade). This is the
//   first measured win the project had: the 2026-09-24 overnight screen (4 arms x 10,120 games) put the
//   global `@w-kill-down` probe at midrange 43.2% against jitter nulls at 41.1% / 40.8% — +2.2pp, paired
//   McNemar p=0.0038 and p=0.0011 against two independent nulls, broad over 4 of 5 midrange decks, and no
//   cost to aggro. That probe was GLOBAL, so it is not shippable; this is the midrange-scoped version.
//   ⚠ SCREENED 2026-09-24: scoped and paired, mgkill came to +0.96pp over the null mean, p=0.072 / 0.0525
//   against the two nulls — the right DIRECTION but NOT significant. It did beat the opposite-direction
//   'mgtrade' head to head 327:233 (p=0.0001), which settles that lower is better. Needs more seeds.
//
// 'mgbomb'  — give MIDRANGE the resourcing rule the control wing already has (owner ruling 2026-09-13:
//   "keep a hand it can CAST — everything castable within ~2 regroups, plus ONE copy of its biggest card").
//   Today that rule is gated `$rank >= 3`, so midrange falls through to the bare `-$cost` fallback and
//   buries its most expensive card. Measured over the run: the midrange bot's resource pick sits +0.67
//   (block 1) and +0.92 (block 4) ABOVE its own hand average and is the most expensive card 40-44% of the
//   time, while the owner's pick is -0.57 and -0.54 BELOW his own hand average across two completely
//   different archetypes (Ahsoka Blue, hand avg 3.31; Colossus, hand avg 4.88). Shipping 'mgkeep' as p11
//   did NOT move the bot's skew. MEASURED +3.14pp for midrange, p=0.0000 vs BOTH nulls -> shipped as p12.
//
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php -d apc.enable_cli=1 -d xdebug.mode=off SWUSim/DevTools/tests/bot_midrange_levers_test.php
require __DIR__ . '/fixtures/bot_test_bootstrap.php';
include_once './SWUSim/BotLegalActions.php';
include_once './SWUSim/Custom/BotLookahead.php';
include_once './SWUSim/BotHeuristic.php';

$check(SWUBotVariantDisabled('try-mgkill') === ['try:mgkill'], 'mgkill is still a proposal (unproven, p~0.05-0.07)');
// mgbomb SHIPPED 2026-09-24 as feature group p12 — it is ON by default and '@no-mgbomb' / '@no-p12' turn it off.
$check(in_array('mgbomb', SWUBotFeatureList(), true) && SWUBotFeatureGroups()['p12'] === ['mgbomb'],
    'mgbomb is a shipped FEATURE, group p12');
$check(SWUBotVariantDisabled('no-mgbomb') === ['mgbomb'] && SWUBotVariantDisabled('no-p12') === ['mgbomb'],
    "'@no-mgbomb' and '@no-p12' both switch it off");
$check(SWUBotVariantDisabled('try-mgbomb') === null, 'mgbomb is no longer a proposal');
// ⚠ MEASURED HARMFUL, kept only so the measurement stays reproducible: mgtrade scored midrange 40.27% against
// jitter nulls at 41.50/41.39, paired McNemar p=0.0008 and 0.0034 — significantly WORSE than doing nothing,
// and it lost to mgkill head to head 233:327 (p=0.0001). Do not screen it again; do not ship it.
$check(SWUBotVariantDisabled('try-mgtrade') === ['try:mgtrade'], 'mgtrade stays a proposal for reproduction only');

// ── mgkill: the kill weight, MIDRANGE ONLY ─────────────────────────────────────────────────────────────
$w = function (string $style, array $flags) {
    SWUBotSetDisabledFeatures($flags);
    $o = SWUBotWeights($style, 1);
    SWUBotSetDisabledFeatures([]);
    return $o;
};
$ON = ['try:mgkill'];
$base = $w('midrange', []);
$check(abs($base['kill'] - 0.90) < 1e-9 && abs($base['base'] - 0.60) < 1e-9, 'shipped midrange is kill 0.90 / base 0.60');
$on = $w('midrange', $ON);
$check(abs($on['kill'] - 0.54) < 1e-9, 'mgkill scales midrange kill 0.90 -> 0.54');
$check(abs($on['kill'] / $on['base'] - 0.9) < 1e-9, 'mgkill inverts the midrange kill:base ratio to 0.9:1');
$check(count(array_diff_assoc($on, $base)) === 1, 'mgkill changes exactly one weight');
// ⚠ the whole point of the scoped version: every OTHER archetype must be untouched.
foreach (['hyperaggro', 'softaggro', 'softcontrol', 'hardcontrol'] as $s) {
    $check($w($s, $ON) == $w($s, []), "mgkill leaves $s untouched (the screened probe was global; this is not)");
}

// ── mgbomb: midrange resources like control — cheapest castable first, and never its bomb ──────────────
// 4 resources, so the rule's horizon is $soon = 4 + 1 + 2 = 7 and all three cards below are "castable soon".
// Baseline (`-$cost`) therefore buries the 6-drop; the control rule protects it and buries a cheap card.
$seat = function (array $hand) use ($build) {
    $build(function ($b) use ($hand) {
        $b->MyLeader('ASH_005', false, false, true); $b->MyBase('JTL_024');
        $b->FillResourcesForPlayer(1, 'SOR_095', 4, false);
        foreach ($hand as $c) $b->WithCardInHandForPlayer(1, $c);
        $b->TheirLeader('ASH_009', true);
    });
};
$pick = function (array $flags, string $style = 'midrange') use ($botCtx) {
    SWUBotSetDisabledFeatures($flags);
    $mz = SWUBotChooseResourceCards($botCtx($style), 1);
    SWUBotSetDisabledFeatures([]);
    $o = GetHand(1)[intval(substr(((array)$mz)[0] ?? 'myHand-0', strlen('myHand-')))] ?? null;
    return $o === null ? '' : strval($o->CardID ?? '');
};
// ASH_056 Huyang cost 2 · LAW_039 Latts Razzi cost 3 · ASH_112 Luke Skywalker cost 6 (the bomb)
$HAND = ['ASH_056', 'LAW_039', 'ASH_112'];
$seat($HAND);
$check($pick(['mgbomb']) === 'ASH_112', 'PRE-p12 BASELINE (@no-mgbomb): midrange buried its 6-drop — `-$cost` picks the most expensive');
// ⚠ NOT the 2-drop: ASH_056 Huyang (2 cost, 2/4) is an "efficient body" (power+HP >= 2x cost) and the
// SHIPPED `mgkeep` (p11) already protects it, so it scores highest of the three. The bomb rule makes
// ASH_112 unresourceable (200) and the pick falls to the cheapest UNPROTECTED card. Full order with the
// proposal on is LAW_039 -> ASH_112 -> ASH_056; the behavioural claim is that the BOMB is no longer first.
$seat($HAND);
$check($pick([]) === 'LAW_039', 'SHIPPED DEFAULT: midrange no longer buries its bomb — the pick moves to the cheapest unprotected card');

// The control wing already has this rule, so the proposal must not change it.
// ⚠ This pair asserts UNCHANGED, not "the bomb is protected". The opponent leader here is ASH_009, an
// AGGRO leader, so for rank >= 3 the shipped `resourcing3` (group p8) tier sort REPLACES the keep score
// entirely — which is why hardcontrol also buries the 6-drop. mgbomb must not touch that branch either.
$seat($HAND);
$ctrlOff = $pick([], 'hardcontrol');
$seat($HAND);
$ctrlOn = $pick(['mgbomb'], 'hardcontrol');
$check($ctrlOff === $ctrlOn, "mgbomb leaves hardcontrol unchanged — it already had the rule (both picked $ctrlOff)");

// And an AGGRO seat keeps the aggro fallback: the proposal is midrange-only.
$seat($HAND);
$aggOff = $pick([], 'hyperaggro');
$seat($HAND);
$aggOn = $pick(['mgbomb'], 'hyperaggro');
$check($aggOff === $aggOn, "mgbomb leaves hyperaggro unchanged (both picked $aggOff)");

bot_test_finish();

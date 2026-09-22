<?php
// The OWNER'S RESOURCING RULINGS (2026-09-22), encoded as proposals 'resourcing2', 'krennicramp', 'aurathreat'.
// Each owner position from bot-sweeps/2026-09-21_resourcing_rulings.md is rebuilt as a board and must produce the
// card the owner ruled. Positions are soft/hard control vs Vader (JTL_006, flavour 'space').
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php -d apc.enable_cli=1 -d xdebug.mode=off SWUSim/DevTools/tests/bot_owner_resourcing_test.php
require __DIR__ . '/fixtures/bot_test_bootstrap.php';
include_once './SWUSim/BotLegalActions.php';
include_once './SWUSim/Custom/BotLookahead.php';
include_once './SWUSim/BotHeuristic.php';

foreach (['resourcing2', 'krennicramp', 'aurathreat'] as $p) $check(SWUBotVariantDisabled("try-$p") === ["try:$p"], "proposal $p is registered");

// The card(s) the control resourcer puts away first, with or without the proposal.
$resourced = function (int $n, bool $on, bool $opening = false) use ($botCtx) {
    SWUBotSetDisabledFeatures($on ? ['try:resourcing2'] : []);
    $ctx = $botCtx('softcontrol');
    if ($opening) $ctx['tooltip'] = 'Choose_2_cards_to_resource';
    $out = [];
    foreach (SWUBotChooseResourceCards($ctx, $n) as $mz) $out[] = strval(GetHand(1)[intval(substr($mz, strlen('myHand-')))]->CardID ?? '?');
    SWUBotSetDisabledFeatures([]);
    sort($out);
    return $out;
};
// Vader Yellow's side: leader JTL_006 (flavour 'space') and a board of ships.
$vader = function ($b, array $ships) { $b->TheirLeader('JTL_006', true); foreach ($ships as $s) $b->WithSpaceUnitForPlayer(2, $s, false); };
$FIVE = ['SEC_215', 'JTL_085', 'JTL_221', 'JTL_081', 'JTL_081'];   // Sheathipede, Victor Leader, AT-Hauler, 2 FO TIEs

// ── Q1: Thrawn DV, round 3, 4 resources — owner: resource TRASK WALKER, not Pre Vizsla ─────────────────
$build(function ($b) use ($vader, $FIVE) {
    $b->MyLeader('JTL_002', false, false, true); $b->FillResourcesForPlayer(1, 'SOR_095', 4, false);
    foreach (['SEC_078', 'ASH_133', 'LAW_133', 'ASH_048', 'ASH_053'] as $c) $b->WithCardInHandForPlayer(1, $c);
    $vader($b, $FIVE);
});
$check($resourced(1, true) === ['ASH_133'], 'Q1: resource Trask Walker (keep Pre Vizsla — it answers the swarm — and HSD)');
$check(!in_array('SEC_078', $resourced(1, true), true), 'Q1: Hyperspace Disaster is never resourced vs Vader');

// ── Q2: Krennic Splash, round 2, 3 resources — owner: resource PRE VIZSLA, keep Chimaera ──────────────
$build(function ($b) use ($vader) {
    $b->MyLeader('LAW_008', false, false, true); $b->FillResourcesForPlayer(1, 'SOR_095', 3, false);
    foreach (['ASH_053', 'ASH_079', 'LAW_039', 'LAW_159', 'ASH_052', 'ASH_116'] as $c) $b->WithCardInHandForPlayer(1, $c);
    $vader($b, ['JTL_081', 'JTL_081', 'SEC_215', 'SEC_213', 'JTL_081']);
});
$base2 = $resourced(1, false);
$check($resourced(1, true) === ['ASH_053'], "Q2: resource Pre Vizsla, keep Chimaera (the default resources " . implode(',', $base2) . ')');

// ── Q3: Thrawn DV OPENING, 0 resources — owner: resource ONE No Glory + ONE Reanimated Night Trooper ──
$build(function ($b) use ($vader) {
    $b->MyLeader('JTL_002', false, false, true);
    foreach (['ASH_045', 'SEC_078', 'ASH_116', 'JTL_043', 'ASH_045', 'LAW_133'] as $c) $b->WithCardInHandForPlayer(1, $c);
    foreach (['JTL_043', 'JTL_043', 'LAW_133', 'SOR_095', 'SOR_095'] as $c) $b->WithCardInDeckForPlayer(1, $c);   // the list: 3 No Glory, 2 L&F
    $vader($b, []);
});
$q3 = $resourced(2, true, true);
$check($q3 === ['ASH_045', 'JTL_043'], 'Q3: opening — one No Glory (3-of) + one Night Trooper (the duplicate); keep L&F, HSD, a 1-drop pair (got ' . implode(',', $q3) . ')');

// ── Q4: Maul Blue Force, round 4, 5 resources — owner: resource ONE Pre Vizsla (the duplicate) ─────────
$build(function ($b) use ($vader) {
    $b->MyLeader('LOF_009', false, false, true); $b->FillResourcesForPlayer(1, 'SOR_095', 5, false);
    foreach (['ASH_053', 'LOF_031', 'LOF_035', 'ASH_053', 'JTL_043'] as $c) $b->WithCardInHandForPlayer(1, $c);
    $vader($b, ['JTL_081', 'JTL_081', 'JTL_081', 'LAW_135', 'LAW_135', 'SEC_215']);
});
$check($resourced(1, true) === ['ASH_053'], 'Q4: resource one Pre Vizsla (two in hand)');

// ── the proposal is inert for non-control archetypes ───────────────────────────────────────────────
SWUBotSetDisabledFeatures(['try:resourcing2']);
$aggroPick = SWUBotChooseResourceCards($botCtx('softaggro'), 1);
SWUBotSetDisabledFeatures([]);
$check($aggroPick === SWUBotChooseResourceCards($botCtx('softaggro'), 1), 'resourcing2 changes nothing for an aggro seat');

// ── krennicramp: fodder on board → the leader's sacrifice-for-Credit ─────────────────────────────────
$rules = SWUBotRulesAfterFilter();
$kr = function (bool $withFodder) use ($build) {
    $build(function ($b) use ($withFodder) {
        $b->MyLeader('LAW_008', true, false, false); $b->FillResourcesForPlayer(1, 'SOR_095', 2);
        if ($withFodder) $b->WithGroundUnitForPlayer(1, 'ASH_116', true);   // Ant Droid (1, When Defeated: draw)
        $b->WithCardInHandForPlayer(1, 'ASH_116');
        $b->TheirLeader('JTL_006', true);
    });
};
$kr(true);
$acts = array_map(fn($a) => strval($a['cardID']), $botCtx('softcontrol')['actions']);
$abil = array_values(array_filter($acts, fn($c) => str_ends_with($c, '!CustomInput!LeaderAbility')));
$check(!empty($abil), 'fixture: the Krennic leader ability is on offer');
SWUBotSetDisabledFeatures(['try:krennicramp']); $p = ($rules['krennic-ramp'])($botCtx('softcontrol')); SWUBotSetDisabledFeatures([]);
$check($p !== null && str_ends_with(strval($p['cardID']), '!CustomInput!LeaderAbility'), 'krennicramp: a cheap body on board → sacrifice it to Krennic for a Credit');
$kr(false);
SWUBotSetDisabledFeatures(['try:krennicramp']); $p = ($rules['krennic-ramp'])($botCtx('softcontrol')); SWUBotSetDisabledFeatures([]);
$check($p !== null && strval($p['cardID']) === 'myHand-0!FSM!', 'krennicramp: no fodder yet → play the cheap body first');
$check(($rules['krennic-ramp'])($botCtx('softcontrol')) === null, 'krennicramp is inert by default');

// ── aurathreat: Victor Leader with four other ships threatens 2 + 4 ─────────────────────────────────
$build(function ($b) use ($vader, $FIVE) { $b->MyLeader('SOR_014', false, false, true); $vader($b, $FIVE); });
$victor = null; $hauler = null;
foreach (SWUBotUnits(2) as $u) { if ($u['cardID'] === 'JTL_085') $victor = $u; if ($u['cardID'] === 'JTL_221') $hauler = $u; }
$check($victor !== null && _SWUBotAuraGrantedPower($victor) === 4, 'Victor Leader grants +1 to each of the other four ships');
SWUBotSetDisabledFeatures(['try:aurathreat']);
$vt = SWUBotUnitBaseThreat(1, $victor); $ht = SWUBotUnitBaseThreat(1, $hauler);
SWUBotSetDisabledFeatures([]);
$check($vt > $ht, "aurathreat: Victor Leader ($vt) outranks the AT-Hauler ($ht) as a threat — the owner's 6 vs 5");
$check(SWUBotUnitBaseThreat(1, $victor) < SWUBotUnitBaseThreat(1, $hauler), 'without the proposal Victor Leader looks like the lesser threat');

bot_test_finish();

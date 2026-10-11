<?php
// Feature 'doubleplay' (p42) — HMW_008 General Grievous: "Action [Exhaust]: Play 2 units from your hand (one at a time, paying their
// costs)." Leader audit 2026-10-08, 48 probe games: the Action was a flat W['ability'] (0.40) — every candidate is affordable, so the
// enabled-play pricing credits nothing — and any real play outscored it: on offer 803 times, used 22; 240 decisions held two units
// payable together and it was used in 4. Its "Choose_a_unit_to_play" pick fell to the first-in-hand tiebreak, past every play-side hold:
// 7 of the 22 uses played a second copy of a unique unit already in play (Moff Gideon x4), which the uniqueness rule then defeated.
// Fixed: the Action is worth the two plays it makes (each scored as the free-play stack scores it), and the pick is that same score.
// Fixtures (dictionary-checked): HMW_008 Grievous (Command/Villainy) · HMW_021 Kachirho (Vigilance) · LOF_084 Knight of Ren (3)
//   · LAW_097 Imperial Door Technician (1) · HMW_109 Tireless Magnaguard (4) · ASH_097 Moff Gideon (3, unique) · SOR_095 (their unit).
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php -d apc.enable_cli=1 -d xdebug.mode=off SWUSim/DevTools/tests/bot_doubleplay_test.php
require __DIR__ . '/fixtures/bot_test_bootstrap.php';
foreach (['BotLegalActions', 'Custom/BotLookahead', 'Custom/BotEvaluator', 'Rl/CardTags', 'Custom/BotStyles', 'Custom/BotResourcing',
          'Custom/BotGuides', 'Custom/BotFallback', 'Custom/BotRules', 'BotHeuristic'] as $f) include_once "./SWUSim/$f.php";
$check(SWUBotVariantDisabled('no-doubleplay') === ['doubleplay'], 'doubleplay is switchable');
$check(in_array('doubleplay', SWUBotFeatureGroups()['p42'] ?? [], true), 'doubleplay is in group p42');

$pick = function (string $variant = '') use (&$gameName) {
    $l = SWUBotLegalActions($gameName, 1);
    $p = SWUBotHeuristicChoose('midrange', (array)$l['actions'], $l, $variant);
    return strval($p['cardID'] ?? '');
};
// Grievous's front side ready (his Epic Action spent, so a deploy does not compete), 4 ready resources, $mine in play, $hand in hand.
$board = function (array $hand, array $mine = []) use ($build) {
    $build(function ($b) use ($hand, $mine) {
        $b->MyLeader('HMW_008', true, false, true); $b->MyBase('HMW_021'); $b->FillResourcesForPlayer(1, 'LOF_084', 4); $b->WithCurrentRoundBeing(4);
        foreach ($mine as $c) $b->WithGroundUnitForPlayer(1, $c, false);
        foreach ($hand as $c) $b->WithCardInHandForPlayer(1, $c);
        $b->WithGroundUnitForPlayer(2, 'SOR_095', false);
        for ($k = 0; $k < 10; $k++) { $b->WithCardInDeckForPlayer(1, 'LOF_084'); $b->WithCardInDeckForPlayer(2, 'SOR_095'); }
    });
};
$ability = 'myLeader-0!CustomInput!LeaderAbility';

// A) Knight of Ren (3) + Imperial Door Technician (1) = 4: both fit, so one Action plays both. Today a single play wins.
$board(['LOF_084', 'LAW_097']);
$check($pick('no-doubleplay') !== $ability, 'A fixture: today a single play beats the Action');
$check($pick() === $ability, 'A: two units payable together — Grievous plays both');

// B) Knight of Ren (3) + Tireless Magnaguard (4): only one fits, so the Action is just a play — the bot plays it directly.
$board(['LOF_084', 'HMW_109']);
$check(str_starts_with($pick(), 'myHand-'), 'B: only one unit fits — a direct play, not the Action; got ' . $pick());

// C) The pick: Moff Gideon (unique) is in play; the hand holds a second Gideon FIRST, then a Knight of Ren. Today the first card in
// hand (the duplicate Gideon, defeated at once by the uniqueness rule) is played; fixed, the Knight.
$board(['ASH_097', 'LOF_084'], ['ASH_097']);
$act(1, 10001, $ability);
$check($botCtx('midrange')['tooltip'] === 'Choose_a_unit_to_play', 'C fixture: Grievous\'s play prompt is pending; got ' . $botCtx('midrange')['tooltip']);
$check($pick('no-doubleplay') === 'myHand-0', 'C fixture: today the first card in hand (the duplicate Gideon) is played');
$check($pick() === 'myHand-1', 'C: the Knight of Ren is played, not the duplicate unique');

bot_test_finish();

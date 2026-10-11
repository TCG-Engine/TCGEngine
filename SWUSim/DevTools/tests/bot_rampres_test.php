<?php
// Proposal 'rampres' (2026-10-11): a credit-ramp control seat's resourcing, from the owner's 22 SWU Forge games on Krennic Blue
// Splash v2 against 600 bot games on the same list (memory krennic-v2-swuforge-resourcing-gap-2026-10-11). The bot's opening pick
// banked the cards she wins with — Craving Power / No Glory / Resupply Carrier vs a non-aggro leader, Expendable Mercenary /
// Lawbringer vs an aggro leader — and kept Tireless Magnaguard and Hyperspace Disaster. Owner rulings, same day:
//   1. Lawbringer is an answer to aggressive go-wide strategies (kept vs aggro).
//   2. Resource HSD unless the opponent is known to go wide in space; keep it later vs control as an answer to a big ship.
//   3. Keep removal for late-game bombs; Sentinels matter less vs a control deck.
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php -d apc.enable_cli=1 -d xdebug.mode=off SWUSim/DevTools/tests/bot_rampres_test.php
require __DIR__ . '/fixtures/bot_test_bootstrap.php';
foreach (['BotLegalActions', 'Custom/BotLookahead', 'Custom/BotEvaluator', 'Rl/CardTags', 'Custom/BotStyles', 'Custom/BotResourcing',
          'Custom/BotGuides', 'Custom/BotFallback', 'Custom/BotRules', 'BotHeuristic'] as $f) include_once "./SWUSim/$f.php";
$check(in_array('rampres', SWUBotProposalList(), true) && SWUBotVariantDisabled('try-rampres') === ['try:rampres'], 'rampres is a switchable proposal');

// The v2 list (force-fam-HMW-predictions), main deck only, so the tiered path's "copies left in deck" term is the real one.
$LIST = [];
foreach (file(__DIR__ . '/../../Tests/BotFixtures/force-fam-HMW-predictions/director-krennic_law_blue-splash.txt') as $l) {
    if (trim($l) === 'Sideboard') break;
    if (preg_match('/^(\d+) ([A-Z0-9]+_[A-Z0-9]+)$/', trim($l), $m) && !in_array($m[2], ['LAW_008', 'LAW_021'], true)) for ($k = 0; $k < intval($m[1]); $k++) $LIST[] = $m[2];
}
// $leader on LAW_021 with $res resources and $hand, against $theirLeader / $theirBase.
$board = function (array $hand, int $res, string $theirLeader, string $theirBase, string $leader = 'LAW_008') use ($build, $LIST) {
    $deck = $LIST;
    foreach ($hand as $c) { $k = array_search($c, $deck, true); if ($k !== false) unset($deck[$k]); }
    $build(function ($b) use ($hand, $res, $deck, $theirLeader, $theirBase, $leader) {
        $b->MyLeader($leader, false); $b->MyBase('LAW_021'); $b->TheirLeader($theirLeader, false); $b->TheirBase($theirBase);
        $b->WithCurrentRoundBeing(max(1, $res - 1));
        if ($res > 0) $b->FillResourcesForPlayer(1, 'SOR_095', $res);
        foreach ($hand as $c) $b->WithCardInHandForPlayer(1, $c);
        foreach ($deck as $c) $b->WithCardInDeckForPlayer(1, $c);
    });
};
// The cards resourced (sorted), opening (2) or regroup (1), with or without the proposal.
$picks = function (array $hand, bool $on, bool $opening) use ($botCtx) {
    SWUBotSetDisabledFeatures($on ? ['try:rampres'] : []);
    $ctx = $botCtx('softcontrol');
    if ($opening) $ctx['tooltip'] = 'Choose_2_cards_to_resource';
    $out = array_map(fn($mz) => $hand[intval(substr($mz, 7))] ?? '?', SWUBotChooseResourceCards($ctx, $opening ? 2 : 1));
    SWUBotSetDisabledFeatures([]);
    sort($out);
    return $out;
};
$show = fn(array $a) => '[' . implode(',', $a) . ']';

// ── Ruling 3, opening vs a NON-aggro leader (Doctor Hemlock HMW_003): answers and the ramp piece stay, filler goes ───────────
// Craving Power, No Glory, Resupply Carrier, Tireless Magnaguard, Moff Gideon, Lepi Lookout.
$H = ['LOF_091', 'JTL_043', 'JTL_119', 'HMW_109', 'ASH_097', 'LAW_038'];
$board($H, 0, 'HMW_003', 'HMW_032');
$base = $picks($H, false, true); $on = $picks($H, true, true);
$check(array_intersect($base, ['LOF_091', 'JTL_043', 'JTL_119']) !== [], 'premise vs Hemlock: today an answer or the Carrier is resourced at the opening; got ' . $show($base));
$check(array_intersect($on, ['LOF_091', 'JTL_043', 'JTL_119']) === [], '@try-rampres vs Hemlock: Craving / No Glory / Carrier all stay in hand; got ' . $show($on));

// ── Rulings 1 + 2, opening vs an AGGRO leader (Ahsoka Blue ASH_009, a ground deck): HSD goes, Mercenary + Lawbringer stay ───
// Expendable Mercenary, Latts Razzi, Lawbringer, Craving Power, Hyperspace Disaster, Moff Gideon.
$A = ['LAW_159', 'LAW_039', 'LAW_101', 'LOF_091', 'SEC_078', 'ASH_097'];
$board($A, 0, 'ASH_009', 'ASH_019');
$base = $picks($A, false, true); $on = $picks($A, true, true);
$check(!in_array('SEC_078', $base, true) && array_intersect($base, ['LAW_159', 'LAW_101']) !== [], 'premise vs Ahsoka: today HSD is kept and Mercenary or Lawbringer is resourced; got ' . $show($base));
$check(in_array('SEC_078', $on, true), '@try-rampres vs Ahsoka: Hyperspace Disaster is resourced (no space deck to hit); got ' . $show($on));
$check(array_intersect($on, ['LAW_159', 'LAW_101']) === [], '@try-rampres vs Ahsoka: Mercenary and Lawbringer stay; got ' . $show($on));

// ── Ruling 2, vs a SPACE deck (Darth Vader JTL_006, flavour 'space'): HSD is kept ──────────────────────────────────────────────
$board($A, 0, 'JTL_006', 'ASH_026');
$on = $picks($A, true, true);
$check(!in_array('SEC_078', $on, true), '@try-rampres vs Vader (space): Hyperspace Disaster stays; got ' . $show($on));

// …and vs a space deck whose leader is NOT on the aggro list (Luke JTL_012, plain path) — there, only 'rampres' itself reads the flavour.
$board($A, 0, 'JTL_012', 'JTL_024');
$check(!in_array('SEC_078', $picks($A, true, true), true), '@try-rampres vs Luke JTL (space, plain path): Hyperspace Disaster stays; got ' . $show($picks($A, true, true)));

// ── Ruling 2, later vs a non-aggro deck: from 6 resources HSD is the answer to a big ship; at 3 it is still resourced ─────────
// Hyperspace Disaster, Tireless Magnaguard, Ant Droid, Imperial Armored Commando.
$L = ['SEC_078', 'HMW_109', 'ASH_116', 'ASH_048'];
$board($L, 3, 'HMW_003', 'HMW_032');
$check($picks($L, true, false) === ['SEC_078'], '@try-rampres vs Hemlock at 3 resources: HSD is resourced; got ' . $show($picks($L, true, false)));
$board($L, 6, 'HMW_003', 'HMW_032');
$check($picks($L, true, false) !== ['SEC_078'], '@try-rampres vs Hemlock at 6 resources: HSD is kept; got ' . $show($picks($L, true, false)));

// ── Ruling 3's Sentinel half, vs a non-aggro deck: Moff Gideon loses the control Sentinel keep, so it goes before Magnaguard ──
// Moff Gideon, Tireless Magnaguard, and Single Reactor Ignition as the hand's one bomb (with two cards, Magnaguard WOULD be the bomb).
$S = ['ASH_097', 'HMW_109', 'LAW_044'];
$board($S, 3, 'HMW_003', 'HMW_032');
$check($picks($S, false, false) === ['HMW_109'], 'premise vs Hemlock: today Magnaguard is resourced and the Sentinel kept; got ' . $show($picks($S, false, false)));
$check($picks($S, true, false) === ['ASH_097'], '@try-rampres vs Hemlock: the Sentinel goes first; got ' . $show($picks($S, true, false)));
// …but vs an aggro leader the Sentinel wall stays (p39 'wallkeep').
$board($S, 3, 'ASH_009', 'ASH_019');
$check($picks($S, true, false) === ['HMW_109'], '@try-rampres vs Ahsoka: the Sentinel stays; got ' . $show($picks($S, true, false)));

// ── Ruling 1 on the plain path: a go-wide deck whose leader is not on the aggro list (Obi-Wan LOF_008, Vergence Temple, 'go-wide') ──
// Lawbringer, Single Reactor Ignition (the hand's bomb), Tireless Magnaguard, Ant Droid, Lepi Lookout, Onyx Squadron Brute.
$W = ['LAW_101', 'LAW_044', 'HMW_109', 'ASH_116', 'LAW_038', 'JTL_033'];
$board($W, 0, 'LOF_008', 'LOF_019');
$check(in_array('LAW_101', $picks($W, false, true), true), 'premise vs Obi-Wan: today Lawbringer is resourced at the opening; got ' . $show($picks($W, false, true)));
$check(!in_array('LAW_101', $picks($W, true, true), true), '@try-rampres vs Obi-Wan (go-wide): Lawbringer stays; got ' . $show($picks($W, true, true)));
$board($W, 0, 'HMW_003', 'HMW_032');
$check(in_array('LAW_101', $picks($W, true, true), true), '@try-rampres vs Hemlock (not go-wide): Lawbringer is still resourced; got ' . $show($picks($W, true, true)));

// ── The tiered path's cheap cap: a credit-ramp seat counts a 3-cost as cheap at the opening, so Latts Razzi stays vs Ahsoka ─────
$board($A, 0, 'ASH_009', 'ASH_019');
$check(!in_array('LAW_039', $picks($A, true, true), true), '@try-rampres vs Ahsoka: Latts Razzi (3) stays as a cheap play; got ' . $show($picks($A, true, true)));

// ── The tiered path's answers: Craving Power (a power-strike) is an answer, so filler goes before it ─────────────────────────────
// Craving Power, Condemn (one copy, not an answer), Moff Gideon (kept by the Sentinel wall) vs Ahsoka. At 0 resources the cheap cap is 1
// (2 with 'rampres'), so neither 3+ cost card is cheap in either arm and only the answer test can move the pick.
$C = ['LOF_091', 'SEC_038', 'ASH_097'];
$board($C, 0, 'ASH_009', 'ASH_019');
$check($picks($C, false, false) === ['LOF_091'], 'premise vs Ahsoka: today Craving Power is resourced over Condemn; got ' . $show($picks($C, false, false)));
$check($picks($C, true, false) === ['SEC_038'], '@try-rampres vs Ahsoka: Condemn is resourced, Craving Power stays; got ' . $show($picks($C, true, false)));

// ── Scope: a seat that is not credit-ramp (Thrawn JTL_002) is untouched ─────────────────────────────────────────────────────
$board($H, 0, 'HMW_003', 'HMW_032', 'JTL_002');
$check($picks($H, true, true) === $picks($H, false, true), '@try-rampres: a non-credit-ramp leader picks the same; got ' . $show($picks($H, true, true)));
$board($A, 0, 'ASH_009', 'ASH_019', 'JTL_002');
$check($picks($A, true, true) === $picks($A, false, true), '@try-rampres: a non-credit-ramp leader picks the same vs aggro; got ' . $show($picks($A, true, true)));

bot_test_finish();

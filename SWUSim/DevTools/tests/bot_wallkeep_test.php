<?php
// Feature 'wallkeep' (p39) — against an aggro leader, control never resources its WALL, and against SPACE aggro it keeps Lawbringer.
// Owner, Krennic (LAW) Blue questionnaire 2026-10-06: vs aggro the deck stabilises with "Sentinel wall + trades" (Moff Gideon, Imperial
// Armored Commando, Koska with a token); early resourcing vs aggro is "Ravager / late bombs, extra Pre Vizsla, off-matchup removal. Never
// a Sentinel"; vs Darth Vader (JTL) Yellow, "Lawbringer on Aggression" (Splash: "Lawbringer as the engine").
// Traced (480 Krennic Blue games, baseline kb1): the resourcing3 tiers have no Sentinel keep (the shipped p4 'sentinelkeep' +50 sits on
// the other path), so a Sentinel went to resources whenever it was not the cheapest-to-keep: Gideon/Commando 26 times and Koska ~45 vs
// aggro, single copies included (ahsoka-yellow s007 R3, boba-blue s010 R4). Lawbringer (8) sits in the 7+ "resource first" tier: cast
// ONCE in 40 games vs Vader Yellow.
//   · tier path, vs an aggro leader: a printed Sentinel, or a unit that gains Sentinel (Koska), is kept (tier 9) — a duplicate unique
//     copy still goes first (tier 0, the owner's 2026-09-18 ruling: "two of the same unique unit Sentinel … safe to resource one");
//   · vs space aggro: a Lawbringer-style "each enemy unit with that aspect -N/-N" card is kept with the space wipes.
// Fixtures (dictionary-checked): LAW_008 Director Krennic (leader) · ASH_019 Fortress of the Great Mothers · ASH_097 Moff Gideon ·
//   ASH_079 Koska Reeves · ASH_052 Chimaera · JTL_043 No Glory, Only Results · SEC_078 Hyperspace Disaster · LAW_097 Imperial Door
//   Technician · ASH_116 Ant Droid · LAW_159 Expendable Mercenary · JTL_033 Onyx Squadron Brute · LAW_101 Lawbringer · ASH_102 Ravager ·
//   LAW_039 Latts Razzi · LOF_091 Craving Power · ASH_009 Ahsoka Tano + SOR_030 (Ahsoka Yellow) · JTL_009 Boba Fett (Boba Blue) ·
//   JTL_006 Darth Vader + ASH_026 (Vader Yellow, space aggro) · HMW_003 Doctor Hemlock (not aggro)
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php -d apc.enable_cli=1 -d xdebug.mode=off SWUSim/DevTools/tests/bot_wallkeep_test.php
require __DIR__ . '/fixtures/bot_test_bootstrap.php';
foreach (['BotLegalActions', 'Custom/BotLookahead', 'Custom/BotEvaluator', 'Rl/CardTags', 'Custom/BotStyles', 'Custom/BotResourcing',
          'Custom/BotGuides', 'Custom/BotFallback', 'Custom/BotRules', 'BotHeuristic'] as $f) include_once "./SWUSim/$f.php";
$check(SWUBotVariantDisabled('no-wallkeep') === ['wallkeep'], 'wallkeep is switchable');
$check(in_array('wallkeep', SWUBotFeatureGroups()['p39'] ?? [], true), 'wallkeep is in group p39');
// The real Krennic Blue main deck (melee 438966 #1): the keep score counts the copies left in the deck (a 3-of is cheaper to resource).
$LIST = [];
foreach (file(__DIR__ . '/../../Tests/BotFixtures/ash-meta-2026-09/director-krennic_law_blue.txt') as $l) {
    if (trim($l) === 'Sideboard') break;
    if (preg_match('/^(\d+) ([A-Z0-9]+_[A-Z0-9]+)$/', trim($l), $m) && !in_array($m[2], ['LAW_008', 'ASH_019'], true)) for ($k = 0; $k < intval($m[1]); $k++) $LIST[] = $m[2];
}
$check(count($LIST) === 50, 'fixture: the Krennic Blue main deck has 50 cards; got ' . count($LIST));
// Krennic Blue in round $rnd with round+1 resources and $hand, the rest of the list as its deck; their $leader on $base, no units.
$board = function (array $hand, string $leader, string $base, int $rnd) use ($build, $LIST) {
    $deck = $LIST;
    foreach ($hand as $c) { $k = array_search($c, $deck, true); if ($k !== false) unset($deck[$k]); }
    $build(function ($b) use ($hand, $leader, $base, $rnd, $deck) {
        $b->MyLeader('LAW_008', false); $b->MyBase('ASH_019'); $b->TheirLeader($leader, false); $b->TheirBase($base);
        $b->WithCurrentRoundBeing($rnd); $b->FillResourcesForPlayer(1, 'LAW_097', $rnd + 1);
        foreach ($hand as $c) $b->WithCardInHandForPlayer(1, $c);
        foreach ($deck as $c) $b->WithCardInDeckForPlayer(1, $c);
    });
};
$pick = function (array $hand, bool $on) use ($botCtx) {
    SWUBotSetDisabledFeatures($on ? [] : ['wallkeep']);
    $r = SWUBotChooseResourceCards($botCtx('softcontrol'), 1); SWUBotSetDisabledFeatures([]);
    return $hand[intval(substr($r[0] ?? 'myHand-99', 7))] ?? '?';
};
// A) R1 regroup vs Ahsoka Yellow: Imperial Armored Commando (4) is not a "cheap" keep at 2 resources, so it was the card resourced.
// (The traced single-copy cases — ahsoka-yellow s007 R3, boba-blue s010 R4 — depend on that game's board and deck; these hands
// show the same tier-2 gap on a clean board.)
$A = ['ASH_048', 'JTL_043', 'ASH_052', 'ASH_116', 'LAW_039', 'LOF_059'];
$board($A, 'ASH_009', 'SOR_030', 1);
$check($pick($A, false) === 'ASH_048', 'A fixture: today the Commando is resourced; got ' . $pick($A, false));
$check($pick($A, true) !== 'ASH_048', 'A: the Commando stays in hand; got ' . $pick($A, true));
// B) The same vs Boba Blue.
$board($A, 'JTL_009', 'ASH_019', 1);
$check($pick($A, false) === 'ASH_048', 'B fixture: today the Commando is resourced vs Boba; got ' . $pick($A, false));
$check($pick($A, true) !== 'ASH_048', 'B: the Commando stays in hand; got ' . $pick($A, true));
// C) Koska (gains Sentinel with a token) is part of the wall too.
$C = ['ASH_079', 'JTL_043', 'ASH_052', 'ASH_116', 'LAW_039', 'LOF_059'];
$board($C, 'ASH_009', 'SOR_030', 1);
$check($pick($C, false) === 'ASH_079', 'C fixture: today Koska is resourced; got ' . $pick($C, false));
$check($pick($C, true) !== 'ASH_079', 'C: Koska stays in hand; got ' . $pick($C, true));
// D) A spare duplicate of a unique Sentinel may still go (the 2026-09-18 ruling).
$D = ['ASH_097', 'ASH_097', 'JTL_043', 'ASH_052', 'ASH_116', 'LAW_039'];
$board($D, 'ASH_009', 'SOR_030', 1);
$check($pick($D, true) === 'ASH_097', 'D: the second Moff Gideon may be resourced; got ' . $pick($D, true));
// E) vs Vader (JTL) Yellow, space aggro: Lawbringer was the FIRST card resourced; now Ravager goes and Lawbringer stays.
$E = ['LAW_101', 'ASH_102', 'ASH_097', 'LAW_039', 'LOF_091'];
$board($E, 'JTL_006', 'ASH_026', 3);
$check($pick($E, false) === 'LAW_101', 'E fixture: today Lawbringer is resourced vs Vader; got ' . $pick($E, false));
$check($pick($E, true) === 'ASH_102', 'E: Ravager (a late bomb) goes, Lawbringer stays; got ' . $pick($E, true));
// F) vs ground aggro (Ahsoka) Lawbringer is a late bomb like any other — unchanged.
$board($E, 'ASH_009', 'SOR_030', 3);
$check($pick($E, true) === $pick($E, false), 'F: vs Ahsoka — unchanged; got ' . $pick($E, true) . ' vs ' . $pick($E, false));
// G) Not an aggro leader (Hemlock): unchanged.
$board($A, 'HMW_003', 'SOR_030', 1);
$check($pick($A, true) === $pick($A, false), 'G: vs a non-aggro leader — unchanged; got ' . $pick($A, true) . ' vs ' . $pick($A, false));

bot_test_finish();

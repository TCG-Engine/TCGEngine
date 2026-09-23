<?php
// Phase 1a Task 6 — resourcing engine v0 (SWUSim/Custom/BotResourcing.php): the floor, the per-style card
// choice and the Plot budget. Fixture costs read from the dictionary before use:
//   SOR_009 Leia Organa leader, deploy threshold 5 — set explicitly (the bootstrap default is SOR_014 Sabine, 4)
//   SOR_095 Battlefield Marine 2 · SOR_046 Consular Security Force 4 · SOR_164 Wampa 4 · LOF_084 Knight of Ren 3
//   Plot cards ($Plot_Cards): SEC_082 Chancellor Palpatine 3 · SEC_111 Jar Jar Binks 2 · SEC_149 Kaydel Connix 3
//   — the owner's shape: Palpatine (3) + Jar Jar (2) Plotted out of resources for 5 total on a 5-threshold deploy.
// Non-standard deploys, from SWUDeployLeader() / SWUComputeActionsData() in Custom/GameLogic.php:
//   JTL_014 Admiral Trench — 6 resources, repeatable (no Epic) · SEC_008 Bail Organa — 4, repeatable
//   LAW_013 Chewbacca — a real [4 resources] cost · LOF_007 Avar Kriss — resources + Force uses this phase ≥ 9
//   ASH_010 Bo-Katan — resources + friendly Mandalorian units ≥ 10 · ASH_018 Grogu — trigger only, no threshold
//   TWI_017 Chancellor Palpatine ("Flipatine") — never deploys
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php -d apc.enable_cli=1 -d xdebug.mode=off SWUSim/DevTools/tests/bot_resourcing_test.php
require __DIR__ . '/fixtures/bot_test_bootstrap.php';
include_once './SWUSim/BotLegalActions.php';
include_once './SWUSim/Custom/BotEvaluator.php';
include_once './SWUSim/Custom/BotStyles.php';
include_once './SWUSim/Custom/BotFeatures.php';
include_once './SWUSim/Rl/CardTags.php';
include_once './SWUSim/Custom/BotFlavours.php';
include_once './SWUSim/Custom/BotResourcing.php';
$hand = function ($b, array $cards) { foreach ($cards as $c) $b->WithCardInHandForPlayer(1, $c); };
$pick = fn(string $style, int $n = 1) => SWUBotChooseResourceCards(['style' => $style, 'seat' => 1], $n);

// ── The floor: resource until the leader can deploy ──────────────────────────────────────────────
$build(function ($b) { $b->MyLeader('SOR_009'); $b->FillResourcesForPlayer(1, 'SOR_095', 4); });
$check(SWUBotLeaderDeployThreshold(1) === 5, 'Leia: the threshold is her printed cost (5)');
$check(SWUBotResourceFloorApplies(1) === true, '4 resources < 5 → the floor applies');
$build(function ($b) { $b->MyLeader('SOR_009'); $b->FillResourcesForPlayer(1, 'SOR_095', 5); });
$check(SWUBotResourceFloorApplies(1) === false, '5 resources = the threshold → the floor stops');
$build(function ($b) { $b->MyLeader('SOR_009', true, true, true, 'unit'); $b->FillResourcesForPlayer(1, 'SOR_095', 2); });
$check(SWUBotLeaderDeployThreshold(1) === 0 && SWUBotResourceFloorApplies(1) === false, 'a deployed leader → no floor');
$build(function ($b) { $b->MyLeader('SOR_009', true, false, true); $b->FillResourcesForPlayer(1, 'SOR_095', 2); });
$check(SWUBotLeaderDeployThreshold(1) === 0, 'Epic Action spent and back in leader form (defeated) → cannot redeploy → no floor');

// ── Non-standard deploys ─────────────────────────────────────────────────────────────────────────
$build(function ($b) { $b->MyLeader('JTL_014'); });
$check(SWUBotLeaderDeployThreshold(1) === 6, 'Admiral Trench: 6');
$build(function ($b) { $b->MyLeader('JTL_014', true, false, true); });
$check(SWUBotLeaderDeployThreshold(1) === 6, 'Admiral Trench redeploys without the Epic Action: still 6 after it is spent');
$build(function ($b) { $b->MyLeader('SEC_008', true, false, true); });
$check(SWUBotLeaderDeployThreshold(1) === 4, 'Bail Organa: 4, repeatable');
$build(function ($b) { $b->MyLeader('LAW_013'); });
$check(SWUBotLeaderDeployThreshold(1) === 4, 'Chewbacca: the [4 resources] cost');
$build(function ($b) { $b->MyLeader('LOF_007'); });
$check(SWUBotLeaderDeployThreshold(1) === 9, 'Avar Kriss: 9 with no Force used this phase');
$build(function ($b) { $b->MyLeader('ASH_010'); });
$check(SWUBotLeaderDeployThreshold(1) === 10, 'Bo-Katan: 10 with no Mandalorian units');
$build(function ($b) { $b->MyLeader('ASH_010'); $b->WithGroundUnitForPlayer(1, 'SOR_095', true); });
$check(SWUBotLeaderDeployThreshold(1) === 10, 'Bo-Katan: a non-Mandalorian unit does not count');
$build(function ($b) { $b->MyLeader('ASH_010'); $b->WithGroundUnitForPlayer(1, 'SHD_040', true); $b->WithGroundUnitForPlayer(1, 'SHD_047', true); });
$check(SWUBotLeaderDeployThreshold(1) === 8, 'Bo-Katan: two Mandalorian units (SHD_040, SHD_047) lower it to 8');
$build(function ($b) { $b->MyLeader('ASH_018'); });
$check(SWUBotLeaderDeployThreshold(1) === 0, 'Grogu: deploys by trigger only → no floor');
$build(function ($b) { $b->MyLeader('TWI_017'); });
$check(SWUBotLeaderDeployThreshold(1) === 0, 'Flipatine: never deploys → no floor');

// ── Card choice per style ────────────────────────────────────────────────────────────────────────
$build(function ($b) use ($hand) { $hand($b, ['SOR_095', 'SOR_046', 'LOF_084']); });   // costs 2, 4, 3
$check($pick('aggro') === ['myHand-1'], 'Aggro resources its most expensive card');
$check($pick('control') === ['myHand-0'], 'Control resources its cheapest card (keeps its bombs)');
$check($pick('normal') === ['myHand-1'], 'Normal: the fallback default is the most expensive');
$check($pick('control', 2) === ['myHand-0', 'myHand-2'], 'two picks, lowest keep value first');
$build(function ($b) use ($hand) { $hand($b, ['SOR_046', 'SOR_095', 'SOR_164']); });   // costs 4, 2, 4
$check($pick('aggro') === ['myHand-0'], 'ties go to the lowest hand index');

// ── Control keeps a hand it can cast (owner, 2026-09-13) ─────────────────────────────────────────────
// The real opening that lost every game (fixture krennic_splash, seed s01): LAW_159 (4), ASH_133 (8),
// SEC_078 (7), SEC_087 (6), ASH_053 (8), ASH_053 (8). v0 resourced the 4 and the 6 and could cast nothing for
// three rounds. Now: 2 resources after the pick → castable soon = cost ≤ 4. The 4 and one bomb (the first
// 8) are kept. Since tags v2 (owner OK 2026-09-14) Pre Vizsla and Hyperspace Disaster are wipes, and key cards go
// to resources after far filler: Dedra Meero (6, filler) and ONE Pre Vizsla are resourced; the other copy stays.
$build(function ($b) use ($hand) { $hand($b, ['LAW_159', 'ASH_133', 'SEC_078', 'SEC_087', 'ASH_053', 'ASH_053']); });
$check($pick('control', 2) === ['myHand-3', 'myHand-4'], 'Control\'s opening: keep the 4 and one bomb; resource the far filler and one extra Pre Vizsla');
// A regroup: 3 resources, hand 8 / 8 / 7 / 2 → castable soon = cost ≤ 6. The just-drawn 2 is kept; the
// resourced card is the one furthest from castable other than the bomb (the second 8).
$build(function ($b) use ($hand) { $b->FillResourcesForPlayer(1, 'SOR_095', 3); $hand($b, ['ASH_133', 'ASH_053', 'SEC_078', 'LOF_059']); });
$check($pick('control') === ['myHand-1'], 'Control\'s regroup: keep the cheap card it can cast, resource a second bomb');

// ── The Plot budget ──────────────────────────────────────────────────────────────────────────────
$build(function ($b) use ($hand) { $b->MyLeader('SOR_009'); $hand($b, ['SOR_095', 'SEC_082', 'SOR_046']); });   // 2, Plot 3, 4
$check($pick('control') === ['myHand-1'] && $pick('aggro') === ['myHand-1'],
    'a Plot card that fits the budget (3 ≤ 5) is resourced first, whatever the style');
$build(function ($b) use ($hand) {
    $b->MyLeader('SOR_009'); $b->FillResourcesForPlayer(1, 'SEC_082', 1);   // Palpatine (Plot 3) already resourced
    $hand($b, ['SEC_149', 'SOR_046']);                     // Kaydel (Plot 3) · 4
});
$check($pick('aggro') === ['myHand-1'], 'a second Plot card over the budget (3 + 3 > 5) gets no priority — reads Plot on the RESOURCE');
$build(function ($b) use ($hand) {
    $b->MyLeader('SOR_009'); $b->FillResourcesForPlayer(1, 'SEC_082', 1);   // Palpatine (Plot 3) already resourced
    $hand($b, ['SOR_046', 'SEC_111']);                     // 4 · Jar Jar (Plot 2)
});
$check($pick('aggro') === ['myHand-1'], 'the owner\'s example: Palpatine + Jar Jar = 5 fits the budget → Jar Jar first');
$build(function ($b) use ($hand) { $b->MyLeader('SOR_009', true, true, true, 'unit'); $hand($b, ['SEC_111', 'SOR_046']); });
$check($pick('aggro') === ['myHand-1'], 'once the leader is deployed, Plot has no budget → no priority');

// ── Key cards stay out of resources (Phase 1b part 2, Task 6, feature 'keep'; owner ruling 2026-09-14 — the
// default improves, training still learns past it). Diagnosis: Piett red resourced 5.9 Capital Ships a game.
$build(function ($b) use ($hand) { $b->MyLeader('JTL_005', false); $b->MyBase('LAW_027'); $b->FillResourcesForPlayer(1, 'SOR_095', 5); $hand($b, ['JTL_143', 'ASH_099', 'SEC_110', 'SOR_095']); });
$check($pick('normal', 2) === ['myHand-2', 'myHand-3'], 'Piett red resources the GNK and the Marine, keeps Devastator and the Gozanti');
SWUBotSetDisabledFeatures(['keep']);
$check($pick('normal', 2) === ['myHand-0', 'myHand-1'], '@no-keep: the old pick, both Capital Ships');
SWUBotSetDisabledFeatures([]);
// Krennic's wipe vs far filler: both 8s are far from castable at 2 resources; the answer stays, Trask Walker (heal
// only) goes. Control's castable-soon rule still comes first (the Marine is kept either way).
$build(function ($b) use ($hand) { $b->MyLeader('LAW_008', false); $b->FillResourcesForPlayer(1, 'SOR_095', 2); $hand($b, ['JTL_041', 'LAW_044', 'ASH_133', 'SOR_095']); });
$check($pick('control') === ['myHand-2'], 'Control keeps Single Reactor Ignition over far filler: Trask Walker goes');
// ⚠ 'wipekeep' must go too (owner-approved 2026-09-17). This check asserts the raw INDEX tie-break between two
// equally-far 8s, so it only means anything while nothing is scoring the wipe above the other card — and
// wipekeep (added 2026-09-16) gives Single Reactor Ignition a protected-wipe keep at 2 resources, which is the
// behaviour the check one line above now covers.
SWUBotSetDisabledFeatures(['keep', 'wipekeep']);
$check($pick('control') === ['myHand-1'], '@no-keep: the tie between the two 8s went to the wipe (lower index)');
SWUBotSetDisabledFeatures([]);
// Boba's burn.
$build(function ($b) use ($hand) { $b->MyLeader('JTL_009', false); $b->MyBase('JTL_031'); $b->FillResourcesForPlayer(1, 'SOR_095', 4); $hand($b, ['JTL_143', 'JTL_240', 'SOR_095']); });
$check($pick('aggro') === ['myHand-2'], 'burn: Devastator and Firespray stay; the Marine goes');

bot_test_finish();

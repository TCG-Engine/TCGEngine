<?php
// LAW_013 Chewbacca, Hero of Kessel — "Epic Action [4 resources]: Deploy this leader." The only leader whose deploy is
// a paid COST (not a "control N resources" threshold). SWUDeployLeader pays it from payment CAPACITY (ready resources +
// Credit tokens, CR 3.13) and refuses when it can't; but SWUComputeActionsData — the Deploy glow the client lights and
// the bot's legal-action list reads — used the generic "control ≥ printed cost" check. With 4 resources of which only 2
// were ready, Deploy glowed, the click did nothing, and the bot re-picked it forever. FOUND 2026-09-15 by the fixture
// smoke (aggro_chewbacca_outpost: 2 games with no result, "DeployLeader:Unit" repeated at round 3, capacity 2).
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php -d apc.enable_cli=1 -d xdebug.mode=off SWUSim/DevTools/tests/chewbacca_deploy_offer_test.php
require __DIR__ . '/fixtures/bot_test_bootstrap.php';
include_once './SWUSim/BotLegalActions.php';
$deployOffered = fn() => !empty((SWUComputeActionsData(1)['leaderDeployByIndex'] ?? [])[0]);

// 4 resources, only 2 ready: capacity 2 < 4 — no Deploy.
$build(function ($b) { $b->MyLeader('LAW_013'); $b->FillResourcesForPlayer(1, 'SOR_095', 4); });
foreach (GetResources(1) as $i => $r) { if ($i >= 2) $r->Status = 0; }   // exhaust 2 of the 4
$check(SWUResourceCount(1) === 4 && SWUTotalPaymentCapacity(1) === 2, 'fixture: 4 resources, capacity 2');
$check(!$deployOffered(), 'Deploy is not offered when the 4-resource cost cannot be paid');

// 4 ready resources: Deploy is offered.
$build(function ($b) { $b->MyLeader('LAW_013'); $b->FillResourcesForPlayer(1, 'SOR_095', 4); });
$check(SWUTotalPaymentCapacity(1) >= 4 && $deployOffered(), 'Deploy is offered with 4 ready resources');

// 3 ready resources and a Credit token (capacity 4): offered — the Credit pays too.
$build(function ($b) { $b->MyLeader('LAW_013'); $b->FillResourcesForPlayer(1, 'SOR_095', 3); });
SWUCreateCreditToken(1, 1, false);
$check(SWUResourceCount(1) === 3 && SWUTotalPaymentCapacity(1) === 4, 'fixture: 3 resources + a Credit, capacity 4');
$check($deployOffered(), 'Deploy is offered when a Credit brings capacity to 4 (3 resources controlled)');

// The Epic Action already used: never offered.
$build(function ($b) { $b->MyLeader('LAW_013', true, false, true); $b->FillResourcesForPlayer(1, 'SOR_095', 6); });
$check(!$deployOffered(), 'a spent Epic Action is never offered');

bot_test_finish();

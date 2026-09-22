<?php
// The Deploy OFFER and the Deploy GATE must agree — for every leader whose deploy condition is not plain
// "control N resources".
// Bug Report #1069 (game 1109600, Twin Suns): "P2 deploy for bo-katan not working. they do have 6R + 4 Mandos on
// board". ASH_010 Bo-Katan Kryze — "Epic Action: If the number of resources you control plus the number of friendly
// Mandalorian units is 10 or more, deploy this leader." SWUDeployLeader() implements exactly that and WOULD have
// deployed her (measured on the live game: 6 resources + 4 Mandalorian units = 10, Epic unused, leader ready).
// SWUComputeActionsData() — the Deploy glow the client lights, and the bot's legal-action list — carried its own
// copy of the condition with branches for JTL_014 / SEC_008 / TWI_017 / LAW_013 only, so Bo-Katan fell through to
// the generic "resources >= printed cost" = 10 RESOURCES and the option was never shown.
// The two copies had drifted THREE ways:
//   · ASH_010 Bo-Katan   — offer hidden though the deploy is legal (the report);
//   · LOF_007 Avar Kriss — same shape: her Force uses count toward the threshold, the offer ignored them;
//   · ASH_018 Grogu      — the opposite: he has NO Epic Action (he deploys only from his own "play a unique unit
//     costing 4+" trigger), but the generic branch OFFERED Deploy at 4 resources, and SWUDeployLeader's ASH_018
//     branch requires only that he is ready — so the click deployed him for free.
// Both sites now read one predicate, SWULeaderDeployThresholdMet() (Custom/GameLogic.php).
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php -d apc.enable_cli=1 -d xdebug.mode=off SWUSim/DevTools/tests/leader_deploy_offer_conditions_test.php
require __DIR__ . '/fixtures/bot_test_bootstrap.php';
include_once './SWUSim/BotLegalActions.php';
$deployOffered = fn(int $idx = 0) => !empty((SWUComputeActionsData(1)['leaderDeployByIndex'] ?? [])[$idx]);
$deployed = function (int $idx = 0) {
    $ls = GetLeader(1);
    return !empty($ls[$idx]->Deployed);
};

// ── ASH_010 Bo-Katan Kryze — resources + friendly Mandalorian units >= 10 ───────────────────────────
// THE REPORTED BOARD, in miniature: 6 resources and 4 Mandalorian units (ASH_249 Covert Veteran x3 plus
// ASH_154 Honorable Nite Owl — all Mandalorian, like the live game's).
$boKatan = function (int $res, int $mandos) use ($build) {
    $build(function ($b) use ($res, $mandos) {
        $b->MyLeader('ASH_010');
        $b->FillResourcesForPlayer(1, 'SOR_095', $res);
        for ($i = 0; $i < $mandos; $i++) $b->WithGroundUnitForPlayer(1, 'ASH_249', true);
    });
};
$boKatan(6, 4);
$check(SWUResourceCount(1) === 6 && count(GetUnitsInPlay(1)) === 4, 'fixture: 6 resources + 4 Mandalorian units');
$check($deployOffered(), 'Bo-Katan: Deploy IS offered at 6 resources + 4 Mandalorians (the report)');
SWUDeployLeader(1, 'Unit', '', 0);
$check($deployed(), 'Bo-Katan: and the deploy goes through');

// One short of the threshold: 6 + 3 = 9 — not offered, and the gate refuses it too.
$boKatan(6, 3);
$check(!$deployOffered(), 'Bo-Katan: 6 + 3 = 9 is not offered');
SWUDeployLeader(1, 'Unit', '', 0);
$check(!$deployed(), 'Bo-Katan: and 9 cannot be deployed');

// Non-Mandalorian units do not count: 6 resources + 4 non-Mandalorians (LOF_093 Gungi).
$build(function ($b) {
    $b->MyLeader('ASH_010');
    $b->FillResourcesForPlayer(1, 'SOR_095', 6);
    for ($i = 0; $i < 4; $i++) $b->WithGroundUnitForPlayer(1, 'LOF_093', true);
});
$check(!$deployOffered(), 'Bo-Katan: non-Mandalorian units do not count toward the 10');

// 10 resources and no units at all — the plain reading still works.
$boKatan(10, 0);
$check($deployOffered(), 'Bo-Katan: 10 resources alone is enough');

// A spent Epic Action is never offered, whatever the board.
$build(function ($b) {
    $b->MyLeader('ASH_010', true, false, true);
    $b->FillResourcesForPlayer(1, 'SOR_095', 6);
    for ($i = 0; $i < 4; $i++) $b->WithGroundUnitForPlayer(1, 'ASH_249', true);
});
$check(!$deployOffered(), 'Bo-Katan: a spent Epic Action is never offered');

// ── LOF_007 Avar Kriss — resources + Force uses this phase >= 9 ─────────────────────────────────────
$build(function ($b) { $b->MyLeader('LOF_007'); $b->FillResourcesForPlayer(1, 'SOR_095', 7); });
$check(!$deployOffered(), 'Avar Kriss: 7 resources and no Force uses is not offered');
for ($i = 0; $i < 2; $i++) AddGlobalEffects(1, 'SWU_FORCE_USED_THIS_PHASE');
$check(GlobalEffectCount(1, 'SWU_FORCE_USED_THIS_PHASE') === 2, 'fixture: the Force was used twice this phase');
$check($deployOffered(), 'Avar Kriss: 7 resources + 2 Force uses IS offered (they count toward the 9)');

// ── ASH_018 Grogu — no Epic Action at all; he deploys only from his own trigger ──────────────────────
$build(function ($b) { $b->MyLeader('ASH_018'); $b->FillResourcesForPlayer(1, 'SOR_095', 6); });
$check(!$deployOffered(), 'Grogu: Deploy is never offered — he has no Epic Action (printed cost 4)');

// ── The leaders that already had their own branch keep working ──────────────────────────────────────
$build(function ($b) { $b->MyLeader('LAW_013'); $b->FillResourcesForPlayer(1, 'SOR_095', 4); });
foreach (GetResources(1) as $i => $r) { if ($i >= 2) $r->Status = 0; }
$check(!$deployOffered(), 'Chewbacca: the 4-resource COST still needs payment capacity');
$build(function ($b) { $b->MyLeader('JTL_014'); $b->FillResourcesForPlayer(1, 'SOR_095', 6); });
$check($deployOffered(), 'Trench: 6 resources, 3 of them ready, is still offered');
$build(function ($b) { $b->MyLeader('SEC_008'); $b->FillResourcesForPlayer(1, 'SOR_095', 4); });
$check(!$deployOffered(), 'Bail Organa: 4 resources but no cards to discard is still not offered');
$build(function ($b) {
    $b->MyLeader('SEC_008');
    $b->FillResourcesForPlayer(1, 'SOR_095', 4);
    $b->WithCardInHandForPlayer(1, 'SOR_095');
    $b->WithCardInHandForPlayer(1, 'SOR_095');
});
$check($deployOffered(), 'Bail Organa: 4 resources and 2 cards in hand is offered');
$build(function ($b) { $b->MyLeader('SOR_001'); $b->FillResourcesForPlayer(1, 'SOR_095', 7); });
$check($deployOffered(), 'a plain leader still deploys on its printed cost alone');

bot_test_finish();

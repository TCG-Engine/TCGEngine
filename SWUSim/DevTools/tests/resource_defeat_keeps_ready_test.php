<?php
// CR 1.x (resources, ".claude/SWUSim/refs/comprehensive-rules.md" line 94): "a player may rearrange their resources at any time up to the
// point of a specific resource being chosen … A player may change which of their resources are ready or exhausted during a rearrangement,
// so long as the number of ready and exhausted resources is the same." OWNER, 2026-10-08: "in SWU defeating resources ready state does not
// matter. so if i have 2 ready, 1 exhausted and i defeat one … re-arrange … to make the ready one i want to defeat exhausted".
// So when a player defeats a resource THEY choose, an exhausted one goes while any is left — the ready count stands. The engine removed
// the exact card picked, ready state and all: Chewbacca's On Attack on a ready resource (41% of bot accepts) cost a ready resource.
// An opponent's or a random pick (SHD Scanning Officer, SEC Elia Kane) and "defeat READY resources" (HMW_049 Greater Sarlacc) stay exact.
// Fixtures (dictionary-checked): LAW_013 Chewbacca (deployed) · LAW_019 Alliance Outpost · SOR_095 · SEC_148 Karis Nemik (their unit).
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php -d apc.enable_cli=1 -d xdebug.mode=off SWUSim/DevTools/tests/resource_defeat_keeps_ready_test.php
require __DIR__ . '/fixtures/bot_test_bootstrap.php';
foreach (['BotLegalActions', 'Custom/BotLookahead', 'Custom/BotEvaluator', 'Rl/CardTags', 'Custom/BotStyles', 'Custom/BotResourcing',
          'Custom/BotGuides', 'Custom/BotFallback', 'Custom/BotRules', 'BotHeuristic'] as $f) include_once "./SWUSim/$f.php";
$counts = function (int $p): array {
    $r = 0; $e = 0;
    foreach (GetResources($p) as $o) {
        if ($o === null || !empty($o->removed) || SWUIsCreditToken(strval($o->CardID ?? ''))) continue;
        if (intval($o->Status ?? 0) === 1) $r++; else $e++;
    }
    return [$r, $e];
};
// 2 ready + 1 exhausted resources (the exhausted one listed LAST); their Karis on the ground.
$board = function () use ($build) {
    $build(function ($b) {
        $b->MyLeader('LAW_013', true, true, true, 'unit'); $b->MyBase('LAW_019'); $b->WithCurrentRoundBeing(4);
        $b->FillResourcesForPlayer(1, 'SOR_095', 2); $b->FillResourcesForPlayer(1, 'SOR_095', 1, false);
        $b->WithGroundUnitForPlayer(2, 'SEC_148', false);
        for ($k = 0; $k < 30; $k++) { $b->WithCardInDeckForPlayer(1, 'SOR_095'); $b->WithCardInDeckForPlayer(2, 'SOR_095'); }
    });
};

// A) Chewbacca attacks; his On Attack defeats a READY resource (myResources-0): an exhausted one goes instead — still 2 ready.
$board();
$check($counts(1) === [2, 1], 'fixture: 2 ready + 1 exhausted; got ' . json_encode($counts(1)));
$check(intval(GetResources(1)[0]->Status ?? 0) === 1, 'fixture: myResources-0 is ready');
$act(1, 10002, 'myGroundArena-0!FSM!');
$tips = [];
for ($k = 0; $k < 6 && in_array($botCtx('hyperaggro')['kind'], ['decision'], true); $k++) {
    $c = $botCtx('hyperaggro'); $tip = strval($c['tooltip'] ?? ''); $tips[] = $tip;
    $ids = array_map(fn($a) => strval($a['cardID']), $c['actions']);
    if (str_contains($tip, 'attack_target')) $ans = in_array('theirBase-0', $ids, true) ? 'theirBase-0' : $ids[0];
    elseif (str_contains($tip, 'resource')) $ans = 'myResources-0';
    else { $ans = ''; foreach ($ids as $id) if (str_starts_with($id, 'their')) { $ans = $id; break; } if ($ans === '') $ans = $ids[0]; }
    $act(1, 100, $ans);
}
$check(in_array('Choose_a_resource', $tips, true), 'A fixture: the On Attack was offered; tips ' . json_encode($tips));
$check($counts(1) === [2, 0], 'A: the exhausted resource went — 2 ready, 0 exhausted; got ' . json_encode($counts(1)));

// B) An opponent-side, exact defeat (the helper with no controller choice) still takes the very card picked.
$board();
SWUDefeatResource(1, 'myResources-0');
$check($counts(1) === [1, 1], 'B: an exact defeat of a ready resource leaves 1 ready + 1 exhausted; got ' . json_encode($counts(1)));

// C) No exhausted resource to swap with: the ready one goes (nothing to rearrange).
$build(function ($b) {
    $b->MyLeader('LAW_013', true); $b->MyBase('LAW_019'); $b->FillResourcesForPlayer(1, 'SOR_095', 2);
    for ($k = 0; $k < 30; $k++) { $b->WithCardInDeckForPlayer(1, 'SOR_095'); $b->WithCardInDeckForPlayer(2, 'SOR_095'); }
});
SWUDefeatResource(1, 'myResources-0', true);
$check($counts(1) === [1, 0], 'C: all ready — one ready resource goes; got ' . json_encode($counts(1)));

bot_test_finish();

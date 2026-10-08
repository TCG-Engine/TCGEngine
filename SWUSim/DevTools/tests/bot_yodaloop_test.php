<?php
// Feature 'yodaloop' (p42) — the LOF_016 Qui-Gon Jinn deck's Yoda loop: cast LOF_101 Yoda ("When Played: You may use the Force. If you do,
// heal 5 damage from a base. When you use the Force: You may deal damage to a unit equal to twice the number of units you control"), tuck
// him with Qui-Gon (a cheaper unit free — Kelleran Beq / Depa Billaba / Queen Amidala), cast him again for another heal.
// OWNER RULINGS 2026-10-08:
//   - the Force: "Yoda's heal first" — while a CASTABLE Yoda in hand still wants the Force for his heal, the tuck (which also spends it) waits;
//     "with the caveat that there is no need to heal when there's nothing on base";
//   - replay targets: "only don't resource" — a chain piece (a non-Villainy unit, 5+ cost, with a When Played / Ambush / Shielded to re-buy)
//     is never resourced by a tuck deck; hard-casting it stays a normal play — "generally good after the 5R turn" (from 5 resources);
//   - "tuck Yoda every round once he's used" — the Force-heal unit is the first one the tuck returns; "if possible to swing to base and get
//     the 5 damage in, then take that line" — a ready unit that can hit the base attacks first (the front Action can wait for it).
// Fixtures (dictionary-checked): LOF_016 Qui-Gon · LOF_023 Jedi Temple · LOF_101 Yoda (8) · LAW_224 Liberty (8) · LOF_100 Kelleran Beq (7)
//   · LOF_199 Depa Billaba (6) · SEC_101 Queen Amidala (5) · ASH_234 Masterstroke (2, event) · ASH_052 Chimaera (Villainy) · LAW_118 Droid Laser Turret (Sentinel) · SOR_225 · SOR_095.
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php -d apc.enable_cli=1 -d xdebug.mode=off SWUSim/DevTools/tests/bot_yodaloop_test.php
require __DIR__ . '/fixtures/bot_test_bootstrap.php';
foreach (['BotLegalActions', 'Custom/BotLookahead', 'Custom/BotEvaluator', 'Rl/CardTags', 'Custom/BotStyles', 'Custom/BotResourcing',
          'Custom/BotGuides', 'Custom/BotFallback', 'Custom/BotRules', 'BotHeuristic'] as $f) include_once "./SWUSim/$f.php";
$check(SWUBotVariantDisabled('no-yodaloop') === ['yodaloop'], 'yodaloop is switchable');
$check(in_array('yodaloop', SWUBotFeatureGroups()['p42'] ?? [], true), 'yodaloop is in group p42');
$ability = 'myLeader-0!CustomInput!LeaderAbility';
$scoreOf = function (string $id, array $off = []) use ($botCtx) {
    SWUBotSetDisabledFeatures($off); $c = $botCtx('midrange'); $s = null;
    foreach ($c['actions'] as $i => $a) if (strval($a['cardID']) === $id) $s = SWUBotScoreAction($c, $a, $i);
    SWUBotSetDisabledFeatures([]); return $s;
};
// Qui-Gon (front, the Force), $res resources, my exhausted $mine, $hand; their Marine.
$board = function (int $res, array $mine, array $hand) use ($build) {
    $build(function ($b) use ($res, $mine, $hand) {
        $b->MyLeader('LOF_016', true); $b->MyBase('LOF_023', 8); $b->WithForceForPlayer(1); $b->FillResourcesForPlayer(1, 'SOR_095', $res);
        $b->WithCurrentRoundBeing(max(1, $res));
        foreach ($mine as $c) $b->WithGroundUnitForPlayer(1, $c, false);
        foreach ($hand as $c) $b->WithCardInHandForPlayer(1, $c);
        $b->WithGroundUnitForPlayer(2, 'SOR_095', false);
        for ($k = 0; $k < 30; $k++) { $b->WithCardInDeckForPlayer(1, 'SOR_095'); $b->WithCardInDeckForPlayer(2, 'SOR_095'); }
    });
};

// F) The Force: Depa on the board, Amidala in hand (a tuck worth taking), and a Yoda in hand CASTABLE (8 resources) — his heal first: the
// tuck waits. With 7 resources Yoda is not castable this round — the tuck goes.
$board(8, ['LOF_199'], ['SEC_101', 'LOF_101']);
$check($scoreOf($ability, ['yodaloop']) > 0, 'F fixture: today the tuck spends the Force a castable Yoda wants');
$check($scoreOf($ability) <= 0, 'F: Yoda castable — the Force waits for his heal; got ' . $scoreOf($ability));
$board(7, ['LOF_199'], ['SEC_101', 'LOF_101']);
$check($scoreOf($ability) > 0, 'F: Yoda not castable this round — the tuck goes; got ' . $scoreOf($ability));
// F2) Nothing on my base: no heal to wait for — the tuck goes even with a castable Yoda.
$build(function ($b) {
    $b->MyLeader('LOF_016', true); $b->MyBase('LOF_023', 0); $b->WithForceForPlayer(1); $b->FillResourcesForPlayer(1, 'SOR_095', 8); $b->WithCurrentRoundBeing(8);
    $b->WithGroundUnitForPlayer(1, 'LOF_199', false); $b->WithCardInHandForPlayer(1, 'SEC_101'); $b->WithCardInHandForPlayer(1, 'LOF_101');
    $b->WithGroundUnitForPlayer(2, 'SOR_095', false);
    for ($k = 0; $k < 30; $k++) { $b->WithCardInDeckForPlayer(1, 'SOR_095'); $b->WithCardInDeckForPlayer(2, 'SOR_095'); }
});
$check($scoreOf($ability) > 0, 'F2: my base undamaged — no heal to wait for, the tuck goes; got ' . $scoreOf($ability));
// F3) 3 damage on my base: Yoda's 5 would waste 2 (owner: "don't waste on a heal of 4 or less") — the tuck goes.
$build(function ($b) {
    $b->MyLeader('LOF_016', true); $b->MyBase('LOF_023', 3); $b->WithForceForPlayer(1); $b->FillResourcesForPlayer(1, 'SOR_095', 8); $b->WithCurrentRoundBeing(8);
    $b->WithGroundUnitForPlayer(1, 'LOF_199', false); $b->WithCardInHandForPlayer(1, 'SEC_101'); $b->WithCardInHandForPlayer(1, 'LOF_101');
    $b->WithGroundUnitForPlayer(2, 'SOR_095', false);
    for ($k = 0; $k < 30; $k++) { $b->WithCardInDeckForPlayer(1, 'SOR_095'); $b->WithCardInDeckForPlayer(2, 'SOR_095'); }
});
$check($scoreOf($ability) > 0, 'F3: a heal of 3 is not worth holding the Force for — the tuck goes; got ' . $scoreOf($ability));

// T) Yoda first: Liberty (8, listed first) and Yoda (8) on the board, both used; Kelleran in hand. The tuck returns Yoda.
$board(0, ['LAW_224', 'LOF_101'], ['LOF_100']);
$act(1, 10001, $ability);
for ($k = 0; $k < 3 && $botCtx('midrange')['kind'] === 'decision' && !str_starts_with($botCtx('midrange')['tooltip'], 'Return_a_friendly'); $k++) $act(1, 100, 'NO');
$check(str_starts_with($botCtx('midrange')['tooltip'], 'Return_a_friendly'), 'T fixture: the return prompt is pending; got ' . $botCtx('midrange')['tooltip']);
$pickRet = function (string $variant) use (&$gameName) { $l = SWUBotLegalActions($gameName, 1); $p = SWUBotHeuristicChoose('midrange', (array)$l['actions'], $l, $variant); $mz = strval($p['cardID'] ?? ''); return preg_match('/^myGroundArena-(\d+)$/', $mz, $m) ? strval(GetZone('myGroundArena')[intval($m[1])]->CardID ?? '') : $mz; };
$check($pickRet('no-yodaloop') === 'LAW_224', 'T fixture: today the first-listed Liberty');
$check($pickRet('') === 'LOF_101', 'T: Yoda goes back (to be cast again for his heal); got ' . $pickRet(''));

// T2) Swing first: Yoda READY with the base open (no Sentinel) and Kelleran in hand — the tuck waits for his attack (today it returns him).
$build(function ($b) {
    $b->MyLeader('LOF_016', true); $b->MyBase('LOF_023', 8); $b->WithForceForPlayer(1); $b->WithCurrentRoundBeing(8);
    $b->WithGroundUnitForPlayer(1, 'LOF_101', true); $b->WithCardInHandForPlayer(1, 'LOF_100');
    $b->WithSpaceUnitForPlayer(2, 'SOR_225', false);
    for ($k = 0; $k < 30; $k++) { $b->WithCardInDeckForPlayer(1, 'SOR_095'); $b->WithCardInDeckForPlayer(2, 'SOR_095'); }
});
$check($scoreOf($ability, ['yodaloop']) > 0, 'T2 fixture: today the ready Yoda is tucked before he swings');
$check($scoreOf($ability) <= 0, 'T2: Yoda swings at the base first; got ' . $scoreOf($ability));
// T3) …but with a Sentinel in his way (no base to hit), the tuck goes.
$build(function ($b) {
    $b->MyLeader('LOF_016', true); $b->MyBase('LOF_023', 8); $b->WithForceForPlayer(1); $b->WithCurrentRoundBeing(8);
    $b->WithGroundUnitForPlayer(1, 'LOF_101', true); $b->WithCardInHandForPlayer(1, 'LOF_100');
    $b->WithGroundUnitForPlayer(2, 'LAW_118', false);   // Droid Laser Turret: Sentinel
    for ($k = 0; $k < 30; $k++) { $b->WithCardInDeckForPlayer(1, 'SOR_095'); $b->WithCardInDeckForPlayer(2, 'SOR_095'); }
});
$check($scoreOf($ability) > 0, 'T3: a Sentinel blocks the base — the tuck goes; got ' . $scoreOf($ability));

// T4) The front pick keeps it too: Yoda ready (base open) and Depa exhausted, Amidala in hand — the Action goes (Depa -> Amidala) and returns
// Depa, not the ready Yoda.
$build(function ($b) {
    $b->MyLeader('LOF_016', true); $b->MyBase('LOF_023', 0); $b->WithForceForPlayer(1); $b->WithCurrentRoundBeing(8);
    $b->WithGroundUnitForPlayer(1, 'LOF_101', true); $b->WithGroundUnitForPlayer(1, 'LOF_199', false); $b->WithCardInHandForPlayer(1, 'SEC_101');
    $b->WithSpaceUnitForPlayer(2, 'SOR_225', false);
    for ($k = 0; $k < 30; $k++) { $b->WithCardInDeckForPlayer(1, 'SOR_095'); $b->WithCardInDeckForPlayer(2, 'SOR_095'); }
});
$act(1, 10001, $ability);
for ($k = 0; $k < 3 && $botCtx('midrange')['kind'] === 'decision' && !str_starts_with($botCtx('midrange')['tooltip'], 'Return_a_friendly'); $k++) $act(1, 100, 'NO');
$check($pickRet('') === 'LOF_199', 'T4: Depa is returned — the ready Yoda swings first; got ' . $pickRet(''));

// R) Resourcing — from 5 resources (owner: "generally good after the 5R turn"). On the soft-aggro wing's keep value (−cost: the most expensive
// card goes), Yoda, Kelleran and a Masterstroke on 5 resources: today Yoda is resourced; fixed, the Masterstroke. On 4 resources: unchanged.
$resourced = function (int $res, array $off) use ($board, $botCtx) {
    $board($res, [], ['LOF_101', 'LOF_100', 'ASH_234']);
    SWUBotSetDisabledFeatures($off); $pickI = SWUBotChooseResourceCards($botCtx('softaggro'), 1)[0] ?? null; SWUBotSetDisabledFeatures([]);
    preg_match('/(\d+)$/', strval($pickI), $m); return strval(GetHand(1)[intval($m[1] ?? -1)]->CardID ?? strval($pickI));
};
$check($resourced(5, ['yodaloop']) === 'LOF_101', 'R fixture: today Yoda is resourced; got ' . $resourced(5, ['yodaloop']));
$check($resourced(5, []) === 'ASH_234', 'R: from 5 resources the chain pieces are kept — the Masterstroke goes; got ' . $resourced(5, []));
$check($resourced(4, []) === $resourced(4, ['yodaloop']), 'R: on 4 resources — unchanged; got ' . $resourced(4, []));
// R2) A Villainy unit is no chain piece (Qui-Gon's free play is "non-Villainy"): Yoda, Kelleran and Chimaera (Vigilance/Villainy) on 5 — the
// Chimaera goes.
$board(5, [], ['LOF_101', 'LOF_100', 'ASH_052']);
$pickI = SWUBotChooseResourceCards($botCtx('softaggro'), 1)[0] ?? null; preg_match('/(\d+)$/', strval($pickI), $m);
$check(strval(GetHand(1)[intval($m[1] ?? -1)]->CardID ?? '') === 'ASH_052', 'R2: the Villainy Chimaera goes, the chain pieces stay; got ' . strval(GetHand(1)[intval($m[1] ?? -1)]->CardID ?? ''));

bot_test_finish();

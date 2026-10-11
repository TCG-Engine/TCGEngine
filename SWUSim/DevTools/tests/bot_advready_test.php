<?php
// Feature 'advready' (p42) — ASH_013 Ezra Bridger, It's Now or Never: "When a friendly unit's attack ends: If it dealt 3 or more combat damage
// to a base, you may exhaust this leader. If you do, give an Advantage token to a different unit." (deployed: the give without the exhaust.)
// The Advantage token (ASH_T02) is +1/+0 until the unit's next attack or defense ends. Leader audit 2026-10-08: the exhaust was taken 100% of
// the time, and in 6.4% of uses (228 of 3,536) no other friendly unit existed — the forced give went to an ENEMY; and the token went to an
// exhausted unit while a ready one existed in 14% (front) / 20% (deployed) of gives. Fixed: no exhaust without a friendly unit to take it;
// the token goes to a READY friendly unit (it swings this round) before an exhausted one.
// Fixtures (dictionary-checked): ASH_013 Ezra Bridger · SOR_020 base · SOR_095 Battlefield Marine (3/3) · SOR_164 Wampa (4/5).
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php -d apc.enable_cli=1 -d xdebug.mode=off SWUSim/DevTools/tests/bot_advready_test.php
require __DIR__ . '/fixtures/bot_test_bootstrap.php';
foreach (['BotLegalActions', 'Custom/BotLookahead', 'Custom/BotEvaluator', 'Rl/CardTags', 'Custom/BotStyles', 'Custom/BotResourcing',
          'Custom/BotGuides', 'Custom/BotFallback', 'Custom/BotRules', 'BotHeuristic'] as $f) include_once "./SWUSim/$f.php";
$check(SWUBotVariantDisabled('no-advready') === ['advready'], 'advready is switchable');
$check(in_array('advready', SWUBotFeatureGroups()['p42'] ?? [], true), 'advready is in group p42');
$pick = function (string $variant = '') use (&$gameName) {
    $l = SWUBotLegalActions($gameName, 1);
    $p = SWUBotHeuristicChoose('midrange', (array)$l['actions'], $l, $variant);
    return strval($p['cardID'] ?? '');
};
// Ezra ready; my attacking Marine (ground-0, ready) plus $others = [[cardID, ready], …]; no enemy unit, so the Marine hits the base for 3
// and Ezra triggers.
$attack = function (array $others) use ($build, $raiseAttack, $botCtx, $act) {
    $build(function ($b) use ($others) {
        $b->MyLeader('ASH_013', true); $b->MyBase('SOR_020'); $b->WithCurrentRoundBeing(4);
        $b->WithGroundUnitForPlayer(1, 'SOR_095', true);
        foreach ($others as [$c, $r]) $b->WithGroundUnitForPlayer(1, $c, $r);
        for ($k = 0; $k < 30; $k++) { $b->WithCardInDeckForPlayer(1, 'SOR_095'); $b->WithCardInDeckForPlayer(2, 'SOR_095'); }
    });
    $raiseAttack(1, 'myGroundArena-0');
    for ($k = 0; $k < 3 && $botCtx('midrange')['kind'] === 'decision' && !str_starts_with($botCtx('midrange')['tooltip'], 'Exhaust_Ezra'); $k++) $act(1, 100, 'PASS');
    return $botCtx('midrange')['tooltip'];
};
$isReady = function (string $mz): ?bool { $o = preg_match('/^myGroundArena-(\d+)$/', $mz, $m) ? (GetZone('myGroundArena')[intval($m[1])] ?? null) : null; return $o === null ? null : intval($o->Status) === 1; };

// A) The attacker is my only unit: the token could only go to an enemy — no exhaust. Today YES.
$tip = $attack([]);
$check($tip === 'Exhaust_Ezra_to_give_an_Advantage_token_to_a_different_unit?', 'A fixture: Ezra\'s offer is pending; got ' . $tip);
$check($pick('no-advready') === 'YES', 'A fixture: today Ezra is exhausted anyway');
$check($pick() === 'NO', 'A: no other friendly unit — declined; got ' . $pick());

// B) An exhausted Wampa (4/5, listed first, the bigger unit) and a ready Marine: the token goes to the READY one — it swings this round.
$attack([['SOR_164', false], ['SOR_095', true]]);
$act(1, 100, 'YES');
$tip = $botCtx('midrange')['tooltip'];
$check(str_contains($tip, 'Advantage'), 'B fixture: the Advantage pick is pending; got ' . $tip);
$check($isReady($pick('no-advready')) === false, 'B fixture: today the bigger, exhausted Wampa');
$check($isReady($pick()) === true, 'B: the token goes to the ready Marine; got ' . $pick());

bot_test_finish();

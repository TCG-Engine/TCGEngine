<?php
// Feature 'healwaste' (p42) — OWNER RULING 2026-10-08: "don't waste on a heal of 4 or less [Yoda's 5]. that goes for other restores too. it is
// sometimes best to let the opponent attack first when you have 0 damage on base if you can restore 1 or 2 after their hit."
//   - "Use the Force to heal N" (LOF_101 Yoda's When Played, LOF_102 Yoda's Lightsaber): declined while my base carries less than N damage —
//     the Force is kept (Qui-Gon's tuck spends it too);
//   - an attacker with Restore N while my base carries less than N damage, and the opponent has a ready unit that can hit my base first:
//     the attack waits behind the round's other actions by the restore it would waste (the shipped "don't end the round with a free attack
//     unused" rule still sends it before the round ends).
// (The Qui-Gon tuck's "Yoda's heal first" hold reads the same: only a FULL heal is worth holding the Force for — bot_yodaloop_test F3.)
// Fixtures (dictionary-checked): LOF_016 Qui-Gon · LOF_023 Jedi Temple · LOF_101 Yoda (8, heal 5) · LOF_057 Owen Lars (1, Restore 2)
//   · SOR_095 Battlefield Marine.
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php -d apc.enable_cli=1 -d xdebug.mode=off SWUSim/DevTools/tests/bot_healwaste_test.php
require __DIR__ . '/fixtures/bot_test_bootstrap.php';
foreach (['BotLegalActions', 'Custom/BotLookahead', 'Custom/BotEvaluator', 'Rl/CardTags', 'Custom/BotStyles', 'Custom/BotResourcing',
          'Custom/BotGuides', 'Custom/BotFallback', 'Custom/BotRules', 'BotHeuristic'] as $f) include_once "./SWUSim/$f.php";
$check(SWUBotVariantDisabled('no-healwaste') === ['healwaste'], 'healwaste is switchable');
$check(in_array('healwaste', SWUBotFeatureGroups()['p42'] ?? [], true), 'healwaste is in group p42');
$pick = function (string $variant = '') use (&$gameName) {
    $l = SWUBotLegalActions($gameName, 1);
    $p = SWUBotHeuristicChoose('midrange', (array)$l['actions'], $l, $variant);
    return strval($p['cardID'] ?? '');
};

// H) Yoda cast with my base on $dmg damage: "use the Force to heal 5?" — 3 damage: no (a heal of 3 wastes 2); 6 damage: yes.
$yoda = function (int $dmg) use ($build, $act, $botCtx) {
    $build(function ($b) use ($dmg) {
        $b->MyLeader('LOF_016', true, false, true); $b->MyBase('LOF_023', $dmg); $b->WithForceForPlayer(1); $b->FillResourcesForPlayer(1, 'SOR_095', 8);
        $b->WithCurrentRoundBeing(8); $b->WithCardInHandForPlayer(1, 'LOF_101');
        for ($k = 0; $k < 30; $k++) { $b->WithCardInDeckForPlayer(1, 'SOR_095'); $b->WithCardInDeckForPlayer(2, 'SOR_095'); }
    });
    $act(1, 10002, 'myHand-0!FSM!');
    for ($k = 0; $k < 3 && $botCtx('midrange')['kind'] === 'decision' && !str_contains($botCtx('midrange')['tooltip'], 'heal_5'); $k++) $act(1, 100, 'PASS');
    return $botCtx('midrange')['tooltip'];
};
$check(str_contains($yoda(3), 'heal_5'), 'H fixture: Yoda\'s heal prompt is pending; got ' . $yoda(3));
$check($pick('no-healwaste') === 'YES', 'H fixture: today the Force goes on a heal of 3');
$check($pick() === 'NO', 'H: 3 damage — a heal of 3 is a waste, the Force is kept; got ' . $pick());
$yoda(6);
$check($pick() === 'YES', 'H: 6 damage — the full 5 lands; got ' . $pick());

// R) Owen Lars (Restore 2) ready, their ready Marine can hit my base: with my base on 0, his attack waits by the restore it wastes; with my
// base on 2, nothing is wasted.
$restore = function (int $dmg, array $off, bool $enemy = true) use ($build, $botCtx) {
    $build(function ($b) use ($dmg, $enemy) {
        $b->MyLeader('SOR_014', false); $b->MyBase('SOR_020', $dmg); $b->WithCurrentRoundBeing(4);
        $b->WithGroundUnitForPlayer(1, 'LOF_057', true); if ($enemy) $b->WithGroundUnitForPlayer(2, 'SOR_095', true);
        for ($k = 0; $k < 30; $k++) { $b->WithCardInDeckForPlayer(1, 'SOR_095'); $b->WithCardInDeckForPlayer(2, 'SOR_095'); }
    });
    SWUBotSetDisabledFeatures($off); $c = $botCtx('midrange'); $s = null;
    foreach ($c['actions'] as $i => $a) if (preg_match('/^myGroundArena-\d+!FSM!$/', strval($a['cardID']))) $s = SWUBotScoreAction($c, $a, $i);
    SWUBotSetDisabledFeatures([]); return $s;
};
$check($restore(0, []) < $restore(0, ['healwaste']), 'R: base on 0 — the Restore 2 attack waits; got ' . json_encode([$restore(0, []), $restore(0, ['healwaste'])]));
$check(abs($restore(2, []) - $restore(2, ['healwaste'])) < 1e-9, 'R: base on 2 — nothing wasted, unchanged');
$check(abs($restore(0, [], false) - $restore(0, ['healwaste'], false)) < 1e-9, 'R: no enemy unit to hit my base first — nothing to wait for, unchanged');

bot_test_finish();

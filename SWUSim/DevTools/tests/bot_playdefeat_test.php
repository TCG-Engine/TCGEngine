<?php
// Features 'playdefeat' + 'deployreplay' (p42) — HMW_016 Maul, Old Master.
//   Front: "Action [Exhaust]: Play a unit from your hand. It costs 1 resource less. Then, defeat it. (When Played abilities resolve after
//          the unit is defeated.)"
//   Deployed: "When Deployed: You may play a unit that was defeated this phase from your discard pile. It costs 5 resources less."
// Leader audit 2026-10-08, 128 traced uses: the -1 made a card one past my resources "unlocked", so the Action was priced at that card's
// FULL play value, body included, though the body dies at once — 25 uses played a unit with no When Played / When Defeated at all (Mae
// x12), and the pick took the biggest body. Deploy was a flat 1.5 at the first legal round: 21 of 31 deploys replayed nothing.
// Fixed: the Action and its pick are worth the unit's EFFECTS only (its When Played / When Defeated tags); the deploy adds the best
// replay it makes, and waits behind a Maul Action that would put a unit in the discard for it.
// Fixtures (dictionary-checked): HMW_016 Maul (Cunning/Villainy) · JTL_020 Shield Generator Complex (Vigilance) · HMW_055 Mae (3, Ambush
//   Shielded Grit — no effect) · SOR_206 Mining Guild TIE Fighter (1, On Attack: draw) · JTL_033 Onyx Squadron Brute (2, WD heal 2) · LOF_213 The Legacy Run (5, WD 6 damage split) · SOR_095.
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php -d apc.enable_cli=1 -d xdebug.mode=off SWUSim/DevTools/tests/bot_playdefeat_test.php
require __DIR__ . '/fixtures/bot_test_bootstrap.php';
foreach (['BotLegalActions', 'Custom/BotLookahead', 'Custom/BotEvaluator', 'Rl/CardTags', 'Custom/BotStyles', 'Custom/BotResourcing',
          'Custom/BotGuides', 'Custom/BotFallback', 'Custom/BotRules', 'BotHeuristic'] as $f) include_once "./SWUSim/$f.php";
foreach (['playdefeat', 'deployreplay'] as $f) {
    $check(SWUBotVariantDisabled("no-$f") === [$f], "$f is switchable");
    $check(in_array($f, SWUBotFeatureGroups()['p42'] ?? [], true), "$f is in group p42");
}
$pick = function (string $variant = '') use (&$gameName) {
    $l = SWUBotLegalActions($gameName, 1);
    $p = SWUBotHeuristicChoose('softcontrol', (array)$l['actions'], $l, $variant);
    return strval($p['cardID'] ?? '');
};
// Maul's front side ready, $res ready resources, $hand in hand, two of their Marines; $epicUsed false = he may deploy (7+ resources).
$board = function (int $res, array $hand, bool $epicUsed = true) use ($build) {
    $build(function ($b) use ($res, $hand, $epicUsed) {
        $b->MyLeader('HMW_016', true, false, $epicUsed); $b->MyBase('JTL_020', 6); $b->FillResourcesForPlayer(1, 'HMW_055', $res); $b->WithCurrentRoundBeing(6);
        foreach ($hand as $c) $b->WithCardInHandForPlayer(1, $c);
        $b->WithGroundUnitForPlayer(2, 'SOR_095', false); $b->WithGroundUnitForPlayer(2, 'SOR_095', false);
        for ($k = 0; $k < 30; $k++) { $b->WithCardInDeckForPlayer(1, 'HMW_055'); $b->WithCardInDeckForPlayer(2, 'SOR_095'); }
    });
};
$ability = 'myLeader-0!CustomInput!LeaderAbility';
$deploy = 'myLeader-0!CustomInput!DeployLeader:Unit';

// A) 2 resources, Mae (3) in hand: the -1 makes her playable, then she dies with nothing to show. Today the Action is used; fixed, not.
$board(2, ['HMW_055']);
$check($pick('no-playdefeat') === $ability, 'A fixture: today Maul plays Mae to her death');
$check($pick() !== $ability, 'A: a unit with no When Played / When Defeated — the Action is not used');

// A2) No resources, a Mining Guild TIE Fighter (1): its draw is an On Attack — not a When Played / When Defeated — so through Maul it
// dies having drawn nothing. Not used.
$board(0, ['SOR_206']);
$check($pick() !== $ability, 'A2: an On Attack unit (Mining Guild TIE Fighter) — the Action is not used');

// B) 4 resources, The Legacy Run (5) in hand: Maul plays it for 4 and its When Defeated deals 6 to their units. Used.
$board(4, ['LOF_213']);
$check($pick() === $ability, 'B: The Legacy Run — its When Defeated is the point; the Action is used');

// C) The pick: 4 resources, Mae (3) and Onyx Squadron Brute (2, When Defeated: heal 2) in hand. Today the bigger body (Mae); fixed, the Brute.
$board(4, ['HMW_055', 'JTL_033']);
$act(1, 10001, $ability);
$tip = $botCtx('softcontrol')['tooltip'];
$check(str_starts_with($tip, 'Play_a_unit_from_your_hand'), 'C fixture: Maul\'s play prompt is pending; got ' . $tip);
$check($pick('no-playdefeat') === 'myHand-0', 'C fixture: today Mae (the bigger body) is played to her death');
$check($pick() === 'myHand-1', 'C: the Onyx Squadron Brute — its When Defeated heals');

// D) Maul's line: 9 resources, Maul may deploy, The Legacy Run in hand. Today it is hard-cast for 5. Fixed: the Action goes first
// (Legacy Run for 4 — its 6 damage now), then the deploy replays it for 0 (the body back, its When Defeated again later).
$board(9, ['LOF_213'], false);
$check($pick('no-deployreplay') === 'myHand-0!FSM!', 'D fixture: today The Legacy Run is hard-cast for 5; got ' . $pick('no-deployreplay'));
$check($pick() === $ability, 'D: the Maul Action goes first');
$act(1, 10001, $ability);
for ($k = 0; $k < 6 && $botCtx('softcontrol')['kind'] === 'decision'; $k++) {   // the play pick, then Legacy Run's 6-damage split
    $l = SWUBotLegalActions($gameName, 1); $p = SWUBotHeuristicChoose('softcontrol', (array)$l['actions'], $l, '');
    $act(1, 100, strval($p['cardID'] ?? 'PASS'));
}
$check(in_array('LOF_213', array_map(fn($o) => strval($o->CardID), GetDiscard(1)), true), 'D fixture: The Legacy Run is in the discard');
$act(2, 10001, 'myHealth-0!CustomInput!Pass');   // their action: a pass, back to me
$check($botCtx('softcontrol')['kind'] === 'free-play', 'D fixture: my action again; got ' . $botCtx('softcontrol')['kind']);
$check($pick() === $deploy, 'D: now the deploy (it replays The Legacy Run for 0)');
// …and the deploy is worth the replay (scored above a bare deploy), which then happens.
$c = $botCtx('softcontrol'); $dScore = function () use ($botCtx, $deploy) { $c = $botCtx('softcontrol'); foreach ($c['actions'] as $i => $a) if ($a['cardID'] === $deploy) return SWUBotScoreAction($c, $a, $i); return null; };
$with = $dScore(); SWUBotSetDisabledFeatures(['deployreplay']); $without = $dScore(); SWUBotSetDisabledFeatures([]);
$check($with !== null && $without !== null && $with > $without + 0.5, 'D: the deploy is priced with its replay; got ' . json_encode([$with, $without]));
$act(1, 10001, $deploy);
for ($k = 0; $k < 6 && $botCtx('softcontrol')['kind'] === 'decision'; $k++) {
    $l = SWUBotLegalActions($gameName, 1); $p = SWUBotHeuristicChoose('softcontrol', (array)$l['actions'], $l, '');
    $act(1, 100, strval($p['cardID'] ?? 'PASS'));
}
$check(in_array('LOF_213', array_map(fn($o) => strval($o->CardID), GetZone('mySpaceArena')), true), 'D: The Legacy Run (a ship) is back in play');

// E) The deploy waits behind a worthwhile Maul Action even when no replay can follow (the deploy's own price, 1.5, is above the Action's):
// 7 resources, 5 ready, Anakin Skywalker (6) in hand — Maul plays him for 5 (his When Played now), nothing is left for a replay, and he
// still deploys after (a leader deploys exhausted or not, CR 4.329).
$build(function ($b) {
    $b->MyLeader('HMW_016', true, false, false); $b->MyBase('JTL_020', 6); $b->FillResourcesForPlayer(1, 'HMW_055', 5); $b->WithCurrentRoundBeing(6);
    $b->WithControlledResourceForPlayer(1, 'HMW_055', 1, false); $b->WithControlledResourceForPlayer(1, 'HMW_055', 1, false);
    $b->WithCardInHandForPlayer(1, 'LOF_070');
    $b->WithGroundUnitForPlayer(2, 'SOR_164', false); $b->WithGroundUnitForPlayer(2, 'SOR_095', false);
    for ($k = 0; $k < 30; $k++) { $b->WithCardInDeckForPlayer(1, 'HMW_055'); $b->WithCardInDeckForPlayer(2, 'SOR_095'); }
});
$check($pick('no-deployreplay') === $deploy, 'E fixture: without the hold Maul deploys first; got ' . $pick('no-deployreplay'));
$check($pick() === $ability, 'E: the Maul Action (Anakin) goes before the deploy; got ' . $pick());

// F) …but a replay the resources cannot cover is no reason to Maul a bomb: 7 ready, Chimaera (7). Through Maul it costs 6 and leaves 1 —
// short of its replay (2) — so it is hard-cast whole.
$build(function ($b) {
    $b->MyLeader('HMW_016', true, false, false); $b->MyBase('JTL_020', 6); $b->FillResourcesForPlayer(1, 'HMW_055', 7); $b->WithCurrentRoundBeing(6);
    $b->WithCardInHandForPlayer(1, 'ASH_052'); $b->WithGroundUnitForPlayer(1, 'HMW_055', false);
    $b->WithGroundUnitForPlayer(2, 'SOR_164', false); $b->WithGroundUnitForPlayer(2, 'SOR_095', false);
    for ($k = 0; $k < 30; $k++) { $b->WithCardInDeckForPlayer(1, 'HMW_055'); $b->WithCardInDeckForPlayer(2, 'SOR_095'); }
});
$check($pick() === 'myHand-0!FSM!', 'F: no replay can follow — Chimaera is hard-cast, not Mauled; got ' . $pick());

bot_test_finish();

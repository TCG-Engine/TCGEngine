<?php
// Features 'tokenfirst' + 'deploybuff' (p42) — ASH_017 Greef Karga, Gracious Magistrate: front "When you play or create a unit: You may
// exhaust this leader. If you do, give an Advantage token to that unit."; deployed "When you play or create a unit: Give an Advantage
// token to that unit."
// Leader audit 2026-10-08: (1) with Greef's trigger and ASH_171 Pegasus Tri-Wing's ("When Played: You may defeat a friendly upgrade. If you
// do, ready this unit") on the stack together, the Tri-Wing's resolved first 645 of 645 times — so it never saw Greef's Advantage and the
// combo fired 20 times (3%): every ordinary trigger scored 1.0 and the first listed went. (2) In the deploy round, 41% of deploys came
// after 2+ units were played, which missed the deployed side's free Advantage. Fixed: a trigger that gives the played unit a token
// resolves before that unit's own triggers; a unit play waits while a leader whose deployed side buffs every unit played can deploy now.
// Fixtures (dictionary-checked): ASH_017 Greef · SOR_020 base · ASH_171 Pegasus Tri-Wing (3, Aggression) · ASH_109 T-6 Shuttle 1974 (4) · SOR_095 Battlefield Marine (2).
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php -d apc.enable_cli=1 -d xdebug.mode=off SWUSim/DevTools/tests/bot_greef_test.php
require __DIR__ . '/fixtures/bot_test_bootstrap.php';
foreach (['BotLegalActions', 'Custom/BotLookahead', 'Custom/BotEvaluator', 'Rl/CardTags', 'Custom/BotStyles', 'Custom/BotResourcing',
          'Custom/BotGuides', 'Custom/BotFallback', 'Custom/BotRules', 'BotHeuristic'] as $f) include_once "./SWUSim/$f.php";
foreach (['tokenfirst', 'deploybuff'] as $f) {
    $check(SWUBotVariantDisabled("no-$f") === [$f], "$f is switchable");
    $check(in_array($f, SWUBotFeatureGroups()['p42'] ?? [], true), "$f is in group p42");
}
$pick = function (string $variant = '') use (&$gameName) {
    $l = SWUBotLegalActions($gameName, 1);
    $p = SWUBotHeuristicChoose('midrange', (array)$l['actions'], $l, $variant);
    return strval($p['cardID'] ?? '');
};
$stackType = function (string $mz): string { return preg_match('/EffectStack-(\d+)$/', $mz, $m) ? strval((GetEffectStack()[intval($m[1])] ?? null)->TriggerType ?? '') : ''; };

// A) Greef (front, epic used) + Pegasus Tri-Wing played: both triggers — Greef's goes first.
$build(function ($b) {
    $b->MyLeader('ASH_017', true, false, true); $b->MyBase('SOR_020'); $b->FillResourcesForPlayer(1, 'SOR_095', 5); $b->WithCurrentRoundBeing(4);
    $b->WithCardInHandForPlayer(1, 'ASH_171');   // Aggression: off-aspect for Greef, 3 + 2
    for ($k = 0; $k < 30; $k++) { $b->WithCardInDeckForPlayer(1, 'SOR_095'); $b->WithCardInDeckForPlayer(2, 'SOR_095'); }
});
$act(1, 10002, 'myHand-0!FSM!');
$check($botCtx('midrange')['tooltip'] === 'Choose_trigger_to_resolve', 'A fixture: both triggers are on the stack; got ' . $botCtx('midrange')['tooltip']);
$check($stackType($pick('no-tokenfirst')) !== 'ASH_017', 'A fixture: today the Tri-Wing\'s trigger goes first; got ' . $stackType($pick('no-tokenfirst')));
$check($stackType($pick()) === 'ASH_017', 'A: Greef\'s Advantage first — the Tri-Wing then has an upgrade to defeat; got ' . $stackType($pick()));

// B) Deploy round: Greef can deploy (6 resources), a T-6 Shuttle 1974 and a Marine in hand — Greef deploys first (each unit then gets
// its Advantage free).
$build(function ($b) {
    $b->MyLeader('ASH_017', true); $b->MyBase('SOR_020'); $b->FillResourcesForPlayer(1, 'SOR_095', 6); $b->WithCurrentRoundBeing(6);
    $b->WithCardInHandForPlayer(1, 'ASH_109'); $b->WithCardInHandForPlayer(1, 'SOR_095');
    $b->WithGroundUnitForPlayer(2, 'SOR_095', false);
    for ($k = 0; $k < 30; $k++) { $b->WithCardInDeckForPlayer(1, 'SOR_095'); $b->WithCardInDeckForPlayer(2, 'SOR_095'); }
});
$check(str_starts_with($pick('no-deploybuff'), 'myHand-'), 'B fixture: today a unit is played before the deploy; got ' . $pick('no-deploybuff'));
$check($pick() === 'myLeader-0!CustomInput!DeployLeader:Unit', 'B: Greef deploys first; got ' . $pick());

// C) His Epic Action already spent (he cannot deploy): nothing to wait for — the unit is played.
$build(function ($b) {
    $b->MyLeader('ASH_017', true, false, true); $b->MyBase('SOR_020'); $b->FillResourcesForPlayer(1, 'SOR_095', 6); $b->WithCurrentRoundBeing(6);
    $b->WithCardInHandForPlayer(1, 'ASH_109'); $b->WithCardInHandForPlayer(1, 'SOR_095');
    $b->WithGroundUnitForPlayer(2, 'SOR_095', false);
    for ($k = 0; $k < 30; $k++) { $b->WithCardInDeckForPlayer(1, 'SOR_095'); $b->WithCardInDeckForPlayer(2, 'SOR_095'); }
});
$check(str_starts_with($pick(), 'myHand-'), 'C: no deploy available — the unit is played; got ' . $pick());

bot_test_finish();

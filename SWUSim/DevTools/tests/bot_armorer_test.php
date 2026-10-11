<?php
// Feature 'armorerpicks' (p42) — ASH_001 The Armorer, Steel Shapes Us: front "Action [Exhaust]: Play an upgrade from your resources on a
// unit that entered play this phase (paying its cost). If you do, resource the top card of your deck."; deployed "When Attack Ends: You
// may play an upgrade from your resources on a friendly unit. If you do, resource the top card of your deck."
// Leader audit 2026-10-08: her prompts ("Play_an_upgrade_from_resources_…", "Choose_a_unit_that_entered_this_phase", "Choose_a_friendly_
// unit") read as neutral and fell to the first-listed tiebreak — the upgrade was always myResources-0 (163/163) and the host the first unit;
// Preparation ("When Played: Exhaust attached unit") went on a READY unit while an exhausted one was there 65/112. Fixed: the upgrade is
// the one worth most to play, the host is the attach scorer's pick, and a host-exhausting upgrade prefers a unit already exhausted.
// Fixtures (dictionary-checked): ASH_001 The Armorer · JTL_028 Nabat Village · ASH_084 Arcana Star Map (1, +0/+3) · LAW_129 Mastery
//   (4, +3/+3) · ASH_228 Preparation (1, +2/+1, When Played: exhaust attached unit) · SOR_095 Battlefield Marine.
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php -d apc.enable_cli=1 -d xdebug.mode=off SWUSim/DevTools/tests/bot_armorer_test.php
require __DIR__ . '/fixtures/bot_test_bootstrap.php';
foreach (['BotLegalActions', 'Custom/BotLookahead', 'Custom/BotEvaluator', 'Rl/CardTags', 'Custom/BotStyles', 'Custom/BotResourcing',
          'Custom/BotGuides', 'Custom/BotFallback', 'Custom/BotRules', 'BotHeuristic'] as $f) include_once "./SWUSim/$f.php";
$check(SWUBotVariantDisabled('no-armorerpicks') === ['armorerpicks'], 'armorerpicks is switchable');
$check(in_array('armorerpicks', SWUBotFeatureGroups()['p42'] ?? [], true), 'armorerpicks is in group p42');
$pick = function (string $variant = '') use (&$gameName) {
    $l = SWUBotLegalActions($gameName, 1);
    $p = SWUBotHeuristicChoose('midrange', (array)$l['actions'], $l, $variant);
    return strval($p['cardID'] ?? '');
};
$resCard = function (string $mz): string { return preg_match('/^myResources-(\d+)$/', $mz, $m) ? strval(GetResources(1)[intval($m[1])]->CardID ?? '') : ''; };
$unitIsReady = function (string $mz): ?bool { $o = preg_match('/^myGroundArena-(\d+)$/', $mz, $m) ? (GetZone('myGroundArena')[intval($m[1])] ?? null) : null; return $o === null ? null : intval($o->Status) === 1; };

// A) Front: a Marine just played (entered this phase); the resources hold Arcana Star Map (listed first) and Mastery. Today the Star Map;
// fixed, Mastery (+3/+3).
$build(function ($b) {
    $b->MyLeader('ASH_001', true); $b->MyBase('JTL_028'); $b->WithCurrentRoundBeing(6);
    $b->FillResourcesForPlayer(1, 'ASH_084', 1); $b->FillResourcesForPlayer(1, 'LAW_129', 1); $b->FillResourcesForPlayer(1, 'SOR_095', 7);
    $b->WithCardInHandForPlayer(1, 'SOR_095');
    $b->WithGroundUnitForPlayer(2, 'SOR_095', false);
    for ($k = 0; $k < 30; $k++) { $b->WithCardInDeckForPlayer(1, 'SOR_095'); $b->WithCardInDeckForPlayer(2, 'SOR_095'); }
});
$act(1, 10002, 'myHand-0!FSM!');
$act(2, 10001, 'myHealth-0!CustomInput!Pass');
$act(1, 10001, 'myLeader-0!CustomInput!LeaderAbility');
$check(str_starts_with($botCtx('midrange')['tooltip'], 'Play_an_upgrade_from_resources'), 'A fixture: the upgrade pick is pending; got ' . $botCtx('midrange')['tooltip']);
$check($resCard($pick('no-armorerpicks')) === 'ASH_084', 'A fixture: today the first-listed Arcana Star Map');
$check($resCard($pick()) === 'LAW_129', 'A: Mastery (+3/+3); got ' . $resCard($pick()));

// B) Deployed: after the Armorer's attack, Preparation goes on a friendly unit — a READY Marine (listed first) or an EXHAUSTED one. Its
// "exhaust attached unit" costs the exhausted one nothing: fixed, the exhausted Marine.
$build(function ($b) {
    $b->MyLeader('ASH_001', true, true, true, 'unit'); $b->MyBase('JTL_028'); $b->WithCurrentRoundBeing(6);
    $b->FillResourcesForPlayer(1, 'ASH_228', 1); $b->FillResourcesForPlayer(1, 'SOR_095', 6);
    $b->WithGroundUnitForPlayer(1, 'SOR_095', true); $b->WithGroundUnitForPlayer(1, 'SOR_095', false);
    for ($k = 0; $k < 30; $k++) { $b->WithCardInDeckForPlayer(1, 'SOR_095'); $b->WithCardInDeckForPlayer(2, 'SOR_095'); }
});
$arm = ''; foreach (GetZone('myGroundArena') as $i => $o) if (strval($o->CardID) === 'ASH_001') $arm = "myGroundArena-$i";
$raiseAttack(1, $arm);
for ($k = 0; $k < 3 && $botCtx('midrange')['kind'] === 'decision' && $botCtx('midrange')['tooltip'] !== 'Choose_a_friendly_unit'; $k++) {
    $act(1, 100, $botCtx('midrange')['tooltip'] === 'Play_an_upgrade_from_your_resources?' || str_starts_with($botCtx('midrange')['tooltip'], 'Choose_an_upgrade') ? 'myResources-0' : 'YES');
}
$check($botCtx('midrange')['tooltip'] === 'Choose_a_friendly_unit', 'B fixture: the host pick is pending; got ' . $botCtx('midrange')['tooltip']);
$check($unitIsReady($pick('no-armorerpicks')) === true, 'B fixture: today the ready Marine (listed first) is exhausted by Preparation');
$check($unitIsReady($pick()) === false, 'B: Preparation goes on the exhausted Marine; got ' . $pick());

bot_test_finish();

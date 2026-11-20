<?php
// Named Action abilities — the bot's legal-action enumeration (infrastructure, not a heuristic): one legal action per
// USABLE Action entry on its own units, "<mz>!CustomInput!Activate:<id>"; the verb classifiers read the id form like
// the bare one; and the bot prices an entry by the card that GIVES it, not by the unit using it.
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php -d apc.enable_cli=1 -d xdebug.mode=off SWUSim/DevTools/tests/bot_named_actions_test.php
require __DIR__ . '/fixtures/bot_test_bootstrap.php';
include_once './SWUSim/BotLegalActions.php';
$ids = fn($legal) => array_map(fn($a) => strval($a['cardID'] ?? ''), (array)($legal['actions'] ?? []));

// Deployed Chewbacca wearing Improvised Identity: BOTH Actions are legal actions, each by id.
$build(function ($b) {
    $b->WithGroundUnitForPlayer(1, 'HMW_009')->WithUpgradesOnGroundUnitForPlayer(1, 0, [GameStateBuilder::Upgrade('ASH_230', 1)]);
    $b->WithGroundUnitForPlayer(2, 'SOR_095');
});
$cards = $ids(SWUBotLegalActions($gameName, 1));
$check(in_array('myGroundArena-0!CustomInput!Activate:HMW_009.u0', $cards, true), "Chewbacca's own Action is a legal action by id");
$check(in_array('myGroundArena-0!CustomInput!Activate:ASH_230.0', $cards, true), "Improvised Identity's Action is a legal action by id");
$check(!in_array('myGroundArena-0!CustomInput!Activate', $cards, true), 'no bare Activate for a unit with named entries');

// Exhausted Crosshair with 2 resources: only the usable [2 resources] Action.
$build(function ($b) {
    $b->FillResourcesForPlayer(1, 'SOR_095', 2);
    $b->WithGroundUnitForPlayer(1, 'SHD_087', false);
    $b->WithGroundUnitForPlayer(2, 'SOR_095');
});
$unitActs = array_values(array_filter($ids(SWUBotLegalActions($gameName, 1)), fn($c) => str_contains($c, '!CustomInput!Activate')));
$check($unitActs === ['myGroundArena-0!CustomInput!Activate:SHD_087.0'], 'exhausted Crosshair: only SHD_087.0 (' . implode(',', $unitActs) . ')');

// The classifiers: the id form is the same kind as the bare one.
foreach ([['myGroundArena-0!CustomInput!Activate', 'unit-action'], ['myGroundArena-0!CustomInput!Activate:ASH_230.0', 'unit-action'],
          ['myLeader-0!CustomInput!LeaderAbility', 'leader-ability'], ['myLeader-0!CustomInput!LeaderAbility:JTL_014.0', 'leader-ability'],
          ['myBase-0!CustomInput!EpicAction', 'base-epic'], ['myBase-0!CustomInput!EpicAction:HMW_095.0', 'base-epic']] as [$c, $k]) {
    $got = SWUBotActionKind(['mode' => 10001, 'cardID' => $c]);
    $check($got === $k, "SWUBotActionKind($c) = $k (got $got)");
}
$check(SWUBotActionAbilityId(['mode' => 10001, 'cardID' => 'myGroundArena-0!CustomInput!Activate:ASH_230.0']) === 'ASH_230.0', 'SWUBotActionAbilityId reads the id');
$check(SWUBotActionAbilityId(['mode' => 10001, 'cardID' => 'myGroundArena-0!CustomInput!Activate']) === '', 'SWUBotActionAbilityId: bare form → ""');

// The source text an entry is priced by is the card that GIVES it.
$build(function ($b) {
    $b->WithGroundUnitForPlayer(1, 'HMW_009')->WithUpgradesOnGroundUnitForPlayer(1, 0, [GameStateBuilder::Upgrade('ASH_230', 1)]);
});
$t = _SWUBotActionSourceText(1, ['mode' => 10001, 'cardID' => 'myGroundArena-0!CustomInput!Activate:ASH_230.0']);
$check(str_contains($t, 'Search the top 3 cards'), "Improvised Identity's entry is priced by Improvised Identity's text");
$t = _SWUBotActionSourceText(1, ['mode' => 10001, 'cardID' => 'myGroundArena-0!CustomInput!Activate:HMW_009.u0']);
$check(str_contains($t, 'Attack with a unit, even if'), "Chewbacca's own entry is priced by his deployed side");

// The base: one legal action per USABLE base entry, "myBase-0!CustomInput!EpicAction:<id>" (Mystic Monastery + Carbonite
// Chamber → both; no bare EpicAction).
$build(function ($b) {
    $b->MyBase('LOF_022');
    $b->WithUpgradeOnBaseForPlayer(1, 'HMW_095');
    $b->WithGroundUnitForPlayer(2, 'SOR_095');
});
$baseActs = array_values(array_filter($ids(SWUBotLegalActions($gameName, 1)), fn($c) => str_contains($c, 'CustomInput!EpicAction')));
sort($baseActs);
$check($baseActs === ['myBase-0!CustomInput!EpicAction:HMW_095.0', 'myBase-0!CustomInput!EpicAction:LOF_022.0'], 'base: one action per usable entry (' . implode(',', $baseActs) . ')');

// A leader: one legal action per USABLE leader entry, "myLeader-0!CustomInput!LeaderAbility:<id>" (no bare form).
$build(function ($b) { $b->MyLeader('JTL_014'); $b->FillResourcesForPlayer(1, 'SOR_095', 3); $b->WithCardInHandForPlayer(1, 'SOR_046'); });
$leaderActs = array_values(array_filter($ids(SWUBotLegalActions($gameName, 1)), fn($c) => str_contains($c, 'CustomInput!LeaderAbility')));
$check($leaderActs === ['myLeader-0!CustomInput!LeaderAbility:JTL_014.0'], 'leader: one action per usable entry (' . implode(',', $leaderActs) . ')');

bot_test_finish();

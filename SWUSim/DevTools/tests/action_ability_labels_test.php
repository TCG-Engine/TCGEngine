<?php
// Named Action abilities — leader-slot labels (owner, 2026-11-03): a leader's Action is labelled "Leader Ability", and a
// hand-written name in $actionAbilityLabelOverrides (SWUSim/Custom/ActionAbilities.php) replaces it for that one id.
// Units and bases keep the card title (Tests/Cases/core/NamedActions_UnitEntries.md::UnitActionLabels_KeepTheCardTitle).
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php -d apc.enable_cli=1 -d xdebug.mode=off SWUSim/DevTools/tests/action_ability_labels_test.php
require __DIR__ . '/fixtures/bot_test_bootstrap.php';
global $actionAbilityLabelOverrides;

$build(fn(GameStateBuilder $b) => null);   // the 'grw' setup's leader is SOR_014 Sabine Wren: one leader-slot Action
$label = function (string $id): ?string {
    foreach (SWUActionEntriesFor(1, 'myLeader-0') as $e) if ($e['id'] === $id) return $e['label'];
    return null;
};
$check(SWUGetLeaderByIndex(1, 0)->CardID === 'SOR_014', 'the board\'s leader is SOR_014');

$check($label('SOR_014.0') === 'Leader Ability', "no override: the leader's Action reads \"Leader Ability\" (got '" . $label('SOR_014.0') . "')");

$saved = $actionAbilityLabelOverrides;
$actionAbilityLabelOverrides['SOR_014.0'] = 'Art of Rebellion';
$check($label('SOR_014.0') === 'Art of Rebellion', "an override replaces the fallback (got '" . $label('SOR_014.0') . "')");
$actionAbilityLabelOverrides = $saved;

bot_test_finish();

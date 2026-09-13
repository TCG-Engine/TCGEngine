<?php
// Phase 1b part 2, Task 1 — per-seat feature switches (SWUSim/Custom/BotFeatures.php) and the variant profiles
// the strength test plays against each other.
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php -d apc.enable_cli=1 -d xdebug.mode=off SWUSim/DevTools/tests/bot_features_test.php
require __DIR__ . '/fixtures/bot_test_bootstrap.php';
include_once './SWUSim/BotLegalActions.php';
include_once './SWUSim/BotHeuristic.php';

$check(SWUBotVariantDisabled('') === [], 'the bare profile turns nothing off');
$check(SWUBotVariantDisabled('base') === SWUBotFeatureList(), '@base turns every feature of this plan off');
$check(SWUBotVariantDisabled('no-such-feature') === null, 'an unknown variant is refused (never silently the full stack)');
foreach (SWUBotFeatureList() as $f) $check(SWUBotVariantDisabled("no-$f") === [$f], "@no-$f turns only $f off");
foreach (['aggro', 'normal', 'control'] as $s) {
    foreach (SWUBotVariants() as $v) $check(isset($GLOBALS['SWUBotChoosers']["heuristic-$s@$v"]), "profile heuristic-$s@$v is registered");
}
$build(function ($b) { $b->MyLeader('SOR_014', false); });
$legal = SWUBotLegalActions($gameName, 1);
SWUBotHeuristicChoose('normal', (array)$legal['actions'], $legal, 'base');
$check(($GLOBALS['SWUBotLastDecisionDisabled'] ?? null) === SWUBotFeatureList(), 'a decision under @base runs with every feature off');
$check(SWUBotFeatureOn('splits'), '… and the switches are restored when it returns (outside a decision, everything is on)');
SWUBotHeuristicChoose('normal', (array)$legal['actions'], $legal);
$check(($GLOBALS['SWUBotLastDecisionDisabled'] ?? null) === [] && SWUBotFeatureOn('anything'), 'the next decision under the bare profile runs with everything on');

bot_test_finish();

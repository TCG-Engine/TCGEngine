<?php
// Feature 'deploystrike' (p39) — LAW_008 Director Krennic deploys only when his When Deployed strike KILLS something. His deployed side:
// "When Deployed: Another friendly unit deals damage equal to its power to an enemy unit." Owner, 2026-10-06: Krennic Blue — "Deploy
// when you have another body that can deal big damage to the enemy"; Krennic Splash — "when the When Deployed ping kills".
// Traced (480 Krennic Blue games, baseline kb1): deployed the round he could (7 resources) — R6 in 305 of 366 deploys.
//   · hold the deploy (score below PASS) unless another friendly unit's power defeats an unshielded enemy unit;
//   · the same hold in the 'plotdeploy' guide, which otherwise deploys to Plot units out of the resource row.
// Fixtures (dictionary-checked): LAW_008 Director Krennic · ASH_019 · ASH_116 Ant Droid 1/2 · SOR_095 Battlefield Marine 3/3 · SOR_T02
//   Shield · ASH_009 Ahsoka Tano · LAW_097 (resources)
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php -d apc.enable_cli=1 -d xdebug.mode=off SWUSim/DevTools/tests/bot_deploystrike_test.php
require __DIR__ . '/fixtures/bot_test_bootstrap.php';
foreach (['BotLegalActions', 'Custom/BotLookahead', 'Custom/BotEvaluator', 'Rl/CardTags', 'Custom/BotStyles', 'Custom/BotResourcing',
          'Custom/BotGuides', 'Custom/BotFallback', 'Custom/BotRules', 'BotHeuristic'] as $f) include_once "./SWUSim/$f.php";
$check(SWUBotVariantDisabled('no-deploystrike') === ['deploystrike'], 'deploystrike is switchable');
$check(in_array('deploystrike', SWUBotFeatureGroups()['p39'] ?? [], true), 'deploystrike is in group p39');
// R6, 7 resources, Krennic undeployed, empty hand; my exhausted $mine; their Marine (shielded if $shield). The bot's free-play pick.
$pick = function (array $mine, bool $shield, string $variant) use ($build, &$gameName) {
    $build(function ($b) use ($mine, $shield) {
        $b->MyLeader('LAW_008', true); $b->MyBase('ASH_019'); $b->TheirLeader('ASH_009', false);
        $b->WithCurrentRoundBeing(6); $b->FillResourcesForPlayer(1, 'LAW_097', 7);
        foreach ($mine as $c) $b->WithGroundUnitForPlayer(1, $c, false);
        $b->WithGroundUnitForPlayer(2, 'SOR_095', false);
        if ($shield) $b->WithUpgradesOnGroundUnitForPlayer(2, 0, [GameStateBuilder::Upgrade('SOR_T02', 2)]);
    });
    $l = SWUBotLegalActions($gameName, 1);
    $p = SWUBotHeuristicChoose('softcontrol', (array)$l['actions'], $l, $variant);
    return str_contains(strval($p['cardID'] ?? ''), 'DeployLeader') ? 'deploy' : strval($p['cardID'] ?? '');
};
// A) Only an Ant Droid (1 power) to strike with: the strike kills nothing. Today: deploy. Fixed: wait.
$check($pick(['ASH_116'], false, 'no-deploystrike') === 'deploy', 'A fixture: today Krennic deploys as soon as he can; got ' . $pick(['ASH_116'], false, 'no-deploystrike'));
$check($pick(['ASH_116'], false, '') !== 'deploy', 'A: the 1-power strike kills nothing — Krennic waits; got ' . $pick(['ASH_116'], false, ''));
// B) A Battlefield Marine (3 power) kills their 3/3: deploy.
$check($pick(['SOR_095'], false, '') === 'deploy', 'B: the Marine\'s strike kills — Krennic deploys; got ' . $pick(['SOR_095'], false, ''));
// C) Their Marine is shielded: the strike only pops the Shield — wait.
$check($pick(['SOR_095'], true, '') !== 'deploy', 'C: a shielded target survives the strike — Krennic waits; got ' . $pick(['SOR_095'], true, ''));

bot_test_finish();

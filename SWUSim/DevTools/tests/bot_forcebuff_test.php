<?php
// Feature 'forcebuff' (p42) — LOF_008 Obi-Wan Kenobi, Courage Makes Heroes: "Action [Exhaust, use the Force]: Give an Experience token to
// a unit without an Experience token on it." The 'force' gate (Talzin's: "never onto an empty enemy board", and "held when its -N/-N
// kills nothing while another card needs the Force") was applied to EVERY use-the-Force Action. Leader audit 2026-10-08: Obi-Wan was used
// 0 times in 2,065 empty-enemy-board spots, and 0.9-4.5% with another Force card in hand — his Action never needs an enemy and kills
// nothing by design. Fixed: the gate judges HOSTILE Force Actions only (a "-N/-N", damage or defeat).
// Fixtures (dictionary-checked): LOF_008 Obi-Wan (front) · LOF_020 base · SOR_095 Battlefield Marine · LOF_035 (a Force card).
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php -d apc.enable_cli=1 -d xdebug.mode=off SWUSim/DevTools/tests/bot_forcebuff_test.php
require __DIR__ . '/fixtures/bot_test_bootstrap.php';
foreach (['BotLegalActions', 'Custom/BotLookahead', 'Custom/BotEvaluator', 'Rl/CardTags', 'Custom/BotStyles', 'Custom/BotResourcing',
          'Custom/BotGuides', 'Custom/BotFallback', 'Custom/BotRules', 'BotHeuristic'] as $f) include_once "./SWUSim/$f.php";
$check(SWUBotVariantDisabled('no-forcebuff') === ['forcebuff'], 'forcebuff is switchable');
$check(in_array('forcebuff', SWUBotFeatureGroups()['p42'] ?? [], true), 'forcebuff is in group p42');
$pick = function (string $variant = '') use (&$gameName) {
    $l = SWUBotLegalActions($gameName, 1);
    $p = SWUBotHeuristicChoose('midrange', (array)$l['actions'], $l, $variant);
    return strval($p['cardID'] ?? '');
};
$ability = 'myLeader-0!CustomInput!LeaderAbility';
$board = function (bool $enemy, array $hand = []) use ($build) {
    $build(function ($b) use ($enemy, $hand) {
        $b->MyLeader('LOF_008', true); $b->MyBase('LOF_020'); $b->WithForceForPlayer(1); $b->WithCurrentRoundBeing(4);
        $b->WithGroundUnitForPlayer(1, 'SOR_095', false);   // exhausted: no attack competes
        if ($enemy) $b->WithGroundUnitForPlayer(2, 'SOR_095', false);
        foreach ($hand as $c) $b->WithCardInHandForPlayer(1, $c);
        for ($k = 0; $k < 30; $k++) { $b->WithCardInDeckForPlayer(1, 'SOR_095'); $b->WithCardInDeckForPlayer(2, 'SOR_095'); }
    });
};
// A) Empty enemy board: Obi-Wan still gives my Marine an Experience token (today: refused).
$board(false);
$check($pick('no-forcebuff') !== $ability, 'A fixture: today the empty enemy board refuses Obi-Wan');
$check($pick() === $ability, 'A: an empty enemy board does not stop a friendly Experience; got ' . $pick());
// B) Another Force card in hand: the Experience is not held as a "-N/-N that kills nothing" (today: held below the initiative).
$board(true, ['LOF_035']);
$check($pick('no-forcebuff') !== $ability, 'B fixture: today another Force card holds Obi-Wan');
$check($pick() === $ability, 'B: Obi-Wan is not held as a non-killing debuff; got ' . $pick());

bot_test_finish();

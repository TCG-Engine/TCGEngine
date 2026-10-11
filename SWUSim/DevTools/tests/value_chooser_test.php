<?php
// The @value chooser (SWUSim/Rl/SwuValue.php) — spec docs/superpowers/specs/2026-09-19-swusim-value-model-design.md §7.
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php -d apc.enable_cli=1 -d xdebug.mode=off SWUSim/DevTools/tests/value_chooser_test.php
require __DIR__ . '/fixtures/bot_test_bootstrap.php';
include_once './SWUSim/BotLegalActions.php';
include_once './SWUSim/BotHeuristic.php';

$names = SWUValueFeatureNames();
$model = function (array $w, ?string $ver = null) use ($names) {
    $path = sys_get_temp_dir() . '/value_model_test_' . getmypid() . '_' . md5(json_encode($w) . $ver) . '.json';
    file_put_contents($path, json_encode(['version' => 'swu-value-v1', 'featureVersion' => $ver ?? SWUValueFeatureVersion(),
        'names' => $names, 'means' => array_fill(0, count($names), 0.0), 'stds' => array_fill(0, count($names), 1.0),
        'weights' => array_map(fn($n) => floatval($w[$n] ?? 0), $names), 'bias' => 0.0]));
    return $path;
};
$pick = function (string $variant) use (&$gameName) {
    SWUBotResetCoverage();
    $legal = SWUBotLegalActions($gameName, 1);
    $p = SWUBotHeuristicChoose('softcontrol', (array)$legal['actions'], $legal, $variant);
    return [$p === null ? null : strval($p['cardID']), array_keys($GLOBALS['SWUBotCoverage'][1] ?? [])];
};
$check(SWUBotVariantDisabled('value') === [] && isset($GLOBALS['SWUBotChoosers']['heuristic-softcontrol@value']), '@value is a registered, feature-neutral variant');

// A board the fallback decides (no rule fires): a ready Marine, an enemy Wampa, cards to play.
$board = function ($b) {
    $b->MyLeader('SOR_014', false, false, true);
    $b->FillResourcesForPlayer(1, 'SOR_095', 4);
    $b->WithGroundUnitForPlayer(1, 'SOR_095', true);
    $b->WithGroundUnitForPlayer(2, 'SOR_164', false);
    $b->WithCardInHandForPlayer(1, 'SOR_095'); $b->WithCardInHandForPlayer(1, 'SEC_080');
};
$build($board);
[$plain, $plainCov] = $pick('');
$check(in_array('fallback', $plainCov, true), 'fixture: the plain stack decides this at the fallback');

// No model file → identical to the plain heuristic, and 'value:off' recorded.
putenv('SWU_VALUE_MODEL=/nonexistent/value.json');
[$p, $cov] = $pick('value');
$check($p === $plain && in_array('value:off', $cov, true), 'missing model: plays the heuristic pick, records value:off');
// Version mismatch → refused.
putenv('SWU_VALUE_MODEL=' . $model(['their_hp_left' => -1.0], 'deadbeef0000'));
[$p, $cov] = $pick('value');
$check($p === $plain && in_array('value:off', $cov, true), 'featureVersion mismatch: refused, heuristic pick');
// Toy model "lower their base HP": the attack on the base (Marine, 3) is the best move.
putenv('SWU_VALUE_MODEL=' . $model(['their_hp_left' => -1.0]));
[$p, $cov] = $pick('value');
$check($p === 'myGroundArena-0!FSM!' && in_array('value:chose', $cov, true), 'toy model (their HP down): attack');
$check(($GLOBALS['SWUBotPlan'][1][0]['answer'] ?? '') === 'theirBase-0', 'the chosen line (target: their base) is the plan');
// Toy model "more units on my board": play a unit from hand.
putenv('SWU_VALUE_MODEL=' . $model(['my_units_ground' => 1.0]));
[$p, $cov] = $pick('value');
$check(str_starts_with(strval($p), 'myHand-'), 'toy model (my units up): play a unit');
// A model may use a SUBSET of the features (the trainer drops collinear columns), scored by name.
$sub = sys_get_temp_dir() . '/value_model_test_' . getmypid() . '_subset.json';
file_put_contents($sub, json_encode(['version' => 'swu-value-v1', 'featureVersion' => SWUValueFeatureVersion(),
    'names' => ['their_hp_left'], 'means' => [0.0], 'stds' => [1.0], 'weights' => [-1.0], 'bias' => 0.0]));
putenv("SWU_VALUE_MODEL=$sub");
[$p, $cov] = $pick('value');
$check($p === 'myGroundArena-0!FSM!' && in_array('value:chose', $cov, true), 'a one-feature subset model is accepted and scored by name');
file_put_contents($sub, json_encode(['version' => 'swu-value-v1', 'featureVersion' => SWUValueFeatureVersion(),
    'names' => ['no_such_feature'], 'means' => [0.0], 'stds' => [1.0], 'weights' => [-1.0], 'bias' => 0.0]));
clearstatcache();
[$p, $cov] = $pick('value');
$check(in_array('value:off', $cov, true), 'a model naming an unknown feature is refused');
// Plain profiles never record value:* keys.
[$p, $cov] = $pick('');
$check(!array_filter($cov, fn($k) => str_starts_with($k, 'value:')), 'no value:* coverage without @value');
putenv('SWU_VALUE_MODEL');
bot_test_finish();

<?php
// RL Phase 3, Task 2 — the learned layer (the '@rl' variant; SWUSim/Rl/SwuPolicy.php). It replaces ONLY the
// fallback's choice (spec Section 2: covered decisions are never learned), keyed by swu-v1 state + move keys.
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php -d apc.enable_cli=1 -d xdebug.mode=off SWUSim/DevTools/tests/rl_policy_test.php
require __DIR__ . '/fixtures/bot_test_bootstrap.php';
include_once './SWUSim/BotLegalActions.php';
include_once './SWUSim/BotHeuristic.php';
$tmp = sys_get_temp_dir() . '/rl_policy_test_' . getmypid();
@mkdir($tmp);
$stack = function (string $style, int $seat = 1, string $variant = '') use (&$gameName) {
    SWUBotResetCoverage(); $legal = SWUBotLegalActions($gameName, $seat);
    $p = SWUBotHeuristicChoose($style, (array)$legal['actions'], $legal, $variant);
    return [$p === null ? null : strval($p['cardID']), array_keys($GLOBALS['SWUBotCoverage'][$seat] ?? [])];
};
$writePolicy = function (array $table) use ($tmp) {
    $path = $tmp . '/policy_' . md5(json_encode($table)) . '.json';
    file_put_contents($path, json_encode(['version' => 'swu-v1', 'batches' => 1, 'games' => 1, 'table' => $table]));
    putenv("SWU_RL_POLICY=$path");
    return $path;
};
$env = function (string $mode, float $eps = 0.1, string $episode = '', string $seed = 's1') {
    putenv("SWU_RL_MODE=$mode"); putenv("SWU_RL_EPSILON=$eps"); putenv("SWU_RL_EPISODE=$episode"); putenv("SWU_RL_SEED=$seed");
    $GLOBALS['SWURlDecisionN'] = [];
};
$check(SWUBotVariantDisabled('rl') === [] && isset($GLOBALS['SWUBotChoosers']['heuristic-normal@rl']), 'the @rl variant turns no feature off and is registered');

// A board the FALLBACK decides: a Marine in hand (affordable), the initiative already taken by the opponent — play
// it or pass. The heuristic plays it.
$quiet = function () use ($build) { $build(function ($b) { $b->MyLeader('SOR_014', false, false, true); $b->FillResourcesForPlayer(1, 'SOR_095', 4);
    $b->WithCardInHandForPlayer(1, 'SOR_095'); $b->WithInitiativePlayerBeing(2); $b->WithInitiativeClaimed(); }); };
$quiet();
$ctx = $botCtx('normal');
$s = SWURlStateKey($ctx);
[$hp, $hcov] = $stack('normal');
$check($hp === 'myHand-0!FSM!' && in_array('fallback', $hcov, true), 'fixture: the fallback plays the Marine; got ' . $hp . ' ' . json_encode($hcov));
$playKey = SWURlMoveKey($ctx, ['cardID' => 'myHand-0!FSM!', 'mode' => 10002]);

// (a) confident evidence that Pass is better → @rl (play mode) passes; the plain heuristic still takes the initiative.
$writePolicy(['normal' => [$s => ['pass' => [20, 0.4], $playKey => [20, -0.2]]]]);
$env('play');
[$pick, $cov] = $stack('normal', 1, 'rl');
$check($pick === 'myHealth-0!CustomInput!Pass' && in_array('rl:override', $cov, true), '@rl overrides the fallback on confident evidence; got ' . $pick . ' ' . json_encode($cov));
$check($stack('normal')[0] === 'myHand-0!FSM!', 'the plain heuristic profile is unchanged');

// (b) the same moves under-visited → the fallback's pick.
$writePolicy(['normal' => [$s => ['pass' => [3, 0.9], $playKey => [3, -0.9]]]]);
[$pick, $cov] = $stack('normal', 1, 'rl');
$check($pick === 'myHand-0!FSM!' && in_array('rl:keep', $cov, true), 'under-visited evidence keeps the fallback\'s pick');

// (c) a RULE decision is never overridden or logged: lethal now (5 left, 7 power on board).
$build(function ($b) { $b->MyLeader('SOR_014', false); $b->TheirBase('SOR_020', 25);
    $b->WithGroundUnitForPlayer(1, 'SOR_095', true); $b->WithGroundUnitForPlayer(1, 'LOF_084', true); });
$lctx = $botCtx('control');
$ls = SWURlStateKey($lctx);
$writePolicy(['control' => [$ls => ['pass' => [99, 1.0]]]]);
$episode = $tmp . '/ep_rule.jsonl'; @unlink($episode);
$env('train', 1.0, $episode);
[$pick, $cov] = $stack('control', 1, 'rl');
$check(str_ends_with(strval($pick), '!FSM!') && in_array('rule:lethal-now', $cov, true), 'the lethal-now rule still decides; got ' . $pick);
$check(!file_exists($episode) || trim(strval(file_get_contents($episode))) === '', 'nothing is logged for a rule decision');

// (d) train mode, ε = 1: always explores, logs the decision, and the same seed repeats the same choice.
$quiet();
$writePolicy([]);
$episode = $tmp . '/ep_train.jsonl'; @unlink($episode);
$env('train', 1.0, $episode, 'seedA');
[$p1, $cov] = $stack('normal', 1, 'rl');
$env('train', 1.0, $episode, 'seedA');
[$p2] = $stack('normal', 1, 'rl');
$lines = array_values(array_filter(explode("\n", strval(@file_get_contents($episode)))));
$rec = json_decode($lines[0] ?? '{}', true);
$check(in_array('rl:explore', $cov, true) && $p1 === $p2, 'exploration is deterministic for a seed');
$check(count($lines) === 2 && ($rec['seat'] ?? 0) === 1 && ($rec['style'] ?? '') === 'normal' && ($rec['s'] ?? '') === $s
    && in_array($rec['m'] ?? '', ['pass', $playKey], true), 'each learned decision is logged; got ' . ($lines[0] ?? 'nothing'));

// (e) no policy file → @rl plays exactly the fallback.
putenv('SWU_RL_POLICY='); $env('play');
$check($stack('normal', 1, 'rl')[0] === 'myHand-0!FSM!', 'no policy: the fallback\'s pick');

// (f)–(h) The significance rule (SWU_RL_Z > 0; RL run 1 showed the margin rule chasing noise). Override only when the
// fallback's OWN move is measured and the alternative beats it by more than Z standard errors (var ≈ 1 − mean²).
$quiet();
putenv('SWU_RL_Z=3'); putenv('SWU_RL_MIN_VISITS=200'); $env('play');
$writePolicy(['normal' => [$s => ['pass' => [300, 0.4], $playKey => [300, -0.2]]]]);   // diff 0.6 vs 3·SE ≈ 0.23
[$pick, $cov] = $stack('normal', 1, 'rl');
$check($pick === 'myHealth-0!CustomInput!Pass' && in_array('rl:override', $cov, true), 'Z=3: a significant advantage overrides; got ' . $pick);
$writePolicy(['normal' => [$s => ['pass' => [300, 0.05], $playKey => [300, 0.0]]]]);   // diff 0.05 < 0.23
$check($stack('normal', 1, 'rl')[0] === 'myHand-0!FSM!', 'Z=3: a difference inside the noise keeps the fallback\'s pick');
$writePolicy(['normal' => [$s => ['pass' => [300, 0.9]]]]);                               // the fallback's move unmeasured
$check($stack('normal', 1, 'rl')[0] === 'myHand-0!FSM!', 'Z=3: an unmeasured fallback move is never overridden');
putenv('SWU_RL_Z='); putenv('SWU_RL_MIN_VISITS=');

array_map('unlink', glob($tmp . '/*') ?: []); @rmdir($tmp);
bot_test_finish();

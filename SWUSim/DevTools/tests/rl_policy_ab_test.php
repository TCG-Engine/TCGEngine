<?php
// RL — the swu-v2 policy (randomised comparison; SWUSim/DevTools/rl/rl_ab.py) in the learned layer
// (SWUSim/Rl/SwuPolicy.php). RL run 3 (2026-09-15) showed the swu-v1 table is biased by the heuristic's own choices,
// so v2 overrides only where EXPLORATION-forced games beat the heuristic's own games in the same state. That needs
// each training record to say how its move was chosen (keep | explore | override), the fallback's move, and the legal
// moves. Section (d)–(g): a v2 policy overrides only on a significant effect with enough samples in both arms.
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php -d apc.enable_cli=1 -d xdebug.mode=off SWUSim/DevTools/tests/rl_policy_ab_test.php
require __DIR__ . '/fixtures/bot_test_bootstrap.php';
include_once './SWUSim/BotLegalActions.php';
include_once './SWUSim/BotHeuristic.php';
$tmp = sys_get_temp_dir() . '/rl_policy_ab_test_' . getmypid();
@mkdir($tmp);
$stack = function (string $style, int $seat = 1, string $variant = '') use (&$gameName) {
    SWUBotResetCoverage(); $legal = SWUBotLegalActions($gameName, $seat);
    $p = SWUBotHeuristicChoose($style, (array)$legal['actions'], $legal, $variant);
    return [$p === null ? null : strval($p['cardID']), array_keys($GLOBALS['SWUBotCoverage'][$seat] ?? [])];
};
$env = function (string $mode, float $eps = 0.1, string $episode = '', string $seed = 's1') {
    putenv("SWU_RL_MODE=$mode"); putenv("SWU_RL_EPSILON=$eps"); putenv("SWU_RL_EPISODE=$episode"); putenv("SWU_RL_SEED=$seed");
    $GLOBALS['SWURlDecisionN'] = [];
};
$writeAb = function (array $ab) use ($tmp) {
    $path = $tmp . '/policy_' . md5(json_encode($ab)) . '.json';
    file_put_contents($path, json_encode(['version' => 'swu-v2', 'batches' => 1, 'games' => 1, 'ab' => $ab]));
    putenv("SWU_RL_POLICY=$path");
};
// [tw, twg, tw2, nc, cg] for n treatment samples at mean qT (weight 1) and n control samples at mean qC.
$entry = fn(int $n, float $qT, int $nc, float $qC) => [$n, $n * $qT, $n, $nc, $nc * $qC];

// The fallback decides this board: a Marine in hand, the initiative taken by the opponent — it plays the Marine.
$build(function ($b) { $b->MyLeader('SOR_014', false, false, true); $b->FillResourcesForPlayer(1, 'SOR_095', 4);
    $b->WithCardInHandForPlayer(1, 'SOR_095'); $b->WithInitiativePlayerBeing(2); $b->WithInitiativeClaimed(); });
$ctx = $botCtx('normal');
$s = SWURlStateKey($ctx);
$playKey = SWURlMoveKey($ctx, ['cardID' => 'myHand-0!FSM!', 'mode' => 10002]);
$check($stack('normal')[0] === 'myHand-0!FSM!', 'fixture: the fallback plays the Marine');

// (a) A kept decision records how = keep, the fallback's move, and the legal moves.
putenv('SWU_RL_POLICY=');
$episode = $tmp . '/ep_keep.jsonl'; @unlink($episode);
$env('train', 0.0, $episode);
$stack('normal', 1, 'rl');
$rec = json_decode(strval(@file_get_contents($episode)), true) ?? [];
$check(($rec['how'] ?? '') === 'keep' && ($rec['m'] ?? '') === $playKey && ($rec['fb'] ?? '') === $playKey,
    'a kept decision logs how=keep and the fallback\'s move; got ' . json_encode($rec));
$L = $rec['L'] ?? [];
$check(is_array($L) && in_array('pass', $L, true) && in_array($playKey, $L, true) && $L === array_values(array_unique($L)),
    'it logs every legal move once; got ' . json_encode($L));

// (b) An explored decision records how = explore — the fallback's move is still the heuristic's.
$episode = $tmp . '/ep_explore.jsonl'; @unlink($episode);
$env('train', 1.0, $episode, 'seedB');
$stack('normal', 1, 'rl');
$rec = json_decode(strval(@file_get_contents($episode)), true) ?? [];
$check(($rec['how'] ?? '') === 'explore' && ($rec['fb'] ?? '') === $playKey && in_array($rec['m'] ?? '', $rec['L'] ?? [], true),
    'an explored decision logs how=explore, the fallback\'s move, and a legal forced move; got ' . json_encode($rec));

// (c) The record sorts its legal moves (the episode files are compared across runs).
$check($L === (function ($x) { sort($x); return $x; })($L), 'the legal moves are sorted');

// (d) v2: forcing Pass beats the heuristic by 0.6 with 300 samples per arm (3·SE ≈ 0.23) → @rl passes.
putenv('SWU_RL_MIN_VISITS=200'); putenv('SWU_RL_Z=3'); $env('play');
$writeAb(['normal' => [$s => ['pass' => $entry(300, 0.4, 300, -0.2)]]]);
[$pick, $cov] = $stack('normal', 1, 'rl');
$check($pick === 'myHealth-0!CustomInput!Pass' && in_array('rl:override', $cov, true), 'v2: a significant effect overrides; got ' . $pick);
$check($stack('normal')[0] === 'myHand-0!FSM!', 'the plain heuristic profile is unchanged');

// (e) v2: an effect inside the noise, or a negative one, keeps the fallback's pick.
$writeAb(['normal' => [$s => ['pass' => $entry(300, 0.05, 300, 0.0)]]]);
$check($stack('normal', 1, 'rl')[0] === 'myHand-0!FSM!', 'v2: an effect inside the noise keeps the fallback\'s pick');
$writeAb(['normal' => [$s => ['pass' => $entry(300, -0.4, 300, 0.2)]]]);
$check($stack('normal', 1, 'rl')[0] === 'myHand-0!FSM!', 'v2: a negative effect keeps the fallback\'s pick');

// (f) v2: too few samples in EITHER arm → keep, however big the effect.
$writeAb(['normal' => [$s => ['pass' => $entry(100, 0.9, 300, -0.9)]]]);
$check($stack('normal', 1, 'rl')[0] === 'myHand-0!FSM!', 'v2: fewer treatment samples than the floor keeps the pick');
$writeAb(['normal' => [$s => ['pass' => $entry(300, 0.9, 100, -0.9)]]]);
$check($stack('normal', 1, 'rl')[0] === 'myHand-0!FSM!', 'v2: fewer control samples than the floor keeps the pick');

// (g) v2 never overrides on a bare positive effect: with SWU_RL_Z unset it still uses z = 3.
putenv('SWU_RL_Z=');
$writeAb(['normal' => [$s => ['pass' => $entry(300, 0.05, 300, 0.0)]]]);
$check($stack('normal', 1, 'rl')[0] === 'myHand-0!FSM!', 'v2 without SWU_RL_Z: a small effect still keeps the pick (z defaults to 3)');
$writeAb(['normal' => [$s => ['pass' => $entry(300, 0.4, 300, -0.2)]]]);
$check($stack('normal', 1, 'rl')[0] === 'myHealth-0!CustomInput!Pass', 'v2 without SWU_RL_Z: a significant effect overrides');
putenv('SWU_RL_MIN_VISITS='); putenv('SWU_RL_POLICY=');

array_map('unlink', glob($tmp . '/*') ?: []); @rmdir($tmp);
bot_test_finish();

<?php
// Value model features (SWUSim/Rl/SwuValueFeatures.php) — spec docs/superpowers/specs/2026-09-19-swusim-value-model-design.md §5.
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php -d apc.enable_cli=1 -d xdebug.mode=off SWUSim/DevTools/tests/value_features_test.php
require __DIR__ . '/fixtures/bot_test_bootstrap.php';
include_once './SWUSim/BotLegalActions.php';
include_once './SWUSim/BotHeuristic.php';

$names = SWUValueFeatureNames();
$check(count($names) === 69 && count(array_unique($names)) === 69, 'there are 69 distinct feature names');
$check(preg_match('/^[0-9a-f]{12}$/', SWUValueFeatureVersion()) === 1, 'the version is a 12-hex hash of the names');

// A known board: seat 1 has a ready Marine (3/3, cost 2) on the ground; seat 2 an exhausted Wampa (4/5, cost 4)
// and 5 damage on its base; seat 1 holds initiative, unclaimed; seat 1 to act.
$known = function ($b) {
    $b->MyLeader('SOR_014', false, false, true);
    $b->TheirBase('SOR_020', 5);
    $b->FillResourcesForPlayer(1, 'SOR_095', 3);
    $b->WithGroundUnitForPlayer(1, 'SOR_095', true);
    $b->WithGroundUnitForPlayer(2, 'SOR_164', false);
    $b->WithCardInHandForPlayer(1, 'SOR_095');
    $b->WithInitiativePlayerBeing(1);
};
$build($known);
$f = SWUValueFeatures(1, SWUValueHandSnapshot(1), 'softcontrol');
$check(array_keys($f) === $names, 'keys are exactly the names, in order');
$check($f['my_units_ground'] == 1 && $f['their_units_ground'] == 1 && $f['my_units_space'] == 0, 'unit counts per arena');
$check($f['my_power'] == 3 && $f['their_power'] == 4, 'total power');
$check($f['my_ready'] == 1 && $f['their_ready'] == 0, 'ready units');
$check($f['their_hp_left'] == SWUBaseRemainingHp(2) && $f['their_hp_left'] < $f['my_hp_left'], 'base HP left');
$check($f['init_mine_unclaimed'] == 1 && $f['init_theirs_unclaimed'] == 0, 'initiative: mine, unclaimed');
$check($f['to_act'] == 1, 'seat 1 is to act');
$check($f['my_res_total'] == 3 && $f['my_hand'] == 1, 'resources and hand size');
$check($f['style_softcontrol'] == 1 && $f['style_midrange'] == 0, 'my style one-hot');
$check($f['round'] == intval(GetTurnNumber()), 'round');
$g = SWUValueFeatures(2, SWUValueHandSnapshot(2), 'softaggro');
$check($g['to_act'] == 0 && $g['init_theirs_unclaimed'] == 1, 'from seat 2: not to act, initiative is theirs');
$check($g['my_power'] == $f['their_power'] && $g['their_power'] == $f['my_power'], 'seat 2 view swaps mine/theirs');

// MIRROR SYMMETRY: on a board identical for both seats, every my_X equals their_X (hand excluded: seat-only).
$build(function ($b) {
    foreach ([1, 2] as $p) {
        $b->FillResourcesForPlayer($p, 'SOR_095', 4);
        $b->WithGroundUnitForPlayer($p, 'SOR_164', true);
        $b->WithSpaceUnitForPlayer($p, 'JTL_095', false);
    }
});
$f = SWUValueFeatures(1, [], 'midrange');
foreach ($names as $n) {
    if (!str_starts_with($n, 'my_') || in_array($n, ['my_hand'], true)) continue;
    $t = 'their_' . substr($n, 3);
    if (!array_key_exists($t, $f)) continue;
    $check($f[$n] == $f[$t], "symmetric board: $n == $t");
}

// HIDDEN INFORMATION: the opponent's hand CONTENTS and my deck ORDER never change my features.
$hidden = function (string $theirHandCard, string $myTopDeck) {
    return function ($b) use ($theirHandCard, $myTopDeck) {
        $b->WithGroundUnitForPlayer(1, 'SOR_095', true);
        $b->WithCardInHandForPlayer(2, $theirHandCard);
        $b->WithCardInDeckForPlayer(1, $myTopDeck); $b->WithCardInDeckForPlayer(1, 'SOR_095');
    };
};
$build($hidden('SOR_095', 'SOR_164')); $a = SWUValueFeatures(1, SWUValueHandSnapshot(1), 'midrange');
$build($hidden('JTL_043', 'LAW_044')); $b2 = SWUValueFeatures(1, SWUValueHandSnapshot(1), 'midrange');
$check($a === $b2, 'opponent hand contents and my deck order are invisible to my features');

// Hand features read ONLY the hand passed in (the guard that keeps a lookahead from seeing its next draw).
$build($known);
$withRemoval = SWUValueFeatures(1, [['cid' => 'JTL_043', 'cost' => 5]], 'softcontrol');
$empty = SWUValueFeatures(1, [], 'softcontrol');
$check($withRemoval['hand_removal'] == 1 && $empty['hand_removal'] == 0 && $empty['my_hand'] == 0, 'hand features come from $hand, not the zone');

// ── LOGGER ────────────────────────────────────────────────────────────────────────────────────────
$log = sys_get_temp_dir() . '/value_log_test_' . getmypid() . '.jsonl';
@unlink($log);
$build($known);
putenv('SWU_VALUE_LOG');                       // off
SWUValueLogPosition($botCtx('softcontrol'));
$check(!file_exists($log), 'logging off: no file');
putenv("SWU_VALUE_LOG=$log"); putenv('SWU_VALUE_LOG_RATE=1'); putenv('SWU_VALUE_SEED=t1');
$GLOBALS['SWUValueLogStyles'] = [1 => 'softcontrol', 2 => 'softaggro'];
SWUValueLogPosition($botCtx('softcontrol'));
$lines = file($log, FILE_IGNORE_NEW_LINES);
$h = json_decode($lines[0], true);
$check($h['names'] === $names && $h['featureVersion'] === SWUValueFeatureVersion(), 'header carries names + version');
$check(count($lines) === 3, 'one free-play decision logs BOTH seats (header + 2 rows)');
$r1 = json_decode($lines[1], true); $r2 = json_decode($lines[2], true);
$check($r1[0] === 1 && array_slice($r1, 1) == SWUValueVector(SWUValueFeatures(1, SWUValueHandSnapshot(1), 'softcontrol')), 'parity: seat-1 row == SWUValueFeatures(1)');
$check($r2[0] === 2 && array_slice($r2, 1) == SWUValueVector(SWUValueFeatures(2, SWUValueHandSnapshot(2), 'softaggro')), 'parity: seat-2 row == SWUValueFeatures(2)');
// Sampling is deterministic for a fixed seed.
$keep = function (string $seed) use ($log, $botCtx) {
    @unlink($log); putenv("SWU_VALUE_SEED=$seed"); putenv('SWU_VALUE_LOG_RATE=0.5'); $GLOBALS['SWUValueLogN'] = 0;
    for ($i = 0; $i < 40; $i++) SWUValueLogPosition($botCtx('softcontrol'));
    return count(file($log)) - 1;
};
$k1 = $keep('seedA'); $k2 = $keep('seedA');
$check($k1 === $k2 && $k1 > 0 && $k1 < 80, "sampling at 0.5 is deterministic per seed ($k1 of 80 rows)");
putenv('SWU_VALUE_LOG'); putenv('SWU_VALUE_LOG_RATE'); putenv('SWU_VALUE_SEED'); @unlink($log);

bot_test_finish();

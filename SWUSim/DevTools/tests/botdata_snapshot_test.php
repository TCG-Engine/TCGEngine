<?php
// The BotData snapshot is a COMPLETE, JSON-safe read of the position (spec §1.5).
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php -d apc.enable_cli=1 -d xdebug.mode=off SWUSim/DevTools/tests/botdata_snapshot_test.php
require __DIR__ . '/fixtures/bot_test_bootstrap.php';
include_once './SWUSim/BotLegalActions.php';
include_once './SWUSim/BotHeuristic.php';
include_once './SWUSim/Custom/BotDataSnapshot.php';

$build(function ($b) {
    $b->MyLeader('ASH_009');
    $b->MyBase('ASH_019');
    $b->TheirLeader('HMW_008');
    $b->TheirBase('HMW_021');
    $b->FillResourcesForPlayer(1, 'SOR_095', 3);
    $b->WithGroundUnitForPlayer(1, 'LOF_093', false);
    $b->WithCardInHandForPlayer(1, 'LAW_037');
    $b->WithCardInHandForPlayer(1, 'ASH_248');
    $b->WithGroundUnitForPlayer(2, 'HMW_103', false);
});

$s = SWUBotDataSnapshot(1, 'human', ['kind' => 'play', 'card' => 'ASH_248', 'mz' => 'myHand-1']);

$check(is_array($s), 'returns an array');
$check(($s['actor'] ?? '') === 'human', 'records the actor; got ' . json_encode($s['actor'] ?? null));
$check(($s['action']['card'] ?? '') === 'ASH_248', 'records the action card');
$check(intval($s['round'] ?? -1) >= 0, 'records the round');
$check(($s['phase'] ?? '') !== '', 'records the phase');

$s1 = $s['seats']['1'] ?? [];
$check(($s1['base'] ?? '') === 'ASH_019', 'seat 1 base; got ' . json_encode($s1['base'] ?? null));
$check(($s1['leader']['id'] ?? '') === 'ASH_009', 'seat 1 leader');
$check(intval($s1['res']['total'] ?? 0) === 3, 'seat 1 resource total; got ' . json_encode($s1['res'] ?? null));
$check(count($s1['hand'] ?? []) === 2, 'seat 1 hand has both cards; got ' . json_encode($s1['hand'] ?? null));
$check(count($s1['ground'] ?? []) === 1 && ($s1['ground'][0]['id'] ?? '') === 'LOF_093', 'seat 1 ground unit');
$check(($s1['ground'][0]['p'] ?? null) === 2 && ($s1['ground'][0]['hp'] ?? null) === 5, 'unit carries power/hp');

$s2 = $s['seats']['2'] ?? [];
$check(count($s2['ground'] ?? []) === 1 && ($s2['ground'][0]['id'] ?? '') === 'HMW_103', 'seat 2 ground unit');
$check(array_key_exists('hand', $s2), 'BOTH seats hands are recorded (post-hoc corpus, not a live view)');

// JSON-SAFE: SWUBotUnitView carries a live object under 'obj' — it must never reach the file.
$json = json_encode($s);
$check(is_string($json) && json_last_error() === JSON_ERROR_NONE, 'snapshot is json_encode-able; err=' . json_last_error_msg());
$check(strpos($json, '"obj"') === false, 'the live card object is stripped');
$check(strlen($json) < 8192, 'a snapshot of a small board stays small; got ' . strlen($json) . ' bytes');

// Tokens and active effects are part of a complete position (spec §1.5).
$check(array_key_exists('exp', $s1['ground'][0]), 'a unit row carries its Experience count');
$check(array_key_exists('effects', $s1['ground'][0]), 'a unit row carries its active effects');

bot_test_finish();

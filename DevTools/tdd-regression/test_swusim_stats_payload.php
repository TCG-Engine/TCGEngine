<?php
// http://localhost:3400/TCGEngine/DevTools/tdd-regression/test_swusim_stats_payload.php
header('Content-Type: text/plain');
include __DIR__ . '/../../SWUSim/StatsSubmit.php';

$match = ['format' => 'premier'];
$game = [
    'gameName' => '777', 'gameNumber' => 2, 'winner' => 1,
    'detail' => [
        'firstPlayer' => 2, 'turns' => 9,
        'leader' => ['1' => 'JTL_001', '2' => 'LOF_001'],
        'base'   => ['1' => 'JTL_023', '2' => 'LOF_020'],
        'baseHpLeft' => ['1' => 12, '2' => 0],
        'telemetry' => [
            'cards' => ['1' => ['JTL_100' => ['played'=>3,'resourced'=>1,'activated'=>0,'drawn'=>4,'discarded'=>1]]],
            'turns' => [['seat'=>1,'cardsUsed'=>3,'resourcesUsed'=>4,'resourcesLeft'=>0,'cardsLeft'=>2,'damageDealt'=>5,'damageTaken'=>3,'restored'=>1]],
        ],
    ],
];

$p = SWUBuildGameResultPayload($match, $game);
$checks = [];
// Top-level SubmitGameResult fields.
$checks['winner'] = ($p['winner'] ?? null) === 1;
$checks['firstPlayer'] = ($p['firstPlayer'] ?? null) === 2;
$checks['winHero=winner leader'] = ($p['winHero'] ?? '') === 'JTL_001';
$checks['loseHero=loser leader'] = ($p['loseHero'] ?? '') === 'LOF_001';
$checks['round'] = ($p['round'] ?? null) === 9;
$checks['winnerHealth'] = ($p['winnerHealth'] ?? null) === 12;
$checks['format'] = ($p['format'] ?? '') === 'premier';
$checks['gameName'] = ($p['gameName'] ?? '') === '777';
$checks['sequenceNumber'] = ($p['sequenceNumber'] ?? null) === 2;
// player1 JSON has the exact cardResults/turnResults shape.
$p1 = json_decode($p['player1'] ?? 'null', true);
$cr = $p1['cardResults'][0] ?? [];
$tr = $p1['turnResults'][0] ?? [];
$checks['p1 leader/base'] = ($p1['leader'] ?? '') === 'JTL_001' && ($p1['base'] ?? '') === 'JTL_023' && ($p1['opposingHero'] ?? '') === 'LOF_001';
$checks['cardResults shape'] = isset($cr['cardId'],$cr['played'],$cr['resourced'],$cr['activated'],$cr['drawn'],$cr['discarded'])
    && $cr['cardId']==='JTL_100' && $cr['played']===3 && $cr['drawn']===4;
$checks['turnResults shape'] = isset($tr['cardsUsed'],$tr['resourcesUsed'],$tr['resourcesLeft'],$tr['cardsLeft'],$tr['damageDealt'],$tr['damageTaken'])
    && $tr['damageDealt']===5 && $tr['damageTaken']===3;
$checks['restored extra present'] = ($tr['restored'] ?? null) === 1;
// player2 present + valid JSON.
$checks['player2 valid'] = is_array(json_decode($p['player2'] ?? 'null', true));

$fails = array_keys(array_filter($checks, fn($v) => $v !== true));
echo empty($fails) ? "PASS (" . count($checks) . " checks)\n"
   : "FAIL: " . implode(', ', $fails) . " payload=" . json_encode($p) . "\n";

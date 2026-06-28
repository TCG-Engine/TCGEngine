<?php
// http://localhost:3400/TCGEngine/DevTools/tdd-regression/test_swusim_stats_html.php
header('Content-Type: text/plain');
include __DIR__ . '/../../SWUSim/GeneratedCode/GeneratedCardDictionaries.php'; // CardTitle/CardSubtitle
include __DIR__ . '/../../SWUSim/StatsSubmit.php';

$match = ['bestOf' => 3, 'wins' => ['1' => 2, '2' => 1]];
$game = ['gameNumber' => 3, 'winner' => 1, 'detail' => ['telemetry' => [
    'cards' => [
        '1' => ['ASH_052' => ['played'=>2,'resourced'=>1,'activated'=>0,'drawn'=>3,'discarded'=>1]],
        '2' => ['ASH_011' => ['played'=>1,'resourced'=>0,'activated'=>1,'drawn'=>2,'discarded'=>0]],
    ],
    'turns' => [
        ['seat'=>1,'cardsUsed'=>2,'resourcesUsed'=>3,'resourcesLeft'=>0,'cardsLeft'=>4,'damageDealt'=>5,'damageTaken'=>2,'restored'=>1],
        ['seat'=>2,'cardsUsed'=>1,'resourcesUsed'=>1,'resourcesLeft'=>2,'cardsLeft'=>5,'damageDealt'=>0,'damageTaken'=>5,'restored'=>0],
    ],
]]];

$checks = [];

// --- Viewer = player 1: own stats only, titled cards, borders ---
$h1 = SWUBuildStatsHtml($match, $game, 1);
$checks['p1 is string']         = is_string($h1) && $h1 !== '';
$checks['p1 shows own card']    = strpos($h1, 'Chimaera') !== false;            // CardTitle(ASH_052)
$checks['p1 shows subtitle']    = strpos($h1, 'Chimaera - A Frightening Reality') !== false;
$checks['p1 hides opp card']    = strpos($h1, 'Cad Bane') === false;            // ASH_011 belongs to P2
$checks['p1 no raw id']         = strpos($h1, 'ASH_052') === false;
$checks['p1 has borders']       = strpos($h1, 'border:1px solid') !== false;
$checks['p1 "Your" header']     = strpos($h1, 'Your cards') !== false;
$checks['p1 own round only']    = (substr_count($h1, '<tr>') === 4);             // 2 headers + p1 card + p1 round
$checks['p1 has bo3 score']     = strpos($h1, 'Match score: 2 – 1') !== false;
$checks['p1 no raw script']     = strpos($h1, '<script') === false;

// --- Viewer = player 2: their own card, not P1's ---
$h2 = SWUBuildStatsHtml($match, $game, 2);
$checks['p2 shows own card']    = strpos($h2, 'Cad Bane') !== false;
$checks['p2 hides opp card']    = strpos($h2, 'Chimaera') === false;

// --- Spectator (null seat): sees both ---
$hs = SWUBuildStatsHtml($match, $game, null);
$checks['spec sees both']       = strpos($hs, 'Chimaera') !== false && strpos($hs, 'Cad Bane') !== false;
$checks['spec player labels']   = strpos($hs, 'Player 1 cards') !== false && strpos($hs, 'Player 2 cards') !== false;

$fails = array_keys(array_filter($checks, fn($v) => $v !== true));
echo empty($fails) ? "PASS (" . count($checks) . " checks)\n" : "FAIL: " . implode(', ', $fails) . "\n";

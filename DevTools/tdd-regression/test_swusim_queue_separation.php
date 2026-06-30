<?php
// http://localhost:3400/TCGEngine/DevTools/tdd-regression/test_swusim_queue_separation.php
header('Content-Type: text/plain');

function _join($format, $queueType, $deck) {
    $ch = curl_init('http://localhost/TCGEngine/APIs/Lobbies/JoinQueue.php');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true, CURLOPT_POST => true, CURLOPT_TIMEOUT => 25,
        CURLOPT_POSTFIELDS => http_build_query([
            'rootName' => 'SWUSim', 'deckLink' => $deck, 'format' => $format, 'queueType' => $queueType,
        ]),
    ]);
    $out = curl_exec($ch); curl_close($ch);
    return json_decode($out, true);
}

// A structurally-valid Premier deck (leader+base+51).
$deck = "Leader\nJTL_001\nBase\nJTL_023\nDeck\n" . str_repeat("3 JTL_100\n", 17);

// Two players in premier/bo3 should pair (second join reports ready + gameName).
$a = _join('premier', 'bo3', $deck);
$b = _join('premier', 'bo3', $deck);
// A player in premier/bo1 must NOT pair with the leftover bo3 game.
$c = _join('premier', 'bo1', $deck);

$checks = [];
$checks['premier/bo3 pair'] = !empty($b['success']) && (!empty($b['ready']) || !empty($b['gameName']));
$checks['bo1 does not steal bo3'] = empty($c['gameName']);

echo (count(array_filter($checks)) === count($checks))
   ? "PASS (" . count($checks) . " checks)\n"
   : "FAIL: " . implode(', ', array_keys(array_filter($checks, fn($v)=>!$v))) . "\n  a=" . json_encode($a) . "\n  b=" . json_encode($b) . "\n  c=" . json_encode($c) . "\n";

<?php
// http://localhost:3400/TCGEngine/DevTools/tdd-regression/test_swusim_nextgame_signal.php
header('Content-Type: text/plain');
$game = 'nextsigtest_' . uniqid();
$dir = __DIR__ . '/../../SWUSim/Games/' . $game;
@mkdir($dir, 0777, true);
file_put_contents($dir . '/NextGame.json', json_encode(['nextGameName' => 'TARGET999']));

$ch = curl_init('http://localhost/TCGEngine/SWUSim/GetNextTurn.php?gameName=' . $game . '&playerID=1&authKey=x');
curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 20]);
$out = trim(curl_exec($ch)); curl_close($ch);

@unlink($dir . '/NextGame.json'); @rmdir($dir);

$pass = (strpos($out, '1235MATCHADVANCE') === 0) && (strpos($out, 'TARGET999') !== false);
echo $pass ? "PASS\n" : "FAIL out=" . substr($out, 0, 200) . "\n";

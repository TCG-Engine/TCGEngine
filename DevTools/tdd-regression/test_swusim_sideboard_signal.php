<?php
// http://localhost:3400/TCGEngine/DevTools/tdd-regression/test_swusim_sideboard_signal.php
header('Content-Type: text/plain');
$game = 'sidesigtest_' . uniqid();
$dir = __DIR__ . '/../../SWUSim/Games/' . $game;
@mkdir($dir, 0777, true);
file_put_contents($dir . '/Sideboard.json', json_encode(['matchId' => 'MTEST777']));

$ch = curl_init('http://localhost/TCGEngine/SWUSim/GetNextTurn.php?gameName=' . $game . '&playerID=1&authKey=x');
curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 20]);
$out = trim(curl_exec($ch)); curl_close($ch);

@unlink($dir . '/Sideboard.json'); @rmdir($dir);

$pass = (strpos($out, '1236SIDEBOARD') === 0) && (strpos($out, 'MTEST777') !== false);
echo $pass ? "PASS\n" : "FAIL out=" . substr($out, 0, 200) . "\n";

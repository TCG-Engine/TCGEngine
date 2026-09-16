<?php
// http://localhost:3400/TCGEngine/DevTools/tdd-regression/test_swusim_queue_separation.php
// Bo1 and Bo3 public queues never cross-pair. Rewritten 2026-09-16 (owner-approved): the original joined ANONYMOUSLY — refused
// by the login gate since e3e1bcb8, so it could never pass — and used an illegal 51-copy deck that would have hidden a
// pairing whose game failed to create. Now: two logged-in players, a Premier-legal list, and a real gameName asserted.
header('Content-Type: text/plain');

function _qsPost($url, $params, $jar) {
    $ch = curl_init($url);
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_POST => true, CURLOPT_TIMEOUT => 40,
        CURLOPT_POSTFIELDS => http_build_query($params), CURLOPT_COOKIEJAR => $jar, CURLOPT_COOKIEFILE => $jar]);
    $out = curl_exec($ch); curl_close($ch);
    return json_decode((string)$out, true) ?: ['RAW' => substr((string)$out, 0, 300)];
}
function _qsLogin($user) {
    $jar = tempnam(sys_get_temp_dir(), 'qsep');
    _qsPost('http://localhost/TCGEngine/AccountFiles/AttemptPasswordLogin.php', ['submit' => '1', 'userID' => $user, 'password' => 'pass'], $jar);
    return $jar;
}
function _join($jar, $format, $queueType, $deck) {
    return _qsPost('http://localhost/TCGEngine/APIs/Lobbies/JoinQueue.php',
        ['rootName' => 'SWUSim', 'deckLink' => $deck, 'format' => $format, 'queueType' => $queueType], $jar);
}
function _leave($jar, $r) {
    if (empty($r['lobbyID'])) return;
    _qsPost('http://localhost/TCGEngine/APIs/Lobbies/LeaveQueue.php',
        ['rootName' => 'SWUSim', 'playerID' => $r['playerID'] ?? 0, 'lobbyID' => $r['lobbyID'], 'authKey' => $r['authKey'] ?? ''], $jar);
}

$deck = trim(implode("\n", array_filter(explode("\n", file_get_contents(__DIR__ . '/../../SWUSim/Tests/BotFixtures/meta-2026-09/aggro_vader_yellow.txt')),
    fn($l) => !str_starts_with($l, '#'))));   // Premier-legal
$p1 = _qsLogin('claudebot1');
$p2 = _qsLogin('claudebot2');

// Two players in premier/bo3 pair (the second join reports ready + a real gameName).
$a = _join($p1, 'premier', 'bo3', $deck);
$b = _join($p2, 'premier', 'bo3', $deck);
// A player in premier/bo1 must NOT pair with anything left over from bo3.
$c = _join($p1, 'premier', 'bo1', $deck);
if (empty($c['gameName'])) _leave($p1, $c);   // only a WAITING seat is left; a matched one belongs to its game

$checks = [];
$checks['premier/bo3 host waits'] = !empty($a['success']) && empty($a['ready']);
$checks['premier/bo3 pair'] = !empty($b['success']) && !empty($b['ready']) && !empty($b['gameName']);
$checks['bo1 does not steal bo3'] = !empty($c['success']) && empty($c['gameName']);

echo (count(array_filter($checks)) === count($checks))
   ? "PASS (" . count($checks) . " checks)\n"
   : "FAIL: " . implode(', ', array_keys(array_filter($checks, fn($v) => !$v))) . "\n  a=" . json_encode($a) . "\n  b=" . json_encode($b) . "\n  c=" . json_encode($c) . "\n";

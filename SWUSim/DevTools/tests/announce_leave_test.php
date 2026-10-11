<?php
// "Return to Main Menu" announces the leave in chat (owner request 2026-10-10: "it'd be nice to get a message that says
// 'Player N left.' or '<username> left.' so we know if we can keep chatting or not").
// SWUGoMainMenu beacons SWUSim/AnnounceLeave.php, which posts ONE seat-0 system row into the game's chat.
//   1. A guest seat in a matchless game reads "Player N left the game."
//   2. A logged-in seat in a matchless game is named from the session.
//   3. A match game names the seat from the match record: account → username, guest → "Player N".
//   4. It says it once per seat (a double click / two tabs), and a spectator or an unknown game posts nothing.
// ⚠ The wrong-auth-key refusal can't be exercised here: SimGameValidateSeatAuth passes everything in the dev env.
// Over HTTP against the local container, so it runs the real endpoint, the real session cookie and the real APCu store.
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php -d xdebug.mode=off SWUSim/DevTools/tests/announce_leave_test.php
chdir(dirname(__DIR__, 3));
require_once './Core/Match/Match.php';
require_once './Database/ConnectionManager.php';
require_once './Core/MatchHistory.php';
$fails = 0;
$check = function ($ok, $msg, $detail = '') use (&$fails) {
    echo ($ok ? 'PASS' : 'FAIL') . ": $msg" . (!$ok && $detail !== '' ? "  [got: $detail]" : '') . "\n";
    if (!$ok) $fails++;
};
$http = function (string $method, string $path, array $params, string $cookie = '') {
    $headers = "Host: localhost\r\n" . ($cookie !== '' ? "Cookie: $cookie\r\n" : '');
    $opts = ['method' => $method, 'timeout' => 60, 'ignore_errors' => true];
    $url = 'http://localhost/TCGEngine/' . $path;
    if ($method === 'POST') {
        $opts['header'] = $headers . "Content-Type: application/x-www-form-urlencoded\r\n";
        $opts['content'] = http_build_query($params);
    } else {
        $opts['header'] = $headers;
        $url .= '?' . http_build_query($params);
    }
    return strval(@file_get_contents($url, false, stream_context_create(['http' => $opts])));
};
$deck = file_get_contents('./SWUSim/Tests/BotFixtures/premier_deck_a.txt');
$newGame = function () use ($http, $deck) {
    $g = json_decode($http('POST', 'APIs/Lobbies/JoinQueue.php',
        ['rootName' => 'SWUSim', 'format' => 'goldfish', 'queueType' => 'bo1', 'deckLink' => $deck]));
    return is_object($g) ? $g : null;
};
$leave = fn(string $gameName, string $seat, string $cookie = '') =>
    trim($http('POST', 'SWUSim/AnnounceLeave.php', ['gameName' => $gameName, 'playerID' => $seat, 'authKey' => ''], $cookie));
// What the opponent's board would receive: the game scope's chat, read as a spectator (the open read).
$chat = fn(string $gameName) => json_decode($http('GET', 'GetChat.php',
    ['gameName' => $gameName, 'lastChatID' => '0', 'playerID' => 'S', 'folderPath' => 'SWUSim']), true) ?: [];
$cleanup = function (string $gameName) {
    array_map('unlink', glob("./SWUSim/Games/$gameName/*") ?: []);
    @rmdir("./SWUSim/Games/$gameName");
};
// A logged-in session, written where Apache's file handler reads it (same as guest_access_test.php).
$login = function (int $userId) {
    $sid = 'announceleave' . bin2hex(random_bytes(6));
    $file = rtrim(session_save_path() ?: sys_get_temp_dir(), '/') . "/sess_$sid";
    file_put_contents($file, "userid|i:$userId;");
    @chown($file, 'www-data');
    return [session_name() . '=' . $sid, $file];
};
// Any real account (the dev DB's ids are not fixed): the lowest one.
$conn = GetLocalMySQLConnection();
$uidRow = $conn->query('SELECT MIN(usersId) AS id FROM users')->fetch_assoc();
$uid = intval($uidRow['id'] ?? 0);
$user1 = strval(MatchHistoryUsername($conn, $uid) ?? '');
$conn->close();
$check($user1 !== '', 'setup: the dev DB has an account to log in as');

// ── 1 + 4. Guest seat, once per seat, spectator / unknown game ─────────────────────────────────────────
$g = $newGame();
$check($g !== null, 'setup: a goldfish game to leave');
if ($g !== null) {
    $gn = strval($g->gameName);
    $check($leave($gn, 'S') === 'Bad request.', 'a spectator cannot announce a leave');
    $check($leave($gn, '5') === 'Bad request.', 'a seat beyond the table cannot announce a leave');
    $check(count($chat($gn)) === 0, 'and neither posted anything', json_encode($chat($gn)));

    $check($leave($gn, '1') === 'OK', 'seat 1 leaving is accepted');
    $rows = $chat($gn);
    $check(count($rows) === 1, 'it posts exactly one chat row', json_encode($rows));
    $row = $rows[0] ?? [];
    $check(strval($row['text'] ?? '') === 'Player 1 left the game.', 'a guest seat reads "Player N left the game."', strval($row['text'] ?? ''));
    $check(strval($row['playerID'] ?? '') === '0' && strval($row['playerLabel'] ?? '') === 'Game',
        'as a seat-0 system row (the board renders it as a log line, not as seat 1 talking)', json_encode($row));

    $check($leave($gn, '1') === 'OK', 'leaving again is still OK for the client');
    $check(count($chat($gn)) === 1, 'but the table is told only once', json_encode($chat($gn)));
    $check($leave($gn, '2') === 'OK' && count($chat($gn)) === 2, 'another seat leaving is its own notice', json_encode($chat($gn)));
    $cleanup($gn);
}
$check($leave('zzNoSuchGame' . bin2hex(random_bytes(4)), '1') === 'No such game.', 'an unknown game posts nothing');

// ── 2. Logged-in seat, matchless game ────────────────────────────────────────────────────────────────
$g = $newGame();
if ($g !== null) {
    $gn = strval($g->gameName);
    [$cookie, $sessFile] = $login($uid);
    $leave($gn, '1', $cookie);
    $text = strval($chat($gn)[0]['text'] ?? '');
    $check($text === "$user1 left the game.", 'a logged-in seat in a matchless game is named by username', $text);
    @unlink($sessFile);
    $cleanup($gn);
}

// ── 3. Match game: the match record names the seat, not the session ──────────────────────────────────
$g = $newGame();
if ($g !== null) {
    $gn = strval($g->gameName);
    $matchId = 'zzleave' . bin2hex(random_bytes(4));
    $matchDir = MatchesDir('SWUSim') . "/$matchId";
    @mkdir($matchDir, 0777, true);
    file_put_contents("$matchDir/Match.json", json_encode(['rootName' => 'SWUSim', 'matchId' => $matchId,
        'players' => ['1' => ['userId' => $uid], '2' => ['userId' => 0]]]));
    file_put_contents("./SWUSim/Games/$gn/MatchRef.json", json_encode(['matchId' => $matchId, 'gameNumber' => 1]));
    @chmod("$matchDir/Match.json", 0666);

    $leave($gn, '1');                                   // no session at all: the record still names seat 1
    [$cookie, $sessFile] = $login($uid);
    $leave($gn, '2', $cookie);                          // a session for that account must NOT rename guest seat 2
    @unlink($sessFile);
    $texts = array_map(fn($r) => strval($r['text'] ?? ''), $chat($gn));
    $check(($texts[0] ?? '') === "$user1 left the game.", 'a match seat with an account is named from the match record', json_encode($texts));
    $check(($texts[1] ?? '') === 'Player 2 left the game.', 'a guest match seat reads "Player N", whoever holds the session', json_encode($texts));

    @unlink("$matchDir/Match.json");
    @rmdir($matchDir);
    $cleanup($gn);
}

echo $fails === 0 ? "\nALL PASS\n" : "\n$fails FAILED\n";
exit($fails ? 1 : 0);

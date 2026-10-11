<?php
// Guests (owner, 2026-09-21): "we can remove the account requirement. we will just not let not-logged in users use the
// chat feature" + "the display name for a guest user can be 'Guest PN', N being the player number they are in that game".
//   1. JoinQueue no longer refuses a logged-out request for a non-Open format (the old "You must be logged in…" gate).
//   2. SubmitChat.php refuses a logged-out SWUSim sender, and still accepts a logged-in one.
//   3. MatchSeatDisplayNames names a guest seat "Guest PN".
// Over HTTP against the local container, so it exercises the real endpoints and the real session cookie.
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php -d xdebug.mode=off SWUSim/DevTools/tests/guest_access_test.php
chdir(dirname(__DIR__, 3));
require_once './Core/Match/Match.php';
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

// ── 1. No login gate on the queue ───────────────────────────────────────────────────────────────────
// A logged-out Premier request with no deck. It must now get past the login gate and fail on the DECK instead; the
// removed gate ran before deck validation, so a login refusal here means the gate is back. (No deck = no lobby
// created, so this leaves nothing behind.)
foreach (['public queue' => [], 'private room' => ['createPrivate' => '1']] as $what => $extra) {
    $r = json_decode($http('POST', 'APIs/Lobbies/JoinQueue.php',
        ['rootName' => 'SWUSim', 'format' => 'premier', 'queueType' => 'bo1', 'deckLink' => ''] + $extra));
    $msg = is_object($r) ? strval($r->message ?? '') : '';
    $check(is_object($r) && stripos($msg, 'logged in') === false, "a logged-out Premier $what request is not refused for being logged out", $msg);
}

// ── 2. Chat: guests read, only accounts send ────────────────────────────────────────────────────────
$deck = file_get_contents('./SWUSim/Tests/BotFixtures/premier_deck_a.txt');
$game = json_decode($http('POST', 'APIs/Lobbies/JoinQueue.php',
    ['rootName' => 'SWUSim', 'format' => 'goldfish', 'queueType' => 'bo1', 'deckLink' => $deck]));
$gameName = is_object($game) ? strval($game->gameName ?? '') : '';
$check($gameName !== '', 'setup: a guest can create a goldfish game to chat in', is_object($game) ? strval($game->message ?? '') : 'no JSON');
if ($gameName !== '') {
    $chat = fn(string $cookie, string $text) => trim($http('GET', 'SubmitChat.php', ['gameName' => $gameName, 'playerID' => '1',
        'authKey' => strval($game->authKey), 'folderPath' => 'SWUSim', 'chatText' => $text], $cookie));
    $check($chat('', 'hello from a guest') === 'Log in to chat.', 'a logged-out SWUSim player cannot send chat', $chat('', 'again'));

    // A real logged-in session, written where Apache's file handler reads it (same container, same save path), in PHP's
    // default session encoding. Written by hand because this script has already printed, so session_start() can't run.
    $sid = 'guestaccesstest' . bin2hex(random_bytes(6));
    $sessFile = rtrim(session_save_path() ?: sys_get_temp_dir(), '/') . "/sess_$sid";
    file_put_contents($sessFile, 'userid|i:1;');
    @chown($sessFile, 'www-data');   // the CLI runs as root; Apache must be able to read it
    $check($chat(session_name() . '=' . $sid, 'hello from an account') === 'OK', 'a logged-in SWUSim player can still send chat',
        $chat(session_name() . '=' . $sid, 'again'));
    @unlink($sessFile);

    array_map('unlink', glob("./SWUSim/Games/$gameName/*") ?: []);
    @rmdir("./SWUSim/Games/$gameName");
}

// ── 3. Guest display name ───────────────────────────────────────────────────────────────────────────
// userId 0 = a guest seat, so no DB lookup happens and this stays a pure check.
$names = MatchSeatDisplayNames(['players' => ['1' => ['userId' => 0], '2' => ['userId' => 0], '3' => ['userId' => 0]]]);
$check($names === [1 => 'Guest P1', 2 => 'Guest P2', 3 => 'Guest P3'], 'a guest seat is named "Guest PN" by its seat number', json_encode($names));

echo $fails === 0 ? "\nALL PASS\n" : "\n$fails FAILED\n";
exit($fails ? 1 : 0);

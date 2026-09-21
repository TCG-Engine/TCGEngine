<?php
// Arenabot is open to EVERYONE, guests included (owner, 2026-09-21: "we can remove the account requirement").
// History: admin-only from 2026-09-15 (moderator trial), every logged-in player from 2026-09-18.
// Tested OUTSIDE local dev — a production Host with DEVENV off — because local dev was always allowed, so a check made
// there cannot tell an open gate from a closed one.
// The menu (SharedUI/Sites/SWUSim/MainMenu.php) and the endpoint (APIs/Lobbies/JoinQueue.php) use the same helper.
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php -d xdebug.mode=off SWUSim/DevTools/tests/bot_practice_gate_test.php
chdir(dirname(__DIR__, 3));
require_once './SWUSim/Mod/DevGate.php';
$fails = 0;
$check = function ($ok, $msg) use (&$fails) { echo ($ok ? 'PASS' : 'FAIL') . ": $msg\n"; if (!$ok) $fails++; };

putenv('DEVENV=false');   // the CLI carries DEVENV=true in the container; the gate must be tested without it
$as = function (string $host, ?string $user) {
    $_SERVER['HTTP_HOST'] = $host;
    if ($user === null) unset($_SESSION['useruid']); else $_SESSION['useruid'] = $user;
    return SWUBotPracticeAllowed();
};
$check($as('swustats.net', null) === true, 'production, logged out: ALLOWED (opened to guests 2026-09-21)');
$check($as('swustats.net', '') === true, 'production, an empty useruid: allowed');
$check($as('swustats.net', 'someplayer') === true, 'production, an ordinary account: allowed');

// The endpoint's refusal (APIs/Lobbies/JoinQueue.php calls SWUBotPracticeRefusal before anything else reads the format).
// Tested here in the CLI: the local container's Apache runs with DEVENV=true, so over HTTP every request is local dev.
$as('swustats.net', null);
$check(SWUBotPracticeRefusal('botpractice') === null, 'a LOGGED-OUT Arenabot request in production is not refused');
$check(SWUBotPracticeRefusal('premier') === null && SWUBotPracticeRefusal('hotseat') === null, 'other formats are never refused by it');

// Over HTTP (local dev): a Bot Practice request still creates a game.
$post = function (string $host) {
    $deck = file_get_contents('./SWUSim/Tests/BotFixtures/premier_deck_a.txt');
    $ctx = stream_context_create(['http' => ['method' => 'POST', 'timeout' => 60, 'ignore_errors' => true,
        'header' => "Content-Type: application/x-www-form-urlencoded\r\nHost: $host\r\n",
        'content' => http_build_query(['rootName' => 'SWUSim', 'format' => 'botpractice', 'queueType' => 'bo1', 'deckLink' => $deck])]]);
    return json_decode(strval(@file_get_contents('http://localhost/TCGEngine/APIs/Lobbies/JoinQueue.php', false, $ctx)));
};
$r = $post('localhost');
$check(is_object($r) && ($r->success ?? false) === true, 'endpoint: from local dev it still creates a game');
if (is_object($r) && !empty($r->gameName)) { array_map('unlink', glob("./SWUSim/Games/{$r->gameName}/*") ?: []); @rmdir("./SWUSim/Games/{$r->gameName}"); }

echo $fails === 0 ? "\nALL PASS\n" : "\n$fails FAILED\n";
exit($fails ? 1 : 0);

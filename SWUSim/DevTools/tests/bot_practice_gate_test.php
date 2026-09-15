<?php
// Bot Practice is admin-only outside local dev (owner, 2026-09-15: "update the Bot Practice local dev gate to now be
// an admin-only gate … for other admins to try out and give me feedback"). "Admin" = the approved moderators
// (AccountFiles/AccountSessionAPI.php ApprovedModeratorUserNames(), the same list CheckLoggedInUserMod() gates the admin
// tools with). Local dev keeps working exactly as before (SWUIsLocalDevRequest: a localhost Host, or DEVENV=true).
// The menu (SharedUI/Sites/SWUSim/MainMenu.php) and the endpoint (APIs/Lobbies/JoinQueue.php) use the same helper.
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php -d xdebug.mode=off SWUSim/DevTools/tests/bot_practice_gate_test.php
chdir(dirname(__DIR__, 3));
require_once './SWUSim/Mod/DevGate.php';
require_once './AccountFiles/AccountSessionAPI.php';
$fails = 0;
$check = function ($ok, $msg) use (&$fails) { echo ($ok ? 'PASS' : 'FAIL') . ": $msg\n"; if (!$ok) $fails++; };

putenv('DEVENV=false');   // the CLI carries DEVENV=true in the container; the gate must be tested without it
$as = function (string $host, ?string $user) {
    $_SERVER['HTTP_HOST'] = $host;
    if ($user === null) unset($_SESSION['useruid']); else $_SESSION['useruid'] = $user;
    return SWUBotPracticeAllowed();
};
$mod = ApprovedModeratorUserNames()[0];
$check($as('localhost:3400', null) === true, 'local dev, logged out: allowed (unchanged)');
$check($as('swustats.net', null) === false, 'production, logged out: not allowed');
$check($as('swustats.net', 'someplayer') === false, 'production, an ordinary account: not allowed');
$check($as('swustats.net', $mod) === true, "production, an approved moderator ($mod): allowed");
putenv('DEVENV=true');
$check($as('swustats.net', null) === true, 'DEVENV=true still counts as local dev');
putenv('DEVENV=false');

// The endpoint's refusal (APIs/Lobbies/JoinQueue.php calls SWUBotPracticeRefusal before anything else reads the format).
// Tested here in the CLI: the local container's Apache runs with DEVENV=true, so over HTTP every request is local dev.
$as('swustats.net', 'someplayer');
$check(SWUBotPracticeRefusal('botpractice') === 'Bot Practice is currently limited to approved testers.', 'a non-admin Bot Practice request gets the refusal');
$check(SWUBotPracticeRefusal('premier') === null && SWUBotPracticeRefusal('hotseat') === null, 'other formats are never refused by it');
$as('swustats.net', $mod);
$check(SWUBotPracticeRefusal('botpractice') === null, 'an approved moderator is not refused');

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

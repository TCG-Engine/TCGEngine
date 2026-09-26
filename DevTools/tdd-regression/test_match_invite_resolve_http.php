<?php
// http://localhost:3400/TCGEngine/DevTools/tdd-regression/test_match_invite_resolve_http.php
//
// PollLobbyUpdates.php through the REAL endpoint, for the case where the private lobby's APCu entry
// has already expired but its match is still being played. Unit coverage for the resolver itself is
// test_match_invite_resolve.php; this pins the WIRING — the response shape the waiting room branches
// on, and the lastAuthKey cookie being what identifies the viewer once the lobby is gone.
//
// ⚠ Web SAPI only (the endpoint wants APCu + sessions). Run it over HTTP, not from the CLI.
// Uses its own throwaway rootName so it never touches SWUSim's matches, and the match records are
// created in-process so the web user owns the files it will then read back over HTTP.
error_reporting(E_ALL & ~E_DEPRECATED); ini_set('display_errors', 1);
set_time_limit(0);
header('Content-Type: text/plain');

require_once __DIR__ . '/../../Core/Match/MatchFlow.php';

$SIM = 'MatchInviteHttpSim';
$URL = 'http://localhost/TCGEngine/APIs/Lobbies/PollLobbyUpdates.php';

$FAILS = 0;
function check($cond, $msg, $extra = null) {
    global $FAILS;
    echo ($cond ? '  ok: ' : '  BAD: ') . $msg . (($cond || $extra === null) ? '' : '  ' . json_encode($extra)) . "\n";
    if (!$cond) $FAILS++;
}
// $cookie: raw Cookie header value, or '' for a browser that holds no key.
function pollInvite($code, $cookie, $sim) {
    global $URL;
    $ch = curl_init($URL);
    $opts = [CURLOPT_POST => 1, CURLOPT_RETURNTRANSFER => 1, CURLOPT_TIMEOUT => 40,
             CURLOPT_POSTFIELDS => http_build_query([
                 'rootName' => $sim, 'lobbyID' => '', 'inviteCode' => $code,
                 'playerID' => '0', 'authKey' => ''])];
    if ($cookie !== '') $opts[CURLOPT_COOKIE] = $cookie;
    curl_setopt_array($ch, $opts);
    $r = curl_exec($ch); curl_close($ch);
    $j = json_decode((string)$r, true);
    return is_array($j) ? $j : ['RAW' => substr((string)$r, 0, 400)];
}

// Clean slate so reruns are deterministic.
$simRoot = __DIR__ . '/../../' . $SIM;
if (is_dir($simRoot)) {
    $rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($simRoot, FilesystemIterator::SKIP_DOTS),
                                         RecursiveIteratorIterator::CHILD_FIRST);
    foreach ($rii as $f) { $f->isDir() ? @rmdir($f->getPathname()) : @unlink($f->getPathname()); }
}

$spawned = 0;
MatchRegisterHooks($SIM, [
    'resolveLobbyDecks' => function ($lobby) {
        return [1 => ['originalDeck' => ['A1'], 'authKey' => 'hostkey1'],
                2 => ['originalDeck' => ['B1'], 'authKey' => 'guestkey2']];
    },
    'validateDeck' => function ($d, $f) { return true; },
    'setupGame'    => function ($lobby, $opts) use (&$spawned, $SIM) {
        $spawned++;
        $g = 'MIHgame' . $spawned;
        @mkdir(__DIR__ . '/../../' . $SIM . '/Games/' . $g, 0777, true);
        return $g;
    },
]);

$code  = 'b7c8d9e0f1a2b3c4d5e6f708';
$lobby = new stdClass();
$lobby->format = 'premier'; $lobby->queueType = 'bo3';
$lobby->isPrivate = true; $lobby->inviteCode = $code; $lobby->players = [];
$matchId = MatchCreateFromLobby($SIM, $lobby);

// Move the match on to game 2, exactly the state the live report was in.
MatchRecordGameResult($SIM, $matchId, 'MIHgame1', 1, 2);
$game2 = MatchSpawnNextGame($SIM, $matchId, 2, 'MIHgame1');

echo "match $matchId, current game $game2\n";
check($game2 === 'MIHgame2', 'fixture is on game 2');

echo "a participant's browser (lastAuthKey cookie from game 1):\n";
$r = pollInvite($code, 'lastAuthKey=hostkey1', $SIM);
check(!empty($r['started']),                  'started is set so the room redirects', $r);
check(($r['gameName'] ?? '') === 'MIHgame2',  'redirected to the CURRENT game, not game 1', $r);
check(intval($r['playerID'] ?? 0) === 1,      'seat resolved from the cookie', $r);
check(empty($r['gone']),                      'not reported gone', $r);

echo "the other seat's browser:\n";
$r = pollInvite($code, 'lastAuthKey=guestkey2', $SIM);
check(intval($r['playerID'] ?? 0) === 2,      'seat 2 resolved from its own cookie', $r);
check(($r['gameName'] ?? '') === 'MIHgame2',  'seat 2 also gets the current game', $r);

echo "a browser holding the link but no key:\n";
$r = pollInvite($code, '', $SIM);
check(!empty($r['gone']),                     'reported gone rather than redirected', $r);
check(($r['message'] ?? '') === 'That match is already in progress.', 'told the match is in progress', $r);
check(empty($r['gameName']),                  'no gameName leaked to a non-participant', $r);

echo "an unknown code:\n";
$r = pollInvite('ffffffffffffffffffffffff', 'lastAuthKey=hostkey1', $SIM);
check(!empty($r['gone']),                     'reported gone', $r);
check(($r['message'] ?? '') === 'That invite is invalid or has expired.', 'keeps the original expired copy', $r);

echo "once the match is complete:\n";
MatchRecordGameResult($SIM, $matchId, 'MIHgame2', 1, 2);   // seat 1 takes it 2-0
$r = pollInvite($code, 'lastAuthKey=hostkey1', $SIM);
check(!empty($r['gone']),                     'a finished match is gone, not a redirect', $r);
check(($r['message'] ?? '') === 'That match has ended.', 'says the match ended', $r);

// Leave nothing behind.
if (is_dir($simRoot)) {
    $rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($simRoot, FilesystemIterator::SKIP_DOTS),
                                         RecursiveIteratorIterator::CHILD_FIRST);
    foreach ($rii as $f) { $f->isDir() ? @rmdir($f->getPathname()) : @unlink($f->getPathname()); }
    @rmdir($simRoot);
}

echo ($FAILS === 0 ? "ALL GREEN\n" : "$FAILS FAILED\n");

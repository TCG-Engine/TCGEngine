<?php
// WHO IS IN THIS ROOM — seat names on the waiting-room roster.
//
// Owner request, 2026-09-27: "can you add usernames of players? if they are not logged in, then label
// them as we do in other places 'Guest PN'". The roster used to render "P1", "P3" and nothing else, so
// a room of four people was four seat numbers.
//
// ⚠ THE SERVER EMITS ACCOUNTS ONLY, AND A MISSING ENTRY *MEANS* "NOT LOGGED IN".
// This is the same contract as window.SWU_SEAT_USERNAMES (see the memory note on its missing
// producer): publishing "Guest PN" from the server would make a guest indistinguishable from an
// account whose name happens to read that way, and both consumers of that global treat presence as
// proof of an account. The "Guest PN" string is composed by the PAGE, from the seat number it is
// already drawing, and that is asserted in the browser harness rather than here.
//
// ⚠ RESOLVED AT JOIN, NOT PER POLL. The name is cached on the Player when the seat is taken, because
// PollLobbyUpdates runs every 1.5s for every open page and the value cannot change between polls.
// These tests therefore also pin WHERE it is resolved: a seat that gains its account later (a guest
// who signs in to chat and comes back) must pick the name up on the RECLAIM path.
//
// Runs in the WEB SAPI — it needs APCu, which the CLI SAPI does not have:
//   http://localhost:3400/TCGEngine/SWUSim/DevTools/tests/lobby_seat_names_test.php
error_reporting(E_ALL & ~E_DEPRECATED); ini_set('display_errors', 1);
set_time_limit(0);
header('Content-Type: text/plain');
require_once __DIR__ . '/../../../APIs/Lobbies/Classes/Player.php';

$FAILS = 0;
function check($cond, $msg) { global $FAILS; if ($cond) { echo "  ok: $msg\n"; } else { echo "  BAD: $msg\n"; $FAILS++; } }

$B = 'http://localhost/TCGEngine/';
$L = $B . 'APIs/Lobbies/';
$DECK = trim(file_get_contents(__DIR__ . '/fixtures/twinsuns_deck.json'));

function hit($url, $params, $jar) {
    $ch = curl_init($url);
    curl_setopt_array($ch, [CURLOPT_POST => 1, CURLOPT_POSTFIELDS => http_build_query($params),
        CURLOPT_RETURNTRANSFER => 1, CURLOPT_TIMEOUT => 45,
        CURLOPT_COOKIEJAR => $jar, CURLOPT_COOKIEFILE => $jar, CURLOPT_FOLLOWLOCATION => false]);
    $r = curl_exec($ch); curl_close($ch);
    $j = json_decode($r, true);
    return is_array($j) ? $j : ['RAW' => substr((string)$r, 0, 300)];
}
function jar() { return tempnam(sys_get_temp_dir(), 'lsn'); }
function login($jar, $user) { global $B; return hit($B . 'AccountFiles/AttemptPasswordLogin.php',
    ['submit' => '1', 'userID' => $user, 'password' => 'pass'], $jar); }

// casterMode partitions this fixture from rooms other test runs leave behind for their TTL — see the
// same note in DevTools/ui-harness/swusim-room-join-refusals-xbrowser.mjs.
function queue($jar) {
    global $L, $DECK;
    return hit($L . 'JoinQueue.php', ['rootName' => 'SWUSim', 'format' => 'twinsuns', 'queueType' => 'bo1',
        'casterMode' => '1', 'deckLink' => $DECK, 'preconstructedDeck' => '', 'game_type' => ''], $jar);
}
function joinByCode($jar, $code, $authKey = '') {
    global $L, $DECK;
    $p = ['rootName' => 'SWUSim', 'privateInviteCode' => $code, 'casterMode' => '1',
          'deckLink' => $DECK, 'preconstructedDeck' => '', 'game_type' => ''];
    if ($authKey !== '') $p['authKey'] = $authKey;
    return hit($L . 'JoinQueue.php', $p, $jar);
}
function poll($jar, $lobbyID, $authKey) {
    global $L;
    return hit($L . 'PollLobbyUpdates.php', ['rootName' => 'SWUSim', 'lobbyID' => $lobbyID,
        'inviteCode' => '', 'playerID' => 0, 'authKey' => $authKey], $jar);
}
function rowFor($poll, $playerID) {
    foreach (($poll['roster'] ?? []) as $r) if (intval($r['playerID']) === intval($playerID)) return $r;
    return null;
}
function bail($jar, $lobbyID, $r) {
    global $L;
    if (empty($r['authKey'])) return;
    hit($L . 'LeaveQueue.php', ['rootName' => 'SWUSim', 'lobbyID' => $lobbyID,
        'playerID' => $r['playerID'] ?? 0, 'authKey' => $r['authKey']], $jar);
}

$jHost = jar(); login($jHost, 'claudebot1');
$jGuest = jar();                      // deliberately NOT logged in
$jLate  = jar();                      // a guest who signs in partway through

echo "\n1. a logged-in seat carries its account name\n";
$host = queue($jHost);
check(!empty($host['success']), 'host created a room: ' . ($host['message'] ?? ''));
$lobby = strval($host['lobbyID'] ?? '');
$seen  = poll($jHost, $lobby, strval($host['authKey'] ?? ''));
$code  = strval($seen['inviteCode'] ?? '');
$hostRow = rowFor($seen, $host['playerID'] ?? 1);
check($hostRow !== null, 'the host has a roster row');
check(($hostRow['username'] ?? null) === 'claudebot1',
      'the host row carries username=claudebot1 — got: ' . json_encode($hostRow['username'] ?? null));

echo "\n2. a GUEST seat carries no username at all\n";
$guest = joinByCode($jGuest, $code);
check(!empty($guest['success']), 'a guest joined by link: ' . ($guest['message'] ?? ''));
$seen2 = poll($jHost, $lobby, strval($host['authKey'] ?? ''));
$guestRow = rowFor($seen2, $guest['playerID'] ?? 0);
check($guestRow !== null, 'the guest has a roster row');
// ⚠ ABSENT OR NULL, never the string. The page composes "Guest PN" from the seat number it draws;
// the server emitting it would make a guest look like an account to every consumer of this field.
check(($guestRow['username'] ?? null) === null,
      'the guest row has NO username — got: ' . json_encode($guestRow['username'] ?? null));
$blob = json_encode($seen2);
check(stripos($blob, 'Guest P') === false,
      'the server never puts a "Guest PN" string in the payload');

echo "\n3. the host still reads correctly with a guest in the room\n";
check((rowFor($seen2, $host['playerID'] ?? 1)['username'] ?? null) === 'claudebot1',
      'the account seat is unaffected by the guest seat');

echo "\n4. a guest who SIGNS IN and comes back gains the name on their existing seat\n";
$late = joinByCode($jLate, $code);
check(!empty($late['success']), 'a second guest joined');
$lateKey = strval($late['authKey'] ?? '');
$latePid = $late['playerID'] ?? 0;
$before = rowFor(poll($jHost, $lobby, strval($host['authKey'] ?? '')), $latePid);
check(($before['username'] ?? null) === null, 'they start with no name');
login($jLate, 'claudebot4');                       // the same browser signs in, e.g. to chat
$again = joinByCode($jLate, $code, $lateKey);      // presenting the held seat key = a RECLAIM
check(!empty($again['success']), 'the reclaim succeeded: ' . ($again['message'] ?? ''));
check(intval($again['playerID'] ?? -1) === intval($latePid),
      'and it is the SAME seat, not a second one (playerID ' . json_encode($again['playerID'] ?? null) . ')');
$after = rowFor(poll($jHost, $lobby, strval($host['authKey'] ?? '')), $latePid);
check(($after['username'] ?? null) === 'claudebot4',
      'the seat now carries username=claudebot4 — got: ' . json_encode($after['username'] ?? null));

echo "\n5. the room still reports the right size\n";
$final = poll($jHost, $lobby, strval($host['authKey'] ?? ''));
check(intval($final['numPlayers'] ?? 0) === 3, 'three seats: host + guest + signed-in guest');

echo "\nteardown\n";
bail($jGuest, $lobby, $guest);
bail($jLate,  $lobby, $again);
bail($jHost,  $lobby, $host);

echo "\n" . ($FAILS === 0 ? "ALL GREEN\n" : "$FAILS FAILED\n");

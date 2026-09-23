<?php
// http://localhost:3400/TCGEngine/DevTools/tdd-regression/test_chat_one_stream_e2e.php
// THE HEADLINE CLAIM: lobby -> game 1 -> sideboard is ONE conversation.
//
// Every other test in this feature proves one link. This one walks the chain a player actually walks,
// through the real endpoints, and is the test that fails if any link is re-keyed later.
error_reporting(E_ALL & ~E_DEPRECATED); ini_set('display_errors', 1);
set_time_limit(0);
header('Content-Type: text/plain');
require_once __DIR__ . '/../../APIs/Lobbies/Classes/Player.php';

$B = 'http://localhost/TCGEngine/'; $L = $B . 'APIs/Lobbies/';
$FAILS = 0;
function check($cond, $msg, $extra = null) {
    global $FAILS;
    echo ($cond ? '  ok: ' : '  BAD: ') . $msg . (($cond || $extra === null) ? '' : '  ' . json_encode($extra)) . "\n";
    if (!$cond) $FAILS++;
}
function req($url, $jar, $post = null) {
    $ch = curl_init($url);
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER=>1, CURLOPT_TIMEOUT=>60, CURLOPT_COOKIEJAR=>$jar, CURLOPT_COOKIEFILE=>$jar]);
    if ($post !== null) { curl_setopt($ch, CURLOPT_POST, 1); curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post)); }
    $r = curl_exec($ch); curl_close($ch); return (string)$r;
}
$texts = fn($rows) => array_map(fn($m) => $m['text'], is_array($rows) ? $rows : []);

$jar1 = tempnam(sys_get_temp_dir(), 'e2e'); $jar2 = tempnam(sys_get_temp_dir(), 'e2e');
req($B . 'AccountFiles/AttemptPasswordLogin.php', $jar1, ['submit'=>'1','userID'=>'claudebot1','password'=>'pass']);
req($B . 'AccountFiles/AttemptPasswordLogin.php', $jar2, ['submit'=>'1','userID'=>'claudebot2','password'=>'pass']);

// ⚠ A PREMIER-LEGAL list. This room is Premier because Premier starts at TWO seats; the Twin Suns
// fixture next door is 2 leaders + 80 highlander and StartRoom would refuse it as an illegal deck
// (and Twin Suns needs 3 players anyway). Same fixture the public-queue test uses; its '#' lines are
// comments the parser must not see.
$deck = trim(implode("\n", array_filter(
    explode("\n", file_get_contents(__DIR__ . '/../../SWUSim/Tests/BotFixtures/meta-2026-09/vader_yellow.txt')),
    fn($l) => !str_starts_with($l, '#'))));
$host = json_decode(req($L . 'JoinQueue.php', $jar1, ['rootName'=>'SWUSim','createPrivate'=>'1','format'=>'premier','deckLink'=>$deck]), true);
check(!empty($host['success']), 'created a private premier room', $host);
if (empty($host['success'])) { echo "\nCANNOT CONTINUE\n"; exit; }
$lobbyID = $host['lobbyID']; $k1 = $host['authKey'];
$p2 = json_decode(req($L . 'JoinQueue.php', $jar2, ['rootName'=>'SWUSim','privateInviteCode'=>$host['inviteCode'],'deckLink'=>$deck]), true);
check(!empty($p2['success']), 'a second seat joined by invite', $p2);

echo "── 1. speak in the ROOM ──\n";
$r = req($B . 'SubmitChat.php?' . http_build_query(
    ['lobbyID'=>$lobbyID,'playerID'=>1,'authKey'=>$k1,'folderPath'=>'SWUSim','chatText'=>'said in the room']), $jar1);
check(trim($r) === 'OK', 'the room message was accepted', trim($r));

echo "── 2. START the room ──\n";
$sr = json_decode(req($L . 'StartRoom.php', $jar1, ['rootName'=>'SWUSim','lobbyID'=>$lobbyID,'playerID'=>1,'authKey'=>$k1]), true);
check(!empty($sr['success']) && !empty($sr['gameName']), 'the room started and made a game', $sr);
if (empty($sr['gameName'])) { echo "\nCANNOT CONTINUE\n"; exit; }
$gameName = $sr['gameName'];

echo "── 3. the room message is READABLE FROM THE GAME ──\n";
$fromGame = $texts(json_decode(req($B . 'GetChat.php?' . http_build_query(['gameName'=>$gameName,'lastChatID'=>0]), $jar1), true));
check(in_array('said in the room', $fromGame, true),
      '★ THE HEADLINE: what was said in the waiting room is in the game chat', $fromGame);

echo "── 4. speaking in the GAME lands in the SAME stream ──\n";
req($B . 'SubmitChat.php?' . http_build_query(
    ['gameName'=>$gameName,'playerID'=>1,'authKey'=>$k1,'folderPath'=>'SWUSim','chatText'=>'said in the game']), $jar1);
$both = $texts(json_decode(req($B . 'GetChat.php?' . http_build_query(
    ['lobbyID'=>$lobbyID,'lastChatID'=>0,'playerID'=>1,'authKey'=>$k1,'folderPath'=>'SWUSim']), $jar1), true));
check(in_array('said in the room', $both, true) && in_array('said in the game', $both, true),
      '★ both messages are in ONE bucket, readable from either scope', $both);

echo "── 5. and from the MATCH scope the sideboard uses ──\n";
require_once __DIR__ . '/../../Core/Match/Match.php';
require_once __DIR__ . '/../../Core/Match/MatchFlow.php';
$ref = MatchReadRef('SWUSim', $gameName);
check(is_array($ref) && !empty($ref['matchId']), 'the game has a match ref', $ref);
$mid = $ref['matchId'] ?? '';
$m = $mid !== '' ? MatchRead('SWUSim', $mid) : [];
check(($m['chatId'] ?? '') === 'l:' . $lobbyID, 'the match adopted the lobby conversation', $m['chatId'] ?? null);
// The Sideboard page is opened with the GAME page's authKey (GameLayoutShared SWUGoSideboard), and
// the match record stores the lobby seat's key (SWUResolveLobbyDecks: 'authKey' => getAuthKey()).
// Those must be the SAME string, or the real sideboard could not read its own match.
$matchKey1 = $m['players']['1']['authKey'] ?? '';
check($matchKey1 === $k1, 'the match stores the seat\'s own authKey (the one the sideboard is opened with)',
      ['lobby' => substr($k1, 0, 8), 'match' => substr($matchKey1, 0, 8)]);
$fromMatch = $texts(json_decode(req($B . 'GetChat.php?' . http_build_query(
    ['matchId'=>$mid,'lastChatID'=>0,'playerID'=>1,'authKey'=>$k1,'folderPath'=>'SWUSim']), $jar1), true));
check(in_array('said in the room', $fromMatch, true) && in_array('said in the game', $fromMatch, true),
      '★ the SIDEBOARD scope reads the same stream', $fromMatch);

echo "── 6. a LATE joiner's room message is not orphaned ──\n";
// The reason the match ADOPTS the lobby stream instead of copying it at Start: a copy is a one-way
// snapshot, so anything said in the room afterwards would land in a bucket nobody reads again.
req($B . 'SubmitChat.php?' . http_build_query(
    ['lobbyID'=>$lobbyID,'playerID'=>2,'authKey'=>$p2['authKey'],'folderPath'=>'SWUSim','chatText'=>'said AFTER start']), $jar2);
$after = $texts(json_decode(req($B . 'GetChat.php?' . http_build_query(['gameName'=>$gameName,'lastChatID'=>0]), $jar1), true));
check(in_array('said AFTER start', $after, true),
      '★ a room message sent AFTER Start still reaches the game', $after);

echo $FAILS === 0 ? "\nALL PASS\n" : "\n$FAILS FAILED\n";

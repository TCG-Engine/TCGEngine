<?php // SWUSim/EndScreenAction.php — what a seat does on a finished 1v1 match's end screen.
// POST gameName, playerID, authKey, action =
//   request  (kind = rematch | quick | convert, bestOf = 1|3 for quick)  — ask the opponent (they get a pop-up)
//   answer   (id, accept = 1|0)                                           — accept or decline the opponent's request
//   lobby                                                                 — Return to Lobby (a match from a room)
//   requeue                                                               — this seat has rejoined the public queue
// Answers {success, message, endScreen: EndReqView(...), go?: {kind:'room', lobbyID, authKey}}. Every request, answer
// and departure writes ONE seat-0 line into the game's log/chat stream.
// Spec: docs/superpowers/specs/2026-10-30-persistent-lobby-rematch-design.md · state machine: EndScreenRequests.php
header('Content-Type: application/json');
chdir(__DIR__ . '/..');
include_once './Core/HTTPLibraries.php';
include_once './Core/NetworkingLibraries.php';
include_once './SWUSim/MatchFlow.php';
// File scope on purpose: an include inside a function makes the included files' top-level variables function-local.
require_once './SWUSim/LobbyAdapter.php';
require_once './APIs/Lobbies/Classes/LobbyBots.php';
require_once './SWUSim/Rooms.php';
require_once './SWUSim/EndScreenRequests.php';
require_once './SWUSim/TableRematch.php';   // the end-screen heartbeat store (SWUTableRematchSeen)
ChatPersistUseRoot('SWUSim');               // saved chat (Core/ChatStore.php)

$gameName = preg_replace('/[^A-Za-z0-9_]/', '', strval($_POST['gameName'] ?? ''));
$seat     = intval($_POST['playerID'] ?? 0);
$authKey  = strval($_POST['authKey'] ?? '');
$action   = strval($_POST['action'] ?? '');
$fail = function ($msg) { echo json_encode(['success' => false, 'message' => $msg]); exit; };

$ref = $gameName !== '' ? SWUReadMatchRef($gameName) : null;
$m = is_array($ref) ? SWUReadMatch($ref['matchId']) : null;
if (!is_array($m)) $fail('No such match.');
if (count($m['players'] ?? []) !== 2) $fail('This is for 1v1 matches.');
$expected = strval($m['players'][strval($seat)]['authKey'] ?? '');
if ($expected === '' || !hash_equals($expected, $authKey)) $fail('Authentication failed.');

$matchId = strval($ref['matchId']);
$origin = SWUMatchOrigin($m);
$now = time();
SWUTableRematchStampSeen($matchId, $seat, $now);

$KIND_WORDS = ['rematch' => 'the rematch', 'quick' => 'the quick rematch', 'convert' => 'the convert to Best of 3'];
$say = function (string $text) use ($gameName) { ChatAppendMessage($gameName, 0, 'Game', $text); };
$name = SWUEndScreenSeatName($m, $seat);

$err = null; $accepted = null; $go = null;
// A blocked pair has nothing to ask each other. Worded like any other unavailable request: a block is private and the
// blocked player is never told (BlockedUsers.php).
$blocked = in_array($action, ['request', 'answer'], true) && SWUAreGamePlayersBlocked($gameName);
if ($blocked) $action = 'blocked';
switch ($action) {
    case 'blocked':
        $err = 'That is not available for this match.';
        break;
    case 'request':
        $kind = strval($_POST['kind'] ?? '');
        $bestOf = intval($_POST['bestOf'] ?? 1);
        $id = bin2hex(random_bytes(6));
        $res = null;
        MatchWithLock('SWUSim', $matchId, function (&$mm) use (&$res, $seat, $kind, $bestOf, $id, $now) {
            $res = EndReqOpen($mm, $seat, $kind, $bestOf, $id, $now);
        });
        if (!is_array($res) || !$res['ok']) { $err = is_array($res) ? $res['error'] : 'Try again.'; break; }
        $say($name . ' ' . ($kind === 'rematch' ? 'requested a rematch.'
            : ($kind === 'quick' ? 'requested a quick rematch (Best of ' . ($bestOf === 3 ? 3 : 1) . ').' : 'requested to convert to Best of 3.')));
        // Both asked for the same thing: that is agreeing (call 2). Logged as the second asker accepting.
        if ($res['accepted'] !== null) { $accepted = $res['accepted']; $say($name . ' accepted ' . $KIND_WORDS[$accepted['kind']] . '.'); }
        break;
    case 'answer':
        $accept = strval($_POST['accept'] ?? '') === '1';
        $id = strval($_POST['id'] ?? '');
        $res = null;
        MatchWithLock('SWUSim', $matchId, function (&$mm) use (&$res, $seat, $id, $accept, $now) {
            $res = EndReqAnswer($mm, $seat, $id, $accept, $now);
        });
        if (!is_array($res) || !$res['ok']) { $err = is_array($res) ? $res['error'] : 'Try again.'; break; }
        $kindAnswered = $res['accepted']['kind'] ?? null;
        if ($res['accepted'] !== null) { $accepted = $res['accepted']; $say($name . ' accepted ' . $KIND_WORDS[$kindAnswered] . '.'); }
        else $say($name . ' declined ' . ($KIND_WORDS[strval($res['declined'] ?? '')] ?? 'the request') . '.');
        break;
    case 'lobby':
        if ($origin !== 'room') { $err = 'This match did not come from a room.'; break; }
        if (strval($m['state'] ?? '') !== 'complete') { $err = 'The match is not over yet.'; break; }
        $back = SWURoomReturnFromMatch($m, $seat);
        if (isset($back['error'])) { $err = $back['error']; break; }
        MatchWithLock('SWUSim', $matchId, function (&$mm) use ($seat) { EndReqMarkDeparted($mm, $seat, 'lobby'); });
        $say($name . ' returned to the lobby.');
        $go = ['kind' => 'room', 'lobbyID' => $back['lobbyID'], 'authKey' => $expected];
        break;
    case 'requeue':
        if ($origin !== 'public') { $err = 'Requeue is for public matches.'; break; }
        if (strval($m['state'] ?? '') !== 'complete') { $err = 'The match is not over yet.'; break; }
        MatchWithLock('SWUSim', $matchId, function (&$mm) use ($seat) { EndReqMarkDeparted($mm, $seat, 'requeue'); });
        $say($name . ' requeued.');
        break;
    default:
        $err = 'Unknown action.';
}

if ($accepted !== null) {
    $result = SWUEndScreenFire($m, $matchId, $origin, $accepted);
    if ($result === null) {
        // Both requests were consumed, so say it to BOTH seats — never a request that silently does nothing.
        $err = 'Could not start that — try again.';
        MatchWithLock('SWUSim', $matchId, function (&$mm) use ($err) { $mm['endScreen']['result'] = ['kind' => 'failed', 'message' => $err]; });
    } else {
        MatchWithLock('SWUSim', $matchId, function (&$mm) use ($result) {
            if ($result['kind'] === 'room') foreach ([1, 2] as $s) EndReqMarkDeparted($mm, $s, 'lobby');   // both are going there
            $mm['endScreen']['result'] = $result;
        });
        if ($result['kind'] === 'room') $go = ['kind' => 'room', 'lobbyID' => $result['lobbyID'], 'authKey' => strval($result['keys'][strval($seat)] ?? '')];
    }
}

$m = SWUReadMatch($matchId);
$out = ['success' => $err === null, 'message' => strval($err ?? ''),
        'endScreen' => EndReqView($m + ['blocked' => $blocked || SWUAreGamePlayersBlocked($gameName)], $seat, SWUTableRematchSeen($matchId), time())];
if ($go !== null) $out['go'] = $go;
echo json_encode($out);

// ── helpers ──────────────────────────────────────────────────────────────────────────────────────────────────────

// The seat's name in a log line: its username when it has an account, else "Player N" (AnnounceLeave.php's rule).
function SWUEndScreenSeatName(array $m, int $seat): string {
    if (intval($m['players'][strval($seat)]['userId'] ?? 0) > 0) {
        $n = strval(MatchSeatDisplayNames($m)[$seat] ?? '');
        if ($n !== '' && $n !== 'Guest P' . $seat) return $n;
    }
    return 'Player ' . $seat;
}

// Carry out what both players agreed. Exactly once: EndReqOpen/EndReqAnswer hand back 'accepted' only to the ONE call
// that consumed the open request under the match lock — a double click or a crossing request finds nothing open.
// Returns the result stored on the match, or null on failure.
function SWUEndScreenFire(array $m, string $matchId, string $origin, array $accepted): ?array {
    $kind = $accepted['kind'];
    if ($kind === 'quick') {
        SWURequestRematch($matchId, 1, intval($accepted['bestOf']), false);
        SWURequestRematch($matchId, 2, intval($accepted['bestOf']), false);
        $newId = SWUAcceptRematch($matchId);   // spawns game 1 + NextGame.json: both boards follow it, as before
        $nm = $newId ? SWUReadMatch($newId) : null;
        $game = is_array($nm) ? MatchCurrentGameName($nm) : '';
        if ($game === '') return null;
        if ($origin === 'room') {
            // The room follows the new match: seats stay held while it runs (call 11).
            $lobby = SWURoomRehydrate(strval($m['roomId'])) ? LobbyMutate(strval($m['roomId']), function ($l) use ($newId, $nm) {
                $l->roomMatchId = strval($newId); SWURoomSyncWithMatch($l, $nm, time()); return true;
            }) : null;
            if (is_object($lobby)) SWURoomSave($lobby);
        }
        return ['kind' => 'quick', 'gameName' => $game];
    }
    if ($kind === 'convert') {
        SWURequestConvertToBo3($matchId, 1);
        SWURequestConvertToBo3($matchId, 2);
        if (SWUAcceptConvertToBo3($matchId) === null) return null;
        // The match is running again: the room follows it now (seats held through the Bo3), not on some later poll
        // that may never come — nobody is on the room page during a match.
        if ($origin === 'room') SWURoomSyncNow(strval($m['roomId']), time());
        return ['kind' => 'convert'];
    }
    // rematch
    if ($origin === 'room') {
        $keys = [];
        foreach ([1, 2] as $s) {
            $back = SWURoomReturnFromMatch($m, $s);
            if (isset($back['error'])) return null;
            $keys[strval($s)] = strval($m['players'][strval($s)]['authKey'] ?? '');
        }
        return ['kind' => 'room', 'lobbyID' => strval($m['roomId']), 'keys' => $keys];
    }
    $room = SWURoomBuildFromPublicMatch($m, intval($accepted['from']));
    return empty($room) ? null : ['kind' => 'room'] + $room;
}

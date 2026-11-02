<?php
// High Ground room actions. POST lobbyID, authKey (the caller's own room key), action, plus the action's params.
// Spec: docs/superpowers/specs/2026-10-30-swusim-high-ground-design.md
//   toBleachers | joinLine | stepDown           — anyone, about themselves
//   reorder (order = comma-separated playerIDs) — host; the whole new line, refused (409) if it no longer matches
//   sendToBack (targetPlayerID)                 — host: someone on the hill (between matches) or in the bleachers → back of the line
//   setBestOf (queueType = bo1 | bo3)           — host, between matches
//   start                                       — host: Start next match
//   endMatch (gonePlayerID)                     — host: end the live match with no result
// Other people are always named by lobby playerID — never by their room key, which only its owner holds.
require_once __DIR__ . '/../Core/NetworkingLibraries.php';
require_once __DIR__ . '/../APIs/Lobbies/Classes/Player.php';
require_once __DIR__ . '/../APIs/Lobbies/Classes/LobbyStore.php';
require_once __DIR__ . '/HighGround.php';
require_once __DIR__ . '/MatchFlow.php';   // SWUCreateMatchFromLobby for Start — at FILE scope (it sets engine globals)

header('Content-Type: application/json');

function HighGroundActionFail($message, $status = 400) {
    http_response_code($status);
    echo json_encode(['success' => false, 'message' => $message]);
    exit;
}

$lobbyID = strval($_POST['lobbyID'] ?? '');
$authKey = strval($_POST['authKey'] ?? '');
$action  = strval($_POST['action'] ?? '');
if ($lobbyID === '' || $authKey === '' || $action === '') HighGroundActionFail('Invalid request.');

SWURoomRehydrate($lobbyID);

// Start / End match create or end a MATCH (file I/O), so they run outside the room lock in HighGround.php.
if ($action === 'start' || $action === 'endMatch') {
    $res = ($action === 'start') ? SWUHighGroundStartMatch($lobbyID, $authKey)
                                 : SWUHighGroundEndMatch($lobbyID, $authKey, intval($_POST['gonePlayerID'] ?? 0));
    if (empty($res['success'])) HighGroundActionFail($res['message'] ?? 'Failed.', intval($res['status'] ?? 400));
    unset($res['status']);
    echo json_encode($res);
    exit;
}

$selfActions = ['toBleachers' => 'HGToBleachers', 'joinLine' => 'HGJoinLine', 'stepDown' => 'HGStepDown'];
$hostActions = ['reorder', 'setBestOf', 'sendToBack'];
if (!isset($selfActions[$action]) && !in_array($action, $hostActions, true)) HighGroundActionFail('Unknown action.');

$err = null; $code = 400;
$lobby = LobbyMutate($lobbyID, function ($lobby) use ($action, $authKey, $selfActions, &$err, &$code) {
    if (!SWUIsHighGround($lobby)) { $err = 'Room not found.'; $code = 404; return false; }
    if (SWUHighGroundPlayerForKey($lobby, $authKey) === null) { $err = 'You are not in this room.'; $code = 403; return false; }
    $hg = $lobby->highGround;
    if (isset($selfActions[$action])) {
        $next = ($selfActions[$action])($hg, $authKey);
        if ($next === null) { $err = 'You can\'t do that right now.'; $code = 409; return false; }
        $lobby->highGround = $next;
        return true;
    }
    if (!SWUHighGroundIsHost($lobby, $authKey)) { $err = 'Only the host can do that.'; $code = 403; return false; }
    if ($action === 'reorder') {
        $keys = [];
        foreach (array_filter(explode(',', strval($_POST['order'] ?? '')), 'strlen') as $id) {
            $k = SWUHighGroundKeyForPlayerID($lobby, intval($id));
            if ($k === null) { $err = 'Someone in that order is no longer here — refresh.'; $code = 409; return false; }
            $keys[] = $k;
        }
        $next = HGReorderLine($hg, $keys);
        if ($next === null) { $err = 'The line changed — refresh and try again.'; $code = 409; return false; }
        $lobby->highGround = $next;
        return true;
    }
    if ($action === 'sendToBack') {
        $k = SWUHighGroundKeyForPlayerID($lobby, intval($_POST['targetPlayerID'] ?? 0));
        $next = $k !== null ? HGSendToBack($hg, $k) : null;
        if ($next === null) { $err = 'They can\'t be moved right now.'; $code = 409; return false; }
        $lobby->highGround = $next;
        return true;
    }
    // setBestOf
    $qt = strtolower(trim(strval($_POST['queueType'] ?? '')));
    if (!in_array($qt, ['bo1', 'bo3'], true)) { $err = 'Unknown match length.'; return false; }
    if ($hg['live'] !== null) { $err = 'Change the length between matches.'; $code = 409; return false; }
    if (strval($lobby->queueType ?? 'bo1') === $qt) return false;
    $lobby->queueType = $qt;
    return true;
});
if ($err !== null)   HighGroundActionFail($err, $code);
if ($lobby === null) HighGroundActionFail('Room not found.', 404);
SWURoomSave($lobby);
echo json_encode(['success' => true]);

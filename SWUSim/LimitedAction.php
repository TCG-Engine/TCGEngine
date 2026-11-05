<?php
// Limited room actions. POST lobbyID, authKey (the caller's own room key), action, plus the action's params.
// Spec: docs/superpowers/specs/2026-11-04-swusim-limited-sealed-draft-design.md §3.1, §3.3, §3.6
//   settings (settings = JSON)        — host, during setup: merged over the defaults, validated, stored with its errors
//   start                             — host: open every seat's packs and begin the build
//   kickVote | kickWait (targetPlayerID) — a live seat about another, during the build (the true-majority vote)
//   tick                              — any seat: apply the build clock and start the match once it is due
//   startRound                        — host, between Swiss rounds: start the next round once every seat is Ready
//   ready                             — a seat, between Swiss rounds: Ready with the deck it played last
// Other people are always named by lobby playerID — never by their room key, which only its owner holds.
// Reply: {success, message?, errors?}.
require_once __DIR__ . '/../Core/NetworkingLibraries.php';
require_once __DIR__ . '/../APIs/Lobbies/Classes/Player.php';
require_once __DIR__ . '/../APIs/Lobbies/Classes/LobbyStore.php';
require_once __DIR__ . '/MatchFlow.php';                 // SWUCreateMatchFromLobby — at FILE scope (it sets engine globals)
require_once __DIR__ . '/Limited/LimitedRoom.php';
require_once __DIR__ . '/Limited/LimitedSettings.php';   // packs + settings (card data) — FILE scope, after the engine
require_once __DIR__ . '/Limited/LimitedDeck.php';
require_once __DIR__ . '/Mod/DevGate.php';               // SWUIsLocalDevRequest — the test-only short pick clock

header('Content-Type: application/json');

function LimitedActionFail($message, $status = 400) {
    http_response_code($status);
    echo json_encode(['success' => false, 'message' => $message]);
    exit;
}

$lobbyID = strval($_POST['lobbyID'] ?? '');
$authKey = strval($_POST['authKey'] ?? '');
$action  = strval($_POST['action'] ?? '');
if ($lobbyID === '' || $authKey === '' || $action === '') LimitedActionFail('Invalid request.');
if (!in_array($action, ['settings', 'start', 'kickVote', 'kickWait', 'tick', 'startRound', 'ready'], true)) LimitedActionFail('Unknown action.');

SWURoomRehydrate($lobbyID);
$now = time();

if ($action === 'tick') {
    $snap = apcu_fetch($lobbyID);
    if (!SWUIsLimitedRoom($snap) || SWULimitedSeatForKey($snap, $authKey) === null) LimitedActionFail('You are not in this room.', 403);
    SWULimitedTick($lobbyID, $now);
    echo json_encode(['success' => true]);
    exit;
}

if ($action === 'startRound') {
    $snap = apcu_fetch($lobbyID);
    if (!SWUIsLimitedRoom($snap)) LimitedActionFail('Room not found.', 404);
    $me = SWULimitedSeatForKey($snap, $authKey);
    if ($me === null) LimitedActionFail('You are not in this room.', 403);
    if (!SWULimitedIsHost($snap, intval($me->getPlayerID()))) LimitedActionFail('Only the host can start the round.', 403);
    if ($snap->limited['mode'] !== 'draft' || $snap->limited['phase'] !== 'between') LimitedActionFail('There is no round to start.', 409);
    $r = SWULimitedTryStartRound($lobbyID, $now, true);
    if (empty($r['success'])) LimitedActionFail(strval($r['message'] ?? 'Everyone must be Ready first.'), 409);
    echo json_encode(['success' => true]);
    exit;
}

// Start: the packs are opened OUTSIDE the lock (a 16-pack Sealed pool is real work) from a snapshot, then committed in a
// short lock that re-checks the room — LTStart refuses if the seats or settings moved meanwhile.
$opened = null;
if ($action === 'start') {
    $snap = apcu_fetch($lobbyID);
    if (!SWUIsLimitedRoom($snap)) LimitedActionFail('Room not found.', 404);
    $me = SWULimitedSeatForKey($snap, $authKey);
    if ($me === null) LimitedActionFail('You are not in this room.', 403);
    if (!SWULimitedIsHost($snap, intval($me->getPlayerID()))) LimitedActionFail('Only the host can start.', 403);
    $lt = $snap->limited;
    if ($lt['phase'] !== 'setup') LimitedActionFail('The event has already started.', 409);
    if ($lt['settingsErrors'] !== []) LimitedActionFail('Fix the settings first: ' . implode(' ', $lt['settingsErrors']), 409);
    $live = LTLiveSeats($lt);
    $draft = $lt['mode'] === 'draft';
    if (!LTCanStart($lt)) LimitedActionFail($draft ? 'Draft needs at least 3 players.' : 'Sealed needs two players.', 409);
    $seed = random_int(1, PHP_INT_MAX);
    $rng = SWULimitedRng($seed);
    $lineups = []; $pools = [];
    if ($draft) {
        ['lineups' => $lineups, 'leaderPacks' => $leaderPacks, 'cardPacks' => $cardPacks, 'aspects' => $aspects, 'order' => $order]
            = SWULimitedOpenDraft($lt['settings'], $live, $rng);
    } else {
        $rolled = SWULimitedRollLineups($lt['settings'], count($live), $rng);   // seats 1..N, in seat order
        foreach ($live as $i => $pid) { $lineups[$pid] = $rolled[$i + 1]; $pools[$pid] = SWULimitedOpenSealedPool($lineups[$pid], $rng); }
        $leaderPacks = []; $cardPacks = []; $aspects = []; $order = $live;
    }
    // The pick clock is 5 minutes. Only a LOCAL dev request may shorten it — for the simulations and harnesses.
    $pickSeconds = LT_PICK_SECONDS;
    if ($draft && SWUIsLocalDevRequest() && isset($_POST['testPickSeconds'])) $pickSeconds = max(5, min(LT_PICK_SECONDS, intval($_POST['testPickSeconds'])));
    $opened = ['lineups' => $lineups, 'pools' => $pools, 'sets' => SWULimitedSetsInPlay($lt['settings']), 'seed' => $seed,
               'seats' => $live, 'settings' => $lt['settings'], 'draft' => $draft, 'order' => $order,
               'leaderPacks' => $leaderPacks, 'cardPacks' => $cardPacks, 'aspects' => $aspects, 'pickSeconds' => $pickSeconds];
}

$err = null; $code = 400; $errors = null;
$lobby = LobbyMutate($lobbyID, function ($l) use ($action, $authKey, $now, $opened, &$err, &$code, &$errors) {
    if (!SWUIsLimitedRoom($l)) { $err = 'Room not found.'; $code = 404; return false; }
    $me = SWULimitedSeatForKey($l, $authKey);
    if ($me === null) { $err = 'You are not in this room.'; $code = 403; return false; }
    $mePid = intval($me->getPlayerID());
    $lt = $l->limited;
    switch ($action) {
        case 'settings':
            if (!SWULimitedIsHost($l, $mePid)) { $err = 'Only the host can change the settings.'; $code = 403; return false; }
            $posted = json_decode(strval($_POST['settings'] ?? ''), true);
            $s = SWULimitedSettingsFromRequest(is_array($posted) ? $posted : [], $lt['mode']);
            $errors = SWULimitedValidateSettings($s);
            $next = LTSetSettings($lt, $s, $errors);
            if ($next === null) { $err = 'The settings are locked once the event starts.'; $code = 409; return false; }
            $l->limited = $next;
            return true;
        case 'start':
            if (LTLiveSeats($lt) !== $opened['seats'] || $lt['settings'] !== $opened['settings']) { $err = 'The room changed — press Start again.'; $code = 409; return false; }
            if ($opened['draft']) {
                $next = LTDraftBegin($lt, $opened['order'], $opened['leaderPacks'], $opened['cardPacks'], $opened['aspects'],
                                     $opened['seed'], $opened['pickSeconds'], $now);
                if ($next !== null) { $next['lineups'] = $opened['lineups']; $next['setsInPlay'] = $opened['sets']; }
            } else {
                $next = LTStart($lt, $opened['lineups'], $opened['pools'], $opened['sets'], $opened['seed'], $now);
            }
            if ($next === null) { $err = 'The event cannot start yet.'; $code = 409; return false; }
            $l->limited = $next;
            // A started room: no deck swaps, no host removal, and joining another room no longer releases this seat
            // (LobbyHasStarted). The kick vote replaces the host's Remove from here on.
            $l->state = 'started';
            return true;
        case 'kickVote':
        case 'kickWait':
            $target = intval($_POST['targetPlayerID'] ?? 0);
            $next = ($action === 'kickVote') ? LTKickVote($lt, $mePid, $target, SWULimitedLastSeen($l), $now)
                                             : LTKickWait($lt, $mePid, $target, $now);
            if ($next === null) { $err = 'You can\'t do that right now.'; $code = 409; return false; }
            $l->limited = $next;
            return true;
        case 'ready':
            // Between rounds a seat may Ready straight from the room with the deck it played last (it is still submitted).
            if (!empty($lt['seats'][$mePid]['dropped'])) { $err = 'You are no longer in this event.'; $code = 409; return false; }
            $next = $lt['phase'] === 'between' ? LTReady($lt, $mePid) : null;
            if ($next === null) { $err = 'Submit a legal deck first.'; $code = 409; return false; }
            $l->limited = $next;
            $me->setDeckLink(SWULimitedDeckJson($next['seats'][$mePid]['deck'], $next['pools'][$mePid] ?? []));
            $me->setDeckOk(true);
            $me->setReady(true);
            return true;
    }
    return false;
});
if ($err !== null) LimitedActionFail($err, $code);
if ($lobby === null) LimitedActionFail('The room is busy — try again.', 409);
SWURoomSaveLatest($lobbyID);
// The last Ready, or a kick that removed the host while everyone else was Ready, may make the next round due now — the
// start needs the engine, which only these endpoints load.
if ($action === 'ready' || $action === 'kickVote') SWULimitedTick($lobbyID, $now);
$out = ['success' => true];
if ($errors !== null) $out['errors'] = array_values($errors);
echo json_encode($out);

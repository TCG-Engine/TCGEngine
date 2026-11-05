<?php
// The Limited DRAFT endpoint — the pick screen's (SWUSim/LimitedDraft.php). POST lobbyID, authKey (the caller's room key),
// action = state | pick (packId, cardId). Spec: docs/superpowers/specs/2026-11-04-swusim-limited-sealed-draft-design.md §3.2.
//   state  {success, phase, dropped, stage, round, rounds, direction, pickNo, packSize, front: null|{packId, cards[]}, queued,
//           endsIn, mine: {leaders[], picks[]}, lean: {counts, pair}, playerID, pod: [{playerID, name, queued, picks, me,
//           dropped, autoPicked, leaders[] (public)}], kick: [the room poll's kick prompts], cards: {id: facts}} — the caller's OWN packs and picks only; also its heartbeat and the
//           draft's clock (overdue picks are made here, as on every room poll)
//   pick   the front pack's id + one of its cards → the same reply as state (refused if that pack is no longer at the front)
require_once __DIR__ . '/../Core/NetworkingLibraries.php';
require_once __DIR__ . '/../APIs/Lobbies/Classes/Player.php';
require_once __DIR__ . '/../APIs/Lobbies/Classes/LobbyStore.php';
require_once __DIR__ . '/MatchFlow.php';                 // the engine, as the other Limited endpoints — FILE scope
require_once __DIR__ . '/Limited/LimitedRoom.php';
require_once __DIR__ . '/Limited/LimitedSettings.php';   // card data — FILE scope, after the engine
require_once __DIR__ . '/Limited/LimitedDeck.php';       // SWULimitedCardFacts
require_once __DIR__ . '/Limited/LimitedBot.php';         // the tick builds bot seats' decks (Plan 5)

header('Content-Type: application/json');

function LimitedDraftFail($message, $status = 400) {
    http_response_code($status);
    echo json_encode(['success' => false, 'message' => $message]);
    exit;
}

$lobbyID = strval($_POST['lobbyID'] ?? '');
$authKey = strval($_POST['authKey'] ?? '');
$action  = strval($_POST['action'] ?? '');
if ($lobbyID === '' || $authKey === '' || !in_array($action, ['state', 'pick'], true)) LimitedDraftFail('Invalid request.');
SWURoomRehydrate($lobbyID);
$now = time();

$err = null; $code = 400; $pid = 0;
$lobby = LobbyMutate($lobbyID, function ($l) use ($action, $authKey, $now, &$err, &$code, &$pid) {
    if (!SWUIsLimitedRoom($l) || ($l->limited['mode'] ?? '') !== 'draft') { $err = 'Room not found.'; $code = 404; return false; }
    $me = SWULimitedSeatForKey($l, $authKey);
    if ($me === null || !isset($l->limited['seats'][intval($me->getPlayerID())])) { $err = 'You are not in this draft.'; $code = 403; return false; }
    $pid = intval($me->getPlayerID());
    $me->touch($now);
    $lt = LTDraftTick($l->limited, SWULimitedLastSeen($l), $now);
    if ($action === 'pick') {
        $next = LTDraftPick($lt, $pid, intval($_POST['packId'] ?? 0), strval($_POST['cardId'] ?? ''), $now);
        if ($next === null) { $err = 'That pack has moved on — here is your next one.'; $code = 409; $l->limited = $lt; return true; }
        $lt = LTDraftTick($next, SWULimitedLastSeen($l), $now);
    }
    $l->limited = $lt;
    return true;
});
if ($lobby === null && $err === null) LimitedDraftFail('The room is busy — try again.', 409);
if ($lobby === null) LimitedDraftFail($err, $code);
SWURoomSaveIfChanged($lobby);   // most polls change nothing — the disk copy is rewritten only when the draft moved

$lt = $lobby->limited;
$names = [];
foreach ($lt['seats'] as $p => $s) $names[$p] = strval($s['name']);
$out = ['success' => $err === null, 'phase' => $lt['phase'], 'playerID' => $pid,
        'dropped' => !empty($lt['seats'][$pid]['dropped'])];   // kicked (or left): the screen says so instead of drafting on
if ($err !== null) $out['message'] = $err;
$d = $lt['draft'] ?? null;
if (is_array($d)) {
    $front = LTDraftFront($d, $pid);
    $mine = ['leaders' => array_values($d['leaders'][$pid] ?? []), 'picks' => array_values($d['picks'][$pid] ?? [])];
    // The pack's own opening size (an Icons pack is 8–9 cards, a seat's leader pack as many leaders as it opened); a pack
    // from before sizes were stored falls back to the old fixed sizes.
    $packSize = $front !== null && isset($front['size']) ? intval($front['size']) : ($d['stage'] === 'leaders' ? intval($d['rounds']) : 14);
    $pod = [];
    foreach ($d['order'] as $p) {
        $pod[] = ['playerID' => $p, 'name' => $names[$p] ?? '', 'queued' => count($d['queues'][$p] ?? []),
                  'picks' => count($d['leaders'][$p] ?? []) + count($d['picks'][$p] ?? []), 'me' => $p === $pid,
                  'dropped' => !empty($lt['seats'][$p]['dropped']), 'autoPicked' => intval($d['autoPicks'][$p] ?? 0) > 0,
                  // Drafted leaders are public (spec §4.1 [my call]; owner 2026-11-05): they shape how the others draft.
                  // Card picks stay private — only their count is shown.
                  'leaders' => array_values($d['leaders'][$p] ?? []),
                  'bot' => LTIsBot($lt, $p)];   // Plan 5: a bot seat (it picks at once)
    }
    $out += [
        'stage' => $d['stage'], 'round' => intval($d['round']), 'rounds' => intval($d['rounds']), 'direction' => LTDraftDirection($d),
        // The set of the pack in front of you. Host's Choice: everyone opened the same lineup, so it is this round's entry
        // (an Icons pack's commons are reprints, whose cards belong to older sets). Chaos: the pack was opened by whoever
        // passed it, so read it off its cards.
        'packSet' => !$front || $d['stage'] !== 'cards' || !$front['cards'] ? ''
            : (($lt['settings']['packMode'] ?? '') === 'hostchoice' ? strval($lt['lineups'][$pid][intval($d['round']) - 1] ?? '')
                                                                   : strval(CardSet($front['cards'][0]) ?? '')),
        'pickNo' => $front ? $packSize - count($front['cards']) + 1 : null, 'packSize' => $packSize,
        'front' => $front ? ['packId' => $front['id'], 'cards' => array_values($front['cards'])] : null,
        'queued' => max(0, count($d['queues'][$pid] ?? []) - 1),
        'endsIn' => ($front && $d['frontSince'][$pid] !== null) ? max(0, $d['frontSince'][$pid] + intval($d['pickSeconds']) - $now) : null,
        'mine' => $mine, 'lean' => LTDraftLean($lt, $pid), 'pod' => $pod,
        'kick' => SWULimitedKickView($lt, $pid, SWULimitedLastSeen($lobby), $now),
        'cards' => SWULimitedCardFacts(array_merge($front['cards'] ?? [], $mine['leaders'], $mine['picks'], ...array_column($pod, 'leaders'))),
    ];
}
echo json_encode($out);

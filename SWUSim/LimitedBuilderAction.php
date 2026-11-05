<?php
// The Limited deckbuilder's endpoint. POST action = state | submit | ready, plus:
//   build context  — lobbyID + authKey (the caller's room key): the room's 30-minute build
// Spec: docs/superpowers/specs/2026-11-04-swusim-limited-sealed-draft-design.md §3.4.
//   state   {success, context, pool[], basesInPlay[], cards{id: facts}, deck: null|{leader, base, main[]}, submitted, ready,
//            endsIn, opponentReady, gameName, bye}  — the caller's OWN seat only; also the builder page's heartbeat. In a Draft
//            pod, endsIn is the build clock or the between-rounds break, and gameName the caller's table in the round
//   submit  deck = JSON {leader, base, main[]} → {success, errors[]} (errors name cards by title)
//   ready   lock in the submitted deck → {success}
require_once __DIR__ . '/../Core/NetworkingLibraries.php';
require_once __DIR__ . '/../APIs/Lobbies/Classes/Player.php';
require_once __DIR__ . '/../APIs/Lobbies/Classes/LobbyStore.php';
require_once __DIR__ . '/MatchFlow.php';                 // SWUCreateMatchFromLobby (the tick starts the match) — FILE scope
require_once __DIR__ . '/Limited/LimitedRoom.php';
require_once __DIR__ . '/Limited/LimitedSettings.php';   // card data — FILE scope, after the engine
require_once __DIR__ . '/Limited/LimitedDeck.php';

header('Content-Type: application/json');

function LimitedBuilderFail($message, $status = 400, array $extra = []) {
    http_response_code($status);
    echo json_encode(['success' => false, 'message' => $message] + $extra);
    exit;
}

// The posted deck as {leader, base, main[]} of plain strings.
function LimitedBuilderPostedDeck(): array {
    $d = json_decode(strval($_POST['deck'] ?? ''), true);
    if (!is_array($d)) $d = [];
    $main = is_array($d['main'] ?? null) ? array_values(array_map('strval', array_filter($d['main'], 'is_scalar'))) : [];
    return ['leader' => is_scalar($d['leader'] ?? null) ? strval($d['leader']) : '',
            'base' => is_scalar($d['base'] ?? null) ? strval($d['base']) : '', 'main' => $main];
}

// Every base the seat may pick: the common bases of the sets in play, then the pool's own bases.
function LimitedBuilderBases(array $pool, array $setsInPlay): array {
    $own = array_values(array_filter($pool, fn($c) => CardType($c) === 'Base'));
    return array_values(array_unique(array_merge(SWULimitedCommonBasesInPlay($setsInPlay), $own)));
}

// The pool-bound check, with every error named by title for the player.
function LimitedBuilderCheck(array $pool, array $setsInPlay, array $deck): array {
    return array_map('SWULimitedErrorText', SWUCheckLimitedDeck($pool, $setsInPlay, $deck['leader'], $deck['base'], $deck['main']));
}

$action = strval($_POST['action'] ?? '');
if (!in_array($action, ['state', 'submit', 'ready'], true)) LimitedBuilderFail('Unknown action.');
$now = time();

// ── Match context: between the games of a Bo3 (Sideboard.php sends a Limited match here) ──────────────────────────────
// The match carries every seat's pool (SWULimitedStartMatch), so a deck is checked against it exactly as in the build,
// and the leader and base may change. Lists are saved and readied through Core's two-step sideboard; its clock is the
// match's own 5 minutes, and a seat that never readies keeps the deck it played last (sideboardFallback 'current').
$matchId = preg_replace('/[^A-Za-z0-9_]/', '', strval($_POST['matchId'] ?? ''));
if ($matchId !== '') {
    $seat = intval($_POST['playerID'] ?? 0);
    $key = strval($_POST['authKey'] ?? '');
    $m = SWUReadMatch($matchId);
    if (!is_array($m) || empty($m['limited'])) LimitedBuilderFail('Unknown match.', 404);
    if ($seat !== 1 && $seat !== 2) LimitedBuilderFail('Bad seat.');
    $expected = strval($m['players'][strval($seat)]['authKey'] ?? '');
    if ($expected === '' || !hash_equals($expected, $key)) LimitedBuilderFail('Auth failed.', 403);
    SWUSideboardTimeoutCheck($matchId);   // this page's poll is a heartbeat of the 5-minute clock
    $m = SWUReadMatch($matchId);
    $pool = $m['limited']['pools'][$seat] ?? [];
    $sets = $m['limited']['setsInPlay'] ?? [];
    $p = $m['players'][strval($seat)] ?? [];
    $last = $p['currentDeck'] ?? $p['originalDeck'] ?? [];
    $sb = is_array($m['sideboard'][strval($seat)] ?? null) ? $m['sideboard'][strval($seat)] : [];
    if ($action === 'submit') {
        $deck = LimitedBuilderPostedDeck();
        $errors = LimitedBuilderCheck($pool, $sets, $deck);
        if ($errors !== []) LimitedBuilderFail('That deck is not legal yet.', 400, ['errors' => $errors]);
        $resolved = SWUResolveDeckInput(SWULimitedDeckJson($deck, $pool));
        if (empty($resolved['success']) || !MatchSaveSideboardDeck('SWUSim', $matchId, $seat, $resolved)) {
            LimitedBuilderFail('You can\'t change your deck now.', 409, ['errors' => []]);
        }
        echo json_encode(['success' => true, 'errors' => []]);
        exit;
    }
    if ($action === 'ready') {
        if (!MatchReadySideboardSeat('SWUSim', $matchId, $seat, $last)) LimitedBuilderFail('You can\'t ready now.', 409);
        MatchMaybeSpawnAfterSideboard('SWUSim', $matchId);
        echo json_encode(['success' => true]);
        exit;
    }
    $state = strval($m['state'] ?? '');
    $bases = LimitedBuilderBases($pool, $sets);
    echo json_encode([
        'success' => true, 'context' => 'match', 'phase' => $state,
        'pool' => $pool, 'basesInPlay' => $bases, 'cards' => SWULimitedCardFacts(array_merge($pool, $bases)),
        'deck' => SWULimitedDeckFromResolved(is_array($sb['deck'] ?? null) ? $sb['deck'] : $last),
        'submitted' => ($sb['deck'] ?? null) !== null, 'ready' => !empty($sb['ready']),
        'endsIn' => ($state === 'sideboarding' && isset($m['sideboardDeadline'])) ? max(0, intval($m['sideboardDeadline']) - $now) : null,
        'opponentReady' => !empty($m['sideboard'][strval(3 - $seat)]['ready']),
        // Once the next game exists (or the match ended), where the page goes next.
        'gameName' => ($state !== 'sideboarding' && !empty($m['games'])) ? strval($m['games'][count($m['games']) - 1]['gameName'] ?? '') : null,
    ]);
    exit;
}

// ── Build context: the room ───────────────────────────────────────────────────────────────────────────────────────────
$lobbyID = strval($_POST['lobbyID'] ?? '');
$authKey = strval($_POST['authKey'] ?? '');
if ($lobbyID === '' || $authKey === '') LimitedBuilderFail('Invalid request.');
SWURoomRehydrate($lobbyID);
$snap = apcu_fetch($lobbyID);
if (!SWUIsLimitedRoom($snap)) LimitedBuilderFail('Room not found.', 404);
$me = SWULimitedSeatForKey($snap, $authKey);
if ($me === null || !isset($snap->limited['seats'][intval($me->getPlayerID())])) LimitedBuilderFail('You are not in this event.', 403);
$pid = intval($me->getPlayerID());
$pool = $snap->limited['pools'][$pid] ?? [];
$sets = $snap->limited['setsInPlay'] ?? [];

if ($action === 'submit') {
    $deck = LimitedBuilderPostedDeck();
    $errors = LimitedBuilderCheck($pool, $sets, $deck);
    if ($errors !== []) LimitedBuilderFail('That deck is not legal yet.', 400, ['errors' => $errors]);
    $refused = false;
    $lobby = LobbyMutate($lobbyID, function ($l) use ($pid, $deck, &$refused) {
        $next = SWUIsLimitedRoom($l) ? LTSubmitDeck($l->limited, $pid, $deck) : null;
        if ($next === null) { $refused = true; return false; }
        $l->limited = $next;
        return true;
    });
    if ($refused) LimitedBuilderFail('You can\'t change your deck now.', 409, ['errors' => []]);
    if ($lobby === null) LimitedBuilderFail('The room is busy — try again.', 409, ['errors' => []]);
    SWURoomSaveLatest($lobbyID);
    echo json_encode(['success' => true, 'errors' => []]);
    exit;
}

if ($action === 'ready') {
    $refused = false;
    $lobby = LobbyMutate($lobbyID, function ($l) use ($pid, $pool, &$refused) {
        $next = SWUIsLimitedRoom($l) ? LTReady($l->limited, $pid) : null;
        if ($next === null) { $refused = true; return false; }
        $l->limited = $next;
        // The seat's deck as the child match will read it (SWUResolveLobbyDecks resolves deckLink).
        foreach ($l->players as $p) {
            if (!($p instanceof Player) || intval($p->getPlayerID()) !== $pid) continue;
            $p->setDeckLink(SWULimitedDeckJson($next['seats'][$pid]['deck'], $pool));
            $p->setDeckOk(true);
            $p->setReady(true);
        }
        return true;
    });
    if ($refused) LimitedBuilderFail('Submit a legal deck first.', 409);
    if ($lobby === null) LimitedBuilderFail('The room is busy — try again.', 409);
    SWURoomSaveLatest($lobbyID);
    SWULimitedTick($lobbyID, $now);   // the last Ready starts the match
    echo json_encode(['success' => true]);
    exit;
}

// state — also the builder page's heartbeat, and the room clock's tick (the deadline, the start once due).
LobbyMutate($lobbyID, function ($l) use ($authKey) {
    $p = SWULimitedSeatForKey($l, $authKey);
    if ($p === null) return false;
    $p->touch();
    return true;
});
SWULimitedTick($lobbyID, $now);
$lt = apcu_fetch($lobbyID)->limited ?? $snap->limited;
$seat = $lt['seats'][$pid];
$others = array_filter(LTLiveSeats($lt), fn($p) => $p !== $pid);
$bases = LimitedBuilderBases($pool, $sets);
$table = $lt['mode'] === 'draft' ? LTSwissMyPairing($lt, $pid) : null;
$endsIn = null;
if ($lt['phase'] === 'building' && $lt['buildDeadline'] !== null) $endsIn = max(0, $lt['buildDeadline'] - $now);
if ($lt['phase'] === 'between' && ($lt['swiss']['betweenDeadline'] ?? null) !== null) $endsIn = max(0, $lt['swiss']['betweenDeadline'] - $now);
$gameName = null;
if ($lt['phase'] === 'playing') {
    if ($lt['mode'] === 'draft') $gameName = ($table !== null && empty($table['bye'])) ? strval($table['gameName']) : null;
    elseif (is_array($lt['match'])) $gameName = strval($lt['match']['gameName']);
}
echo json_encode([
    'success' => true, 'context' => 'build', 'phase' => $lt['phase'], 'mode' => $lt['mode'],
    'playerID' => $pid,   // the caller's own lobby seat: the page's chat sends as it (the lobby scope trusts the posted id)
    'pool' => $pool, 'basesInPlay' => $bases, 'cards' => SWULimitedCardFacts(array_merge($pool, $bases)),
    'deck' => $seat['deck'], 'submitted' => !empty($seat['submitted']), 'ready' => !empty($seat['ready']),
    'endsIn' => $endsIn,
    'opponentReady' => $others !== [] && array_filter($others, fn($p) => empty($lt['seats'][$p]['ready'])) === [],
    'gameName' => $gameName,
    'bye' => $table !== null && !empty($table['bye']),
]);

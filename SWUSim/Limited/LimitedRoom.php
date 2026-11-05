<?php
// Limited rooms — the glue between the pure state (LimitedRoomState.php) and the lobby, its disk snapshot and the match
// layer. Spec: docs/superpowers/specs/2026-11-04-swusim-limited-sealed-draft-design.md §3.1, §3.3, §4.1.
//
// A Limited room is a private SWUSim lobby (format 'limited') carrying $lobby->limited. It is a persistent room (snapshot
// on disk, SWUSim/Rooms.php) — an event lasts hours and must outlive APCu — and it never gets a gameName: the room stays
// the event's home while its match runs, exactly like High Ground.
//
// ⚠ NO CARD DICTIONARY AND NO ENGINE HERE: the lobby poll loads this file on every request. Anything that opens packs or
// checks a deck (LimitedSettings.php, LimitedDeck.php) and anything that creates a match (SWUSim/MatchFlow.php) is loaded
// at FILE scope by the endpoints that need it (LimitedAction.php, LimitedBuilderAction.php).
require_once __DIR__ . '/LimitedRoomState.php';
require_once __DIR__ . '/../Rooms.php';
require_once __DIR__ . '/../../AppCore/SWU/Formats.php';

function SWUIsLimitedRoom($lobby): bool {
    return is_object($lobby) && is_array($lobby->limited ?? null);
}

function _SWULimitedSeatName(Player $p): string {
    $n = strval($p->getUsername());
    return $n !== '' ? $n : 'Guest';
}

// The host's seat opens the room. Nobody is ever Ready in the lobby's own sense until they have built a deck: a private
// room otherwise readies a seat that arrives with a legal deck.
function SWULimitedCreate(object $lobby, Player $host, string $mode): void {
    $host->setReady(false);
    $host->setDeckOk(false);
    $lobby->limited = LTAddSeat(LTNew($mode), intval($host->getPlayerID()), _SWULimitedSeatName($host));
    $lobby->persistentRoom = true;
    $lobby->maxPlayers = LTSeatCap($lobby->limited);
}

// A NEW seat joining (never a reclaim). Returns the refusal message, or null once seated.
function SWULimitedOnJoin(object $lobby, Player $p): ?string {
    $next = LTAddSeat($lobby->limited, intval($p->getPlayerID()), _SWULimitedSeatName($p));
    if ($next === null) return $lobby->limited['phase'] === 'setup' ? 'That room is full.' : 'That event has already started.';
    $p->setReady(false);
    $p->setDeckOk(false);
    $lobby->limited = $next;
    return null;
}

// Someone left, was removed by the host (setup only), or had their seat released for another room.
function SWULimitedOnLeave(object $lobby, int $playerID): void {
    $lobby->limited = LTDropSeat($lobby->limited, $playerID, 'left');
}

function SWULimitedSeatForKey(object $lobby, string $authKey): ?Player {
    if ($authKey === '') return null;
    foreach (($lobby->players ?? []) as $p) {
        if (($p instanceof Player) && hash_equals(strval($p->getAuthKey()), $authKey)) return $p;
    }
    return null;
}

function SWULimitedIsHost(object $lobby, int $playerID): bool {
    return $playerID > 0 && $playerID === intval($lobby->hostPlayerID ?? 0);
}

// [pid => the seat's last heartbeat] — the room poll and the builder poll both stamp it (Player::touch).
function SWULimitedLastSeen(object $lobby): array {
    $out = [];
    foreach (($lobby->players ?? []) as $p) {
        if ($p instanceof Player) $out[intval($p->getPlayerID())] = intval($p->getLastSeen());
    }
    return $out;
}

// Everything the room screen draws for $viewerKey. Never a pool, a lineup other than the viewer's own, or a deck: those
// are private until the games start (spec §4.1), and the builder fetches the viewer's own through LimitedBuilderAction.
function SWULimitedPollView(object $lobby, string $viewerKey, int $now): array {
    $lt = $lobby->limited;
    $me = SWULimitedSeatForKey($lobby, $viewerKey);
    $mePid = ($me !== null && isset($lt['seats'][intval($me->getPlayerID())])) ? intval($me->getPlayerID()) : 0;
    $seen = SWULimitedLastSeen($lobby);
    $seats = [];
    foreach ($lt['seats'] as $pid => $s) {
        $seats[] = ['playerID' => intval($pid), 'name' => strval($s['name']), 'submitted' => !empty($s['submitted']),
                    'ready' => !empty($s['ready']), 'dropped' => !empty($s['dropped']),
                    'connected' => ($now - intval($seen[$pid] ?? 0)) <= LT_DISCONNECT_SECONDS];
    }
    $isHost = $mePid > 0 && SWULimitedIsHost($lobby, $mePid);
    $view = [
        'mode' => $lt['mode'], 'phase' => $lt['phase'], 'isHost' => $isHost,
        'canStart' => $isHost && $lt['phase'] === 'setup' && $lt['settingsErrors'] === [] && count(LTLiveSeats($lt)) === LTSeatCap($lt),
        'settings' => $lt['settings'], 'settingsErrors' => $lt['settingsErrors'], 'boosterSets' => array_keys(SWUBoosterSets()),
        'packRange' => SWULimitedPackRange($lt['mode']), 'seats' => $seats, 'hostPlayerID' => intval($lobby->hostPlayerID ?? 0),
        'buildEndsIn' => ($lt['phase'] === 'building' && $lt['buildDeadline'] !== null) ? max(0, $lt['buildDeadline'] - $now) : null,
        'me' => null, 'live' => null, 'result' => $lt['result'], 'kick' => [],
    ];
    if ($mePid === 0) return $view;
    $view['me'] = ['playerID' => $mePid, 'lineup' => array_values($lt['lineups'][$mePid] ?? []),
                   'submitted' => !empty($lt['seats'][$mePid]['submitted']), 'ready' => !empty($lt['seats'][$mePid]['ready'])];
    if ($lt['phase'] === 'playing' && is_array($lt['match'])) {
        $seat = array_search($mePid, $lt['match']['seats'], true);
        if ($seat !== false) $view['live'] = ['gameName' => strval($lt['match']['gameName']), 'seat' => intval($seat)];
    }
    foreach (LTKickableSeats($lt, $seen, $now) as $target) {
        if ($target === $mePid) continue;
        $voters = LTKickVoters($lt, $target);
        $votes = array_keys($lt['kick'][$target]['votes'] ?? []);
        $view['kick'][] = ['target' => $target, 'name' => strval($lt['seats'][$target]['name']),
                           'votes' => count(array_intersect($votes, $voters)), 'needed' => LTKickVotesNeeded(count($voters)),
                           'iVoted' => in_array($mePid, $votes, true), 'canVote' => in_array($mePid, $voters, true)];
    }
    return $view;
}

// The room's clock: the build deadline readies submitted seats, and once every seat is Ready the match is claimed and
// created. Creating a match needs the engine, so the START half runs only where SWUSim/MatchFlow.php is loaded (the
// Limited endpoints); the lobby poll applies the deadline in its own lock (it has no engine) and the room page asks
// LimitedAction.php to tick when the start is due.
function SWULimitedTick(string $lobbyID, int $now): void {
    $snap = apcu_fetch($lobbyID);
    if (!SWUIsLimitedRoom($snap)) return;
    $canStart = function_exists('SWUCreateMatchFromLobby');   // the engine is loaded (a Limited endpoint)
    $live = SWULimitedLiveMatch($snap);                       // read BEFORE the lock (I/O)
    $claimed = false; $changed = false;
    $lobby = LobbyMutate($lobbyID, function ($l) use ($now, $canStart, $live, &$claimed, &$changed) {
        if (!SWUIsLimitedRoom($l)) return false;
        $before = $l->limited;
        $lt = LTExpireBuild($l->limited, $now);
        if ($live !== null) $lt = LTApplyMatch($lt, $live);   // a result the gameResultRecorded hook missed
        if ($canStart && ($c = LTClaimStart($lt, $now)) !== null) { $lt = $c; $claimed = true; }
        $l->limited = $lt;
        $changed = $lt !== $before;
        return $changed;
    });
    // The builder ticks every 2 s: only a real change goes to disk (LobbyMutate returns the lobby even when nothing changed).
    if ($lobby === null || !$changed) return;
    SWURoomSaveLatest($lobbyID);
    if ($claimed) SWULimitedStartMatch($lobbyID);
}

// The room's running match (its Match.json), read BEFORE any lobby lock — or null outside a match.
function SWULimitedLiveMatch(object $lobby): ?array {
    if (!SWUIsLimitedRoom($lobby) || $lobby->limited['phase'] !== 'playing' || !is_array($lobby->limited['match'] ?? null)) return null;
    $m = MatchRead('SWUSim', strval($lobby->limited['match']['matchId']));
    return is_array($m) ? $m : null;
}

// ── The match ─────────────────────────────────────────────────────────────────────────────────────────────────────────

// Create the event's match once the tick has claimed the start: an ordinary private two-seat match built from the live
// seats, High Ground's way — each seat's Player is CLONED onto match seat 1/2 (the room keeps its playerIDs; the seat
// keeps its room key, which is therefore also its key in the game), and its deckLink is set here, from the seat's
// submitted deck in the blob, as the standard deck JSON (SWULimitedDeckJson — LimitedDeck.php, loaded by the endpoint).
// The match carries every seat's pool, so game 2 and 3 stay bound to it (LimitedBuilderAction.php), plus the 5-minute
// between-games cap and the "keep your last deck" timeout (Core/Match: sideboardSeconds / sideboardFallback).
// Needs the engine (SWUSim/MatchFlow.php) — only the Limited endpoints call this.
function SWULimitedStartMatch(string $lobbyID): array {
    $snap = apcu_fetch($lobbyID);
    if (!SWUIsLimitedRoom($snap) || empty($snap->limited['starting'])) return ['success' => false, 'message' => 'Nothing to start.'];
    $lt = $snap->limited;
    $release = function () use ($lobbyID) {
        LobbyMutate($lobbyID, function ($l) { if (SWUIsLimitedRoom($l)) $l->limited = LTReleaseStart($l->limited); return true; });
        SWURoomSaveLatest($lobbyID);
    };
    $stand = new stdClass();
    $stand->id = $lobbyID;                 // the match chat is the room's (l:<lobbyID>)
    $stand->rootName = 'SWUSim';
    $stand->format = 'limited';
    $stand->queueType = intval($lt['settings']['bestOf'] ?? 1) === 3 ? 'bo3' : 'bo1';
    $stand->isPrivate = true;
    $stand->players = [];
    $seats = []; $pools = [];
    foreach (LTLiveSeats($lt) as $i => $pid) {
        $p = null;
        foreach ($snap->players as $q) if (($q instanceof Player) && intval($q->getPlayerID()) === $pid) $p = $q;
        $deck = $lt['seats'][$pid]['deck'] ?? null;
        if ($p === null || !is_array($deck)) { $release(); return ['success' => false, 'message' => 'A seat is missing its deck.']; }
        $c = clone $p;
        $c->setPlayerID($i + 1);
        // The deck comes from the BLOB, never the Player: the build clock readies a submitted seat that never pressed Ready
        // (LTExpireBuild), and only Ready used to write deckLink — so that seat had none and the match could never start.
        $c->setDeckLink(SWULimitedDeckJson($deck, $lt['pools'][$pid]));
        $c->setDeckOk(true);
        $stand->players[] = $c;
        $seats[$i + 1] = $pid;
        $pools[$i + 1] = $lt['pools'][$pid];
    }
    $stand->limited = ['pools' => $pools, 'setsInPlay' => $lt['setsInPlay']];   // SWUResolveLobbyDecks validates against these
    try { $matchId = SWUCreateMatchFromLobby($stand); } catch (Throwable $e) { $matchId = null; }
    $gameName = strval($stand->gameName ?? '');
    if (!$matchId || $gameName === '') { $release(); return ['success' => false, 'message' => 'The match could not be created.']; }
    MatchWithLock('SWUSim', $matchId, function (&$m) use ($lobbyID, $pools, $seats, $lt) {
        $m['roomId'] = $lobbyID;
        $m['limited'] = ['pools' => $pools, 'setsInPlay' => $lt['setsInPlay'], 'seats' => $seats];
        $m['sideboardSeconds'] = LT_SIDEBOARD_SECONDS;
        $m['sideboardFallback'] = 'current';
    });
    if (function_exists('RegisterActiveGame')) RegisterActiveGame('SWUSim', $gameName, true);
    $committed = false;
    LobbyMutate($lobbyID, function ($l) use ($matchId, $gameName, $seats, &$committed) {
        if (!SWUIsLimitedRoom($l)) return false;
        $next = LTBeginMatch($l->limited, strval($matchId), $gameName, $seats);
        if ($next === null) return false;
        $l->limited = $next;
        $committed = true;
        return true;
    });
    if (!$committed) {
        // The event ended under the start (a kick or a leave): the match we built has nobody to play it.
        MatchAbortNoResult('SWUSim', strval($matchId));
        if (function_exists('RemoveActiveGame')) RemoveActiveGame('SWUSim', $gameName);
        $release();
        return ['success' => false, 'message' => 'The event changed while the match was starting.'];
    }
    SWURoomSaveLatest($lobbyID);
    return ['success' => true, 'matchId' => strval($matchId), 'gameName' => $gameName];
}

// The gameResultRecorded hook (Core/Match/MatchFlow.php): a game of the event's match was just recorded.
function SWULimitedOnGameResult(array $m, string $gameName): void {
    $roomId = strval($m['roomId'] ?? '');
    if (empty($m['limited']) || $roomId === '') return;
    SWURoomRehydrate($roomId);
    $lobby = LobbyMutate($roomId, function ($l) use ($m) {
        if (!SWUIsLimitedRoom($l)) return false;
        $next = LTApplyMatch($l->limited, $m);
        if ($next === $l->limited) return false;
        $l->limited = $next;
        return true;
    });
    if ($lobby !== null && SWUIsLimitedRoom($lobby)) SWURoomSaveLatest($roomId);
}

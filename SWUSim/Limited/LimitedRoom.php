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
require_once __DIR__ . '/LimitedDraftState.php';   // (it requires LimitedRoomState.php)
require_once __DIR__ . '/LimitedSwissState.php';   // a Draft pod's Swiss rounds (Plan 4)
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
        'canStart' => $isHost && LTCanStart($lt),
        'settings' => $lt['settings'], 'settingsErrors' => $lt['settingsErrors'], 'boosterSets' => array_keys(SWUBoosterSets()),
        'hostChoiceSets' => SWUHostChoiceSets(),   // + supplemental sets (IC27 Icons packs) — Host's Choice only
        'packRange' => SWULimitedPackRange($lt['mode']), 'seats' => $seats, 'hostPlayerID' => intval($lobby->hostPlayerID ?? 0),
        'buildEndsIn' => ($lt['phase'] === 'building' && $lt['buildDeadline'] !== null) ? max(0, $lt['buildDeadline'] - $now) : null,
        'me' => null, 'live' => null, 'result' => $lt['result'], 'kick' => [], 'draft' => null,
    ];
    // Draft: the public half only — seating, names, each seat's queue and pick counts, who has been auto-picked. Never a
    // pack's contents or anyone's picks: those are the pick screen's (LimitedDraftAction.php), and only the holder's.
    if ($lt['phase'] === 'drafting' && is_array($lt['draft'] ?? null)) {
        $d = $lt['draft']; $pod = [];
        foreach ($d['order'] as $p) {
            $pod[] = ['playerID' => $p, 'name' => strval($lt['seats'][$p]['name'] ?? ''), 'queued' => count($d['queues'][$p] ?? []),
                      'picks' => count($d['leaders'][$p] ?? []) + count($d['picks'][$p] ?? []),
                      'dropped' => !empty($lt['seats'][$p]['dropped']), 'autoPicked' => intval($d['autoPicks'][$p] ?? 0) > 0];
        }
        $view['draft'] = ['stage' => $d['stage'], 'round' => intval($d['round']), 'rounds' => intval($d['rounds']),
                          'direction' => LTDraftDirection($d), 'pod' => $pod];
    }
    if ($mePid === 0) return $view;
    $view['me'] = ['playerID' => $mePid, 'lineup' => array_values($lt['lineups'][$mePid] ?? []),
                   'submitted' => !empty($lt['seats'][$mePid]['submitted']), 'ready' => !empty($lt['seats'][$mePid]['ready'])];
    if ($lt['phase'] === 'playing' && is_array($lt['match'])) {
        $seat = array_search($mePid, $lt['match']['seats'], true);
        if ($seat !== false) $view['live'] = ['gameName' => strval($lt['match']['gameName']), 'seat' => intval($seat)];
    }
    if ($lt['mode'] === 'draft') {
        $view['me']['dropped'] = !empty($lt['seats'][$mePid]['dropped']);   // kicked or left: the page says so (Draft only)
        $mine = LTSwissMyPairing($lt, $mePid);
        // A finished table has no game to go back to while the rest of the round plays on.
        if ($mine !== null && empty($mine['bye']) && empty($mine['done'])) $view['live'] = ['gameName' => strval($mine['gameName']), 'seat' => intval(array_search($mePid, $mine['seats'], true))];
        $view['swiss'] = SWULimitedSwissView($lobby, $mePid, $now);
    }
    $view['kick'] = SWULimitedKickView($lt, $mePid, $seen, $now);
    return $view;
}

// A Draft room's rounds for the room page: this round's tables (public — who plays whom and the score), the standings once
// a round is complete, the break's clock, and whether the host may start the next round. Null before round 1 is claimed.
function SWULimitedSwissView(object $lobby, int $mePid, int $now): ?array {
    $lt = $lobby->limited;
    $sw = $lt['swiss'] ?? null;
    if (!is_array($sw)) return null;
    $name = fn($p) => $p === null ? '' : strval($lt['seats'][$p]['name'] ?? '');
    $n = intval($sw['round']);
    $tables = [];
    foreach (($sw['pairings'][$n] ?? []) as $x) {
        $tables[] = ['a' => $x['a'], 'b' => $x['b'], 'aName' => $name($x['a']), 'bName' => $name($x['b']), 'bye' => !empty($x['bye']),
                     'games' => [intval($x['games'][$x['a']] ?? 0), $x['b'] === null ? 0 : intval($x['games'][$x['b']] ?? 0)],
                     'done' => !empty($x['done']), 'me' => $x['a'] === $mePid || $x['b'] === $mePid];
    }
    $complete = false;   // at least one round fully played
    foreach ($sw['pairings'] as $r) { if ($r && array_filter($r, fn($x) => empty($x['done'])) === []) { $complete = true; break; } }
    $host = intval($lobby->hostPlayerID ?? 0);
    $mine = LTSwissMyPairing($lt, $mePid);
    return ['round' => $n, 'rounds' => intval($sw['rounds']), 'tables' => $tables,
            'standings' => $complete ? LTSwissStandings($lt) : null,
            'betweenEndsIn' => ($lt['phase'] === 'between' && $sw['betweenDeadline'] !== null) ? max(0, $sw['betweenDeadline'] - $now) : null,
            'canStartRound' => $mePid > 0 && $mePid === $host && $lt['phase'] === 'between' && LTSwissCanStartRound($lt, $now, $host, true),
            'myBye' => $mine !== null && !empty($mine['bye'])];
}

// The kick prompts $mePid is shown: every kickable seat but its own, with the true-majority tally. The room poll and the
// pick screen (LimitedDraftAction.php) both carry it — while drafting, everyone sits on the pick screen.
function SWULimitedKickView(array $lt, int $mePid, array $seen, int $now): array {
    $out = [];
    foreach (LTKickableSeats($lt, $seen, $now) as $target) {
        if ($target === $mePid) continue;
        $voters = LTKickVoters($lt, $target);
        $votes = array_keys($lt['kick'][$target]['votes'] ?? []);
        $out[] = ['target' => $target, 'name' => strval($lt['seats'][$target]['name']),
                  'votes' => count(array_intersect($votes, $voters)), 'needed' => LTKickVotesNeeded(count($voters)),
                  'iVoted' => in_array($mePid, $votes, true), 'canVote' => in_array($mePid, $voters, true)];
    }
    return $out;
}

// One step of the room's own clock, pure (the lobby poll runs it in its lock): overdue draft picks, the build deadline, and
// a Draft pod whose every live seat is Ready becoming 'built'.
function SWULimitedClockStep(array $lt, array $lastSeen, int $now): array {
    if ($lt['phase'] === 'drafting') $lt = LTDraftTick($lt, $lastSeen, $now);
    $lt = LTExpireBuild($lt, $now);
    $lt = LTSwissExpireBetween($lt, $now);   // the 15-minute break readies everyone with their last deck
    return LTMarkBuilt($lt) ?? $lt;
}

// The room's clock: the build deadline readies submitted seats, and once every seat is Ready the match is claimed and
// created. Creating a match needs the engine, so the START half runs only where SWUSim/MatchFlow.php is loaded (the
// Limited endpoints); the lobby poll applies the deadline in its own lock (it has no engine) and the room page asks
// LimitedAction.php to tick when the start is due.
function SWULimitedTick(string $lobbyID, int $now): void {
    $snap = apcu_fetch($lobbyID);
    if (!SWUIsLimitedRoom($snap)) return;
    $canStart = function_exists('SWUCreateMatchFromLobby');   // the engine is loaded (a Limited endpoint)
    $live = SWULimitedLiveMatches($snap);                     // read BEFORE the lock (I/O)
    $claimed = false; $changed = false; $round = false;
    $lobby = LobbyMutate($lobbyID, function ($l) use ($now, $canStart, $live, &$claimed, &$changed, &$round) {
        if (!SWUIsLimitedRoom($l)) return false;
        $before = $l->limited;
        $lt = SWULimitedClockStep($l->limited, SWULimitedLastSeen($l), $now);
        $lt = SWULimitedApplyMatches($lt, $live, $now);   // a result the gameResultRecorded hook missed
        if ($canStart && $lt['mode'] === 'draft') {
            // A Draft pod's next Swiss round: round 1 once built, a later one at the break's deadline (or once the host is gone).
            if (($c = LTSwissClaimRound($lt, $now, intval($l->hostPlayerID ?? 0), false)) !== null) { $lt = $c; $claimed = true; $round = true; }
        } elseif ($canStart && ($c = LTClaimStart($lt, $now)) !== null) { $lt = $c; $claimed = true; }
        $l->limited = $lt;
        $changed = $lt !== $before;
        return $changed;
    });
    // The builder ticks every 2 s: only a real change goes to disk (LobbyMutate returns the lobby even when nothing changed).
    if ($lobby === null || !$changed) return;
    SWURoomSaveLatest($lobbyID);
    if ($claimed) $round ? SWULimitedStartRound($lobbyID) : SWULimitedStartMatch($lobbyID);
}

// The host's "Start round" (or a fixture): claim the next round with the host's ask, then create its matches. Needs the
// engine. Refused unless every live seat is Ready (LTSwissCanStartRound).
function SWULimitedTryStartRound(string $lobbyID, int $now, bool $hostAsked): array {
    $snap = apcu_fetch($lobbyID);
    if (!SWUIsLimitedRoom($snap)) return ['success' => false, 'message' => 'Room not found.'];
    $live = SWULimitedLiveMatches($snap);
    $claimed = false;
    LobbyMutate($lobbyID, function ($l) use ($now, $hostAsked, $live, &$claimed) {
        if (!SWUIsLimitedRoom($l)) return false;
        $lt = SWULimitedApplyMatches(SWULimitedClockStep($l->limited, SWULimitedLastSeen($l), $now), $live, $now);
        $c = LTSwissClaimRound($lt, $now, intval($l->hostPlayerID ?? 0), $hostAsked);
        if ($c === null) { if ($lt !== $l->limited) { $l->limited = $lt; return true; } return false; }
        $l->limited = $c;
        $claimed = true;
        return true;
    });
    if (!$claimed) return ['success' => false, 'message' => 'Everyone must be Ready first.'];
    SWURoomSaveLatest($lobbyID);
    return SWULimitedStartRound($lobbyID);
}

// The room's running matches ([matchId => Match.json]), read BEFORE any lobby lock: Sealed's one match, or the unfinished
// tables of a Draft pod's current round. Empty outside a round.
function SWULimitedLiveMatches(object $lobby): array {
    if (!SWUIsLimitedRoom($lobby) || $lobby->limited['phase'] !== 'playing') return [];
    $lt = $lobby->limited;
    $ids = [];
    if ($lt['mode'] === 'draft') {
        foreach (($lt['swiss']['pairings'][intval($lt['swiss']['round'] ?? 0)] ?? []) as $x) if (empty($x['bye']) && empty($x['done'])) $ids[] = strval($x['matchId']);
    } elseif (is_array($lt['match'] ?? null)) {
        $ids[] = strval($lt['match']['matchId']);
    }
    $out = [];
    foreach ($ids as $id) { $m = MatchRead('SWUSim', $id); if (is_array($m)) $out[$id] = $m; }
    return $out;
}

// Fold the room's matches into the blob (pure): Sealed follows its one match, a Draft pod each table of the round.
function SWULimitedApplyMatches(array $lt, array $live, int $now): array {
    foreach ($live as $id => $m) $lt = $lt['mode'] === 'draft' ? LTSwissApplyMatch($lt, strval($id), $m, $now) : LTApplyMatch($lt, $m);
    return $lt;
}

// ── The match ─────────────────────────────────────────────────────────────────────────────────────────────────────────

// One child match for two seats of the room: an ordinary private two-seat match, High Ground's way — each seat's Player is
// CLONED onto match seat 1/2 (the room keeps its playerIDs; the seat keeps its room key, which is therefore also its key in
// the game), and its deckLink is set here, from the seat's submitted deck in the blob, as the standard deck JSON
// (SWULimitedDeckJson — LimitedDeck.php, loaded by the endpoint). The match carries both seats' pools, so game 2 and 3
// stay bound to them (LimitedBuilderAction.php), plus the 5-minute between-games cap and the "keep your last deck" timeout
// (Core/Match: sideboardSeconds / sideboardFallback). Returns [matchId, gameName, seats: [1 => $pidA, 2 => $pidB]], or
// null with $why set. Needs the engine (SWUSim/MatchFlow.php) — only the Limited endpoints call this.
function _SWULimitedCreateChildMatch(object $snap, array $lt, int $pidA, int $pidB, ?string &$why = null): ?array {
    $lobbyID = strval($snap->id);
    $stand = new stdClass();
    $stand->id = $lobbyID;                 // the match chat is the room's (l:<lobbyID>)
    $stand->rootName = 'SWUSim';
    $stand->format = 'limited';
    $stand->queueType = intval($lt['settings']['bestOf'] ?? 1) === 3 ? 'bo3' : 'bo1';
    $stand->isPrivate = true;
    $stand->players = [];
    $seats = []; $pools = [];
    foreach ([$pidA, $pidB] as $i => $pid) {
        $p = null;
        foreach ($snap->players as $q) if (($q instanceof Player) && intval($q->getPlayerID()) === $pid) $p = $q;
        $deck = $lt['seats'][$pid]['deck'] ?? null;
        if ($p === null || !is_array($deck)) { $why = 'A seat is missing its deck.'; return null; }
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
    if (!$matchId || $gameName === '') { $why = 'The match could not be created.'; return null; }
    MatchWithLock('SWUSim', $matchId, function (&$m) use ($lobbyID, $pools, $seats, $lt) {
        $m['roomId'] = $lobbyID;
        $m['limited'] = ['pools' => $pools, 'setsInPlay' => $lt['setsInPlay'], 'seats' => $seats];
        $m['sideboardSeconds'] = LT_SIDEBOARD_SECONDS;
        $m['sideboardFallback'] = 'current';
    });
    if (function_exists('RegisterActiveGame')) RegisterActiveGame('SWUSim', $gameName, true);
    return ['matchId' => strval($matchId), 'gameName' => $gameName, 'seats' => $seats];
}

// A match the room made but could not keep (the event changed under the start): nobody will play it.
function _SWULimitedDiscardMatch(array $made): void {
    MatchAbortNoResult('SWUSim', $made['matchId']);
    if (function_exists('RemoveActiveGame')) RemoveActiveGame('SWUSim', $made['gameName']);
}

// Sealed: create the event's one match once the tick has claimed the start.
function SWULimitedStartMatch(string $lobbyID): array {
    $snap = apcu_fetch($lobbyID);
    if (!SWUIsLimitedRoom($snap) || empty($snap->limited['starting'])) return ['success' => false, 'message' => 'Nothing to start.'];
    $lt = $snap->limited;
    $release = function () use ($lobbyID) {
        LobbyMutate($lobbyID, function ($l) { if (SWUIsLimitedRoom($l)) $l->limited = LTReleaseStart($l->limited); return true; });
        SWURoomSaveLatest($lobbyID);
    };
    $live = LTLiveSeats($lt);
    $why = null;
    $made = count($live) === 2 ? _SWULimitedCreateChildMatch($snap, $lt, $live[0], $live[1], $why) : null;
    if ($made === null) { $release(); return ['success' => false, 'message' => $why ?? 'A seat is missing its deck.']; }
    $committed = false;
    LobbyMutate($lobbyID, function ($l) use ($made, &$committed) {
        if (!SWUIsLimitedRoom($l)) return false;
        $next = LTBeginMatch($l->limited, $made['matchId'], $made['gameName'], $made['seats']);
        if ($next === null) return false;
        $l->limited = $next;
        $committed = true;
        return true;
    });
    if (!$committed) {
        // The event ended under the start (a kick or a leave): the match we built has nobody to play it.
        _SWULimitedDiscardMatch($made);
        $release();
        return ['success' => false, 'message' => 'The event changed while the match was starting.'];
    }
    SWURoomSaveLatest($lobbyID);
    return ['success' => true, 'matchId' => $made['matchId'], 'gameName' => $made['gameName']];
}

// Draft: create a match for every table of the claimed round (up to 4 at once), then commit the round. If any match cannot
// be made, or the round can no longer be committed, every match made here is discarded and the claim released — the next
// tick (or the host) tries again.
function SWULimitedStartRound(string $lobbyID): array {
    $snap = apcu_fetch($lobbyID);
    if (!SWUIsLimitedRoom($snap) || empty($snap->limited['starting']) || !is_array($snap->limited['swiss']['pending'] ?? null)) {
        return ['success' => false, 'message' => 'Nothing to start.'];
    }
    $lt = $snap->limited;
    $made = []; $why = null;
    foreach ($lt['swiss']['pending'] as $i => $x) {
        if ($x['b'] === null) continue;
        $m = _SWULimitedCreateChildMatch($snap, $lt, intval($x['a']), intval($x['b']), $why);
        if ($m === null) break;
        $made[$i] = $m;
    }
    $tables = count(array_filter($lt['swiss']['pending'], fn($x) => $x['b'] !== null));
    $committed = false;
    if (count($made) === $tables) {
        LobbyMutate($lobbyID, function ($l) use ($made, &$committed) {
            if (!SWUIsLimitedRoom($l)) return false;
            $next = LTSwissCommitRound($l->limited, $made, time());
            if ($next === null) return false;
            $l->limited = $next;
            $committed = true;
            return true;
        });
    }
    if (!$committed) {
        foreach ($made as $m) _SWULimitedDiscardMatch($m);
        LobbyMutate($lobbyID, function ($l) { if (SWUIsLimitedRoom($l)) $l->limited = LTSwissReleaseClaim($l->limited); return true; });
        SWURoomSaveLatest($lobbyID);
        return ['success' => false, 'message' => $why ?? 'The event changed while the round was starting.'];
    }
    SWURoomSaveLatest($lobbyID);
    return ['success' => true, 'matches' => array_values($made)];
}

// The gameResultRecorded hook (Core/Match/MatchFlow.php): a game of one of the event's matches was just recorded.
function SWULimitedOnGameResult(array $m, string $gameName): void {
    $roomId = strval($m['roomId'] ?? '');
    if (empty($m['limited']) || $roomId === '') return;
    SWURoomRehydrate($roomId);
    $lobby = LobbyMutate($roomId, function ($l) use ($m) {
        if (!SWUIsLimitedRoom($l)) return false;
        $next = SWULimitedApplyMatches($l->limited, [strval($m['matchId'] ?? '') => $m], time());
        if ($next === $l->limited) return false;
        $l->limited = $next;
        return true;
    });
    if ($lobby !== null && SWUIsLimitedRoom($lobby)) SWURoomSaveLatest($roomId);
}

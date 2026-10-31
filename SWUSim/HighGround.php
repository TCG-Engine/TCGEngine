<?php
// High Ground rooms — the glue between the pure state (HighGroundState.php) and the lobby, its disk snapshot and the
// match layer. Spec: docs/superpowers/specs/2026-10-30-swusim-high-ground-design.md
//
// A High Ground room is a private SWUSim lobby carrying $lobby->highGround. It is a persistent room (snapshot on disk) but
// never has a roomMatchId, so the 1v1 room's match sync (SWURoomSyncWithMatch) leaves it alone, and it never gets a
// gameName: the room stays open while a hill match runs, so people can keep joining, chatting and changing decks.
require_once __DIR__ . '/HighGroundState.php';
require_once __DIR__ . '/Rooms.php';
// ⚠ NOT SWUSim/MatchFlow.php: every SWUSim room poll loads this file, and that one drags in the whole engine (~1,900 files).
// Matches are read through Core (MatchRead, via Rooms.php); only Start needs SWUCreateMatchFromLobby, and its caller
// (HighGroundAction.php) loads SWUSim/MatchFlow.php at file scope.
require_once __DIR__ . '/../AppCore/SWU/Formats.php';

function SWUIsHighGround($lobby): bool {
    return is_object($lobby) && !empty($lobby->highGround) && is_array($lobby->highGround);
}

// Any 1v1 format that isn't rated: never Meta Premier, never Twin Suns.
function SWUHighGroundFormatRefusal(string $format): ?string {
    if (SWUFormatIsRated($format) || SWUFormatIsRoomFormat($format)) return "High Ground needs a 1v1 format that isn't rated.";
    return null;
}

function _SWUHighGroundPlayerUserId($p): int {
    return method_exists($p, 'getUserId') ? intval($p->getUserId() ?? 0) : 0;
}

// ⚠ Ready means "here and ready to play NOW" in High Ground, so nobody arrives Ready — a private room otherwise readies a
// seat that joins with a legal deck (_SWURoomApplyResolvedDeck), which would let Start call up someone who walked away.
function SWUHighGroundCreate(object $lobby, Player $creator): void {
    $creator->setReady(false);
    $lobby->maxPlayers = HG_MAX_MEMBERS;
    $lobby->persistentRoom = true;
    $lobby->highGround = HGRotateWatchKey(HGAddMember(HGNew(), strval($creator->getAuthKey()),
        strval($creator->getUsername()), _SWUHighGroundPlayerUserId($creator)));
}

function SWUHighGroundOnJoin(object $lobby, Player $p): void {
    $p->setReady(false);
    $lobby->highGround = HGAddMember($lobby->highGround, strval($p->getAuthKey()), strval($p->getUsername()),
        _SWUHighGroundPlayerUserId($p));
}

// Someone left or was kicked. The watch key rotates so they can't watch the NEXT match (the live one keeps its key).
function SWUHighGroundOnLeave(object $lobby, string $key): void {
    $lobby->highGround = HGRotateWatchKey(HGRemoveMember($lobby->highGround, $key));
}

function SWUHighGroundKeyForPlayerID(object $lobby, int $playerID): ?string {
    foreach (($lobby->players ?? []) as $p) {
        if (($p instanceof Player) && intval($p->getPlayerID()) === $playerID) return strval($p->getAuthKey());
    }
    return null;
}

function SWUHighGroundPlayerIDForKey(object $lobby, string $key): int {
    foreach (($lobby->players ?? []) as $p) {
        if (($p instanceof Player) && hash_equals(strval($p->getAuthKey()), $key)) return intval($p->getPlayerID());
    }
    return 0;
}

function SWUHighGroundPlayerForKey(object $lobby, string $key): ?Player {
    foreach (($lobby->players ?? []) as $p) {
        if (($p instanceof Player) && hash_equals(strval($p->getAuthKey()), $key)) return $p;
    }
    return null;
}

function SWUHighGroundIsHost(object $lobby, string $key): bool {
    return SWUHighGroundPlayerIDForKey($lobby, $key) === intval($lobby->hostPlayerID ?? 0) && $key !== '';
}

// ── Hill matches ─────────────────────────────────────────────────────────────────────────────────────────────────────

const HG_START_CLAIM_SECONDS = 60;   // a Start that crashed mid-way frees the claim after this
const HG_GAME_FRESH_SECONDS  = 300;  // a hill game with an action this recent counts its two players as present

// Start next match (host only): an ordinary private two-seat match built from the two on the hill. The members' own
// Player objects are CLONED onto seats 1/2 — the room keeps its playerIDs; the seats keep each member's room key, which
// is therefore also their seat key in the game. Returns ['success'=>true,'matchId','gameName'] or ['success'=>false,
// 'message','status'].
function SWUHighGroundStartMatch(string $lobbyID, string $hostAuthKey): array {
    SWURoomRehydrate($lobbyID);
    $err = null; $status = 409; $pair = []; $snapshot = null;
    $claimed = LobbyMutate($lobbyID, function ($l) use ($hostAuthKey, &$err, &$status, &$pair, &$snapshot) {
        if (!SWUIsHighGround($l)) { $err = 'Room not found.'; $status = 404; return false; }
        if (!SWUHighGroundIsHost($l, $hostAuthKey)) { $err = 'Only the host can start the next match.'; $status = 403; return false; }
        $hg = $l->highGround;
        if ($hg['live'] !== null) { $err = 'A match is already on.'; return false; }
        if (time() - intval($hg['starting'] ?? 0) < HG_START_CLAIM_SECONDS) { $err = 'Starting…'; return false; }
        $keys = HGNextPair($hg);
        if (count($keys) < 2) { $err = 'Two people need to be in line.'; return false; }
        $notReady = [];
        foreach ($keys as $k) {
            $p = SWUHighGroundPlayerForKey($l, $k);
            if ($p === null || !$p->getDeckOk() || !$p->getReady()) $notReady[] = $hg['names'][$k] ?? 'Someone';
        }
        if (!empty($notReady)) { $err = 'Waiting for ' . implode(' and ', $notReady) . ' to be Ready.'; return false; }
        $l->highGround['starting'] = time();
        $pair = $keys;
        $snapshot = clone $l;
        return true;
    });
    if ($err !== null || $claimed === null) return ['success' => false, 'message' => $err ?? 'The room is busy — try again.', 'status' => $err !== null ? $status : 409];

    $stand = new stdClass();
    $stand->id = $lobbyID;                 // the match chat is the room's (l:<lobbyID>)
    $stand->rootName = 'SWUSim';
    $stand->format = strval($snapshot->format ?? 'premier');
    $stand->queueType = strval($snapshot->queueType ?? 'bo1');
    $stand->isPrivate = true;
    $stand->spectatorKey = strval($snapshot->highGround['watchKey'] ?? '');
    $stand->players = [];
    foreach ($pair as $i => $k) {
        $c = clone SWUHighGroundPlayerForKey($snapshot, $k);
        $c->setPlayerID($i + 1);
        $stand->players[] = $c;
    }
    $matchId = null;
    if (!function_exists('SWUCreateMatchFromLobby')) {
        LobbyMutate($lobbyID, function ($l) { unset($l->highGround['starting']); return true; });
        return ['success' => false, 'message' => 'The match framework is not loaded.', 'status' => 500];
    }
    try { $matchId = SWUCreateMatchFromLobby($stand); } catch (Throwable $e) { $matchId = null; }
    $gameName = strval($stand->gameName ?? '');
    if (!$matchId || $gameName === '') {
        LobbyMutate($lobbyID, function ($l) { unset($l->highGround['starting']); return true; });
        return ['success' => false, 'message' => 'The match could not be created — check both decks.', 'status' => 500];
    }
    MatchWithLock('SWUSim', $matchId, function (&$m) use ($lobbyID, $stand) {
        $m['roomId'] = $lobbyID;
        $m['highGround'] = true;
        $m['spectatorKey'] = $stand->spectatorKey;
    });
    if (function_exists('RegisterActiveGame')) RegisterActiveGame('SWUSim', $gameName, true);
    $committed = false;
    $lobby = LobbyMutate($lobbyID, function ($l) use ($matchId, $pair, &$committed) {
        unset($l->highGround['starting']);
        $next = HGBeginMatch($l->highGround, strval($matchId), $pair);   // refused if the pair moved while we built it
        if ($next !== null) {
            $l->highGround = $next;
            $committed = true;
            foreach ($pair as $k) { $p = SWUHighGroundPlayerForKey($l, $k); if ($p !== null) $p->setReady(false); }
        }
        return true;
    });
    if (!$committed) {
        // The line changed under Start (a step down, the bleachers, a leave, a reorder): the match we built is for a pair
        // that is no longer next. End it with no result rather than seat one pair and record another.
        MatchAbortNoResult('SWUSim', strval($matchId));
        if (function_exists('RemoveActiveGame')) RemoveActiveGame('SWUSim', $gameName);
        if ($lobby !== null) SWURoomSave($lobby);
        return ['success' => false, 'message' => 'The line changed while the match was starting — press Start again.', 'status' => 409];
    }
    if ($lobby !== null) SWURoomSave($lobby);
    return ['success' => true, 'matchId' => strval($matchId), 'gameName' => $gameName];
}

// A card's display name for the table (title only — "Leia Organa", not the CardID). The engine request that records a game
// has the card dictionary loaded; anywhere it isn't, the id stands in rather than loading the dictionary on a poll.
function _SWUHighGroundCardName(string $cid): string {
    if ($cid === '') return '';
    $t = function_exists('CardTitle') ? strval(CardTitle($cid)) : '';
    return $t !== '' ? $t : $cid;
}

// A member's leader(s) or base by name, from the identity cached when their deck was checked (the same strip the plain
// room shows) — no dictionary lookup on the poll.
function _SWUHighGroundIdentityNames(Player $p, string $kind): array {
    $out = [];
    foreach ($p->getIdentityCards() as $c) {
        if (($c['kind'] ?? '') !== $kind) continue;
        $name = strval($c['name'] ?? $c['id'] ?? '');
        $out[] = strpos($name, ', ') !== false ? substr($name, 0, strpos($name, ', ')) : $name;   // the title, without the subtitle
    }
    return $out;
}

// Who sat where in a match, in HGRecordGame's shape.
function _SWUHighGroundSeats(array $m): array {
    $seats = [];
    foreach ([1, 2] as $s) {
        $p = $m['players'][strval($s)] ?? null;
        if (!is_array($p)) continue;
        $deck = is_array($p['originalDeck'] ?? null) ? $p['originalDeck'] : [];
        $leader = $deck['leader'] ?? '';
        $seats[$s] = ['key' => strval($p['authKey'] ?? ''), 'userId' => intval($p['userId'] ?? 0), 'deckLink' => strval($p['deckLink'] ?? ''),
                      'leader' => implode(' / ', array_map('_SWUHighGroundCardName', array_map('strval', (array)$leader))),
                      'base' => _SWUHighGroundCardName(strval($deck['base'] ?? '')),
                      'deckName' => strval($deck['name'] ?? '')];
    }
    return $seats;
}

// Fold a hill match into the room (in-lock, no I/O): every decided or drawn game into Tonight, and — once the match is
// over — the hill and the line. Idempotent: games by name (HGRecordGame), the match by live.matchId.
function SWUHighGroundApplyMatch(object $lobby, array $m): bool {
    if (!SWUIsHighGround($lobby)) return false;
    $before = $lobby->highGround;
    $hg = $lobby->highGround;
    $seats = _SWUHighGroundSeats($m);
    foreach (($m['games'] ?? []) as $g) {
        $name = strval($g['gameName'] ?? '');
        if ($name === '') continue;
        if (!empty($g['draw'])) $hg = HGRecordGame($hg, $name, $seats, 0);
        elseif (($g['winner'] ?? null) !== null && intval($g['winner']) > 0) $hg = HGRecordGame($hg, $name, $seats, intval($g['winner']));
    }
    if (is_array($hg['live']) && strval($hg['live']['matchId']) === strval($m['matchId'] ?? '')) {
        if (($m['state'] ?? '') === 'complete') $hg = HGEndMatch($hg, !empty($m['matchDraw']) ? 0 : intval($m['winner'] ?? 0));
        elseif (($m['state'] ?? '') === 'abandoned') $hg = HGAbortMatch($hg, strval($m['hgGone'] ?? ''));
    }
    $lobby->highGround = $hg;
    return $hg !== $before;
}

// The gameResultRecorded hook (Core/Match/MatchFlow.php): one game of a hill match was just recorded.
function SWUHighGroundOnGameResult(array $m, string $gameName): void {
    $roomId = strval($m['roomId'] ?? '');
    if (empty($m['highGround']) || $roomId === '') return;
    SWURoomRehydrate($roomId);
    $lobby = LobbyMutate($roomId, fn($l) => SWUHighGroundApplyMatch($l, $m));
    if ($lobby !== null && SWUIsHighGround($lobby)) SWURoomSave($lobby);
}

// Read BEFORE the room lock (it does I/O): the live hill match and whether its game saw an action recently.
function SWUHighGroundLiveState(object $lobby, int $now): ?array {
    if (!SWUIsHighGround($lobby) || !is_array($lobby->highGround['live'] ?? null)) return null;
    $m = MatchRead('SWUSim', strval($lobby->highGround['live']['matchId']));
    if (!is_array($m)) return null;
    $last = intval($m['updatedAt'] ?? $m['createdAt'] ?? 0);
    $game = MatchCurrentGameName($m);
    if ($game !== '' && function_exists('ReadActiveGameIndex')) {
        $e = ReadActiveGameIndex()['SWUSim:' . $game] ?? null;
        if (is_array($e)) $last = max($last, intval($e['lastUpdatedAt'] ?? $e['createdAt'] ?? 0));
    }
    return ['match' => $m, 'fresh' => ($now - $last) < HG_GAME_FRESH_SECONDS];
}

// The room poll's fallback (in-lock): apply anything the hook missed, and keep two players who are busy playing a fresh
// game counted as present — otherwise a host on the hill would lose host to SWUMigrateHostIfAway every match.
function SWUHighGroundSync(object $lobby, ?array $live, int $now): bool {
    if ($live === null) return false;
    $changed = SWUHighGroundApplyMatch($lobby, $live['match']);
    $state = strval($live['match']['state'] ?? '');
    if (!empty($live['fresh']) && in_array($state, ['in_progress', 'sideboarding'], true) && is_array($lobby->highGround['live'] ?? null)) {
        foreach ($lobby->highGround['live']['keys'] as $k) {
            $p = SWUHighGroundPlayerForKey($lobby, strval($k));
            if ($p !== null) { $p->touch($now); $changed = true; }
        }
    }
    return $changed;
}

// End match (host only): the live match ends with NO result because $gonePlayerID vanished. Everyone on its board is sent
// back to the room (RoomReturn.json, read by the generated GetNextTurn before anything else).
function SWUHighGroundEndMatch(string $lobbyID, string $hostAuthKey, int $gonePlayerID): array {
    SWURoomRehydrate($lobbyID);
    $snap = apcu_fetch($lobbyID);
    if (!SWUIsHighGround($snap)) return ['success' => false, 'message' => 'Room not found.', 'status' => 404];
    if (!SWUHighGroundIsHost($snap, $hostAuthKey)) return ['success' => false, 'message' => 'Only the host can end a match.', 'status' => 403];
    $live = $snap->highGround['live'];
    if (!is_array($live)) return ['success' => false, 'message' => 'No match is on.', 'status' => 409];
    $goneKey = SWUHighGroundKeyForPlayerID($snap, $gonePlayerID);
    if ($goneKey === null || !in_array($goneKey, $live['keys'], true)) return ['success' => false, 'message' => 'Pick one of the two players.', 'status' => 400];
    $matchId = strval($live['matchId']);
    $m = null;
    if (is_array(MatchRead('SWUSim', $matchId))) {   // MatchWithLock needs the match's directory
        MatchWithLock('SWUSim', $matchId, function (&$m) use ($goneKey) { $m['hgGone'] = $goneKey; });
        $m = MatchAbortNoResult('SWUSim', $matchId);
    }
    $game = is_array($m) ? MatchCurrentGameName($m) : '';
    if ($game !== '') {
        if (function_exists('RemoveActiveGame')) RemoveActiveGame('SWUSim', $game);
        $dir = MatchGamesDir('SWUSim') . '/' . $game;
        if (is_dir($dir)) file_put_contents($dir . '/RoomReturn.json', json_encode(['lobbyID' => $lobbyID]), LOCK_EX);
    }
    // No match record (reaped, lost): still free the hill — a room must never stay stuck on a match nobody can end.
    $lobby = LobbyMutate($lobbyID, function ($l) use ($m, $goneKey) {
        if (is_array($m)) return SWUHighGroundApplyMatch($l, $m);
        $l->highGround = HGAbortMatch($l->highGround, $goneKey);
        return true;
    });
    if ($lobby !== null) SWURoomSave($lobby);
    return ['success' => true];
}

// ── The room poll's High Ground block ─────────────────────────────────────────────────────────────────────────────────
// Everything the room screen draws, with people named by lobby playerID ONLY — no room key ever leaves the server except
// the viewer's own, which they already hold. $viewerKey is '' for someone not in the room (no watch key, no "me").
// $liveMatch: the live hill match as read before the lock (SWUHighGroundLiveState), or null.
function SWUHighGroundPollView(object $lobby, string $viewerKey, ?array $liveMatch): array {
    $hg = $lobby->highGround;
    $idOf = fn($k) => SWUHighGroundPlayerIDForKey($lobby, strval($k));
    $ids = fn(array $keys) => array_values(array_filter(array_map($idOf, $keys)));
    $where = function ($k) use ($hg) {
        if (in_array($k, $hg['hill'], true)) return 'hill';
        if (in_array($k, $hg['bleachers'], true)) return 'bleachers';
        return in_array($k, $hg['line'], true) ? 'line' : '';
    };
    $members = [];
    foreach (($lobby->players ?? []) as $p) {
        if (!($p instanceof Player)) continue;
        $k = strval($p->getAuthKey());
        if (!HGIsMember($hg, $k)) continue;
        $members[] = ['playerID' => intval($p->getPlayerID()), 'name' => $hg['names'][$k], 'isGuest' => intval($p->getUserId() ?? 0) <= 0,
                      'where' => $where($k), 'ready' => (bool)$p->getReady(), 'deckOk' => (bool)$p->getDeckOk(),
                      'leaders' => _SWUHighGroundIdentityNames($p, 'leader'), 'base' => implode('', _SWUHighGroundIdentityNames($p, 'base')),
                      'isHost' => intval($p->getPlayerID()) === intval($lobby->hostPlayerID ?? 0)];
    }
    $live = null;
    if (is_array($hg['live']) && is_array($liveMatch) && strval($liveMatch['matchId'] ?? '') === strval($hg['live']['matchId'])) {
        $live = ['gameName' => MatchCurrentGameName($liveMatch), 'gameNumber' => intval($liveMatch['currentGameNumber'] ?? 1),
                 'bestOf' => intval($liveMatch['bestOf'] ?? 1), 'state' => strval($liveMatch['state'] ?? ''),
                 'score' => ['1' => intval($liveMatch['wins']['1'] ?? 0), '2' => intval($liveMatch['wins']['2'] ?? 0)],
                 'players' => $ids([$hg['live']['keys'][1], $hg['live']['keys'][2]])];
    }
    $view = ['members' => $members, 'line' => $ids($hg['line']), 'bleachers' => $ids($hg['bleachers']), 'hill' => $ids($hg['hill']),
             'nextPair' => $hg['live'] === null ? $ids(HGNextPair($hg)) : [], 'live' => $live, 'drawn' => !empty($hg['drawn']),
             'bestOf' => strval($lobby->queueType ?? 'bo1') === 'bo3' ? 3 : 1, 'tonight' => HGTonightRows($hg), 'max' => HG_MAX_MEMBERS];
    if ($viewerKey !== '' && HGIsMember($hg, $viewerKey)) {
        $seat = 0;
        if (is_array($hg['live'])) foreach ($hg['live']['keys'] as $s => $k) if ($k === $viewerKey) $seat = intval($s);
        $view['me'] = ['playerID' => $idOf($viewerKey), 'where' => $where($viewerKey), 'seat' => $seat, 'onLiveMatch' => $seat > 0,
                       'canStepDown' => !empty($hg['drawn']) && $hg['live'] === null && in_array($viewerKey, $hg['hill'], true)];
        // Watch live opens the LIVE match, whose games all take the key it was started with — the room's key may have rotated
        // since (a leave or kick mid-match rotates it for the NEXT match), so hand out the live match's own key while one is on.
        $view['watchKey'] = (is_array($liveMatch) && $live !== null && strval($liveMatch['spectatorKey'] ?? '') !== '')
            ? strval($liveMatch['spectatorKey']) : strval($hg['watchKey']);
    }
    return $view;
}

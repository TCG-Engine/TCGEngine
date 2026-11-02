<?php
// SWUSim/Rooms.php — the persistent 1v1 waiting room ("Return to Lobby").
// Spec: docs/superpowers/specs/2026-10-30-persistent-lobby-rematch-design.md · tests: SWUSim/DevTools/tests/room_lifecycle_test.php
//
// The room IS the APCu lobby, exactly as before. What this adds:
//   • a disk SNAPSHOT of that lobby (Matches/rooms/<lobbyID>.ser), so the room survives APCu being cleared or evicted —
//     APCu filling up has wiped every lobby at once before (memory swusim-seat-auth-single-cookie);
//   • the link between the room and its match: $m['roomId'] on the match, $lobby->roomMatchId on the lobby;
//   • the SYNC: while a match from the room is running the lobby stays 'started'; once it completes the room reopens,
//     every seat not Ready and "on the end-game screen" until that player comes back (or leaves);
//   • per-seat state $lobby->roomSeatState[authKey] = 'endscreen' | 'lobby' (absent = 'lobby'). Keyed by the auth key,
//     not the playerID, because StartRoom renumbers playerIDs and the key is the seat's stable identity.
//
// ⚠ Functions marked IN-LOCK run inside LobbyMutate's callback and must do no I/O (LobbyStore.php's rule). Read the
// match first and pass it in.
require_once __DIR__ . '/../Core/Match/MatchFlow.php';
require_once __DIR__ . '/../APIs/Lobbies/Classes/Player.php';
require_once __DIR__ . '/../APIs/Lobbies/Classes/LobbyStore.php';
require_once __DIR__ . '/../APIs/Lobbies/Classes/TeamRooms.php';

// The end-screen quiet rule, shared with the Twin Suns table vote (TableRematch.php defines it too).
if (!defined('TABLE_REMATCH_QUIET_AFTER')) define('TABLE_REMATCH_QUIET_AFTER', 120);

// ── Snapshot on disk ────────────────────────────────────────────────────────────────────────────────────────────────
function _SWURoomsDir(): string {
    $dir = MatchesDir('SWUSim') . '/rooms';
    if (!is_dir($dir) && !@mkdir($dir, 0777, true) && !is_dir($dir)) return '';
    return $dir;
}
function SWURoomPath(string $roomId): string {
    $roomId = preg_replace('/[^A-Za-z0-9_-]/', '', $roomId);
    $dir = _SWURoomsDir();
    return ($roomId === '' || $dir === '') ? '' : $dir . '/' . $roomId . '.ser';
}
function SWURoomInvitePath(string $code): string {
    $code = preg_replace('/[^A-Za-z0-9]/', '', $code);
    $dir = _SWURoomsDir();
    return ($code === '' || $dir === '') ? '' : $dir . '/invite-' . $code . '.json';
}
// Written to a temp file and RENAMED into place, so a concurrent SWURoomLoad reads the whole old snapshot or the whole
// new one — never a half-written file (which loads as null, and the room would read as ended).
function SWURoomSave(object $lobby): bool {
    $path = SWURoomPath(strval($lobby->id ?? ''));
    if ($path === '') return false;
    $tmp = $path . '.tmp' . getmypid() . bin2hex(random_bytes(3));
    $ok = file_put_contents($tmp, serialize(['lobby' => $lobby, 'matchId' => strval($lobby->roomMatchId ?? ''), 'savedAt' => time()])) !== false
          && @rename($tmp, $path);
    if (!$ok) @unlink($tmp);
    $code = strval($lobby->inviteCode ?? '');
    $ipath = $code !== '' ? SWURoomInvitePath($code) : '';
    if ($ok && $ipath !== '') file_put_contents($ipath, json_encode(['roomId' => strval($lobby->id)]), LOCK_EX);
    if ($ok) SWURoomReapIfDue(time());
    return $ok;
}
// ['lobby' => object, 'matchId' => string, 'savedAt' => int], or null when missing or unreadable.
function SWURoomLoad(string $roomId): ?array {
    $path = SWURoomPath($roomId);
    if ($path === '' || !is_file($path)) return null;
    $rec = @unserialize(strval(@file_get_contents($path)), ['allowed_classes' => [Player::class, 'stdClass']]);
    if (!is_array($rec) || !is_object($rec['lobby'] ?? null)) return null;
    return $rec;
}
function SWURoomIdForInvite(string $code): string {
    $path = SWURoomInvitePath($code);
    if ($path === '' || !is_file($path)) return '';
    $d = json_decode(strval(@file_get_contents($path)), true);
    return is_array($d) ? preg_replace('/[^A-Za-z0-9_-]/', '', strval($d['roomId'] ?? '')) : '';
}
// Liveness is the file's mtime: the room poll and the end-screen heartbeat touch it, throttled to once a minute.
function SWURoomTouch(string $roomId, int $now): void {
    $path = SWURoomPath($roomId);
    if ($path === '' || !is_file($path)) return;
    clearstatcache(true, $path);
    if ($now - intval(@filemtime($path)) >= 60) @touch($path, $now);
}
// A room is alive while a match from it is running, or until LOBBY_TTL_SECONDS after anyone last touched it (call 13).
// "Touched" counts the match finishing: nothing touches the room while a match is played (nobody is on the room page),
// so after an hour-long match the snapshot is an hour old — the match record's updatedAt is the room's real last use.
function SWURoomIsAlive(?array $match, int $fileMtime, int $now): bool {
    if (is_array($match) && strval($match['state'] ?? '') !== 'complete') return true;
    $last = max($fileMtime, is_array($match) ? intval($match['updatedAt'] ?? 0) : 0);
    return ($now - $last) < LOBBY_TTL_SECONDS;
}
// Is this lobby a persistent room (one that is saved to disk)? A room that started a match, or one an accepted
// Rematch built from a public match.
function SWURoomIsPersistent(object $lobby): bool {
    return !empty($lobby->persistentRoom) || strval($lobby->roomMatchId ?? '') !== '';
}
// What, if it changed, the disk copy must catch up with (seats, decks, ready, host, length, state).
function SWURoomMaterialSig(object $lobby): string {
    $seats = [];
    foreach (($lobby->players ?? []) as $p) {
        if (!($p instanceof Player)) continue;
        $seats[] = [$p->getAuthKey(), $p->getPlayerID(), $p->getDeckLink(), $p->getReady(), $p->getUsername(), $p->getUserId()];
    }
    return md5(serialize([$seats, $lobby->hostPlayerID ?? null, $lobby->queueType ?? null, $lobby->state ?? null,
                          $lobby->gameName ?? null, $lobby->roomSeatState ?? null, $lobby->roomMatchId ?? null,
                          $lobby->highGround ?? null]));   // a High Ground room's line, bleachers and Tonight table
}
// Save the room if anything material changed since it was last saved (the room poll calls this every time).
function SWURoomSaveIfChanged(object $lobby): void {
    $sig = SWURoomMaterialSig($lobby);
    $key = 'swu_room_sig_' . strval($lobby->id ?? '');
    if (function_exists('apcu_fetch') && apcu_fetch($key) === $sig) return;
    if (SWURoomSave($lobby) && function_exists('apcu_store')) apcu_store($key, $sig, LOBBY_TTL_SECONDS);
}
// Clear out rooms that ended long ago: a day after a room ended its snapshot and invite index are removed (the day keeps
// "This room has ended." for a late invite click). A room whose match is still running is kept — unless nothing has
// moved in 30 days (an abandoned match). Also removes orphaned invite indexes and temp files a failed save left behind.
// Returns how many files it removed.
function SWURoomReap(int $now, int $grace = 86400): int {
    $dir = _SWURoomsDir();
    if ($dir === '') return 0;
    $removed = 0;
    $rm = function (string $f) use (&$removed) { if ($f !== '' && is_file($f) && @unlink($f)) $removed++; };
    foreach (glob($dir . '/*.ser') ?: [] as $path) {
        $mtime = intval(@filemtime($path));
        $rec = SWURoomLoad(basename($path, '.ser'));
        if ($rec === null) { if ($now - $mtime >= $grace) $rm($path); continue; }
        $match = $rec['matchId'] !== '' ? MatchRead('SWUSim', $rec['matchId']) : null;
        $match = is_array($match) ? $match : null;
        $lastUse = max($mtime, intval($match['updatedAt'] ?? 0));
        $abandoned = ($now - $lastUse) >= 30 * 86400;
        if (!$abandoned && SWURoomIsAlive($match, $mtime, $now - $grace)) continue;
        $code = strval($rec['lobby']->inviteCode ?? '');
        $rm($path);
        if ($code !== '' && SWURoomIdForInvite($code) === basename($path, '.ser')) $rm(SWURoomInvitePath($code));
    }
    foreach (glob($dir . '/invite-*.json') ?: [] as $ipath) {
        $d = json_decode(strval(@file_get_contents($ipath)), true);
        $roomPath = SWURoomPath(is_array($d) ? strval($d['roomId'] ?? '') : '');
        if (($roomPath === '' || !is_file($roomPath)) && $now - intval(@filemtime($ipath)) >= $grace) $rm($ipath);
    }
    foreach (glob($dir . '/*.tmp*') ?: [] as $tmp) if ($now - intval(@filemtime($tmp)) >= 3600) $rm($tmp);
    return $removed;
}
// SWURoomSave runs this: one request an hour pays for the scan.
function SWURoomReapIfDue(int $now): void {
    if (function_exists('apcu_add') && apcu_add('swu_rooms_reap', 1, 3600)) SWURoomReap($now);
}
// Everyone left (LeaveQueue deleted the lobby): the room is over, so its disk copy must not bring it back.
function SWURoomForget(string $roomId): void {
    $rec = SWURoomLoad($roomId);
    $code = $rec !== null ? strval($rec['lobby']->inviteCode ?? '') : '';
    $path = SWURoomPath($roomId);
    if ($path !== '' && is_file($path)) @unlink($path);
    $ipath = $code !== '' ? SWURoomInvitePath($code) : '';
    if ($ipath !== '' && is_file($ipath)) @unlink($ipath);
}

// ── Seat state + sync (IN-LOCK, no I/O) ─────────────────────────────────────────────────────────────────────────────
function SWURoomSeatState(object $lobby, string $authKey): string {
    $s = is_array($lobby->roomSeatState ?? null) ? ($lobby->roomSeatState[$authKey] ?? '') : '';
    return $s === 'endscreen' ? 'endscreen' : 'lobby';
}
function _SWURoomHumans(object $lobby): array {
    return array_values(array_filter($lobby->players ?? [], fn($p) => $p instanceof Player && $p->getBotProfile() === ''));
}
// IN-LOCK. Make the lobby follow its match. Returns whether anything changed.
function SWURoomSyncWithMatch(object $lobby, array $match, int $now): bool {
    if (strval($match['state'] ?? '') !== 'complete') {
        $game = MatchCurrentGameName($match);
        if (($lobby->state ?? '') === 'started' && strval($lobby->gameName ?? '') === $game) return false;
        $lobby->state = 'started';
        $lobby->ready = true;
        if ($game !== '') $lobby->gameName = $game;
        // A Quick Rematch or a Convert from the end screens: both players are on the board now, not on an end screen.
        $lobby->roomSeatState = [];
        return true;
    }
    if (($lobby->state ?? '') !== 'started') return false;
    // Reopen: everyone is still looking at the end screen, nobody is Ready, and every seat counts as just seen — the
    // last room poll was before the match, so without the touch the first returner's poll would hand them the host.
    $lobby->state = 'open';
    $lobby->ready = false;
    unset($lobby->gameName);
    $lobby->roomSeatState = [];
    foreach (_SWURoomHumans($lobby) as $p) {
        $p->setReady(false);
        $p->touch($now);
        $lobby->roomSeatState[strval($p->getAuthKey())] = 'endscreen';
    }
    return true;
}
// IN-LOCK. This seat is back in the room: in the lobby, not Ready. Returns the Player, or null if the key holds no seat.
function SWURoomMarkInLobby(object $lobby, string $authKey, int $now): ?Player {
    $p = SWURoomFindPlayerByAuthKey($lobby, $authKey);
    if ($p === null) return null;
    if (!is_array($lobby->roomSeatState ?? null)) $lobby->roomSeatState = [];
    $lobby->roomSeatState[$authKey] = 'lobby';
    $p->setReady(false);
    $p->touch($now);
    return $p;
}
// IN-LOCK. Free a seat (Return to Menu, Requeue, or a quiet end screen). Same shape as LeaveQueue.php's removal.
function SWURoomReleaseSeat(object $lobby, string $authKey): bool {
    foreach (($lobby->players ?? []) as $i => $p) {
        if (!($p instanceof Player) || !hash_equals(strval($p->getAuthKey()), $authKey)) continue;
        array_splice($lobby->players, $i, 1);
        $lobby->numPlayers = max(0, intval($lobby->numPlayers ?? 1) - 1);
        if (is_array($lobby->roomSeatState ?? null)) unset($lobby->roomSeatState[$authKey]);
        SWUMigrateHostIfNeeded($lobby);
        return true;
    }
    return false;
}
// IN-LOCK. Release every end-screen seat whose page has gone quiet (tab closed) — only while the room is open; while a
// match from it is running every seat is held (call 11).
function SWURoomReleaseQuietSeats(object $lobby, int $now): bool {
    if (($lobby->state ?? '') !== 'open') return false;
    $quiet = [];
    foreach (_SWURoomHumans($lobby) as $p) {
        $key = strval($p->getAuthKey());
        if (SWURoomSeatState($lobby, $key) === 'endscreen' && ($now - $p->getLastSeen()) >= TABLE_REMATCH_QUIET_AFTER) $quiet[] = $key;
    }
    foreach ($quiet as $key) SWURoomReleaseSeat($lobby, $key);
    return !empty($quiet);
}
// Who is still on the end screen, by display name: the username, else "Guest PN" by table position — the same name
// the waiting room's seat tile prints for a guest, so the blocker and the tile agree.
function SWURoomEndScreenNames(object $lobby): array {
    $out = [];
    foreach (array_values($lobby->players ?? []) as $i => $p) {
        if (!($p instanceof Player) || $p->getBotProfile() !== '') continue;
        if (SWURoomSeatState($lobby, strval($p->getAuthKey())) !== 'endscreen') continue;
        $out[] = $p->getUsername() !== '' ? $p->getUsername() : 'Guest P' . ($i + 1);
    }
    return $out;
}

// ══ I/O ═════════════════════════════════════════════════════════════════════════════════════════════════════════════

// The match the room is following, or null (a room that has not started a match yet).
function SWURoomMatchFor(object $lobby): ?array {
    $mid = strval($lobby->roomMatchId ?? '');
    if ($mid === '') return null;
    $m = MatchRead('SWUSim', $mid);
    return is_array($m) ? $m : null;
}
// StartRoom created $matchId from this room: link both ways and save the room.
function SWURoomOnMatchStarted(string $lobbyID, string $matchId): void {
    MatchWithLock('SWUSim', $matchId, function (&$m) use ($lobbyID) { $m['roomId'] = $lobbyID; });
    $lobby = LobbyMutate($lobbyID, function ($l) use ($matchId) { $l->roomMatchId = $matchId; $l->roomSeatState = []; $l->persistentRoom = true; return true; });
    if (is_object($lobby)) SWURoomSave($lobby);
}
// Put a live room back into APCu from its snapshot when APCu has lost it. Returns whether the room is in APCu afterwards.
// The snapshot may lag the APCu copy by a few unsaved changes (a ready toggle, a deck swap): those are lost, the seats
// and the match link are not.
function SWURoomRehydrate(string $roomId): bool {
    if ($roomId === '' || !function_exists('apcu_fetch')) return false;
    if (is_object(apcu_fetch($roomId))) return true;
    $rec = SWURoomLoad($roomId);
    if ($rec === null) return false;
    // A High Ground room has no roomMatchId: it is alive while its LIVE hill match runs (nobody need be on the room page
    // while everyone watches), and it is never synced the 1v1 way (that would mark it started and redirect everyone).
    $hgLiveId = strval($rec['lobby']->highGround['live']['matchId'] ?? '');
    $matchId = $rec['matchId'] !== '' ? $rec['matchId'] : $hgLiveId;
    $match = $matchId !== '' ? MatchRead('SWUSim', $matchId) : null;
    $path = SWURoomPath($roomId);
    clearstatcache(true, $path);
    if (!SWURoomIsAlive(is_array($match) ? $match : null, intval(@filemtime($path)), time())) return false;
    $lobby = $rec['lobby'];
    if (is_array($match) && $rec['matchId'] !== '') SWURoomSyncWithMatch($lobby, $match, time());
    apcu_add($roomId, $lobby, LOBBY_TTL_SECONDS);   // add, not store: a concurrent rehydrate or a live write wins
    $code = strval($lobby->inviteCode ?? '');
    if ($code !== '') apcu_store('invite:' . $code, $roomId, LOBBY_TTL_SECONDS);
    return is_object(apcu_fetch($roomId));
}
// Does a snapshot exist for this invite whose room is no longer alive? ("This room has ended.")
function SWURoomEndedForInvite(string $code): bool {
    $id = SWURoomIdForInvite($code);
    return $id !== '' && SWURoomLoad($id) !== null && !SWURoomRehydrate($id);
}
// An invite join is about to look at this room: bring it back from disk if APCu lost it, bring it in step with its
// match (a match running from it holds every seat), and free any end-game seat that has gone quiet — so the join's
// capacity and already-started checks see the room as it really is.
function SWURoomPrepareForInvite(string $code, int $now): void {
    $roomId = SWURoomIdForInvite($code);
    if ($roomId === '' || !SWURoomRehydrate($roomId)) return;
    SWURoomSyncNow($roomId, $now, true);
}
// Bring a live room in step with its match right now (and optionally free quiet end screens). For the moments no room
// poll will come — an accepted Convert to Bo3, an invite join.
function SWURoomSyncNow(string $roomId, int $now, bool $releaseQuiet = false): void {
    if (!SWURoomRehydrate($roomId)) return;
    $match = SWURoomMatchFor(apcu_fetch($roomId));
    $changed = false;
    $lobby = LobbyMutate($roomId, function ($l) use ($match, $now, $releaseQuiet, &$changed) {
        if ($match !== null && SWURoomSyncWithMatch($l, $match, $now)) $changed = true;
        if ($releaseQuiet && SWURoomReleaseQuietSeats($l, $now)) $changed = true;
        return $changed;
    });
    if ($changed && is_object($lobby)) SWURoomSave($lobby);
}
// Where a finished 1v1 match came from: 'room' (a waiting room — Return to Lobby), 'public' (the public queue —
// Requeue), or 'legacy' (a private match from before rooms were linked — neither).
function SWUMatchOrigin(array $m): string {
    if (strval($m['roomId'] ?? '') !== '') return 'room';
    return empty($m['isPrivate']) ? 'public' : 'legacy';
}
// A seat's end-game screen poll (EndGameInfo.php) for a match that belongs to a room: it keeps the room in step with the
// match, keeps THIS seat present (call 5 — an hour-long Bo3 must not hand host to whoever gets back first, and the seat
// must not read as quiet), and frees the OTHER seat if its end screen went quiet. Throttled to once per 5s per seat.
// Only while the room is following THIS match — once a Quick Rematch moved it on, an old end screen changes nothing.
function SWURoomEndScreenHeartbeat(string $roomId, string $matchId, string $authKey, int $now): void {
    if ($roomId === '' || $authKey === '' || !function_exists('apcu_add')) return;
    if (!apcu_add('swu_room_hb_' . $roomId . '_' . md5($authKey), 1, 5)) return;
    if (!SWURoomRehydrate($roomId)) return;
    $match = MatchRead('SWUSim', $matchId);
    if (!is_array($match)) return;
    $changed = false;
    $lobby = LobbyMutate($roomId, function ($l) use ($match, $matchId, $authKey, $now, &$changed) {
        if (strval($l->roomMatchId ?? '') !== $matchId) return false;
        $changed = SWURoomSyncWithMatch($l, $match, $now);
        $me = SWURoomFindPlayerByAuthKey($l, $authKey);
        if ($me !== null && SWURoomSeatState($l, $authKey) === 'endscreen') $me->touch($now);
        if (SWURoomReleaseQuietSeats($l, $now)) $changed = true;
        return true;
    });
    if ($changed && is_object($lobby)) SWURoomSave($lobby);
    SWURoomTouch($roomId, $now);
}

// A seat of a room's finished match goes back to the room (Return to Lobby, or an accepted Rematch). Returns
// ['lobbyID' => …] or ['error' => …]. The lobby seat key IS the match seat key (SWUSim/MatchHooks.php reuses it).
function SWURoomReturnFromMatch(array $m, int $seat): array {
    $roomId = strval($m['roomId'] ?? '');
    $key = strval($m['players'][strval($seat)]['authKey'] ?? '');
    if ($roomId === '' || $key === '' || !SWURoomRehydrate($roomId)) return ['error' => 'This room has ended.'];
    $roomMatch = SWURoomMatchFor(apcu_fetch($roomId));
    $now = time();
    $seated = false;
    $lobby = LobbyMutate($roomId, function ($l) use ($roomMatch, $key, $now, &$seated) {
        if ($roomMatch !== null) SWURoomSyncWithMatch($l, $roomMatch, $now);
        $seated = SWURoomMarkInLobby($l, $key, $now) !== null;
        return $seated;
    });
    if ($lobby === null) return ['error' => 'The room is busy — try again.'];
    if (!$seated) return ['error' => 'Your seat in that room has been given up.'];
    SWURoomSave($lobby);
    return ['lobbyID' => $roomId];
}

// An accepted Rematch after a PUBLIC match: a new private 1v1 room with these two players, both not Ready, decks
// loaded, the requester ($hostSeat) as host. Its id is the public pairing's lobby id when the match chat is 'l:<id>',
// so the room's chat IS the match's chat (owner: room chat carries over). Returns ['lobbyID', 'keys' => [seat => key]],
// or [] on failure.
function SWURoomBuildFromPublicMatch(array $m, int $hostSeat): array {
    require_once __DIR__ . '/LobbyAdapter.php';
    require_once __DIR__ . '/../APIs/Lobbies/Classes/LobbyBots.php';
    $adapter = new SWULobbyAdapter();
    $format = strval($m['format'] ?? 'premier');
    $stub = new stdClass(); $stub->format = $format; $stub->rootName = 'SWUSim';
    $names = MatchSeatDisplayNames($m);
    $now = time();
    $players = []; $keys = []; $hostId = 1;
    foreach ([1, 2] as $s) {
        $p = $m['players'][strval($s)] ?? null;
        if (!is_array($p)) return [];
        $deck = strval($p['deckLink'] ?? '');
        $userId = intval($p['userId'] ?? 0) > 0 ? intval($p['userId']) : null;
        $pl = new Player($s, $deck, '', $userId);
        if ($userId !== null && strval($names[$s] ?? '') !== '' && $names[$s] !== 'Guest P' . $s) $pl->setUsername(strval($names[$s]));
        LobbyApplyBotDeck($pl, $deck, $adapter->validateDeck($stub, $deck));   // deck identity + deckOk (not a bot)
        $pl->setReady(false);
        $pl->setJoinedAt($now);
        $pl->touch($now);
        $players[] = $pl;
        $keys[strval($s)] = $pl->getAuthKey();
        if ($s === $hostSeat) $hostId = $s;
    }
    $chatId = strval($m['chatId'] ?? '');
    $lobby = new stdClass();
    $lobby->id = str_starts_with($chatId, 'l:') ? preg_replace('/[^A-Za-z0-9_-]/', '', substr($chatId, 2)) : uniqid();
    if ($lobby->id === '') $lobby->id = uniqid();
    $lobby->rootName = 'SWUSim';
    $lobby->format = $format;
    $lobby->queueType = strval($m['requeue']['queueType'] ?? ($m['queueType'] ?? 'bo1'));
    $lobby->isPrivate = true;
    $lobby->maxPlayers = 2;
    $lobby->numPlayers = 2;
    $lobby->ready = false;
    $lobby->casterMode = false;
    $lobby->state = 'open';
    $lobby->hostPlayerID = $hostId;
    $lobby->hostUserId = $m['players'][strval($hostId)]['userId'] ?? null;
    $lobby->inviteCode = bin2hex(random_bytes(12));
    $lobby->players = $players;
    $lobby->roomSeatState = [];
    $lobby->persistentRoom = true;
    // store, not add: the public pairing's own lobby under this id finished long ago (a matched lobby lives 90s).
    apcu_store($lobby->id, $lobby, LOBBY_TTL_SECONDS);
    apcu_store('invite:' . $lobby->inviteCode, $lobby->id, LOBBY_TTL_SECONDS);
    SWURoomSave($lobby);
    return ['lobbyID' => $lobby->id, 'keys' => $keys];
}

// A seat of a finished 1v1 match pressed Return to Menu (AnnounceLeave.php). Idempotent. Only once the match is over —
// leaving mid-series is the forfeit path, which ends the match first.
function SWUEndScreenLeftForMenu(string $gameName, int $seat): void {
    if (!function_exists('EndReqMarkDeparted')) require_once __DIR__ . '/EndScreenRequests.php';
    $ref = MatchReadRef('SWUSim', $gameName);
    $m = is_array($ref) ? MatchRead('SWUSim', strval($ref['matchId'])) : null;
    if (!is_array($m) || count($m['players'] ?? []) !== 2 || strval($m['state'] ?? '') !== 'complete') return;
    MatchWithLock('SWUSim', strval($ref['matchId']), function (&$mm) use ($seat) { EndReqMarkDeparted($mm, $seat, 'menu'); });
    $roomId = strval($m['roomId'] ?? '');
    $key = strval($m['players'][strval($seat)]['authKey'] ?? '');
    if ($roomId === '' || $key === '' || !SWURoomRehydrate($roomId)) return;
    $released = false;
    $lobby = LobbyMutate($roomId, function ($l) use ($key, &$released) { $released = SWURoomReleaseSeat($l, $key); return $released; });
    if ($released && is_object($lobby)) SWURoomSave($lobby);
}

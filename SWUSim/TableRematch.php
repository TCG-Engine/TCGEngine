<?php
// Twin Suns rematch — the table-wide vote for Quick Rematch / Rematch after a finished 3-4 seat game.
// Spec: docs/superpowers/specs/2026-10-20-twinsuns-rematch-design.md · tests: SWUSim/DevTools/tests/table_rematch_votes_test.php
//
// PURE section first: functions over the match ARRAY only (no APCu, no files, no HTTP), so the whole vote is
// unit-testable. The state lives on the match record:
//   $m['tableRematch'] = ['since' => int,                          // when the vote was opened (quiet clock base)
//                         'choices' => [seat => 'quick'|'room'],   // one choice per player
//                         'left' => [seat => 'announced'|'kicked'],
//                         'claim' => null|['kind', 'at'],           // set ONCE, under the match lock, by whoever fires
//                         'result' => null|['kind' => 'quick', 'gameName']
//                                         |['kind' => 'room', 'lobbyID', 'inviteCode', 'keys' => [oldSeat => authKey]]]
//
// The rules (owner, 2026-10-20):
//   • voters = the humans seated at game start, eliminated players included; bots never vote, spectators never vote
//   • a seat is LEFT if it pressed Main Menu ('announced'), was removed for inactivity ('kicked'), or is QUIET — no
//     end-screen heartbeat for TABLE_REMATCH_QUIET_AFTER seconds
//   • Quick needs every human and nobody LEFT; Room needs every human who has NOT left (at least one)

if (!defined('TABLE_REMATCH_QUIET_AFTER')) define('TABLE_REMATCH_QUIET_AFTER', 120);
// A claim with no result after this long is a fire that died mid-way (a fatal while spawning): ignore it, so the table
// is not frozen forever. The result, once stored, is permanent.
if (!defined('TABLE_REMATCH_CLAIM_STALE')) define('TABLE_REMATCH_CLAIM_STALE', 90);

// Is a fire in progress (or done)? A stale claim without a result does not count.
function _TableRematchClaimed(array $tr, int $now): bool {
    if ($tr['result'] !== null) return true;
    if ($tr['claim'] === null) return false;
    return ($now - intval($tr['claim']['at'] ?? 0)) < TABLE_REMATCH_CLAIM_STALE;
}

function _TableRematchState(array $m): array {
    $tr = is_array($m['tableRematch'] ?? null) ? $m['tableRematch'] : [];
    $tr['choices'] = is_array($tr['choices'] ?? null) ? $tr['choices'] : [];
    $tr['left']    = is_array($tr['left'] ?? null) ? $tr['left'] : [];
    $tr['claim']   = $tr['claim'] ?? null;
    $tr['result']  = $tr['result'] ?? null;
    return $tr;
}

// The voters: every seat that is not a bot, sorted.
function TableRematchHumanSeats(array $m): array {
    $out = [];
    foreach (($m['players'] ?? []) as $seat => $p) {
        if (strval($p['botProfile'] ?? '') === '') $out[] = intval($seat);
    }
    sort($out);
    return $out;
}

// Humans counted as gone: announced, kicked, or quiet. $seen = [seat => last end-screen heartbeat].
function TableRematchLeftSeats(array $m, array $seen, int $now): array {
    $tr = _TableRematchState($m);
    // $seen['_since'] = when the heartbeat store itself was (re)created. After a wipe (server restart, APCu expunge)
    // every seat's stamp is gone; without this base, every seat would look quiet at once and a Room would fire with
    // only the first seat to poll.
    $since = max(intval($tr['since'] ?? 0), intval($seen['_since'] ?? 0));
    $out = [];
    foreach (TableRematchHumanSeats($m) as $s) {
        if (isset($tr['left'][strval($s)])) { $out[] = $s; continue; }
        $base = max(intval($seen[$s] ?? 0), $since);
        if ($base > 0 && ($now - $base) >= TABLE_REMATCH_QUIET_AFTER) $out[] = $s;
    }
    return $out;
}

// Record $seat's choice ('quick' | 'room' | ''). The same choice again withdraws it. Returns an error, or null.
function TableRematchSetChoice(array &$m, int $seat, string $choice, int $now): ?string {
    if (!in_array($choice, ['quick', 'room', ''], true)) return 'Unknown rematch choice.';
    if (!in_array($seat, TableRematchHumanSeats($m), true)) return 'Only players at this table can vote.';
    $tr = _TableRematchState($m);
    if (_TableRematchClaimed($tr, $now)) return 'The rematch is already starting.';
    if (isset($tr['left'][strval($seat)])) return 'You have left this table.';
    if (empty($tr['since'])) $tr['since'] = $now;
    $key = strval($seat);
    if ($choice === '' || (($tr['choices'][$key] ?? '') === $choice)) unset($tr['choices'][$key]);
    else $tr['choices'][$key] = $choice;
    $m['tableRematch'] = $tr;
    return null;
}

function TableRematchMarkLeft(array &$m, int $seat, string $why): void {
    $tr = _TableRematchState($m);
    $tr['left'][strval($seat)] = $why;
    unset($tr['choices'][strval($seat)]);
    $m['tableRematch'] = $tr;
}

function _TableRematchVotesFor(array $tr, string $kind, array $seats): array {
    return array_values(array_filter($seats, fn($s) => ($tr['choices'][strval($s)] ?? '') === $kind));
}

// What should fire now: 'quick', 'room', or '' (nothing yet — or already claimed).
function TableRematchOutcome(array $m, array $seen, int $now): string {
    $tr = _TableRematchState($m);
    if (_TableRematchClaimed($tr, $now)) return '';
    $humans = TableRematchHumanSeats($m);
    $left = TableRematchLeftSeats($m, $seen, $now);
    if (!empty($humans) && empty($left) && count(_TableRematchVotesFor($tr, 'quick', $humans)) === count($humans)) return 'quick';
    $stay = array_values(array_diff($humans, $left));
    if (!empty($stay) && count(_TableRematchVotesFor($tr, 'room', $stay)) === count($stay)) return 'room';
    return '';
}

// Display names by seat: the seat's username (accounts and bots), else "Player N".
function TableRematchNames(array $m): array {
    $out = [];
    foreach (($m['players'] ?? []) as $seat => $p) {
        $n = strval($p['username'] ?? '');
        $out[intval($seat)] = $n !== '' ? $n : 'Player ' . intval($seat);
    }
    ksort($out);
    return $out;
}

// What one viewer's end screen draws. A room result hands a seat ONLY its own new key; a seat with none (it left, or
// a spectator) gets the invite to join as a new seat.
function TableRematchView(array $m, array $seen, int $now, int $viewerSeat): array {
    $tr = _TableRematchState($m);
    $humans = TableRematchHumanSeats($m);
    $left = TableRematchLeftSeats($m, $seen, $now);
    $stay = array_values(array_diff($humans, $left));
    $result = null;
    if (is_array($tr['result'])) {
        if (($tr['result']['kind'] ?? '') === 'quick') {
            $result = ['kind' => 'quick', 'gameName' => strval($tr['result']['gameName'] ?? '')];
        } elseif (($tr['result']['kind'] ?? '') === 'room') {
            $key = strval($tr['result']['keys'][strval($viewerSeat)] ?? '');
            $isSeat = $viewerSeat >= 1 && isset($m['players'][strval($viewerSeat)]);
            // A seat with a key goes to its own seat; a SEAT of this table without one (it left) gets the invite.
            // Anyone else — a spectator, an unauthenticated caller — learns only that a room was made: a private
            // room's invite is never handed out (game names are sequential, so this is enumerable).
            if ($key !== '')   $result = ['kind' => 'room', 'lobbyID' => strval($tr['result']['lobbyID'] ?? ''), 'authKey' => $key];
            elseif ($isSeat)   $result = ['kind' => 'room', 'joinInvite' => strval($tr['result']['inviteCode'] ?? '')];
            else               $result = ['kind' => 'room'];
        }
    }
    return [
        'mine'     => strval($tr['choices'][strval($viewerSeat)] ?? ''),
        'quick'    => ['votes' => _TableRematchVotesFor($tr, 'quick', $humans), 'needed' => $humans, 'blocked' => !empty($left)],
        'room'     => ['votes' => _TableRematchVotesFor($tr, 'room', $stay), 'needed' => $stay, 'blocked' => empty($stay)],
        'left'     => $left,
        'names'    => TableRematchNames($m),
        'starting' => $tr['result'] === null && _TableRematchClaimed($tr, $now),
        'result'   => $result,
    ];
}

// ══ I/O section ════════════════════════════════════════════════════════════════════════════════════════════════════
// Everything below touches APCu, match files, games or lobbies. Dependencies load lazily, so the pure section above
// stays unit-testable on its own.

function _SWUTableRematchSeenKey(string $matchId): string { return 'swu_tr_seen_' . $matchId; }

// Last end-screen heartbeat per seat (EndGameInfo polls and votes stamp it). APCu: losing it costs nothing — every
// seat still on the end screen re-stamps within one poll.
function SWUTableRematchSeen(string $matchId): array {
    if (!function_exists('apcu_fetch')) return [];
    $v = apcu_fetch(_SWUTableRematchSeenKey($matchId));
    return is_array($v) ? $v : [];
}

function SWUTableRematchStampSeen(string $matchId, int $seat, int $now): void {
    if ($seat < 1 || !function_exists('apcu_store')) return;
    $seen = SWUTableRematchSeen($matchId);
    if (($now - intval($seen[$seat] ?? 0)) < 5) return;   // throttled: the end screen polls every 2.5s
    if (!isset($seen['_since'])) $seen['_since'] = $now;  // the store's own birth — the quiet clock's floor (see LeftSeats)
    $seen[$seat] = $now;
    apcu_store(_SWUTableRematchSeenKey($matchId), $seen, 3600);
}

// Record that a seat of $gameName's match has left the table (Main Menu, or removed for inactivity). Multi-seat only.
function SWUTableRematchMarkLeftForGame(string $gameName, int $seat, string $why): void {
    if (!function_exists('SWUReadMatchRef')) require_once __DIR__ . '/MatchFlow.php';
    $ref = SWUReadMatchRef($gameName);
    if (!is_array($ref) || empty($ref['matchId'])) return;
    $m = SWUReadMatch($ref['matchId']);
    if (!is_array($m) || count($m['players'] ?? []) <= 2) return;
    MatchWithLock('SWUSim', $ref['matchId'], function (&$mm) use ($seat, $why) { TableRematchMarkLeft($mm, $seat, $why); });
}

// QUICK REMATCH: a new match + game with the same seats, keys, decks, house rules, format and bots. Returns the new
// gameName ('' on failure). Writes NextGame.json on the old game so every viewer (spectators too) follows it.
function SWUTableRematchSpawnQuick(array $old, string $oldGame): string {
    $players = []; $resolvedDecks = []; $lobbyPlayers = [];
    $seats = array_map('intval', array_keys($old['players'] ?? []));
    sort($seats);
    foreach ($seats as $s) {
        $p = $old['players'][strval($s)];
        $players[$s] = ['originalDeck' => $p['originalDeck'] ?? [], 'authKey' => strval($p['authKey'] ?? ''),
            'userId' => $p['userId'] ?? null, 'deckIdentity' => strval($p['deckIdentity'] ?? ''),
            'deckLink' => strval($p['deckLink'] ?? ''), 'cosmetics' => $p['cosmetics'] ?? null,
            'botProfile' => strval($p['botProfile'] ?? ''), 'username' => strval($p['username'] ?? ''), 'team' => $p['team'] ?? null];
        $resolvedDecks[$s] = $p['originalDeck'] ?? [];
        $lobbyPlayers[] = new MatchSyntheticPlayer($s, strval($p['authKey'] ?? ''),
            ['botProfile' => $p['botProfile'] ?? '', 'username' => $p['username'] ?? '', 'team' => $p['team'] ?? null]);
    }
    $newId = MatchCreate('SWUSim', strval($old['format'] ?? ''), 'bo1', $players, !empty($old['isPrivate']));
    if (!$newId) return '';
    $houseRules = is_array($old['houseRules'] ?? null) ? $old['houseRules'] : [];
    $hostSeat = $old['hostSeat'] ?? null;
    MatchWithLock('SWUSim', $newId, function (&$m) use ($houseRules, $hostSeat) {
        if (!empty($houseRules)) $m['houseRules'] = $houseRules;
        if ($hostSeat !== null) $m['hostSeat'] = intval($hostSeat);
    });
    $lobby = new stdClass();
    $lobby->format = strval($old['format'] ?? '');
    $lobby->isPrivate = !empty($old['isPrivate']);
    $lobby->players = $lobbyPlayers;
    if (!empty($houseRules)) $lobby->houseRulesActive = $houseRules;
    $game = MatchHook('SWUSim', 'setupGame', $lobby, ['resolvedDecks' => $resolvedDecks, 'matchId' => $newId, 'gameNumber' => 1]);
    if (strval($game) === '') return '';
    MatchWithLock('SWUSim', $newId, function (&$m) use ($game) {
        $m['currentGameNumber'] = 1;
        $m['games'][] = ['gameName' => strval($game), 'gameNumber' => 1, 'winner' => null];
    });
    MatchWriteRef('SWUSim', $game, $newId, 1);
    if (function_exists('RegisterActiveGame')) RegisterActiveGame('SWUSim', strval($game), !empty($old['isPrivate']));
    $ptr = MatchNextGamePointerPath('SWUSim', $oldGame);
    if ($ptr !== '' && is_dir(dirname($ptr))) file_put_contents($ptr, json_encode(['nextGameName' => strval($game)]), LOCK_EX);
    return strval($game);
}

// REMATCH: a fresh waiting room built from the finished match. Returning humans in old-seat order with their last
// deck loaded, NOT ready; bots seated with their decks, READY; LEFT seats absent; old host is host (else the first
// returning human); house rules seeded so the same rules are lit; new invite code; same privacy and format.
// Returns ['lobbyID', 'inviteCode', 'keys' => [oldSeat => authKey]], or [] on failure.
function SWUTableRematchBuildRoom(array $old, array $leftSeats): array {
    $root = dirname(__DIR__);
    require_once $root . '/APIs/Lobbies/Classes/Player.php';
    require_once $root . '/APIs/Lobbies/Classes/LobbyStore.php';
    require_once $root . '/APIs/Lobbies/Classes/LobbyBots.php';
    require_once $root . '/APIs/Lobbies/Classes/HouseRules.php';
    require_once __DIR__ . '/LobbyAdapter.php';
    $adapter = new SWULobbyAdapter();
    $format = strval($old['format'] ?? 'twinsuns');
    $stub = new stdClass(); $stub->format = $format; $stub->rootName = 'SWUSim';
    $isTeam = function_exists('SWUFormatIsTeamFormat') && SWUFormatIsTeamFormat($format);

    $seats = array_map('intval', array_keys($old['players'] ?? []));
    sort($seats);
    $returning = array_values(array_filter($seats, fn($s) => !in_array($s, $leftSeats, true)));
    // Decks are validated BEFORE anything is stored — a deck link may need the network.
    $validated = [];
    foreach ($returning as $s) $validated[$s] = $adapter->validateDeck($stub, strval($old['players'][strval($s)]['deckLink'] ?? ''));

    $now = time();
    $players = []; $keys = []; $newIdOf = [];
    $pid = 1;
    foreach ($returning as $s) {
        $p = $old['players'][strval($s)];
        $deck = strval($p['deckLink'] ?? '');
        $isBot = strval($p['botProfile'] ?? '') !== '';
        $pl = new Player($pid, $deck, '', $isBot ? null : ($p['userId'] ?? null));
        if (strval($p['username'] ?? '') !== '') $pl->setUsername(strval($p['username']));
        LobbyApplyBotDeck($pl, $deck, $validated[$s]);
        if ($isBot) { $pl->setBotProfile(strval($p['botProfile'])); $pl->setReady(true); }
        else        { $pl->setReady(false); $keys[strval($s)] = $pl->getAuthKey(); }
        if ($isTeam) { $pl->setSeat($s); $pl->setTeam(strval($p['team'] ?? ($s % 2 === 1 ? 'red' : 'blue'))); }
        $pl->setJoinedAt($now);
        $pl->touch($now);
        $players[] = $pl;
        $newIdOf[$s] = $pid;
        ++$pid;
    }
    if (empty($keys)) return [];   // no returning human: nothing to open
    $hostSeat = intval($old['hostSeat'] ?? 0);
    if (!isset($keys[strval($hostSeat)])) $hostSeat = intval(array_key_first($keys));

    [, $maxPlayers] = SWUFormatSeatRange($format);
    $lobby = new stdClass();
    $lobby->id = uniqid();
    $lobby->rootName = 'SWUSim';
    $lobby->format = $format;
    $lobby->queueType = 'bo1';
    $lobby->isPrivate = !empty($old['isPrivate']);
    $lobby->maxPlayers = $maxPlayers;
    $lobby->numPlayers = count($players);
    $lobby->ready = false;
    $lobby->casterMode = false;
    $lobby->hostPlayerID = $newIdOf[$hostSeat];
    $lobby->hostUserId = $old['players'][strval($hostSeat)]['userId'] ?? null;
    $lobby->inviteCode = bin2hex(random_bytes(12));
    $lobby->players = $players;
    // House rules: every returning human voted for every rule that was active (in a private room the host is the only
    // voter), with the same values — so the same rules are lit, and changing them works as usual.
    $active = is_array($old['houseRules'] ?? null) ? $old['houseRules'] : [];
    if (!empty($active)) {
        $voters = $lobby->isPrivate ? [$newIdOf[$hostSeat]] : array_map(fn($s) => $newIdOf[intval($s)], array_keys($keys));
        $hr = ['votes' => [], 'values' => [], 'active' => []];
        foreach ($active as $rule => $val) {
            foreach ($voters as $v) $hr['votes'][$rule][$v] = true;
            if ($val !== true) $hr['values'][$rule] = intval($val);
        }
        $lobby->houseRules = $hr;
        LobbyHouseRulesResolve($lobby, $adapter->houseRuleDefs($lobby));
    }
    apcu_store($lobby->id, $lobby, LOBBY_TTL_SECONDS);
    apcu_store('invite:' . $lobby->inviteCode, $lobby->id, LOBBY_TTL_SECONDS);
    return ['lobbyID' => $lobby->id, 'inviteCode' => $lobby->inviteCode, 'keys' => $keys];
}

// Store a fire's result — only if THIS claim is still the live one and nothing has been stored yet. A claim that went
// stale (TABLE_REMATCH_CLAIM_STALE) may be re-taken by another caller; the older fire, finishing late, must then lose
// rather than overwrite the result the table may already have followed. Returns whether it stored.
function _SWUTableRematchStoreResult(string $matchId, int $claimAt, ?array $result): bool {
    $stored = false;
    MatchWithLock('SWUSim', $matchId, function (&$m) use ($claimAt, $result, &$stored) {
        $tr = $m['tableRematch'] ?? [];
        $mine = is_array($tr['claim'] ?? null) && intval($tr['claim']['at'] ?? -1) === $claimAt;
        if (!$mine || !empty($tr['result'])) return;
        if ($result === null) { $m['tableRematch']['claim'] = null; return; }   // let the table try again
        $m['tableRematch']['result'] = $result;
        $stored = true;
    });
    return $stored;
}

// If the table's vote is complete, fire it — exactly once (the claim is taken under the match lock; a second caller
// sees it and fires nothing). Called by every vote AND every end-screen poll, so a leave or a seat going quiet
// completes the vote with nobody clicking. A lock-free look comes first: with nothing due (the common case, every
// 2.5s per open end screen) the match record is neither locked nor rewritten.
function SWUTableRematchFireIfDue(string $matchId, string $oldGame): void {
    $now = time();
    $seen = SWUTableRematchSeen($matchId);
    $peek = SWUReadMatch($matchId);
    if (!is_array($peek) || ($peek['state'] ?? '') !== 'complete' || count($peek['players'] ?? []) <= 2) return;
    if (TableRematchOutcome($peek, $seen, $now) === '') return;
    $kind = ''; $left = [];
    MatchWithLock('SWUSim', $matchId, function (&$m) use (&$kind, &$left, $seen, $now) {
        $kind = TableRematchOutcome($m, $seen, $now);   // re-checked under the lock
        if ($kind === '') return;
        $left = TableRematchLeftSeats($m, $seen, $now);
        $m['tableRematch']['claim'] = ['kind' => $kind, 'at' => $now];
    });
    if ($kind === '') return;
    $old = SWUReadMatch($matchId);
    $result = null;
    try {
        if ($kind === 'quick') {
            $g = SWUTableRematchSpawnQuick($old, $oldGame);
            if ($g !== '') $result = ['kind' => 'quick', 'gameName' => $g];
        } else {
            $room = SWUTableRematchBuildRoom($old, $left);
            if (!empty($room)) $result = ['kind' => 'room'] + $room;
        }
    } catch (Throwable $e) {
        error_log('SWU table rematch failed (' . $matchId . '): ' . $e->getMessage());
    }
    if (!_SWUTableRematchStoreResult($matchId, $now, $result) && ($result['kind'] ?? '') === 'room' && function_exists('LobbyMutate')) {
        LobbyMutate(strval($result['lobbyID']), fn($l) => 'delete');   // lost the race: never leave an orphan room
    }
}

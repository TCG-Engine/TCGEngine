<?php
// The ONE place that read-modify-writes a lobby.
//
// Every lobby endpoint used to do its own `apcu_fetch → modify → apcu_store` — fourteen call sites
// across eight files, sharing a single APCu key, with no lock and no CAS. Two costs, both measured:
//
//   1. LOST WRITES. Whatever a writer did not know about when it fetched is erased when it stores.
//      JoinQueue and UpdateLobbyDeck held their snapshot across a remote swudb fetch (0.4s cold,
//      3.2-4.2s once swudb throttles, capped at CURLOPT_TIMEOUT 10) and then wrote the whole object
//      back, reverting every heartbeat, ready flag, team pick and join committed in between.
//   2. It made "when the 4th player joined, someone else dropped" a real causal chain rather than a
//      coincidence.
//
// ⚠ THE CALLBACK MUST NOT DO I/O. Resolve decks, hit the network and read the database BEFORE
// calling this and pass the results in. That is what keeps the critical section sub-millisecond, and
// it is why the lock below is uncontended in practice rather than a new bottleneck.
require_once __DIR__ . '/Player.php';

if (!defined('LOBBY_TTL_SECONDS'))       define('LOBBY_TTL_SECONDS', 900);
// The lock's own TTL is the self-heal: a worker killed mid-mutation cannot wedge the room for longer
// than this, and no mutation legitimately takes anywhere near it once I/O is hoisted out.
if (!defined('LOBBY_LOCK_TTL_SECONDS'))  define('LOBBY_LOCK_TTL_SECONDS', 5);
if (!defined('LOBBY_LOCK_WAIT_SECONDS')) define('LOBBY_LOCK_WAIT_SECONDS', 2.0);

function LobbyLockKey(string $lobbyID): string { return 'lobbylock:' . $lobbyID; }

/**
 * Run $fn against the lobby at $lobbyID under an exclusive lock, then store the result.
 *
 * $fn($lobby) returns:
 *   true / null  store the mutated lobby (the normal case)
 *   false        abandon the write — the caller decided not to change anything
 *   'delete'     remove the lobby from the cache (the last seat left)
 *
 * $ttl overrides LOBBY_TTL_SECONDS. Only the public queue needs it: a MATCHED lobby is kept alive
 * for 90s, just long enough for the pollers already waiting on it to receive the ready state.
 *
 * Returns the lobby, or NULL when the lobby is gone or the lock could not be taken. A null return is
 * a TRANSIENT failure the caller should report as such ("Room is busy, try again"), never a reason to
 * fall back to an unlocked write.
 */
function LobbyMutate(string $lobbyID, callable $fn, ?int $ttl = null) {
    if ($lobbyID === '' || !function_exists('apcu_add')) return null;
    $lockKey  = LobbyLockKey($lobbyID);
    $deadline = microtime(true) + LOBBY_LOCK_WAIT_SECONDS;
    while (!apcu_add($lockKey, 1, LOBBY_LOCK_TTL_SECONDS)) {
        if (microtime(true) >= $deadline) return null;
        usleep(5000);   // 5ms — a mutation is microseconds, so this almost never spins twice
    }
    try {
        $lobby = apcu_fetch($lobbyID);
        if (!is_object($lobby)) return null;
        $verdict = $fn($lobby);
        if ($verdict === false)    return $lobby;
        if ($verdict === 'delete') { apcu_delete($lobbyID); return $lobby; }
        apcu_store($lobbyID, $lobby, $ttl ?? LOBBY_TTL_SECONDS);
        return $lobby;
    } finally {
        apcu_delete($lockKey);   // released on the exception path too, or one bad request wedges the room
    }
}

/**
 * Resolve an invite code to a lobby cache key.
 *
 * Index first; the full-cache scan stays as a fallback so lobbies created before the index existed
 * (and any lobby whose index entry was evicted under memory pressure) still resolve.
 */
function LobbyKeyForInvite(string $code, string $rootName = ''): ?string {
    if ($code === '') return null;
    $hit = apcu_fetch('invite:' . $code);
    if (is_string($hit) && $hit !== '') {
        $lobby = apcu_fetch($hit);
        if (is_object($lobby) && strval($lobby->inviteCode ?? '') === $code
            && ($rootName === '' || strval($lobby->rootName ?? '') === $rootName)) return $hit;
    }
    $ci = function_exists('apcu_cache_info') ? apcu_cache_info() : null;
    if (!is_array($ci) || !isset($ci['cache_list']) || !is_array($ci['cache_list'])) return null;
    foreach ($ci['cache_list'] as $e) {
        if (!isset($e['info']) || !is_string($e['info']) || $e['info'] === '') continue;
        $cand = apcu_fetch($e['info']);
        if (!is_object($cand)) continue;
        if (strval($cand->inviteCode ?? '') !== $code) continue;
        if ($rootName !== '' && strval($cand->rootName ?? '') !== $rootName) continue;
        return $e['info'];
    }
    return null;
}

/**
 * Commit what game creation wrote onto the lobby, after the fact.
 *
 * Game creation is I/O (it writes the gamestate, the match record and the auth-keys file), so it runs
 * OUTSIDE the lock, on the copy LobbyMutate handed back. That copy is not the cached lobby, so
 * everything the sim's setupGame hook stamped onto it has to be carried across explicitly.
 *
 * ⚠ It is not just gameName. SWUSim/CreateGame.php calls setGamePlayerID() on every Player, and
 * AzukiSim/MatchHooks.php can set casterMode. Nothing reads either back off the CACHED lobby today —
 * SimGameBuildAuthKeysFromLobby() consumes gamePlayerID in the same request — but writing only
 * gameName here would leave a lobby whose seats silently carry gamePlayerID 0, which is a trap for
 * the next person rather than a bug for this one. Players are matched by authKey: it is the stable
 * identity, and playerID is a seat that game creation itself renumbers.
 */
function LobbyCommitGameCreation(string $lobbyID, object $created, string $state, ?int $ttl = null): ?object {
    $seats = [];
    foreach (($created->players ?? []) as $p) {
        if (!($p instanceof Player)) continue;
        $seats[strval($p->getAuthKey())] = $p->getGamePlayerID();
    }
    return LobbyMutate($lobbyID, function ($l) use ($created, $state, $seats) {
        $l->gameName   = $created->gameName ?? '';
        $l->state      = $state;
        $l->ready      = true;
        $l->casterMode = !empty($created->casterMode);
        foreach (($l->players ?? []) as $p) {
            if (!($p instanceof Player)) continue;
            $key = strval($p->getAuthKey());
            if (array_key_exists($key, $seats) && $seats[$key] !== null) $p->setGamePlayerID($seats[$key]);
        }
        return true;
    }, $ttl);
}

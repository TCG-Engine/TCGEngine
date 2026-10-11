<?php
// WHO ALREADY HAS A SEAT — the identity layer the join paths were missing.
//
// Three player reports on 2026-09-26 ("the game launched with me as both P1 and P3", "a guest signed
// in so they could chat and it added them twice", "1 player was somehow in the game twice") all came
// from the same hole: JoinQueue.php appended `new Player(...)` on every successful join and never
// asked whether that human was already sitting in the room. Abandoning a tab does not release a seat
// — by design, only Leave does — and a lobby lives 900s with its TTL renewed by anyone's poll, so
// coming back ten minutes later lands in a room that still holds your old seat.
//
// ⚠ NOTHING DOWNSTREAM CAN CATCH THIS. A Gamestate carries no player identity at all — no userId, no
// authKey, no username (verified on game 1311327) — so a duplicate that reaches the game is invisible
// to the engine forever. The lobby is the only place it can be prevented.
//
// Owner ruling, 2026-09-26: RECLAIM the existing seat (update it with the newly submitted deck and
// hand back its authKey) rather than refuse — a player who comes back with a different deck means to
// swap decks, not to be locked out. And one account holds a seat in ONE room: joining elsewhere
// releases the old seat rather than leaving a ghost occupying a slot for the rest of the TTL.
require_once __DIR__ . '/Player.php';
require_once __DIR__ . '/LobbyStore.php';

/**
 * The seat this joiner ALREADY holds in $lobby, or null.
 *
 * ⚠ TWO KEYS, IN THIS ORDER, AND BOTH ARE LOAD-BEARING.
 *  - authKey first. It is the only key a GUEST can present, and it is the only one that survives a
 *    sign-in: the guest's seat carries userId NULL, so after they log in the account no longer
 *    matches anything and account-matching alone would mint them a second seat (report 2). The
 *    client keeps it in localStorage under `tcg:lobbyAuth:<lobbyID>` with a 24h backstop, so it is
 *    genuinely available on a rejoin.
 *  - account second. It covers the case the authKey cannot: a DIFFERENT browser, a cleared tab, or
 *    a return via the main menu, which never knew the lobbyID and so holds no key (report 1).
 *
 * A bot seat carries userId NULL and is therefore never matched by the account branch.
 */
function LobbyFindExistingSeat($lobby, $joiningUserId, string $presentedAuthKey = ''): ?Player
{
    if (!is_object($lobby) || empty($lobby->players) || !is_array($lobby->players)) return null;

    if ($presentedAuthKey !== '') {
        foreach ($lobby->players as $p) {
            if (!($p instanceof Player)) continue;
            if (hash_equals(strval($p->getAuthKey()), $presentedAuthKey)) return $p;
        }
    }
    $uid = (int)$joiningUserId;
    if ($uid > 0) {
        foreach ($lobby->players as $p) {
            if (!($p instanceof Player)) continue;
            if ($p->getBotProfile() !== '') continue;
            if ((int)$p->getUserId() === $uid) return $p;
        }
    }
    return null;
}

/**
 * Has this lobby already started? A started room's seats are the GAME's seats and must never be
 * reclaimed, renumbered or released out from under a live match.
 */
function LobbyHasStarted($lobby): bool
{
    if (!is_object($lobby)) return false;
    if (!empty($lobby->gameName)) return true;
    return in_array(strval($lobby->state ?? ''), ['starting', 'started', 'matched'], true);
}

/**
 * Every OPEN lobby of $rootName in which this joiner already holds a seat.
 *
 * Returns [['lobbyID' => …, 'authKey' => …, 'format' => …, 'queueType' => …], …].
 *
 * ⚠ A FULL APCu WALK, and deliberately so: there is no per-user lobby index, and the public queue
 * already walks the same cache on every join, so this adds a second pass over a list that is short
 * in practice (lobbies, not gamestates, are what we open). If lobby counts ever grow enough to make
 * this hurt, the fix is a `userseat:<rootName>:<userId>` index maintained by join/leave — not a
 * cheaper scan.
 */
function LobbyFindMySeats(string $rootName, $userId, string $presentedAuthKey = '', string $excludeLobbyID = ''): array
{
    $ci = function_exists('apcu_cache_info') ? apcu_cache_info() : null;
    if (!is_array($ci) || !isset($ci['cache_list']) || !is_array($ci['cache_list'])) return [];
    $out = [];
    foreach ($ci['cache_list'] as $e) {
        if (!isset($e['info']) || !is_string($e['info']) || $e['info'] === '') continue;
        if ($e['info'] === $excludeLobbyID) continue;
        $lobby = apcu_fetch($e['info']);
        if (!is_object($lobby) || !isset($lobby->players, $lobby->rootName)) continue;
        if (strval($lobby->rootName) !== $rootName) continue;
        if (LobbyHasStarted($lobby)) continue;                 // a live match is not ours to touch
        $seat = LobbyFindExistingSeat($lobby, $userId, $presentedAuthKey);
        if ($seat === null) continue;
        $out[] = [
            'lobbyID'   => $e['info'],
            'authKey'   => strval($seat->getAuthKey()),
            'format'    => strval($lobby->format ?? ''),
            'queueType' => strval($lobby->queueType ?? 'bo1'),
            'isPrivate' => !empty($lobby->isPrivate),
        ];
    }
    return $out;
}

/**
 * Drop the seat holding $authKey from $lobbyID.
 *
 * Mirrors LeaveQueue.php's removal: splice, decrement, delete the lobby when the last human goes,
 * and migrate the host BEFORE the store so the room is never written back naming a host nobody
 * holds. It is a separate implementation only because LeaveQueue also owns an HTTP response; if a
 * third caller ever appears, collapse them rather than copy this again.
 */
function LobbyReleaseSeat(string $lobbyID, string $authKey): bool
{
    if ($lobbyID === '' || $authKey === '') return false;
    $released = false;
    LobbyMutate($lobbyID, function ($lobby) use ($authKey, &$released) {
        if (!isset($lobby->players) || !is_array($lobby->players)) return false;
        if (LobbyHasStarted($lobby)) return false;
        foreach ($lobby->players as $i => $p) {
            if (!($p instanceof Player)) continue;
            if (!hash_equals(strval($p->getAuthKey()), $authKey)) continue;
            array_splice($lobby->players, $i, 1);
            $lobby->numPlayers = count($lobby->players);
            $released = true;
            if (!array_filter($lobby->players, fn($q) => $q instanceof Player && $q->getBotProfile() === '')) return 'delete';
            if ($lobby->numPlayers <= 0) return 'delete';
            if (function_exists('SWUMigrateHostIfNeeded')) SWUMigrateHostIfNeeded($lobby);
            return true;
        }
        return false;
    });
    return $released;
}

/**
 * Release every seat this joiner holds in a lobby OTHER than $keepLobbyID.
 *
 * Called AFTER the new seat is secured, never before: releasing first and then failing to join would
 * cost someone a seat they still wanted.
 */
function LobbyReleaseOtherSeats(string $rootName, $userId, string $presentedAuthKey, string $keepLobbyID): int
{
    $n = 0;
    foreach (LobbyFindMySeats($rootName, $userId, $presentedAuthKey, $keepLobbyID) as $s) {
        if (LobbyReleaseSeat($s['lobbyID'], $s['authKey'])) $n++;
    }
    return $n;
}

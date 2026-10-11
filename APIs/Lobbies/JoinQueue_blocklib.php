<?php
require_once __DIR__ . '/../../Database/ConnectionManager.php';
require_once __DIR__ . '/../../Database/functions.inc.php';

// True when the joining user and a lobby host must not be paired.
function SWUJoinBlocked($joiningUserId, $hostUserId)
{
    return AreUsersBlocked((int)$joiningUserId, (int)$hostUserId);
}

// Every SEATED account id in a lobby. Guests and bots carry a NULL userId and drop out — they are
// nobody's block.
function SWULobbySeatedUserIds($lobby)
{
    if (!is_object($lobby) || empty($lobby->players)) return [];
    $ids = [];
    foreach ($lobby->players as $p) {
        if (!is_object($p) || !method_exists($p, 'getUserId')) continue;
        $u = (int)$p->getUserId();
        if ($u > 0) $ids[$u] = true;
    }
    return array_keys($ids);
}

// True when the joining user must not take a seat in THIS lobby — a block with ANY player already
// seated, not just the host.
//
// ⚠ TESTING THE HOST ALONE IS COMPLETE ONLY AT TWO SEATS. Twin Suns / Team Suns rooms hold 3-4, so
// a block pair can meet through a third-party host: A blocks B, C hosts, A joins, B queues, and a
// host-only check pairs B with A because B has no quarrel with C (reported 2026-09-26).
//
// Owner ruling, 2026-09-26: refuse the join. The caller falls through to the generic
// "invalid/expired/full" response — which never reveals the block — and the refused player ends up
// creating a room of their own for other people to join.
function SWUJoinBlockedFromLobby($joiningUserId, $lobby)
{
    return AreUsersBlockedAny((int)$joiningUserId, SWULobbySeatedUserIds($lobby));
}

// Read the host (seat-1) userId from a lobby object; 0 if unavailable/anonymous.
function SWULobbyHostUserId($lobby)
{
    if (!is_object($lobby) || empty($lobby->players) || !isset($lobby->players[0])) return 0;
    $host = $lobby->players[0];
    if (is_object($host) && method_exists($host, 'getUserId')) {
        return (int)$host->getUserId();
    }
    return 0;
}

<?php
require_once __DIR__ . '/../../Database/ConnectionManager.php';
require_once __DIR__ . '/../../Database/functions.inc.php';

// True when the joining user and a lobby host must not be paired.
function SWUJoinBlocked($joiningUserId, $hostUserId)
{
    return AreUsersBlocked((int)$joiningUserId, (int)$hostUserId);
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

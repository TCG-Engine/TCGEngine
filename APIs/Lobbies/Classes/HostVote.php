<?php
// Kick Host vote — a room-forming (not in-game) vote to remove a host who is holding a room hostage:
// present but not starting it, and nobody else can. Pure functions over a $lobby stdClass, same
// contract as TeamRooms.php — no APCu, no HTTP, no globals, so the whole timer/vote/carry sequence
// is unit-testable (SWUSim/DevTools/tests/lobby_host_vote_test.php) with an injected $now.
//
// Threshold is the SAME rule as the in-game kick vote (KickVoteThreshold, non-unanimous): 2 of 2 at
// three seats, 2 of 3 at four — a lobby kick never needs the in-game "opposing team only" variant,
// because handing a team the win is not a thing that can happen before the game exists.

require_once __DIR__ . '/Player.php';
require_once __DIR__ . '/../../../Core/KickVoteThreshold.php';

// How long a room must sit at 3+ human seats, with the SAME host and roster, before a Kick Host
// vote may open. Matches SWU_LOBBY_HOST_AWAY_AFTER (TeamRooms.php) — an away host loses the room by
// the same deadline a present-but-idle one can be voted out.
if (!defined('SWU_HOSTVOTE_ARM_AFTER')) define('SWU_HOSTVOTE_ARM_AFTER', 120);

// Every human (non-bot) seat's playerID, sorted. A bot never counts toward the 3+ that arms the
// timer and never votes — same reasoning as SWUMigrateHostIfAway / SWUSeatIsAway.
function SWUHostVoteHumanPlayerIDs(object $lobby): array {
    $ids = [];
    foreach (($lobby->players ?? []) as $p) {
        if ($p instanceof Player && $p->getBotProfile() === '') $ids[] = intval($p->getPlayerID());
    }
    sort($ids);
    return $ids;
}

// Changes whenever the human roster or the host identity changes; unaffected by a bot join/leave.
// This IS the "roster change" the arming clock restarts on.
function SWUHostVoteSignature(object $lobby): string {
    return implode(',', SWUHostVoteHumanPlayerIDs($lobby)) . ':' . intval($lobby->hostPlayerID ?? 0);
}

// Re-stamp the arming clock when the signature has moved. Call this from the SAME LobbyMutate
// PollLobbyUpdates already runs on every poll (alongside touch()/SWUMigrateHostIfAway), so every
// join, leave, kick, or host migration re-arms for free with no new call site. Returns whether
// anything changed, so the caller can skip a write when it did not (LobbyMutate's own convention).
function SWUHostVoteArm(object $lobby, int $now): bool {
    $sig = SWUHostVoteSignature($lobby);
    if (($lobby->hostVoteSig ?? null) === $sig) return false;
    $lobby->hostVoteSig     = $sig;
    $lobby->hostVoteArmedAt = $now;
    return true;
}

// May a Kick Host vote be open right now? Needs 3+ human seats, the arm delay elapsed since the
// roster last settled, and no game created yet (a started room has StartRoom's own lock; KickSeat
// and StartRoom both already refuse once $lobby->gameName is set, and this mirrors that check so a
// stray vote can never race a start).
function SWUHostVoteWindowOpen(object $lobby, int $now): bool {
    if (!empty($lobby->gameName)) return false;
    if (count(SWUHostVoteHumanPlayerIDs($lobby)) < 3) return false;
    $armedAt = intval($lobby->hostVoteArmedAt ?? 0);
    if ($armedAt <= 0) return false;
    return ($now - $armedAt) >= SWU_HOSTVOTE_ARM_AFTER;
}

// Every human seat but the host. Bots are already excluded by SWUHostVoteHumanPlayerIDs.
function SWUHostVoteVoterSeats(object $lobby): array {
    $hostID = intval($lobby->hostPlayerID ?? 0);
    return array_values(array_filter(SWUHostVoteHumanPlayerIDs($lobby), fn($id) => $id !== $hostID));
}

function SWUHostVoteNeeded(object $lobby): int {
    return KickVoteThreshold(count(SWUHostVoteVoterSeats($lobby)), false);
}

// Raw Yes voter ids, scoped to the CURRENT host. A vote is an opinion about a specific host
// IDENTITY: if the room's host has since changed (this vote carried, or some other path such as
// SWUMigrateHostIfAway reassigned it), a tally recorded against the previous host reads as empty
// rather than silently carrying over onto whoever holds the seat now.
function SWUHostVoteYesIDs(object $lobby): array {
    $v = $lobby->hostVote ?? null;
    if (!is_array($v)) return [];
    if (intval($v['target'] ?? -1) !== intval($lobby->hostPlayerID ?? 0)) return [];
    return array_values(array_map('intval', array_keys($v['yes'] ?? [])));
}

// Record a Yes from $voterPlayerID. Returns false — changing nothing — when the vote is not open,
// or the voter is not a legal voter (the host, a bot, or nobody currently seated). Voting twice is
// a no-op, not an error: the caller does not need to distinguish "already voted" from "just voted".
function SWUHostVoteRecordYes(object $lobby, int $voterPlayerID, int $now): bool {
    if (!SWUHostVoteWindowOpen($lobby, $now)) return false;
    if (!in_array($voterPlayerID, SWUHostVoteVoterSeats($lobby), true)) return false;
    $hostID = intval($lobby->hostPlayerID ?? 0);
    $existing = (is_array($lobby->hostVote ?? null) && intval($lobby->hostVote['target'] ?? -1) === $hostID)
        ? $lobby->hostVote : ['target' => $hostID, 'yes' => []];
    if (isset($existing['yes'][$voterPlayerID])) return false;
    $existing['yes'][$voterPlayerID] = true;
    $lobby->hostVote = $existing;
    return true;
}

// Only Yes votes from CURRENT legal voters count — mirrors SWUVoteIsCarried's own filter (the
// in-game kick vote), so a seat that has since left, or the target itself, cannot carry a stale
// tally forward.
function SWUHostVoteTally(object $lobby): array {
    $legal  = SWUHostVoteVoterSeats($lobby);
    $yes    = array_values(array_intersect(SWUHostVoteYesIDs($lobby), $legal));
    $needed = SWUHostVoteNeeded($lobby);
    return ['yes' => $yes, 'needed' => $needed, 'carried' => $needed > 0 && count($yes) >= $needed];
}

// The read PollLobbyUpdates hands the client: whether the window is open, the live tally, and
// whether THIS viewer may vote / already has. $viewerPlayerID is 0 (or any id that holds no seat)
// for a viewer with no seat, which naturally reads as canVote=false, youVoted=false.
function SWUHostVoteState(object $lobby, int $now, int $viewerPlayerID): array {
    $open  = SWUHostVoteWindowOpen($lobby, $now);
    $tally = SWUHostVoteTally($lobby);
    $voted = in_array($viewerPlayerID, $tally['yes'], true);
    return [
        'open'         => $open,
        'yesCount'     => count($tally['yes']),
        'needed'       => $tally['needed'],
        'canVote'      => $open && !$voted && in_array($viewerPlayerID, SWUHostVoteVoterSeats($lobby), true),
        'youVoted'     => $voted,
        'hostPlayerID' => intval($lobby->hostPlayerID ?? 0),
    ];
}

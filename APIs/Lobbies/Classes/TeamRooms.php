<?php
// Team Suns room rules — PURE functions over a $lobby stdClass. No APCu, no HTTP, no globals,
// so every rule here is unit-testable (SWUSim/DevTools/tests/teamsuns_rooms_test.php) and the
// endpoints stay thin.
//
// Seats are FIXED SLOTS, not array positions: red holds 1 and 3, blue holds 2 and 4, giving the
// forced Red/Blue/Red/Blue table order the engine relies on. They must be explicit because
// LeaveQueue.php removes a player with array_splice, which reindexes — a positional seat would
// silently shuffle the whole table whenever anybody dropped.

require_once __DIR__ . '/Player.php';
$_swuFormatsPath = __DIR__ . '/../../../AppCore/SWU/Formats.php';
if (is_file($_swuFormatsPath)) require_once $_swuFormatsPath;
$_swuDeckValidationPath = __DIR__ . '/../../../AppCore/SWU/DeckValidation.php';
if (is_file($_swuDeckValidationPath)) require_once $_swuDeckValidationPath;

function SWURoomTeamSeatSlots($team) {
    if ($team === 'red')  return [1, 3];
    if ($team === 'blue') return [2, 4];
    return [];
}

function SWURoomTeamNames() { return ['red', 'blue']; }

// Is this lobby a team room at all? Everything below no-ops for a non-team format.
function SWURoomIsTeamLobby($lobby) {
    if (!is_object($lobby)) return false;
    if (($lobby->rootName ?? '') !== 'SWUSim') return false;
    if (!function_exists('SWUFormatIsTeamFormat')) return false;
    return SWUFormatIsTeamFormat($lobby->format ?? '');
}

function SWURoomTeamMembers($lobby, $team) {
    $out = [];
    foreach (($lobby->players ?? []) as $p) {
        if (($p instanceof Player) && $p->getTeam() === $team) $out[] = $p;
    }
    return $out;
}

// Assign $player to $team, taking the LOWEST free seat of that team's pair. Releases any seat the
// player already held, so switching teams frees the old slot for the next picker. $team === null
// unassigns. Returns false — changing NOTHING — if the team is unknown or already full.
function SWURoomAssignTeam($lobby, $player, $team) {
    if (!($player instanceof Player)) return false;
    if ($team === null) { $player->setTeam(null); $player->setSeat(null); return true; }

    $slots = SWURoomTeamSeatSlots($team);
    if (empty($slots)) return false;

    // Occupied slots on the target team, ignoring this player's own current seat.
    $taken = [];
    foreach (SWURoomTeamMembers($lobby, $team) as $m) {
        if ($m === $player) continue;
        if ($m->getSeat() !== null) $taken[] = intval($m->getSeat());
    }
    if (count($taken) >= count($slots)) return false;   // team full

    $free = null;
    foreach ($slots as $s) { if (!in_array($s, $taken, true)) { $free = $s; break; } }
    if ($free === null) return false;

    $player->setTeam($team);
    $player->setSeat($free);
    return true;
}

// The team a joiner must be forced onto, or null if they may pick. Per spec §4.3: auto-assign
// ONLY when exactly one team is already full. 0/0, 1/0 and 1/1 all leave the choice open.
function SWURoomAutoTeamOnJoin($lobby) {
    if (!SWURoomIsTeamLobby($lobby)) return null;
    $full = [];
    $open = [];
    foreach (SWURoomTeamNames() as $t) {
        $cap = count(SWURoomTeamSeatSlots($t));
        if (count(SWURoomTeamMembers($lobby, $t)) >= $cap) $full[] = $t; else $open[] = $t;
    }
    if (count($full) === 1 && count($open) === 1) return $open[0];
    return null;
}

// [seat => leader CardIDs] for every seated player, from the cache written at deck-validation time.
// Feed this into SWURoomStartBlockers so the team leader-conflict check runs in the LIVE path;
// without it that check is dead code and a team with two Vaders starts happily.
// Find a lobby member by authKey — THE identity lookup for every lobby endpoint.
//
// ⚠ Do NOT also match on playerID. playerID is a SEAT, not an identity: StartRoom compacts seats to
// 1..N and, in a team room, first sorts by the PICKED seat (red 1,3 / blue 2,4) — so a player's
// playerID changes at start whenever join order differs from seat order, which is the normal case.
// Every caller's stored playerID is stale from that moment on. authKey is a 128-bit per-player secret
// (bin2hex(random_bytes(16))), so it identifies the player alone, and matching it alone is strictly
// STRONGER than pairing it with a non-secret seat number.
//
// Twin Suns never sets a seat, so its sort is a no-op and playerIDs only ever shifted to close a gap
// left by someone leaving — which is why this was invisible until the first Team Suns start.
function SWURoomFindPlayerByAuthKey($lobby, $authKey) {
    if ($authKey === '' || $authKey === null) return null;
    foreach (($lobby->players ?? []) as $p) {
        if (($p instanceof Player) && hash_equals(strval($p->getAuthKey()), strval($authKey))) return $p;
    }
    return null;
}

function SWURoomLeaderSets($lobby) {
    $out = [];
    foreach (($lobby->players ?? []) as $p) {
        if (!($p instanceof Player)) continue;
        $seat = $p->getSeat();
        if ($seat === null) continue;
        $out[intval($seat)] = $p->getLeaders();
    }
    return $out;
}

// Human-readable reasons this room cannot start. [] means go.
// $leaderSets (optional) maps SEAT => that seat's leader CardIDs, for the team-wide leader check.
// Omit it to skip that check (e.g. when decks have not been resolved yet).
function SWURoomStartBlockers($lobby, array $leaderSets = []) {
    $blockers = [];
    $players = array_values(array_filter(($lobby->players ?? []), fn($p) => $p instanceof Player));

    [$minSeats] = function_exists('SWUFormatSeatRange')
        ? SWUFormatSeatRange($lobby->format ?? '') : [2, 2];
    if (count($players) < $minSeats) {
        $blockers[] = "Need {$minSeats} players to start (currently " . count($players) . ").";
    }

    foreach ($players as $p) {
        if (!$p->getDeckOk()) {
            $blockers[] = "Seat " . ($p->getSeat() ?? '?') . " has an illegal or unreadable deck.";
        }
    }

    if (!SWURoomIsTeamLobby($lobby)) return $blockers;

    foreach ($players as $p) {
        if ($p->getTeam() === null) { $blockers[] = "Every player must pick a team."; break; }
    }
    foreach (SWURoomTeamNames() as $t) {
        $cap = count(SWURoomTeamSeatSlots($t));
        $n   = count(SWURoomTeamMembers($lobby, $t));
        if ($n !== $cap) $blockers[] = "Team " . ucfirst($t) . " needs {$cap} players (currently {$n}).";
    }

    // Team-wide leader uniqueness (spec §2.1), by CANONICAL CardID so reprints collapse.
    if (!empty($leaderSets) && function_exists('SWUTeamLeaderConflicts')) {
        foreach (SWURoomTeamNames() as $t) {
            $sets = [];
            foreach (SWURoomTeamMembers($lobby, $t) as $m) {
                $seat = $m->getSeat();
                if ($seat !== null && isset($leaderSets[$seat])) $sets[] = $leaderSets[$seat];
            }
            foreach (SWUTeamLeaderConflicts($sets) as $dupe) {
                $blockers[] = "Team " . ucfirst($t) . ": two players have the same leader ({$dupe}).";
            }
        }
    }

    return $blockers;
}

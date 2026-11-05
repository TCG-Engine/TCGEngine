<?php
// Limited room state — the pure rules of a Sealed (later Draft) room, kept on the lobby as $lobby->limited.
// Spec: docs/superpowers/specs/2026-11-04-swusim-limited-sealed-draft-design.md §3.1, §3.3, §3.4, §3.6.
//
// Like HighGroundState.php: every function takes the blob and returns a NEW blob, or null when the move is refused. No I/O
// and no card dictionary — the lobby poll loads this file on every request. The caller validates decks and settings
// (LimitedDeck.php / LimitedSettings.php need card data) and passes the verdicts in.
//
// Seats are keyed by the lobby playerID; auth keys never enter the blob, so the blob can be logged or snapshotted.
//   mode           'sealed'
//   phase          'setup' → 'building' → 'playing' → 'done'
//   settings / settingsErrors   the host's choices and what is wrong with them ([] = startable)
//   seats          [pid => [name, deck: null|{leader, base, main[]}, submitted, ready, dropped]]
//   lineups / pools / setsInPlay / seed   what the room OPENED at start — persisted, never rebuilt from the seed
//   buildDeadline  unix time the 30-minute build ends
//   starting       false, or the unix time a start claim was taken (the match is being created outside the lobby lock);
//                  a claim older than LT_START_CLAIM_SECONDS is a start that crashed and is free again
//   match          null | [matchId, gameName (the latest game), seats: [match seat => pid]]
//   result         null | [winner: ?pid, score: [pid => games won], reason: 'played'|'kicked'|'left']
//   kick           [target pid => [votes: [voter pid => true], waitUntil]]
require_once __DIR__ . '/LimitedDefaults.php';

const LT_BUILD_SECONDS      = 1800;   // owner: 30 minutes to build
const LT_SIDEBOARD_SECONDS  = 300;    // the between-games builder cap in a Bo3 (spec §3.4)
const LT_KICK_WAIT_SECONDS  = 20;     // "Wait another 20 seconds", as in every other format
const LT_DISCONNECT_SECONDS = 30;     // a seat silent this long is disconnected (the in-game rule)
const LT_START_CLAIM_SECONDS = 60;   // a start that crashed mid-way frees its claim after this (High Ground: HG_START_CLAIM_SECONDS)

function LTNew(string $mode): array {
    return ['mode' => $mode, 'phase' => 'setup', 'settings' => SWULimitedDefaultSettings($mode), 'settingsErrors' => [],
            'seats' => [], 'lineups' => [], 'pools' => [], 'setsInPlay' => [], 'seed' => 0, 'buildDeadline' => null,
            'starting' => false, 'match' => null, 'result' => null, 'kick' => []];
}

function LTSeatCap(array $lt): int {
    return 2;   // Sealed is exactly two players (Draft's 3–8 arrives with Draft)
}

// The seats still in the event, in seat order.
function LTLiveSeats(array $lt): array {
    $out = array_keys(array_filter($lt['seats'], fn($s) => empty($s['dropped'])));
    sort($out);
    return $out;
}

function LTAddSeat(array $lt, int $pid, string $name): ?array {
    if ($lt['phase'] !== 'setup' || isset($lt['seats'][$pid]) || count($lt['seats']) >= LTSeatCap($lt)) return null;
    $lt['seats'][$pid] = ['name' => $name, 'deck' => null, 'submitted' => false, 'ready' => false, 'dropped' => false];
    return $lt;
}

// Settings change only during setup. Both values are stored, valid or not, so the host sees what is wrong.
function LTSetSettings(array $lt, array $settings, array $errors): ?array {
    if ($lt['phase'] !== 'setup') return null;
    $lt['settings'] = $settings;
    $lt['settingsErrors'] = array_values($errors);
    return $lt;
}

function LTStart(array $lt, array $lineups, array $pools, array $setsInPlay, int $seed, int $now): ?array {
    if ($lt['phase'] !== 'setup' || $lt['settingsErrors'] !== [] || count(LTLiveSeats($lt)) !== LTSeatCap($lt)) return null;
    $lt['lineups'] = $lineups;
    $lt['pools'] = $pools;
    $lt['setsInPlay'] = $setsInPlay;
    $lt['seed'] = $seed;
    $lt['phase'] = 'building';
    $lt['buildDeadline'] = $now + LT_BUILD_SECONDS;
    return $lt;
}

// Submit saves an already-validated deck and un-Readies nothing (there is no editing after Ready). The Bo3 between-games
// builder writes to the match instead, never through here.
function LTSubmitDeck(array $lt, int $pid, array $deck): ?array {
    $s = $lt['seats'][$pid] ?? null;
    if ($lt['phase'] !== 'building' || $s === null || !empty($s['ready']) || !empty($s['dropped'])) return null;
    $lt['seats'][$pid]['deck'] = $deck;
    $lt['seats'][$pid]['submitted'] = true;
    $lt['seats'][$pid]['ready'] = false;
    return $lt;
}

function LTReady(array $lt, int $pid): ?array {
    $s = $lt['seats'][$pid] ?? null;
    if ($lt['phase'] !== 'building' || $s === null || empty($s['submitted']) || !empty($s['ready']) || !empty($s['dropped'])) return null;
    $lt['seats'][$pid]['ready'] = true;
    return $lt;
}

// At the deadline a submitted seat is readied with its submitted deck (spec §3.4 [my call]); a seat with none stays
// unready and becomes kickable (LTKickableSeats).
function LTExpireBuild(array $lt, int $now): array {
    if ($lt['phase'] !== 'building' || $lt['buildDeadline'] === null || $now < $lt['buildDeadline']) return $lt;
    foreach (LTLiveSeats($lt) as $pid) {
        if (!empty($lt['seats'][$pid]['submitted'])) $lt['seats'][$pid]['ready'] = true;
    }
    return $lt;
}

// Is a start claim in flight right now? A claim older than LT_START_CLAIM_SECONDS belongs to a start that crashed.
function _LTClaimIsFresh(array $lt, int $now): bool {
    return !empty($lt['starting']) && ($now - intval($lt['starting'])) < LT_START_CLAIM_SECONDS;
}

function LTReadyToStart(array $lt, ?int $now = null): bool {
    if ($lt['phase'] !== 'building' || _LTClaimIsFresh($lt, $now ?? time()) || $lt['match'] !== null) return false;
    $live = LTLiveSeats($lt);
    if ($live === []) return false;
    foreach ($live as $pid) if (empty($lt['seats'][$pid]['ready'])) return false;
    return true;
}

// The match is created outside the lobby lock (it does I/O), so the start is claimed first — exactly one caller wins.
function LTClaimStart(array $lt, ?int $now = null): ?array {
    $now = $now ?? time();
    if (!LTReadyToStart($lt, $now)) return null;
    $lt['starting'] = $now;
    return $lt;
}

function LTReleaseStart(array $lt): array {
    $lt['starting'] = false;
    return $lt;
}

// $seats = [match seat => pid].
function LTBeginMatch(array $lt, string $matchId, string $gameName, array $seats): ?array {
    if ($lt['phase'] !== 'building' || empty($lt['starting'])) return null;
    $lt['phase'] = 'playing';
    $lt['starting'] = false;
    $lt['match'] = ['matchId' => $matchId, 'gameName' => $gameName, 'seats' => $seats];
    return $lt;
}

// Follow the match ($m = its Match.json): the room points at its latest game, and a complete match ends the event.
function LTApplyMatch(array $lt, array $m): array {
    if ($lt['phase'] !== 'playing' || $lt['match'] === null) return $lt;
    $games = $m['games'] ?? [];
    if ($games) $lt['match']['gameName'] = strval(end($games)['gameName'] ?? $lt['match']['gameName']);
    if (($m['state'] ?? '') !== 'complete') return $lt;
    $score = [];
    foreach ($lt['match']['seats'] as $seat => $pid) $score[$pid] = intval($m['wins'][strval($seat)] ?? 0);
    $winnerSeat = intval($m['winner'] ?? 0);   // 0 = a drawn series
    $lt['phase'] = 'done';
    $lt['result'] = ['winner' => $lt['match']['seats'][$winnerSeat] ?? null, 'score' => $score, 'reason' => 'played'];
    return $lt;
}

// A seat leaves or is kicked. During setup that just frees the seat. During the build a Sealed event cannot go on with
// one player, so it ends and the other seat wins. Once the match runs, the match itself decides (the in-game kick).
function LTDropSeat(array $lt, int $pid, string $reason): array {
    if (!isset($lt['seats'][$pid])) return $lt;
    if ($lt['phase'] === 'setup') {
        unset($lt['seats'][$pid], $lt['kick'][$pid]);
        return $lt;
    }
    $lt['seats'][$pid]['dropped'] = true;
    unset($lt['kick'][$pid]);
    if ($lt['phase'] === 'building') {
        $left = LTLiveSeats($lt);
        $lt['phase'] = 'done';
        $lt['starting'] = false;
        $lt['result'] = ['winner' => count($left) === 1 ? $left[0] : null, 'score' => [], 'reason' => $reason];
    }
    return $lt;
}

// A TRUE majority of the voters (owner: 4 of 7). Not Core/KickVoteThreshold.php, which caps at 2 for every other format.
function LTKickVotesNeeded(int $voters): int {
    return $voters <= 0 ? 0 : intdiv($voters, 2) + 1;
}

// Who votes on $target: every other live seat.
function LTKickVoters(array $lt, int $target): array {
    return array_values(array_filter(LTLiveSeats($lt), fn($p) => $p !== $target));
}

// Who may be voted out now (spec §3.6 [my call]). Only during the build — setup has the host's own seat removal and a
// running match has the in-game kick. A seat is kickable when it has been silent for over 30 s, or once the build clock
// has run out without a submitted deck; a "Wait" holds the vote off for 20 s.
function LTKickableSeats(array $lt, array $lastSeen, int $now): array {
    if ($lt['phase'] !== 'building') return [];
    $out = [];
    foreach (LTLiveSeats($lt) as $pid) {
        if (intval($lt['kick'][$pid]['waitUntil'] ?? 0) > $now) continue;
        $gone = $now - intval($lastSeen[$pid] ?? 0) > LT_DISCONNECT_SECONDS;
        $late = $lt['buildDeadline'] !== null && $now >= $lt['buildDeadline'] && empty($lt['seats'][$pid]['submitted']);
        if ($gone || $late) $out[] = $pid;
    }
    return $out;
}

function LTKickVote(array $lt, int $voter, int $target, array $lastSeen, int $now): ?array {
    $voters = LTKickVoters($lt, $target);
    if (!in_array($voter, $voters, true) || !in_array($target, LTKickableSeats($lt, $lastSeen, $now), true)) return null;
    $lt['kick'][$target]['votes'][$voter] = true;
    $lt['kick'][$target]['waitUntil'] = intval($lt['kick'][$target]['waitUntil'] ?? 0);
    $yes = count(array_intersect(array_keys($lt['kick'][$target]['votes']), $voters));
    return $yes >= LTKickVotesNeeded(count($voters)) ? LTDropSeat($lt, $target, 'kicked') : $lt;
}

// "Wait another 20 seconds": the vote on $target closes for 20 s and its votes so far are cleared.
function LTKickWait(array $lt, int $voter, int $target, int $now): ?array {
    if (!in_array($voter, LTKickVoters($lt, $target), true)) return null;
    $lt['kick'][$target] = ['votes' => [], 'waitUntil' => $now + LT_KICK_WAIT_SECONDS];
    return $lt;
}

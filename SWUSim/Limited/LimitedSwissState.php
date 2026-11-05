<?php
// Limited Swiss rounds — the pure rules of a Draft pod's three rounds, kept on the room blob as $lt['swiss'].
// Spec: docs/superpowers/specs/2026-11-04-swusim-limited-sealed-draft-design.md §3.4 (between rounds), §3.5, §3.6.
//
// Like LimitedRoomState.php: every function takes the blob and returns a NEW blob, or null when the move is refused. No
// I/O and no card dictionary — the lobby poll loads this file on every request.
//   round            the round being played or just finished (0 before round 1)
//   rounds           LT_SWISS_ROUNDS
//   pairings         [round => [pairing, …]] — pairing = [a, b (null = a bye), bye, matchId, gameName, seats: [match seat
//                    => pid], done, games: [pid => games won], winner: ?pid, draw]; a = the higher-ranked, match seat 1
//   betweenDeadline  unix time the 15-minute break ends (phase 'between')
//   pending          the next round's pairings while its matches are being created (the start claim is held)
require_once __DIR__ . '/LimitedRoomState.php';

const LT_SWISS_ROUNDS    = 3;     // spec §3.5: three rounds, no top cut
const LT_BETWEEN_SECONDS = 900;   // owner: 15 minutes between rounds
const LT_BETWEEN_KICK_GRACE = 120; // nobody is kickable in a break's first 2 minutes (players come back from their boards)

function LTSwissNew(): array {
    return ['round' => 0, 'rounds' => LT_SWISS_ROUNDS, 'pairings' => [], 'betweenDeadline' => null, 'pending' => null];
}

// Every seat of the pod (dropped ones too — they still count as opponents), best first: points → OMW% → GW% → OGW%,
// each percentage floored at 1/3. A bye is a match win for the seat itself and never an opponent. Two seats still tied
// after that are ordered by their match if they played one; anything else by the draft seating.
function LTSwissStandings(array $lt): array {
    $order = array_values($lt['draft']['order'] ?? array_keys($lt['seats']));
    $t = [];
    foreach ($order as $p) $t[$p] = ['points' => 0, 'wins' => 0, 'losses' => 0, 'draws' => 0, 'byes' => 0, 'played' => 0,
                                     'gWon' => 0, 'gPlayed' => 0, 'opps' => []];
    $beat = [];   // [winner][loser] => matches won
    foreach (($lt['swiss']['pairings'] ?? []) as $round) {
        foreach ($round as $x) {
            if (empty($x['done']) || !isset($t[$x['a']])) continue;
            if (!empty($x['bye'])) {
                $g = intval($x['games'][$x['a']] ?? 0);
                $t[$x['a']]['points'] += 3; $t[$x['a']]['wins']++; $t[$x['a']]['byes']++; $t[$x['a']]['played']++;
                $t[$x['a']]['gWon'] += $g; $t[$x['a']]['gPlayed'] += $g;
                continue;
            }
            $a = $x['a']; $b = $x['b'];
            if (!isset($t[$b])) continue;
            $ga = intval($x['games'][$a] ?? 0); $gb = intval($x['games'][$b] ?? 0);
            foreach ([[$a, $b, $ga, $gb], [$b, $a, $gb, $ga]] as [$me, $op, $gm, $go]) {
                $t[$me]['played']++; $t[$me]['opps'][] = $op;
                $t[$me]['gWon'] += $gm; $t[$me]['gPlayed'] += $gm + $go;
            }
            if (!empty($x['draw']) || $x['winner'] === null) {
                foreach ([$a, $b] as $p) { $t[$p]['points'] += 1; $t[$p]['draws']++; }
            } else {
                $w = $x['winner']; $l = $w === $a ? $b : $a;
                $t[$w]['points'] += 3; $t[$w]['wins']++; $t[$l]['losses']++;
                $beat[$w][$l] = ($beat[$w][$l] ?? 0) + 1;
            }
        }
    }
    $floor = 1 / 3;
    $mw = []; $gw = [];
    foreach ($t as $p => $r) {
        $mw[$p] = $r['played'] ? max($floor, floatval($r['points'] / (3 * $r['played']))) : $floor;   // floats: 2 / 2 is int 1
        $gw[$p] = $r['gPlayed'] ? max($floor, floatval($r['gWon'] / $r['gPlayed'])) : $floor;
    }
    $mean = fn(array $ps, array $v) => $ps ? array_sum(array_map(fn($o) => $v[$o], $ps)) / count($ps) : $floor;
    $rows = [];
    foreach ($order as $i => $p) {
        $r = $t[$p];
        $rows[] = ['playerID' => $p, 'name' => strval($lt['seats'][$p]['name'] ?? ''), 'rank' => 0, 'points' => $r['points'],
                   'wins' => $r['wins'], 'losses' => $r['losses'], 'draws' => $r['draws'], 'byes' => $r['byes'],
                   'mw' => $mw[$p], 'omw' => $mean($r['opps'], $mw), 'gw' => $gw[$p], 'ogw' => $mean($r['opps'], $gw),
                   'dropped' => !empty($lt['seats'][$p]['dropped']), '_seat' => $i];
    }
    $key = fn($r) => [-$r['points'], -round($r['omw'], 9), -round($r['gw'], 9), -round($r['ogw'], 9)];
    usort($rows, fn($x, $y) => $key($x) <=> $key($y) ?: $x['_seat'] <=> $y['_seat']);
    // Head-to-head: exactly two seats level on all four, and one of them won more of their matches against the other.
    for ($i = 0; $i + 1 < count($rows); $i++) {
        $j = $i; while ($j + 1 < count($rows) && $key($rows[$j + 1]) === $key($rows[$i])) $j++;
        if ($j === $i + 1) {
            $x = $rows[$i]['playerID']; $y = $rows[$j]['playerID'];
            if (($beat[$y][$x] ?? 0) > ($beat[$x][$y] ?? 0)) [$rows[$i], $rows[$j]] = [$rows[$j], $rows[$i]];
        }
        $i = $j;
    }
    foreach ($rows as $n => &$r) { $r['rank'] = $n + 1; unset($r['_seat']); }
    return $rows;
}

// Round $round's tables (spec §3.5): live seats only. Round 1 is the live seats shuffled with the room seed; later rounds
// pair the current standings top-down. Returns [[a, b], …] with a the higher-ranked (match seat 1); the bye, if any, last
// as [a, null].
function LTSwissPair(array $lt, int $round): array {
    $live = LTLiveSeats($lt);
    if ($round <= 1) {
        $order = (new \Random\Randomizer(new \Random\Engine\Mt19937(intval($lt['seed'] ?? 0) + 7919 * $round)))->shuffleArray($live);
    } else {
        $order = array_values(array_filter(array_column(LTSwissStandings($lt), 'playerID'), fn($p) => in_array($p, $live, true)));
    }
    $played = []; $hadBye = [];
    foreach (($lt['swiss']['pairings'] ?? []) as $r) foreach ($r as $x) {
        if (!empty($x['bye'])) $hadBye[] = $x['a']; else $played[] = [$x['a'], $x['b']];
    }
    return _LTSwissSearch(array_values($order), $played, $hadBye);
}

// The pairing search alone. $order is best-first; $played lists [pid, pid] matches so far; $hadBye the seats that had one.
// Depth-first over the order: the first seat left takes the best-ranked partner it can, backtracking when the rest cannot
// be paired. Cost = (rematches, second byes), compared in that order; the first order-respecting minimum wins, and the
// search stops at no cost. On an odd count the bye is tried on the lowest-ranked seat first.
function _LTSwissSearch(array $order, array $played, array $hadBye): array {
    $met = [];
    foreach ($played as [$x, $y]) { $met[$x][$y] = true; $met[$y][$x] = true; }
    $best = null; $bestCost = [PHP_INT_MAX, PHP_INT_MAX];
    $pairUp = function (array $left, array $acc, int $rematches, int $secondByes, ?array $bye) use (&$pairUp, &$best, &$bestCost, $met) {
        if ([$rematches, $secondByes] >= $bestCost) return;
        if ($left === []) { $best = $bye ? array_merge($acc, [$bye]) : $acc; $bestCost = [$rematches, $secondByes]; return; }
        $a = array_shift($left);
        foreach ($left as $i => $b) {
            $rest = $left; unset($rest[$i]);
            $pairUp(array_values($rest), array_merge($acc, [['a' => $a, 'b' => $b]]), $rematches + (isset($met[$a][$b]) ? 1 : 0), $secondByes, $bye);
            if ($bestCost === [0, 0]) return;
        }
    };
    if (count($order) % 2 === 0) {
        $pairUp($order, [], 0, 0, null);
    } else {
        for ($i = count($order) - 1; $i >= 0 && $bestCost !== [0, 0]; $i--) {
            $rest = $order; unset($rest[$i]);
            $pairUp(array_values($rest), [], 0, in_array($order[$i], $hadBye, true) ? 1 : 0, ['a' => $order[$i], 'b' => null]);
        }
    }
    return $best ?? [];
}

// ── The round flow ────────────────────────────────────────────────────────────────────────────────────────────────────

// May the next round start now? Round 1 starts as soon as the pod has built. Between rounds every live seat must be Ready,
// and then the host starts it — or the 15-minute deadline does, or nobody needs to once the host has dropped.
function LTSwissCanStartRound(array $lt, int $now, int $hostPid, bool $hostAsked): bool {
    if ($lt['mode'] !== 'draft' || _LTClaimIsFresh($lt, $now)) return false;
    if ($lt['phase'] === 'built') return count(LTLiveSeats($lt)) >= 2;
    if ($lt['phase'] !== 'between') return false;
    $live = LTLiveSeats($lt);
    foreach ($live as $p) if (empty($lt['seats'][$p]['ready'])) return false;
    $deadline = $lt['swiss']['betweenDeadline'] ?? null;
    return $hostAsked || ($deadline !== null && $now >= $deadline) || !in_array($hostPid, $live, true);
}

// Claim the next round: exactly one caller wins (the matches are created outside the lobby lock), and the round's tables
// are fixed here. A claim left by a start that crashed is simply replaced once it is stale.
function LTSwissClaimRound(array $lt, int $now, int $hostPid, bool $hostAsked): ?array {
    if (!LTSwissCanStartRound($lt, $now, $hostPid, $hostAsked)) return null;
    $lt['swiss'] = $lt['swiss'] ?? LTSwissNew();
    $lt['swiss']['pending'] = LTSwissPair($lt, intval($lt['swiss']['round']) + 1);
    $lt['starting'] = $now;
    return $lt;
}

function LTSwissReleaseClaim(array $lt): array {
    $lt['starting'] = false;
    if (isset($lt['swiss'])) $lt['swiss']['pending'] = null;
    return $lt;
}

// $matches = [pending index => [matchId, gameName, seats: [1 => a, 2 => b]]] for every table; a bye is won at once.
function LTSwissCommitRound(array $lt, array $matches, int $now): ?array {
    if (!in_array($lt['phase'], ['built', 'between'], true) || empty($lt['starting']) || !is_array($lt['swiss']['pending'] ?? null)) return null;
    $bye = intval($lt['settings']['bestOf'] ?? 1) === 3 ? 2 : 1;
    $round = [];
    foreach ($lt['swiss']['pending'] as $i => $x) {
        if ($x['b'] === null) {
            $round[] = ['a' => $x['a'], 'b' => null, 'bye' => true, 'matchId' => '', 'gameName' => '', 'seats' => [],
                        'done' => true, 'games' => [$x['a'] => $bye], 'winner' => $x['a'], 'draw' => false];
            continue;
        }
        $m = $matches[$i] ?? null;
        if (!is_array($m) || strval($m['matchId'] ?? '') === '') return null;
        $round[] = ['a' => $x['a'], 'b' => $x['b'], 'bye' => false, 'matchId' => strval($m['matchId']), 'gameName' => strval($m['gameName']),
                    'seats' => $m['seats'], 'done' => false, 'games' => [$x['a'] => 0, $x['b'] => 0], 'winner' => null, 'draw' => false];
    }
    $n = intval($lt['swiss']['round']) + 1;
    $lt['swiss']['round'] = $n;
    $lt['swiss']['pairings'][$n] = $round;
    $lt['swiss']['pending'] = null;
    $lt['swiss']['betweenDeadline'] = null;
    $lt['starting'] = false;
    $lt['phase'] = 'playing';
    return _LTSwissMaybeEndRound($lt, $now);   // a round of nothing but a bye (two seats left, one of them dropped) ends at once
}

// Follow a table's match ($m = its Match.json). Only the current round's tables; a finished table never changes again.
// A complete match records its games and winner (0 = a drawn series); an abandoned one is a draw with the games so far.
function LTSwissApplyMatch(array $lt, string $matchId, array $m, int $now): array {
    if ($lt['phase'] !== 'playing' || $matchId === '' || !isset($lt['swiss'])) return $lt;
    $n = intval($lt['swiss']['round']);
    foreach (($lt['swiss']['pairings'][$n] ?? []) as $i => $x) {
        if (!empty($x['bye']) || $x['matchId'] !== $matchId || !empty($x['done'])) continue;
        $games = $m['games'] ?? [];
        if ($games) $lt['swiss']['pairings'][$n][$i]['gameName'] = strval(end($games)['gameName'] ?? $x['gameName']);
        // The running game score, so a Bo3 table reads 1–0 after game 1 (the standings count only finished tables).
        $lt['swiss']['pairings'][$n][$i]['games'] = [$x['seats'][1] => intval($m['wins']['1'] ?? 0), $x['seats'][2] => intval($m['wins']['2'] ?? 0)];
        $state = strval($m['state'] ?? '');
        if ($state !== 'complete' && $state !== 'abandoned') return $lt;
        $x = $lt['swiss']['pairings'][$n][$i];
        $winnerSeat = $state === 'complete' ? intval($m['winner'] ?? 0) : 0;
        $x['winner'] = $x['seats'][$winnerSeat] ?? null;
        $x['draw'] = $x['winner'] === null;
        $x['done'] = true;
        $lt['swiss']['pairings'][$n][$i] = $x;
        return _LTSwissMaybeEndRound($lt, $now);
    }
    return $lt;
}

// Every table of the round is done: the 15-minute break (live seats un-Readied, their decks kept), or after the last
// round — or with fewer than two seats left — the end of the event. The winner is the best-ranked seat still in it.
function _LTSwissMaybeEndRound(array $lt, int $now): array {
    $n = intval($lt['swiss']['round']);
    foreach ($lt['swiss']['pairings'][$n] as $x) if (empty($x['done'])) return $lt;
    $live = LTLiveSeats($lt);
    if ($n < intval($lt['swiss']['rounds']) && count($live) >= 2) {
        $lt['phase'] = 'between';
        $lt['swiss']['betweenDeadline'] = $now + LT_BETWEEN_SECONDS;
        foreach ($live as $p) $lt['seats'][$p]['ready'] = false;
        return $lt;
    }
    $winner = null;
    foreach (LTSwissStandings($lt) as $r) if (in_array($r['playerID'], $live, true)) { $winner = $r['playerID']; break; }
    $lt['phase'] = 'done';
    $lt['result'] = ['winner' => $winner, 'score' => [], 'reason' => 'played'];
    return $lt;
}

// The break's clock: at the deadline every live seat is readied with its last deck (spec §3.4 [my call]).
function LTSwissExpireBetween(array $lt, int $now): array {
    $deadline = $lt['swiss']['betweenDeadline'] ?? null;
    if ($lt['phase'] !== 'between' || $deadline === null || $now < $deadline) return $lt;
    foreach (LTLiveSeats($lt) as $p) if (!empty($lt['seats'][$p]['submitted'])) $lt['seats'][$p]['ready'] = true;
    return $lt;
}

// The caller's table in the round being played (a bye included), or null.
function LTSwissMyPairing(array $lt, int $pid): ?array {
    if ($lt['phase'] !== 'playing' || !isset($lt['swiss'])) return null;
    foreach (($lt['swiss']['pairings'][intval($lt['swiss']['round'])] ?? []) as $x) if ($x['a'] === $pid || $x['b'] === $pid) return $x;
    return null;
}

// Who may be kick-voted between rounds (spec §3.6): a seat silent for over 30 s — but never in the break's first
// LT_BETWEEN_KICK_GRACE seconds, and silence counts from the break's start at the earliest. A seat's presence only moves
// while a Limited page polls, so the players of the round's last table were "last seen" when the round began.
function LTSwissKickable(array $lt, array $lastSeen, int $now): array {
    $deadline = $lt['swiss']['betweenDeadline'] ?? null;
    if ($lt['phase'] !== 'between' || $deadline === null) return [];
    $start = intval($deadline) - LT_BETWEEN_SECONDS;
    if ($now < $start + LT_BETWEEN_KICK_GRACE) return [];
    $out = [];
    foreach (LTLiveSeats($lt) as $p) {
        if (intval($lt['kick'][$p]['waitUntil'] ?? 0) > $now) continue;
        if ($now - max(intval($lastSeen[$p] ?? 0), $start) > LT_DISCONNECT_SECONDS) $out[] = $p;
    }
    return $out;
}

<?php
// Meta Premier rated queue — docs/superpowers/specs/2026-10-03-swusim-metapremier-ratings-design.md.
// Pure classifier + DB layer + queue helpers. All tunables live in this one block.
require_once __DIR__ . '/../AppCore/SWU/Formats.php';
require_once __DIR__ . '/../AppCore/SWU/Glicko2.php';

const MP_SEASON              = 1;        // no seasons yet; the column exists so starting them needs no migration
const MP_TAU                 = 0.5;
const MP_ABANDON_PENALTY     = 25.0;     // flat rating deduction on top of the Glicko-2 loss
const MP_RATING_FLOOR        = 100.0;
const MP_PROVISIONAL_RD      = 110.0;    // display only: above this the profile shows "1500?"
const MP_PERIOD_SECONDS      = 604800;   // one Glicko-2 rating period = 1 week (RD inflation while idle)
const MP_WINDOW_START        = 150;      // pairing window: ±150 ...
const MP_WINDOW_STEP         = 50;       // ... +50 ...
const MP_WINDOW_STEP_SECONDS = 15;       // ... every 15 s waited ...
const MP_WINDOW_OPEN_AFTER   = 120;      // ... and anyone at all after 2 minutes
const MP_COOLDOWNS           = [1 => 300, 2 => 1800, 3 => 86400];   // abandon strike → queue cooldown (s); 3+ = 24 h
const MP_FORGIVE_EVERY       = 10;       // clean rated matches that remove one strike
const MP_FORMAT              = 'metapremier';   // the rated format this module serves; glicko_* rows are keyed by it
const MP_TABLES              = ['glicko_ratings', 'glicko_results', 'glicko_penalties'];

// ── Pure: what does this finished match mean for ratings? (spec §4.2-§4.4) ──────────────────────────
// Null = not rateable (unrated format, unfinished, not exactly two distinct accounts, no winner).
// started  — game 1 got past pregame (mulligans + starting resources), or the series reached a 2nd game.
// outcome  — 'abandon' if the MATCH LOSER lost any game of the series by inactivity removal; else 'early' if they
//            conceded any game before Round 2's action phase; else 'concede' if they conceded the match or the deciding
//            game; else 'win'. 'abandon' and 'early' carry the penalty and a cooldown strike.
function SWUMetaPremierClassify(array $m): ?array {
    if (!SWUFormatIsRated(strval($m['format'] ?? ''))) return null;
    if (($m['state'] ?? '') !== 'complete') return null;
    $players = $m['players'] ?? [];
    if (count($players) !== 2) return null;
    $u1 = intval($players['1']['userId'] ?? 0); $u2 = intval($players['2']['userId'] ?? 0);
    if ($u1 <= 0 || $u2 <= 0 || $u1 === $u2) return null;
    $winner = intval($m['winner'] ?? 0);
    if ($winner !== 1 && $winner !== 2) return null;
    $loser = ($winner === 1) ? 2 : 1;
    $games = is_array($m['games'] ?? null) ? $m['games'] : [];

    $started = count($games) >= 2;
    foreach ($games as $g) if (($g['detail']['pregameDone'] ?? false) === true) $started = true;
    // A whole-match forfeit from a path with no live gamestate (blocking the opponent) cannot know whether pregame had
    // finished. It is a deliberate forfeit, so it is rated rather than being a free exit from a losing game 1.
    foreach ($games as $g) if (!empty($g['detail']['pregameUnknown'])) $started = true;

    $outcome = 'win';
    foreach ($games as $g) {
        if (($g['winner'] ?? null) === null) continue;
        if (($g['detail']['endReason'] ?? '') === 'abandon' && intval($g['winner']) !== $loser) { $outcome = 'abandon'; break; }
    }
    // EARLY concede (owner, 2026-10-05): the series loser conceded a game before Round 2's action phase — during the
    // mulligans, Round 1 or Round 1's regroup. Punished like an abandon: a ranked mode is for real practice, and one round
    // is not a game (the same Round-2 cutoff SWUStats uses). `turns` is the engine's round counter, captured with the game;
    // it reaches 2 only when Round 1's regroup ends. A forfeit with no live gamestate (blocking the opponent) has no
    // `turns` and stays an ordinary concede — blocking is a safety tool, not a dodge.
    if ($outcome === 'win') {
        foreach ($games as $g) {
            if (($g['winner'] ?? null) === null || intval($g['winner']) === $loser) continue;
            $d = $g['detail'] ?? [];
            if (($d['endReason'] ?? '') === 'concede' && isset($d['turns']) && intval($d['turns']) < 2) { $outcome = 'early'; break; }
        }
    }
    if ($outcome === 'win') {
        $last = null;
        foreach ($games as $g) if (($g['winner'] ?? null) !== null) $last = $g;
        if (intval($m['concededBy'] ?? 0) === $loser || (($last['detail']['endReason'] ?? '') === 'concede')) $outcome = 'concede';
    }
    return [
        'matchId'        => strval($m['matchId'] ?? ''),
        'matchCreatedAt' => intval($m['createdAt'] ?? 0),
        'format'         => strval($m['format']),
        'queueType'      => strval($m['queueType'] ?? 'bo1'),
        'winnerSeat'     => $winner,
        'loserSeat'      => $loser,
        'winnerUserId'   => $winner === 1 ? $u1 : $u2,
        'loserUserId'    => $winner === 1 ? $u2 : $u1,
        'outcome'        => $outcome,
        'started'        => $started,
    ];
}

// ── DB (spec §3) ──────────────────────────────────────────────────────────────────────────────────
// Needs Database/ConnectionManager.php + Database/functions.inc.php (DBTableExists) loaded by the caller.
// ⚠ mysqli prepare()/execute() THROW mysqli_sql_exception here (PHP 8.1+ default); there are no false returns to check.

function SWUMetaPremierTablesReady(mysqli $conn): bool {
    foreach (MP_TABLES as $t) if (!DBTableExists($conn, $t)) return false;
    return true;
}

// Create-if-missing, then lock, one rating row. Inside a transaction.
function _SWUMPLockRating(mysqli $conn, int $userId, string $format, string $qt, int $now): array {
    $season = MP_SEASON;
    $ins = $conn->prepare("INSERT IGNORE INTO glicko_ratings (userId, format, queueType, season, updatedAt) VALUES (?, ?, ?, ?, ?)");
    $ins->bind_param('issii', $userId, $format, $qt, $season, $now); $ins->execute(); $ins->close();
    $sel = $conn->prepare("SELECT rating, rd, volatility, games, wins, losses, abandons, lastRatedAt FROM glicko_ratings
                           WHERE userId = ? AND format = ? AND queueType = ? AND season = ? FOR UPDATE");
    $sel->bind_param('issi', $userId, $format, $qt, $season); $sel->execute();
    $row = $sel->get_result()->fetch_assoc(); $sel->close();
    return $row;
}

function _SWUMPLockPenalty(mysqli $conn, int $userId): array {
    $ins = $conn->prepare("INSERT IGNORE INTO glicko_penalties (userId) VALUES (?)");
    $ins->bind_param('i', $userId); $ins->execute(); $ins->close();
    $sel = $conn->prepare("SELECT abandonStrikes, cooldownUntil, cleanMatchesSinceStrike FROM glicko_penalties WHERE userId = ? FOR UPDATE");
    $sel->bind_param('i', $userId); $sel->execute();
    $row = $sel->get_result()->fetch_assoc(); $sel->close();
    return $row;
}

// A strike (abandon) or a clean match, applied to a locked penalty row. A strike resets the clean count and sets the
// cooldown for that strike number; every MP_FORGIVE_EVERY clean matches forgive one strike.
function _SWUMPWritePenalty(mysqli $conn, int $userId, array $p, bool $strike, int $now): void {
    $strikes = intval($p['abandonStrikes']); $clean = intval($p['cleanMatchesSinceStrike']); $until = intval($p['cooldownUntil']);
    if ($strike) {
        $strikes++; $clean = 0;
        $until = max($until, $now + MP_COOLDOWNS[min($strikes, 3)]);
        $st = $conn->prepare("UPDATE glicko_penalties SET abandonStrikes=?, cooldownUntil=?, lastAbandonAt=?, cleanMatchesSinceStrike=? WHERE userId=?");
        $st->bind_param('iiiii', $strikes, $until, $now, $clean, $userId);
    } else {
        $clean++;
        if ($strikes > 0 && $clean >= MP_FORGIVE_EVERY) { $strikes--; $clean = 0; }
        $st = $conn->prepare("UPDATE glicko_penalties SET abandonStrikes=?, cleanMatchesSinceStrike=? WHERE userId=?");
        $st->bind_param('iii', $strikes, $clean, $userId);
    }
    $st->execute(); $st->close();
}

// The idempotency gate: a second insert for the same (matchId, matchCreatedAt) throws duplicate-key 1062.
function _SWUMPInsertResult(mysqli $conn, array $c, int $rated, array $wb, array $wa, array $lb, array $la, float $penalty, int $now): void {
    $season = MP_SEASON;
    $fmt = strval($c['format']);   // required — the classifier always sets it; there is no default to fall back on
    $st = $conn->prepare("INSERT INTO glicko_results (matchId, matchCreatedAt, format, queueType, season, rated, winnerUserId, loserUserId, outcome,
        wRatingBefore, wRdBefore, wVolBefore, wRatingAfter, wRdAfter, wVolAfter,
        lRatingBefore, lRdBefore, lVolBefore, lRatingAfter, lRdAfter, lVolAfter, penaltyApplied, ratedAt)
        VALUES (?,?,?,?,?,?,?,?,?, ?,?,?,?,?,?, ?,?,?,?,?,?, ?,?)");
    // 23 placeholders: s i s s i i i i s | 12 × d (before/after) | d penalty | i ratedAt
    $st->bind_param('sissiiiis' . str_repeat('d', 13) . 'i',
        $c['matchId'], $c['matchCreatedAt'], $fmt, $c['queueType'], $season, $rated, $c['winnerUserId'], $c['loserUserId'], $c['outcome'],
        $wb['rating'], $wb['rd'], $wb['volatility'], $wa['rating'], $wa['rd'], $wa['volatility'],
        $lb['rating'], $lb['rd'], $lb['volatility'], $la['rating'], $la['rd'], $la['volatility'], $penalty, $now);
    $st->execute(); $st->close();
}

// Apply one classified match ($c = SWUMetaPremierClassify output). Returns:
//   'rated'        both ratings updated (+ abandon penalty/strike when outcome = abandon)
//   'strike_only'  pregame abandon: a cooldown strike, ratings untouched (spec §4.3)
//   'skipped'      pregame concede: nothing at all
//   'duplicate'    this match was already applied — nothing changed
//   'no_tables'    migration 17 not run on this server — nothing changed
//   'error'        any other DB failure (logged) — nothing changed
function SWUMetaPremierApply(mysqli $conn, array $c, ?int $now = null): string {
    $now = $now ?? time();
    if (!SWUMetaPremierTablesReady($conn)) return 'no_tables';
    $punished = in_array($c['outcome'], ['abandon', 'early'], true);   // a rated loss plus the penalty and a strike
    if (!$c['started'] && !$punished) return 'skipped';
    $conn->begin_transaction();
    try {
        $qt = $c['queueType'];
        $fmt = strval($c['format']);
        $wRow = _SWUMPLockRating($conn, $c['winnerUserId'], $fmt, $qt, $now);
        $lRow = _SWUMPLockRating($conn, $c['loserUserId'], $fmt, $qt, $now);
        $wb = ['rating' => floatval($wRow['rating']), 'rd' => floatval($wRow['rd']), 'volatility' => floatval($wRow['volatility'])];
        $lb = ['rating' => floatval($lRow['rating']), 'rd' => floatval($lRow['rd']), 'volatility' => floatval($lRow['volatility'])];

        if (!$c['started']) {
            _SWUMPInsertResult($conn, $c, 0, $wb, $wb, $lb, $lb, 0.0, $now);
            _SWUMPWritePenalty($conn, $c['loserUserId'], _SWUMPLockPenalty($conn, $c['loserUserId']), true, $now);
            $conn->commit();
            return 'strike_only';
        }

        // Each side is rated against the OTHER side's pre-match values; idle weeks since its last rated match inflate RD.
        $idle = fn($row) => $row['lastRatedAt'] === null ? 0.0 : max(0.0, ($now - intval($row['lastRatedAt'])) / MP_PERIOD_SECONDS);
        $wa = Glicko2Rate($wb, [['rating' => $lb['rating'], 'rd' => $lb['rd'], 'score' => 1.0]], $idle($wRow), MP_TAU);
        $la = Glicko2Rate($lb, [['rating' => $wb['rating'], 'rd' => $wb['rd'], 'score' => 0.0]], $idle($lRow), MP_TAU);
        $wa['rating'] = max(MP_RATING_FLOOR, $wa['rating']);
        $abandon = ($c['outcome'] === 'abandon');   // counted in `abandons`; an early concede is punished but not counted
        $glickoLoser = max(MP_RATING_FLOOR, $la['rating']);
        $la['rating'] = $punished ? max(MP_RATING_FLOOR, $glickoLoser - MP_ABANDON_PENALTY) : $glickoLoser;
        $penalty = $glickoLoser - $la['rating'];   // what was actually deducted (less than the full penalty at the floor)

        _SWUMPInsertResult($conn, $c, 1, $wb, $wa, $lb, $la, $penalty, $now);

        $season = MP_SEASON;
        $up = $conn->prepare("UPDATE glicko_ratings SET rating=?, rd=?, volatility=?, games=games+1, wins=wins+?, losses=losses+?,
                              abandons=abandons+?, lastRatedAt=?, updatedAt=? WHERE userId=? AND format=? AND queueType=? AND season=?");
        foreach ([[$c['winnerUserId'], $wa, 1, 0, 0], [$c['loserUserId'], $la, 0, 1, $abandon ? 1 : 0]] as [$uid, $after, $w, $l, $ab]) {
            $up->bind_param('dddiiiiiissi', $after['rating'], $after['rd'], $after['volatility'], $w, $l, $ab, $now, $now, $uid, $fmt, $qt, $season);
            $up->execute();
        }
        $up->close();
        _SWUMPWritePenalty($conn, $c['winnerUserId'], _SWUMPLockPenalty($conn, $c['winnerUserId']), false, $now);
        _SWUMPWritePenalty($conn, $c['loserUserId'],  _SWUMPLockPenalty($conn, $c['loserUserId']),  $punished, $now);
        $conn->commit();
        return 'rated';
    } catch (mysqli_sql_exception $e) {
        $conn->rollback();
        if (intval($e->getCode()) === 1062) return 'duplicate';
        error_log('SWUMetaPremierApply: ' . $e->getMessage());
        return 'error';
    }
}

function SWUMetaPremierGetRating(mysqli $conn, int $userId, string $queueType, string $format = MP_FORMAT): ?array {
    if (!SWUMetaPremierTablesReady($conn)) return null;
    $season = MP_SEASON;
    $st = $conn->prepare("SELECT rating, rd, volatility, games, wins, losses, abandons FROM glicko_ratings WHERE userId=? AND format=? AND queueType=? AND season=?");
    $st->bind_param('issi', $userId, $format, $queueType, $season); $st->execute();
    $row = $st->get_result()->fetch_assoc(); $st->close();
    return $row ?: null;
}

// Seconds left on this account's Meta Premier queue cooldown (0 = none).
function SWUMetaPremierCooldownLeft(mysqli $conn, int $userId, ?int $now = null): int {
    $now = $now ?? time();
    if (!SWUMetaPremierTablesReady($conn)) return 0;
    $st = $conn->prepare("SELECT cooldownUntil FROM glicko_penalties WHERE userId=?");
    $st->bind_param('i', $userId); $st->execute();
    $row = $st->get_result()->fetch_assoc(); $st->close();
    return $row ? max(0, intval($row['cooldownUntil']) - $now) : 0;
}

// ── Queue (spec §2.3, §5.1-5.2) ─────────────────────────────────────────────────────────────────────

// The rating gap a waiting lobby accepts after $waitSeconds in the queue: ±150, +50 per 15 s, anyone after 2 minutes.
function SWUMetaPremierWindow(int $waitSeconds): float {
    if ($waitSeconds >= MP_WINDOW_OPEN_AFTER) return INF;
    return MP_WINDOW_START + MP_WINDOW_STEP * intdiv(max(0, $waitSeconds), MP_WINDOW_STEP_SECONDS);
}

// Why this player may not join $format/$queueType right now, or null. Only rated formats are gated; every other
// format returns null untouched. $userId must come from the SESSION. $cooldownLeft(int $userId): int seconds.
function SWUMetaPremierQueueRefusal(string $format, string $queueType, ?int $userId, bool $isPrivateCreate, callable $cooldownLeft): ?array {
    if (!SWUFormatIsRated($format)) return null;
    $name = SWUGetFormat($format)['displayName'];
    if ($userId === null || $userId <= 0) return ['code' => 'login_required', 'message' => "Log in to play $name.", 'secondsLeft' => 0];
    if ($isPrivateCreate && SWUFormatIsQueueOnly($format)) return ['code' => 'queue_only', 'message' => "$name is played through matchmaking only.", 'secondsLeft' => 0];
    if (!SWUFormatAllowsQueueType($format, $queueType)) return ['code' => 'queue_type_unavailable', 'message' => "$name isn't open for that match type.", 'secondsLeft' => 0];
    $left = intval($cooldownLeft($userId));
    if ($left > 0) {
        $txt = intdiv($left, 60) . 'm ' . ($left % 60) . 's';
        return ['code' => 'cooldown', 'message' => "You left a rated match — $name unlocks in $txt.", 'secondsLeft' => $left];
    }
    return null;
}

// The APCu cache-list entries a rated joiner may pair with, best first: same format + match type, public, carrying a
// rating, not one of the joiner's own lobbies, and inside the WIDER of the two windows — the waiting lobby's and the
// joiner's own ($joinerWaitSeconds: how long the joiner has already been queued). Using only the waiting lobby's window
// left two out-of-range players waiting forever, each in their own lobby, as both windows opened (final review #1).
// Closest rating first; on a tie the longer-waiting lobby. JoinQueue's scan still applies every other join rule.
function SWUMetaPremierOrderCandidates(array $cacheList, callable $fetch, string $format, string $queueType, float $joinerRating, ?int $joinerUserId, int $now, int $joinerWaitSeconds = 0): array {
    $scored = [];
    foreach ($cacheList as $entry) {
        if (!isset($entry['info'])) continue;
        $l = $fetch($entry['info']);
        if (!is_object($l) || ($l->format ?? '') !== $format || ($l->queueType ?? '') !== $queueType) continue;
        if (!empty($l->isPrivate) || !isset($l->rating, $l->createdAt)) continue;
        $own = false;
        foreach (($l->players ?? []) as $p) {
            if ($joinerUserId !== null && $p instanceof Player && intval($p->getUserId()) === $joinerUserId) $own = true;
        }
        if ($own) continue;
        $diff = abs(floatval($l->rating) - $joinerRating);
        if ($diff > max(SWUMetaPremierWindow($now - intval($l->createdAt)), SWUMetaPremierWindow($joinerWaitSeconds))) continue;
        $scored[] = [$diff, intval($l->createdAt), $entry];
    }
    usort($scored, fn($x, $y) => [$x[0], $x[1]] <=> [$y[0], $y[1]]);
    return array_map(fn($s) => $s[2], $scored);
}

// When this player started waiting: the oldest of their own waiting rated lobbies of this format + match type, or null.
// A re-queue (the poll's `requeue`, or the player clicking Join Queue again) keeps this start, so the wait — and the
// window it has opened — carries over instead of snapping back to ±150.
function SWUMetaPremierOwnWaitStart(array $cacheList, callable $fetch, string $format, string $queueType, ?int $userId): ?int {
    if ($userId === null || $userId <= 0) return null;
    $start = null;
    foreach ($cacheList as $entry) {
        if (!isset($entry['info'])) continue;
        $l = $fetch($entry['info']);
        if (!is_object($l) || ($l->format ?? '') !== $format || ($l->queueType ?? '') !== $queueType || !isset($l->createdAt)) continue;
        // only a lobby still WAITING — a matched one keeps its APCu entry for its TTL after the game starts
        if (!empty($l->gameName) || (($l->state ?? '') === 'matched')) continue;
        foreach (($l->players ?? []) as $p) {
            if ($p instanceof Player && intval($p->getUserId()) === $userId) { $start = min($start ?? PHP_INT_MAX, intval($l->createdAt)); break; }
        }
    }
    return $start;
}

// The poll's question for a player waiting in lobby $myKey: is another waiting rated lobby now inside the wider of the
// two windows? Then the client re-queues, and JoinQueue — which applies the same rule — pairs the two.
function SWUMetaPremierShouldRequeue(string $myKey, array $cacheList, callable $fetch, int $now): bool {
    $me = $fetch($myKey);
    if (!is_object($me) || !isset($me->rating, $me->createdAt) || !empty($me->gameName)) return false;
    $mine = [];
    foreach (($me->players ?? []) as $p) if ($p instanceof Player && intval($p->getUserId()) > 0) $mine[] = intval($p->getUserId());
    $myWindow = SWUMetaPremierWindow($now - intval($me->createdAt));
    foreach ($cacheList as $entry) {
        $key = $entry['info'] ?? null;
        if ($key === null || $key === $myKey) continue;
        $l = $fetch($key);
        if (!is_object($l) || ($l->format ?? '') !== ($me->format ?? '') || ($l->queueType ?? '') !== ($me->queueType ?? '')) continue;
        if (!empty($l->isPrivate) || !empty($l->gameName) || !isset($l->rating, $l->createdAt)) continue;
        if (intval($l->numPlayers ?? 0) >= intval($l->maxPlayers ?? 2)) continue;
        $theirs = false;
        foreach (($l->players ?? []) as $p) if ($p instanceof Player && in_array(intval($p->getUserId()), $mine, true)) $theirs = true;
        if ($theirs) continue;
        $gap = abs(floatval($l->rating) - floatval($me->rating));
        if ($gap <= max($myWindow, SWUMetaPremierWindow($now - intval($l->createdAt)))) return true;
    }
    return false;
}

// The rating the queue pairs on: the player's current rating, or the Glicko-2 default for a new (or guest) player.
function SWUMetaPremierQueueRating(?int $userId, string $queueType, string $format = MP_FORMAT): float {
    if ($userId === null || $userId <= 0) return GLICKO2_DEFAULT_RATING;
    require_once __DIR__ . '/../Database/ConnectionManager.php';
    require_once __DIR__ . '/../Database/functions.inc.php';
    try { $r = SWUMetaPremierGetRating(GetLocalMySQLConnection(), $userId, $queueType, $format); }
    catch (Throwable $e) { $r = null; }
    return $r ? floatval($r['rating']) : GLICKO2_DEFAULT_RATING;
}

// Seconds of cooldown left, swallowing DB trouble (a missing table or a DB outage must not lock anyone out).
function SWUMetaPremierCooldownLeftSafe(int $userId): int {
    require_once __DIR__ . '/../Database/ConnectionManager.php';
    require_once __DIR__ . '/../Database/functions.inc.php';
    try { return SWUMetaPremierCooldownLeft(GetLocalMySQLConnection(), $userId); }
    catch (Throwable $e) { return 0; }
}

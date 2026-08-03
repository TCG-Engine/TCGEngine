<?php
// Core/Match/Match.php — game-agnostic Match: the top-level unit of play
// (Bo1 = bestOf 1, Bo3 = bestOf 3) owning a sequence of child games.
// Filesystem-durable under {rootName}/Matches; every read-modify-write is flock-guarded.
// Generified from SWUSim/Match.php: prefix stripped, $rootName threaded, deck-stats
// routed through the registered 'recordDeckStats' hook.
require_once __DIR__ . '/Hooks.php';
require_once __DIR__ . '/QueueTypes.php';

// Repo root is two levels up from Core/Match.
function MatchRepoRoot() { return dirname(dirname(__DIR__)); }

function MatchesDir($rootName) {
    $rootName = preg_replace('/[^A-Za-z0-9_]/', '', strval($rootName));
    $dir = MatchRepoRoot() . '/' . $rootName . '/Matches';
    if (!is_dir($dir)) mkdir($dir, 0777, true);
    return $dir;
}

function MatchPath($rootName, $matchId) {
    $matchId = preg_replace('/[^A-Za-z0-9_]/', '', strval($matchId));
    if ($matchId === '') return '';
    return MatchesDir($rootName) . '/' . $matchId . '/Match.json';
}

// Mirror of GetGameCounter (flock'd) for monotonic match IDs.
function MatchNextId($rootName) {
    $dir = MatchesDir($rootName);
    $filename = $dir . '/MatchIDCounter.txt';
    if (!is_file($filename)) file_put_contents($filename, '101');
    $fp = fopen($filename, 'r+');
    $tries = 0;
    while (!flock($fp, LOCK_EX) && $tries < 30) { sleep(1); ++$tries; }
    $counter = intval(fgets($fp));
    ftruncate($fp, 0); rewind($fp); fwrite($fp, $counter + 1);
    flock($fp, LOCK_UN); fclose($fp);
    $matchId = 'M' . $counter;
    if (!is_dir($dir . '/' . $matchId)) mkdir($dir . '/' . $matchId, 0777, true);
    return $matchId;
}

function MatchRead($rootName, $matchId) {
    $path = MatchPath($rootName, $matchId);
    if ($path === '' || !is_file($path)) return null;
    $decoded = json_decode(file_get_contents($path), true);
    return is_array($decoded) ? $decoded : null;
}

// The match carries its own rootName, so writes derive the path from the array.
function MatchWrite(array $match) {
    $path = MatchPath($match['rootName'] ?? '', $match['matchId'] ?? '');
    if ($path === '') return false;
    $match['updatedAt'] = time();
    return file_put_contents($path, json_encode($match), LOCK_EX) !== false;
}

// Read-modify-write under an exclusive lock. $fn receives the match array by
// reference; whatever it leaves in the array is persisted. Returns the new match.
function MatchWithLock($rootName, $matchId, callable $fn) {
    $dir = dirname(MatchPath($rootName, $matchId));
    $lockPath = $dir . '/Match.lock';
    $fp = fopen($lockPath, 'c');
    $tries = 0;
    while (!flock($fp, LOCK_EX) && $tries < 30) { usleep(100000); ++$tries; }
    $match = MatchRead($rootName, $matchId);
    if (is_array($match)) {
        $fn($match);
        MatchWrite($match);
    }
    flock($fp, LOCK_UN);
    fclose($fp);
    return $match;
}

function MatchCreate($rootName, $format, $queueType, $players, $isPrivate = false) {
    $qt = MatchGetQueueType($queueType);
    $bestOf = $qt ? intval($qt['bestOf']) : 1;
    $winsNeeded = intval(floor($bestOf / 2)) + 1;

    $matchId = MatchNextId($rootName);
    $matchPlayers = [];
    $wins = [];
    foreach ($players as $seat => $p) {
        $seatKey = strval($seat);
        $matchPlayers[$seatKey] = [
            'originalDeck' => $p['originalDeck'] ?? [], 'authKey' => strval($p['authKey'] ?? ''),
            'userId' => $p['userId'] ?? null, 'deckIdentity' => strval($p['deckIdentity'] ?? ''),
            'deckLink' => strval($p['deckLink'] ?? ''),
            'cosmetics' => $p['cosmetics'] ?? null,
        ];
        $wins[$seatKey] = 0;
    }
    $match = [
        'matchId'           => $matchId,
        'rootName'          => strval($rootName),
        'format'            => strval($format),
        'queueType'         => strval($queueType),
        'isPrivate'         => (bool)$isPrivate,   // private lobbies get no forced sideboard timer
        'bestOf'            => $bestOf,
        'winsNeeded'        => $winsNeeded,
        'players'           => $matchPlayers,
        'games'             => [],
        'wins'              => $wins,
        'currentGameNumber' => 0,
        'state'             => 'in_progress',
        'winner'            => null,
        'createdAt'         => time(),
        'updatedAt'         => time(),
    ];
    MatchWrite($match);
    return $matchId;
}

function MatchIsOver(array $match) {
    $need = intval($match['winsNeeded'] ?? 1);
    foreach (($match['wins'] ?? []) as $w) {
        if (intval($w) >= $need) return true;
    }
    return false;
}

function MatchWinner(array $match) {
    $need = intval($match['winsNeeded'] ?? 1);
    foreach (($match['wins'] ?? []) as $seat => $w) {
        if (intval($w) >= $need) return intval($seat);
    }
    return 0;
}

// Human-readable name per seat: the player's PUBLIC username when they were logged in, otherwise
// the neutral "Player N". Used by the end-game overlay, which has to name winners in a game with
// more than two seats ("Player 2" alone is meaningless when there are four of them).
// Best-effort by design: no DB (schema-test harness, local dev without MySQL), no userId (guest),
// or a deleted account all degrade to "Player N" rather than failing the whole endpoint.
// Returns [seat(int) => name(string)] for every seat present in the match.
function MatchSeatDisplayNames(array $match) {
    $names = [];
    $conn = null;
    $tried = false;
    foreach (array_keys($match['players'] ?? []) as $seatKey) {
        $seat = intval($seatKey);
        if ($seat < 1) continue;
        $names[$seat] = 'Player ' . $seat;
        $userId = intval($match['players'][$seatKey]['userId'] ?? 0);
        if ($userId <= 0) continue;                      // not logged in → keep the seat name
        if (!$tried) {                                   // connect at most once, and only if needed
            $tried = true;
            $dbPath = __DIR__ . '/../../Database/ConnectionManager.php';
            $mhPath = __DIR__ . '/../MatchHistory.php';
            if (is_file($dbPath) && is_file($mhPath)) {
                require_once $dbPath;
                require_once $mhPath;
                if (function_exists('GetLocalMySQLConnection')) $conn = GetLocalMySQLConnection();
            }
        }
        if (!$conn || !function_exists('MatchHistoryUsername')) continue;
        $uid = MatchHistoryUsername($conn, $userId);
        if ($uid !== null && $uid !== '') $names[$seat] = $uid;
    }
    if ($conn) $conn->close();
    return $names;
}

// Every seat that reached winsNeeded. Normally a single seat (MatchWinner returns the same one), but
// a multiplayer format can end in a shared victory — see MatchGetGameWinners / SWU CR §12.7.3.
function MatchWinners(array $match) {
    $need = intval($match['winsNeeded'] ?? 1);
    $seats = [];
    foreach (($match['wins'] ?? []) as $seat => $w) {
        if (intval($w) >= $need) $seats[] = intval($seat);
    }
    sort($seats);
    return $seats;
}

// Idempotent by gameName: recording the same game twice does not double-count.
// Games may be pre-seeded as shells (winner => null) when spawned; fill the shell
// rather than appending a duplicate, and skip games already decided.
// $winnerSeats (optional) is the full winner SET for a shared victory — see MatchGetGameWinners.
// Omitted / empty means "just $winnerSeat", so every existing 2-player caller behaves identically.
function MatchRecordGameResult($rootName, $matchId, $gameName, $winnerSeat, $roundNumber = null, $winnerSeats = null) {
    return MatchWithLock($rootName, $matchId, function (&$match) use ($rootName, $gameName, $winnerSeat, $roundNumber, $winnerSeats) {
        $seat = intval($winnerSeat);
        $seats = is_array($winnerSeats) ? array_values(array_unique(array_map('intval', $winnerSeats))) : [];
        if (empty($seats)) $seats = [$seat];
        sort($seats);
        $idx = null;
        foreach ($match['games'] as $i => $g) {
            if (($g['gameName'] ?? null) === strval($gameName)) {
                if (($g['winner'] ?? null) !== null) return; // already decided — idempotent
                $idx = $i; break;
            }
        }
        if ($idx === null) {
            $match['games'][] = ['gameName' => strval($gameName), 'gameNumber' => count($match['games']) + 1, 'winner' => null];
            $idx = count($match['games']) - 1;
        }
        if (isset($match['players'][strval($seat)])) {
            $match['games'][$idx]['winner'] = $seat;
            // 'winners' is written alongside (not instead of) the scalar so readers that only know
            // about 'winner' — stats, history, the Bo3 sideboard flow — are untouched.
            $match['games'][$idx]['winners'] = $seats;
            foreach ($seats as $s) {
                if (!isset($match['players'][strval($s)])) continue;
                $match['wins'][strval($s)] = intval($match['wins'][strval($s)] ?? 0) + 1;
            }
            // Reached only on the null→seat transition (idempotent early-return above) → count once.
            // Skip a game that ended before Round 2 (early concede) — same rule as the stats submit.
            // ($roundNumber null = caller didn't supply it → record, preserving non-hook callers.)
            // Direct callable dispatch (not MatchHook) to preserve the recordDeckStats &$match by-ref contract.
            if ($roundNumber === null || intval($roundNumber) >= 2) {
                $rds = $GLOBALS['MATCH_HOOKS'][$rootName]['recordDeckStats'] ?? null;
                if (is_callable($rds)) { $rds($match, $seat); }
            }
        }
        if (MatchIsOver($match)) {
            $match['state']    = 'complete';
            $match['winner']   = MatchWinner($match);
            $match['winners']  = MatchWinners($match);
        }
    });
}

// ── Sideboarding (Bo3 between-games) ──────────────────────────────────────────
if (!defined('MATCH_SIDEBOARD_SECONDS')) define('MATCH_SIDEBOARD_SECONDS', 180);
// A spawn-claim older than this (seconds) is considered stale (spawner crashed) and may be retaken,
// so a mid-spawn failure can never strand both players in sideboarding forever.
if (!defined('MATCH_SPAWN_CLAIM_TTL')) define('MATCH_SPAWN_CLAIM_TTL', 15);

function MatchBeginSideboarding($rootName, $matchId, $loserSeat) {
    $seconds = intval(MatchConfig($rootName, 'sideboardSeconds', MATCH_SIDEBOARD_SECONDS));
    MatchWithLock($rootName, $matchId, function (&$m) use ($loserSeat, $seconds) {
        $m['state'] = 'sideboarding';
        $m['pendingFirstPlayer'] = ($loserSeat === 1 || $loserSeat === 2) ? $loserSeat : 1;
        $m['sideboard'] = [
            '1' => ['ready' => false, 'deck' => null],
            '2' => ['ready' => false, 'deck' => null],
        ];
        // Private matches have NO forced sideboard timer — leaving the deadline unset makes
        // MatchSideboardTimeoutCheck (which treats an absent deadline as PHP_INT_MAX) never fire, so
        // players take as long as they want. Public/queued matches keep the shared countdown.
        unset($m['sideboardDeadline']);
        if (empty($m['isPrivate'])) {
            $m['sideboardDeadline'] = time() + $seconds;
        }
    });
}
function MatchSideboardSeatReady(array $m, $seat) {
    return !empty($m['sideboard'][strval($seat)]['ready']);
}
function MatchSideboardBothReady(array $m) {
    return MatchSideboardSeatReady($m, 1) && MatchSideboardSeatReady($m, 2);
}
// $resolvedDeck = an already-validated resolved-deck array (caller validates via the validateDeck hook).
function MatchSubmitSideboardDeck($rootName, $matchId, $seat, $resolvedDeck) {
    return MatchWithLock($rootName, $matchId, function (&$m) use ($seat, $resolvedDeck) {
        if (($m['state'] ?? '') !== 'sideboarding') return;
        $s = strval(intval($seat));
        if ($s !== '1' && $s !== '2') return;
        if (!empty($m['sideboard'][$s]['ready'])) return; // first submit wins
        $m['sideboard'][$s] = ['ready' => true, 'deck' => $resolvedDeck];
    });
}

<?php
// GA Match: the top-level unit of play (Bo1 = bestOf 1, Bo3 = bestOf 3) owning a
// sequence of child games. Filesystem-durable; every read-modify-write is flock-guarded.
// Ported from SWUSim/Match.php.
require_once __DIR__ . '/Formats.php';

function GAMatchesDir() {
    $dir = __DIR__ . '/Matches';
    if (!is_dir($dir)) mkdir($dir, 0777, true);
    return $dir;
}

function GAMatchPath($matchId) {
    $matchId = preg_replace('/[^A-Za-z0-9_]/', '', strval($matchId));
    if ($matchId === '') return '';
    return GAMatchesDir() . '/' . $matchId . '/Match.json';
}

// Mirror of GetGameCounter (flock'd) for monotonic match IDs.
function GANextMatchId() {
    $dir = GAMatchesDir();
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

function GAReadMatch($matchId) {
    $path = GAMatchPath($matchId);
    if ($path === '' || !is_file($path)) return null;
    $decoded = json_decode(file_get_contents($path), true);
    return is_array($decoded) ? $decoded : null;
}

function GAWriteMatch(array $match) {
    $path = GAMatchPath($match['matchId'] ?? '');
    if ($path === '') return false;
    $match['updatedAt'] = time();
    return file_put_contents($path, json_encode($match), LOCK_EX) !== false;
}

// Read-modify-write under an exclusive lock. $fn receives the match array by
// reference; whatever it leaves in the array is persisted. Returns the new match.
function GAWithMatchLock($matchId, callable $fn) {
    $dir = dirname(GAMatchPath($matchId));
    if (!is_dir($dir)) mkdir($dir, 0777, true);
    $lockPath = $dir . '/Match.lock';
    $fp = fopen($lockPath, 'c');
    $tries = 0;
    while (!flock($fp, LOCK_EX) && $tries < 30) { usleep(100000); ++$tries; }
    $match = GAReadMatch($matchId);
    if (is_array($match)) {
        $fn($match);
        GAWriteMatch($match);
    }
    flock($fp, LOCK_UN);
    fclose($fp);
    return $match;
}

function GACreateMatch($rootName, $format, $queueType, $players) {
    $qt = GAGetQueueType($queueType);
    $bestOf = $qt ? intval($qt['bestOf']) : 1;
    $winsNeeded = intval(floor($bestOf / 2)) + 1;

    $matchId = GANextMatchId();
    $match = [
        'matchId'           => $matchId,
        'rootName'          => strval($rootName),
        'format'            => strval($format),
        'queueType'         => strval($queueType),
        'bestOf'            => $bestOf,
        'winsNeeded'        => $winsNeeded,
        'players'           => [
            '1' => ['originalDeck' => $players[1]['originalDeck'] ?? [], 'authKey' => strval($players[1]['authKey'] ?? ''),
                    'userId' => $players[1]['userId'] ?? null, 'deckLink' => strval($players[1]['deckLink'] ?? '')],
            '2' => ['originalDeck' => $players[2]['originalDeck'] ?? [], 'authKey' => strval($players[2]['authKey'] ?? ''),
                    'userId' => $players[2]['userId'] ?? null, 'deckLink' => strval($players[2]['deckLink'] ?? '')],
        ],
        'games'             => [],
        'wins'              => ['1' => 0, '2' => 0],
        'currentGameNumber' => 0,
        'state'             => 'in_progress',
        'winner'            => null,
        'createdAt'         => time(),
        'updatedAt'         => time(),
    ];
    GAWriteMatch($match);
    return $matchId;
}

function GAMatchIsOver(array $match) {
    $need = intval($match['winsNeeded'] ?? 1);
    return intval($match['wins']['1'] ?? 0) >= $need || intval($match['wins']['2'] ?? 0) >= $need;
}

function GAMatchWinner(array $match) {
    $need = intval($match['winsNeeded'] ?? 1);
    if (intval($match['wins']['1'] ?? 0) >= $need) return 1;
    if (intval($match['wins']['2'] ?? 0) >= $need) return 2;
    return 0;
}

// Idempotent by gameName: recording the same game twice does not double-count.
// Games may be pre-seeded as shells (winner => null) when spawned; fill the shell
// rather than appending a duplicate, and skip games already decided.
function GARecordGameResult($matchId, $gameName, $winnerSeat, $roundNumber = null) {
    return GAWithMatchLock($matchId, function (&$match) use ($gameName, $winnerSeat) {
        $seat = intval($winnerSeat);
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
        if ($seat === 1 || $seat === 2) {
            $match['games'][$idx]['winner'] = $seat;
            $match['wins'][strval($seat)] = intval($match['wins'][strval($seat)] ?? 0) + 1;
        }
        if (GAMatchIsOver($match)) {
            $match['state']  = 'complete';
            $match['winner'] = GAMatchWinner($match);
        }
    });
}

// ── Sideboarding (Bo3 between-games) ──────────────────────────────────────────
if (!defined('GA_SIDEBOARD_SECONDS')) define('GA_SIDEBOARD_SECONDS', 180);

function GABeginSideboarding($matchId, $loserSeat) {
    GAWithMatchLock($matchId, function (&$m) use ($loserSeat) {
        $m['state'] = 'sideboarding';
        $m['pendingFirstPlayer'] = ($loserSeat === 1 || $loserSeat === 2) ? $loserSeat : 1;
        $m['sideboard'] = [
            '1' => ['ready' => false, 'deck' => null],
            '2' => ['ready' => false, 'deck' => null],
        ];
        $m['sideboardDeadline'] = time() + GA_SIDEBOARD_SECONDS;
    });
}
function GASideboardSeatReady(array $m, $seat) {
    return !empty($m['sideboard'][strval($seat)]['ready']);
}
function GASideboardBothReady(array $m) {
    return GASideboardSeatReady($m, 1) && GASideboardSeatReady($m, 2);
}
// $resolvedDeck = a GrandArchiveResolveDeckInput-shaped array (already validated by the caller).
function GASubmitSideboardDeck($matchId, $seat, $resolvedDeck) {
    return GAWithMatchLock($matchId, function (&$m) use ($seat, $resolvedDeck) {
        if (($m['state'] ?? '') !== 'sideboarding') return;
        $s = strval(intval($seat));
        if ($s !== '1' && $s !== '2') return;
        if (!empty($m['sideboard'][$s]['ready'])) return; // first submit wins
        $m['sideboard'][$s] = ['ready' => true, 'deck' => $resolvedDeck];
    });
}

<?php
// Core/Match/MatchFlow.php — game-agnostic Bo3 match orchestration: create a match
// from a ready lobby, spawn child games, advance the match on game-over, run the
// sideboard/rematch/convert handshakes. Generified from SWUSim/MatchFlow.php:
// prefix stripped, $rootName threaded, game-specific work routed through hooks.
require_once __DIR__ . '/Match.php';
require_once __DIR__ . '/Hooks.php';

// A lobby-player stand-in for spawning child games (mid-match, no real lobby).
class MatchSyntheticPlayer {
    private $seat; private $authKey;
    public function __construct($seat, $authKey) { $this->seat = intval($seat); $this->authKey = strval($authKey); }
    public function getGamePlayerID() { return $this->seat; }
    public function setGamePlayerID($s) { $this->seat = intval($s); }
    public function getAuthKey() { return $this->authKey; }
    public function getDeckLink() { return ''; }            // decks injected via resolvedDecks
    public function getPreconstructedDeck() { return ''; }
}

// ── Per-game pointer files (canonical SWUSim storage model) ───────────────────
function MatchGamesDir($rootName) {
    $rootName = preg_replace('/[^A-Za-z0-9_]/', '', strval($rootName));
    return MatchRepoRoot() . '/' . $rootName . '/Games';
}
function MatchRefPath($rootName, $gameName) {
    $gameName = preg_replace('/[^A-Za-z0-9_]/', '', strval($gameName));
    if ($gameName === '') return '';
    return MatchGamesDir($rootName) . '/' . $gameName . '/MatchRef.json';
}
function MatchWriteRef($rootName, $gameName, $matchId, $gameNumber) {
    $path = MatchRefPath($rootName, $gameName);
    if ($path === '') return false;
    return file_put_contents($path, json_encode(['matchId' => strval($matchId), 'gameNumber' => intval($gameNumber)]), LOCK_EX) !== false;
}
function MatchReadRef($rootName, $gameName) {
    $path = MatchRefPath($rootName, $gameName);
    if ($path === '' || !is_file($path)) return null;
    $d = json_decode(file_get_contents($path), true);
    return is_array($d) ? $d : null;
}
function MatchNextGamePointerPath($rootName, $gameName) {
    $gameName = preg_replace('/[^A-Za-z0-9_]/', '', strval($gameName));
    if ($gameName === '') return '';
    return MatchGamesDir($rootName) . '/' . $gameName . '/NextGame.json';
}
function MatchSideboardPointerPath($rootName, $gameName) {
    $gameName = preg_replace('/[^A-Za-z0-9_]/', '', strval($gameName));
    if ($gameName === '') return '';
    return MatchGamesDir($rootName) . '/' . $gameName . '/Sideboard.json';
}

// Reads the game-over winner from the currently-loaded gamestate (0 if not over).
// Both SWUSim and GA store this in the GAMEOVER_WINNER decision-queue variable.
function MatchGetGameWinner() {
    if (!class_exists('DecisionQueueController')) return 0;
    $w = intval(DecisionQueueController::GetVariable('GAMEOVER_WINNER'));
    return ($w >= 1 && $w <= 4) ? $w : 0;
}

// Resolve the opponent of $userId in the match backing $gameName.
// Returns null if no match/ref, the user isn't a seat, or the game store is unavailable.
function MatchResolveOpponent($rootName, $gameName, $userId) {
    $userId = (int)$userId;
    if ($userId <= 0) return null;
    $ref = MatchReadRef($rootName, $gameName);
    if (!$ref || empty($ref['matchId'])) return null;
    $m = MatchRead($rootName, $ref['matchId']);
    if (!$m || empty($m['players'])) return null;
    $u1 = isset($m['players']['1']['userId']) ? (int)$m['players']['1']['userId'] : 0;
    $u2 = isset($m['players']['2']['userId']) ? (int)$m['players']['2']['userId'] : 0;
    if     ($u1 === $userId) { $mySeat = 1; $oppUserId = $u2; }
    elseif ($u2 === $userId) { $mySeat = 2; $oppUserId = $u1; }
    else return null;
    $winsNeeded = (int)($m['winsNeeded'] ?? 1);
    $seriesOver = ($m['state'] ?? '') === 'complete'
        || (int)($m['wins']['1'] ?? 0) >= $winsNeeded
        || (int)($m['wins']['2'] ?? 0) >= $winsNeeded;
    return [
        'matchId'    => $ref['matchId'],
        'mySeat'     => $mySeat,
        'oppUserId'  => $oppUserId > 0 ? $oppUserId : null,
        'bestOf'     => (int)($m['bestOf'] ?? 1),
        'seriesOver' => $seriesOver,
    ];
}

// True when the two seats of the match backing $gameName have a block between them.
// Delegates the actual block lookup to the optional 'arePlayersBlocked' hook.
function MatchAreGamePlayersBlocked($rootName, $gameName) {
    $ref = MatchReadRef($rootName, $gameName);
    if (!$ref || empty($ref['matchId'])) return false;
    $m = MatchRead($rootName, $ref['matchId']);
    if (!$m || empty($m['players'])) return false;
    $u1 = isset($m['players']['1']['userId']) ? (int)$m['players']['1']['userId'] : 0;
    $u2 = isset($m['players']['2']['userId']) ? (int)$m['players']['2']['userId'] : 0;
    if ($u1 <= 0 || $u2 <= 0) return false;
    return (bool)MatchHook($rootName, 'arePlayersBlocked', $u1, $u2);
}

// Create a match from a ready 2-player lobby, spawn game 1, return matchId (or null).
// The game-specific deck resolution/validation + per-seat metadata lives in the
// 'resolveLobbyDecks' hook, which returns [1 => wrapper, 2 => wrapper] (or null on
// failure, having set its own flash message). Each wrapper carries at least
// 'originalDeck' + 'authKey'; optionally userId/deckIdentity/deckLink/cosmetics.
function MatchCreateFromLobby($rootName, $lobby) {
    $format    = $lobby->format ?? '';
    $queueType = $lobby->queueType ?? 'bo1';

    $resolved = MatchHook($rootName, 'resolveLobbyDecks', $lobby);
    if (!is_array($resolved) || count($resolved) < 2) return null; // hook set flash; need at least 2 seats
    foreach ($resolved as $seat => $wrapper) {
        if (empty($wrapper)) return null; // every present seat must have resolved
    }

    $matchId = MatchCreate($rootName, $format, $queueType, $resolved);

    // Spawn game 1 from the real lobby, injecting the already-resolved decks. matchId/gameNumber are
    // offered so a sim's setupGame MAY stamp them into the gamestate for its own client match-detection
    // (canonical cross-game state is still the pointer files written below).
    $resolvedDecksOnly = [];
    foreach ($resolved as $seat => $wrapper) { $resolvedDecksOnly[$seat] = $wrapper['originalDeck'] ?? []; }
    $gameName = MatchHook($rootName, 'setupGame', $lobby, [
        'resolvedDecks' => $resolvedDecksOnly,
        'matchId' => $matchId, 'gameNumber' => 1]);
    $lobby->gameName = $gameName; // some setupGame hooks set this themselves; ensure it for all

    MatchWithLock($rootName, $matchId, function (&$m) use ($gameName) {
        $m['currentGameNumber'] = 1;
        $m['games'][] = ['gameName' => strval($gameName), 'gameNumber' => 1, 'winner' => null];
    });
    MatchWriteRef($rootName, $gameName, $matchId, 1);
    return $matchId;
}

// Spawn the next child game with explicit per-seat resolved decks (sideboarded).
// Returns the new gameName. Writes its MatchRef and a NextGame pointer on $priorGame.
function MatchSpawnNextGameWithDecks($rootName, $matchId, $firstPlayer, $priorGame, $resolvedDecks) {
    $m = MatchRead($rootName, $matchId);
    if (!is_array($m)) return '';
    $players = [
        new MatchSyntheticPlayer(1, $m['players']['1']['authKey'] ?? ''),
        new MatchSyntheticPlayer(2, $m['players']['2']['authKey'] ?? ''),
    ];
    $lobby = new stdClass();
    $lobby->isPrivate = false;
    $lobby->players = $players;

    $nextNumber = count($m['games']) + 1;
    $next = MatchHook($rootName, 'setupGame', $lobby, [
        'forcedFirstPlayer' => ($firstPlayer === 1 || $firstPlayer === 2) ? $firstPlayer : null,
        'resolvedDecks'     => $resolvedDecks,
        'matchId'           => $matchId, 'gameNumber' => $nextNumber,
    ]);

    MatchWithLock($rootName, $matchId, function (&$mm) use ($next, $nextNumber) {
        $mm['currentGameNumber'] = $nextNumber;
        $mm['games'][] = ['gameName' => strval($next), 'gameNumber' => $nextNumber, 'winner' => null];
    });
    MatchWriteRef($rootName, $next, $matchId, $nextNumber);

    if (function_exists('RemoveActiveGame') && $priorGame !== '') RemoveActiveGame($rootName, $priorGame);

    if ($priorGame !== '') {
        $ptr = MatchNextGamePointerPath($rootName, $priorGame);
        if ($ptr !== '' && is_dir(dirname($ptr))) file_put_contents($ptr, json_encode(['nextGameName' => strval($next)]), LOCK_EX);
    }
    return $next;
}

// Wrapper: replay each seat's originalDeck (used by direct/legacy callers + tests).
function MatchSpawnNextGame($rootName, $matchId, $loserSeat, $priorGame) {
    $m = MatchRead($rootName, $matchId);
    if (!is_array($m)) return '';
    $decks = [1 => $m['players']['1']['originalDeck'] ?? [], 2 => $m['players']['2']['originalDeck'] ?? []];
    return MatchSpawnNextGameWithDecks($rootName, $matchId, $loserSeat, $priorGame, $decks);
}

// No-stall: if the sideboard deadline passed, auto-submit any not-ready seat's prior deck and spawn.
function MatchSideboardTimeoutCheck($rootName, $matchId) {
    $m = MatchRead($rootName, $matchId);
    if (!is_array($m) || ($m['state'] ?? '') !== 'sideboarding') return;
    if (time() < intval($m['sideboardDeadline'] ?? PHP_INT_MAX)) return;
    foreach ([1,2] as $seat) {
        if (MatchSideboardSeatReady($m, $seat)) continue;
        $prior = $m['players'][strval($seat)]['originalDeck'] ?? []; // safe known-legal fallback
        MatchSubmitSideboardDeck($rootName, $matchId, $seat, $prior);
    }
    MatchMaybeSpawnAfterSideboard($rootName, $matchId);
}

// Spawn the next game once both sideboards are in (or a timeout forced them). Idempotent.
// Concurrency-safe: both seats' submit/poll may call this at once — an atomic spawn-claim ensures
// exactly ONE caller spawns the next game (else two games would spawn and the players desync). The
// loser of the claim returns '' and its waiting poll picks up the spawned game via the in_progress
// recovery branch in SubmitSideboard. A stale claim (spawner crashed) is retaken after the TTL.
function MatchMaybeSpawnAfterSideboard($rootName, $matchId) {
    $m = MatchRead($rootName, $matchId);
    if (!is_array($m) || ($m['state'] ?? '') !== 'sideboarding') return '';
    if (!MatchSideboardBothReady($m)) return '';

    $now = time();
    $claimed = false;
    $m = MatchWithLock($rootName, $matchId, function (&$mm) use (&$claimed, $now) {
        if (($mm['state'] ?? '') !== 'sideboarding') return;
        if (empty($mm['sideboard']['1']['ready']) || empty($mm['sideboard']['2']['ready'])) return;
        $prev = intval($mm['spawnClaimedAt'] ?? 0);
        if ($prev !== 0 && ($now - $prev) <= MATCH_SPAWN_CLAIM_TTL) return; // another caller is spawning
        $mm['spawnClaimedAt'] = $now;
        $claimed = true;
    });
    if (!$claimed) return '';

    $first = $m['pendingFirstPlayer'] ?? 1;
    $prior = '';
    if (!empty($m['games'])) { $prior = $m['games'][count($m['games']) - 1]['gameName']; }

    $decks = [1 => $m['sideboard']['1']['deck'], 2 => $m['sideboard']['2']['deck']];
    $next = MatchSpawnNextGameWithDecks($rootName, $matchId, $first, $prior, $decks);

    MatchWithLock($rootName, $matchId, function (&$mm) {
        $mm['state'] = 'in_progress';
        unset($mm['sideboard'], $mm['sideboardDeadline'], $mm['pendingFirstPlayer'], $mm['spawnClaimedAt']);
    });
    return $next;
}

// Rematch: create a NEW match between the same players (mutual agreement). Each seat records its
// chosen bestOf (1|3) + sideboard preference; both must agree on bestOf to pair.
function MatchRequestRematch($rootName, $oldMatchId, $seat, $bestOf, $sideboard) {
    return MatchWithLock($rootName, $oldMatchId, function (&$m) use ($seat, $bestOf, $sideboard) {
        if (($m['state'] ?? '') !== 'complete') return;
        if ($seat !== 1 && $seat !== 2) return;
        if (!isset($m['rematchRequests'])) $m['rematchRequests'] = [];
        $m['rematchRequests'][strval($seat)] = ['bestOf' => (intval($bestOf) === 3 ? 3 : 1), 'sideboard' => (bool)$sideboard];
    });
}
// On both-accept (matching bestOf), create a new match with the same decks/authkeys; returns new matchId.
function MatchAcceptRematch($rootName, $oldMatchId) {
    $m = MatchRead($rootName, $oldMatchId);
    if (!is_array($m) || ($m['state'] ?? '') !== 'complete') return null;
    $r1 = $m['rematchRequests']['1'] ?? null;
    $r2 = $m['rematchRequests']['2'] ?? null;
    if (!$r1 || !$r2) return null;
    if (intval($r1['bestOf']) !== intval($r2['bestOf'])) return null; // must agree on series length
    $bestOf = intval($r1['bestOf']);
    $sideboard = !empty($r1['sideboard']) || !empty($r2['sideboard']);
    $queueType = ($bestOf === 3) ? 'bo3' : 'bo1';

    $newId = MatchCreate($rootName, strval($m['format'] ?? ''), $queueType, [
        1 => ['originalDeck' => $m['players']['1']['originalDeck'] ?? [], 'authKey' => $m['players']['1']['authKey'] ?? ''],
        2 => ['originalDeck' => $m['players']['2']['originalDeck'] ?? [], 'authKey' => $m['players']['2']['authKey'] ?? ''],
    ]);
    MatchWithLock($rootName, $oldMatchId, function (&$mm) { unset($mm['rematchRequests']); }); // fire once

    $oldGame = !empty($m['games']) ? $m['games'][count($m['games'])-1]['gameName'] : '';
    $lastWinner = !empty($m['games']) ? intval($m['games'][count($m['games'])-1]['winner'] ?? 1) : 1;
    $first = ($lastWinner === 1) ? 2 : 1; // loser of the prior game goes first

    if ($sideboard) {
        MatchBeginSideboarding($rootName, $newId, $first);
        if ($oldGame !== '') { $p = MatchSideboardPointerPath($rootName, $oldGame); if ($p!=='' && is_dir(dirname($p))) file_put_contents($p, json_encode(['matchId'=>$newId]), LOCK_EX); }
    } else {
        $decks = [1 => $m['players']['1']['originalDeck'] ?? [], 2 => $m['players']['2']['originalDeck'] ?? []];
        MatchSpawnNextGameWithDecks($rootName, $newId, $first, $oldGame, $decks); // appends as game 1 + writes NextGame.json on $oldGame
    }
    return $newId;
}

// Convert a finished Bo1 into a Bo3 (mutual agreement). Records a per-seat request.
function MatchRequestConvertToBo3($rootName, $matchId, $seat) {
    return MatchWithLock($rootName, $matchId, function (&$m) use ($seat) {
        if (($m['state'] ?? '') !== 'complete' || intval($m['bestOf'] ?? 1) !== 1) return;
        if (!isset($m['convertRequests'])) $m['convertRequests'] = [];
        if ($seat === 1 || $seat === 2) $m['convertRequests'][strval($seat)] = true;
    });
}
// When BOTH seats have requested, promote to Bo3 (keeping game-1 result) and re-enter sideboarding.
function MatchAcceptConvertToBo3($rootName, $matchId) {
    $m = MatchRead($rootName, $matchId);
    if (!is_array($m) || intval($m['bestOf'] ?? 1) !== 1) return null;
    if (empty($m['convertRequests']['1']) || empty($m['convertRequests']['2'])) return null;
    MatchWithLock($rootName, $matchId, function (&$mm) {
        $mm['bestOf'] = 3; $mm['winsNeeded'] = 2; $mm['state'] = 'in_progress';
        unset($mm['convertRequests'], $mm['winner']);
    });
    // Game 1's win stays recorded (1-0). Re-enter sideboarding for game 2; loser of game 1 goes first.
    $m = MatchRead($rootName, $matchId);
    $g1winner = intval($m['games'][0]['winner'] ?? 1);
    MatchBeginSideboarding($rootName, $matchId, ($g1winner === 1) ? 2 : 1);
    $prior = $m['games'][count($m['games']) - 1]['gameName'] ?? '';
    if ($prior !== '') { $p = MatchSideboardPointerPath($rootName, $prior); if ($p !== '' && is_dir(dirname($p))) file_put_contents($p, json_encode(['matchId' => $matchId]), LOCK_EX); }
    return $matchId;
}

// Disconnect policy: an inactive player in a match game forfeits THAT GAME (not the whole match).
// Declaring the opponent the game winner lets the normal hook advance/sideboard the match.
function MatchInactivityForfeit($rootName, $gameName, $inactiveSeat) {
    if ($inactiveSeat !== 1 && $inactiveSeat !== 2) return;
    MatchHook($rootName, 'declareGameWinner', ($inactiveSeat === 1) ? 2 : 1);
}

// Orphaned-match reaper: delete Matches/ dirs whose Match.json is complete (or abandoned) and older
// than $maxAgeSeconds. Cheap GC — call opportunistically, never on the hot path. Logs the count.
function MatchReapStale($rootName, $maxAgeSeconds = 86400, $nowTs = null) {
    $now = ($nowTs === null) ? time() : intval($nowTs);
    $dir = MatchesDir($rootName);
    $reaped = 0;
    foreach (glob($dir . '/M*', GLOB_ONLYDIR) as $mDir) {
        $f = $mDir . '/Match.json';
        if (!is_file($f)) continue;
        $m = json_decode(file_get_contents($f), true);
        if (!is_array($m)) continue;
        $state = $m['state'] ?? '';
        $age = $now - intval($m['updatedAt'] ?? 0);
        if ($age < $maxAgeSeconds) continue;
        if ($state !== 'complete' && $state !== 'abandoned') continue;
        array_map('unlink', glob($mDir . '/*') ?: []);
        @rmdir($mDir);
        $reaped++;
    }
    if ($reaped > 0) error_log("MatchReapStale($rootName): reaped $reaped stale match dir(s).");
    return $reaped;
}

// Concede the whole match: award the opponent the wins needed to clinch. Idempotent.
function MatchConcede($rootName, $matchId, $concedingSeat) {
    return MatchWithLock($rootName, $matchId, function (&$m) use ($rootName, $concedingSeat) {
        if (($m['state'] ?? '') === 'complete') return;       // idempotent
        $opp = ($concedingSeat === 1) ? 2 : 1;
        // Count the in-progress game (if any) as a decisive loss for the conceding seat.
        foreach ($m['games'] as $i => $g) {
            if (($g['winner'] ?? null) === null) {
                $m['games'][$i]['winner'] = $opp;
                $rds = $GLOBALS['MATCH_HOOKS'][$rootName]['recordDeckStats'] ?? null;
                if (is_callable($rds)) { $rds($m, $opp); }
                break;
            }
        }
        $m['wins'][strval($opp)] = intval($m['winsNeeded'] ?? 1); // clinch
        $m['state'] = 'complete';
        $m['winner'] = $opp;
        unset($m['sideboard'], $m['sideboardDeadline'], $m['pendingFirstPlayer']);
    });
}

// Post-action: if this game belongs to a match and just ended, advance the match.
function MatchAfterActionHook($rootName, $gameName) {
    $ref = MatchReadRef($rootName, $gameName);
    if ($ref === null) return;                 // not a match game (goldfish/legacy)
    if (MatchGetGameWinner() === 0) return;     // game not over

    $winner = MatchGetGameWinner();
    // Capture per-game telemetry/detail while this game's gamestate is loaded (for stats).
    $detail = MatchHook($rootName, 'captureGameDetail');
    $roundNumber = is_array($detail) ? intval($detail['turns'] ?? 0) : null; // gate early-concede stats (round < 2)
    MatchRecordGameResult($rootName, $ref['matchId'], $gameName, $winner, $roundNumber);
    if ($detail !== null) {
        MatchWithLock($rootName, $ref['matchId'], function (&$mm) use ($gameName, $detail) {
            foreach ($mm['games'] as &$g) { if (($g['gameName'] ?? '') === strval($gameName)) { $g['detail'] = $detail; break; } }
        });
    }
    $m = MatchRead($rootName, $ref['matchId']);
    if (!is_array($m)) return;

    if (MatchIsOver($m)) {
        // Match complete — the flashMatchResult hook carries the series result to the client.
        MatchHook($rootName, 'flashMatchResult', $gameName, MatchWinner($m), $m);
        if (function_exists('RemoveActiveGame')) RemoveActiveGame($rootName, $gameName);
        MatchHook($rootName, 'submitResults', $ref['matchId']);
        return;
    }
    // Not over — begin sideboarding; both clients move to the sideboard screen. 2-seat Bo3 only:
    // Twin Suns (and any >2-seat match) is Bo1-only, so MatchIsOver is always true after game 1
    // for those and this branch is unreachable in practice; the guard makes that crash-proof.
    if (count($m['players'] ?? []) > 2) return;
    $loser = ($winner === 1) ? 2 : 1;
    MatchBeginSideboarding($rootName, $ref['matchId'], $loser);
    $ptr = MatchSideboardPointerPath($rootName, $gameName);
    if ($ptr !== '' && is_dir(dirname($ptr))) file_put_contents($ptr, json_encode(['matchId' => $ref['matchId']]), LOCK_EX);
}

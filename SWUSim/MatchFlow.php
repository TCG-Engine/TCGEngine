<?php
// Bo3 match orchestration: create a match from a ready lobby, spawn child games,
// and advance the match on game-over. Defines SWUSetupGame via CreateGame include
// (no ambient $lobby here, so its auto-run guard does not fire).
include_once __DIR__ . '/Formats.php';
include_once __DIR__ . '/Match.php';
include_once __DIR__ . '/CreateGame.php';      // defines SWUSetupGame / LoadPlayerDeck
include_once __DIR__ . '/Custom/DeckImport.php'; // SWUResolveDeckInput / SWUCheckFormat
include_once __DIR__ . '/StatsSubmit.php';       // telemetry → SubmitGameResult on final completion
require_once __DIR__ . '/../Database/functions.inc.php'; // SWUResolveSeatCosmetics (Feature C snapshot)

// A lobby-player stand-in for spawning child games (mid-match, no real lobby).
class SWUMatchSyntheticPlayer {
    private $seat; private $authKey;
    public function __construct($seat, $authKey) { $this->seat = intval($seat); $this->authKey = strval($authKey); }
    public function getGamePlayerID() { return $this->seat; }
    public function setGamePlayerID($s) { $this->seat = intval($s); }
    public function getAuthKey() { return $this->authKey; }
    public function getDeckLink() { return ''; }            // decks injected via resolvedDecks
    public function getPreconstructedDeck() { return ''; }
}

function SWUMatchRefPath($gameName) {
    $gameName = preg_replace('/[^A-Za-z0-9_]/', '', strval($gameName));
    if ($gameName === '') return '';
    return __DIR__ . '/Games/' . $gameName . '/MatchRef.json';
}
function SWUWriteMatchRef($gameName, $matchId, $gameNumber) {
    $path = SWUMatchRefPath($gameName);
    if ($path === '') return false;
    return file_put_contents($path, json_encode(['matchId' => strval($matchId), 'gameNumber' => intval($gameNumber)]), LOCK_EX) !== false;
}
function SWUReadMatchRef($gameName) {
    $path = SWUMatchRefPath($gameName);
    if ($path === '' || !is_file($path)) return null;
    $d = json_decode(file_get_contents($path), true);
    return is_array($d) ? $d : null;
}

// Create a match from a ready 2-player lobby, spawn game 1, return matchId (or null).
function SWUCreateMatchFromLobby($lobby) {
    $format    = $lobby->format ?? 'premier';
    $queueType = $lobby->queueType ?? 'bo1';

    // Resolve + validate each seat's deck against the format (match-creation is the authority).
    $resolved = [];
    $seat = 1;
    foreach ($lobby->players as $player) {
        $r = SWUResolveDeckInput($player->getDeckLink());
        if (empty($r['success'])) { SetFlashMessage("Deck error for player $seat: " . ($r['message'] ?? '')); return null; }
        $errs = SWUCheckFormat($format, $r['leader'], $r['base'], $r['mainDeck'], $r['sideboard']);
        if (!empty($errs)) { SetFlashMessage("Deck illegal for player $seat ($format): " . implode('; ', array_slice($errs, 0, 3))); return null; }
        $resolved[$seat] = $r;
        ++$seat;
    }

    // Per-seat match auth keys = the lobby players' keys (carried into every child game).
    // Also capture userId + a stable deck identity so the match can attribute per-deck W/L.
    $authKeys = []; $userIds = []; $deckIdentities = [];
    $seat = 1;
    foreach ($lobby->players as $player) {
        $authKeys[$seat]       = $player->getAuthKey();
        $userIds[$seat]        = method_exists($player, 'getUserId') ? $player->getUserId() : null;
        $deckIdentities[$seat] = SWUComputeDeckIdentity($player->getDeckLink());
        ++$seat;
    }

    $matchId = SWUCreateMatch('SWUSim', $format, $queueType, [
        1 => ['originalDeck' => $resolved[1], 'authKey' => $authKeys[1], 'userId' => $userIds[1], 'deckIdentity' => $deckIdentities[1],
              'cosmetics' => SWUResolveSeatCosmetics($userIds[1])],
        2 => ['originalDeck' => $resolved[2], 'authKey' => $authKeys[2], 'userId' => $userIds[2], 'deckIdentity' => $deckIdentities[2],
              'cosmetics' => SWUResolveSeatCosmetics($userIds[2])],
    ]);

    // Spawn game 1 from the real lobby (its AuthKeys.json gets the match auth keys for free),
    // injecting the already-resolved decks so we don't re-resolve/refetch.
    $gameName = SWUSetupGame($lobby, ['resolvedDecks' => [1 => $resolved[1], 2 => $resolved[2]]]);

    SWUWithMatchLock($matchId, function (&$m) use ($gameName) {
        $m['currentGameNumber'] = 1;
        // record the spawned game shell (winner filled in on game-over)
        $m['games'][] = ['gameName' => strval($gameName), 'gameNumber' => 1, 'winner' => null];
    });
    SWUWriteMatchRef($gameName, $matchId, 1);
    return $matchId;
}

function SWUNextGamePointerPath($gameName) {
    $gameName = preg_replace('/[^A-Za-z0-9_]/', '', strval($gameName));
    if ($gameName === '') return '';
    return __DIR__ . '/Games/' . $gameName . '/NextGame.json';
}

function SWUSideboardPointerPath($gameName) {
    $gameName = preg_replace('/[^A-Za-z0-9_]/', '', strval($gameName));
    if ($gameName === '') return '';
    return __DIR__ . '/Games/' . $gameName . '/Sideboard.json';
}

// Spawn the next child game with explicit per-seat resolved decks (C: sideboarded).
// Returns the new gameName. Writes its MatchRef and a NextGame pointer on $priorGame.
function SWUSpawnNextMatchGameWithDecks($matchId, $firstPlayer, $priorGame, $resolvedDecks) {
    $m = SWUReadMatch($matchId);
    if (!is_array($m)) return '';
    $players = [
        new SWUMatchSyntheticPlayer(1, $m['players']['1']['authKey'] ?? ''),
        new SWUMatchSyntheticPlayer(2, $m['players']['2']['authKey'] ?? ''),
    ];
    $lobby = new stdClass();
    $lobby->isPrivate = false;
    $lobby->players = $players;

    $next = SWUSetupGame($lobby, [
        'forcedFirstPlayer' => ($firstPlayer === 1 || $firstPlayer === 2) ? $firstPlayer : null,
        'resolvedDecks'     => $resolvedDecks,
    ]);

    $nextNumber = count($m['games']) + 1;
    SWUWithMatchLock($matchId, function (&$mm) use ($next, $nextNumber) {
        $mm['currentGameNumber'] = $nextNumber;
        $mm['games'][] = ['gameName' => strval($next), 'gameNumber' => $nextNumber, 'winner' => null];
    });
    SWUWriteMatchRef($next, $matchId, $nextNumber);

    if (function_exists('RemoveActiveGame') && $priorGame !== '') RemoveActiveGame('SWUSim', $priorGame);

    if ($priorGame !== '') {
        $ptr = SWUNextGamePointerPath($priorGame);
        if ($ptr !== '') file_put_contents($ptr, json_encode(['nextGameName' => strval($next)]), LOCK_EX);
    }
    return $next;
}

// B2-compatible wrapper: replay each seat's originalDeck (used by direct/legacy callers + tests).
function SWUSpawnNextMatchGame($matchId, $loserSeat, $priorGame) {
    $m = SWUReadMatch($matchId);
    if (!is_array($m)) return '';
    $decks = [1 => $m['players']['1']['originalDeck'] ?? [], 2 => $m['players']['2']['originalDeck'] ?? []];
    return SWUSpawnNextMatchGameWithDecks($matchId, $loserSeat, $priorGame, $decks);
}

// No-stall: if the sideboard deadline passed, auto-submit any not-ready seat's prior deck and spawn.
function SWUSideboardTimeoutCheck($matchId) {
    $m = SWUReadMatch($matchId);
    if (!is_array($m) || ($m['state'] ?? '') !== 'sideboarding') return;
    if (time() < intval($m['sideboardDeadline'] ?? PHP_INT_MAX)) return;
    foreach ([1,2] as $seat) {
        if (SWUSideboardSeatReady($m, $seat)) continue;
        $prior = $m['players'][strval($seat)]['originalDeck'] ?? []; // safe known-legal fallback
        SWUSubmitSideboardDeck($matchId, $seat, $prior);
    }
    SWUMaybeSpawnAfterSideboard($matchId);
}

// Spawn the next game once both sideboards are in (or a timeout forced them). Idempotent.
function SWUMaybeSpawnAfterSideboard($matchId) {
    $m = SWUReadMatch($matchId);
    if (!is_array($m) || ($m['state'] ?? '') !== 'sideboarding') return '';
    if (!SWUSideboardBothReady($m)) return '';

    $first = $m['pendingFirstPlayer'] ?? 1;
    $prior = '';
    if (!empty($m['games'])) { $prior = $m['games'][count($m['games']) - 1]['gameName']; }

    $decks = [1 => $m['sideboard']['1']['deck'], 2 => $m['sideboard']['2']['deck']];
    $next = SWUSpawnNextMatchGameWithDecks($matchId, $first, $prior, $decks);

    SWUWithMatchLock($matchId, function (&$mm) {
        $mm['state'] = 'in_progress';
        unset($mm['sideboard'], $mm['sideboardDeadline'], $mm['pendingFirstPlayer']);
    });
    return $next;
}

// Rematch: create a NEW match between the same players (mutual agreement). Each seat records its
// chosen bestOf (1|3) + sideboard preference; both must agree on bestOf to pair.
function SWURequestRematch($oldMatchId, $seat, $bestOf, $sideboard) {
    return SWUWithMatchLock($oldMatchId, function (&$m) use ($seat, $bestOf, $sideboard) {
        if (($m['state'] ?? '') !== 'complete') return;
        if ($seat !== 1 && $seat !== 2) return;
        if (!isset($m['rematchRequests'])) $m['rematchRequests'] = [];
        $m['rematchRequests'][strval($seat)] = ['bestOf' => (intval($bestOf) === 3 ? 3 : 1), 'sideboard' => (bool)$sideboard];
    });
}
// On both-accept (matching bestOf), create a new match with the same decks/authkeys; returns new matchId.
function SWUAcceptRematch($oldMatchId) {
    $m = SWUReadMatch($oldMatchId);
    if (!is_array($m) || ($m['state'] ?? '') !== 'complete') return null;
    $r1 = $m['rematchRequests']['1'] ?? null;
    $r2 = $m['rematchRequests']['2'] ?? null;
    if (!$r1 || !$r2) return null;
    if (intval($r1['bestOf']) !== intval($r2['bestOf'])) return null; // must agree on series length
    $bestOf = intval($r1['bestOf']);
    $sideboard = !empty($r1['sideboard']) || !empty($r2['sideboard']);
    $queueType = ($bestOf === 3) ? 'bo3' : 'bo1';

    $newId = SWUCreateMatch('SWUSim', strval($m['format'] ?? 'premier'), $queueType, [
        1 => ['originalDeck' => $m['players']['1']['originalDeck'] ?? [], 'authKey' => $m['players']['1']['authKey'] ?? ''],
        2 => ['originalDeck' => $m['players']['2']['originalDeck'] ?? [], 'authKey' => $m['players']['2']['authKey'] ?? ''],
    ]);
    SWUWithMatchLock($oldMatchId, function (&$mm) { unset($mm['rematchRequests']); }); // fire once

    $oldGame = !empty($m['games']) ? $m['games'][count($m['games'])-1]['gameName'] : '';
    $lastWinner = !empty($m['games']) ? intval($m['games'][count($m['games'])-1]['winner'] ?? 1) : 1;
    $first = ($lastWinner === 1) ? 2 : 1; // loser of the prior game goes first

    if ($sideboard) {
        SWUBeginSideboarding($newId, $first);
        if ($oldGame !== '') { $p = SWUSideboardPointerPath($oldGame); if ($p!=='') file_put_contents($p, json_encode(['matchId'=>$newId]), LOCK_EX); }
    } else {
        $decks = [1 => $m['players']['1']['originalDeck'] ?? [], 2 => $m['players']['2']['originalDeck'] ?? []];
        SWUSpawnNextMatchGameWithDecks($newId, $first, $oldGame, $decks); // appends as game 1 + writes NextGame.json on $oldGame
    }
    return $newId;
}

// Convert a finished Bo1 into a Bo3 (mutual agreement). Records a per-seat request.
function SWURequestConvertToBo3($matchId, $seat) {
    return SWUWithMatchLock($matchId, function (&$m) use ($seat) {
        if (($m['state'] ?? '') !== 'complete' || intval($m['bestOf'] ?? 1) !== 1) return;
        if (!isset($m['convertRequests'])) $m['convertRequests'] = [];
        if ($seat === 1 || $seat === 2) $m['convertRequests'][strval($seat)] = true;
    });
}
// When BOTH seats have requested, promote to Bo3 (keeping game-1 result) and re-enter sideboarding.
function SWUAcceptConvertToBo3($matchId) {
    $m = SWUReadMatch($matchId);
    if (!is_array($m) || intval($m['bestOf'] ?? 1) !== 1) return null;
    if (empty($m['convertRequests']['1']) || empty($m['convertRequests']['2'])) return null;
    SWUWithMatchLock($matchId, function (&$mm) {
        $mm['bestOf'] = 3; $mm['winsNeeded'] = 2; $mm['state'] = 'in_progress';
        unset($mm['convertRequests'], $mm['winner']);
    });
    // Game 1's win stays recorded (1-0). Re-enter sideboarding for game 2; loser of game 1 goes first.
    $m = SWUReadMatch($matchId);
    $g1winner = intval($m['games'][0]['winner'] ?? 1);
    SWUBeginSideboarding($matchId, ($g1winner === 1) ? 2 : 1);
    $prior = $m['games'][count($m['games']) - 1]['gameName'] ?? '';
    if ($prior !== '') { $p = SWUSideboardPointerPath($prior); if ($p !== '') file_put_contents($p, json_encode(['matchId' => $matchId]), LOCK_EX); }
    return $matchId;
}

// Disconnect policy: an inactive player in a match game forfeits THAT GAME (not the whole match).
// Declaring the opponent the game winner lets the normal hook advance/sideboard the match.
function SWUMatchInactivityForfeit($gameName, $inactiveSeat) {
    if ($inactiveSeat !== 1 && $inactiveSeat !== 2) return;
    if (function_exists('SWUDeclareGameWinner')) SWUDeclareGameWinner(($inactiveSeat === 1) ? 2 : 1);
}

// Orphaned-match reaper: delete Matches/ dirs whose Match.json is complete (or abandoned) and older
// than $maxAgeSeconds. Cheap GC — call opportunistically, never on the hot path. Logs the count.
function SWUReapStaleMatches($maxAgeSeconds = 86400, $nowTs = null) {
    $now = ($nowTs === null) ? time() : intval($nowTs);
    $dir = SWUMatchesDir();
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
    if ($reaped > 0) error_log("SWUReapStaleMatches: reaped $reaped stale match dir(s).");
    return $reaped;
}

// Concede the whole match: award the opponent the wins needed to clinch. Idempotent.
function SWUConcedeMatch($matchId, $concedingSeat) {
    return SWUWithMatchLock($matchId, function (&$m) use ($concedingSeat) {
        if (($m['state'] ?? '') === 'complete') return;       // idempotent
        $opp = ($concedingSeat === 1) ? 2 : 1;
        // Count the in-progress game (if any) as a decisive loss for the conceding seat.
        foreach ($m['games'] as $i => $g) {
            if (($g['winner'] ?? null) === null) {
                $m['games'][$i]['winner'] = $opp;
                if (function_exists('SWURecordDeckStatsForGame')) SWURecordDeckStatsForGame($m, $opp);
                break;
            }
        }
        $m['wins'][strval($opp)] = intval($m['winsNeeded'] ?? 1); // clinch
        $m['state'] = 'complete';
        $m['winner'] = $opp;
        unset($m['sideboard'], $m['sideboardDeadline'], $m['pendingFirstPlayer']);
    });
}

// Record per-game deck stats for both seats. Idempotency is the caller's job
// (only call once per game — at the winner-set transition or on concede).
function SWURecordDeckStatsForGame(array &$match, $winnerSeat) {
    $winnerSeat = intval($winnerSeat);
    if ($winnerSeat !== 1 && $winnerSeat !== 2) return;
    require_once __DIR__ . '/../Database/ConnectionManager.php';
    require_once __DIR__ . '/../Database/functions.inc.php';
    require_once __DIR__ . '/Custom/DeckImport.php';
    foreach ([1, 2] as $seat) {
        $p = $match['players'][strval($seat)] ?? null;
        if (!$p) continue;
        $userId = $p['userId'] ?? null;
        $deckId = $p['deckIdentity'] ?? '';
        if ($userId === null || $deckId === '') continue;          // guest or no identity
        $won = ($seat === $winnerSeat);
        $affected = RecordSavedDeckResult($userId, $deckId, $won);
        if ($affected <= 0) continue;                              // deck not saved → skip matchup
        $opp = ($seat === 1) ? '2' : '1';
        $oppDeck = $match['players'][$opp]['originalDeck'] ?? [];
        $oppLeader = (string)($oppDeck['leader'] ?? '');
        $oppBase   = SWUNormalizeBaseForMatchup($oppDeck['base'] ?? '');
        if ($oppLeader !== '' && $oppBase !== '') {
            RecordSavedDeckMatchup($userId, $deckId, $oppLeader, $oppBase, $won);
        }
    }
}

// Post-action: if this SWUSim game belongs to a match and just ended, advance the match.
function SWUAfterActionMatchHook($folderPath, $gameName) {
    if ($folderPath !== 'SWUSim') return;
    $ref = SWUReadMatchRef($gameName);
    if ($ref === null) return;                 // not a match game (goldfish/legacy)
    if (SWUGetGameWinner() === 0) return;       // game not over

    $winner = SWUGetGameWinner();
    // Capture per-game telemetry/detail while this game's gamestate is loaded (for stats).
    $detail = function_exists('SWUCaptureCurrentGameDetail') ? SWUCaptureCurrentGameDetail() : null;
    $m = SWURecordGameResult($ref['matchId'], $gameName, $winner);
    if ($detail !== null) {
        SWUWithMatchLock($ref['matchId'], function (&$mm) use ($gameName, $detail) {
            foreach ($mm['games'] as &$g) { if (($g['gameName'] ?? '') === strval($gameName)) { $g['detail'] = $detail; break; } }
        });
    }
    $m = SWUReadMatch($ref['matchId']);
    if (!is_array($m)) return;

    if (SWUMatchIsOver($m)) {
        // Match complete — flash carries the series result; clients show match-over UX (Task 5).
        SetFlashMessage("MATCHOVER:Player " . SWUMatchWinner($m) . " wins the match " .
            intval($m['wins']['1']) . "-" . intval($m['wins']['2']) . "!");
        if (function_exists('RemoveActiveGame')) RemoveActiveGame('SWUSim', $gameName);
        if (function_exists('SWUSubmitMatchResults')) SWUSubmitMatchResults($ref['matchId']);
        return;
    }
    // Not over — begin sideboarding; both clients move to the sideboard screen (Task 3).
    $loser = ($winner === 1) ? 2 : 1;
    SWUBeginSideboarding($ref['matchId'], $loser);
    $ptr = SWUSideboardPointerPath($gameName);
    if ($ptr !== '') file_put_contents($ptr, json_encode(['matchId' => $ref['matchId']]), LOCK_EX);
}

<?php
// GA Bo3 match orchestration: create a match from a ready lobby, spawn child games, and advance the
// match on game-over. Adapted from SWUSim/MatchFlow.php. GA stores each game's match ref in its own
// gamestate (MatchId/GameNumber DQ variables); cross-game state (sideboarding, next game, winner) is
// derived from the Match object itself (Match.json) — no per-game pointer files.
require_once __DIR__ . '/Match.php';
include_once __DIR__ . '/CreateGame.php';       // defines GASetupGame / LoadResolvedDeck (no ambient $lobby → auto-run guard off)

// A lobby-player stand-in for spawning child games (mid-match, no real lobby).
class GAMatchSyntheticPlayer {
    private $seat; private $authKey;
    public function __construct($seat, $authKey) { $this->seat = intval($seat); $this->authKey = strval($authKey); }
    public function getGamePlayerID() { return $this->seat; }
    public function setGamePlayerID($s) { $this->seat = intval($s); }
    public function getAuthKey() { return $this->authKey; }
    public function getDeckLink() { return ''; }            // decks injected via resolvedDecks
    public function getPreconstructedDeck() { return ''; }
}

// Per-game match ref lives in the game's own gamestate (GA stores its own refs; not ref-files).
function GAWriteGameMatchRef($matchId, $gameNumber) {
    DecisionQueueController::StoreVariable('MatchId', strval($matchId));
    DecisionQueueController::StoreVariable('GameNumber', strval(intval($gameNumber)));
}
function GAReadGameMatchRef() {
    $mid = DecisionQueueController::GetVariable('MatchId');
    if ($mid === null || $mid === '') return null;
    return ['matchId' => strval($mid), 'gameNumber' => intval(DecisionQueueController::GetVariable('GameNumber') ?? 1)];
}

// Reads the game-over winner from the currently-loaded gamestate (0 if not over).
function GAGetGameWinner() {
    $w = DecisionQueueController::GetVariable('GAMEOVER_WINNER');
    return in_array(intval($w), [1, 2], true) ? intval($w) : 0;
}

// Resolve both lobby decks (keyed by seat) for the match record + game load.
function GAResolveLobbyDecks($lobby) {
    $out = []; $seat = 1;
    foreach ($lobby->players as $player) {
        $r = GrandArchiveResolveDeckInput($player->getDeckLink());
        $out[$seat] = !empty($r['success']) ? $r : ['material' => [], 'mainDeck' => [], 'sideboard' => [], 'unresolved' => []];
        ++$seat;
    }
    return $out;
}

// Create a match from a ready 2-player lobby, spawn game 1, return matchId (or null).
function GACreateMatchFromLobby($lobby) {
    $format    = strval($lobby->format ?? 'standard');
    $queueType = strval($lobby->queueType ?? 'bo1');

    $resolved = GAResolveLobbyDecks($lobby);
    if (count($resolved) < 2) { SetFlashMessage('Match needs two decks.'); return null; }

    $authKeys = []; $deckLinks = []; $seat = 1;
    foreach ($lobby->players as $player) {
        $authKeys[$seat]  = $player->getAuthKey();
        $deckLinks[$seat] = $player->getDeckLink();
        ++$seat;
    }

    $matchId = GACreateMatch('GrandArchiveSim', $format, $queueType, [
        1 => ['originalDeck' => $resolved[1], 'authKey' => $authKeys[1], 'userId' => null, 'deckLink' => $deckLinks[1]],
        2 => ['originalDeck' => $resolved[2], 'authKey' => $authKeys[2], 'userId' => null, 'deckLink' => $deckLinks[2]],
    ]);

    // Spawn game 1 from the real lobby, injecting the already-resolved decks.
    $gameName = GASetupGame($lobby, ['resolvedDecks' => [1 => $resolved[1], 2 => $resolved[2]]]);

    GAWithMatchLock($matchId, function (&$m) use ($gameName) {
        $m['currentGameNumber'] = 1;
        $m['games'][] = ['gameName' => strval($gameName), 'gameNumber' => 1, 'winner' => null];
    });
    // Stamp the ref into game 1's gamestate (currently loaded after GASetupGame) and persist.
    GAWriteGameMatchRef($matchId, 1);
    WriteGamestate(__DIR__ . '/');
    $lobby->gameName = $gameName;
    return $matchId;
}

// Spawn the next child game with explicit per-seat resolved (sideboarded) decks. Loser goes first.
// Returns the new gameName; appends it to the match and flips the match back to in_progress.
function GASpawnNextMatchGameWithDecks($matchId, $firstPlayer, $priorGame, $resolvedDecks) {
    $m = GAReadMatch($matchId);
    if (!is_array($m)) return '';
    $lobby = new stdClass();
    $lobby->rootName = 'GrandArchiveSim';
    $lobby->format = $m['format']; $lobby->queueType = $m['queueType'];
    $lobby->goldfishPlayers = [];
    $lobby->players = [
        new GAMatchSyntheticPlayer(1, $m['players']['1']['authKey'] ?? ''),
        new GAMatchSyntheticPlayer(2, $m['players']['2']['authKey'] ?? ''),
    ];

    $nextNumber = intval($m['currentGameNumber'] ?? count($m['games'])) + 1;
    $gameName = GASetupGame($lobby, [
        'resolvedDecks'     => $resolvedDecks,
        'forcedFirstPlayer' => in_array($firstPlayer, [1, 2], true) ? $firstPlayer : null,
    ]);

    GAWithMatchLock($matchId, function (&$mm) use ($gameName, $nextNumber) {
        $mm['currentGameNumber'] = $nextNumber;
        $mm['games'][] = ['gameName' => strval($gameName), 'gameNumber' => $nextNumber, 'winner' => null];
        $mm['state'] = 'in_progress';   // sideboarding complete
    });
    GAWriteGameMatchRef($matchId, $nextNumber);
    WriteGamestate(__DIR__ . '/');
    if (function_exists('RemoveActiveGame') && $priorGame !== '') RemoveActiveGame('GrandArchiveSim', $priorGame);
    return $gameName;
}

// Post-action: advance the match if this game just ended. Called by Core/EngineActionRunner.
function GAAfterActionMatchHook($folderPath, $gameName) {
    if ($folderPath !== 'GrandArchiveSim') return;
    $ref = GAReadGameMatchRef();
    if ($ref === null) return;                 // not a match game (goldfish/hotseat/legacy)
    $winner = GAGetGameWinner();
    if ($winner === 0) return;                 // not over

    $m = GARecordGameResult($ref['matchId'], $gameName, $winner);
    if (!is_array($m)) $m = GAReadMatch($ref['matchId']);
    if (!is_array($m)) return;

    if (GAMatchIsOver($m)) {
        SetFlashMessage('MATCHOVER:Player ' . GAMatchWinner($m) . ' wins the match ' .
            intval($m['wins']['1']) . '-' . intval($m['wins']['2']) . '!');
        if (function_exists('RemoveActiveGame')) RemoveActiveGame('GrandArchiveSim', $gameName);
        return;
    }
    // Not over — the loser sideboards first. Cross-game state lives on the Match object.
    $loser = ($winner === 1) ? 2 : 1;
    GABeginSideboarding($ref['matchId'], $loser);
}

// Once both seats have submitted their sideboarded decks, spawn the next game (loser first) and clear
// the sideboard state. Returns the new gameName (or '' if not both-ready). Called by SubmitSideboard.
function GAMaybeSpawnAfterSideboard($matchId) {
    $m = GAReadMatch($matchId);
    if (!is_array($m) || ($m['state'] ?? '') !== 'sideboarding') return '';
    if (!GASideboardBothReady($m)) return '';

    $first = intval($m['pendingFirstPlayer'] ?? 1);
    $prior = !empty($m['games']) ? strval($m['games'][count($m['games']) - 1]['gameName']) : '';
    $decks = [1 => $m['sideboard']['1']['deck'], 2 => $m['sideboard']['2']['deck']];
    $next = GASpawnNextMatchGameWithDecks($matchId, $first, $prior, $decks);

    GAWithMatchLock($matchId, function (&$mm) {
        $mm['state'] = 'in_progress';
        unset($mm['sideboard'], $mm['sideboardDeadline'], $mm['pendingFirstPlayer']);
    });
    return $next;
}

// ─── Post-match: rematch & convert-to-Bo3 ────────────────────────────────────────────────────────
// GA keeps NO pointer files (unlike SWUSim): a rematch spawns a brand-new match, and the OLD match
// records `rematchInto` (the new matchId) so the finished game's EndGameInfo can steer players into it.

// Record one seat's rematch request on the finished match (mutual handshake).
function GARequestRematch($oldMatchId, $seat, $bestOf, $sideboard) {
    return GAWithMatchLock($oldMatchId, function (&$m) use ($seat, $bestOf, $sideboard) {
        if (($m['state'] ?? '') !== 'complete') return;
        if ($seat !== 1 && $seat !== 2) return;
        if (!isset($m['rematchRequests'])) $m['rematchRequests'] = [];
        $m['rematchRequests'][strval($seat)] = ['bestOf' => (intval($bestOf) === 3 ? 3 : 1), 'sideboard' => (bool)$sideboard];
    });
}

// When both seats agree on bestOf, create a new match (same decks/authkeys) and point the old one at it.
// Quick rematch → spawn game 1 immediately; full rematch → new match enters sideboarding. Returns newId.
function GAAcceptRematch($oldMatchId) {
    $m = GAReadMatch($oldMatchId);
    if (!is_array($m) || ($m['state'] ?? '') !== 'complete') return null;
    if (!empty($m['rematchInto'])) return $m['rematchInto'];   // already fired
    $r1 = $m['rematchRequests']['1'] ?? null;
    $r2 = $m['rematchRequests']['2'] ?? null;
    if (!$r1 || !$r2) return null;
    if (intval($r1['bestOf']) !== intval($r2['bestOf'])) return null;   // must agree on series length
    $bestOf    = intval($r1['bestOf']);
    $sideboard = !empty($r1['sideboard']) || !empty($r2['sideboard']);
    $queueType = ($bestOf === 3) ? 'bo3' : 'bo1';

    $newId = GACreateMatch('GrandArchiveSim', strval($m['format'] ?? 'standard'), $queueType, [
        1 => ['originalDeck' => $m['players']['1']['originalDeck'] ?? [], 'authKey' => $m['players']['1']['authKey'] ?? '',
              'userId' => null, 'deckLink' => $m['players']['1']['deckLink'] ?? ''],
        2 => ['originalDeck' => $m['players']['2']['originalDeck'] ?? [], 'authKey' => $m['players']['2']['authKey'] ?? '',
              'userId' => null, 'deckLink' => $m['players']['2']['deckLink'] ?? ''],
    ]);

    $oldGame    = !empty($m['games']) ? strval($m['games'][count($m['games']) - 1]['gameName']) : '';
    $lastWinner = !empty($m['games']) ? intval($m['games'][count($m['games']) - 1]['winner'] ?? 1) : 1;
    $first      = ($lastWinner === 1) ? 2 : 1;   // loser of the prior game goes first

    $newGame = '';
    if ($sideboard) {
        GABeginSideboarding($newId, $first);
    } else {
        $decks = [1 => $m['players']['1']['originalDeck'] ?? [], 2 => $m['players']['2']['originalDeck'] ?? []];
        $newGame = GASpawnNextMatchGameWithDecks($newId, $first, $oldGame, $decks);   // appends as game 1
    }

    // Stamp the pointer on the OLD match (fire once) so the finished game navigates into the rematch.
    GAWithMatchLock($oldMatchId, function (&$mm) use ($newId, $newGame) {
        $mm['rematchInto']     = $newId;
        $mm['rematchGameName'] = ($newGame !== '') ? strval($newGame) : null;
        unset($mm['rematchRequests']);
    });
    return $newId;
}

// Record one seat's convert-to-Bo3 request on a finished Bo1 (mutual handshake).
function GARequestConvertToBo3($matchId, $seat) {
    return GAWithMatchLock($matchId, function (&$m) use ($seat) {
        if (($m['state'] ?? '') !== 'complete' || intval($m['bestOf'] ?? 1) !== 1) return;
        if (!empty($m['rematchInto'])) return;   // already rematched → no convert
        if (!isset($m['convertRequests'])) $m['convertRequests'] = [];
        if ($seat === 1 || $seat === 2) $m['convertRequests'][strval($seat)] = true;
    });
}

// When both seats have requested, promote the finished Bo1 to Bo3 in place (keeping game 1's result) and
// re-enter sideboarding for game 2 (loser of game 1 first). Returns the matchId (unchanged) or null.
function GAAcceptConvertToBo3($matchId) {
    $m = GAReadMatch($matchId);
    if (!is_array($m) || intval($m['bestOf'] ?? 1) !== 1) return null;
    if (empty($m['convertRequests']['1']) || empty($m['convertRequests']['2'])) return null;
    GAWithMatchLock($matchId, function (&$mm) {
        $mm['bestOf'] = 3; $mm['winsNeeded'] = 2; $mm['state'] = 'in_progress'; $mm['queueType'] = 'bo3';
        unset($mm['convertRequests'], $mm['winner']);
    });
    $m = GAReadMatch($matchId);
    $g1winner = intval($m['games'][0]['winner'] ?? 1);
    GABeginSideboarding($matchId, ($g1winner === 1) ? 2 : 1);   // loser of game 1 sideboards first
    return $matchId;
}

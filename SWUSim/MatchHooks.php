<?php
// SWUSim/MatchHooks.php — registers SWUSim's Match adapter for the shared Core/Match framework,
// and holds SWUSim's game-specific match hook bodies (deck resolution, stats, flash, block check).
require_once __DIR__ . '/../Core/Match/Hooks.php';
require_once __DIR__ . '/Custom/DeckImport.php';           // SWUResolveDeckInput / SWUCheckFormat / SWUComputeDeckIdentity
require_once __DIR__ . '/CreateGame.php';                  // SWUSetupGame
require_once __DIR__ . '/StatsSubmit.php';                 // SWUCaptureCurrentGameDetail / SWUSubmitMatchResults / SWUBuildStatsHtml
require_once __DIR__ . '/../Database/functions.inc.php';   // SWUResolveSeatCosmetics / AreUsersBlocked

// ── Hook bodies ───────────────────────────────────────────────────────────────

// Resolve + validate each lobby seat's deck, returning the per-seat wrapper the framework expects:
// [seat => ['originalDeck'=>resolved, 'authKey'=>..., 'userId'=>..., 'deckIdentity'=>..., 'deckLink'=>..., 'cosmetics'=>...]].
// Returns null (having set a flash message) on any resolution / legality failure. Extracted verbatim
// from the old SWUCreateMatchFromLobby resolve block.
function SWUResolveLobbyDecks($lobby) {
    $format = $lobby->format ?? 'premier';
    $resolved = []; $seat = 1;
    foreach ($lobby->players as $player) {
        $r = SWUResolveDeckInput($player->getDeckLink());
        if (empty($r['success'])) { SetFlashMessage("Deck error for player $seat: " . ($r['message'] ?? '')); return null; }
        $errs = SWUCheckFormat($format, $r['leader'], $r['base'], $r['mainDeck'], $r['sideboard']);
        if (!empty($errs)) { SetFlashMessage("Deck illegal for player $seat ($format): " . implode('; ', array_slice($errs, 0, 3))); return null; }
        $resolved[$seat] = $r;
        ++$seat;
    }
    $out = []; $seat = 1;
    foreach ($lobby->players as $player) {
        $userId = method_exists($player, 'getUserId') ? $player->getUserId() : null;
        $out[$seat] = [
            'originalDeck' => $resolved[$seat],
            'authKey'      => $player->getAuthKey(),
            'userId'       => $userId,
            'deckIdentity' => SWUComputeDeckIdentity($player->getDeckLink()),
            'deckLink'     => $player->getDeckLink(),
            'cosmetics'    => SWUResolveSeatCosmetics($userId),
        ];
        ++$seat;
    }
    return $out;
}

// Validate an already-resolved sideboarded deck against the format (validateDeck hook contract).
function SWUValidateResolvedDeck($resolved, $format) {
    $errs = SWUCheckFormat($format, $resolved['leader'] ?? '', $resolved['base'] ?? '',
                           $resolved['mainDeck'] ?? [], $resolved['sideboard'] ?? []);
    return empty($errs);
}

// Match-over flash: the client's GAMEOVER banner is reused for MATCHOVER (GameLayoutShared).
function SWUFlashMatchResult($gameName, $winnerSeat, $m) {
    SetFlashMessage("MATCHOVER:Player " . intval($winnerSeat) . " wins the match " .
        intval($m['wins']['1'] ?? 0) . "-" . intval($m['wins']['2'] ?? 0) . "!");
}

// Block check (arePlayersBlocked hook). Loads the DB layer lazily like the old SWUAreGamePlayersBlocked.
function SWUArePlayersBlocked($u1, $u2) {
    require_once __DIR__ . '/../Database/ConnectionManager.php';
    require_once __DIR__ . '/../Database/functions.inc.php';
    return AreUsersBlocked($u1, $u2);
}

// Record per-game deck stats for both seats (recordDeckStats hook). Idempotency is the caller's job.
// Extracted verbatim from the old SWUSim/MatchFlow.php.
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

// ── Registration ──────────────────────────────────────────────────────────────
MatchRegisterHooks('SWUSim', [
    // required
    'resolveLobbyDecks' => 'SWUResolveLobbyDecks',
    'validateDeck'      => 'SWUValidateResolvedDeck',
    'setupGame'         => 'SWUSetupGame',
    // optional
    'recordDeckStats'   => 'SWURecordDeckStatsForGame',
    'captureGameDetail' => 'SWUCaptureCurrentGameDetail',
    'submitResults'     => 'SWUSubmitMatchResults',
    'buildStatsHtml'    => 'SWUBuildStatsHtml',
    'flashMatchResult'  => 'SWUFlashMatchResult',
    'arePlayersBlocked' => 'SWUArePlayersBlocked',
    'declareGameWinner' => 'SWUDeclareGameWinner',
    // config
    'queueTypes'        => ['bo1', 'bo3'],
    'sideboardUrl'      => 'Sideboard.php',
    'sideboardSeconds'  => 180,
]);

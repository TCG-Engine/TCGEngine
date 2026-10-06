<?php
// SWUSim/MatchHooks.php — registers SWUSim's Match adapter for the shared Core/Match framework,
// and holds SWUSim's game-specific match hook bodies (deck resolution, stats, flash, block check).
require_once __DIR__ . '/../Core/Match/Hooks.php';
require_once __DIR__ . '/Custom/DeckImport.php';           // SWUResolveDeckInput / SWUCheckFormat / SWUComputeDeckIdentity
require_once __DIR__ . '/CreateGame.php';                  // SWUSetupGame
require_once __DIR__ . '/StatsSubmit.php';                 // SWUCaptureCurrentGameDetail / SWUSubmitMatchResults / SWUBuildStatsHtml
require_once __DIR__ . '/../Database/functions.inc.php';   // SWUResolveSeatCosmetics / AreUsersBlocked
require_once __DIR__ . '/../AppCore/SWU/Formats.php';      // SWUFormatIsRated

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
// The banner is only the fallback — the flash's real job is to open the end-game menu, which names
// the winners properly. Twin Suns has no series score to report (Bo1, and a "1-0" over four seats
// reads as nonsense), so >2 seats gets the result without one.
function SWUFlashMatchResult($gameName, $winnerSeat, $m) {
    if (count($m['players'] ?? []) > 2) {
        $winners = is_array($m['winners'] ?? null) && !empty($m['winners']) ? $m['winners'] : [intval($winnerSeat)];
        $labels = array_map(fn($s) => 'Player ' . intval($s), $winners);
        SetFlashMessage("MATCHOVER:" . implode(', ', $labels) . " " . (count($labels) > 1 ? 'win' : 'wins') . " the game!");
        return;
    }
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
    // Twin Suns (v1): N-player deck W/L attribution isn't modeled yet (the pairwise
    // opponent-matchup logic below assumes exactly 2 seats) — skip stats for >2 seats.
    if (count($match['players'] ?? []) > 2) return;
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

// Meta Premier rating (rateMatch hook) — classify the finished match and apply it. Idempotent; silent for unrated
// formats, guests, and a server without migration 17. docs/superpowers/specs/2026-10-03-swusim-metapremier-ratings-design.md §4.1.
function SWUMetaPremierRateMatch($matchId) {
    $m = SWUReadMatch($matchId);
    if (!is_array($m)) return;
    require_once __DIR__ . '/MetaPremier.php';
    $c = SWUMetaPremierClassify($m);
    if ($c === null) return;
    require_once __DIR__ . '/../Database/ConnectionManager.php';
    try { SWUMetaPremierApply(GetLocalMySQLConnection(), $c); }
    catch (Throwable $e) { error_log('SWUMetaPremierRateMatch: ' . $e->getMessage()); }
}

// allowsSeriesChange hook: no Rematch / Quick Rematch / Convert-to-Bo3 in a rated format — those build a new or longer
// match between the same two players without the queue (spec §4.5). Players re-queue instead.
function SWUMatchAllowsSeriesChange(array $m): bool {
    return !SWUFormatIsRated(strval($m['format'] ?? ''));
}

// endSeriesAfterGame hook: in a rated format an ABANDON (inactivity removal) or an EARLY concede (before Round 2's action
// phase — owner, 2026-10-05) forfeits the whole series; the opponent should not have to sit through sideboarding for a
// series that is already decided. Returns the seat that lost the game just recorded that way, or 0.
function SWUMatchEndSeriesAfterGame(array $m): int {
    if (!SWUFormatIsRated(strval($m['format'] ?? '')) || count($m['players'] ?? []) !== 2) return 0;
    $last = null;
    foreach (($m['games'] ?? []) as $g) if (($g['winner'] ?? null) !== null) $last = $g;
    if ($last === null) return 0;
    $d = $last['detail'] ?? [];
    $early = ($d['endReason'] ?? '') === 'concede' && isset($d['turns']) && intval($d['turns']) < 2;
    if (($d['endReason'] ?? '') !== 'abandon' && !$early) return 0;
    $winner = intval($last['winner']);
    return ($winner === 1) ? 2 : (($winner === 2) ? 1 : 0);
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
    'rateMatch'         => 'SWUMetaPremierRateMatch',
    'allowsSeriesChange' => 'SWUMatchAllowsSeriesChange',
    'endSeriesAfterGame' => 'SWUMatchEndSeriesAfterGame',
    // config
    'queueTypes'        => ['bo1', 'bo3'],
    'sideboardUrl'      => 'Sideboard.php',
    'sideboardSeconds'  => 180,
]);

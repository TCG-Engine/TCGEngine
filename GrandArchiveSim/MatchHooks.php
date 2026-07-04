<?php
// GrandArchiveSim/MatchHooks.php — registers GA's Match adapter for the shared
// Core/Match framework, and holds GA's game-specific match hook bodies (so GA's
// old MatchFlow.php can be retired).
require_once __DIR__ . '/../Core/Match/Hooks.php';
require_once __DIR__ . '/Custom/DeckImport.php'; // GrandArchiveResolveDeckInput / GAValidateResolvedDeck

// Resolve both lobby decks into the per-seat wrapper the framework expects:
// [seat => ['originalDeck'=>resolvedDeck, 'authKey'=>..., 'userId'=>..., 'deckLink'=>...]].
// Returns null (with a flash) if fewer than two decks resolve. Mirrors the old
// GAResolveLobbyDecks + GACreateMatchFromLobby wrapper build.
function GAResolveLobbyDecksForMatch($lobby) {
    $out = []; $seat = 1;
    foreach ($lobby->players as $player) {
        $r = GrandArchiveResolveDeckInput($player->getDeckLink());
        $deck = !empty($r['success']) ? $r : ['material' => [], 'mainDeck' => [], 'sideboard' => [], 'unresolved' => []];
        $out[$seat] = [
            'originalDeck' => $deck,
            'authKey'      => $player->getAuthKey(),
            'userId'       => null,
            'deckLink'     => $player->getDeckLink(),
        ];
        ++$seat;
    }
    if (count($out) < 2) { if (function_exists('SetFlashMessage')) SetFlashMessage('Match needs two decks.'); return null; }
    return $out;
}

MatchRegisterHooks('GrandArchiveSim', [
    // required
    'resolveLobbyDecks' => 'GAResolveLobbyDecksForMatch',
    'validateDeck'      => 'GAValidateResolvedDeck',   // (resolvedDeck, $format)
    'setupGame'         => 'GASetupGame',
    // optional — GA has no per-game deck stats, no login (no block), no telemetry yet.
    'buildStatsHtml'    => (function_exists('GABuildStatsHtml') ? 'GABuildStatsHtml' : null),
    // config
    'queueTypes'        => ['bo1', 'bo3'],
    'sideboardUrl'      => 'Sideboard.php',   // GA ships its own card editor
    'sideboardSeconds'  => 180,
]);

<?php
// SWUSim public matchmaking rules — docs/superpowers/specs/2026-09-16-swusim-public-queues-design.md §2.
// Used by APIs/Lobbies/JoinQueue.php (and PollLobbyUpdates.php via the lobby's queueNotices). Needs
// SWUSim/Custom/DeckImport.php (SWUResolveDeckInput, SWUCheckFormat) loaded by the caller.
require_once __DIR__ . '/../AppCore/SWU/Formats.php';

// Why $formatId may not use the public queue, or null when it may. The Twin Suns family is pointed at private rooms;
// everything else not allowed (a local mode, a disabled format, the site-wide switch off) gets the generic line.
function SWUPublicQueueRefusal(string $formatId): ?string {
    if (SWUFormatAllowsPublicQueue($formatId)) return null;
    if (SWUFormatIsRoomFormat($formatId)) return "Twin Suns games are played in private rooms — create a room and share the invite.";
    return "Public matchmaking isn't open for this format.";
}

// Format-legality errors for a deck entering a public queue ([] = legal). The same check private rooms run at join
// (SWULobbyAdapter::validateDeck) — before this, a public deck was only checked in the browser and again at pairing.
function SWUPublicQueueDeckErrors(string $formatId, string $deckInput): array {
    if (trim($deckInput) === '') return ['Deck link is required.'];
    $r = SWUResolveDeckInput($deckInput);
    if (empty($r['success'])) return [strval($r['message'] ?? 'Could not read deck.')];
    return SWUCheckFormat($formatId, $r['leader'] ?? '', $r['base'] ?? '', $r['mainDeck'] ?? [], $r['sideboard'] ?? []);
}

// Pairing-time check (§2.3): every seat whose deck fails its format now → [authKey => message]. A deck that was legal at
// join can fail here when its SWUDB list changed while it waited. `testFailAtPairing` (authKeys) is set only through the
// local-dev test hook in APIs/Lobbies/JoinQueue.php.
function SWUQueueFailingSeats($lobby): array {
    $out = [];
    $format = strval($lobby->format ?? 'premier');
    $testFail = is_array($lobby->testFailAtPairing ?? null) ? $lobby->testFailAtPairing : [];
    foreach (($lobby->players ?? []) as $p) {
        if (!($p instanceof Player)) continue;
        $key = strval($p->getAuthKey());
        if (in_array($key, $testFail, true)) {
            $out[$key] = 'Your deck failed its format check when the match was found (test hook).';
            continue;
        }
        $errs = SWUPublicQueueDeckErrors($format, strval($p->getDeckLink()));
        if (!empty($errs)) $out[$key] = "Your deck is no longer legal for $format: " . implode('; ', array_slice($errs, 0, 3));
    }
    return $out;
}

// Release the failing seats (§2.3). The lobby goes back to searching with whoever is left; each released seat gets a
// notice that PollLobbyUpdates.php answers as {gone, message}. Mutates $lobby — call inside LobbyMutate.
function SWUQueueDropSeats($lobby, array $failing): void {
    $notices = is_array($lobby->queueNotices ?? null) ? $lobby->queueNotices : [];
    $keep = [];
    foreach (($lobby->players ?? []) as $p) {
        $key = ($p instanceof Player) ? strval($p->getAuthKey()) : '';
        if ($key !== '' && isset($failing[$key])) { $notices[$key] = strval($failing[$key]); continue; }
        $keep[] = $p;
    }
    $lobby->players = $keep;
    $lobby->numPlayers = count($keep);
    $lobby->ready = false;
    $lobby->queueNotices = $notices;
    if (is_array($lobby->testFailAtPairing ?? null)) {
        $lobby->testFailAtPairing = array_values(array_diff($lobby->testFailAtPairing, array_keys($failing)));
    }
}

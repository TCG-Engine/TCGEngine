<?php
// The Rematch inputs for the end-game menu (spec 2026-09-23-swusim-bot-data-loop-design.md §3).
//
// ⚠ NOT SWURequestRematch. That flow routes through a SIDEBOARD step and an opponent ACCEPTING,
// neither of which an Arenabot game has. Bending it would put local-mode-only branches into shared
// match code. Instead the client re-POSTs these inputs to the existing APIs/Lobbies/JoinQueue.php, so
// the whole validated creation path — deck resolution, format legality, card pool — is reused.
//
// ⚠ AND THERE IS NO MATCH RECORD TO READ THEM BACK FROM. An Arenabot game sets isGoldfish
// (APIs/Lobbies/JoinQueue.php:293) and match creation is gated on !isGoldfish (:425), so the deck
// links exist only on the lobby at creation time and nowhere afterwards. They are persisted into the
// game's own directory the moment the game is set up, or they are gone by the end-game menu.
//
// Only the creation INPUTS are stored. No authKey, no userId: a rematch re-runs JoinQueue.php, which
// mints fresh credentials of its own.

function SWUBotDataWriteRematch(string $gameDir, array $inputs): void {
    $row = ['format'    => 'botpractice',
            'deckLink'  => strval($inputs['deckLink'] ?? ''),
            'deckLink2' => strval($inputs['deckLink2'] ?? ''),
            'botStyle'  => strval($inputs['botStyle'] ?? ''),
            'cardPool'  => strval($inputs['cardPool'] ?? '')];
    @file_put_contents(rtrim($gameDir, '/') . '/Rematch.json', json_encode($row, JSON_UNESCAPED_SLASHES), LOCK_EX);
}

// Returns null when a rematch is not offered: no file, unreadable, or a half-known game that would
// create a broken one.
function SWUBotDataReadRematch(string $gameDir): ?array {
    $p = rtrim($gameDir, '/') . '/Rematch.json';
    if (!is_file($p)) return null;
    $r = json_decode(strval(@file_get_contents($p)), true);
    if (!is_array($r)) return null;
    if (strval($r['deckLink'] ?? '') === '' || strval($r['deckLink2'] ?? '') === '') return null;
    return $r;
}

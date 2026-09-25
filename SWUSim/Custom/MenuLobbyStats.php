<?php
// What the main menu's PvP and Twin Suns cards say under "Enter".
//
// Those two lines were mockup fixtures hardcoded in MainMenu.php ("4 players in queue",
// "2 tables forming"). A number that never moves is worse than no number: it tells a visitor the
// site is busy when it is empty, and it tells a regular the card is decoration.
//
// The source is the SAME lobby rows APIs/Lobbies/GetLobbies.php builds from APCu. This file does
// not read the cache itself — the derivation is pure so DevTools/tdd-regression/
// test_swusim_menu_lobby_stats.php can drive every branch without APCu, a lobby or a web server.

require_once __DIR__ . '/../../AppCore/SWU/Formats.php';   // SWUFormatSeatRange

// $lobbies    rows shaped like GetLobbies.php's payload: format, numPlayers, isPrivate, state
// $seatRange  fn(formatId) -> [minPlayers, maxPlayers]
//
// ⚠ THAT IS A LIST, [min, max] — the exact return of SWUFormatSeatRange() in
// AppCore/SWU/Formats.php, which is the default. The first version of this file documented and
// read an ASSOCIATIVE ['maxPlayers'=>…] instead, and the unit test injected a fake of that
// invented shape, so both agreed with each other and neither agreed with production: every
// format fell through to the 2-seat default and a live Twin Suns table was counted as two PvP
// players. The injected shape MUST be the real function's shape, or the test proves nothing.
//
// Returns ['pvpPlayers'=>int, 'pvpLobbies'=>int, 'multiTables'=>int].
function SWUMenuLobbyStats(array $lobbies, ?callable $seatRange = null): array {
    if ($seatRange === null) {
        $seatRange = function (string $f): array { return SWUFormatSeatRange($f); };
    }
    $out = ['pvpPlayers' => 0, 'pvpLobbies' => 0, 'multiTables' => 0];

    foreach ($lobbies as $lobby) {
        // The rows come from a cache any session can write, so nothing here may assume a shape.
        if (!is_array($lobby)) continue;
        $format = trim(strval($lobby['format'] ?? ''));
        if ($format === '') continue;

        // A private room is not a queue anyone can join. Counting it would both overstate the
        // queue and advertise that a private room exists, which is the one thing it is not.
        if (!empty($lobby['isPrivate'])) continue;
        // 'matched' means the lobby has already become a game: that is Games in Progress' story.
        if (strval($lobby['state'] ?? '') === 'matched') continue;

        $range = array_values((array)$seatRange($format));
        $max   = max(2, intval($range[1] ?? 2));

        // ⚠ The split is the FORMAT'S OWN seat range, never a list of format ids — the same rule
        // SWUFormatIsRoomFormat() states ("a room format is any format seating more than 2 … use
        // this instead of comparing $lobby->format to a literal"). twinsuns, teamsuns and every
        // preview variant are 3-4 seats and sort themselves; a multiplayer format added later is
        // covered the day it is defined. A hardcoded list is what drifted in eight other places
        // before SWUStatsFormats() existed (see AppCore/SWU/Formats.php).
        if ($max > 2) {
            // A table is the thing you join, so multiplayer counts ROOMS, not the seats in them.
            $out['multiTables']++;
            continue;
        }

        // A queue is people waiting, so PvP counts PLAYERS. Clamped to the format's own seat
        // range: numPlayers comes from the cache, and neither a negative nor a 999 may show up
        // on the card.
        $out['pvpLobbies']++;
        $out['pvpPlayers'] += max(0, min($max, intval($lobby['numPlayers'] ?? 0)));
    }

    return $out;
}

// The impure half: the APCu scan, kept out of SWUMenuLobbyStats() so that stays testable.
//
// ⚠ The shape guard is COPIED FROM APIs/Lobbies/GetLobbies.php on purpose, not imported: that file
// is a public endpoint five sims read, and requiring it here would run its top-level code and emit
// its JSON. If its notion of "a matchmaking lobby" ever changes, this guard has to follow.
//
// Returns [] when APCu is unavailable — which is also the truth, since the lobby store IS APCu:
// no cache means no lobbies, and the cards correctly say the site is quiet.
function SWUMenuLobbyRows(string $rootName = 'SWUSim'): array {
    if (!function_exists('apcu_cache_info')) return [];
    $info = @apcu_cache_info();
    if (!is_array($info) || !isset($info['cache_list']) || !is_array($info['cache_list'])) return [];

    $rows = [];
    foreach ($info['cache_list'] as $entry) {
        if (!isset($entry['info']) || !is_string($entry['info']) || $entry['info'] === '') continue;
        $lobby = apcu_fetch($entry['info']);
        if ($lobby === false || !is_object($lobby)) continue;
        if (!isset($lobby->id) || !isset($lobby->numPlayers) || !isset($lobby->maxPlayers) || !isset($lobby->ready)) continue;
        if (($lobby->rootName ?? '') !== $rootName) continue;

        $rows[] = [
            'format'     => strval($lobby->format ?? ''),
            'numPlayers' => intval($lobby->numPlayers),
            'isPrivate'  => !empty($lobby->isPrivate),
            'state'      => strval($lobby->state ?? ''),
        ];
    }
    return $rows;
}

// The card copy. Every state says something, INCLUDING zero — a card whose foot goes blank when
// the site is quiet reads as broken rather than as empty.
function SWUMenuStatLabel(string $card, array $stats): string {
    if ($card === 'multi') {
        $n = intval($stats['multiTables'] ?? 0);
        if ($n <= 0) return 'No tables forming';
        return $n . ($n === 1 ? ' table forming' : ' tables forming');
    }
    $n = intval($stats['pvpPlayers'] ?? 0);
    if ($n <= 0) return 'No one in queue';
    return $n . ($n === 1 ? ' player in queue' : ' players in queue');
}

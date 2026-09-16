<?php
// How the SWUSim game-setup choices are interpreted — the rules the menu (SharedUI/Sites/SWUSim/MainMenu.php), the lobby
// (APIs/Lobbies/JoinQueue.php) and game creation (SWUSim/CreateGame.php) must agree on.
// Spec: docs/superpowers/specs/2026-09-16-swusim-format-menu-design.md §2.
// Pure, apart from SWUArenabotDeckRefusal: its caller must already have loaded SWUSim/Custom/DeckImport.php (JoinQueue does,
// for rootName SWUSim).
require_once __DIR__ . '/../AppCore/SWU/Formats.php';

// The card pools Arenabot offers: every enabled Constructed pool, in menu order. Login-independent, because Arenabot needs no
// account (SWUMenuTreeFor keeps its pools for logged-out viewers too).
function SWUArenabotPools(): array {
    foreach (SWUMenuTreeFor(true, true) as $gt) {
        foreach ($gt['options'] as $opt) {
            if ($opt['id'] === 'arenabot') return array_map(fn($p) => $p['format'], $opt['pools']);
        }
    }
    return [];
}

// The pool an Arenabot request asked for. ABSENT (null) → 'open', so an older client keeps today's anything-goes behaviour.
// PRESENT but not an Arenabot pool — including an empty value — → null: refused, never silently widened to Open.
function SWUArenabotResolvePool(?string $raw): ?string {
    if ($raw === null) return 'open';
    $pool = strtolower(trim($raw));
    return in_array($pool, SWUArenabotPools(), true) ? $pool : null;
}

// The card pool a game records as the game variable SWUCardPool: an Arenabot game its requested pool (open when missing or
// invalid); Goldfish and Hotseat 'open', because they are unrestricted; every other game its own format.
function SWUCardPoolFor(string $format, ?string $requestedPool): string {
    $format = strtolower(trim($format));
    if ($format === 'botpractice') return SWUArenabotResolvePool($requestedPool) ?? 'open';
    if ($format === 'goldfish' || $format === 'hotseat') return 'open';
    return $format;
}

// Why an Arenabot game may not start in $pool, or null when it may. Both decks are checked on the server: the menu's own
// check is a convenience, and it never looked at the bot's deck at all. $deckLink2 is the bot's deck; blank means the bot
// plays the player's deck, which the first check already covers. Open checks nothing — it is today's behaviour.
function SWUArenabotDeckRefusal(string $pool, string $deckLink, string $deckLink2): ?string {
    if ($pool === 'open') return null;
    $name = SWUGetFormat($pool)['displayName'] ?? $pool;
    $seats = [['Your deck', $deckLink]];
    if (trim($deckLink2) !== '') $seats[] = ["The bot's deck", $deckLink2];
    foreach ($seats as [$who, $link]) {
        if (trim($link) === '') return "$who is required for an Arenabot $name game.";
        $r = SWUResolveDeckInput($link);
        if (empty($r['success'])) return "$who could not be loaded: " . strval($r['message'] ?? '');
        $errs = SWUCheckFormat($pool, $r['leader'], $r['base'], $r['mainDeck'], $r['sideboard']);
        if (!empty($errs)) {
            return "$who is not legal in $name: " . implode('; ', array_slice($errs, 0, 3))
                 . '. Choose the Open card pool to practice with any deck.';
        }
    }
    return null;
}

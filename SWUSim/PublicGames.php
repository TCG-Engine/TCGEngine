<?php
// SWUSim public games in progress — feeds the main menu's left panel (owner, 2026-09-21: "show which games are
// available … each game that is public will have a chip with the leader vs leader and the spectate button").
//
// GET SWUSim/PublicGames.php → { success, count, formats:[{id,name}], games:[{
//     gameName, format, formatName, isTeam, spectateUrl, lastUpdatedAt,
//     seats:[{ seat, team, leaders:[{id,name,url}], base:{id,name,url}|null }] }] }
//
// A game is listed when it is (1) in the shared active-game index for SWUSim and touched recently, (2) NOT private —
// by its auth record AND its match record, (3) the CURRENT game of an in-progress MATCH. Solo games (Goldfish, Hotseat,
// Arenabot) never create a match, so they never appear. Leaders and bases are public information (both start face up),
// read from Match.json rather than the full gamestate — one small JSON read per game.
// Spectating a public SWUSim game needs no account since 2026-09-21 (Core/GameAuth.php).
//
// New endpoint, not a change to the shared APIs/Lobbies/GetActiveGames.php (five sims read that one).

require_once __DIR__ . '/../Core/Match/MatchFlow.php';     // MatchReadRef / MatchRead
require_once __DIR__ . '/../AppCore/SWU/Formats.php';       // SWUGetFormat (display names, team formats)
require_once __DIR__ . '/../AppCore/SWU/CardImagePath.php'; // SWUCardImagePath — the one seam that handles mock_ art

// Pure: everything it touches comes in through the arguments, so the test can drive it without APCu or files.
//   $index      the active-game index (ReadActiveGameIndex())
//   $readRef    fn(gameName) → ['matchId','gameNumber'] | null
//   $readMatch  fn(matchId) → match array | null
//   $isPrivate  fn(gameName) → bool   (the game's own auth record)
//   $cardName   fn(cardID) → display name
function SWUPublicGamesList(array $index, int $now, callable $readRef, callable $readMatch, callable $isPrivate,
                            callable $cardName, int $activeWithinSeconds = 1800): array {
    $card = function ($cid) use ($cardName) {
        $cid = strval($cid);
        if ($cid === '') return null;
        // The set code is the CardID's own prefix. NOTE: set codes CONTAIN DIGITS (TS26_01,
        // IC27_001), so this splits on the first underscore rather than matching [A-Z]+.
        $us = strpos($cid, '_');
        return ['id' => $cid, 'name' => strval($cardName($cid)) ?: $cid,
                'set' => $us === false ? '' : substr($cid, 0, $us),
                'url' => SWUCardImagePath($cid, 'card')];
    };
    $games = [];
    foreach ($index as $entry) {
        if (!is_array($entry) || strval($entry['rootName'] ?? '') !== 'SWUSim') continue;
        $gameName = strval($entry['gameName'] ?? '');
        $touched = intval($entry['lastUpdatedAt'] ?? 0);
        if ($gameName === '' || $touched <= 0 || ($now - $touched) > $activeWithinSeconds) continue;
        if (!empty($entry['isPrivate']) || $isPrivate($gameName)) continue;

        $ref = $readRef($gameName);
        if (!is_array($ref) || strval($ref['matchId'] ?? '') === '') continue;          // solo game: no match
        $match = $readMatch(strval($ref['matchId']));
        if (!is_array($match) || !empty($match['isPrivate'])) continue;
        if (strval($match['state'] ?? 'in_progress') !== 'in_progress') continue;       // match already decided
        if (intval($ref['gameNumber'] ?? 0) !== intval($match['currentGameNumber'] ?? 1)) continue;   // an earlier game of a Bo3

        $format = strval($match['format'] ?? '');
        $def = SWUGetFormat($format);
        $isTeam = function_exists('SWUFormatIsTeamFormat') && SWUFormatIsTeamFormat($format);
        $seats = [];
        foreach (($match['players'] ?? []) as $seatKey => $p) {
            $seat = intval($seatKey);
            if ($seat < 1 || !is_array($p)) continue;
            $deck = is_array($p['originalDeck'] ?? null) ? $p['originalDeck'] : [];
            $leaders = [];
            foreach ((array)($deck['leader'] ?? []) as $l) {   // one CardID, or a list of two in Twin Suns / Team Suns
                if (($c = $card($l)) !== null) $leaders[] = $c;
            }
            $seats[] = [
                'seat'    => $seat,
                'team'    => $isTeam ? (($seat % 2) === 1 ? 1 : 2) : 0,   // Team Suns pairs seats 1+3 against 2+4
                'leaders' => $leaders,
                'base'    => $card($deck['base'] ?? ''),
            ];
        }
        usort($seats, fn($a, $b) => $a['seat'] <=> $b['seat']);
        if (count($seats) < 2) continue;

        $games[] = [
            'gameName'      => $gameName,
            'format'        => $format,
            'formatName'    => strval($def['displayName'] ?? $format),
            'isTeam'        => $isTeam,
            'spectateUrl'   => '/TCGEngine/NextTurn.php?playerID=S&gameName=' . rawurlencode($gameName) . '&folderPath=SWUSim',
            'lastUpdatedAt' => $touched,
            // ROUND rides on the active-game index, which the engine already rewrites on every
            // action, so it costs no extra read. It is 0 until the game's first action after the
            // index was last built, and the chip simply omits "Round N" then rather than guessing.
            'round'         => max(0, intval($entry['round'] ?? 0)),
            // STARTED-AT IS THE MATCH RECORD'S, NOT THE INDEX'S. The index is an APCu cache with a
            // 60s TTL: let a game go quiet for a minute and the entry is rebuilt with createdAt =
            // now, so an elapsed time derived from it silently RESETS mid-game. Measured doing
            // exactly that (1790366499 -> 1790366695 on one idle game). Match.json is a real file
            // and its createdAt is the match's true start.
            'startedAt'     => max(0, intval($match['createdAt'] ?? 0)),
            'seats'         => $seats,
        ];
    }
    usort($games, fn($a, $b) => $b['lastUpdatedAt'] <=> $a['lastUpdatedAt']);

    $formats = [];
    foreach ($games as $g) $formats[$g['format']] = $g['formatName'];
    asort($formats);
    return [
        'success' => true,
        'count'   => count($games),
        'formats' => array_map(fn($id, $name) => ['id' => $id, 'name' => $name], array_keys($formats), array_values($formats)),
        'games'   => $games,
    ];
}

// ── HTTP ─────────────────────────────────────────────────────────────────────────────────────────────
if (realpath($_SERVER['SCRIPT_FILENAME'] ?? '') === realpath(__FILE__)) {
    require_once __DIR__ . '/../Core/NetworkingLibraries.php';                       // ReadActiveGameIndex, SimGame* auth
    // Every open main menu polls this every 20s, and each build reads one Match.json per running game — so the built
    // list is shared through APCu for 10s. A game that starts or ends shows up within one poll either way.
    $cacheKey = 'swusim:publicgames:v1';
    $cached = function_exists('apcu_fetch') ? apcu_fetch($cacheKey) : false;
    if (is_string($cached) && $cached !== '') {
        header('Content-Type: application/json');
        header('Cache-Control: no-store');
        echo $cached;
        return;
    }
    require_once __DIR__ . '/GeneratedCode/GeneratedCardDictionaries.php';            // CardTitle / CardSubtitle
    require_once __DIR__ . '/Custom/MenuLobbyStats.php';                              // the mode cards' counts
    $out = SWUPublicGamesList(
        ReadActiveGameIndex(),
        time(),
        fn($g) => MatchReadRef('SWUSim', $g),
        fn($id) => MatchRead('SWUSim', $id),
        fn($g) => SimGameHasAuthKeys('SWUSim', $g) && SimGameIsPrivateGame('SWUSim', $g),
        function ($cid) {
            $t = function_exists('CardTitle') ? strval(CardTitle($cid)) : '';
            $s = ($t !== '' && function_exists('CardSubtitle')) ? strval(CardSubtitle($cid)) : '';
            return $s !== '' ? "$t, $s" : $t;
        }
    );
    // ADDITIVE: the main menu's PvP / Twin Suns cards need a queue count on the same 20s poll that
    // already refreshes Games in Progress, so it rides along here rather than costing a second
    // request per menu. Existing consumers see one more key and are unaffected.
    // ⚠ This lands INSIDE the 10s cache above, so a count can be up to 10s stale. That is well
    // under the 20s poll and invisible on a "players in queue" line; a lobby scan of its own on
    // every request would not be worth the freshness.
    $out['lobbyStats'] = SWUMenuLobbyStats(SWUMenuLobbyRows());
    $out['lobbyLabels'] = [
        'pvp'   => SWUMenuStatLabel('pvp', $out['lobbyStats']),
        'multi' => SWUMenuStatLabel('multi', $out['lobbyStats']),
    ];
    $json = json_encode($out);
    if (function_exists('apcu_store')) apcu_store($cacheKey, $json, 10);
    header('Content-Type: application/json');
    header('Cache-Control: no-store');
    echo $json;
}

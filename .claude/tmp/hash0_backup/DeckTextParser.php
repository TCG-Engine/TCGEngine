<?php

/**
 * Builds a lowercase-name → first-matching-ID index from $nameData.
 * Called once per request and cached in a static.
 */
function GetNameToIDIndex() {
    global $nameData;
    static $index = null;
    if ($index !== null) return $index;
    $index = [];
    if (!is_array($nameData)) {
        return $index;
    }
    foreach ($nameData as $id => $name) {
        $key = strtolower(trim($name));
        if (!isset($index[$key])) {
            $index[$key] = $id;
        }
    }
    return $index;
}

/**
 * Returns the canonical card ID for the given card name, or null if not found.
 * Matching is case-insensitive.
 */
function CardNameToID($cardName) {
    $index = GetNameToIDIndex();
    $key = strtolower(trim($cardName));
    return $index[$key] ?? null;
}

/**
 * Parses a free-text SWU deck list and returns:
 *   [
 *     'leader'    => string cardID (first leader line found),
 *     'base'      => string cardID (first base line found),
 *     'mainDeck'  => [ cardID, ... ],
 *     'sideboard' => [ cardID, ... ],
 *     'unresolved'=> [ 'token', ... ],
 *   ]
 *
 * Supported section headers (case-insensitive, with or without leading # or trailing :):
 *   Leader / Leader:
 *   Base / Base:
 *   Deck / Main Deck / Main / Main:
 *   Sideboard / Sideboard:
 *
 * Card lines:  [N[x]] <name or SWU card ID>  [(<SET_NNN>)]
 *   e.g.  "3 K-2SO"   "1x Sabine Wren (SOR_014)"   "SOR_014"
 */
function ParseFreeTextDeck($text) {
    $leader    = '';
    $base      = '';
    $mainDeck  = [];
    $sideboard = [];
    $unresolved = [];

    $section = 'main';

    $lines = preg_split('/\r?\n/', $text);
    foreach ($lines as $raw) {
        $line = trim($raw);
        if ($line === '') continue;

        // Section headers
        if (preg_match('/^#?\s*leader\s*:?\s*$/i', $line))                   { $section = 'leader';    continue; }
        if (preg_match('/^#?\s*base\s*:?\s*$/i', $line))                     { $section = 'base';      continue; }
        if (preg_match('/^#?\s*sideboard\s*:?\s*$/i', $line))                { $section = 'sideboard'; continue; }
        if (preg_match('/^#?\s*(deck|main(\s+deck)?)\s*:?\s*$/i', $line))    { $section = 'main';      continue; }
        if ($line[0] === '#') continue;

        // Parse quantity + token
        $quantity = 1;
        $token    = $line;
        if (preg_match('/^(\d+)x?\s+(.+)$/', $line, $m)) {
            $quantity = intval($m[1]);
            $token    = trim($m[2]);
        }

        // Strip trailing parenthetical set ID, e.g. "(SOR_014)"
        $setId = '';
        if (preg_match('/\(([A-Z]{2,5}_\d+)\)\s*$/', $token, $sm)) {
            $setId = $sm[1];
            $token = trim(substr($token, 0, -strlen($sm[0])));
        }

        // Resolve card: explicit set ID → bare token as ID → name lookup
        $cardID = null;
        if ($setId !== '' && IsSWUCardID($setId))    $cardID = $setId;
        elseif (IsSWUCardID($token))                 $cardID = $token;
        else                                         $cardID = CardNameToID($token);

        if ($cardID === null) {
            if (!in_array($token, $unresolved)) $unresolved[] = $token;
            continue;
        }

        for ($i = 0; $i < $quantity; ++$i) {
            switch ($section) {
                case 'leader':    if ($leader === '') $leader = $cardID; break;
                case 'base':      if ($base   === '') $base   = $cardID; break;
                case 'sideboard': $sideboard[] = $cardID; break;
                default:          $mainDeck[]  = $cardID;
            }
        }
    }

    return [
        'leader'     => $leader,
        'base'       => $base,
        'mainDeck'   => $mainDeck,
        'sideboard'  => $sideboard,
        'unresolved' => $unresolved,
    ];
}

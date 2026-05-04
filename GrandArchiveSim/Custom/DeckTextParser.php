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
    return isset($index[$key]) ? $index[$key] : null;
}

/**
 * Parses a free-text deck list and returns an associative array:
 *   [
 *     'material' => [ cardID, ... ],
 *     'mainDeck' => [ cardID, ... ],
 *     'sideboard' => [ cardID, ... ],
 *     'unresolved' => [ 'cardName', ... ],   // names that couldn't be matched
 *   ]
 *
 * Supported section headers (case-insensitive):
 *   # Material Deck  /  # Material
 *   # Main Deck      /  # Main
 *   # Sideboard
 *
 * Card lines:  <qty> <card name>   e.g.  4 Fireball
 */
function ParseFreeTextDeck($text) {
    $material   = [];
    $mainDeck   = [];
    $sideboard  = [];
    $unresolved = [];

    $currentSection = 'main'; // default if no header seen

    $lines = preg_split('/\r?\n/', $text);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '') continue;

        // Section headers
        if (preg_match('/^#\s*material(\s+deck)?\s*$/i', $line)) {
            $currentSection = 'material';
            continue;
        }
        if (preg_match('/^#\s*main(\s+deck)?\s*$/i', $line)) {
            $currentSection = 'main';
            continue;
        }
        if (preg_match('/^#\s*sideboard\s*$/i', $line)) {
            $currentSection = 'sideboard';
            continue;
        }
        if ($line[0] === '#') continue; // ignore other comment lines

        // Card line: optional "SBx" prefix (some export formats), qty, card name
        if (preg_match('/^(?:SB:\s*)?(\d+)\s+(.+)$/', $line, $m)) {
            $quantity = intval($m[1]);
            $cardName = trim($m[2]);
            $cardID   = CardNameToID($cardName);

            if ($cardID === null) {
                if (!in_array($cardName, $unresolved)) {
                    $unresolved[] = $cardName;
                }
                continue;
            }

            for ($i = 0; $i < $quantity; ++$i) {
                if ($currentSection === 'material') {
                    $material[] = $cardID;
                } elseif ($currentSection === 'sideboard') {
                    $sideboard[] = $cardID;
                } else {
                    $mainDeck[] = $cardID;
                }
            }
        }
    }

    return [
        'material'   => $material,
        'mainDeck'   => $mainDeck,
        'sideboard'  => $sideboard,
        'unresolved' => $unresolved,
    ];
}

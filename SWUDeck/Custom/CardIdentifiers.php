<?php
/**
 * Helper functions for converting between card names and their internal identifiers.
 *
 * Every function here speaks SET_NNN ("SOR_033"). It used to speak FFG UIDs ("2579145458"),
 * because deck files and the dictionaries were UUID-keyed until the 2026-08-06 SET_NNN
 * migration. UUIDLookup() survives only as an EXISTENCE TEST — nothing here converts to a
 * UUID any more, and CardIDLookup() must not be called on a $titleData key, which is now
 * already a SET_NNN id (the call returns null and the lookup fails silently).
 */

// Include the necessary files for card dictionaries
include_once dirname(__FILE__) . '/../GeneratedCode/GeneratedCardDictionaries.php';
include_once dirname(__FILE__) . '/../../AppCore/SWU/CardNameMatch.php';

// Sets whose canonical card ID uses a 2-digit zero-padded number (e.g. "TS26_34"),
// matching the convention other deckbuilders use for this set, rather than the
// standard 3-digit padding ("SOR_034"). Deck sources may still send 1, 3, or 4
// digits (un-padded, or padded to the standard width) — normalize down to 2.
$doubleDigitsSets = ['TS26'];

/**
 * Canonicalizes a card ID's numeric suffix to the width its set expects — 2 digits
 * for sets in $doubleDigitsSets, unchanged otherwise — so it matches UUIDLookup's
 * dictionary keys regardless of how the source padded it.
 *
 * @param string|null $cardID
 * @return string|null
 */
function NormalizeCardID($cardID) {
    global $doubleDigitsSets;
    if ($cardID === null || $cardID === '') return $cardID;
    if (preg_match('/^([A-Za-z0-9]+)_(\d+)$/', $cardID, $m)
        && in_array($m[1], $doubleDigitsSets, true)) {
        return $m[1] . '_' . str_pad(ltrim($m[2], '0') ?: '0', 2, '0', STR_PAD_LEFT);
    }
    return $cardID;
}

/**
 * Resolves an imported card identifier to the canonical SET_NNN id that deck files and
 * ownership.keyIndicator1/2/3 store, or null if the dictionary does not know it.
 *
 * Import paths used to end in UUIDLookup(), which STORED the UUID — that is what kept
 * re-introducing the old identity into deck files after the migration rewrote them. The
 * lookup remains, but only to decide whether the id is real: an unknown one must be skipped
 * rather than pushed into a zone as a blank CardID, which renders as a broken card image.
 *
 * @param string|null $cardID A SET_NNN id from an export, importer, or override table
 * @return string|null The same id, normalized — or null if it is not a known card
 */
function SWUDeckImportCardID($cardID) {
    $cardID = NormalizeCardID($cardID);
    if ($cardID === null || $cardID === '') return null;
    return UUIDLookup($cardID) === null ? null : $cardID;
}

/**
 * Converts a card name to its internal SET_NNN card id
 *
 * @param string $cardName The name of the card to look up
 * @return array Array of matching SET_NNN card ids
 */
function FindCard($cardName) {
    $cardName = trim($cardName);
    $cardName = str_replace('_', '', $cardName);
    
    // Special handling for melee.gg's pipe format (Character | Subtitle)
    if(strpos($cardName, '|') !== false) {
        $parts = explode('|', $cardName);
        $characterName = trim($parts[0]);
        $subtitle = isset($parts[1]) ? trim($parts[1]) : '';
        
        // Try an exact dictionary hit on the character name
        $id = substr_replace(strtoupper($characterName), '_', 3, 0);
        if(UUIDLookup($id) != null) {
            return [ $id ];
        }

        // Try searching for the full name or subtitle separately
        $id = substr_replace(strtoupper($cardName), '_', 3, 0);
        if(UUIDLookup($id) != null) {
            return [ $id ];
        }
        
        // Try searching for variations without the subtitle
        $cardName = $characterName;
    }
    
    $id = substr_replace(strtoupper($cardName), '_', 3, 0);
    if(UUIDLookup($id) != null) {
        return [ $id ];
    }
    else {
        // Exact titles first, substring hits after — see RankCardTitleMatches.
        return RankCardTitleMatches($cardName);
    }
}

// Name matching lives in AppCore/SWU/CardNameMatch.php so SWUSim resolves melee.gg names the
// same way; these keep SWUDeck's historical names. See that file for the ranking tiers and the
// Zeb Orellios alias history.

/**
 * Every $titleData entry matching $cardName, best match FIRST.
 *
 * @param string $cardName
 * @return array SET_NNN ids, best first
 */
function RankCardTitleMatches($cardName) {
    return SWURankCardTitleMatches($cardName);
}

/**
 * Handle common card nicknames
 *
 * @param string $cardName The nickname or card name
 * @return string The standardized card name
 */
function CardNicknames($cardName) {
    return SWUCardNameAlias($cardName);
}

/**
 * Find card set code from card name
 *
 * @param string $cardName The name of the card to look up
 * @return string|null The card set code or null if not found
 */
function FindCardSetCode($cardName) {
    return SWUFindCardIdByName($cardName);
}

/**
 * Find matching cards by name
 * 
 * @param string $cardName The name of the card to look up
 * @return array Array of matching card set codes
 */
function FindCardMatches($cardName) {
    // Exact titles first, substring hits after — see RankCardTitleMatches.
    return RankCardTitleMatches($cardName);
}

/**
 * Find the internal SET_NNN card id for a leader name
 *
 * Named GetLeaderUUID until 2026-08-06. It returned a mix of UUIDs and SET_NNN ids by then —
 * the $titleData branches already yielded SET_NNN while the FindCardSetCode branches converted
 * to a UUID — and its result is written to meleetournamentdeck.leader, one of the tables the
 * SET_NNN migration re-keyed. Every branch now returns SET_NNN.
 *
 * @param string $leaderName The name of the leader (e.g. "Jango Fett, Concealing the Conspiracy")
 * @return string|null The SET_NNN id of the leader card or null if not found
 */
function GetLeaderCardID($leaderName) {
    if(empty($leaderName)) return null;

    // Debug: log the original input via error_log only (no more file logging)
    error_log("GetLeaderCardID input: '$leaderName'");
    
    // Load the title and subtitle data for direct lookup
    global $titleData, $subtitleData;
    
    // Method 1: Try exact match with combined name
    foreach ($titleData as $cardID => $title) {
        if (isset($subtitleData[$cardID])) {
            $fullName = "$title, $subtitleData[$cardID]";
            if (strtolower($fullName) === strtolower($leaderName)) {
                return $cardID;
            }
        }
    }
    
    // Method 2: Try with the set code with the full name
    $leaderSetCode = FindCardSetCode($leaderName);
    if($leaderSetCode !== null && UUIDLookup($leaderSetCode) !== null) {
        return $leaderSetCode;
    }
    
    // Method 3: Parse name and subtitle and try to match them separately
    if(strpos($leaderName, ',') !== false) {
        $parts = explode(',', $leaderName);
        $characterName = trim($parts[0]);
        $subtitle = isset($parts[1]) ? trim($parts[1]) : '';
        
        // Look for exact matches on character name and subtitle
        foreach ($titleData as $cardID => $title) {
            if (strtolower($title) === strtolower($characterName)) {
                if (isset($subtitleData[$cardID])) {
                    if (strtolower($subtitleData[$cardID]) === strtolower($subtitle)) {
                        return $cardID;
                    }
                }
            }
        }
    }
    
    // Method 4: Try pipe format
    if(strpos($leaderName, ',') !== false) {
        $pipeFormat = str_replace(',', ' | ', $leaderName);
        $leaderSetCode = FindCardSetCode($pipeFormat);
        if($leaderSetCode !== null && UUIDLookup($leaderSetCode) !== null) {
            return $leaderSetCode;
        }
    }
    
    // Method 5: Try just the character name (before the comma)
    if(strpos($leaderName, ',') !== false) {
        $baseCharacterName = trim(explode(',', $leaderName)[0]);
        
        // Try with just the base character name via set code
        $leaderSetCode = FindCardSetCode($baseCharacterName);
        if($leaderSetCode !== null && UUIDLookup($leaderSetCode) !== null) {
            return $leaderSetCode;
        }

        // Try direct name lookup for the base character
        foreach ($titleData as $cardID => $title) {
            if (strtolower($title) === strtolower($baseCharacterName)) {
                return $cardID;
            }
        }
    }
    
    // Method 6: Try direct dictionary lookup
    $matches = FindCard($leaderName);
    if(count($matches) > 0) {
        return $matches[0];
    }

    // Method 7: Fuzzy search - try to match any part of the name
    foreach ($titleData as $cardID => $title) {
        // Check if the leader name contains the title or vice versa
        if (stripos($leaderName, $title) !== false || stripos($title, $leaderName) !== false) {
            return $cardID;
        }

        // Also check with the subtitle if available
        if (isset($subtitleData[$cardID])) {
            $fullName = "$title, $subtitleData[$cardID]";
            if (stripos($leaderName, $title) !== false || stripos($fullName, $leaderName) !== false) {
                return $cardID;
            }
        }
    }

    error_log("No card id found for leader: '$leaderName'");
    return null;
}

/**
 * Find the internal SET_NNN card id for a base name
 *
 * Named GetBaseUUID until 2026-08-06 — see GetLeaderCardID for why this now returns SET_NNN.
 *
 * @param string $baseName The name of the base (e.g. "Death Watch Hideout")
 * @return string|null The SET_NNN id of the base card or null if not found
 */
function GetBaseCardID($baseName) {
    if(empty($baseName)) return null;

    // First try to get the set code
    $baseSetCode = FindCardSetCode($baseName);

    // FindCardSetCode already returns a SET_NNN id; UUIDLookup only confirms it is a real card.
    if($baseSetCode !== null && UUIDLookup($baseSetCode) !== null) {
        return $baseSetCode;
    }

    // If not found by set code, try direct dictionary lookup
    $matches = FindCard($baseName);
    if(count($matches) > 0) {
        return $matches[0];
    }
    
    return null;
}
?>
<?php
/**
 * Helper functions for converting between card names and their internal identifiers
 */

// Include the necessary files for card dictionaries
include_once dirname(__FILE__) . '/../GeneratedCode/GeneratedCardDictionaries.php';

/**
 * Converts a card name to its internal UUID
 * 
 * @param string $cardName The name of the card to look up
 * @return array Array of matching UUIDs
 */
function FindCard($cardName) {
    $cardName = trim($cardName);
    $cardName = str_replace('_', '', $cardName);
    
    // Special handling for melee.gg's pipe format (Character | Subtitle)
    if(strpos($cardName, '|') !== false) {
        $parts = explode('|', $cardName);
        $characterName = trim($parts[0]);
        $subtitle = isset($parts[1]) ? trim($parts[1]) : '';
        
        // Try exact UUID lookup with the character name
        $uuid = UUIDLookup(substr_replace(strtoupper($characterName), '_', 3, 0));
        if($uuid != null) {
            return [ $uuid ];
        }
        
        // Try searching for the full name or subtitle separately
        $uuid = UUIDLookup(substr_replace(strtoupper($cardName), '_', 3, 0));
        if($uuid != null) {
            return [ $uuid ];
        }
        
        // Try searching for variations without the subtitle
        $cardName = $characterName;
    }
    
    $uuid = UUIDLookup(substr_replace(strtoupper($cardName), '_', 3, 0));
    if($uuid != null) {
        return [ $uuid ];
    }
    else {
        $cardName = strtolower(CardNicknames($cardName));
        global $titleData;
        $matches = [];
        foreach ($titleData as $uuid => $title) {
            if (stripos($title, $cardName) !== false) {
                $matches[] = $uuid;
            }
        }
        
        // If no matches found, try normalizing further - removing apostrophes, etc.
        if(count($matches) == 0) {
            $normalizedCardName = preg_replace('/[^a-zA-Z0-9]/', '', strtolower($cardName));
            foreach ($titleData as $uuid => $title) {
                $normalizedTitle = preg_replace('/[^a-zA-Z0-9]/', '', strtolower($title));
                if (stripos($normalizedTitle, $normalizedCardName) !== false) {
                    $matches[] = $uuid;
                }
            }
        }
        
        return $matches;
    }
}

/**
 * Handle common card nicknames
 * 
 * @param string $cardName The nickname or card name
 * @return string The standardized card name
 */
function CardNicknames($cardName) {
    switch($cardName) {
        case "chewie":
            return "Chewbacca";
        case "flyboy":
            return "Han Solo";
        case "threepio":
            return "C-3PO";
        case "artoo":
            return "R2-D2";
        case "beebee":
            return "BB-8";
        case "baby yoda":
            return "Grogu";
        case "uwing":
            return "U-Wing Reinforcements";
        default: 
            return $cardName;
    }
}

/**
 * Find card set code from card name
 * 
 * @param string $cardName The name of the card to look up
 * @return string|null The card set code or null if not found
 */
function FindCardSetCode($cardName) {
    $cardName = trim($cardName);
    
    // Special handling for melee.gg's pipe format (Character | Subtitle)
    if(strpos($cardName, '|') !== false) {
        $parts = explode('|', $cardName);
        $characterName = trim($parts[0]);
        $subtitle = isset($parts[1]) ? trim($parts[1]) : '';
        
        // First try to find an exact match with both title and subtitle
        global $titleData, $subtitleData;
        foreach ($titleData as $uuid => $title) {
            if (strtolower($title) == strtolower($characterName)) {
                // Found a match for the title, check if subtitle matches
                if (isset($subtitleData[$uuid])) {
                    $cardSubtitle = $subtitleData[$uuid];
                    if (strtolower($cardSubtitle) == strtolower($subtitle)) {
                        // Found exact match for both title and subtitle
                        return CardIDLookup($uuid);
                    }
                }
            }
        }
        
        // If no exact match found, try with just the character name
        $matches = FindCardMatches($characterName);
        if(count($matches) > 0) {
            return $matches[0];
        }
    } else {
        // Try to find a match for the card name
        $matches = FindCardMatches($cardName);
        if(count($matches) > 0) {
            return $matches[0];
        }
    }
    
    // Still not found, try more aggressive normalization
    $normalizedCardName = preg_replace('/[^a-zA-Z0-9]/', '', strtolower($cardName));
    global $titleData;
    foreach ($titleData as $uuid => $title) {
        $normalizedTitle = preg_replace('/[^a-zA-Z0-9]/', '', strtolower($title));
        if (stripos($normalizedTitle, $normalizedCardName) !== false) {
            return CardIDLookup($uuid); // Return set code instead of UUID
        }
    }
    
    return null;
}

/**
 * Find matching cards by name
 * 
 * @param string $cardName The name of the card to look up
 * @return array Array of matching card set codes
 */
function FindCardMatches($cardName) {
    $cardName = strtolower(CardNicknames($cardName));
    global $titleData;
    $matches = [];
    foreach ($titleData as $uuid => $title) {
        if (stripos(strtolower($title), $cardName) !== false) {
            $matches[] = CardIDLookup($uuid); // Return set code instead of UUID
        }
    }
    return $matches;
}

/**
 * Find the internal UUID for a leader name
 * 
 * @param string $leaderName The name of the leader (e.g. "Jango Fett, Concealing the Conspiracy")
 * @return string|null The internal UUID of the leader card or null if not found
 */
function GetLeaderUUID($leaderName) {
    if(empty($leaderName)) return null;
    
    // Debug: log the original input via error_log only (no more file logging)
    error_log("GetLeaderUUID input: '$leaderName'");
    
    // Load the title and subtitle data for direct lookup
    global $titleData, $subtitleData;
    
    // Method 1: Try exact match with combined name
    foreach ($titleData as $uuid => $title) {
        if (isset($subtitleData[$uuid])) {
            $fullName = "$title, $subtitleData[$uuid]";
            if (strtolower($fullName) === strtolower($leaderName)) {
                return $uuid;
            }
        }
    }
    
    // Method 2: Try with the set code with the full name
    $leaderSetCode = FindCardSetCode($leaderName);
    if($leaderSetCode !== null) {
        $uuid = UUIDLookup($leaderSetCode);
        if ($uuid) {
            return $uuid;
        }
    }
    
    // Method 3: Parse name and subtitle and try to match them separately
    if(strpos($leaderName, ',') !== false) {
        $parts = explode(',', $leaderName);
        $characterName = trim($parts[0]);
        $subtitle = isset($parts[1]) ? trim($parts[1]) : '';
        
        // Look for exact matches on character name and subtitle
        foreach ($titleData as $uuid => $title) {
            if (strtolower($title) === strtolower($characterName)) {
                if (isset($subtitleData[$uuid])) {
                    if (strtolower($subtitleData[$uuid]) === strtolower($subtitle)) {
                        return $uuid;
                    }
                }
            }
        }
    }
    
    // Method 4: Try pipe format
    if(strpos($leaderName, ',') !== false) {
        $pipeFormat = str_replace(',', ' | ', $leaderName);
        $leaderSetCode = FindCardSetCode($pipeFormat);
        if($leaderSetCode !== null) {
            $uuid = UUIDLookup($leaderSetCode);
            if ($uuid) {
                return $uuid;
            }
        }
    }
    
    // Method 5: Try just the character name (before the comma)
    if(strpos($leaderName, ',') !== false) {
        $baseCharacterName = trim(explode(',', $leaderName)[0]);
        
        // Try with just the base character name via set code
        $leaderSetCode = FindCardSetCode($baseCharacterName);
        if($leaderSetCode !== null) {
            $uuid = UUIDLookup($leaderSetCode);
            if ($uuid) {
                return $uuid;
            }
        }
        
        // Try direct name lookup for the base character
        foreach ($titleData as $uuid => $title) {
            if (strtolower($title) === strtolower($baseCharacterName)) {
                return $uuid;
            }
        }
    }
    
    // Method 6: Try direct UUID lookup
    $matches = FindCard($leaderName);
    if(count($matches) > 0) {
        return $matches[0];
    }
    
    // Method 7: Fuzzy search - try to match any part of the name
    foreach ($titleData as $uuid => $title) {
        // Check if the leader name contains the title or vice versa
        if (stripos($leaderName, $title) !== false || stripos($title, $leaderName) !== false) {
            return $uuid;
        }
        
        // Also check with the subtitle if available
        if (isset($subtitleData[$uuid])) {
            $fullName = "$title, $subtitleData[$uuid]";
            if (stripos($leaderName, $title) !== false || stripos($fullName, $leaderName) !== false) {
                return $uuid;
            }
        }
    }
    
    error_log("No UUID found for leader: '$leaderName'");
    return null;
}

/**
 * Find the internal UUID for a base name
 * 
 * @param string $baseName The name of the base (e.g. "Death Watch Hideout")
 * @return string|null The internal UUID of the base card or null if not found
 */
function GetBaseUUID($baseName) {
    if(empty($baseName)) return null;
    
    // First try to get the set code
    $baseSetCode = FindCardSetCode($baseName);
    
    // If found, convert to UUID
    if($baseSetCode !== null) {
        return UUIDLookup($baseSetCode);
    }
    
    // If not found by set code, try direct UUID lookup
    $matches = FindCard($baseName);
    if(count($matches) > 0) {
        return $matches[0];
    }
    
    return null;
}
?>
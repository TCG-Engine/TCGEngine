<?php

include_once __DIR__ . '/../GeneratedCode/GeneratedCardDictionaries.php';
include_once __DIR__ . '/DeckTextParser.php';
include_once __DIR__ . '/../../SWUDeck/Overrides.php'; // CardIDOverride — reprint → earliest printing

// Sets whose card abilities the sim actually implements. A reprint printed only
// in a non-implemented set (e.g. SHD/TWI promos) must be aliased to one of these
// so the engine fires the real ability. SOR is implemented though not Premier-legal.
const SWUImplementedSets = ['SOR', 'JTL', 'LOF', 'SEC', 'IBH', 'LAW', 'ASH'];

// Short set prefix of a SET_NNN card ID.
function SWUCardSet($cardID) {
    return strtoupper(explode('_', (string)$cardID)[0] ?? '');
}

// Every known printing that shares a canonical (earliest) printing with $cardID,
// including $cardID and the canonical itself. Built once by inverting
// CardIDOverride over the full card dictionary — the single source of reprint
// relationships shared with SWUDeck stats.
function SWUReprintGroup($cardID) {
    static $groups = null;
    if ($groups === null) {
        global $titleData;
        $groups = [];
        $ids = is_array($titleData) ? array_keys($titleData) : [];
        foreach ($ids as $id) {
            $groups[CardIDOverride($id)][] = $id;
        }
    }
    $canon = CardIDOverride($cardID);
    $group = $groups[$canon] ?? [];
    if (!in_array($cardID, $group, true)) $group[] = $cardID;
    if (!in_array($canon,  $group, true)) $group[] = $canon;
    return array_values(array_unique($group));
}

// Pick a printing of $cardID that the sim implements. Keeps the given printing
// when its set is implemented; otherwise prefers the canonical, then any reprint
// in an implemented set; falls back to the original if none exists.
function SWUResolveToImplementedPrint($cardID) {
    if ($cardID === '' || $cardID === null) return $cardID;
    if (in_array(SWUCardSet($cardID), SWUImplementedSets, true)) return $cardID;
    $canon = CardIDOverride($cardID);
    if (in_array(SWUCardSet($canon), SWUImplementedSets, true)) return $canon;
    foreach (SWUReprintGroup($cardID) as $print) {
        if (in_array(SWUCardSet($print), SWUImplementedSets, true)) return $print;
    }
    return $cardID;
}

// True when $cardID — by any of its printings — appears in one of $legalSets.
// Lets a deck list an older/alternate printing of a card that is Premier-legal
// via a reprint (e.g. SHD_030 Death Trooper is legal because SEC_030 is).
function SWUCardHasLegalPrint($cardID, array $legalSets) {
    foreach (SWUReprintGroup($cardID) as $print) {
        if (in_array(SWUCardSet($print), $legalSets, true)) return true;
    }
    return false;
}

/**
 * Validate a deck link or paste without fully loading the deck.
 */
function SWUValidateDeckForQueue($deckLink, $preconstructedDeck = '') {
    if (!empty($preconstructedDeck)) {
        return ['success' => true, 'message' => ''];
    }
    $deckLink = trim($deckLink);
    if ($deckLink === '') {
        return ['success' => false, 'message' => 'Deck link is required.'];
    }
    $resolved = SWUResolveDeckInput($deckLink);
    return [
        'success' => $resolved['success'],
        'message' => $resolved['success'] ? '' : $resolved['message'],
    ];
}

/**
 * Resolve a deck from a URL, JSON string, or free-text paste.
 *
 * Returns:
 *   success    bool
 *   message    string   (error description on failure)
 *   leader     string   card ID
 *   base       string   card ID
 *   mainDeck   string[] expanded card IDs (one entry per copy)
 *   sideboard  string[] expanded card IDs
 *   unresolved string[] names/IDs that could not be resolved
 */
function SWUResolveDeckInput($deckLink) {
    $deckLink = trim($deckLink);
    if ($deckLink === '') {
        return SWUDeckError('Deck link is required.');
    }

    // ── Standardized SWU JSON (direct paste or API response) ─────────────────
    if ($deckLink[0] === '{') {
        $data = json_decode($deckLink, true);
        if (is_array($data)) {
            return SWUNormalizeStandardJSON($data);
        }
        return SWUDeckError('Could not parse the pasted JSON. Please verify its format.');
    }

    // ── Free-text deck list (multi-line paste) ────────────────────────────────
    if (strpos($deckLink, "\n") !== false || strpos($deckLink, "\r") !== false) {
        return SWUParseFreeText($deckLink);
    }

    // ── SWUDeck URL (sibling project) ─────────────────────────────────────────
    // Example: https://swudeck.com/decks/<id>  or  swudeck.com/deck/<id>
    if (stripos($deckLink, 'swudeck.com') !== false) {
        return SWUImportFromSWUDeck($deckLink);
    }

    // ── SWUDB URL ─────────────────────────────────────────────────────────────
    // Example: https://swudb.com/deck/<id>
    if (stripos($deckLink, 'swudb.com') !== false) {
        return SWUImportFromSWUDB($deckLink);
    }

    return SWUDeckError('Unsupported deck format. Paste a deck list, JSON, or a SWUDeck / SWUDB URL.');
}

// ─── Source: standardized SWU JSON ───────────────────────────────────────────

/**
 * Normalize the standardized SWU JSON format:
 * {
 *   "metadata": { "name": "...", "author": "..." },
 *   "leader":   { "id": "SOR_014", "count": 1 },
 *   "base":     { "id": "SOR_022", "count": 1 },
 *   "deck":     [ { "id": "SHD_154", "count": 3 }, ... ],
 *   "sideboard":[ { "id": "SHD_262", "count": 2 }, ... ]
 * }
 */
// Accept a card ID if it passes the dictionary check OR matches the SET_NNN pattern.
// Deck sources like SWUDB already guarantee valid IDs; pure format matching is safe here.
function SWUIsAcceptableCardID($cardId) {
    if (function_exists('IsSWUCardID') && IsSWUCardID($cardId)) return true;
    return (bool)preg_match('/^[A-Z]{2,5}_\d{3,4}$/', $cardId);
}

function SWUNormalizeStandardJSON($data) {
    $leader    = '';
    $base      = '';
    $mainDeck  = [];
    $sideboard = [];
    $unresolved = [];

    // Leader
    $leaderId = trim((string)($data['leader']['id'] ?? ''));
    if ($leaderId !== '') {
        if (SWUIsAcceptableCardID($leaderId)) {
            $leader = $leaderId;
        } else {
            $unresolved[] = $leaderId;
        }
    }

    // Base
    $baseId = trim((string)($data['base']['id'] ?? ''));
    if ($baseId !== '') {
        if (SWUIsAcceptableCardID($baseId)) {
            $base = $baseId;
        } else {
            $unresolved[] = $baseId;
        }
    }

    // Main deck
    foreach (($data['deck'] ?? []) as $entry) {
        $cardId = trim((string)($entry['id'] ?? ''));
        $count  = intval($entry['count'] ?? 1);
        if ($cardId === '' || $count <= 0) continue;
        if (!SWUIsAcceptableCardID($cardId)) {
            if (!in_array($cardId, $unresolved)) $unresolved[] = $cardId;
            continue;
        }
        for ($i = 0; $i < $count; ++$i) $mainDeck[] = $cardId;
    }

    // Sideboard
    foreach (($data['sideboard'] ?? []) as $entry) {
        $cardId = trim((string)($entry['id'] ?? ''));
        $count  = intval($entry['count'] ?? 1);
        if ($cardId === '' || $count <= 0) continue;
        if (SWUIsAcceptableCardID($cardId)) {
            for ($i = 0; $i < $count; ++$i) $sideboard[] = $cardId;
        }
    }

    if ($leader === '' && $base === '' && empty($mainDeck)) {
        return SWUDeckError('The JSON did not contain any recognizable SWU cards.');
    }

    return SWUDeckSuccess($leader, $base, $mainDeck, $sideboard, $unresolved);
}

// ─── Source: SWUDeck ─────────────────────────────────────────────────────────

function SWUImportFromSWUDeck($url) {
    // Extract deck ID from URL.
    // Supported patterns:
    //   https://swudeck.com/decks/<id>
    //   https://swudeck.com/deck/<id>
    if (!preg_match('#/decks?/([a-zA-Z0-9_-]+)#i', $url, $m)) {
        return SWUDeckError('Could not extract a deck ID from the SWUDeck URL.');
    }
    $deckId = $m[1];

    // SWUDeck exposes its deck data as JSON — try the API endpoint.
    $apiUrl  = 'https://swudeck.com/api/decks/' . rawurlencode($deckId);
    $data    = SWUFetchDeckJson($apiUrl, ['Accept: application/json']);
    if (!is_array($data)) {
        return SWUDeckError('Could not load the SWUDeck deck. It may be private or unavailable.');
    }

    // SWUDeck returns the standardized JSON format directly.
    return SWUNormalizeStandardJSON($data);
}

// ─── Source: SWUDB ────────────────────────────────────────────────────────────

function SWUImportFromSWUDB($url) {
    // Extract deck ID from URL.
    // Supported patterns:
    //   https://swudb.com/deck/<id>
    //   https://www.swudb.com/deck/<id>
    if (!preg_match('#/deck/([a-zA-Z0-9_-]+)#i', $url, $m)) {
        return SWUDeckError('Could not extract a deck ID from the SWUDB URL.');
    }
    $deckId = $m[1];

    // getDeckJson returns SET_NNN card IDs directly — the correct endpoint for SWUSim.
    // (The /api/deck/ endpoint returns Strapi UUID-based IDs which are not usable here.)
    $apiUrl = 'https://swudb.com/api/getDeckJson/' . rawurlencode($deckId);
    $data   = SWUFetchDeckJson($apiUrl, ['Accept: application/json']);
    if (!is_array($data)) {
        return SWUDeckError('Could not load the SWUDB deck. It may be private or unavailable.');
    }

    return SWUNormalizeStandardJSON($data);
}

function SWUNormalizeSWUDBLegacy($data) {
    $leader    = trim((string)($data['leader_id'] ?? ($data['leaderId'] ?? '')));
    $base      = trim((string)($data['base_id']   ?? ($data['baseId']   ?? '')));
    $mainDeck  = [];
    $unresolved = [];

    foreach (($data['cards'] ?? []) as $entry) {
        $cardId   = trim((string)($entry['id'] ?? ($entry['card_id'] ?? '')));
        $quantity = intval($entry['quantity'] ?? ($entry['count'] ?? 1));
        if ($cardId === '' || $quantity <= 0) continue;
        if (!SWUIsAcceptableCardID($cardId)) {
            if (!in_array($cardId, $unresolved)) $unresolved[] = $cardId;
            continue;
        }
        for ($i = 0; $i < $quantity; ++$i) $mainDeck[] = $cardId;
    }

    if ($leader === '' && $base === '' && empty($mainDeck)) {
        return SWUDeckError('The SWUDB response did not contain recognizable SWU cards.');
    }

    return SWUDeckSuccess($leader, $base, $mainDeck, [], $unresolved);
}

// ─── Source: free-text paste ──────────────────────────────────────────────────

/**
 * Parse a free-text SWU deck list.
 *
 * Supported section headers (case-insensitive):
 *   Leader / Leader:
 *   Base / Base:
 *   Deck / Main Deck / Main:
 *   Sideboard:
 *
 * Card lines:  [N]  <name or ID>
 *   e.g.  "3 K-2SO"  or  "1x Sabine Wren (SOR_014)"  or  "SOR_014"
 */
function SWUParseFreeText($text) {
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
        if (preg_match('/^#?\s*(leader)\s*:?\s*$/i', $line)) { $section = 'leader'; continue; }
        if (preg_match('/^#?\s*(base)\s*:?\s*$/i', $line))   { $section = 'base';   continue; }
        if (preg_match('/^#?\s*(sideboard)\s*:?\s*$/i', $line)) { $section = 'sideboard'; continue; }
        if (preg_match('/^#?\s*(deck|main(\s+deck)?)\s*:?\s*$/i', $line)) { $section = 'main'; continue; }
        if ($line[0] === '#') continue; // other comment

        // Try to parse "N card name (optional set id)"
        // Patterns:
        //   "3 K-2SO"
        //   "3x K-2SO"
        //   "1 Sabine Wren (SOR_014)"
        //   "SOR_014"     (bare ID)
        //   "K-2SO"       (bare name)
        $quantity = 1;
        $token    = $line;

        if (preg_match('/^(\d+)x?\s+(.+)$/', $line, $m)) {
            $quantity = intval($m[1]);
            $token    = trim($m[2]);
        }

        // Strip parenthetical set reference, e.g. "(SOR_014)" at end
        $setId = '';
        if (preg_match('/\(([A-Z]{2,5}_\d+)\)\s*$/', $token, $sm)) {
            $setId = $sm[1];
            $token = trim(substr($token, 0, -strlen($sm[0])));
        }

        // Resolve: prefer explicit set ID, then card name lookup, then bare-token-as-ID
        $cardId = null;
        if ($setId !== '' && SWUIsAcceptableCardID($setId)) {
            $cardId = $setId;
        } elseif (SWUIsAcceptableCardID($token)) {
            $cardId = $token;
        } else {
            $cardId = CardNameToID($token);
        }

        if ($cardId === null) {
            if (!in_array($token, $unresolved)) $unresolved[] = $token;
            continue;
        }

        // Place into section
        for ($i = 0; $i < $quantity; ++$i) {
            switch ($section) {
                case 'leader':
                    if ($leader === '') $leader = $cardId;
                    break;
                case 'base':
                    if ($base === '') $base = $cardId;
                    break;
                case 'sideboard':
                    $sideboard[] = $cardId;
                    break;
                default:
                    $mainDeck[] = $cardId;
            }
        }
    }

    if ($leader === '' && $base === '' && empty($mainDeck)) {
        return SWUDeckError('Unable to parse the deck list. Please check the format.');
    }

    return SWUDeckSuccess($leader, $base, $mainDeck, $sideboard, $unresolved);
}

// ─── Helpers ─────────────────────────────────────────────────────────────────

if (!function_exists('IsSWUCardID')) {
function IsSWUCardID($cardId) {
    global $titleData;
    return is_array($titleData) && isset($titleData[$cardId]);
}
}

function SWUFetchDeckJson($url, $headers = []) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_MAXREDIRS, 3);
    if (!empty($headers)) {
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    }
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    $body = curl_exec($ch);
    curl_close($ch); // no-op on PHP 8.5+ but harmless on older versions

    if ($body === false || $body === '') return null;

    $decoded = json_decode($body, true);
    if (is_string($decoded)) $decoded = json_decode($decoded, true);
    return is_array($decoded) ? $decoded : null;
}

function SWUDeckSuccess($leader, $base, $mainDeck, $sideboard, $unresolved) {
    // Alias every printing to one the sim implements so reprints (incl. cards
    // printed only in non-implemented sets) play with their real abilities.
    return [
        'success'    => true,
        'message'    => '',
        'leader'     => SWUResolveToImplementedPrint($leader),
        'base'       => SWUResolveToImplementedPrint($base),
        'mainDeck'   => array_map('SWUResolveToImplementedPrint', $mainDeck),
        'sideboard'  => array_map('SWUResolveToImplementedPrint', $sideboard),
        'unresolved' => $unresolved,
    ];
}

function SWUDeckError($message) {
    return [
        'success'    => false,
        'message'    => $message,
        'leader'     => '',
        'base'       => '',
        'mainDeck'   => [],
        'sideboard'  => [],
        'unresolved' => [],
    ];
}

<?php

include_once __DIR__ . '/../GeneratedCode/GeneratedCardDictionaries.php';
include_once __DIR__ . '/DeckTextParser.php';
require_once __DIR__ . '/../Formats.php';

function GrandArchiveValidateDeckForQueue($deckLink, $preconstructedDeck = '', $format = 'standard') {
    if (!empty($preconstructedDeck)) {
        return ['success' => true, 'message' => '']; // preconstructed decks bypass validation
    }

    $deckLink = trim($deckLink);
    if ($deckLink === '') {
        return ['success' => false, 'message' => 'Deck link is required.'];
    }

    $resolved = GrandArchiveResolveDeckInput($deckLink);
    if (!$resolved['success']) {
        return ['success' => $resolved['success'], 'message' => $resolved['message']];
    }
    return GAValidateResolvedDeck($resolved, $format);
}

function GAIsChampion($uuid) {
    global $typeData;
    return strpos(strtoupper((string)($typeData[$uuid] ?? '')), 'CHAMPION') !== false;
}
function GAIsRegalia($uuid) {
    global $typeData;
    return strpos(strtoupper((string)($typeData[$uuid] ?? '')), 'REGALIA') !== false;
}
function GACountByName(array $uuids) {
    global $nameData;
    $c = [];
    foreach ($uuids as $u) { $nm = $nameData[$u] ?? $u; $c[$nm] = ($c[$nm] ?? 0) + 1; }
    return $c;
}

// Enforce banlist (per-format, UUID) + full constructed deckbuilding rules (CR §129–132).
// Mode-formats (goldfish) enforce only the banlist. Returns ['success'=>bool,'message'=>string].
function GAValidateResolvedDeck(array $resolved, string $format) {
    global $nameData, $levelData;
    $fmt = GAGetFormat($format) ?? GAGetFormat('standard');
    $label = $fmt['displayName'] ?? $format;

    $main       = $resolved['mainDeck'] ?? [];
    $material   = $resolved['material'] ?? [];
    $sideboard  = $resolved['sideboard'] ?? [];
    $unresolved = $resolved['unresolved'] ?? [];

    if (!empty($unresolved)) {
        return ['success' => false, 'message' =>
            'Unrecognized card(s): ' . implode(', ', array_slice($unresolved, 0, 10)) . '. Deck could not be validated.'];
    }

    // a. Banlist across all zones
    $banned = [];
    foreach (array_merge($main, $material, $sideboard) as $u) {
        if (GACardBanned($u, $format)) {
            $nm = $nameData[$u] ?? $u;
            if (!in_array($nm, $banned, true)) $banned[] = $nm;
        }
    }
    if (!empty($banned)) {
        return ['success' => false, 'message' => 'Banned in ' . $label . ': ' . implode(', ', $banned) . '.'];
    }

    // Mode-formats (goldfish) skip structural rules
    if (!empty($fmt['mode'])) {
        return ['success' => true, 'message' => ''];
    }

    $rules = GAConstructedDeckRules();

    // b. Main deck size + copy limit (main + sideboard combined, by name)
    if (count($main) < $rules['mainMin']) {
        return ['success' => false, 'message' => 'Main deck has ' . count($main) . ' cards; minimum is ' . $rules['mainMin'] . '.'];
    }
    foreach (GACountByName(array_merge($main, $sideboard)) as $nm => $n) {
        if ($n > $rules['mainMaxCopies']) {
            return ['success' => false, 'message' => "Too many copies of \"$nm\" ($n; max {$rules['mainMaxCopies']} across main + sideboard)."];
        }
    }

    // c. Material deck size + copy + Level-0 champion
    if (count($material) > $rules['materialMax']) {
        return ['success' => false, 'message' => 'Material deck has ' . count($material) . ' cards; maximum is ' . $rules['materialMax'] . '.'];
    }
    foreach (GACountByName($material) as $nm => $n) {
        if ($n > $rules['materialMaxCopies']) {
            return ['success' => false, 'message' => "Too many copies of \"$nm\" in material ($n; max {$rules['materialMaxCopies']})."];
        }
    }
    if ($rules['materialNeedsLv0Champion']) {
        $hasLv0 = false;
        foreach ($material as $u) {
            if (GAIsChampion($u) && intval($levelData[$u] ?? -1) === 0) { $hasLv0 = true; break; }
        }
        if (!$hasLv0) return ['success' => false, 'message' => 'Material deck must contain a Level 0 champion.'];
    }

    // d. Sideboard: card count + 15-point system + combined material+sideboard champ/regalia <=1/name
    if (count($sideboard) > $rules['sideboardMaxCards']) {
        return ['success' => false, 'message' => 'Sideboard has ' . count($sideboard) . ' cards; maximum is ' . $rules['sideboardMaxCards'] . '.'];
    }
    $points = 0;
    foreach ($sideboard as $u) {
        $points += (GAIsChampion($u) || GAIsRegalia($u)) ? $rules['sideboardChampionRegaliaPoints'] : 1;
    }
    if ($points > $rules['sideboardMaxPoints']) {
        return ['success' => false, 'message' => 'Sideboard is ' . $points . ' points; maximum is ' . $rules['sideboardMaxPoints'] . '.'];
    }
    $cr = [];
    foreach (array_merge($material, $sideboard) as $u) {
        if (GAIsChampion($u) || GAIsRegalia($u)) { $nm = $nameData[$u] ?? $u; $cr[$nm] = ($cr[$nm] ?? 0) + 1; }
    }
    foreach ($cr as $nm => $n) {
        if ($n > 1) return ['success' => false, 'message' => "Too many copies of champion/regalia \"$nm\" ($n; max 1 across material + sideboard)."];
    }

    return ['success' => true, 'message' => ''];
}

function GrandArchiveResolveDeckInput($deckLink) {
    global $tcgArchitectAPIKey;

    $deckLink = trim($deckLink);
    if ($deckLink === '') {
        return [
            'success' => false,
            'message' => 'Deck link is required.',
            'material' => [],
            'mainDeck' => [],
            'unresolved' => []
        ];
    }

    // Sleeved.gg import.
    if (stripos($deckLink, 'sleeved.gg') !== false) {
        $sleevedInfo = ExtractSleevedDeckInfo($deckLink);
        if (!$sleevedInfo['success']) {
            return [
                'success' => false,
                'message' => 'Deck link appears malformed. Please provide a valid Sleeved deck URL.',
                'material' => [],
                'mainDeck' => [],
                'unresolved' => []
            ];
        }

        $apiUrl = 'https://api.sleeved.gg/v1/decks/' . rawurlencode($sleevedInfo['deckId']) . '?format=json';
        if ($sleevedInfo['token'] !== '') {
            $apiUrl .= '&token=' . rawurlencode($sleevedInfo['token']);
        }

        $deckData = GrandArchiveFetchDeckJson($apiUrl, ['Accept: application/json']);
        $normalized = GrandArchiveNormalizeSleevedDeck($deckData);
        if (!$normalized['success']) {
            return [
                'success' => false,
                'message' => 'Could not load that Sleeved deck. The deck may be private, deleted, or unavailable.',
                'material' => [],
                'mainDeck' => [],
                'unresolved' => []
            ];
        }

        return $normalized;
    }

    // Free-text deck input.
    if (strpos($deckLink, "\n") !== false || strpos($deckLink, "\r") !== false) {
        $parsed = ParseFreeTextDeck($deckLink);
        $material = $parsed['material'] ?? [];
        $mainDeck = $parsed['mainDeck'] ?? [];
        if (count($material) + count($mainDeck) <= 0) {
            return [
                'success' => false,
                'message' => 'Unable to parse the pasted deck list. Please verify the list format and card names.',
                'material' => [],
                'mainDeck' => [],
                'sideboard' => [],
                'unresolved' => $parsed['unresolved'] ?? []
            ];
        }

        return [
            'success' => true,
            'message' => '',
            'material' => $material,
            'mainDeck' => $mainDeck,
            'sideboard' => $parsed['sideboard'] ?? [],
            'unresolved' => $parsed['unresolved'] ?? []
        ];
    }

    // ShoutAtYourDecks / DungeonGUI import.
    if (stripos($deckLink, 'shoutatyourdecks.com') !== false || stripos($deckLink, 'dungeongui.de') !== false) {
        if (!preg_match('/([0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12})/i', $deckLink, $matches)) {
            return [
                'success' => false,
                'message' => 'Deck link appears malformed. Please provide a valid ShoutAtYourDecks or DungeonGUI deck URL.',
                'material' => [],
                'mainDeck' => [],
                'unresolved' => []
            ];
        }

        $uuid = $matches[1];
        $apiUrl = 'https://shoutatyourdecks.com/api/' . $uuid;
        if (stripos($deckLink, 'dungeongui.de') !== false) {
            $apiUrl = 'https://dungeongui.de/deckbuilder/json/' . $uuid;
        }

        $deckData = GrandArchiveFetchDeckJson($apiUrl, ['Accept: application/json']);
        $normalized = GrandArchiveNormalizeShoutLikeDeck($deckData);
        if (!$normalized['success']) {
            return [
                'success' => false,
                'message' => 'Could not load that deck link. The deck may be private, deleted, or unavailable.',
                'material' => [],
                'mainDeck' => [],
                'unresolved' => []
            ];
        }

        return $normalized;
    }

    // TCGArchitect import (UUID URL or bare UUID).
    if (!preg_match('/([0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12})/i', $deckLink, $matches)) {
        return [
            'success' => false,
            'message' => 'Deck link must be a valid UUID, supported deck URL, or pasted deck list.',
            'material' => [],
            'mainDeck' => [],
            'unresolved' => []
        ];
    }

    $uuid = $matches[1];
    if (!isset($tcgArchitectAPIKey)) {
        $apiKeysPath = __DIR__ . '/../../APIKeys/APIKeys.php';
        if (is_file($apiKeysPath)) {
            include_once $apiKeysPath;
        }
    }
    $headers = ['Accept: application/json'];
    if (isset($tcgArchitectAPIKey) && trim($tcgArchitectAPIKey) !== '') {
        $headers[] = 'x-api-key: ' . $tcgArchitectAPIKey;
    }

    $apiUrl = 'https://api.tcgarchitect.com/api/decks/' . $uuid;
    $deckData = GrandArchiveFetchDeckJson($apiUrl, $headers);
    $normalized = GrandArchiveNormalizeTCGArchitectDeck($deckData);
    if (!$normalized['success']) {
        return [
            'success' => false,
            'message' => 'Could not load that deck link. It may be private, invalid, or unavailable.',
            'material' => [],
            'mainDeck' => [],
            'unresolved' => []
        ];
    }

    return $normalized;
}

function GrandArchiveFetchDeckJson($url, $headers = []) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    if (!empty($headers)) {
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    }
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    $apiResponse = curl_exec($ch);
    curl_close($ch);

    if ($apiResponse === false) {
        return null;
    }

    $decoded = json_decode($apiResponse, true);
    if (is_string($decoded)) {
        $decoded = json_decode($decoded, true);
    }

    return is_array($decoded) ? $decoded : null;
}

function GrandArchiveNormalizeShoutLikeDeck($deckData) {
    if (!is_array($deckData) || !isset($deckData['cards']) || !is_array($deckData['cards'])) {
        return [
            'success' => false,
            'message' => '',
            'material' => [],
            'mainDeck' => [],
            'unresolved' => []
        ];
    }

    $material = [];
    $mainDeck = [];

    foreach (($deckData['cards']['material'] ?? []) as $card) {
        $cardID = $card['uuid'] ?? '';
        $quantity = intval($card['quantity'] ?? 0);
        if ($cardID === '' || $quantity <= 0) continue;
        for ($i = 0; $i < $quantity; ++$i) {
            $material[] = $cardID;
        }
    }

    foreach (($deckData['cards']['main'] ?? []) as $card) {
        $cardID = $card['uuid'] ?? '';
        $quantity = intval($card['quantity'] ?? 0);
        if ($cardID === '' || $quantity <= 0) continue;
        for ($i = 0; $i < $quantity; ++$i) {
            $mainDeck[] = $cardID;
        }
    }

    if (count($material) + count($mainDeck) <= 0) {
        return [
            'success' => false,
            'message' => '',
            'material' => [],
            'mainDeck' => [],
            'unresolved' => []
        ];
    }

    return [
        'success' => true,
        'message' => '',
        'material' => $material,
        'mainDeck' => $mainDeck,
        'sideboard' => [],   // ShoutLike source exposes no sideboard section
        'unresolved' => []
    ];
}

function GrandArchiveNormalizeTCGArchitectDeck($deckData) {
    if (!is_array($deckData) || !isset($deckData['cards']) || !is_array($deckData['cards'])) {
        return [
            'success' => false,
            'message' => '',
            'material' => [],
            'mainDeck' => [],
            'unresolved' => []
        ];
    }

    $material = [];
    $mainDeck = [];
    $sideboard = [];

    foreach ($deckData['cards'] as $card) {
        $cardID = $card['id'] ?? '';
        $quantity = intval($card['pivot']['quantity'] ?? 0);
        $deckType = strtolower(trim($card['pivot']['deck_type'] ?? 'main'));

        if ($cardID === '' || $quantity <= 0) continue;

        for ($i = 0; $i < $quantity; ++$i) {
            if ($deckType === 'material') {
                $material[] = $cardID;
            } elseif ($deckType === 'sideboard') {
                $sideboard[] = $cardID;
            } else {
                $mainDeck[] = $cardID;
            }
        }
    }

    if (count($material) + count($mainDeck) <= 0) {
        return [
            'success' => false,
            'message' => '',
            'material' => [],
            'mainDeck' => [],
            'sideboard' => [],
            'unresolved' => []
        ];
    }

    return [
        'success' => true,
        'message' => '',
        'material' => $material,
        'mainDeck' => $mainDeck,
        'sideboard' => $sideboard,
        'unresolved' => []
    ];
}

function ExtractSleevedDeckInfo($deckLink) {
    $deckLink = trim((string)$deckLink);
    $parsed = parse_url($deckLink);
    if (!is_array($parsed)) {
        return ['success' => false, 'deckId' => '', 'token' => ''];
    }

    $host = strtolower((string)($parsed['host'] ?? ''));
    if ($host === '' || strpos($host, 'sleeved.gg') === false) {
        return ['success' => false, 'deckId' => '', 'token' => ''];
    }

    $path = (string)($parsed['path'] ?? '');
    $deckId = '';
    if (preg_match('#/decks/([^/?]+)#i', $path, $matches)) {
        $deckId = urldecode($matches[1]);
    }
    if ($deckId === '') {
        return ['success' => false, 'deckId' => '', 'token' => ''];
    }

    $token = '';
    if (isset($parsed['query'])) {
        parse_str($parsed['query'], $query);
        $token = trim((string)($query['token'] ?? ''));
    }

    return [
        'success' => true,
        'deckId' => $deckId,
        'token' => $token,
    ];
}

function GrandArchiveNormalizeSleevedDeck($deckData) {
    if (!is_array($deckData) || !isset($deckData['data']) || !is_array($deckData['data'])) {
        return [
            'success' => false,
            'message' => '',
            'material' => [],
            'mainDeck' => [],
            'unresolved' => []
        ];
    }

    $cards = $deckData['data']['cards'] ?? null;
    if (!is_array($cards)) {
        return [
            'success' => false,
            'message' => '',
            'material' => [],
            'mainDeck' => [],
            'unresolved' => []
        ];
    }

    $material = [];
    $mainDeck = [];
    $sideboard = [];
    $unresolved = [];

    foreach ($cards as $card) {
        if (!is_array($card)) continue;

        $quantity = intval($card['quantity'] ?? 0);
        if ($quantity <= 0) continue;

        $resolvedCardID = ResolveSleevedCardID($card);
        if ($resolvedCardID === null) {
            $rawName = trim((string)($card['name'] ?? ($card['cardId'] ?? 'unknown')));
            if ($rawName !== '' && !in_array($rawName, $unresolved)) {
                $unresolved[] = $rawName;
            }
            continue;
        }

        $zone = strtolower(trim((string)($card['zoneId'] ?? 'main')));
        for ($i = 0; $i < $quantity; ++$i) {
            if ($zone === 'material') {
                $material[] = $resolvedCardID;
            }
            else if ($zone === 'sideboard') {
                $sideboard[] = $resolvedCardID;
            }
            else if ($zone === 'references') {
                continue;  // referenced/token cards, not part of the constructed deck
            }
            else {
                $mainDeck[] = $resolvedCardID;
            }
        }
    }

    if (count($material) + count($mainDeck) <= 0) {
        return [
            'success' => false,
            'message' => '',
            'material' => [],
            'mainDeck' => [],
            'sideboard' => [],
            'unresolved' => $unresolved
        ];
    }

    return [
        'success' => true,
        'message' => '',
        'material' => $material,
        'mainDeck' => $mainDeck,
        'sideboard' => $sideboard,
        'unresolved' => $unresolved
    ];
}

function ResolveSleevedCardID($card) {
    $rawCardID = trim((string)($card['cardId'] ?? ''));
    if ($rawCardID !== '') {
        if (IsGrandArchiveCardID($rawCardID)) {
            return $rawCardID;
        }

        $bySlug = CardSlugToID($rawCardID);
        if ($bySlug !== null) {
            return $bySlug;
        }
    }

    $rawName = trim((string)($card['name'] ?? ''));
    if ($rawName !== '') {
        $byName = CardNameToID($rawName);
        if ($byName !== null) {
            return $byName;
        }

        $byNameSlug = CardSlugToID($rawName);
        if ($byNameSlug !== null) {
            return $byNameSlug;
        }
    }

    return null;
}

function IsGrandArchiveCardID($cardID) {
    global $nameData;
    return is_array($nameData) && isset($nameData[$cardID]);
}

function CardSlugToID($input) {
    $slug = NormalizeCardSlug($input);
    if ($slug === '') return null;

    static $index = null;
    if ($index === null) {
        $index = BuildCardSlugToIDIndex();
    }

    return $index[$slug] ?? null;
}

function BuildCardSlugToIDIndex() {
    global $nameData;
    $index = [];

    if (!is_array($nameData)) {
        return $index;
    }

    foreach ($nameData as $cardID => $name) {
        $slug = NormalizeCardSlug((string)$name);
        if ($slug !== '' && !isset($index[$slug])) {
            $index[$slug] = $cardID;
        }
    }

    return $index;
}

function NormalizeCardSlug($value) {
    $value = strtolower(trim((string)$value));
    if ($value === '') return '';

    $value = str_replace('&', ' and ', $value);
    // Strip apostrophes so possessives collapse the way sleeved.gg's slugs do
    // ("Grand Crusader's Ring" -> "grand-crusaders-ring", not "grand-crusader-s-ring").
    $value = str_replace(["'", "\u{2019}"], '', $value);
    $value = preg_replace('/[^a-z0-9]+/', '-', $value);
    $value = preg_replace('/-+/', '-', $value);
    return trim($value, '-');
}

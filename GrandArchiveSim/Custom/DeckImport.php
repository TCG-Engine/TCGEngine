<?php

include_once __DIR__ . '/../GeneratedCode/GeneratedCardDictionaries.php';
include_once __DIR__ . '/DeckTextParser.php';

function GrandArchiveValidateDeckForQueue($deckLink, $preconstructedDeck = '') {
    if (!empty($preconstructedDeck)) {
        return [
            'success' => true,
            'message' => ''
        ];
    }

    $deckLink = trim($deckLink);
    if ($deckLink === '') {
        return [
            'success' => false,
            'message' => 'Deck link is required.'
        ];
    }

    $resolved = GrandArchiveResolveDeckInput($deckLink);
    return [
        'success' => $resolved['success'],
        'message' => $resolved['success'] ? '' : $resolved['message']
    ];
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
                'unresolved' => $parsed['unresolved'] ?? []
            ];
        }

        return [
            'success' => true,
            'message' => '',
            'material' => $material,
            'mainDeck' => $mainDeck,
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

    foreach ($deckData['cards'] as $card) {
        $cardID = $card['id'] ?? '';
        $quantity = intval($card['pivot']['quantity'] ?? 0);
        $deckType = strtolower(trim($card['pivot']['deck_type'] ?? 'main'));

        if ($cardID === '' || $quantity <= 0) continue;

        for ($i = 0; $i < $quantity; ++$i) {
            if ($deckType === 'material') {
                $material[] = $cardID;
            } elseif ($deckType === 'sideboard') {
                continue;
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
            'unresolved' => []
        ];
    }

    return [
        'success' => true,
        'message' => '',
        'material' => $material,
        'mainDeck' => $mainDeck,
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
            else if ($zone === 'sideboard' || $zone === 'references') {
                continue;
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
            'unresolved' => $unresolved
        ];
    }

    return [
        'success' => true,
        'message' => '',
        'material' => $material,
        'mainDeck' => $mainDeck,
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
    $value = preg_replace('/[^a-z0-9]+/', '-', $value);
    $value = preg_replace('/-+/', '-', $value);
    return trim($value, '-');
}

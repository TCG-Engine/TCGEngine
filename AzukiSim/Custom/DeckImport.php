<?php

include_once __DIR__ . '/../GeneratedCode/GeneratedCardDictionaries.php';
include_once __DIR__ . '/../../AzukiDeck/DeckService.php';

function AzukiValidateDeckForQueue($deckLink, $preconstructedDeck = '', $userID = null) {
    $deckLink = trim((string)$deckLink);
    if ($deckLink === '') {
        return [
            'success' => !empty($preconstructedDeck),
            'message' => !empty($preconstructedDeck) ? '' : 'Either a deck link or starter deck is required.'
        ];
    }

    $resolved = AzukiResolveDeckInput($deckLink, $userID);
    return [
        'success' => $resolved['success'],
        'message' => $resolved['success'] ? '' : $resolved['message']
    ];
}

function AzukiResolveDeckInput($deckLink, $userID = null) {
    $deckLink = trim((string)$deckLink);
    if ($deckLink === '') {
        return [
            'success' => false,
            'message' => 'Deck link is required.',
            'leader' => '',
            'gate' => '',
            'mainDeck' => [],
            'unresolved' => []
        ];
    }

    if (preg_match('/^azukideck:(\d+)$/i', $deckLink, $matches)) {
        return AzukiCanonicalizeResolvedDeck(AzukiDeckResolveOwnedDeck($matches[1], $userID));
    }

    $slug = AzukiExtractDeckSlug($deckLink);
    if ($slug === '') {
        return [
            'success' => false,
            'message' => 'Deck link must be a valid thegateikz.com deck URL or deck slug.',
            'leader' => '',
            'gate' => '',
            'mainDeck' => [],
            'unresolved' => []
        ];
    }

    $deckData = AzukiFetchDeckJsonBySlug($slug);
    $normalized = AzukiNormalizeGateDeck($deckData);
    if (!$normalized['success']) {
        $normalized['message'] = $normalized['message'] !== '' ? $normalized['message'] : 'Could not load that deck link. It may be private, invalid, or unavailable.';
        return $normalized;
    }

    return AzukiCanonicalizeResolvedDeck($normalized);
}

function AzukiImportedCardCanonicalMap() {
    return [
        'AZK01-028A_Soryu-no-Rin_E_SR_Die' => 'S1-AZK01-028_Soryu-no-Rin_E_SR_die',
        'AZK01-042A_Thunderclap_S_SR_TEX2_Die' => 'S1-AZK01-042_Thunderclap_S_SR_die',
        'AZK01-054A_Teb-Fea_E_SR_Die' => 'S1-AZK01-054_Teb-Fea_E_SR_die',
        'AZK01-064A_Zero_E_SR_Die' => 'S1-AZK01-064_Zero_E_SR_die',
        'AZP-001_IKZ!_IKZ-Token-AX-Participation_Die' => 'IKZ-002_IKZ!_IKZ-Token_Die',
        'AZP-002_IKZ!_IKZ-Token-AX-Winner_Die' => 'IKZ-002_IKZ!_IKZ-Token_Die',
        'AZP-003_IKZ_INV26-Participation_die' => 'IKZ-001_IKZ!_IKZ_die',
        'IKZ-001A_IKZ!_IKZ_Die' => 'IKZ-001_IKZ!_IKZ_die',
        'IKZ-002A_IKZ!_IKZ-Token_Die' => 'IKZ-002_IKZ!_IKZ-Token_Die',
        'S1-AZK01-079A_Gin-and-Tonika_E_SR_die' => 'S1-AZK01-079_Gin-and-Tonika_E_SR_die',
        'S1-AZK01-087A_Mizuryuus-Torrent_S_SR_die' => 'S1-AZK01-087_Mizuryuus-Torrent_S_SR_die',
        'S1-AZK01-099A_Raikos-Wrath-Shin_E_SR_die' => 'S1-AZK01-099_Raikos-Wrath-Shin_E_SR_die',
        'S1-AZK01-112A_Enrai-Shakunetsu_E_SR_die' => 'S1-AZK01-112_Enrai-Shakunetsu_E_SR_die',
        'S1-AZK01-119A_Piko-of-Thousand-Blades_L_L_die' => 'S1-AZK01-119_Piko-of-Thousand-Blades_L_L_die',
        'S1-AZK01-121A_Kagoro-of-the-Burnt-Path_L_L_die' => 'S1-AZK01-121_Kagoro-of-the-Burnt-Path_L_L_die',
        'S1-AZK01-123A_Goro-Graveloth_L_L_die' => 'S1-AZK01-123_Goro-Graveloth_L_L_die',
        'S1-AZK01-125A_Benzai-the-Sly_L_L_die' => 'S1-AZK01-125_Benzai-the-Sly_L_L_die',
        'S1-STT01-001AX1_Raizan_L_INV26-WINNER_die' => 'S1-STT01-001_Raizan_L_L_die',
        'S1-STT01-001AX2_Raizan_L_INV26-SECOND_die' => 'S1-STT01-001_Raizan_L_L_die',
        'S1-STT02-001AX_Shao_L_INV26-TOP8_die' => 'S1-STT02-001_Shao_L_L_die',
        'S1-STT03-001A_Bobu_L_L_die' => 'S1-STT03-001_Bobu_L_L_die',
        'S1-STT03-002A_Stonehaven-Gate_G_G_die' => 'S1-STT03-002_Stonehaven-Gate_G_G_die',
        'S1-STT03-013A_Stone-Masked-Ancient_E_SR_die' => 'S1-STT03-013_Stone-Masked-Ancient_E_SR_die',
        'S1-STT04-001_Zero_L_L_die__2' => 'S1-STT04-001_Zero_L_L_die',
        'S1-STT04-002A_Ragefire-Gate_G_G_die' => 'S1-STT04-002_Ragefire-Gate_G_G_die',
        'S1-STT04-014A_Scorchveil-Shinobi-Suzuka_E_SR_die' => 'S1-STT04-014_Scorchveil-Shinobi-Suzuka_E_SR_die',
        'STT01-001A_Raizan_L_AA_Die' => 'S1-STT01-001_Raizan_L_L_die',
        'STT01-002A_Surge-Gate_G_die' => 'S1-STT01-002_Surge-Gate_G_G_die',
        'STT01-011A_Raizan_E_SR_die' => 'S1-STT01-011_Raizan_E_SR_die',
        'STT01-016A_Ikazuchi_W_SR_die' => 'S1-STT01-016_Ikazuchi_W_SR_die',
        'STT02-001A_Shao_L_AA_Die' => 'S1-STT02-001_Shao_L_L_die',
        'STT02-002A_Hydromancy-Gate_G_die' => 'S1-STT02-002_Hydromancy-Gate_G_G_die',
        'STT02-013A_Mizuki_E_SR_die' => 'S1-STT02-013_Mizuki_E_SR_die',
        'STT02-013ASN_Mizuki_E_SR_die' => 'S1-STT02-013_Mizuki_E_SR_die',
        'STT02-017A_Shaos-Perseverance_S_SR_die' => 'S1-STT02-017_Shaos-Perseverance_S_SR_die'
    ];
}

function AzukiCanonicalImportedCardID($cardID) {
    $cardID = trim((string)$cardID);
    $canonicalMap = AzukiImportedCardCanonicalMap();
    return $canonicalMap[$cardID] ?? $cardID;
}

function AzukiCanonicalizeResolvedDeck($resolvedDeck) {
    if (!is_array($resolvedDeck)) return $resolvedDeck;

    foreach (['leader', 'gate'] as $key) {
        if (isset($resolvedDeck[$key])) {
            $resolvedDeck[$key] = AzukiCanonicalImportedCardID($resolvedDeck[$key]);
        }
    }

    if (isset($resolvedDeck['mainDeck']) && is_array($resolvedDeck['mainDeck'])) {
        $resolvedDeck['mainDeck'] = array_map('AzukiCanonicalImportedCardID', $resolvedDeck['mainDeck']);
    }

    return $resolvedDeck;
}

function AzukiExtractDeckSlug($deckLink) {
    $deckLink = trim((string)$deckLink);
    if ($deckLink === '') return '';

    $parsed = parse_url($deckLink);
    if (!is_array($parsed) || !isset($parsed['scheme'])) {
        return preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/i', $deckLink) ? strtolower($deckLink) : '';
    }

    $host = strtolower((string)($parsed['host'] ?? ''));
    if ($host !== '' && strpos($host, 'thegateikz.com') === false) {
        return '';
    }

    if (isset($parsed['query'])) {
        parse_str($parsed['query'], $queryParams);
        foreach (['slug', 'deck', 'deckLink', 'id'] as $key) {
            if (!empty($queryParams[$key]) && is_string($queryParams[$key])) {
                return strtolower(trim($queryParams[$key]));
            }
        }
    }

    $path = trim((string)($parsed['path'] ?? ''), '/');
    if ($path === '') return '';

    $segments = array_values(array_filter(explode('/', $path), function($segment) {
        return $segment !== '';
    }));
    if (empty($segments)) return '';

    return strtolower(trim($segments[count($segments) - 1]));
}

function AzukiFetchDeckJsonBySlug($slug) {
    global $azukiDeckSupabaseAnonKey;

    if (!isset($azukiDeckSupabaseAnonKey) || trim((string)$azukiDeckSupabaseAnonKey) === '') {
        $azukiDeckSupabaseAnonKey = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6InB3eXdla3ZvdW5zZXVueXBuc2t0Iiwicm9sZSI6ImFub24iLCJpYXQiOjE3NjQ5NTA1ODUsImV4cCI6MjA4MDUyNjU4NX0.0Lc4q-e43wEzDaXI2vi0hkyllXpppF4Kx_8U9MTW5e0';
    }

    $select = 'id,slug,name,description,official_tag,created_at,creator:profiles!creator_id(id,display_name,username,avatar_url,social_x),deck_cards(quantity,card:cards(id,name,element,ikz_cost,card_type,image_url))';
    $apiUrl = 'https://pwywekvounseunypnskt.supabase.co/rest/v1/decks?select=' . rawurlencode($select) . '&slug=eq.' . rawurlencode($slug);
    $headers = [
        'Accept: application/json',
        'Accept-Profile: public',
        'apikey: ' . $azukiDeckSupabaseAnonKey,
        'Authorization: Bearer ' . $azukiDeckSupabaseAnonKey,
        'Origin: https://thegateikz.com',
        'Referer: https://thegateikz.com/',
        'User-Agent: TCGEngine-AzukiSim-DeckImport'
    ];

    return AzukiFetchDeckJson($apiUrl, $headers);
}

function AzukiFetchDeckJson($url, $headers = []) {
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
    return is_array($decoded) ? $decoded : null;
}

function AzukiNormalizeGateDeck($deckData) {
    if (!is_array($deckData) || empty($deckData) || !isset($deckData[0]['deck_cards']) || !is_array($deckData[0]['deck_cards'])) {
        return [
            'success' => false,
            'message' => '',
            'leader' => '',
            'gate' => '',
            'mainDeck' => [],
            'unresolved' => []
        ];
    }

    $leader = '';
    $gate = '';
    $mainDeck = [];
    $unresolved = [];

    foreach ($deckData[0]['deck_cards'] as $entry) {
        $card = $entry['card'] ?? null;
        $quantity = intval($entry['quantity'] ?? 0);
        if (!is_array($card) || $quantity <= 0) continue;

        $resolvedCardID = AzukiResolveImportedCardID($card);
        if ($resolvedCardID === '') {
            $cardName = trim((string)($card['name'] ?? 'Unknown card'));
            if ($cardName !== '' && !in_array($cardName, $unresolved, true)) {
                $unresolved[] = $cardName;
            }
            continue;
        }

        $resolvedType = strtolower((string)(CardCategory($resolvedCardID) ?? ''));
        $importType = strtolower(trim((string)($card['card_type'] ?? '')));

        if (($resolvedType === 'leader' || $importType === 'leader') && $leader === '') {
            $leader = $resolvedCardID;
            continue;
        }

        if (($resolvedType === 'gate' || $importType === 'gate') && $gate === '') {
            $gate = $resolvedCardID;
            continue;
        }

        for ($i = 0; $i < $quantity; ++$i) {
            $mainDeck[] = $resolvedCardID;
        }
    }

    if ($gate === '' && $leader !== '') {
        $gate = AzukiInferGateForLeader($leader);
    }

    if ($leader === '' || $gate === '' || empty($mainDeck)) {
        return [
            'success' => false,
            'message' => 'Deck import was missing a leader, gate, or playable deck cards.',
            'leader' => $leader,
            'gate' => $gate,
            'mainDeck' => $mainDeck,
            'unresolved' => $unresolved
        ];
    }

    return [
        'success' => true,
        'message' => '',
        'leader' => $leader,
        'gate' => $gate,
        'mainDeck' => $mainDeck,
        'unresolved' => $unresolved
    ];
}

function AzukiResolveImportedCardID($card) {
    global $idData;

    if (!is_array($card)) return '';

    $imageUrl = trim((string)($card['image_url'] ?? ''));
    if ($imageUrl !== '') {
        $path = parse_url($imageUrl, PHP_URL_PATH);
        $basename = is_string($path) ? pathinfo($path, PATHINFO_FILENAME) : '';
        if ($basename !== '' && isset($idData[$basename])) {
            return AzukiCanonicalImportedCardID($basename);
        }
    }

    $cardName = trim((string)($card['name'] ?? ''));
    if ($cardName === '') return '';

    $importType = strtolower(trim((string)($card['card_type'] ?? '')));
    $normalizedName = AzukiNormalizeImportedCardName($cardName);
    $matches = AzukiFindLocalCardIDsByName($normalizedName);
    if (empty($matches)) return '';

    if ($importType !== '') {
        for ($i = 0; $i < count($matches); ++$i) {
            $candidateCardID = AzukiCanonicalImportedCardID($matches[$i]);
            if (strtolower((string)(CardCategory($candidateCardID) ?? '')) === $importType) {
                return $candidateCardID;
            }
        }
    }

    return AzukiCanonicalImportedCardID($matches[0]);
}

function AzukiNormalizeImportedCardName($cardName) {
    $cardName = strtolower(trim((string)$cardName));
    $cardName = preg_replace('/\s*\(leader\)\s*$/i', '', $cardName);
    $cardName = preg_replace('/\s+/', ' ', $cardName);
    return trim((string)$cardName);
}

function AzukiFindLocalCardIDsByName($normalizedName) {
    global $nameData;
    static $index = null;

    if ($index === null) {
        $index = [];
        if (is_array($nameData)) {
            foreach ($nameData as $cardID => $name) {
                $key = AzukiNormalizeImportedCardName($name);
                if (!isset($index[$key])) {
                    $index[$key] = [];
                }
                $index[$key][] = $cardID;
            }
        }
    }

    return isset($index[$normalizedName]) ? $index[$normalizedName] : [];
}

function AzukiInferGateForLeader($leaderCardID) {
    switch (strtolower((string)(CardElement($leaderCardID) ?? ''))) {
        case 'fire':
            return 'S1-AZK01-122_Rushfire-Gate_G_G_die';
        case 'earth':
            return 'S1-AZK01-124_Gate-of-Devotion-Gate_G_G_die';
        case 'water':
            return 'S1-AZK01-126_Gate-of-Echoed-Waves-Gate_G_G_die';
        case 'lightning':
            return 'S1-AZK01-120_Stormchain-Gate_G_G_die';
        default:
            return '';
    }
}

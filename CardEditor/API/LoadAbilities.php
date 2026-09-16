<?php
// Load Abilities API Endpoint
// Returns all abilities for a given card

include_once __DIR__ . '/../../AccountFiles/AccountSessionAPI.php';
include_once('../Database/CardAbilityRepository.php');
include_once __DIR__ . '/../../Core/CardBaseMap.php';

header('Content-Type: application/json');

$authError = CheckLoggedInUserModStrict();
if ($authError !== '') {
    http_response_code(403);
    echo json_encode(['error' => $authError]);
    exit;
}

try {
    $rootName = $_GET['root'] ?? null;
    $cardId = $_GET['card'] ?? null;
    
    if (!$rootName || !$cardId) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing root or card parameter']);
        exit;
    }
    
    // A variant printing (borderless, alt art) shows its base card's abilities.
    $resolution = CardBaseResolution((string)$rootName, (string)$cardId);
    $cardId = $resolution['cardId'];

    $db = OpenCardAbilityRepository($rootName);
    if (method_exists($db, 'loadCardWithRevision')) {
        $loaded = $db->loadCardWithRevision($rootName, $cardId);
        $abilities = $loaded['abilities'] ?? [];
        $revision = (string)($loaded['revision'] ?? '');
    } else {
        $abilities = $db->loadCardAbilities($rootName, $cardId);
        $revision = $db->revisionForCard($rootName, $cardId);
    }
    
    echo json_encode([
        'success' => true,
        'abilities' => $abilities,
        'hasAbilities' => count($abilities) > 0,
        'revision' => $revision
    ] + $resolution);
    $db->close();
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}


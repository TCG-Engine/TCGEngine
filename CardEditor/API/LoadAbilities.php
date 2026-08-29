<?php
// Load Abilities API Endpoint
// Returns all abilities for a given card

include_once('../Database/CardAbilityRepository.php');

header('Content-Type: application/json');

try {
    $rootName = $_GET['root'] ?? null;
    $cardId = $_GET['card'] ?? null;
    
    if (!$rootName || !$cardId) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing root or card parameter']);
        exit;
    }
    
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
    ]);
    $db->close();
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}


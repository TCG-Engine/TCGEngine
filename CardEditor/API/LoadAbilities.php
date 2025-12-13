<?php
// Load Abilities API Endpoint
// Returns all abilities for a given card

include_once('../../Database/ConnectionManager.php');
include_once('../Database/CardAbilityDB.php');

header('Content-Type: application/json');

try {
    $conn = GetLocalMySQLConnection();
    $db = new CardAbilityDB($conn);
    
    $rootName = $_GET['root'] ?? null;
    $cardId = $_GET['card'] ?? null;
    
    if (!$rootName || !$cardId) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing root or card parameter']);
        exit;
    }
    
    $abilities = $db->loadCardAbilities($rootName, $cardId);
    
    echo json_encode([
        'success' => true,
        'abilities' => $abilities,
        'hasAbilities' => count($abilities) > 0
    ]);
    
    mysqli_close($conn);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}


<?php
// Save Abilities API Endpoint
// Saves or updates abilities for a given card
// Also supports marking cards as implemented without abilities

include_once __DIR__ . '/../../AccountFiles/AccountSessionAPI.php';
include_once('../Database/CardAbilityRepository.php');

header('Content-Type: application/json');

$authError = CheckLoggedInUserModStrict();
if ($authError !== '') {
    http_response_code(403);
    echo json_encode(['error' => $authError]);
    exit;
}

try {
    // Only accept POST requests
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        exit;
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    $rootName = $input['root'] ?? null;
    $cardId = $input['card'] ?? null;
    $abilities = $input['abilities'] ?? [];
    $cardImplemented = $input['cardImplemented'] ?? false;
    $baseRevision = isset($input['baseRevision']) ? (string)$input['baseRevision'] : null;
    
    if (!$rootName || !$cardId) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing root or card parameter']);
        exit;
    }
    
    if (!is_array($abilities)) {
        http_response_code(400);
        echo json_encode(['error' => 'Abilities must be an array']);
        exit;
    }
    
    $db = OpenCardAbilityRepository($rootName);

    $result = $db->replaceCardAbilities($rootName, $cardId, $abilities, (bool)$cardImplemented, $baseRevision);
    echo json_encode([
        'success' => true,
        'message' => 'Saved successfully',
        'saved' => $result['abilities'] ?? [],
        'revision' => $result['revision'] ?? '',
        'cardImplemented' => $cardImplemented,
    ]);
    $db->close();

} catch (CardCodeConflictException $e) {
    http_response_code(409);
    echo json_encode(['error' => $e->getMessage(), 'conflict' => $e->conflict]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Save failed: ' . $e->getMessage()]);
}

?>

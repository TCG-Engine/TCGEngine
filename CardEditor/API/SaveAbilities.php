<?php
// Save Abilities API Endpoint
// Saves or updates abilities for a given card
// Also supports marking cards as implemented without abilities

include_once('../../Database/ConnectionManager.php');
include_once('../Database/CardAbilityDB.php');

header('Content-Type: application/json');

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
    
    $conn = GetLocalMySQLConnection();
    $db = new CardAbilityDB($conn);
    
    // Start transaction
    mysqli_query($conn, "START TRANSACTION");
    
    try {
        // First, get existing abilities
        $existingAbilities = $db->loadCardAbilities($rootName, $cardId);
        $existingIds = array_column($existingAbilities, 'id');
        
        $savedIds = [];
        $results = [];
        
        // Process each ability in the request
        foreach ($abilities as $ability) {
            $id = $ability['id'] ?? null;
            $macroName = $ability['macroName'] ?? null;
            $abilityCode = $ability['abilityCode'] ?? null;
            $abilityName = $ability['abilityName'] ?? null;
            $isImplemented = $ability['isImplemented'] ?? 0;
            
            if (!$macroName || !$abilityCode) {
                throw new Exception("Ability missing macroName or abilityCode");
            }
            
            $savedId = $db->saveAbility($id, $rootName, $cardId, $macroName, $abilityCode, $abilityName, $isImplemented);
            if (!$savedId) {
                throw new Exception("Failed to save ability");
            }
            
            $savedIds[] = $savedId;
            $results[] = ['id' => $savedId, 'macroName' => $macroName];
        }
        
        // If card is marked as implemented but has no abilities, create a marker ability
        if ($cardImplemented && count($abilities) === 0) {
            // Create a marker ability to indicate card is implemented (no macro, no code, just the flag)
            $markerId = $db->saveAbility(null, $rootName, $cardId, '', '', '[Card Implemented]', 1);
            if ($markerId) {
                $savedIds[] = $markerId;
            }
        }
        
        // Delete any abilities that were removed (existed but not in current request)
        foreach ($existingIds as $existingId) {
            if (!in_array($existingId, $savedIds)) {
                $db->deleteAbility($existingId, $rootName, $cardId);
            }
        }
        
        // Commit transaction
        mysqli_query($conn, "COMMIT");
        
        echo json_encode([
            'success' => true,
            'message' => 'Saved successfully',
            'saved' => $results,
            'cardImplemented' => $cardImplemented
        ]);
        
        mysqli_close($conn);
    } catch (Exception $e) {
        mysqli_query($conn, "ROLLBACK");
        throw $e;
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Save failed: ' . $e->getMessage()]);
}

?>

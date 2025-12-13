<?php
// Get Roots and Cards API Endpoint
// Returns available root games and card lists from their data sources

include_once('../../Database/ConnectionManager.php');

header('Content-Type: application/json');

try {
    // Scan for root folders that contain GameSchema and card data
    $rootsDir = '../../';
    $roots = [];
    
    $dirs = array_filter(scandir($rootsDir), function($item) use ($rootsDir) {
        $path = $rootsDir . $item;
        return is_dir($path) && $item[0] !== '.' && file_exists($path . '/ZoneAccessors.php');
    });
    
    foreach ($dirs as $dir) {
        $rootName = $dir;
        $rootPath = $rootsDir . $dir;
        
        // Load cards from the root's card data source
        // Typically cards.json or fetched from zzCardCodeGenerator
        $cards = loadCardsForRoot($rootName, $rootPath);
        
        if ($cards !== null) {
            $roots[$rootName] = $cards;
        }
    }
    
    echo json_encode([
        'success' => true,
        'roots' => $roots
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to load roots: ' . $e->getMessage()]);
}

/**
 * Load cards for a specific root game
 * Tries multiple sources: cards.json, generated card list, etc.
 */
function loadCardsForRoot($rootName, $rootPath) {
    $cards = [];
    
    // Try cards.json first (standard format)
    $cardsJsonPath = $rootPath . '/cards.json';
    if (file_exists($cardsJsonPath)) {
        $json = json_decode(file_get_contents($cardsJsonPath), true);
        if (is_array($json)) {
            foreach ($json as $card) {
                $cardId = $card['CardID'] ?? $card['id'] ?? null;
                if ($cardId) {
                    $cards[$cardId] = $card['Name'] ?? $cardId;
                }
            }
        }
    }
    
    // Try top-level cards.json if root-specific not found
    if (empty($cards)) {
        $cardsJsonPath = '../../cards.json';
        if (file_exists($cardsJsonPath)) {
            $json = json_decode(file_get_contents($cardsJsonPath), true);
            if (is_array($json)) {
                foreach ($json as $card) {
                    $cardId = $card['CardID'] ?? $card['id'] ?? null;
                    if ($cardId) {
                        $cards[$cardId] = $card['Name'] ?? $cardId;
                    }
                }
            }
        }
    }
    
    // Return null if no cards found, otherwise return sorted array
    if (empty($cards)) {
        return null;
    }
    
    ksort($cards);
    return $cards;
}

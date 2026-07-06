<?php
// Get Macros API Endpoint
// Returns all macros defined in a root's GameSchema

include_once('../../Database/ConnectionManager.php');

header('Content-Type: application/json');

try {
    $rootName = $_GET['root'] ?? null;
    
    if (!$rootName) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing root parameter']);
        exit;
    }
    
    $macros = getMacrosForRoot($rootName);
    $zones = getCardZonesForRoot($rootName);
    
    echo json_encode([
        'success' => true,
        'macros' => $macros,
        'zones' => $zones
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to load macros: ' . $e->getMessage()]);
}

/**
 * Parse GameSchema.txt to extract macro names
 * Looks for lines like: Macro: Name=MacroName(...);
 */
function getMacrosForRoot($rootName) {
    $macros = [];
    $schemaPath = "../../Schemas/$rootName/GameSchema.txt";
    
    if (!file_exists($schemaPath)) {
        return [];
    }
    
    $lines = file($schemaPath, FILE_IGNORE_NEW_LINES);
    
    foreach ($lines as $line) {
        if (strpos($line, 'Macro:') === 0) {
            // Extract macro name from: Macro: Name=MacroName(...);
            if (preg_match('/Name=([^(;]+)(\([^)]*\))?/', $line, $matches)) {
                $macroName = $matches[1];
                $macros[] = $macroName;
            }
        }
    }
    
    sort($macros);
    return array_values(array_unique($macros));
}

/**
 * Parse GameSchema.txt to extract card-bearing zone names.
 * Listener abilities use these schema zone names as their active-zone metadata.
 */
function getCardZonesForRoot($rootName) {
    $zones = [];
    $schemaPath = "../../Schemas/$rootName/GameSchema.txt";

    if (!file_exists($schemaPath)) {
        return [];
    }

    $lines = file($schemaPath, FILE_IGNORE_NEW_LINES);
    foreach ($lines as $line) {
        $trimmed = trim($line);
        if ($trimmed === '' || $trimmed[0] === '#') continue;
        if (!preg_match('/^(\w+)\s*-\s*(.+)$/', $trimmed, $matches)) continue;
        $zoneName = trim($matches[1]);
        $fieldSpec = trim($matches[2]);
        if (preg_match('/(^|,\s*)CardID\s*:/', $fieldSpec)) {
            $zones[] = $zoneName;
        }
    }

    sort($zones);
    return array_values(array_unique($zones));
}

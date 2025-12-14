<?php
// Get Asset Path API Endpoint
// Returns the asset reflection path for a root (if configured)
// This allows proper image loading when a root uses images from another root

header('Content-Type: application/json');

try {
    $rootName = $_GET['root'] ?? null;
    
    if (!$rootName) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing root parameter']);
        exit;
    }
    
    $schemaFile = "../../Schemas/" . $rootName . "/GameSchema.txt";
    
    if (!file_exists($schemaFile)) {
        http_response_code(404);
        echo json_encode(['error' => 'Root not found']);
        exit;
    }
    
    // Parse the schema to find AssetReflection
    $assetReflectionPath = "";
    $handle = fopen($schemaFile, "r");
    
    while (!feof($handle)) {
        $line = trim(fgets($handle));
        
        if (empty($line) || $line[0] === '#') {
            continue;
        }
        
        if (strpos($line, 'AssetReflection:') === 0) {
            $assetReflectionPath = trim(substr($line, strlen('AssetReflection:')));
            break;
        }
    }
    
    fclose($handle);
    
    // If no asset reflection is set, use the current root
    if (empty($assetReflectionPath)) {
        $assetReflectionPath = $rootName;
    }
    
    echo json_encode([
        'success' => true,
        'root' => $rootName,
        'assetPath' => $assetReflectionPath
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error reading schema: ' . $e->getMessage()]);
}
?>

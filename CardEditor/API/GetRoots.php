<?php
// Get Roots and Cards API Endpoint
// Returns available root games and card lists from their data sources

include_once('../../Database/ConnectionManager.php');

header('Content-Type: application/json');

try {
    // First, get roots from the database (from card_abilities table)
    $conn = GetLocalMySQLConnection();
    
    // Query for unique root names in the database
    $stmt = mysqli_prepare($conn, "SELECT DISTINCT root_name FROM card_abilities ORDER BY root_name ASC");
    if (!$stmt) {
        throw new Exception("Prepare failed: " . mysqli_error($conn));
    }
    
    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception("Execute failed: " . mysqli_stmt_error($stmt));
    }
    
    $result = mysqli_stmt_get_result($stmt);
    
    $databaseRoots = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $databaseRoots[] = $row['root_name'];
    }
    mysqli_stmt_close($stmt);
    
    // If no roots found in database, return error
    if (empty($databaseRoots)) {
        mysqli_close($conn);
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'No roots found in database. Run zzCardCodeGenerator first.',
            'databaseRootsCount' => 0
        ]);
        exit;
    }
    
    // Now load cards for each root from the database
    $roots = [];
    foreach ($databaseRoots as $rootName) {
        $cardsStmt = mysqli_prepare($conn, "SELECT DISTINCT card_id FROM card_abilities WHERE root_name = ? ORDER BY card_id ASC");
        if (!$cardsStmt) {
            throw new Exception("Prepare cards query failed: " . mysqli_error($conn));
        }
        
        mysqli_stmt_bind_param($cardsStmt, "s", $rootName);
        if (!mysqli_stmt_execute($cardsStmt)) {
            throw new Exception("Execute cards query failed: " . mysqli_stmt_error($cardsStmt));
        }
        
        $cardsResult = mysqli_stmt_get_result($cardsStmt);
        $cards = [];
        while ($cardRow = mysqli_fetch_assoc($cardsResult)) {
            $cardId = $cardRow['card_id'];
            // Use card ID as both key and display name (can be enhanced later with card names from other sources)
            $cards[$cardId] = $cardId;
        }
        mysqli_stmt_close($cardsStmt);
        
        if (!empty($cards)) {
            $roots[$rootName] = $cards;
        }
    }
    
    mysqli_close($conn);
    
    echo json_encode([
        'success' => true,
        'roots' => $roots,
        'databaseRootsCount' => count($databaseRoots),
        'loadedRootsCount' => count($roots)
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to load roots: ' . $e->getMessage()]);
}

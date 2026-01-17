<?php
/**
 * Base De-duplication Conversion Script
 * 
 * This script migrates historical data from non-canonical bases to their canonical versions.
 * It processes the following tables:
 *   - deckmetastats (baseID)
 *   - deckmetamatchupstats (baseID and opponentBaseID)
 * 
 * The script will:
 *   1. Find all rows for non-canonical bases in the specified week(s)
 *   2. Add those totals to the row for the canonical base (creating if needed)
 *   3. Delete the row for the non-canonical base
 */

require_once __DIR__ . "/../Database/ConnectionManager.php";
require_once __DIR__ . "/../SWUDeck/GeneratedCode/GeneratedCardDictionaries.php";
require_once __DIR__ . "/../AccountFiles/AccountSessionAPI.php";
require_once __DIR__ . "/../AccountFiles/AccountDatabaseAPI.php";

// Authentication check
if(!IsUserLoggedIn()) {
    if (isset($_COOKIE["rememberMeToken"])) {
        include_once __DIR__ . '/../Assets/patreon-php-master/src/OAuth.php';
        include_once __DIR__ . '/../Assets/patreon-php-master/src/PatreonLibraries.php';
        include_once __DIR__ . '/../Assets/patreon-php-master/src/API.php';
        include_once __DIR__ . '/../Assets/patreon-php-master/src/PatreonDictionary.php';
        include_once __DIR__ . '/../Database/functions.inc.php';
        include_once __DIR__ . '/../Database/dbh.inc.php';
        loginFromCookie();
    }
}

if(!IsUserLoggedIn()) {
    echo "<h2>Error: You must be logged in to use this tool</h2>";
    echo "<a href='../SharedUI/Sites/SWUDeck/'>Return to SWUDeck</a>";
    exit();
}

$userName = LoggedInUserName();
if($userName != "OotTheMonk") {
    echo "<h2>Error: You must be an approved user to use this tool</h2>";
    echo "<a href='../SharedUI/Sites/SWUDeck/'>Return to SWUDeck</a>";
    exit();
}

// Canonical base IDs by aspect (HP 30 bases)
$canonicalBases = [
    'Cunning' => '2376813177',
    'Command' => '7790300585',
    'Aggression' => '2696059415',
    'Vigilance' => '9014930596'
];

// Handle AJAX requests
$isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
if ($isAjax || (isset($_POST['action']) && $_POST['action'] === 'process')) {
    header('Content-Type: application/json');
    
    $action = isset($_POST['action']) ? $_POST['action'] : '';
    
    if ($action === 'get_bases') {
        // Get all bases with HP 30 from the database
        $conn = GetLocalMySQLConnection();
        $bases = getNonCanonicalBases($conn);
        echo json_encode(['success' => true, 'bases' => $bases]);
        exit();
    }
    
    if ($action === 'process') {
        $dryRun = isset($_POST['dryRun']) && $_POST['dryRun'] === 'true';
        $weeksInput = isset($_POST['weeks']) ? $_POST['weeks'] : '';
        
        // Parse weeks
        $weeks = [];
        if ($weeksInput === 'all') {
            $weeks = 'all';
        } elseif (strpos($weeksInput, '-') !== false) {
            $range = explode('-', $weeksInput);
            if (count($range) === 2 && is_numeric($range[0]) && is_numeric($range[1])) {
                for ($i = intval($range[0]); $i <= intval($range[1]); $i++) {
                    $weeks[] = $i;
                }
            }
        } else {
            $weekParts = explode(',', $weeksInput);
            foreach ($weekParts as $w) {
                if (is_numeric(trim($w))) {
                    $weeks[] = intval(trim($w));
                }
            }
        }
        
        if (empty($weeks) && $weeks !== 'all') {
            echo json_encode(['success' => false, 'error' => 'No valid weeks specified']);
            exit();
        }
        
        $conn = GetLocalMySQLConnection();
        
        // Get list of weeks to process
        if ($weeks === 'all') {
            $sql = "SELECT DISTINCT week FROM deckmetastats ORDER BY week";
            $result = mysqli_query($conn, $sql);
            $weeks = [];
            while ($row = mysqli_fetch_assoc($result)) {
                $weeks[] = intval($row['week']);
            }
        }
        
        $output = [];
        $totalMerged = 0;
        $deckMetaStatsTotal = 0;
        $matchupStatsTotal = 0;
        
        foreach ($weeks as $week) {
            $output[] = "--- Processing Week $week ---";
            
            // Process deckmetastats table
            $output[] = "[deckmetastats]";
            $result = processDeckMetaStats($conn, $week, $dryRun);
            $output = array_merge($output, $result['log']);
            $deckMetaStatsTotal += $result['count'];
            $totalMerged += $result['count'];
            
            // Process deckmetamatchupstats table
            $output[] = "[deckmetamatchupstats]";
            $result = processDeckMetaMatchupStats($conn, $week, $dryRun);
            $output = array_merge($output, $result['log']);
            $matchupStatsTotal += $result['count'];
            $totalMerged += $result['count'];
        }
        
        $output[] = "=== Summary ===";
        $output[] = "deckmetastats rows: $deckMetaStatsTotal";
        $output[] = "deckmetamatchupstats rows: $matchupStatsTotal";
        $output[] = "Total rows to be merged/deleted: $totalMerged";
        if ($dryRun) {
            $output[] = "*** This was a dry run - no actual changes were made ***";
        }
        
        mysqli_close($conn);
        echo json_encode(['success' => true, 'output' => $output, 'totalMerged' => $totalMerged]);
        exit();
    }
}

// If not AJAX, show the GUI
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Base De-duplication Tool</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 1200px;
            margin: 20px auto;
            padding: 20px;
            background-color: #f5f5f5;
        }
        h1 {
            color: #333;
        }
        .container {
            background-color: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .form-group {
            margin-bottom: 15px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #555;
        }
        input[type="text"] {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }
        button {
            background-color: #4CAF50;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            margin-right: 10px;
        }
        button:hover {
            background-color: #45a049;
        }
        button.dry-run {
            background-color: #2196F3;
        }
        button.dry-run:hover {
            background-color: #0b7dda;
        }
        button:disabled {
            background-color: #cccccc;
            cursor: not-allowed;
        }
        #output {
            background-color: #f9f9f9;
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 15px;
            max-height: 500px;
            overflow-y: auto;
            font-family: 'Courier New', monospace;
            font-size: 14px;
            white-space: pre-wrap;
            display: none;
        }
        #output.visible {
            display: block;
        }
        .info {
            background-color: #e7f3fe;
            border-left: 4px solid #2196F3;
            padding: 12px;
            margin-bottom: 15px;
        }
        .warning {
            background-color: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 12px;
            margin-bottom: 15px;
        }
        .bases-list {
            background-color: #f9f9f9;
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 15px;
            margin-top: 10px;
            max-height: 300px;
            overflow-y: auto;
        }
        .base-item {
            padding: 8px;
            margin: 5px 0;
            background-color: white;
            border-left: 3px solid #4CAF50;
            border-radius: 3px;
        }
        .loading {
            text-align: center;
            padding: 20px;
            color: #666;
        }
    </style>
</head>
<body>
    <h1>Base De-duplication Tool</h1>
    
    <div class="container">
        <div class="info">
            <strong>What this tool does:</strong><br>
            This tool consolidates stats for functionally duplicate bases (HP 30 bases) into canonical versions based on their aspect:
            <ul>
                <li>Cunning → <?php echo $canonicalBases['Cunning']; ?></li>
                <li>Command → <?php echo $canonicalBases['Command']; ?></li>
                <li>Aggression → <?php echo $canonicalBases['Aggression']; ?></li>
                <li>Vigilance → <?php echo $canonicalBases['Vigilance']; ?></li>
            </ul>
            It processes the <code>deckmetastats</code> and <code>deckmetamatchupstats</code> tables.
        </div>
        
        <div class="form-group">
            <label for="weeks">Weeks to Process:</label>
            <input type="text" id="weeks" placeholder="e.g., 0,1,2,3 or 0-5 or 'all'" value="all">
            <small style="color: #666;">Enter specific weeks (0,1,2), a range (0-5), or 'all' for all weeks</small>
        </div>
        
        <div class="form-group">
            <button onclick="loadBases()">Preview Non-Canonical Bases</button>
        </div>
        
        <div id="basesContainer" style="display: none;">
            <div class="bases-list" id="basesList"></div>
        </div>
        
        <div class="form-group">
            <button class="dry-run" onclick="processConversion(true)">Dry Run (Preview Only)</button>
            <button onclick="processConversion(false)" id="processBtn">Process Conversion</button>
        </div>
        
        <div class="warning" style="display: none;" id="warningBox">
            <strong>Warning:</strong> This will modify database records. Make sure you have a backup!
        </div>
    </div>
    
    <div class="container">
        <h2>Output Log</h2>
        <div id="output"></div>
    </div>
    
    <script>
        function loadBases() {
            const container = document.getElementById('basesContainer');
            const list = document.getElementById('basesList');
            
            list.innerHTML = '<div class="loading">Loading non-canonical bases...</div>';
            container.style.display = 'block';
            
            fetch('', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: 'action=get_bases'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    if (data.bases.length === 0) {
                        list.innerHTML = '<div style="padding: 10px; color: #666;">No non-canonical bases found in the database.</div>';
                    } else {
                        list.innerHTML = '<h3>Non-Canonical Bases Found:</h3>' + 
                            data.bases.map(base => `<div class="base-item"><strong>${base.baseID}</strong> (${base.aspect}) - Found in ${base.occurrences} row(s)</div>`).join('');
                    }
                } else {
                    list.innerHTML = '<div style="padding: 10px; color: red;">Error loading bases</div>';
                }
            })
            .catch(error => {
                list.innerHTML = '<div style="padding: 10px; color: red;">Error: ' + error.message + '</div>';
            });
        }
        
        function processConversion(dryRun) {
            const weeks = document.getElementById('weeks').value.trim();
            const output = document.getElementById('output');
            const processBtn = document.getElementById('processBtn');
            const warningBox = document.getElementById('warningBox');
            
            if (!weeks) {
                alert('Please specify which weeks to process');
                return;
            }
            
            if (!dryRun) {
                warningBox.style.display = 'block';
                if (!confirm('Are you sure you want to process the conversion? This will modify database records!')) {
                    return;
                }
            }
            
            output.classList.add('visible');
            output.textContent = (dryRun ? '[DRY RUN MODE - No changes will be made]\n' : '') + 'Processing...\n';
            processBtn.disabled = true;
            
            fetch('', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: `action=process&weeks=${encodeURIComponent(weeks)}&dryRun=${dryRun}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    output.textContent = (dryRun ? '[DRY RUN MODE - No changes were made]\n\n' : '') + 
                        data.output.join('\n') + '\n\n' +
                        '✓ Complete! Total rows processed: ' + data.totalMerged;
                } else {
                    output.textContent = 'Error: ' + (data.error || 'Unknown error occurred');
                }
                processBtn.disabled = false;
            })
            .catch(error => {
                output.textContent = 'Error: ' + error.message;
                processBtn.disabled = false;
            });
        }
    </script>
</body>
</html>
<?php
exit();

// ====== Helper Functions ======

/**
 * Get all non-canonical bases with HP 30 from the database
 */
function getNonCanonicalBases($conn) {
    global $canonicalBases;
    $nonCanonicalBases = [];
    
    // Get all unique baseIDs from deckmetastats
    $sql = "SELECT DISTINCT baseID FROM deckmetastats";
    $result = mysqli_query($conn, $sql);
    $allBases = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $allBases[] = $row['baseID'];
    }
    
    // Check each base
    foreach ($allBases as $baseID) {
        $hp = CardHp($baseID);
        $aspect = CardAspect($baseID);
        
        // Only consider HP 30 bases
        if ($hp == 30 && $aspect) {
            // Check if it's not a canonical base
            if (!in_array($baseID, $canonicalBases)) {
                // Count occurrences
                $countSql = "SELECT COUNT(*) as cnt FROM deckmetastats WHERE baseID = ?";
                $stmt = mysqli_stmt_init($conn);
                mysqli_stmt_prepare($stmt, $countSql);
                mysqli_stmt_bind_param($stmt, "s", $baseID);
                mysqli_stmt_execute($stmt);
                $countResult = mysqli_stmt_get_result($stmt);
                $countRow = mysqli_fetch_assoc($countResult);
                mysqli_stmt_close($stmt);
                
                $nonCanonicalBases[] = [
                    'baseID' => $baseID,
                    'aspect' => $aspect,
                    'occurrences' => $countRow['cnt']
                ];
            }
        }
    }
    
    return $nonCanonicalBases;
}

/**
 * Get mapping of non-canonical bases to their canonical equivalents
 */
function getNonCanonicalBaseMapping($conn) {
    global $canonicalBases;
    $mapping = [];
    
    // Get all unique baseIDs from deckmetastats and deckmetamatchupstats
    $sql = "SELECT DISTINCT baseID FROM deckmetastats 
            UNION 
            SELECT DISTINCT opponentBaseID as baseID FROM deckmetamatchupstats";
    $result = mysqli_query($conn, $sql);
    
    while ($row = mysqli_fetch_assoc($result)) {
        $baseID = $row['baseID'];
        $hp = CardHp($baseID);
        $aspect = CardAspect($baseID);
        
        // Only consider HP 30 bases with a valid aspect
        if ($hp == 30 && $aspect && !in_array($baseID, $canonicalBases)) {
            $mapping[$baseID] = $aspect;
        }
    }
    
    return $mapping;
}

/**
 * Process deckmetastats table for a given week
 */
function processDeckMetaStats($conn, $week, $dryRun) {
    global $canonicalBases;
    $nonCanonicalBases = getNonCanonicalBaseMapping($conn);
    
    $mergedCount = 0;
    $log = [];
    
    foreach ($nonCanonicalBases as $nonCanonicalBaseID => $aspect) {
        $canonicalBaseID = $canonicalBases[$aspect];
        
        // Find rows with non-canonical base for this week
        $sql = "SELECT * FROM deckmetastats WHERE baseID = ? AND week = ?";
        $stmt = mysqli_stmt_init($conn);
        if (!mysqli_stmt_prepare($stmt, $sql)) {
            $log[] = "  Error preparing statement: " . mysqli_error($conn);
            continue;
        }
        mysqli_stmt_bind_param($stmt, "si", $nonCanonicalBaseID, $week);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        while ($row = mysqli_fetch_assoc($result)) {
            $leaderID = $row['leaderID'];
            $log[] = "  Found: leaderID=$leaderID, baseID=$nonCanonicalBaseID -> $canonicalBaseID";
            
            if (!$dryRun) {
                // Check if canonical row exists
                $checkSql = "SELECT COUNT(*) FROM deckmetastats WHERE leaderID = ? AND baseID = ? AND week = ?";
                $checkStmt = mysqli_stmt_init($conn);
                mysqli_stmt_prepare($checkStmt, $checkSql);
                mysqli_stmt_bind_param($checkStmt, "ssi", $leaderID, $canonicalBaseID, $week);
                mysqli_stmt_execute($checkStmt);
                mysqli_stmt_bind_result($checkStmt, $count);
                mysqli_stmt_fetch($checkStmt);
                mysqli_stmt_close($checkStmt);
                
                if ($count == 0) {
                    // Insert new canonical row with the non-canonical data
                    $insertSql = "INSERT INTO deckmetastats (leaderID, baseID, week, numWins, numPlays, playsGoingFirst, turnsInWins, totalTurns, cardsResourcedInWins, totalCardsResourced, remainingHealthInWins, winsGoingFirst, winsGoingSecond) 
                                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                    $insertStmt = mysqli_stmt_init($conn);
                    mysqli_stmt_prepare($insertStmt, $insertSql);
                    mysqli_stmt_bind_param($insertStmt, "ssiiiiiiiiiii", 
                        $leaderID, $canonicalBaseID, $week,
                        $row['numWins'], $row['numPlays'], $row['playsGoingFirst'],
                        $row['turnsInWins'], $row['totalTurns'], 
                        $row['cardsResourcedInWins'], $row['totalCardsResourced'],
                        $row['remainingHealthInWins'], $row['winsGoingFirst'], $row['winsGoingSecond']
                    );
                    mysqli_stmt_execute($insertStmt);
                    mysqli_stmt_close($insertStmt);
                    $log[] = "    Created new canonical row";
                } else {
                    // Update existing canonical row by adding the values
                    $updateSql = "UPDATE deckmetastats SET 
                        numWins = numWins + ?,
                        numPlays = numPlays + ?,
                        playsGoingFirst = playsGoingFirst + ?,
                        turnsInWins = turnsInWins + ?,
                        totalTurns = totalTurns + ?,
                        cardsResourcedInWins = cardsResourcedInWins + ?,
                        totalCardsResourced = totalCardsResourced + ?,
                        remainingHealthInWins = remainingHealthInWins + ?,
                        winsGoingFirst = winsGoingFirst + ?,
                        winsGoingSecond = winsGoingSecond + ?
                        WHERE leaderID = ? AND baseID = ? AND week = ?";
                    $updateStmt = mysqli_stmt_init($conn);
                    mysqli_stmt_prepare($updateStmt, $updateSql);
                    mysqli_stmt_bind_param($updateStmt, "iiiiiiiiiissi",
                        $row['numWins'], $row['numPlays'], $row['playsGoingFirst'],
                        $row['turnsInWins'], $row['totalTurns'], 
                        $row['cardsResourcedInWins'], $row['totalCardsResourced'],
                        $row['remainingHealthInWins'], $row['winsGoingFirst'], $row['winsGoingSecond'],
                        $leaderID, $canonicalBaseID, $week
                    );
                    mysqli_stmt_execute($updateStmt);
                    mysqli_stmt_close($updateStmt);
                    $log[] = "    Merged into existing canonical row";
                }
                
                // Delete the non-canonical row
                $deleteSql = "DELETE FROM deckmetastats WHERE leaderID = ? AND baseID = ? AND week = ?";
                $deleteStmt = mysqli_stmt_init($conn);
                mysqli_stmt_prepare($deleteStmt, $deleteSql);
                mysqli_stmt_bind_param($deleteStmt, "ssi", $leaderID, $nonCanonicalBaseID, $week);
                mysqli_stmt_execute($deleteStmt);
                mysqli_stmt_close($deleteStmt);
                $log[] = "    Deleted non-canonical row";
            }
            
            $mergedCount++;
        }
        mysqli_stmt_close($stmt);
    }
    
    $log[] = "  Processed $mergedCount rows";
    return ['count' => $mergedCount, 'log' => $log];
}

/**
 * Process deckmetamatchupstats table for a given week
 * Single pass: check BOTH baseID and opponentBaseID for each row
 */
function processDeckMetaMatchupStats($conn, $week, $dryRun) {
    global $canonicalBases;
    $nonCanonicalBases = getNonCanonicalBaseMapping($conn);
    
    $mergedCount = 0;
    $log = [];
    
    // Get ALL rows for this week - we'll check each one
    $sql = "SELECT * FROM deckmetamatchupstats WHERE week = ?";
    $stmt = mysqli_stmt_init($conn);
    if (!mysqli_stmt_prepare($stmt, $sql)) {
        $log[] = "  Error preparing statement: " . mysqli_error($conn);
        return ['count' => 0, 'log' => $log];
    }
    mysqli_stmt_bind_param($stmt, "i", $week);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    // Collect all rows first (so we don't modify while iterating)
    $rowsToProcess = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $baseID = $row['baseID'];
        $opponentBaseID = $row['opponentBaseID'];
        
        // Check if either base needs normalization
        $baseNeedsNormalization = isset($nonCanonicalBases[$baseID]);
        $opponentNeedsNormalization = isset($nonCanonicalBases[$opponentBaseID]);
        
        if ($baseNeedsNormalization || $opponentNeedsNormalization) {
            $rowsToProcess[] = $row;
        }
    }
    mysqli_stmt_close($stmt);
    
    // Now process each row that needs normalization
    foreach ($rowsToProcess as $row) {
        $leaderID = $row['leaderID'];
        $baseID = $row['baseID'];
        $opponentLeaderID = $row['opponentLeaderID'];
        $opponentBaseID = $row['opponentBaseID'];
        
        // Determine canonical base IDs
        $canonicalBaseID = $baseID;
        if (isset($nonCanonicalBases[$baseID])) {
            $aspect = $nonCanonicalBases[$baseID];
            $canonicalBaseID = $canonicalBases[$aspect];
        }
        
        $canonicalOpponentBaseID = $opponentBaseID;
        if (isset($nonCanonicalBases[$opponentBaseID])) {
            $aspect = $nonCanonicalBases[$opponentBaseID];
            $canonicalOpponentBaseID = $canonicalBases[$aspect];
        }
        
        // Build descriptive log message
        $baseChange = ($canonicalBaseID !== $baseID) ? "$baseID -> $canonicalBaseID" : $baseID;
        $oppBaseChange = ($canonicalOpponentBaseID !== $opponentBaseID) ? "$opponentBaseID -> $canonicalOpponentBaseID" : $opponentBaseID;
        $log[] = "  Found: $leaderID/$baseChange vs $opponentLeaderID/$oppBaseChange";
        
        if (!$dryRun) {
            // Merge into canonical row
            mergeMatchupRow($conn, $row, $leaderID, $canonicalBaseID, $opponentLeaderID, $canonicalOpponentBaseID, $week, $log);
            
            // Delete the original non-canonical row
            $deleteSql = "DELETE FROM deckmetamatchupstats WHERE leaderID = ? AND baseID = ? AND opponentLeaderID = ? AND opponentBaseID = ? AND week = ?";
            $deleteStmt = mysqli_stmt_init($conn);
            mysqli_stmt_prepare($deleteStmt, $deleteSql);
            mysqli_stmt_bind_param($deleteStmt, "ssssi", $leaderID, $baseID, $opponentLeaderID, $opponentBaseID, $week);
            mysqli_stmt_execute($deleteStmt);
            mysqli_stmt_close($deleteStmt);
            $log[] = "    Deleted original row";
        }
        
        $mergedCount++;
    }
    
    $log[] = "  Processed $mergedCount rows";
    return ['count' => $mergedCount, 'log' => $log];
}

/**
 * Merge a matchup row into the canonical row (create if needed)
 */
function mergeMatchupRow($conn, $row, $leaderID, $baseID, $opponentLeaderID, $opponentBaseID, $week, &$log) {
    // Check if canonical row exists
    $checkSql = "SELECT COUNT(*) FROM deckmetamatchupstats WHERE leaderID = ? AND baseID = ? AND opponentLeaderID = ? AND opponentBaseID = ? AND week = ?";
    $checkStmt = mysqli_stmt_init($conn);
    mysqli_stmt_prepare($checkStmt, $checkSql);
    mysqli_stmt_bind_param($checkStmt, "ssssi", $leaderID, $baseID, $opponentLeaderID, $opponentBaseID, $week);
    mysqli_stmt_execute($checkStmt);
    mysqli_stmt_bind_result($checkStmt, $count);
    mysqli_stmt_fetch($checkStmt);
    mysqli_stmt_close($checkStmt);
    
    if ($count == 0) {
        // Insert new canonical row
        $insertSql = "INSERT INTO deckmetamatchupstats (leaderID, baseID, opponentLeaderID, opponentBaseID, week, numWins, numPlays, playsGoingFirst, turnsInWins, totalTurns, cardsResourcedInWins, totalCardsResourced, remainingHealthInWins, winsGoingFirst, winsGoingSecond) 
                      VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $insertStmt = mysqli_stmt_init($conn);
        mysqli_stmt_prepare($insertStmt, $insertSql);
        mysqli_stmt_bind_param($insertStmt, "ssssiiiiiiiiiii", 
            $leaderID, $baseID, $opponentLeaderID, $opponentBaseID, $week,
            $row['numWins'], $row['numPlays'], $row['playsGoingFirst'],
            $row['turnsInWins'], $row['totalTurns'], 
            $row['cardsResourcedInWins'], $row['totalCardsResourced'],
            $row['remainingHealthInWins'], $row['winsGoingFirst'], $row['winsGoingSecond']
        );
        mysqli_stmt_execute($insertStmt);
        mysqli_stmt_close($insertStmt);
        $log[] = "    Created new canonical row";
    } else {
        // Update existing canonical row
        $updateSql = "UPDATE deckmetamatchupstats SET 
            numWins = numWins + ?,
            numPlays = numPlays + ?,
            playsGoingFirst = playsGoingFirst + ?,
            turnsInWins = turnsInWins + ?,
            totalTurns = totalTurns + ?,
            cardsResourcedInWins = cardsResourcedInWins + ?,
            totalCardsResourced = totalCardsResourced + ?,
            remainingHealthInWins = remainingHealthInWins + ?,
            winsGoingFirst = winsGoingFirst + ?,
            winsGoingSecond = winsGoingSecond + ?
            WHERE leaderID = ? AND baseID = ? AND opponentLeaderID = ? AND opponentBaseID = ? AND week = ?";
        $updateStmt = mysqli_stmt_init($conn);
        mysqli_stmt_prepare($updateStmt, $updateSql);
        mysqli_stmt_bind_param($updateStmt, "iiiiiiiiiissssi",
            $row['numWins'], $row['numPlays'], $row['playsGoingFirst'],
            $row['turnsInWins'], $row['totalTurns'], 
            $row['cardsResourcedInWins'], $row['totalCardsResourced'],
            $row['remainingHealthInWins'], $row['winsGoingFirst'], $row['winsGoingSecond'],
            $leaderID, $baseID, $opponentLeaderID, $opponentBaseID, $week
        );
        mysqli_stmt_execute($updateStmt);
        mysqli_stmt_close($updateStmt);
        $log[] = "    Merged into existing canonical row";
    }
}

?>

<?php
/**
 * API to retrieve details for a single Melee tournament
 * 
 * Required parameters:
 * - id: Tournament ID to retrieve details for
 * 
 * Optional parameters:
 * - include_matchups: Set to 1 to include detailed matchup data (default: 0)
 */

// Set headers for CORS and JSON response
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

// Include database connection and helper functions
require_once "../Database/ConnectionManager.php";
require_once "../SWUDeck/Custom/CardIdentifiers.php"; // Include the card identifier helpers

// Load the card dictionaries for name lookups
include_once "../SWUDeck/GeneratedCode/GeneratedCardDictionaries.php";

// Function to get card name from UUID
function getCardNameFromUUID($uuid) {
    global $titleData, $subtitleData;
    
    if (empty($uuid)) return null;
    
    $title = isset($titleData[$uuid]) ? $titleData[$uuid] : null;
    $subtitle = isset($subtitleData[$uuid]) ? $subtitleData[$uuid] : null;
    
    if ($title) {
        if ($subtitle) {
            return $title . ", " . $subtitle;
        }
        return $title;
    }
    
    return null;
}

// Get database connection
$conn = GetLocalMySQLConnection();

// Get tournament ID parameter
$tournamentId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$includeMatchups = isset($_GET['include_matchups']) && $_GET['include_matchups'] == 1;

// Validate tournament ID
if ($tournamentId <= 0) {
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "message" => "Missing or invalid tournament ID"
    ]);
    mysqli_close($conn);
    exit();
}

// Fetch tournament data
$tournamentSql = "SELECT * FROM meleetournament WHERE tournamentID = ?";
$tournamentStmt = mysqli_prepare($conn, $tournamentSql);
mysqli_stmt_bind_param($tournamentStmt, "i", $tournamentId);
mysqli_stmt_execute($tournamentStmt);
$tournamentResult = mysqli_stmt_get_result($tournamentStmt);

// Check if tournament exists
if (mysqli_num_rows($tournamentResult) == 0) {
    http_response_code(404);
    echo json_encode([
        "success" => false,
        "message" => "Tournament not found"
    ]);
    mysqli_stmt_close($tournamentStmt);
    mysqli_close($conn);
    exit();
}

// Get tournament data
$tournament = mysqli_fetch_assoc($tournamentResult);
mysqli_stmt_close($tournamentStmt);

// Fetch all decks in this tournament
$decksSql = "SELECT * FROM meleetournamentdeck WHERE tournamentID = ? ORDER BY rank ASC, points DESC";
$decksStmt = mysqli_prepare($conn, $decksSql);
mysqli_stmt_bind_param($decksStmt, "i", $tournamentId);
mysqli_stmt_execute($decksStmt);
$decksResult = mysqli_stmt_get_result($decksStmt);

// Prepare decks array
$decks = [];
$deckIds = [];

while ($deck = mysqli_fetch_assoc($decksResult)) {
    // Calculate win rate
    $totalMatches = $deck['matchWins'] + $deck['matchLosses'] + $deck['matchDraws'];
    $winRate = $totalMatches > 0 ? round(($deck['matchWins'] / $totalMatches) * 100, 2) : 0;
    
    // Calculate game win rate
    $totalGames = $deck['gameWins'] + $deck['gameLosses'] + $deck['gameDraws'];
    $gameWinRate = $totalGames > 0 ? round(($deck['gameWins'] / $totalGames) * 100, 2) : 0;
    
    // Format deck data
    $deckData = [
        "id" => (int)$deck['deckID'],
        "player" => $deck['player'],
        "meleeId" => $deck['sourceID'],
        "leader" => [
            "uuid" => $deck['leader'],
            "name" => getCardNameFromUUID($deck['leader'])
        ],
        "base" => [
            "uuid" => $deck['base'],
            "name" => getCardNameFromUUID($deck['base'])
        ],
        "rank" => (int)$deck['rank'],
        "standings" => [
            "match_record" => "{$deck['matchWins']}-{$deck['matchLosses']}-{$deck['matchDraws']}",
            "match_wins" => (int)$deck['matchWins'],
            "match_losses" => (int)$deck['matchLosses'],
            "match_draws" => (int)$deck['matchDraws'],
            "match_win_rate" => $winRate,
            "game_record" => "{$deck['gameWins']}-{$deck['gameLosses']}-{$deck['gameDraws']}",
            "game_wins" => (int)$deck['gameWins'],
            "game_losses" => (int)$deck['gameLosses'],
            "game_draws" => (int)$deck['gameDraws'],
            "game_win_rate" => $gameWinRate,
        ],
        "points" => (int)$deck['points'],
        "tiebreakers" => [
            "omwp" => round($deck['OMWP'] * 100, 2),
            "tgwp" => round($deck['TGWP'] * 100, 2),
            "ogwp" => round($deck['OGWP'] * 100, 2)
        ],
        "matchups" => []
    ];
    
    $decks[] = $deckData;
    $deckIds[$deck['deckID']] = count($decks) - 1; // Store the index for quick access
}

mysqli_stmt_close($decksStmt);

// If requested, fetch and include matchups
if ($includeMatchups) {
    $matchupSql = "SELECT * FROM meleetournamentmatchup WHERE player IN (
                    SELECT deckID FROM meleetournamentdeck WHERE tournamentID = ?
                  )";
    $matchupStmt = mysqli_prepare($conn, $matchupSql);
    mysqli_stmt_bind_param($matchupStmt, "i", $tournamentId);
    mysqli_stmt_execute($matchupStmt);
    $matchupResult = mysqli_stmt_get_result($matchupStmt);
    
    while ($matchup = mysqli_fetch_assoc($matchupResult)) {
        $playerId = $matchup['player'];
        $opponentId = $matchup['opponent'];
        
        // Only add matchup if both deck IDs are found in our array
        if (isset($deckIds[$playerId]) && isset($deckIds[$opponentId])) {
            $playerIdx = $deckIds[$playerId];
            
            // Find opponent name
            $opponentIdx = $deckIds[$opponentId];
            $opponentName = $decks[$opponentIdx]['player'];
            $opponentLeader = $decks[$opponentIdx]['leader'];
            
            // Add the matchup data to the player's deck
            $decks[$playerIdx]['matchups'][] = [
                "opponent_id" => (int)$opponentId,
                "opponent_name" => $opponentName,
                "opponent_leader" => [
                    "uuid" => $opponentLeader['uuid'],
                    "name" => $opponentLeader['name']
                ],
                "wins" => (int)$matchup['wins'],
                "losses" => (int)$matchup['losses'],
                "draws" => (int)$matchup['draws'],
                "result" => "{$matchup['wins']}-{$matchup['losses']}-{$matchup['draws']}"
            ];
        }
    }
    
    mysqli_stmt_close($matchupStmt);
}

// Prepare tournament response
$response = [
    "success" => true,
    "tournament" => [
        "id" => (int)$tournament['tournamentID'],
        "name" => $tournament['tournamentName'],
        "date" => $tournament['tournamentDate'],
        "link" => (int)$tournament['tournamentLink'],
        "round_id" => (int)$tournament['roundId'],
        "melee_url" => "https://melee.gg/tournament/" . $tournament['tournamentLink']
    ],
    "decks_count" => count($decks),
    "decks" => $decks
];

// Close database connection
mysqli_close($conn);

// Return JSON response
echo json_encode($response, JSON_PRETTY_PRINT);
?>
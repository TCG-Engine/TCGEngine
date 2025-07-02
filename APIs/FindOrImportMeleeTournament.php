<?php
// APIs/FindOrImportMeleeTournament.php
header('Content-Type: application/json');

// Only allow POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Get input
$input = json_decode(file_get_contents('php://input'), true);
if (!isset($input['melee_url']) || !filter_var($input['melee_url'], FILTER_VALIDATE_URL)) {
    echo json_encode(['success' => false, 'message' => 'Invalid or missing melee.gg URL.']);
    exit;
}
$meleeUrl = $input['melee_url'];

// Extract melee.gg tournament ID from URL (assume format: https://melee.gg/Tournament/View/{id} or similar)
if (!preg_match('~melee\.gg/(?:Tournament/View/|tournament/view/|tournament/)([0-9]+)~i', $meleeUrl, $matches)) {
    echo json_encode(['success' => false, 'message' => 'Could not extract tournament ID from URL.']);
    exit;
}
$meleeId = $matches[1];

// DB connection
require_once '../Database/ConnectionManager.php';
$conn = GetLocalMySQLConnection();

// Check if tournament exists in DB (by melee.gg ID or URL)
$stmt = $conn->prepare('SELECT tournamentID FROM meleetournament WHERE tournamentLink = ? OR tournamentID = ? LIMIT 1');
$stmt->execute([$meleeId, $meleeId]);
$row = $stmt->get_result()->fetch_assoc();
if ($row && isset($row['tournamentID'])) {
    echo json_encode(['success' => true, 'tournament_id' => $row['tournamentID']]);
    exit;
}

// Not found, try to import using MeleeTournamentParserAPI.php
require_once '../Stats/MeleeTournamentParserAPI.php';
if (!function_exists('importMeleeTournamentById')) {
    echo json_encode(['success' => false, 'message' => 'Parser function not found.']);
    exit;
}

$tournamentId = importMeleeTournamentById($meleeId);
if ($tournamentId) {
    echo json_encode(['success' => true, 'tournament_id' => $tournamentId]);
    exit;
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to import tournament from melee.gg.']);
    exit;
}

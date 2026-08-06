<?php
// Maintenance gate — must precede any write.
// Imports a tournament, which INSERTs meleetournamentdeck (a migration target).
require_once __DIR__ . '/../AppCore/SWU/Maintenance.php';
SWUMaintenanceRequire('SWUDeck', 'stats');

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

// Check if tournament exists in DB (by melee.gg ID or URL).
// A row with zero decks is the residue of an import that died partway through — treat it as
// not-imported and let the import below run again, otherwise every retry reports success on
// an empty tournament.
$stmt = $conn->prepare(
    'SELECT mt.tournamentID, (SELECT COUNT(*) FROM meleetournamentdeck mtd WHERE mtd.tournamentId = mt.tournamentID) AS deckCount
     FROM meleetournament mt WHERE mt.tournamentLink = ? OR mt.tournamentID = ? LIMIT 1'
);
$stmt->execute([$meleeId, $meleeId]);
$row = $stmt->get_result()->fetch_assoc();
if ($row && isset($row['tournamentID']) && (int)$row['deckCount'] > 0) {
    echo json_encode(['success' => true, 'tournament_id' => $row['tournamentID']]);
    exit;
}
if ($row && isset($row['tournamentID'])) {
    // Clear the empty shell so the re-import isn't short-circuited by parseMeleeTournament's
    // own "already loaded" roundId check.
    $cleanup = $conn->prepare('DELETE FROM meleetournament WHERE tournamentID = ?');
    $cleanup->execute([$row['tournamentID']]);
}

// Not found, try to import using MeleeTournamentParserAPI.php
require_once '../Stats/MeleeTournamentParserAPI.php';
if (!function_exists('importMeleeTournamentById')) {
    echo json_encode(['success' => false, 'message' => 'Parser function not found.']);
    exit;
}

// A DB/parse fatal here would emit an HTML error page instead of JSON, which the caller
// reports as an opaque failure. Convert it into a readable JSON message.
$failureReason = null;
$diag = null;
try {
    $tournamentId = importMeleeTournamentById($meleeId, null, $failureReason, $diag);
} catch (Throwable $e) {
    $tournamentId = false;
    $failureReason = $e->getMessage();
    $diag = is_array($diag) ? $diag : [];
    $diag['exception'] = [
        'type' => get_class($e),
        'message' => $e->getMessage(),
        'file' => basename($e->getFile()),
        'line' => $e->getLine(),
    ];
}
if ($tournamentId) {
    echo json_encode(['success' => true, 'tournament_id' => $tournamentId]);
    exit;
}

// Failure: report the specific reason plus the step-by-step trace. `diagnostics` is an
// additive field on the existing {success, message} shape — existing consumers that only
// read `success`/`message` are unaffected.
$message = 'Failed to import tournament from melee.gg.';
if (!empty($failureReason)) $message .= ' ' . $failureReason;
if (!is_array($diag)) $diag = [];
$diag += ['parserVersion' => defined('MELEE_PARSER_VERSION') ? MELEE_PARSER_VERSION : 'unknown'];
$diag['meleeTournamentId'] = $meleeId;
echo json_encode([
    'success' => false,
    'message' => $message,
    'reason' => $failureReason,
    'diagnostics' => $diag,
], JSON_UNESCAPED_SLASHES);
exit;

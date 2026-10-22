<?php
// Maintenance gate — must precede any write.
// Imports tournaments, which INSERTs meleetournamentdeck (a migration target).
require_once __DIR__ . '/../AppCore/SWU/Maintenance.php';
SWUMaintenanceRequire('SWUDeck', 'stats');

// APIs/ImportMeleeTournamentPackage.php — moderators upload up to five tournament packages
// (DevTools/melee-package.php, the swustats-melee-package skill) from Stats/MeleeTournaments.php.
// melee.gg 403s this server, so the packages are replayed: no request reaches melee.
// POST multipart packages[] → {success, results: [{file, status, tournament_id, name, decks, matchups, message, warning?}]}
//   status: imported | already_imported | failed; success = no file failed.
header('Content-Type: application/json');

if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../AccountFiles/AccountSessionAPI.php';

$authError = CheckLoggedInUserModStrict();
if ($authError !== '') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => $authError]);
    exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

require_once __DIR__ . '/../Stats/MeleePackage.php';

$files = [];
$upload = $_FILES['packages'] ?? null;
if (is_array($upload) && is_array($upload['name'] ?? null)) {
    foreach ($upload['name'] as $i => $name) {
        $files[] = ['name' => (string)$name, 'tmp' => (string)$upload['tmp_name'][$i], 'error' => (int)$upload['error'][$i]];
    }
}
if (empty($files)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Choose at least one tournament package.']);
    exit;
}
if (count($files) > SWU_MELEE_PACKAGE_MAX_FILES) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'At most ' . SWU_MELEE_PACKAGE_MAX_FILES . ' packages at a time.']);
    exit;
}

require_once '../Stats/MeleeTournamentParserAPI.php';
require_once '../Database/ConnectionManager.php';
$conn = GetLocalMySQLConnection();

$results = [];
foreach ($files as $f) {
    $row = ['file' => $f['name'], 'status' => 'failed', 'tournament_id' => null, 'name' => null, 'decks' => null, 'matchups' => null];
    if ($f['error'] !== UPLOAD_ERR_OK || !is_uploaded_file($f['tmp'])) {
        $row['message'] = $f['error'] === UPLOAD_ERR_INI_SIZE || $f['error'] === UPLOAD_ERR_FORM_SIZE
            ? 'The file is too large to upload.' : 'The upload did not arrive.';
        $results[] = $row;
        continue;
    }
    if (filesize($f['tmp']) > SWU_MELEE_PACKAGE_MAX_BYTES) {
        $row['message'] = 'The file is larger than the package limit.';
        $results[] = $row;
        continue;
    }
    $pkg = SWUMeleePackageDecode(file_get_contents($f['tmp']), $decodeError);
    if ($pkg === null) {
        $row['message'] = $decodeError;
        $results[] = $row;
        continue;
    }
    $r = SWUMeleePackageImport($conn, $pkg);
    $row['status'] = $r['status'];
    $row['tournament_id'] = $r['tournament_id'];
    $row['message'] = $r['message'];
    if (is_array($r['summary'])) {
        $row['name'] = $r['summary']['tournament_name'];
        $row['decks'] = $r['summary']['decks'];
        $row['matchups'] = $r['summary']['matchups'];
    }
    if (isset($r['warning'])) $row['warning'] = $r['warning'];
    $results[] = $row;
}
$conn->close();

$failed = array_filter($results, fn($r) => $r['status'] === 'failed');
echo json_encode(['success' => empty($failed), 'results' => $results], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

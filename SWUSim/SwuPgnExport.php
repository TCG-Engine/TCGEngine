<?php
// Export SWUPGN: GET ?gameName&playerID&authKey → {success, filename, pgn, summary} — the caller's OWN view of a finished
// game. The seat is the one the seat key authenticates; spectators and wrong keys get 403, an unfinished game 404.
error_reporting(E_ALL & ~E_DEPRECATED);
ini_set('display_errors', '0');
header('Content-Type: application/json');
header('Cache-Control: no-store');
include_once __DIR__ . '/../Core/HTTPLibraries.php';
include_once __DIR__ . '/../Core/GameAuth.php';
include_once __DIR__ . '/../Core/ViewerIdentity.php';
if (!function_exists('ConvertMzIDToAbsolute')) { function ConvertMzIDToAbsolute($mzID, $playerPerspective): string { return ''; } }
include_once __DIR__ . '/../Core/DeterministicRNG.php';
include_once __DIR__ . '/../Core/CoreZoneModifiers.php';
include_once __DIR__ . '/../Core/NetworkingLibraries.php';
include_once __DIR__ . '/ZoneClasses.php';
include_once __DIR__ . '/ZoneAccessors.php';
include_once __DIR__ . '/GeneratedCode/GeneratedCardDictionaries.php';
include_once __DIR__ . '/GamestateParser.php';
include_once __DIR__ . '/Custom/GameLogic.php';
require_once __DIR__ . '/Custom/SwuPgnViewerStore.php';   // _SwuPgnViewerSummary

function SwuPgnExportRespond(array $r): void {
    if (!$r['ok']) { http_response_code($r['status']); echo json_encode(['success' => false, 'message' => $r['message']]); exit; }
    unset($r['ok']);
    echo json_encode(['success' => true] + $r, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

SwuPgnExportRespond(SwuPgnExportRequest($_GET, $_COOKIE));

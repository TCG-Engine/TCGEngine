<?php
// SWU-PGN replay viewer endpoint. open (POST file text) · info (GET) · seek (POST JSON).
// Guests may use it: viewer games are private, read-only, capped per client and overall (SwuPgnViewerLimits.php)
// and expire after 24 hours.
error_reporting(E_ALL & ~E_DEPRECATED);
ini_set('display_errors', '0');
header('Content-Type: application/json');
include_once __DIR__ . '/../Core/HTTPLibraries.php';
include_once __DIR__ . '/../Core/GameAuth.php';
if (!function_exists('ConvertMzIDToAbsolute')) { function ConvertMzIDToAbsolute($mzID, $playerPerspective): string { return ''; } }
if (!function_exists('QueueDamageAnimation')) { function QueueDamageAnimation($t, $a): void {} }
if (!function_exists('QueueRestoreAnimation')) { function QueueRestoreAnimation($t, $a): void {} }
if (!function_exists('QueuePreventedDamageAnimation')) { function QueuePreventedDamageAnimation($t): void {} }
include_once __DIR__ . '/../Core/DeterministicRNG.php';
include_once __DIR__ . '/../Core/CoreZoneModifiers.php';
include_once __DIR__ . '/../Core/NetworkingLibraries.php';
include_once __DIR__ . '/ZoneClasses.php';
include_once __DIR__ . '/ZoneAccessors.php';
include_once __DIR__ . '/GeneratedCode/GeneratedCardDictionaries.php';
include_once __DIR__ . '/GamestateParser.php';
include_once __DIR__ . '/TurnController.php';
include_once __DIR__ . '/Custom/GameLogic.php';
include_once __DIR__ . '/Custom/CombatLogic.php';
require_once __DIR__ . '/Custom/SwuPgnViewerStore.php';

function SwuPgnViewerRespond(array $r): void
{
    if (!$r['ok']) { http_response_code($r['status']); echo json_encode(['success' => false, 'message' => $r['message']]); exit; }
    unset($r['ok']);
    echo json_encode(['success' => true] + $r, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

$action = strval($_GET['action'] ?? '');
$appBase = rtrim(dirname(dirname($_SERVER['SCRIPT_NAME'] ?? '/TCGEngine/SWUSim/SwuPgnViewer.php')), '/') . '/';
if ($action === 'open' && ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $r = SwuPgnViewerOpen((string)file_get_contents('php://input', false, null, 0, SWUPGN_VIEWER_MAX_BYTES + 1),
        substr(hash('sha256', strval($_SERVER['REMOTE_ADDR'] ?? '')), 0, 16));
    if ($r['ok']) {
        $seat = $r['meta']['perspective'] === 'P2' ? 2 : 1;
        $r['viewUrl'] = $appBase . 'NextTurn.php?' . http_build_query(['gameName' => $r['gameName'], 'playerID' => 'S', 'folderPath' => 'SWUSim',
            'authKey' => $r['key'], 'viewerPerspective' => $seat, 'replay' => 1, 'swupgnViewer' => 1]);
    }
    SwuPgnViewerRespond($r);
}
if ($action === 'info') SwuPgnViewerRespond(SwuPgnViewerInfo(strval($_GET['gameName'] ?? ''), strval($_GET['key'] ?? '')));
if ($action === 'seek' && ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $b = json_decode((string)file_get_contents('php://input'), true);
    if (!is_array($b)) SwuPgnViewerRespond(['ok' => false, 'status' => 400, 'message' => 'Invalid request.']);
    SwuPgnViewerRespond(SwuPgnViewerSeek(strval($b['gameName'] ?? ''), strval($b['key'] ?? ''), intval($b['step'] ?? 0),
        isset($b['view']) ? strval($b['view']) : null));
}
SwuPgnViewerRespond(['ok' => false, 'status' => 400, 'message' => 'Unsupported action.']);

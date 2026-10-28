<?php
// Experimental read-only projection; not used by the existing match renderer.
ini_set('display_errors', '0');
chdir(dirname(__DIR__));
require_once 'Core/EngineActionRunner.php';
require_once 'Core/NetworkingLibraries.php';
require_once 'Core/HTTPLibraries.php';
require_once 'Core/ViewerIdentity.php';
require_once 'Core/GameAuth.php';
require_once 'Core/GameWriteLock.php';
header('Content-Type: application/json');
header('Cache-Control: private, no-store');
header('X-Content-Type-Options: nosniff');
function GAUIReplyError(int $status, string $message): void {
    http_response_code($status);
    echo json_encode(['error' => $message]);
    exit;
}
$input = ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' ? $_POST : $_GET;
foreach (['gameName', 'playerID', 'authKey'] as $key) {
    if (!isset($input[$key]) || !is_string($input[$key])) GAUIReplyError(400, 'Invalid request');
}
$gameName = $input['gameName'];
if (!preg_match('/^[0-9]+$/D', $gameName)) GAUIReplyError(400, 'Invalid request');
$viewer = NormalizeViewerIdentity($input['playerID']);
if ($viewer['viewerID'] === '' || !SimGameValidateViewerAuth('GrandArchiveSim', $gameName, $viewer, $input['authKey'])) {
    GAUIReplyError(403, 'Access denied');
}
$lock = SimGameAcquireWriteLock('GrandArchiveSim', $gameName, 100);
if (!is_resource($lock)) GAUIReplyError(409, 'Match unavailable; try again');
try {
    if (!is_file("GrandArchiveSim/Games/$gameName/Gamestate.txt")) GAUIReplyError(404, 'Match unavailable');
    $playerID = $viewer['viewerSeat'] ?? 1;
    EngineLoadRootRuntime('GrandArchiveSim');
    ParseGamestate('./GrandArchiveSim/');
    require_once 'GrandArchiveSim/Custom/UIProjection.php';
    require_once 'GrandArchiveSim/Custom/UIControls.php';
    echo json_encode(GAUILiveProjection($GLOBALS, $viewer['viewerSeat']), JSON_THROW_ON_ERROR);
} finally {
    SimGameReleaseWriteLock($lock);
}

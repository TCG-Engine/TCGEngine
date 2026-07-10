<?php
// SWUSim/CosmeticsLive.php — polled by the in-game board to pick up live cosmetic changes
// (the picker patches the match snapshot; this re-emits the viewer-relative payload). Read-only.
header('Content-Type: application/json');
require_once __DIR__ . '/../AccountFiles/AccountSessionAPI.php';
require_once __DIR__ . '/../Database/ConnectionManager.php';
require_once __DIR__ . '/../Database/functions.inc.php';
require_once __DIR__ . '/CosmeticsBridge.php';           // SWUBuildCosmeticsPayload
require_once __DIR__ . '/Custom/GameLayoutDevice.php';   // SWUSimIsMobileRequest

CheckSession();
$uid = isset($_SESSION['userid']) ? (int)$_SESSION['userid'] : 0;
if ($uid === 0) { echo '{}'; exit; }                     // no session -> neutral, poller treats as "no change"

$gameName = $_GET['gameName'] ?? '';
$vp = (($_GET['viewerPerspective'] ?? '1') === '2') ? 2 : 1;
$mobile = function_exists('SWUSimIsMobileRequest') ? SWUSimIsMobileRequest() : false;
$overrides = SWUCosmeticSeatOverrides($_GET['authKey'] ?? '');   // keep schema-editor P2 playmat stable across polls

echo json_encode(SWUBuildCosmeticsPayload($gameName, $vp, $uid, $mobile, $overrides));

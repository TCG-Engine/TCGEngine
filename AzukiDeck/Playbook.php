<?php

require_once __DIR__ . '/../Core/HTTPLibraries.php';
require_once __DIR__ . '/../Core/AssetPlaybookService.php';
require_once __DIR__ . '/../AccountFiles/AccountSessionAPI.php';
require_once __DIR__ . '/../AccountFiles/AccountDatabaseAPI.php';
require_once __DIR__ . '/../Database/ConnectionManager.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

function AzukiDeckPlaybookRespond($payload, $status = 200) {
  http_response_code($status);
  echo json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
  exit();
}

function AzukiDeckPlaybookSameOriginWrite() {
  if (strcasecmp((string)($_SERVER['HTTP_X_REQUESTED_WITH'] ?? ''), 'XMLHttpRequest') !== 0) return false;
  $fetchSite = strtolower(trim((string)($_SERVER['HTTP_SEC_FETCH_SITE'] ?? '')));
  if ($fetchSite === 'cross-site') return false;

  $origin = trim((string)($_SERVER['HTTP_ORIGIN'] ?? ''));
  if ($origin === '') return true;
  $originHost = strtolower((string)parse_url($origin, PHP_URL_HOST));
  $requestHost = strtolower(preg_replace('/:\d+$/', '', (string)($_SERVER['HTTP_HOST'] ?? '')));
  return $originHost !== '' && hash_equals($requestHost, $originHost);
}

if (!IsUserLoggedIn()) {
  AzukiDeckPlaybookRespond(['success' => false, 'error' => 'You must be logged in to edit deck lines.'], 401);
}

$deckID = trim((string)TryGet('deckID', ''));
if (!preg_match('/^\d+$/', $deckID)) {
  AzukiDeckPlaybookRespond(['success' => false, 'error' => 'Missing or invalid deckID.'], 400);
}

$asset = LoadAssetData(1, $deckID);
if (!is_array($asset) || (string)($asset['assetOwner'] ?? '') !== (string)LoggedInUser()) {
  AzukiDeckPlaybookRespond(['success' => false, 'error' => 'You do not own this deck.'], 403);
}

$method = strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET'));
$assetsRoot = __DIR__ . '/Games';

if ($method === 'GET') {
  $result = AssetPlaybookLoad($assetsRoot, $deckID);
  if (!$result['success']) AzukiDeckPlaybookRespond($result, 500);
  AzukiDeckPlaybookRespond($result);
}

if ($method !== 'POST') {
  header('Allow: GET, POST');
  AzukiDeckPlaybookRespond(['success' => false, 'error' => 'Method not allowed.'], 405);
}

if (!AzukiDeckPlaybookSameOriginWrite()) {
  AzukiDeckPlaybookRespond(['success' => false, 'error' => 'This request was not accepted.'], 403);
}

$contentLength = intval($_SERVER['CONTENT_LENGTH'] ?? 0);
if ($contentLength > 524288) {
  AzukiDeckPlaybookRespond(['success' => false, 'error' => 'The playbook payload is too large.'], 413);
}

$payload = json_decode((string)file_get_contents('php://input'), true);
if (!is_array($payload) || !isset($payload['playbook']) || !is_array($payload['playbook'])) {
  AzukiDeckPlaybookRespond(['success' => false, 'error' => 'Invalid playbook payload.'], 400);
}

$result = AssetPlaybookSave(
  $assetsRoot,
  $deckID,
  $payload['playbook'],
  intval($payload['revision'] ?? -1)
);
$status = intval($result['status'] ?? ($result['success'] ? 200 : 500));
AzukiDeckPlaybookRespond($result, $status);

?>

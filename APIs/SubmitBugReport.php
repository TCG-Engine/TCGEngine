<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');

// is_file: a box with no remote intake (petranaki.net) has no use for this file, and a missing-include warning
// would land in the JSON (display_errors is on above).
if (is_file(__DIR__ . '/../APIKeys/APIKeys.php')) include_once __DIR__ . '/../APIKeys/APIKeys.php';
include_once __DIR__ . '/../AccountFiles/AccountSessionAPI.php';
include_once __DIR__ . '/../Core/GameAuth.php';
include_once __DIR__ . '/../Core/HTTPLibraries.php';
include_once __DIR__ . '/../Core/ViewerIdentity.php';
include_once __DIR__ . '/../Core/RegressionTestFramework.php';

function SubmitBugReportRespond(int $statusCode, array $payload): void
{
  http_response_code($statusCode);
  echo json_encode($payload);
  exit;
}

function SubmitBugReportTrim(?string $value, int $maxLength): string
{
  if ($value === null) return '';
  return substr(trim($value), 0, $maxLength);
}

function SubmitBugReportIsFolderPathValid(string $folderPath): bool
{
  return preg_match('/^[A-Za-z0-9_\/\-]+$/', $folderPath) === 1;
}

// SWUSim reports are stored on this box (LocalBugReportLib.php), never forwarded
// (docs/superpowers/specs/2026-10-22-petranaki-local-bug-reports-design.md). Players said reports they sent never
// arrived, so a report whose game or seat key can't be confirmed is still SAVED, as "unverified" and without a
// snapshot (an unchecked request must not pull a game's hidden state). Only an empty description (400) or a rate
// limit (429) is turned away; a retry carrying an already-saved clientToken gets the same P-<n> back.
function SubmitBugReportLocal(array $body): void
{
  require_once __DIR__ . '/../Database/ConnectionManager.php';
  require_once __DIR__ . '/../LocalBugReportLib.php';

  // mb_strcut: 4000 BYTES, but never inside a character (a half "é" or emoji made the report unreadable).
  $description = mb_strcut(trim(strval($body['description'] ?? '')), 0, 4000, 'UTF-8');
  if ($description === '') SubmitBugReportRespond(400, ['error' => 'Description is required.']);

  $clientToken = SubmitBugReportTrim($body['clientToken'] ?? '', 64);
  if (preg_match('/^[A-Za-z0-9-]{8,64}$/', $clientToken) !== 1) $clientToken = bin2hex(random_bytes(16));

  $known = LocalBugReportFindByToken($clientToken);
  if ($known !== null) {
    $saved = json_decode((string)@file_get_contents(LocalBugReportStorageDir() . "/P-$known/report.json"), true);
    SubmitBugReportRespond(200, ['success' => true, 'report_number' => "P-$known", 'verified' => !empty($saved['verified']),
      'duplicate' => true, 'message' => "Bug report P-$known saved."]);
  }

  $gameName = SubmitBugReportTrim($body['gameName'] ?? '', 32);
  $authKey = SubmitBugReportTrim($body['authKey'] ?? '', 128);
  $viewerInfo = NormalizeViewerIdentity(SubmitBugReportTrim($body['playerID'] ?? '', 8), SimGameMaxSeats('SWUSim'));
  $reporter = SubmitBugReportTrim($body['reporter'] ?? $body['discordID'] ?? $body['discordId'] ?? $body['discord_user_id'] ?? '', 64);
  if ($reporter !== '' && preg_match('/^[A-Za-z0-9_\.-]{2,64}$/', $reporter) !== 1) $reporter = '';
  if ($reporter === '') {
    CheckSession();
    if (isset($_SESSION['discordID'])) $reporter = SubmitBugReportTrim(strval($_SESSION['discordID']), 64);
  }

  // Any REAL seat may fall back to the cookie, not just seats 1-2 (a spectator has no seat auth).
  if (!$viewerInfo['isSpectator'] && $viewerInfo['viewerSeat'] !== null && $authKey === '' && isset($_COOKIE['lastAuthKey'])) {
    $authKey = SubmitBugReportTrim($_COOKIE['lastAuthKey'], 128);
  }
  $check = LocalBugReportVerifySeat($gameName, $viewerInfo, $authKey);
  $verified = $check['verified'];
  $reason = $check['reason'];
  $gamestateText = $check['gamestateText'];

  $conn = null;
  try { $conn = GetLocalMySQLConnection() ?: null; } catch (Throwable $e) { $conn = null; }
  $now = time();
  $clientIp = strval($_SERVER['REMOTE_ADDR'] ?? '');
  $wait = LocalBugReportRateLimit($conn, $verified, $gameName, strval($viewerInfo['viewerID']), $clientIp, $now);
  if ($wait !== null) {
    SubmitBugReportRespond(429, ['error' => "Please wait $wait seconds before sending another report.", 'retry_after' => $wait]);
  }

  $loggedIn = function_exists('IsUserLoggedIn') && IsUserLoggedIn();
  $save = LocalBugReportSave([
    'gameName' => $gameName,
    'playerID' => strval($viewerInfo['viewerID']),
    'verified' => $verified,
    'unverifiedReason' => $reason,
    'reporter' => $reporter,
    'accountId' => $loggedIn ? intval(LoggedInUser()) : null,
    'accountName' => $loggedIn ? strval(LoggedInUserName()) : null,
    'description' => $description,
    'gamestateHash' => $verified ? RegressionCurrentGamestateHash('SWUSim', $gameName) : '',
    'clientIp' => $clientIp,
    'createdAt' => $now,
  ], $verified ? $gamestateText : null, $clientToken, $conn);
  if (!$save['ok']) SubmitBugReportRespond(500, ['error' => 'The report could not be saved: ' . $save['error']]);

  $n = $save['reportNumber'];
  SubmitBugReportRespond(200, ['success' => true, 'report_number' => "P-$n", 'verified' => $verified,
    'duplicate' => $save['duplicate'], 'message' => "Bug report P-$n saved."]);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  SubmitBugReportRespond(405, ['error' => 'Method not allowed. Use POST.']);
}

// Peek at the root before the remote-intake checks below, so every other root keeps its exact old behaviour.
$peekBody = json_decode(file_get_contents('php://input'), true);
if (is_array($peekBody) && ($peekBody['folderPath'] ?? null) === 'SWUSim') {
  SubmitBugReportLocal($peekBody);
}

$isDevEnv = getenv('DEVENV') === 'true';

if (!isset($bugReportApiUrl) || trim($bugReportApiUrl) === '') {
  SubmitBugReportRespond(500, ['error' => 'Bug report forwarding is not configured (missing URL).']);
}

// The API key is required in prod, but ignored under DEVENV so a local intake can
// accept reports without a shared secret.
if (!$isDevEnv && (!isset($bugReportApiKey) || trim($bugReportApiKey) === '')) {
  SubmitBugReportRespond(500, ['error' => 'Bug report forwarding is not configured (missing API key).']);
}

$body = json_decode(file_get_contents('php://input'), true);
if (json_last_error() !== JSON_ERROR_NONE || !is_array($body)) {
  SubmitBugReportRespond(400, ['error' => 'Invalid JSON body: ' . json_last_error_msg()]);
}

$description = SubmitBugReportTrim($body['description'] ?? '', 4000);
$gameName = SubmitBugReportTrim($body['gameName'] ?? '', 32);
$folderPath = SubmitBugReportTrim($body['folderPath'] ?? '', 100);
$authKey = SubmitBugReportTrim($body['authKey'] ?? '', 128);
$rawPlayerID = SubmitBugReportTrim($body['playerID'] ?? '', 8);
$reporter = SubmitBugReportTrim(
  $body['reporter'] ?? $body['discordID'] ?? $body['discordId'] ?? $body['discord_user_id'] ?? '',
  64
);
if ($description === '') {
  SubmitBugReportRespond(400, ['error' => 'Description is required.']);
}

if ($gameName === '' || !IsGameNameValid($gameName)) {
  SubmitBugReportRespond(400, ['error' => 'Invalid game name.']);
}

if ($folderPath === '' || !SubmitBugReportIsFolderPathValid($folderPath)) {
  SubmitBugReportRespond(400, ['error' => 'Invalid folder path.']);
}

// ⚠ Normalise the reporter AGAINST THE ROOT'S REAL SEAT COUNT — this call used to omit it, and the
// parameter defaults to 2. On a 3-4 seat game that silently rewrote P3 to 'S' (the pre-'S' legacy
// "3 means spectator" rule, which is only correct below three seats) and pushed P4 out of range
// entirely, so both were treated as spectators and their auth check failed with "Invalid auth key."
// Deliberately placed AFTER the folderPath validation above, since SimGameMaxSeats reads it.
// (Reported from a Team Suns game 2026-08-26; Twin Suns had the same bug.)
$viewerInfo = NormalizeViewerIdentity($rawPlayerID, SimGameMaxSeats($folderPath));
$playerID = $viewerInfo['viewerID'];

// Lobby-managed sim games live in APCu and may not have a Games/<name> directory.
// Use the shared storage-aware lookup so both memory-backed and legacy disk games
// can attach their authoritative gamestate snapshot.
if (!SimGameExists($folderPath, $gameName)) {
  SubmitBugReportRespond(404, ['error' => 'Game not found.']);
}

$parserPath = __DIR__ . '/../' . $folderPath . '/GamestateParser.php';
 $zoneClassesPath = __DIR__ . '/../' . $folderPath . '/ZoneClasses.php';
 $zoneAccessorsPath = __DIR__ . '/../' . $folderPath . '/ZoneAccessors.php';
if (!is_file($parserPath)) {
  SubmitBugReportRespond(500, ['error' => 'Game parser not found for root ' . $folderPath . '.']);
}

if (!is_file($zoneClassesPath) || !is_file($zoneAccessorsPath)) {
  SubmitBugReportRespond(500, ['error' => 'Game runtime files are missing for root ' . $folderPath . '.']);
}

include_once $zoneClassesPath;
include_once $zoneAccessorsPath;
include_once $parserPath;
$GLOBALS['gameName'] = strval($gameName);
ParseGamestate(__DIR__ . '/../' . $folderPath . '/');

// Any REAL seat may fall back to the cookie, not just seats 1-2 (a spectator has no seat auth).
if (!$viewerInfo['isSpectator'] && $viewerInfo['viewerSeat'] !== null && $authKey === '') {
  if (isset($_COOKIE['lastAuthKey'])) {
    $authKey = SubmitBugReportTrim($_COOKIE['lastAuthKey'], 128);
  }
}

if ($reporter !== '' && preg_match('/^[A-Za-z0-9_\.-]{2,64}$/', $reporter) !== 1) {
  SubmitBugReportRespond(400, ['error' => 'Invalid Discord ID format.']);
}

if ($reporter === '') {
  CheckSession();
  if (isset($_SESSION['discordID'])) {
    $reporter = SubmitBugReportTrim(strval($_SESSION['discordID']), 64);
  }
}

if (!SimGameValidateViewerAuth($folderPath, $gameName, $viewerInfo, $authKey)) {
  SubmitBugReportRespond(403, ['error' => 'Invalid auth key.']);
}

$gamestateText = RegressionCurrentGamestateText($folderPath, $gameName);
if ($gamestateText === '') {
  SubmitBugReportRespond(500, ['error' => 'Unable to capture the current gamestate.']);
}

$gamestateHash = RegressionCurrentGamestateHash($folderPath, $gameName);
$loggedInUserID = function_exists('IsUserLoggedIn') && IsUserLoggedIn() ? strval(LoggedInUser()) : null;
$loggedInUserName = function_exists('IsUserLoggedIn') && IsUserLoggedIn() ? strval(LoggedInUserName()) : null;
$reporterDisplayName = $reporter !== '' ? $reporter : ($loggedInUserName ?: 'TCGEngine Player');

$payload = [
  'operation' => 'create',
  'origin' => 'engine-ui',
  'discord_channel_id' => '',
  'discord_user_id' => $reporter,
  'discord_username' => $reporterDisplayName,
  'reporter' => $reporter,
  'reporter_account_id' => $loggedInUserID,
  'reporter_account_name' => $loggedInUserName,
  'root_name' => $folderPath,
  'game_name' => $gameName,
  'viewer_player_id' => $playerID,
  'gamestate_hash' => $gamestateHash,
  'snapshot_format' => 'raw-gamestate-v1',
  'gamestate_text' => $gamestateText,
  'description' => $description,
];

$ch = curl_init($bugReportApiUrl);
curl_setopt_array($ch, [
  CURLOPT_POST => true,
  CURLOPT_POSTFIELDS => json_encode($payload),
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_HTTPHEADER => [
    'X-API-Key: ' . ($bugReportApiKey ?? ''),
    'Content-Type: application/json',
  ],
]);

$responseBody = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

if ($responseBody === false) {
  SubmitBugReportRespond(502, ['error' => 'Bug report forwarding failed: ' . $curlError]);
}

$responseJson = json_decode($responseBody, true);
if ($httpCode < 200 || $httpCode >= 300) {
  SubmitBugReportRespond(502, [
    'error' => $responseJson['error'] ?? ('Bug report forwarding failed with HTTP ' . $httpCode),
  ]);
}

SubmitBugReportRespond(200, [
  'success' => true,
  'message' => 'Bug report submitted.',
  'bug_id' => $responseJson['bug_id'] ?? null,
  'gamestate_hash' => $gamestateHash,
]);

?>

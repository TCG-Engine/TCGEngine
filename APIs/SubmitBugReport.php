<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');

include_once __DIR__ . '/../APIKeys/APIKeys.php';
include_once __DIR__ . '/../AccountFiles/AccountSessionAPI.php';
include_once __DIR__ . '/../Core/HTTPLibraries.php';
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

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  SubmitBugReportRespond(405, ['error' => 'Method not allowed. Use POST.']);
}

if (!isset($bugReportApiKey) || trim($bugReportApiKey) === '' || !isset($bugReportApiUrl) || trim($bugReportApiUrl) === '') {
  SubmitBugReportRespond(500, ['error' => 'Bug report forwarding is not configured.']);
}

$body = json_decode(file_get_contents('php://input'), true);
if (json_last_error() !== JSON_ERROR_NONE || !is_array($body)) {
  SubmitBugReportRespond(400, ['error' => 'Invalid JSON body: ' . json_last_error_msg()]);
}

$description = SubmitBugReportTrim($body['description'] ?? '', 4000);
$gameName = SubmitBugReportTrim($body['gameName'] ?? '', 32);
$folderPath = SubmitBugReportTrim($body['folderPath'] ?? '', 100);
$authKey = SubmitBugReportTrim($body['authKey'] ?? '', 128);
$reporter = SubmitBugReportTrim($body['reporter'] ?? '', 64);
$playerID = isset($body['playerID']) ? intval($body['playerID']) : 0;

if ($description === '') {
  SubmitBugReportRespond(400, ['error' => 'Description is required.']);
}

if ($gameName === '' || !IsGameNameValid($gameName)) {
  SubmitBugReportRespond(400, ['error' => 'Invalid game name.']);
}

if ($folderPath === '' || !SubmitBugReportIsFolderPathValid($folderPath)) {
  SubmitBugReportRespond(400, ['error' => 'Invalid folder path.']);
}

$gameDir = __DIR__ . '/../' . $folderPath . '/Games/' . $gameName;
if (!is_dir($gameDir)) {
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

if (($playerID === 1 || $playerID === 2) && $authKey === '') {
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

if ($playerID === 1 && isset($GLOBALS['p1Key']) && $authKey !== strval($GLOBALS['p1Key'])) {
  SubmitBugReportRespond(403, ['error' => 'Invalid auth key for player 1.']);
}

if ($playerID === 2 && isset($GLOBALS['p2Key']) && $authKey !== strval($GLOBALS['p2Key'])) {
  SubmitBugReportRespond(403, ['error' => 'Invalid auth key for player 2.']);
}

$gamestateText = RegressionCurrentGamestateText($folderPath, $gameName);
if ($gamestateText === '') {
  SubmitBugReportRespond(500, ['error' => 'Unable to capture the current gamestate.']);
}

$gamestateHash = RegressionCurrentGamestateHash($folderPath, $gameName);
$loggedInUserID = function_exists('IsUserLoggedIn') && IsUserLoggedIn() ? strval(LoggedInUser()) : null;
$loggedInUserName = function_exists('IsUserLoggedIn') && IsUserLoggedIn() ? strval(LoggedInUserName()) : null;

$payload = [
  'operation' => 'create',
  'origin' => 'engine-ui',
  'discord_channel_id' => '',
  'discord_user_id' => $reporter,
  'discord_username' => $loggedInUserName ?: 'TCGEngine Player',
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
    'X-API-Key: ' . $bugReportApiKey,
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
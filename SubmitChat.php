<?php
include_once './Core/HTTPLibraries.php';
include_once './Core/NetworkingLibraries.php';
include_once './Core/ViewerIdentity.php';

$gameName  = TryGET("gameName", "");
$playerID  = TryGET("playerID", "");
$authKey   = TryGET("authKey", "");
$folderPath = TryGET("folderPath", "");
$chatText  = TryGET("chatText", "");

if ($gameName === "" || !IsGameNameValid($gameName)) { echo "Invalid game name."; exit; }
$viewerInfo = NormalizeViewerIdentity($playerID);
if ($viewerInfo['viewerID'] === '')                  { echo "Invalid player.";    exit; }
$playerID = $viewerInfo['viewerID'];

// Sanitize chat text
$chatText = trim(strip_tags($chatText));
$chatText = substr($chatText, 0, 500);
if ($chatText === "") { echo "Empty message."; exit; }

// Validate auth key for real players (spectators skip auth)
if ($folderPath !== "") {
    $folderPath = preg_replace('/[^A-Za-z0-9_\/\-]/', '', $folderPath);
    if (!SimGameValidateViewerAuth($folderPath, $gameName, $viewerInfo, $authKey)) {
        echo "Invalid auth key."; exit;
    }
}

// Store message in APCu
if (!extension_loaded('apcu') || !apcu_enabled()) { echo "Chat unavailable (APCu not enabled)."; exit; }

$cacheKey = GetChatMessagesCacheKey($gameName);
$existing = apcu_fetch($cacheKey);
$messages = ($existing !== false) ? $existing : [];

$nextId     = empty($messages) ? 1 : (end($messages)['id'] + 1);
$messages[] = [
    'id'       => $nextId,
    'playerID' => $playerID,
    'playerLabel' => $viewerInfo['label'],
    'text'     => $chatText,
    'time'     => time(),
];

// Keep at most 100 messages
if (count($messages) > 100) {
    $messages = array_slice($messages, -100);
}

apcu_store($cacheKey, $messages, 3600);
IncrementChatUpdateVersion($gameName);
echo "OK";

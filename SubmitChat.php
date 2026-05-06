<?php
include_once './Core/HTTPLibraries.php';
include_once './Core/NetworkingLibraries.php';

$gameName  = TryGET("gameName", "");
$playerID  = intval(TryGET("playerID", "0"));
$authKey   = TryGET("authKey", "");
$folderPath = TryGET("folderPath", "");
$chatText  = TryGET("chatText", "");

if ($gameName === "" || !IsGameNameValid($gameName)) { echo "Invalid game name."; exit; }
if ($playerID < 1 || $playerID > 3)                 { echo "Invalid player.";    exit; }

// Sanitize chat text
$chatText = trim(strip_tags($chatText));
$chatText = substr($chatText, 0, 500);
if ($chatText === "") { echo "Empty message."; exit; }

// Validate auth key for real players (spectators playerID=3 skip auth)
if ($playerID !== 3 && $folderPath !== "") {
    $folderPath = preg_replace('/[^A-Za-z0-9_\/\-]/', '', $folderPath);
    $parserPath       = __DIR__ . '/' . $folderPath . '/GamestateParser.php';
    $zoneClassesPath  = __DIR__ . '/' . $folderPath . '/ZoneClasses.php';
    $zoneAccessorsPath = __DIR__ . '/' . $folderPath . '/ZoneAccessors.php';
    if (is_file($parserPath) && is_file($zoneClassesPath) && is_file($zoneAccessorsPath)) {
        include_once $zoneClassesPath;
        include_once $zoneAccessorsPath;
        include_once $parserPath;
        $GLOBALS['gameName'] = strval($gameName);
        ParseGamestate(__DIR__ . '/' . $folderPath . '/');
        $targetKey = $playerID === 1
            ? strval($GLOBALS['p1Key'] ?? '')
            : strval($GLOBALS['p2Key'] ?? '');
        if ($targetKey !== '' && $authKey !== $targetKey) {
            echo "Invalid auth key."; exit;
        }
    }
}

// Store message in APCu
if (!extension_loaded('apcu') || !apcu_enabled()) { echo "Chat unavailable (APCu not enabled)."; exit; }

$cacheKey = "chat_" . $gameName;
$existing = apcu_fetch($cacheKey);
$messages = ($existing !== false) ? $existing : [];

$nextId     = empty($messages) ? 1 : (end($messages)['id'] + 1);
$messages[] = [
    'id'       => $nextId,
    'playerID' => $playerID,
    'text'     => $chatText,
    'time'     => time(),
];

// Keep at most 100 messages
if (count($messages) > 100) {
    $messages = array_slice($messages, -100);
}

apcu_store($cacheKey, $messages, 3600);
echo "OK";

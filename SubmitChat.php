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
$viewerInfo = NormalizeViewerIdentity($playerID, SimGameMaxSeats($folderPath));
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

// Blocked players cannot chat. Generic response — never reveals the block to the other side.
if ($folderPath === 'SWUSim') {
    $swuMatchFlow = __DIR__ . '/SWUSim/MatchFlow.php';
    if (is_file($swuMatchFlow)) {
        include_once $swuMatchFlow;
        if (function_exists('SWUAreGamePlayersBlocked') && SWUAreGamePlayersBlocked($gameName)) {
            echo "Chat disabled."; exit;
        }
    }
}

// Whisper: only the TEXT is private — see Core/ChatWhisper.php. Validated here, redacted at egress.
$whisperTo = [];
$whisperRaw = TryGET("whisperTo", "");
if (trim(strval($whisperRaw)) !== "") {
    include_once './Core/ChatWhisper.php';
    if (!empty($viewerInfo['isSpectator'])) { echo "Spectators cannot whisper."; exit; }
    // An empty folderPath skips auth above, and there is no sim policy to consult — never a whisper.
    $whisperPolicy = ($folderPath !== "") ? __DIR__ . '/' . $folderPath . '/Custom/ChatWhisperPolicy.php' : '';
    if ($whisperPolicy === '' || !is_file($whisperPolicy)) { echo "Whispers are not available."; exit; }
    $senderSeat = intval($viewerInfo['viewerSeat'] ?? 0);
    $whisperTo = ChatParseWhisperTargets($whisperRaw, $senderSeat, SimGameMaxSeats($folderPath));
    if ($whisperTo === null || count($whisperTo) === 0) { echo "Invalid whisper."; exit; }
    include_once $whisperPolicy;   // TOP-LEVEL include on purpose (engine globals)
    if (!function_exists('ChatWhisperAllowed') || !ChatWhisperAllowed($gameName, $senderSeat, $whisperTo)) {
        echo "Whisper not allowed."; exit;
    }
}

// Store message in APCu
if (!extension_loaded('apcu') || !apcu_enabled()) { echo "Chat unavailable (APCu not enabled)."; exit; }

$cacheKey = GetChatMessagesCacheKey($gameName);
$existing = apcu_fetch($cacheKey);
$messages = ($existing !== false) ? $existing : [];

$nextId     = empty($messages) ? 1 : (end($messages)['id'] + 1);
$row = [
    'id'       => $nextId,
    'playerID' => $playerID,
    'playerLabel' => $viewerInfo['label'],
    'text'     => $chatText,
    'time'     => time(),
];
if (!empty($whisperTo)) $row['to'] = $whisperTo;   // public rows keep today's exact shape
$messages[] = $row;

// Keep at most 100 messages
if (count($messages) > 100) {
    $messages = array_slice($messages, -100);
}

apcu_store($cacheKey, $messages, 3600);
IncrementChatUpdateVersion($gameName);
echo "OK";

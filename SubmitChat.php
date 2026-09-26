<?php
include_once './Core/HTTPLibraries.php';
include_once './Core/NetworkingLibraries.php';
include_once './Core/ViewerIdentity.php';

include_once './Core/ChatScopeAuth.php';
include_once './APIs/Lobbies/Classes/Player.php';   // unserializing an APCu lobby needs the class

$playerID   = TryGET("playerID", "");
$authKey    = TryGET("authKey", "");
$folderPath = preg_replace('/[^A-Za-z0-9_\/\-]/', '', TryGET("folderPath", ""));
$chatText   = TryGET("chatText", "");

$scope = ChatResolveRequestScope($_GET, $folderPath);
if ($scope['token'] === null) { echo $scope['error']; exit; }
// The scope token; the name is kept so the rest of this file reads unchanged. For the game scope it
// IS the gameName, exactly as before.
$gameName = $scope['token'];

$viewerInfo = NormalizeViewerIdentity($playerID, SimGameMaxSeats($folderPath));
if ($viewerInfo['viewerID'] === '') { echo "Invalid player."; exit; }
$playerID = $viewerInfo['viewerID'];

// Sanitize chat text
$chatText = trim(strip_tags($chatText));
$chatText = substr($chatText, 0, 500);
if ($chatText === "") { echo "Empty message."; exit; }

// ⚠ The game scope keeps its exact existing behaviour, including "an empty folderPath skips auth"
// (spectators rely on it). The two new scopes ALWAYS authenticate — there is no anonymous write to a
// room's or a match's conversation.
if ($scope['kind'] === 'game') {
    if ($folderPath !== "" && !SimGameValidateViewerAuth($folderPath, $gameName, $viewerInfo, $authKey)) {
        echo "Invalid auth key."; exit;
    }
} else if (!ChatScopeAuthOk($scope['kind'], $gameName, $viewerInfo, $authKey, $folderPath)) {
    echo "Invalid auth key."; exit;
}

// The login gate, via the per-sim seam (Core/ChatPolicy.php) so it also holds on the Waiting Room and
// the Sideboard, which have no gameName. SWUSim needs no account to PLAY, only to CHAT (owner,
// 2026-09-21) — players and spectators alike. The game page renders no message box for a guest; this
// is the enforcement behind it. Read the session and release its lock at once, so a chat send never
// serialises behind the same browser's game polls.
if (session_status() === PHP_SESSION_NONE) session_start();
$chatUserId = intval($_SESSION['userid'] ?? 0);
session_write_close();
$viewerInfo['userId'] = $chatUserId;

include_once './Core/ChatPolicy.php';
$chatRefusal = ChatSendRefusal($folderPath, $viewerInfo, $gameName);
if ($chatRefusal !== null) { echo $chatRefusal; exit; }

// Blocked players cannot chat. Generic response — never reveals the block to the other side.
// ⚠ A LOBBY needs no check: JoinQueue.php's SWUJoinBlocked already refuses a blocked player a seat,
// so two mutually-blocked players can never be in one room. A MATCH does need one — the Sideboard
// sits BETWEEN games, where the per-game check has no gameName to work with.
if ($folderPath === 'SWUSim') {
    $swuMatchFlow = __DIR__ . '/SWUSim/MatchFlow.php';
    if (is_file($swuMatchFlow)) {
        include_once $swuMatchFlow;
        $chatBlocked = false;
        if ($scope['kind'] === 'game' && function_exists('SWUAreGamePlayersBlocked')) {
            $chatBlocked = SWUAreGamePlayersBlocked($gameName);
        } else if ($scope['kind'] === 'match' && function_exists('MatchArePlayersBlocked')) {
            $chatBlocked = MatchArePlayersBlocked('SWUSim', substr($gameName, 2));
        }
        if ($chatBlocked) { echo "Chat disabled."; exit; }
    }
}

// Whisper: only the TEXT is private — see Core/ChatWhisper.php. Validated here, redacted at egress.
$whisperTo = [];
$whisperRaw = TryGET("whisperTo", "");
if (trim(strval($whisperRaw)) !== "") {
    include_once './Core/ChatWhisper.php';
    // Whispers are GAME-ONLY. Twin Suns seats are not assigned until the host presses Start, so a
    // room whisper has no addressable target, and the Sideboard is two-player, where a whisper is
    // just a message.
    if ($scope['kind'] !== 'game')          { echo "Whispers are only available in a game."; exit; }
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
    // Whole seconds cannot order a chat message against the game-log lines around it, and the
    // panel interleaves the two streams. ADDITIVE: 'time' is untouched for every existing reader.
    'ts'       => round(microtime(true), 4),
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

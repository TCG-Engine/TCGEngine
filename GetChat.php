<?php
header('Content-Type: application/json');

include_once './Core/HTTPLibraries.php';
include_once './Core/NetworkingLibraries.php';
include_once './Core/ViewerIdentity.php';
include_once './Core/ChatScopeAuth.php';
include_once './APIs/Lobbies/Classes/Player.php';   // unserializing an APCu lobby needs the class

$folderPath = preg_replace('/[^A-Za-z0-9_]/', '', TryGET("folderPath", ""));
$lastChatID = intval(TryGET("lastChatID", "0"));

// A lobby or match conversation is NOT public: unlike a game, which anyone may spectate, a room's
// talk is between the people in it. Those two scopes therefore require the caller's seat authKey,
// while the game scope keeps its existing open read (spectators depend on it).
$scope = ChatResolveRequestScope($_GET, $folderPath);
if ($scope['token'] === null) { echo "[]"; exit; }

if ($scope['kind'] !== 'game') {
    $viewerInfo = NormalizeViewerIdentity(TryGET("playerID", ""), SimGameMaxSeats($folderPath));
    if (!ChatScopeAuthOk($scope['kind'], $scope['token'], $viewerInfo, TryGET("authKey", ""), $folderPath)) { echo "[]"; exit; }
    echo json_encode(GetChatMessagesSince($scope['token'], $lastChatID, $viewerInfo));
    exit;
}
echo json_encode(GetChatMessagesSince($scope['token'], $lastChatID));

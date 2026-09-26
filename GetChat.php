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
    // ⚠ A READ THAT TICKS THE SIDEBOARD COUNTDOWN, and deliberately so. The Sideboard page polls
    // this every 2s from the moment it loads, which makes it the ONLY reliable tick while neither
    // player has submitted yet: SubmitSideboard's own poll does not start until after you submit,
    // and a spectator's GetNextTurn poll may not exist at all. GetNextTurn.php already ticks
    // MatchSideboardTimeoutCheck from a read for exactly the same reason.
    // The call is a no-op unless this match is sideboarding with a deadline, and it claims its marks
    // under the match lock, so being hit by both seats at once cannot double-post.
    // ⚠ AFTER the auth gate — never tick a match the caller cannot prove they are seated in.
    if ($folderPath === 'SWUSim' && $scope['kind'] === 'match' && strpos($scope['token'], 'm:') === 0) {
        $swuMatchFlow = __DIR__ . '/SWUSim/MatchFlow.php';
        if (is_file($swuMatchFlow)) {
            include_once $swuMatchFlow;
            if (function_exists('SWUSideboardWarningCheck')) SWUSideboardWarningCheck(substr($scope['token'], 2));
        }
    }
    echo json_encode(GetChatMessagesSince($scope['token'], $lastChatID, $viewerInfo));
    exit;
}
echo json_encode(GetChatMessagesSince($scope['token'], $lastChatID));

<?php // SWUSim/AnnounceLeave.php — "<username> left the game." in the chat when a seat goes to the Main Menu
// Owner request 2026-10-10: after a game ends, nobody could tell whether the other player was still on the
// end-game screen, so whether chatting was still worth it. SWUGoMainMenu (GameLayoutShared.php) beacons
// here on the way out; we post ONE system row into the game's chat conversation.
//
// The row is playerID 0, the same shape as the Sideboard countdown notices (Core/Match/MatchFlow.php):
// the board log renders a seat-0 row as a grey log line, and every other surface falls back to its label.
// The TEXT is composed here, never taken from the client — a beacon must not be a way to post arbitrary
// "system" messages.
header('Content-Type: text/plain');
chdir(__DIR__ . '/..');   // the Core includes below resolve from the engine root, as SubmitChat.php's do
include_once './Core/HTTPLibraries.php';
include_once './Core/NetworkingLibraries.php';
include_once './Core/ViewerIdentity.php';
include_once './Core/GameAuth.php';
include_once './SWUSim/MatchFlow.php';

$gameName = preg_replace('/[^A-Za-z0-9_]/', '', strval($_POST['gameName'] ?? ''));
$seat     = intval($_POST['playerID'] ?? 0);   // 'S' → 0: a spectator never "leaves" anybody
$authKey  = strval($_POST['authKey'] ?? '');

if ($gameName === '' || $seat < 1 || $seat > SimGameMaxSeats('SWUSim')) { echo 'Bad request.'; exit; }
if (!is_dir(__DIR__ . '/Games/' . $gameName))                             { echo 'No such game.'; exit; }
if (!SimGameValidateSeatAuth('SWUSim', $gameName, $seat, $authKey))       { echo 'Invalid auth key.'; exit; }

// Blocking is private: a blocked player is never told (BlockedUsers.php), and Block → forfeit → Main Menu
// is one of the ways out. Chat between a blocked pair is already off (SubmitChat.php) — so is this.
if (SWUAreGamePlayersBlocked($gameName)) { echo 'OK'; exit; }

// Once per seat per game: a double click, or Leave in two tabs, says it once.
if (function_exists('apcu_add') && !apcu_add('swu_left_' . $gameName . '_' . $seat, 1, 3600)) { echo 'OK'; exit; }

if (session_status() === PHP_SESSION_NONE) session_start();
$sessionUserId = intval($_SESSION['userid'] ?? 0);
session_write_close();

ChatAppendMessage($gameName, 0, 'Game', SWULeaveNoticeName($gameName, $seat, $sessionUserId) . ' left the game.');
echo 'OK';

// The leaver's name: their username when they are logged in, "Player N" otherwise (a guest's match
// display name is "Guest PN", which reads oddly in a sentence).
// A match game names the seat from the match record. A matchless game (goldfish / hotseat / Arenabot) has
// no record, so the logged-in SESSION names it — the seat's auth key above is what ties it to this seat.
function SWULeaveNoticeName(string $gameName, int $seat, int $sessionUserId): string {
    $ref = SWUReadMatchRef($gameName);
    $m = ($ref !== null && !empty($ref['matchId'])) ? SWUReadMatch($ref['matchId']) : null;
    $userId = is_array($m) ? intval($m['players'][strval($seat)]['userId'] ?? 0) : $sessionUserId;
    if ($userId > 0) {
        if (is_array($m)) {
            $name = strval(MatchSeatDisplayNames($m)[$seat] ?? '');
        } else {
            require_once __DIR__ . '/../Database/ConnectionManager.php';
            require_once __DIR__ . '/../Core/MatchHistory.php';
            $conn = function_exists('GetLocalMySQLConnection') ? GetLocalMySQLConnection() : null;
            $name = $conn ? strval(MatchHistoryUsername($conn, $userId) ?? '') : '';
            if ($conn) $conn->close();
        }
        // MatchSeatDisplayNames keeps its "Guest PN" placeholder when the username lookup fails.
        if ($name !== '' && $name !== 'Guest P' . $seat) return $name;
    }
    return 'Player ' . $seat;
}

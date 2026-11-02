<?php
// Player-report endpoint — the Report half of Report/Block Player (SWUSim-local, swusim DB)
// (docs/superpowers/specs/2026-10-27-saved-chat-and-player-reports-design.md). POST gameName, note, clientToken.
//
// Who may report whom is exactly who may BLOCK whom (BlockedUsers.php's blockOpponent): the logged-in session must be a
// seat of the match behind gameName, and the other seat must be an account. Identity is the session plus
// SWUResolveOpponent, not a seat authKey — a report only ever exists between two accounts in one match, and the
// session is the stronger proof of which account is asking. The reported player is never told.
// Storage, follow-ups and the frozen chat: PlayerReportLib.php.
$__test = !empty($GLOBALS['__REPORT_TEST']);
if (!$__test) ob_start();
require_once __DIR__ . '/../AccountFiles/AccountSessionAPI.php';
require_once __DIR__ . '/../Database/ConnectionManager.php';
require_once __DIR__ . '/../PlayerReportLib.php';
require_once __DIR__ . '/MatchFlow.php';
ChatPersistUseRoot('SWUSim');

$respond = function ($arr) use ($__test) {
    if ($__test) return $arr;
    while (ob_get_level() > 0) { ob_end_clean(); }
    header('Content-Type: application/json'); echo json_encode($arr); exit;
};

if ($__test) {
    $uid = (int)($GLOBALS['__REPORT_TEST_UID'] ?? 0);
    $post = (array)($GLOBALS['__REPORT_TEST_POST'] ?? []);
} else {
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') return $respond(['success' => false, 'error' => 'post_only']);
    CheckSession();
    $uid = isset($_SESSION['userid']) ? (int)$_SESSION['userid'] : 0;
    session_write_close();
    $post = $_POST;
}
if ($uid <= 0) return $respond(['success' => false, 'error' => 'not_logged_in', 'message' => 'Log in to report a player.']);

$gameName = preg_replace('/[^A-Za-z0-9_]/', '', strval($post['gameName'] ?? ''));
$info = $gameName !== '' ? SWUResolveOpponent($gameName, $uid) : null;
if (!$info || empty($info['oppUserId']) || (int)$info['oppUserId'] === $uid) {
    return $respond(['success' => false, 'error' => 'no_opponent', 'message' => 'There is no logged-in opponent to report.']);
}
$match = MatchRead('SWUSim', $info['matchId']);
if (!is_array($match)) return $respond(['success' => false, 'error' => 'no_opponent', 'message' => 'There is no logged-in opponent to report.']);
// 1v1 ONLY. SWUResolveOpponent pairs seat 1 with seat 2 at any table size, so without this a Twin Suns seat 1 could
// report seat 2 (perhaps a teammate) and freeze the whole table's chat — Twin Suns reporting is a non-goal.
if (count((array)($match['players'] ?? [])) > 2) {
    return $respond(['success' => false, 'error' => 'not_one_v_one', 'message' => 'Reporting is only available in 1v1 games.']);
}

// 1000 CHARACTERS, never cut inside one.
$note = mb_substr(mb_scrub(trim(strval($post['note'] ?? '')), 'UTF-8'), 0, 1000, 'UTF-8');
$clientToken = substr(trim(strval($post['clientToken'] ?? '')), 0, 64);
if (preg_match('/^[A-Za-z0-9-]{8,64}$/', $clientToken) !== 1) $clientToken = bin2hex(random_bytes(16));

$conn = null;
try { $conn = GetLocalMySQLConnection() ?: null; } catch (Throwable $e) { $conn = null; }
$clientIp = strval($_SERVER['REMOTE_ADDR'] ?? '');
$now = time();
if (($wait = PlayerReportRateLimit($conn, $uid, $clientIp, $now)) !== null) {
    if ($conn) $conn->close();
    return $respond(['success' => false, 'error' => 'rate_limited', 'message' => 'Too many reports. Try again later.', 'retry_after' => $wait]);
}

$mySeat = (int)$info['mySeat'];
$oppSeat = $mySeat === 1 ? 2 : 1;
$names = MatchSeatDisplayNames($match);
$chatId = strval($match['chatId'] ?? '');
$res = PlayerReportFile([
    'gameName' => $gameName, 'matchId' => strval($info['matchId']),
    'lobbyId' => strncmp($chatId, 'l:', 2) === 0 ? substr($chatId, 2) : '', 'format' => strval($match['format'] ?? ''),
    'reporterId' => $uid, 'reporterName' => strval($names[$mySeat] ?? ''), 'reporterSeat' => $mySeat,
    'reportedId' => (int)$info['oppUserId'], 'reportedName' => strval($names[$oppSeat] ?? ''), 'reportedSeat' => $oppSeat,
    'note' => $note, 'clientIp' => $clientIp, 'createdAt' => $now,
], $clientToken, $conn);
if ($conn) $conn->close();

if (empty($res['ok'])) {
    error_log('SubmitPlayerReport: ' . strval($res['error'] ?? 'unknown failure'));
    return $respond(['success' => false, 'error' => 'save_failed', 'message' => 'The report could not be saved. Please try again.']);
}
$messages = [
    'new'       => 'Reported. A moderator will review it.',
    'duplicate' => 'Reported. A moderator will review it.',
    'followup'  => 'Added to your earlier report.',
    'limit'     => "You've already reported this player for this match.",
];
return $respond(['success' => true, 'kind' => $res['kind'], 'message' => $messages[$res['kind']] ?? $messages['new'],
                 'report_number' => 'R-' . (int)$res['reportNumber']]);

<?php
// zzPlayerReportViewer.php — moderator-only view of Petranaki's player reports (Report/Block Player)
// (docs/superpowers/specs/2026-10-27-saved-chat-and-player-reports-design.md). Gated by CheckLoggedInUserModStrict(),
// the STRICT gate zzBugReportViewer.php uses for a report's package: a report holds whispers in full, as sensitive as
// a package's hidden cards, so DEVENV never opens this page (CheckLoggedInUserMod would) — only a real loopback request
// or a logged-in approved moderator.
//
//   http://localhost:3400/zzPlayerReportViewer.php              the list (open first)
//   http://localhost:3400/zzPlayerReportViewer.php?report=R-3   one report, with its frozen chat
// POST action=close|reopen|delete, id=R-<n> (same-origin only).

include_once './AccountFiles/AccountSessionAPI.php';
include_once './Core/HTTPLibraries.php';

$error = CheckLoggedInUserModStrict();
if ($error !== '') {
    echo htmlspecialchars($error);
    exit;
}

require_once './PlayerReportLib.php';
require_once './PlayerReportViewerLib.php';
require_once './Database/ConnectionManager.php';
require_once './Core/Match/Match.php';
ChatPersistUseRoot('SWUSim');   // "after the report" reads the saved chat

$conn = null;
try { $conn = GetLocalMySQLConnection() ?: null; } catch (Throwable $e) { $conn = null; }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    if (!BugReportViewerIsSameOrigin($_SERVER)) {
        http_response_code(403);
        echo json_encode(['error' => 'Refused: the request did not come from this site.']);
        exit;
    }
    $n = PlayerReportParseNumber(strval($_POST['id'] ?? ''));
    $action = strval($_POST['action'] ?? '');
    if ($n === null || $conn === null || !in_array($action, ['close', 'reopen', 'delete'], true)) {
        echo json_encode(['error' => 'Invalid request.']);
        exit;
    }
    $note = strval($_POST['note'] ?? '');
    echo json_encode($action === 'delete' ? PlayerReportDelete($conn, $n)
        : PlayerReportSetStatus($conn, $n, $action === 'close' ? 'closed' : 'open', $action === 'close' ? $note : ''));
    exit;
}

header('Content-Type: text/html; charset=utf-8');
$n = PlayerReportParseNumber(strval($_GET['report'] ?? ''));
if ($n !== null) {
    $loaded = PlayerReportLoad($n);
    if ($loaded === null) { echo PlayerReportViewerPage('<div class="brv-empty">Player report R-' . $n . ' was not found.</div>'); exit; }
    // The status lives in the table once it exists (a moderator's close/reopen); report.json mirrors it.
    $matchGames = [strval($loaded['report']['game_name'] ?? '')];
    foreach ((array)($loaded['report']['followups'] ?? []) as $f) $matchGames[] = strval($f['game_name'] ?? '');
    $m = ($loaded['report']['match_id'] ?? '') !== '' ? MatchRead('SWUSim', strval($loaded['report']['match_id'])) : null;
    foreach ((array)($m['games'] ?? []) as $g) $matchGames[] = strval($g['gameName'] ?? '');
    echo PlayerReportViewerPage(PlayerReportViewerDetailHtml($loaded, PlayerReportChatAfter($loaded), array_values(array_unique(array_filter($matchGames)))));
    exit;
}

$notice = '';
if ($conn === null) {
    $notice = 'No database connection: reports saved on disk cannot be listed.';
} else if (PlayerReportReconcile($conn) >= 0 && empty(PlayerReportList($conn)) && count(glob(PlayerReportStorageDir() . '/R-*', GLOB_ONLYDIR) ?: []) > 0) {
    $notice = 'Player reports are saved on disk but not in the database. Run Database/migrations/21_swusim_player_reports.sql on this site\'s database; they appear on the next load.';
}
echo PlayerReportViewerPage(PlayerReportViewerListHtml($conn ? PlayerReportList($conn) : []), $notice);

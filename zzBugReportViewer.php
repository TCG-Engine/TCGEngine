<?php
// zzBugReportViewer.php — mod-only dev tool to browse bug reports saved in the shared
// TCGEngine-Discord database, for ANY root (?rootName=SWUSim, GrandArchiveSim, …).
//
// It reads the SAME reports the Discord admin UI does, straight from the remote intake
// API (GET https://…/BugReportAPI.php with X-API-Key), so nothing new is stored locally.
// Loading a report's snapshot into a local game (Current / Last Round / Game Begin) is a
// follow-up; this page is the browser view. Gated by CheckLoggedInUserMod() — the remote
// key never reaches the client, and reports are never exposed to non-mods.
//
//   http://localhost:3400/zzBugReportViewer.php?rootName=SWUSim
//
// Petranaki's own SWUSim reports (LocalBugReportLib.php, 2026-10-22) are listed beside the remote ones as P-<n>.
// Their package (both players' hidden cards) downloads only through this page, only for CheckLoggedInUserModStrict().
// The remote intake is read only when $bugReportApiUrl and $bugReportApiKey are both set (petranaki.net has neither).

include_once './AccountFiles/AccountSessionAPI.php';
include_once './Core/HTTPLibraries.php';

$error = CheckLoggedInUserMod();
if ($error !== '') {
    echo htmlspecialchars($error);
    exit;
}

require_once './BugReportViewerLib.php';
require_once './LocalBugReportLib.php';
require_once './Database/ConnectionManager.php';

$localConn = null;
try { $localConn = GetLocalMySQLConnection() ?: null; } catch (Throwable $e) { $localConn = null; }

// ── Download a Petranaki report's package (GET) — strict mod only: it holds both players' hidden cards. ──────
if (($_GET['action'] ?? '') === 'download') {
    if (CheckLoggedInUserModStrict() !== '') {
        http_response_code(403);
        echo 'Forbidden.';
        exit;
    }
    $n = LocalBugReportParseNumber(strval($_GET['report'] ?? ''));
    $zipPath = $n !== null ? LocalBugReportZipPath($n) : null;
    if ($zipPath === null) {
        http_response_code(404);
        echo 'That report has no snapshot to download (unverified, or not found).';
        exit;
    }
    header('Content-Type: application/zip');
    header('Content-Disposition: attachment; filename="' . LocalBugReportZipName($n) . '"');
    header('Content-Length: ' . filesize($zipPath));
    readfile($zipPath);
    unlink($zipPath);
    exit;
}

// ── Close / Delete a Petranaki report (POST, id "P-<n>"). A numeric id keeps the remote path below. ────────
$postedId = strval($_POST['id'] ?? '');
if ($_SERVER['REQUEST_METHOD'] === 'POST' && stripos($postedId, 'P-') === 0) {
    header('Content-Type: application/json');
    if (!BugReportViewerIsSameOrigin($_SERVER)) {
        http_response_code(403);
        echo json_encode(['error' => 'Refused: the request did not come from this site.']);
        exit;
    }
    $n = LocalBugReportParseNumber($postedId);
    $action = strval($_POST['action'] ?? '');
    if ($n === null || $localConn === null || !in_array($action, ['resolve', 'delete'], true)) {
        echo json_encode(['error' => 'Invalid request.']);
        exit;
    }
    echo json_encode($action === 'delete'
        ? LocalBugReportDelete($localConn, $n)
        : LocalBugReportResolve($localConn, $n, substr(trim(strval($_POST['note'] ?? '')), 0, 1000)));
    exit;
}

$rootName = isset($_GET['rootName']) ? preg_replace('/[^A-Za-z0-9_]/', '', strval($_GET['rootName'])) : '';

// Config lives in APIKeys.php (out of git): $bugReportApiUrl, $bugReportApiKey.
$bugReportApiUrl = '';
$bugReportApiKey = '';
@include './APIKeys/APIKeys.php';

// ── Load action (POST) — write a report's snapshot into a local SWUSim game, at Current/Last Round/
//    Game Begin. SWUSim-only; the undo-stepping runs in SWUSim/DevTools/bugreport-load-state.php. ──────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'load') {
    header('Content-Type: application/json');
    echo json_encode(BugReportViewerHandleLoad(
        $bugReportApiUrl,
        $bugReportApiKey,
        intval($_POST['id'] ?? 0),
        strval($_POST['mode'] ?? ''),
        preg_replace('/[^0-9]/', '', strval($_POST['targetGame'] ?? '')),
        __DIR__
    ));
    exit;
}

// ── Close/resolve action (POST) — marks a report resolved via the intake API (any root). ────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'resolve') {
    header('Content-Type: application/json');
    echo json_encode(BugReportViewerResolve(
        $bugReportApiUrl,
        $bugReportApiKey,
        intval($_POST['id'] ?? 0),
        substr(trim(strval($_POST['note'] ?? '')), 0, 1000)
    ));
    exit;
}

$fetch = (trim($bugReportApiUrl) !== '' && trim($bugReportApiKey) !== '')
    ? BugReportViewerFetch($bugReportApiUrl, $bugReportApiKey)
    : ['ok' => true, 'error' => '', 'reports' => []];
if ($localConn !== null) {
    LocalBugReportReconcile($localConn);   // reports saved while the database was down
    $fetch['reports'] = array_merge(LocalBugReportList($localConn), $fetch['reports']);
}
$fetch['notice'] = LocalBugReportUnindexedNotice($localConn);

header('Content-Type: text/html; charset=utf-8');
echo BugReportViewerRenderPage($fetch, $rootName, IsLocalDevelopmentRequest());

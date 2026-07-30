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

include_once './AccountFiles/AccountSessionAPI.php';
include_once './Core/HTTPLibraries.php';

$error = CheckLoggedInUserMod();
if ($error !== '') {
    echo htmlspecialchars($error);
    exit;
}

require_once './BugReportViewerLib.php';

$rootName = isset($_GET['rootName']) ? preg_replace('/[^A-Za-z0-9_]/', '', strval($_GET['rootName'])) : '';

// Config lives in APIKeys.php (out of git): $bugReportApiUrl, $bugReportApiKey.
$bugReportApiUrl = '';
$bugReportApiKey = '';
@include './APIKeys/APIKeys.php';

$fetch = BugReportViewerFetch($bugReportApiUrl, $bugReportApiKey);

header('Content-Type: text/html; charset=utf-8');
echo BugReportViewerRenderPage($fetch, $rootName);

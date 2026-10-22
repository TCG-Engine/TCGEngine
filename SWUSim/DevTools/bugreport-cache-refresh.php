<?php
// bugreport-cache-refresh.php — loopback only. Called by import-bugreport.php after it writes
// SWUSim/Games/<game>/Gamestate.txt from the CLI: Apache's APCu may still hold an older gamestate for that number
// (a local game that happened to share it), and ParseGamestate reads APCu before the file. Replaces only the cached
// gamestate payload — seat auth is preserved, as in BugReportViewerHandleLoad — and only when an entry exists.
//   GET ?game=<n>  →  {"refreshed": bool}
require_once __DIR__ . '/../../AccountFiles/AccountSessionAPI.php';
require_once __DIR__ . '/../../Core/GameAuth.php';

if (!IsStrictLoopbackRequest()) { http_response_code(404); exit; }
header('Content-Type: application/json');

$game = strval($_GET['game'] ?? '');
$path = __DIR__ . '/../Games/' . $game . '/Gamestate.txt';
if (!ctype_digit($game) || !is_file($path)) { echo json_encode(['refreshed' => false]); exit; }

$hit = false;
SimGameFetchCacheRecord('SWUSim', $game, $hit);
echo json_encode(['refreshed' => $hit && SimGameWriteGamestateCache('SWUSim', $game, (string)file_get_contents($path))]);

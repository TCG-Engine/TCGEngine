<?php
// Public, privacy-safe summary of recently completed matches. Raw Match.json files contain
// deck links, auth keys, and user IDs and must never be served directly to the lobby.
require_once __DIR__ . '/../../Core/Match/Match.php';

$rootName = isset($_GET['rootName']) ? preg_replace('/[^A-Za-z0-9_]/', '', (string)$_GET['rootName']) : '';
if ($rootName === '') $rootName = 'GrandArchiveSim';
$limit = isset($_GET['limit']) ? max(1, min(10, intval($_GET['limit']))) : 5;
$maxAge = isset($_GET['maxAgeSeconds']) ? max(3600, min(604800, intval($_GET['maxAgeSeconds']))) : 604800;
$now = time();
$matches = [];
$dir = MatchesDir($rootName);

foreach (glob($dir . '/M*', GLOB_ONLYDIR) ?: [] as $matchDir) {
    $path = $matchDir . '/Match.json';
    if (!is_file($path)) continue;
    $match = json_decode(file_get_contents($path), true);
    if (!is_array($match) || ($match['state'] ?? '') !== 'complete') continue;
    // Private matches are deliberately excluded, even if their metadata is otherwise public.
    if (!empty($match['isPrivate'])) continue;
    $updatedAt = intval($match['updatedAt'] ?? $match['createdAt'] ?? 0);
    if ($updatedAt <= 0 || ($now - $updatedAt) > $maxAge) continue;
    $matches[] = [
        'matchId' => basename($matchDir),
        'format' => strval($match['format'] ?? ''),
        'queueType' => strval($match['queueType'] ?? ''),
        'bestOf' => intval($match['bestOf'] ?? 1),
        'winner' => intval($match['winner'] ?? 0),
        'wins' => [
            '1' => intval($match['wins']['1'] ?? 0),
            '2' => intval($match['wins']['2'] ?? 0),
        ],
        'games' => count($match['games'] ?? []),
        'completedAt' => $updatedAt,
    ];
}

usort($matches, function ($a, $b) { return $b['completedAt'] <=> $a['completedAt']; });
$matches = array_slice($matches, 0, $limit);
header('Content-Type: application/json');
echo json_encode(['success' => true, 'data' => $matches]);

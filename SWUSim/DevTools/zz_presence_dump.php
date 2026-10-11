<?php
// DEV/TEST ONLY — read-only dump of the presence store for one game, so a CLI test can inspect the
// WEB server's APCu (the CLI SAPI has no APCu, so it cannot see what the poll/action path wrote).
// Refuses unless DEVENV/localhost (Core/GameAuth.php SimGameIsDevelopmentEnvironment).
header('Content-Type: application/json');
require_once __DIR__ . '/../../Core/GameAuth.php';
require_once __DIR__ . '/../../Core/GamePresence.php';
if (!SimGameIsDevelopmentEnvironment()) { http_response_code(403); echo '{}'; exit; }
$gameName = preg_replace('/[^A-Za-z0-9_]/', '', strval($_GET['gameName'] ?? ''));
echo json_encode(PresenceRead($gameName));

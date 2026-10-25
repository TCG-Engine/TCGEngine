<?php

require_once __DIR__ . '/HttpServer.php';
require_once __DIR__ . '/../../Database/ConnectionManager.php';
require_once __DIR__ . '/../Database/CardAuthoringDB.php';

// Keep legacy engine includes relative to the repository rather than the endpoint directory.
chdir(dirname(__DIR__, 2));
ini_set('display_errors', '0');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');
$conn = null;
try {
    if (!CardEditingSecureRequest($_SERVER)) {
        http_response_code(403);
        header('Content-Type: application/json');
        echo json_encode(['error' => 'HTTPS is required for remote card editing']);
        exit;
    }
    $raw = file_get_contents('php://input', false, null, 0, CardEditingHttpServer::MAX_BODY + 1);
    $origins = array_values(array_filter(array_map('trim', explode(',', getenv('CARD_EDITOR_MCP_ALLOWED_ORIGINS') ?: ''))));
    $response = CardEditingHttpServer::handle($_SERVER, $raw === false ? '' : $raw,
        function($secret) use (&$conn) {
            $conn = GetLocalMySQLConnection();
            return (new CardEditingTokens($conn))->authenticate($secret);
        },
        function($userId) use (&$conn) { return new CardAuthoringDB($conn, $userId); }, $origins);
    http_response_code($response['status']);
    foreach ($response['headers'] as $name => $value) header($name . ': ' . $value);
    if ($response['body'] !== null) echo json_encode($response['body'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
} catch (Throwable $error) {
    error_log('Card editing MCP unavailable: ' . $error->getMessage());
    http_response_code(503);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Card editing service unavailable']);
} finally {
    if ($conn) mysqli_close($conn);
}

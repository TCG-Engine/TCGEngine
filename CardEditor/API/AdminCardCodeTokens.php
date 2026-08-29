<?php

// Authenticated GUI backend for hosted Card Code token administration.
// Plaintext secrets are returned exactly once on create/rotate and are never stored.

include_once __DIR__ . '/../../AccountFiles/AccountSessionAPI.php';
include_once __DIR__ . '/../../Database/ConnectionManager.php';
include_once __DIR__ . '/../Database/CardCodeServiceDB.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');

function CardCodeAdminJson(int $status, array $payload): void
{
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

function CardCodeAdminBody(): array
{
    $raw = file_get_contents('php://input');
    $body = $raw === false || trim($raw) === '' ? [] : json_decode($raw, true);
    if (!is_array($body)) throw new InvalidArgumentException('Request body must be valid JSON');
    return $body;
}

function CardCodeAdminCheckCsrf(array $body): void
{
    CheckSession();
    $session = (string)($_SESSION['generator_admin_csrf'] ?? '');
    $request = (string)($body['csrf'] ?? '');
    if ($session === '' || $request === '' || !hash_equals($session, $request)) {
        throw new InvalidArgumentException('Invalid security token; reload Generator Workspace');
    }
}

function CardCodeAdminRateLimit(): void
{
    CheckSession();
    $now = time();
    $attempts = array_values(array_filter((array)($_SESSION['card_code_token_issues'] ?? []), fn($time) => (int)$time > $now - 3600));
    if (count($attempts) >= 10) throw new RuntimeException('Token creation limit reached; try again later');
    $attempts[] = $now;
    $_SESSION['card_code_token_issues'] = $attempts;
}

$authError = CheckLoggedInUserModStrict();
if ($authError !== '') CardCodeAdminJson(403, ['success' => false, 'error' => $authError]);

$conn = null;
try {
    CheckSession();
    $method = strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET'));
    $body = $method === 'POST' ? CardCodeAdminBody() : [];
    $action = (string)($_GET['action'] ?? $body['action'] ?? ($method === 'GET' ? 'list' : ''));
    $root = CardCodeServiceDB::NormalizeRoot((string)($_GET['root'] ?? $body['root'] ?? ''));
    $actorId = LoggedInUser() === '' ? null : (int)LoggedInUser();
    $actorName = IsUserLoggedIn() ? LoggedInUserName() : 'local-admin';
    $conn = GetLocalMySQLConnection();
    $service = new CardCodeServiceDB($conn);

    if ($action === 'list' && $method === 'GET') {
        CardCodeAdminJson(200, ['success' => true, 'root' => $root, 'tokens' => $service->listTokens($root)]);
    }
    if ($method !== 'POST') throw new InvalidArgumentException('Unknown token administration action');
    CardCodeAdminCheckCsrf($body);

    if ($action === 'create') {
        CardCodeAdminRateLimit();
        $created = $service->createToken($root, (string)($body['name'] ?? ''), (string)($body['role'] ?? 'developer'), (int)($body['expiresDays'] ?? 90), $actorId, $actorName);
        CardCodeAdminJson(201, ['success' => true, 'created' => $created]);
    }
    if ($action === 'revoke') {
        $changed = $service->revokeToken($root, (int)($body['tokenId'] ?? 0), $actorId, $actorName);
        if (!$changed) throw new InvalidArgumentException('Active token not found');
        CardCodeAdminJson(200, ['success' => true]);
    }
    if ($action === 'rotate') {
        CardCodeAdminRateLimit();
        $created = $service->rotateToken($root, (int)($body['tokenId'] ?? 0), (int)($body['expiresDays'] ?? 90), $actorId, $actorName);
        CardCodeAdminJson(201, ['success' => true, 'created' => $created]);
    }
    throw new InvalidArgumentException('Unknown token administration action');
} catch (InvalidArgumentException $error) {
    CardCodeAdminJson(400, ['success' => false, 'error' => $error->getMessage()]);
} catch (RuntimeException $error) {
    $status = str_contains($error->getMessage(), 'limit') ? 429 : 500;
    if ($status === 500) error_log('AdminCardCodeTokens error: ' . $error->getMessage());
    CardCodeAdminJson($status, ['success' => false, 'error' => $status === 500 ? 'Token administration failed' : $error->getMessage()]);
} catch (Throwable $error) {
    error_log('AdminCardCodeTokens error: ' . $error->getMessage());
    CardCodeAdminJson(500, ['success' => false, 'error' => 'Token administration failed']);
} finally {
    if ($conn) mysqli_close($conn);
}

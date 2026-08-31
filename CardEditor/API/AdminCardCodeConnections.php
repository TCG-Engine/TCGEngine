<?php

// Loopback-only developer connection setup. Secrets are written to a protected local file and are
// never returned to the browser after the save request.

include_once __DIR__ . '/../../AccountFiles/AccountSessionAPI.php';
include_once __DIR__ . '/../Database/CardAbilityRepository.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');

function CardCodeConnectionJson(int $status, array $payload): void
{
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

function CardCodeConnectionBody(): array
{
    $raw = file_get_contents('php://input');
    $body = $raw === false || trim($raw) === '' ? [] : json_decode($raw, true);
    if (!is_array($body)) throw new InvalidArgumentException('Request body must be valid JSON');
    return $body;
}

function CardCodeConnectionCheckCsrf(array $body): void
{
    CheckSession();
    $session = (string)($_SESSION['generator_admin_csrf'] ?? '');
    $request = (string)($body['csrf'] ?? '');
    if ($session === '' || $request === '' || !hash_equals($session, $request)) {
        throw new InvalidArgumentException('Invalid security token; reload Generator Workspace');
    }
}

function CardCodeTestConnection(array $connection): array
{
    $url = $connection['url'] . '?action=snapshot&root=' . rawurlencode($connection['workspace']);
    $curl = curl_init($url);
    curl_setopt_array($curl, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $connection['token'], 'Accept: application/json'],
        CURLOPT_TIMEOUT => 20,
        CURLOPT_CONNECTTIMEOUT => 8,
    ]);
    $raw = curl_exec($curl);
    $status = (int)curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
    $error = curl_error($curl);
    curl_close($curl);
    if ($raw === false) throw new RuntimeException('Could not reach the Card Code host: ' . $error);
    $payload = json_decode($raw, true);
    if (!is_array($payload)) throw new RuntimeException("Card Code host returned invalid JSON (HTTP $status)");
    if ($status < 200 || $status >= 300 || empty($payload['success'])) {
        throw new RuntimeException((string)($payload['error'] ?? "Card Code host returned HTTP $status"));
    }
    return [
        'ok' => true,
        'root' => (string)($payload['root'] ?? $connection['workspace']),
        'abilityCount' => count((array)($payload['abilities'] ?? [])),
        'revision' => (string)($payload['revision'] ?? ''),
    ];
}

function CardCodeEffectiveConnection(string $root): array
{
    $local = CardCodeLoadLocalConnections();
    $effective = CardCodeRemoteConfigForRoot($root);
    return [$effective, isset($local[$root]) ? 'local-file' : ($effective ? 'environment' : null)];
}

if (!IsStrictLoopbackRequest()) {
    CardCodeConnectionJson(403, ['success' => false, 'error' => 'Developer connection setup is available only from localhost']);
}

try {
    $method = strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET'));
    $body = $method === 'POST' ? CardCodeConnectionBody() : [];
    $action = (string)($_GET['action'] ?? $body['action'] ?? ($method === 'GET' ? 'get' : ''));
    $root = trim((string)($_GET['root'] ?? $body['root'] ?? ''));
    if (!preg_match('/^[A-Za-z0-9_-]{1,64}$/', $root)) throw new InvalidArgumentException('Invalid local app name');

    if ($method === 'GET' && $action === 'get') {
        [$connection, $source] = CardCodeEffectiveConnection($root);
        CardCodeConnectionJson(200, ['success' => true, 'connection' => CardCodeConnectionMetadata($root, $connection, $source)]);
    }
    if ($method !== 'POST') throw new InvalidArgumentException('Unknown connection action');
    CardCodeConnectionCheckCsrf($body);

    if ($action === 'save' || $action === 'test') {
        [$existing] = CardCodeEffectiveConnection($root);
        $token = trim((string)($body['token'] ?? ''));
        if ($token === '' && $existing) $token = (string)$existing['token'];
        $connection = CardCodeNormalizeConnection($root, [
            'url' => $body['url'] ?? ($existing['url'] ?? ''),
            'workspace' => $body['workspace'] ?? ($existing['workspace'] ?? $root),
            'token' => $token,
        ]);
        $tested = CardCodeTestConnection($connection);
        if ($action === 'save') {
            $connections = CardCodeLoadLocalConnections();
            $connections[$root] = $connection;
            CardCodeSaveLocalConnections($connections);
        }
        CardCodeConnectionJson(200, [
            'success' => true,
            'test' => $tested,
            'connection' => CardCodeConnectionMetadata($root, $connection, $action === 'save' ? 'local-file' : 'unsaved'),
        ]);
    }

    if ($action === 'disconnect') {
        $connections = CardCodeLoadLocalConnections();
        unset($connections[$root]);
        CardCodeSaveLocalConnections($connections);
        [$effective, $source] = CardCodeEffectiveConnection($root);
        CardCodeConnectionJson(200, ['success' => true, 'connection' => CardCodeConnectionMetadata($root, $effective, $source)]);
    }
    throw new InvalidArgumentException('Unknown connection action');
} catch (InvalidArgumentException $error) {
    CardCodeConnectionJson(400, ['success' => false, 'error' => $error->getMessage()]);
} catch (Throwable $error) {
    error_log('AdminCardCodeConnections error: ' . $error->getMessage());
    CardCodeConnectionJson(502, ['success' => false, 'error' => $error->getMessage()]);
}


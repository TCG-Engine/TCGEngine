<?php

// Hosted Card Code API. Point developer checkouts at this endpoint instead of exposing MySQL.
// All responses are JSON; all access requires a root-scoped bearer token.

include_once __DIR__ . '/../../Database/ConnectionManager.php';
include_once __DIR__ . '/../Database/CardCodeServiceDB.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');

function CardCodeServiceJson(int $status, array $payload): void
{
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

function CardCodeServiceBearerToken(): string
{
    $header = (string)($_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '');
    if (!preg_match('/^Bearer\s+(.+)$/i', trim($header), $matches)) return '';
    return trim($matches[1]);
}

function CardCodeServiceBody(): array
{
    $raw = file_get_contents('php://input');
    if ($raw === false || trim($raw) === '') return [];
    $body = json_decode($raw, true);
    if (!is_array($body)) throw new InvalidArgumentException('Request body must be valid JSON');
    return $body;
}

$conn = null;
try {
    $method = strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET'));
    $body = $method === 'POST' ? CardCodeServiceBody() : [];
    $action = (string)($_GET['action'] ?? $body['action'] ?? '');
    $root = (string)($_GET['root'] ?? $body['root'] ?? '');
    $scope = in_array($action, ['save', 'ensure-cards'], true) ? 'write'
        : ($action === 'checkpoint' ? 'checkpoint' : ($action === 'restore' ? 'restore' : 'read'));

    $conn = GetLocalMySQLConnection();
    $service = new CardCodeServiceDB($conn);
    $token = $service->authenticate(CardCodeServiceBearerToken(), $root, $scope);
    $actor = (string)$token['token_name'];

    if ($action === 'card' && $method === 'GET') {
        $cardId = (string)($_GET['card'] ?? '');
        $rows = $service->rows($root, $cardId);
        CardCodeServiceJson(200, ['success' => true, 'root' => $root, 'cardId' => $cardId, 'revision' => CardCodeServiceDB::RevisionForRows($rows), 'abilities' => $rows]);
    }
    if ($action === 'snapshot' && $method === 'GET') {
        $rows = $service->rows($root);
        CardCodeServiceJson(200, ['success' => true, 'root' => $root, 'revision' => CardCodeServiceDB::RevisionForRows($rows), 'abilities' => $rows]);
    }
    if ($action === 'cards' && $method === 'GET') {
        $rows = $service->rows($root);
        $cards = [];
        foreach ($rows as $row) {
            $cardId = (string)$row['card_id'];
            if (!isset($cards[$cardId])) $cards[$cardId] = ['cardId' => $cardId, 'isImplemented' => false];
            if (!empty($row['is_implemented'])) $cards[$cardId]['isImplemented'] = true;
        }
        ksort($cards, SORT_NATURAL | SORT_FLAG_CASE);
        CardCodeServiceJson(200, ['success' => true, 'root' => $root, 'cards' => array_values($cards)]);
    }
    if ($action === 'save' && $method === 'POST') {
        $service->checkpoint($root, $actor);
        $result = $service->replaceCard(
            $root,
            (string)($body['cardId'] ?? $body['card'] ?? ''),
            is_array($body['abilities'] ?? null) ? $body['abilities'] : [],
            !empty($body['cardImplemented']),
            isset($body['baseRevision']) ? (string)$body['baseRevision'] : null
        );
        if (!empty($result['conflict'])) CardCodeServiceJson(409, ['success' => false, 'error' => 'Card abilities changed since they were loaded', 'conflict' => $result]);
        CardCodeServiceJson(200, ['success' => true] + $result);
    }
    if ($action === 'ensure-cards' && $method === 'POST') {
        $service->checkpoint($root, $actor);
        $cardIds = is_array($body['cardIds'] ?? null) ? $body['cardIds'] : [];
        if (count($cardIds) > 10000) throw new InvalidArgumentException('Too many card IDs');
        mysqli_begin_transaction($conn);
        $inserted = 0;
        try {
            $check = mysqli_prepare($conn, 'SELECT 1 FROM card_abilities WHERE root_name = ? AND card_id = ? LIMIT 1');
            $insert = mysqli_prepare($conn, "INSERT INTO card_abilities (root_name, card_id, macro_name, ability_type, ability_code, is_implemented) VALUES (?, ?, '', 'macro', '', 0)");
            foreach (array_unique($cardIds) as $candidate) {
                $cardId = trim((string)$candidate);
                if ($cardId === '' || strlen($cardId) > 128) continue;
                mysqli_stmt_bind_param($check, 'ss', $root, $cardId);
                mysqli_stmt_execute($check);
                mysqli_stmt_store_result($check);
                if (mysqli_stmt_num_rows($check) > 0) continue;
                mysqli_stmt_bind_param($insert, 'ss', $root, $cardId);
                if (mysqli_stmt_execute($insert)) ++$inserted;
            }
            mysqli_stmt_close($check); mysqli_stmt_close($insert);
            mysqli_commit($conn);
        } catch (Throwable $error) {
            mysqli_rollback($conn);
            throw $error;
        }
        CardCodeServiceJson(200, ['success' => true, 'insertedCount' => $inserted]);
    }
    if ($action === 'checkpoints' && $method === 'GET') {
        CardCodeServiceJson(200, ['success' => true, 'root' => $root, 'checkpoints' => $service->listCheckpoints($root)]);
    }
    if ($action === 'checkpoint' && $method === 'POST') {
        CardCodeServiceJson(200, ['success' => true, 'checkpoint' => $service->checkpoint($root, $actor)]);
    }
    if ($action === 'restore' && $method === 'POST') {
        CardCodeServiceJson(200, ['success' => true] + $service->restore($root, (int)($body['checkpointId'] ?? 0), $actor));
    }
    CardCodeServiceJson(404, ['success' => false, 'error' => 'Unknown Card Code service action']);
} catch (InvalidArgumentException $error) {
    CardCodeServiceJson(400, ['success' => false, 'error' => $error->getMessage()]);
} catch (RuntimeException $error) {
    $message = $error->getMessage();
    $status = (str_contains($message, 'token') || str_contains($message, 'scope')) ? 403 : 500;
    if ($status === 500) error_log('CardCodeService error: ' . $message);
    CardCodeServiceJson($status, ['success' => false, 'error' => $status === 500 ? 'Card Code service failed' : $message]);
} catch (Throwable $error) {
    error_log('CardCodeService error: ' . $error->getMessage());
    CardCodeServiceJson(500, ['success' => false, 'error' => 'Card Code service failed']);
} finally {
    if ($conn) mysqli_close($conn);
}

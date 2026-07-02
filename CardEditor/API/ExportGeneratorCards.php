<?php

include_once(__DIR__ . '/../../Database/ConnectionManager.php');
include_once(__DIR__ . '/../Database/CardAuthoringDB.php');

header('Content-Type: application/json');

function ce_generator_error($message, $status = 500) {
    http_response_code($status);
    echo json_encode(['success' => false, 'error' => $message]);
    exit;
}

function ce_generator_require_local() {
    $remote = $_SERVER['REMOTE_ADDR'] ?? '';
    if($remote === '' && php_sapi_name() === 'cli') return;
    if(in_array($remote, ['127.0.0.1', '::1'])) return;
    ce_generator_error('This export endpoint is only available from localhost', 403);
}

function ce_generator_param($name) {
    $value = $_GET[$name] ?? '';
    $value = trim((string)$value);
    if($value === '') ce_generator_error("Missing $name parameter", 400);
    return $value;
}

function ce_generator_prepare($conn, $sql, $types, $values) {
    $stmt = mysqli_prepare($conn, $sql);
    if(!$stmt) throw new Exception(mysqli_error($conn));
    mysqli_stmt_bind_param($stmt, $types, ...$values);
    if(!mysqli_stmt_execute($stmt)) throw new Exception(mysqli_stmt_error($stmt));
    return mysqli_stmt_get_result($stmt);
}

function ce_generator_value($row) {
    if($row['field_type'] === 'number') {
        return $row['value_number'] === null ? null : (float)$row['value_number'];
    }
    if($row['field_type'] === 'boolean') {
        return $row['value_boolean'] === null ? null : (bool)$row['value_boolean'];
    }
    if($row['field_type'] === 'multiselect') {
        if($row['value_json'] === null || $row['value_json'] === '') return [];
        $decoded = json_decode($row['value_json'], true);
        return is_array($decoded) ? array_values($decoded) : [];
    }
    return $row['value_text'];
}

try {
    ce_generator_require_local();
    if(($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') ce_generator_error('Method not allowed', 405);

    $gameSlug = ce_generator_param('gameSlug');
    $setSlug = ce_generator_param('setSlug');
    $templateSlug = trim((string)($_GET['templateSlug'] ?? ''));

    $conn = GetLocalMySQLConnection();
    new CardAuthoringDB($conn);

    $sql = "
        SELECT
            c.id AS card_id,
            c.name AS card_name,
            f.field_key,
            f.field_type,
            v.value_text,
            v.value_number,
            v.value_boolean,
            v.value_json
        FROM ce_cards c
        INNER JOIN ce_games g ON g.id = c.game_id
        INNER JOIN ce_sets s ON s.id = c.set_id
        INNER JOIN ce_templates t ON t.id = c.template_id
        INNER JOIN ce_template_fields f ON f.template_id = c.template_id
        LEFT JOIN ce_card_field_values v ON v.card_id = c.id AND v.field_id = f.id
        WHERE g.slug = ? AND s.slug = ?
    ";
    $types = "ss";
    $params = [$gameSlug, $setSlug];
    if($templateSlug !== '') {
        $sql .= " AND t.slug = ?";
        $types .= "s";
        $params[] = $templateSlug;
    }
    $sql .= " ORDER BY c.name ASC, f.sort_order ASC, f.id ASC";

    $result = ce_generator_prepare($conn, $sql, $types, $params);
    $cardsById = [];
    while($row = mysqli_fetch_assoc($result)) {
        $cardId = (int)$row['card_id'];
        if(!isset($cardsById[$cardId])) {
            $cardsById[$cardId] = [
                'name' => $row['card_name']
            ];
        }
        $value = ce_generator_value($row);
        if($value !== null && $value !== '') {
            $cardsById[$cardId][$row['field_key']] = $value;
        }
    }

    $cards = array_values($cardsById);
    foreach($cards as &$card) {
        if((!isset($card['name']) || $card['name'] === '') && isset($card['title'])) {
            $card['name'] = $card['title'];
        }
    }

    echo json_encode(['success' => true, 'data' => $cards]);
    mysqli_close($conn);
} catch(Exception $e) {
    ce_generator_error($e->getMessage(), 500);
}

?>

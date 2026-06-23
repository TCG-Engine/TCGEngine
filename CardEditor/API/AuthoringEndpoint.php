<?php

include_once('../../Database/ConnectionManager.php');
include_once('../Database/CardAuthoringDB.php');

function ce_json_headers() {
    header('Content-Type: application/json');
}

function ce_input_json() {
    $raw = file_get_contents('php://input');
    if ($raw === false || trim($raw) === '') return [];
    $input = json_decode($raw, true);
    if (!is_array($input)) {
        throw new Exception("Invalid JSON payload");
    }
    return $input;
}

function ce_require_method($method) {
    if ($_SERVER['REQUEST_METHOD'] !== $method) {
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Method not allowed']);
        exit;
    }
}

function ce_db() {
    return new CardAuthoringDB(GetLocalMySQLConnection());
}

function ce_success($data) {
    echo json_encode(['success' => true, 'data' => $data]);
}

function ce_error($message, $status = 500) {
    http_response_code($status);
    echo json_encode(['success' => false, 'error' => $message]);
}

function ce_int_param($name, $source = null) {
    $source = $source ?? $_GET;
    $value = $source[$name] ?? null;
    if ($value === null || $value === '') {
        throw new Exception("Missing $name parameter");
    }
    return (int)$value;
}

function ce_run($method, $handler) {
    ce_json_headers();
    try {
        ce_require_method($method);
        $conn = GetLocalMySQLConnection();
        $db = new CardAuthoringDB($conn);
        $result = $handler($db);
        ce_success($result);
        mysqli_close($conn);
    } catch (Exception $e) {
        $message = $e->getMessage();
        $status = 500;
        if (stripos($message, 'logged in') !== false || stripos($message, 'permission') !== false || stripos($message, 'access') !== false) {
            $status = 403;
        } elseif (stripos($message, 'changed elsewhere') !== false) {
            $status = 409;
        }
        ce_error($message, $status);
    }
}

?>

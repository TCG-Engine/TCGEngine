<?php
// Narrow POST bridge: no credentials in URLs and no client-selected engine mode.
chdir(dirname(__DIR__));
header('Content-Type: application/json');
header('Cache-Control: private, no-store');
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    echo json_encode(['success' => false, 'message' => 'POST required.']);
    exit;
}
foreach (['gameName', 'playerID', 'authKey', 'expectedRevision', 'command'] as $field) {
    if (!isset($_POST[$field]) || !is_string($_POST[$field])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid request.']);
        exit;
    }
}
$_GET = array_intersect_key($_POST, array_flip(['gameName', 'playerID', 'authKey', 'expectedRevision']));
$_GET += ['folderPath' => 'GrandArchiveSim', 'mode' => '100', 'cardID' => 'PASS',
    'responseFormat' => 'json', 'uiCommand' => $_POST['command']];
if (isset($_POST['answer']) && is_string($_POST['answer'])) $_GET['uiAnswer'] = $_POST['answer'];
require 'ProcessInput.php';

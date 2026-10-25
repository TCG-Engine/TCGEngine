<?php
// Run on the machine holding the source MySQL database. Never reads a remote connection.
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
$rootName = 'GrandArchiveSim';
require_once __DIR__ . '/../../Database/ConnectionManager.php';
require_once __DIR__ . '/../../CardEditor/Database/CardCodeServiceDB.php';
$conn = GetLocalMySQLConnection();
try {
    $service = new CardCodeServiceDB($conn);
    $rows = $service->rows($rootName);
    echo json_encode(['root' => $rootName, 'revision' => CardCodeServiceDB::RevisionForRows($rows), 'abilities' => $rows], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
} finally { mysqli_close($conn); }

<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../AccountFiles/AccountSessionAPI.php';
$modErr = CheckLoggedInUserMod();
if ($modErr !== '') { http_response_code(403); echo json_encode(['success'=>false,'error'=>$modErr]); exit; }
require_once __DIR__ . '/../../Database/ConnectionManager.php';
require_once __DIR__ . '/../../Database/functions.inc.php';

$slot = $_POST['slot'] ?? '';
$id   = $_POST['id'] ?? '';
if ($slot === '' || $id === '') { echo json_encode(['success'=>false,'error'=>'missing']); exit; }

$asset = DeleteCosmeticUpload($slot, $id);   // null => not an uploaded row (built-ins protected)
if ($asset === null) { echo json_encode(['success'=>false,'error'=>'not_found_or_builtin']); exit; }

// Unlink the file(s), constrained to the repo root (no traversal).
$repoRoot = realpath(__DIR__ . '/../..');
$rel = ltrim((string)$asset, '.');                 // ./Assets/... -> /Assets/...
$paths = [$repoRoot . $rel];
if ($slot === 'background') $paths[] = $repoRoot . preg_replace('/\.webp$/', '-mobile.webp', $rel);
foreach ($paths as $p) {
    $real = realpath($p);
    if ($real && strpos($real, $repoRoot) === 0 && is_file($real)) @unlink($real);
}
echo json_encode(['success'=>true]);

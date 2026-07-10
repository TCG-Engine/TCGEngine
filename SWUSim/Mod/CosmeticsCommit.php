<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../AccountFiles/AccountSessionAPI.php';
require_once __DIR__ . '/DevGate.php';
if (!SWUIsLocalDevRequest()) { http_response_code(403); echo json_encode(['success'=>false,'error'=>'dev_only']); exit; }
$modErr = CheckLoggedInUserMod();
if ($modErr !== '') { http_response_code(403); echo json_encode(['success'=>false,'error'=>$modErr]); exit; }
require_once __DIR__ . '/../Cosmetics/CosmeticAssets.php';
require_once __DIR__ . '/../Cosmetics/CatalogWriter.php';

$fail = function($e){ echo json_encode(['success'=>false,'error'=>$e]); exit; };

$action = $_POST['action'] ?? '';
$slot   = $_POST['slot'] ?? '';
$id     = $_POST['id'] ?? '';
$label  = trim($_POST['label'] ?? '');

$rel = SWUCosmeticAssetRel($slot, $id);   // null for unknown slot / empty id
if ($rel === null) $fail('bad_slot_or_id');

if ($action === 'save') {
    if ($label === '') $fail('label_required');
    // The processed webp must already exist (written by CosmeticsUpload.php).
    $repoRoot = realpath(__DIR__ . '/../..');
    if (!is_file($repoRoot . substr($rel, 1))) $fail('asset_missing');
    if (!SWUCatalogAppendEntry($slot, $id, $label, $rel)) $fail('catalog_write_failed');
    echo json_encode(['success'=>true]);
    exit;
}

if ($action === 'discard') {
    SWUCosmeticDeleteAsset($slot, $id);
    echo json_encode(['success'=>true]);
    exit;
}

$fail('bad_action');

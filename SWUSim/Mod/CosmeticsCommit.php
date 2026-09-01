<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../AccountFiles/AccountSessionAPI.php';
require_once __DIR__ . '/DevGate.php';
if (!SWUIsLocalDevRequest()) { http_response_code(403); echo json_encode(['success'=>false,'error'=>'dev_only']); exit; }
$modErr = CheckLoggedInUserMod();
if ($modErr !== '') { http_response_code(403); echo json_encode(['success'=>false,'error'=>$modErr]); exit; }
require_once __DIR__ . '/../Cosmetics/CosmeticAssets.php';
require_once __DIR__ . '/../Cosmetics/CatalogWriter.php';
require_once __DIR__ . '/../Cosmetics/Catalog.php';   // SWUCosmeticCatalog — the isDefault guard below

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

// ── Editing an existing cosmetic ──────────────────────────────────────────────────────────────────
// Only the LABEL and the ART are editable. The id and the asset path are deliberately frozen: they are
// what every saved `usercosmetic` row points at, so changing either would silently reset every player
// who had chosen this cosmetic back to the slot default.
if ($action === 'rename') {
    if ($label === '') $fail('label_required');
    if (!SWUCatalogHasEntry($slot, $id)) $fail('not_found');
    if (!SWUCatalogUpdateEntryLabel($slot, $id, $label)) $fail('catalog_write_failed');
    echo json_encode(['success'=>true]);
    exit;
}

// Confirm a staged art replacement: move the staged webp(s) over the live asset, and apply the label
// too if it was edited in the same panel.
if ($action === 'replace') {
    if (!SWUCatalogHasEntry($slot, $id)) $fail('not_found');
    if (!SWUCosmeticCommitStaged($slot, $id)) $fail('nothing_staged');
    if ($label !== '' && !SWUCatalogUpdateEntryLabel($slot, $id, $label)) $fail('catalog_write_failed');
    echo json_encode(['success'=>true]);
    exit;
}

// Cancel a staged art replacement — the live asset is untouched, so only the staged files go.
if ($action === 'discard_staged') {
    SWUCosmeticDiscardStaged($slot, $id);
    echo json_encode(['success'=>true]);
    exit;
}

if ($action === 'delete') {
    if (!SWUCatalogHasEntry($slot, $id)) $fail('not_found');
    // ⚠ NEVER DELETE A SLOT'S DEFAULT. SWUCosmeticResolve falls back to the default whenever a saved
    // choice is missing, so the default is what makes deleting anything ELSE safe. Remove it and every
    // unresolved selection in that slot resolves to nothing.
    $opt = SWUCosmeticCatalog()[$slot][$id] ?? null;
    if ($opt === null) $fail('not_found');
    if (!empty($opt['isDefault'])) $fail('cannot_delete_default');
    if (!SWUCatalogDeleteEntry($slot, $id)) $fail('catalog_write_failed');
    SWUCosmeticDeleteAsset($slot, $id);     // live art + the -mobile variant for backgrounds
    SWUCosmeticDiscardStaged($slot, $id);   // and anything left staged from an abandoned replace
    echo json_encode(['success'=>true]);
    exit;
}

$fail('bad_action');

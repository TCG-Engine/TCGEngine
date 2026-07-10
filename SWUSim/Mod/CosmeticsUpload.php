<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../AccountFiles/AccountSessionAPI.php';
require_once __DIR__ . '/DevGate.php';
if (!SWUIsLocalDevRequest()) { http_response_code(403); echo json_encode(['success'=>false,'error'=>'dev_only']); exit; }
$modErr = CheckLoggedInUserMod();
if ($modErr !== '') { http_response_code(403); echo json_encode(['success'=>false,'error'=>$modErr]); exit; }
require_once __DIR__ . '/../Cosmetics/Catalog.php';
require_once __DIR__ . '/../Cosmetics/CosmeticAssets.php';
require_once __DIR__ . '/CosmeticsImage.php';

$fail = function($e){ echo json_encode(['success'=>false,'error'=>$e]); exit; };

$slot  = $_POST['slot'] ?? '';
$label = trim($_POST['label'] ?? '');
$specs = ['background'=>[1920,1080], 'cardback'=>[512,512], 'playmat'=>[2560,1080]];   // playmat = 21:9
if (!isset($specs[$slot])) $fail('bad_slot');
if ($label === '') $fail('label_required');
// A POST exceeding post_max_size arrives with $_FILES (and $_POST) empty.
if (empty($_FILES['image'])) {
    $fail((int)($_SERVER['CONTENT_LENGTH'] ?? 0) > 0 ? 'upload_too_large_for_post_max' : 'upload_error');
}
if ($_FILES['image']['error'] !== UPLOAD_ERR_OK) {
    $codes = [
        UPLOAD_ERR_INI_SIZE   => 'upload_exceeds_upload_max_filesize',
        UPLOAD_ERR_FORM_SIZE  => 'upload_exceeds_form_max',
        UPLOAD_ERR_PARTIAL    => 'upload_partial',
        UPLOAD_ERR_NO_FILE    => 'upload_no_file',
        UPLOAD_ERR_NO_TMP_DIR => 'upload_no_tmp_dir',
        UPLOAD_ERR_CANT_WRITE => 'upload_cant_write',
        UPLOAD_ERR_EXTENSION  => 'upload_blocked_by_extension',
    ];
    $fail($codes[$_FILES['image']['error']] ?? 'upload_error');
}
if ($_FILES['image']['size'] > 10*1024*1024) $fail('too_large');
$tmp = $_FILES['image']['tmp_name'];
if (@getimagesize($tmp) === false) $fail('not_an_image');

// slugify label -> base id; ensure unique within slot (vs built-ins + uploads)
$base = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $label));
$base = trim($base, '-'); if ($base === '') $base = 'cosmetic';
$existing = SWUCosmeticCatalog()[$slot] ?? [];
$id = $base; $n = 2;
while (isset($existing[$id])) { $id = $base . '-' . $n; $n++; }

$repoRoot = realpath(__DIR__ . '/../..');   // /var/www/html/TCGEngine
$dir  = SWUCosmeticSlotDir($slot);          // slot validated above, non-null
$abs  = $repoRoot . $dir . $id . '.webp';
[$w,$h] = $specs[$slot];
$vAnchor = $slot === 'playmat' ? 'top' : 'center';   // playmats crop from the top; others stay centered
if (!SWUCosmeticProcessImage($tmp, $abs, $w, $h, $vAnchor)) $fail('process_failed');
if ($slot === 'background') {   // mobile portrait variant
    if (!SWUCosmeticProcessImage($tmp, $repoRoot . $dir . $id . '-mobile.webp', 1080, 1920)) $fail('process_failed_mobile');
}

// Processed webp is on disk; the catalog entry is deferred to CosmeticsCommit.php (save/discard).
$asset = SWUCosmeticAssetRel($slot, $id);   // -> ./Assets/...
echo json_encode(['success'=>true, 'slot'=>$slot, 'id'=>$id, 'label'=>$label, 'asset'=>$asset]);

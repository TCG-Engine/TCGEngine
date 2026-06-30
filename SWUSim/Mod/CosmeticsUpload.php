<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../AccountFiles/AccountSessionAPI.php';
$modErr = CheckLoggedInUserMod();
if ($modErr !== '') { http_response_code(403); echo json_encode(['success'=>false,'error'=>$modErr]); exit; }
require_once __DIR__ . '/../../Database/ConnectionManager.php';
require_once __DIR__ . '/../../Database/functions.inc.php';
require_once __DIR__ . '/../Cosmetics/Catalog.php';
require_once __DIR__ . '/CosmeticsImage.php';

$fail = function($e){ echo json_encode(['success'=>false,'error'=>$e]); exit; };

$slot  = $_POST['slot'] ?? '';
$label = trim($_POST['label'] ?? '');
$specs = ['background'=>[1920,1080], 'cardback'=>[512,512], 'playmat'=>[2560,1080]];   // playmat = 21:9
if (!isset($specs[$slot])) $fail('bad_slot');
if ($label === '') $fail('label_required');
if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) $fail('upload_error');
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
$destDirs = [
    'background' => '/Assets/Boards/SWUSim/',
    'cardback'   => '/Assets/CardBacks/SWUSim/',
    'playmat'    => '/Assets/Playmats/SWUSim/',
];
$rel  = $destDirs[$slot] . $id . '.webp';
$abs  = $repoRoot . $rel;
[$w,$h] = $specs[$slot];
if (!SWUCosmeticProcessImage($tmp, $abs, $w, $h)) $fail('process_failed');
if ($slot === 'background') {   // mobile portrait variant
    if (!SWUCosmeticProcessImage($tmp, $repoRoot . $destDirs[$slot] . $id . '-mobile.webp', 1080, 1920)) $fail('process_failed_mobile');
}

$asset = '.' . $rel;   // -> ./Assets/...
$uid = isset($_SESSION['userid']) ? (int)$_SESSION['userid'] : null;
if (!AddCosmeticUpload($slot, $id, $label, $asset, $uid)) $fail('db_failed');
echo json_encode(['success'=>true, 'slot'=>$slot, 'id'=>$id, 'label'=>$label, 'asset'=>$asset]);

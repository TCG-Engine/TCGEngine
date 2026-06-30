<?php
// Cosmetics endpoint (SWUSim-local). Serves the Profile chooser. Actions: get / set.
$__test = !empty($GLOBALS['__COSMETICS_TEST']);
if (!$__test) ob_start();
require_once __DIR__ . '/../AccountFiles/AccountSessionAPI.php';
require_once __DIR__ . '/../Database/ConnectionManager.php';
require_once __DIR__ . '/../Database/functions.inc.php';
require_once __DIR__ . '/Cosmetics/Catalog.php';

$respond = function($arr) use ($__test) {
    if ($__test) return $arr;
    while (ob_get_level() > 0) { ob_end_clean(); }
    header('Content-Type: application/json'); echo json_encode($arr); exit;
};

CheckSession();
$uid = isset($_SESSION['userid']) ? (int)$_SESSION['userid'] : 0;
if ($uid === 0) return $respond(['success'=>false,'error'=>'not_logged_in']);
$action = $_POST['action'] ?? $_GET['action'] ?? '';

if ($action === 'get') {
    return $respond(['success'=>true, 'cosmetics'=>LoadUserCosmetics($uid)]);
}
if ($action === 'set') {
    $slot = $_POST['slot'] ?? '';
    $choiceId = $_POST['choiceId'] ?? '';
    if (!SetUserCosmetic($uid, $slot, $choiceId)) return $respond(['success'=>false,'error'=>'invalid_choice']);
    return $respond(['success'=>true, 'slot'=>$slot, 'choice'=>SWUCosmeticResolve($slot, $choiceId)]);
}
return $respond(['success'=>false,'error'=>'unknown_action']);

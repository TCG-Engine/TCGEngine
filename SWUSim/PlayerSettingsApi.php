<?php
// SWUSim player-settings endpoint (SWUSim-local). Serves the Profile toggle AND the in-game gear
// menu, so both surfaces write through one path. Actions: get / set.
// Mirrors Cosmetics.php's shape (ob_start + JSON respond + CheckSession) so the two behave alike.
$__test = !empty($GLOBALS['__PLAYERSETTINGS_TEST']);
if (!$__test) ob_start();
require_once __DIR__ . '/../AccountFiles/AccountSessionAPI.php';
require_once __DIR__ . '/PlayerSettings.php';

$respond = function ($arr) use ($__test) {
    if ($__test) return $arr;
    while (ob_get_level() > 0) { ob_end_clean(); }
    header('Content-Type: application/json'); echo json_encode($arr); exit;
};

CheckSession();
$uid = isset($_SESSION['userid']) ? (int)$_SESSION['userid'] : 0;
// A guest is NOT an error here: muting still works for them, it just lives in their browser only.
// Answering with mute=null lets the client fall back to its local value without special-casing.
if ($uid === 0) return $respond(['success' => true, 'loggedIn' => false, 'mute' => null]);

$action = $_POST['action'] ?? $_GET['action'] ?? '';

if ($action === 'get') {
    $m = SWUSimAccountMuted($uid);
    return $respond(['success' => true, 'loggedIn' => true, 'mute' => $m === null ? null : ($m ? 1 : 0)]);
}

if ($action === 'set') {
    if (!array_key_exists('mute', $_POST)) return $respond(['success' => false, 'error' => 'missing_mute']);
    $val = intval($_POST['mute']) ? 1 : 0;
    if (!SWUSimSetSetting($uid, SWUSIM_SET_MUTE, $val)) return $respond(['success' => false, 'error' => 'save_failed']);
    return $respond(['success' => true, 'loggedIn' => true, 'mute' => $val]);
}

// "Carry a browser choice onto the account at login" (product decision 2026-08-20). Only writes when
// the account has NO stored value: an account that has already expressed a preference must not be
// overwritten by whatever a shared/guest browser happened to have set. First login after muting as a
// guest promotes; every later login is a no-op.
if ($action === 'promote') {
    if (!array_key_exists('mute', $_POST)) return $respond(['success' => false, 'error' => 'missing_mute']);
    if (SWUSimGetSetting($uid, SWUSIM_SET_MUTE) !== null) {
        return $respond(['success' => true, 'loggedIn' => true, 'promoted' => false,
                         'mute' => SWUSimAccountMuted($uid) ? 1 : 0]);
    }
    $val = intval($_POST['mute']) ? 1 : 0;
    SWUSimSetSetting($uid, SWUSIM_SET_MUTE, $val);
    return $respond(['success' => true, 'loggedIn' => true, 'promoted' => true, 'mute' => $val]);
}

return $respond(['success' => false, 'error' => 'unknown_action']);

<?php
// SWUSim player-settings endpoint (SWUSim-local). Serves the Profile toggle AND the in-game gear
// menu, so both surfaces write through one path. Actions: get / set / promote (mute), setCardLanguage / promoteCardLanguage (card language),
// setHideHealthBars / promoteHideHealthBars (turn off health bars).
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
if ($uid === 0) return $respond(['success' => true, 'loggedIn' => false, 'mute' => null, 'cardLanguage' => null, 'hideHealthBars' => null]);

$action = $_POST['action'] ?? $_GET['action'] ?? '';

if ($action === 'get') {
    $m = SWUSimAccountMuted($uid);
    return $respond(['success' => true, 'loggedIn' => true, 'mute' => $m === null ? null : ($m ? 1 : 0),
                     'cardLanguage' => SWUSimAccountCardLanguage($uid),
                     'hideHealthBars' => ($h = SWUSimAccountHideHealthBars($uid)) === null ? null : ($h ? 1 : 0)]);
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

// Turn off health bars. Same two-layer shape as mute: set writes the account; promote carries a browser choice
// onto an account that has NEVER set one, and is a no-op otherwise.
if ($action === 'setHideHealthBars' || $action === 'promoteHideHealthBars') {
    if (!array_key_exists('hide', $_POST)) return $respond(['success' => false, 'error' => 'missing_hide']);
    $val = intval($_POST['hide']) ? 1 : 0;
    if ($action === 'promoteHideHealthBars' && SWUSimGetSetting($uid, SWUSIM_SET_HIDE_HEALTH_BARS) !== null) {
        return $respond(['success' => true, 'loggedIn' => true, 'promoted' => false,
                         'hideHealthBars' => SWUSimAccountHideHealthBars($uid) ? 1 : 0]);
    }
    if (!SWUSimSetSetting($uid, SWUSIM_SET_HIDE_HEALTH_BARS, $val)) return $respond(['success' => false, 'error' => 'save_failed']);
    $out = ['success' => true, 'loggedIn' => true, 'hideHealthBars' => $val];
    if ($action === 'promoteHideHealthBars') $out['promoted'] = true;
    return $respond($out);
}

// Card language (localized card art). Same two-layer shape as mute: the browser choice wins locally, the
// account value follows the player to other devices, and a browser choice is promoted only onto an account
// that has never set one.
if ($action === 'setCardLanguage') {
    $lang = $_POST['lang'] ?? null;
    if (SWUSimCardLanguageToCode($lang) === null) return $respond(['success' => false, 'error' => 'invalid_lang']);
    if (!SWUSimSetCardLanguage($uid, $lang)) return $respond(['success' => false, 'error' => 'save_failed']);
    return $respond(['success' => true, 'loggedIn' => true, 'cardLanguage' => strtolower(trim($lang))]);
}

if ($action === 'promoteCardLanguage') {
    $lang = $_POST['lang'] ?? null;
    if (SWUSimCardLanguageToCode($lang) === null) return $respond(['success' => false, 'error' => 'invalid_lang']);
    $current = SWUSimAccountCardLanguage($uid);
    if ($current !== null) {
        return $respond(['success' => true, 'loggedIn' => true, 'promoted' => false, 'cardLanguage' => $current]);
    }
    SWUSimSetCardLanguage($uid, $lang);
    return $respond(['success' => true, 'loggedIn' => true, 'promoted' => true, 'cardLanguage' => strtolower(trim($lang))]);
}

return $respond(['success' => false, 'error' => 'unknown_action']);

<?php
// SWUSim per-ACCOUNT player settings.
//
// Stored in the `savedsettings` table (playerId, settingNumber, settingValue) through
// Database/functions.inc.php's SaveSetting() / LoadSavedSettings(). That table AND its read/write
// pair already existed in this repo with ZERO callers — inherited plumbing that was never wired up.
// This file is its first consumer; nothing here needed a migration.
//
// ⚠ SETTING NUMBERS ARE PERMANENT AND SWUSim-LOCAL.
//   • Permanent: the number IS the stored key, so renumbering silently re-points every saved row.
//     Only ever APPEND to the registry below; never reuse or reorder.
//   • SWUSim-local: savedsettings has no rootName column, but SiteRegistry gives SWUSim its own
//     database ('swusim'), so these numbers cannot collide with SWUDeck's or any other root's. That
//     is also why we do NOT mirror SWUOnline's numbering (its $SET_Mute is 11) — a shared number
//     would imply a shared namespace that does not exist here.
//
//   REGISTRY
//     1 = Mute sounds (0/1)
//     -- next free: 2 --
if (!defined('SWUSIM_SET_MUTE')) define('SWUSIM_SET_MUTE', 1);

require_once __DIR__ . '/../Database/ConnectionManager.php';
require_once __DIR__ . '/../Database/functions.inc.php';

// settingNumber => settingValue for one account. LoadSavedSettings returns a FLAT
// [num, val, num, val, …] list, so pair it up here rather than at every call site.
if (!function_exists('SWUSimLoadPlayerSettings')) {
    function SWUSimLoadPlayerSettings($userId): array {
        if ($userId === null || $userId === '' || intval($userId) <= 0) return [];
        $flat = LoadSavedSettings($userId);
        $out  = [];
        for ($i = 0; $i + 1 < count($flat); $i += 2) $out[intval($flat[$i])] = (string)$flat[$i + 1];
        return $out;
    }
}

// null = this account has NO stored value for the setting. Deliberately distinct from an explicit
// "0": "never chosen" is what lets a browser-side choice be promoted onto the account at login,
// while an explicit 0 is a real preference that must not be overwritten.
if (!function_exists('SWUSimGetSetting')) {
    function SWUSimGetSetting($userId, int $num): ?string {
        $s = SWUSimLoadPlayerSettings($userId);
        return array_key_exists($num, $s) ? $s[$num] : null;
    }
}

if (!function_exists('SWUSimSetSetting')) {
    function SWUSimSetSetting($userId, int $num, $value): bool {
        if ($userId === null || $userId === '' || intval($userId) <= 0) return false;
        SaveSetting($userId, $num, intval($value) ? '1' : '0');
        return true;
    }
}

// null = no account-level answer (guest, or a logged-in account that has never set it). The caller
// then falls back to the per-browser value — see swuSoundsMuted() on the client.
if (!function_exists('SWUSimAccountMuted')) {
    function SWUSimAccountMuted($userId): ?bool {
        $v = SWUSimGetSetting($userId, SWUSIM_SET_MUTE);
        return $v === null ? null : ($v === '1');
    }
}

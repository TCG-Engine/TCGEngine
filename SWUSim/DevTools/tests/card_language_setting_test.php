<?php
// Card language (localized card art) — the account half of the setting.
// savedsettings.settingValue is an INT column, so a language is stored as a code: 0=en 1=es 2=it 3=fr.
// The API change is additive: every existing action and field is kept (the gear menu and Profile depend on them).
// Design: docs/superpowers/specs/2026-09-17-swusim-card-i18n-images-design.md §2
function check($cond, $msg) { if (!$cond) { fwrite(STDERR, "FAIL: $msg\n"); exit(1); } echo "  ok: $msg\n"; }

$root = __DIR__ . '/../../..';
require_once $root . '/SWUSim/PlayerSettings.php';

check(defined('SWUSIM_SET_MUTE') && SWUSIM_SET_MUTE === 1, 'Mute keeps setting number 1');
check(defined('SWUSIM_SET_CARD_LANGUAGE') && SWUSIM_SET_CARD_LANGUAGE === 2, 'card language is setting number 2');
check(SWUSimCardLanguageCodes() === ['en' => 0, 'es' => 1, 'it' => 2, 'fr' => 3], 'the code table is fixed');

foreach (['en' => 0, 'es' => 1, 'it' => 2, 'fr' => 3] as $lang => $code) {
    check(SWUSimCardLanguageToCode($lang) === $code, "$lang -> $code");
    check(SWUSimCardLanguageFromCode($code) === $lang, "$code -> $lang");
    check(SWUSimCardLanguageFromCode((string)$code) === $lang, "'$code' (string, as the DB returns it) -> $lang");
}
check(SWUSimCardLanguageToCode('ES') === 1, 'case-insensitive input');
foreach (['de', '', 'english', '1', null, '../es'] as $bad) {
    check(SWUSimCardLanguageToCode($bad) === null, 'rejects ' . var_export($bad, true));
}
foreach ([null, '', '4', '-1', 'es', '1.5'] as $bad) {
    check(SWUSimCardLanguageFromCode($bad) === null, 'unreadable stored value ' . var_export($bad, true) . ' -> null');
}
check(SWUSimSetCardLanguage(0, 'es') === false, 'a guest cannot save');
check(SWUSimSetCardLanguage(5, 'de') === false, 'an unknown language is refused before touching the DB');
check(SWUSimAccountCardLanguage(0) === null, 'a guest has no account language');

// API: additive only.
$api = file_get_contents($root . '/SWUSim/PlayerSettingsApi.php');
$code = preg_replace('~//[^\n]*~', '', $api);
foreach (["'get'", "'set'", "'promote'", "'setCardLanguage'", "'promoteCardLanguage'"] as $action) {
    check(strpos($code, "\$action === $action") !== false, "API handles $action");
}
check(strpos($code, "'mute' => null") !== false, 'guest response still carries mute => null');
check(preg_match("~'loggedIn' => false, 'mute' => null, 'cardLanguage' => null~", $code) === 1, 'guest response adds cardLanguage => null');
check(strpos($code, "SWUSimSetSetting(\$uid, SWUSIM_SET_MUTE, \$val)") !== false, 'mute set path unchanged');
echo "ALL PASS\n";

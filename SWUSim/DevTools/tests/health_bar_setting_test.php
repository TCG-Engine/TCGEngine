<?php
// "Turn off health bars" (owner request 2026-10-08) — the ACCOUNT half of the setting, and the API actions.
// Same two layers as Mute sounds: the browser choice wins locally, the account value follows the player, and a
// browser choice is promoted only onto an account that has never set one. The API change is ADDITIVE.
function check($cond, $msg) { if (!$cond) { fwrite(STDERR, "FAIL: $msg\n"); exit(1); } echo "  ok: $msg\n"; }

$root = __DIR__ . '/../../..';
require_once $root . '/SWUSim/PlayerSettings.php';

check(SWUSIM_SET_MUTE === 1 && SWUSIM_SET_CARD_LANGUAGE === 2, 'earlier setting numbers are unchanged');
check(defined('SWUSIM_SET_HIDE_HEALTH_BARS') && SWUSIM_SET_HIDE_HEALTH_BARS === 3, 'hide health bars is setting number 3');
check(SWUSimAccountHideHealthBars(0) === null, 'a guest has no account value');

// The API, in-process, as a throwaway account id nobody owns. Its rows are removed before and after, so no
// real account is touched (there is no delete helper in Database/functions.inc.php; this is test-only SQL).
$TEST_UID = 987654321;
$wipe = function () use ($TEST_UID) {
    $c = GetLocalMySQLConnection();
    $s = $c->prepare('DELETE FROM savedsettings WHERE playerId = ?');
    $s->bind_param('i', $TEST_UID); $s->execute(); $s->close(); $c->close();
};
$api = function (array $post, int $uid) use ($root) {
    $GLOBALS['__PLAYERSETTINGS_TEST'] = true;
    $_SESSION['userid'] = $uid; $_POST = $post; $_GET = [];
    return include $root . '/SWUSim/PlayerSettingsApi.php';
};

$wipe();
try {
    $r = $api(['action' => 'get'], $TEST_UID);
    check($r['success'] && array_key_exists('hideHealthBars', $r) && $r['hideHealthBars'] === null, 'get: never set -> hideHealthBars null');
    $r = $api(['action' => 'promoteHideHealthBars', 'hide' => '1'], $TEST_UID);
    check($r['success'] && $r['promoted'] === true && $r['hideHealthBars'] === 1, 'promote onto an account that never set it');
    $r = $api(['action' => 'promoteHideHealthBars', 'hide' => '0'], $TEST_UID);
    check($r['success'] && $r['promoted'] === false && $r['hideHealthBars'] === 1, 'promote never overwrites an account value');
    $r = $api(['action' => 'setHideHealthBars', 'hide' => '0'], $TEST_UID);
    check($r['success'] && $r['hideHealthBars'] === 0, 'set to off');
    check(SWUSimAccountHideHealthBars($TEST_UID) === false, 'stored as an explicit off (not "never set")');
    $r = $api(['action' => 'setHideHealthBars'], $TEST_UID);
    check(!$r['success'] && ($r['error'] ?? '') === 'missing_hide', 'set without a value is refused');
    $r = $api(['action' => 'get'], $TEST_UID);
    check($r['hideHealthBars'] === 0 && array_key_exists('mute', $r) && array_key_exists('cardLanguage', $r),
          'get keeps mute + cardLanguage and adds hideHealthBars');
    check(SWUSimAccountMuted($TEST_UID) === null, 'the new actions never touch mute');
    $r = $api(['action' => 'get'], 0);
    check($r['success'] && $r['loggedIn'] === false && array_key_exists('hideHealthBars', $r) && $r['hideHealthBars'] === null,
          'guest: hideHealthBars => null, like mute');
} finally {
    $wipe();
}
echo "ALL PASS\n";

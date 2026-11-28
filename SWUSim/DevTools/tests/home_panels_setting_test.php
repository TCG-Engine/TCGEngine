<?php
// "Home Panels Layout" (owner 2026-11-22: "let's add a toggle for 'Home Panels Layout' so people can switch on the previous layout
// if they so choose") — the ACCOUNT half of the setting and its API actions, the health-bar setting's shape. The layout itself is
// chosen server-side by SWUTableHomePanelsPreferred() (SWUSim/Custom/TableLayout.php, pinned in table_seating_test.php). ADDITIVE API.
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php -d xdebug.mode=off SWUSim/DevTools/tests/home_panels_setting_test.php
function check($cond, $msg) { if (!$cond) { fwrite(STDERR, "FAIL: $msg\n"); exit(1); } echo "  ok: $msg\n"; }

$root = __DIR__ . '/../../..';
require_once $root . '/SWUSim/PlayerSettings.php';

check(SWUSIM_SET_MUTE === 1 && SWUSIM_SET_CARD_LANGUAGE === 2 && SWUSIM_SET_HIDE_HEALTH_BARS === 3, 'earlier setting numbers are unchanged');
check(defined('SWUSIM_SET_HOME_PANELS') && SWUSIM_SET_HOME_PANELS === 4, 'Home Panels Layout is setting number 4');
check(SWUSimAccountHomePanels(0) === null, 'a guest has no account value');

$TEST_UID = 987654322;
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
    check($r['success'] && array_key_exists('homePanels', $r) && $r['homePanels'] === null, 'get: never set -> homePanels null');
    $r = $api(['action' => 'promoteHomePanels', 'on' => '1'], $TEST_UID);
    check($r['success'] && $r['promoted'] === true && $r['homePanels'] === 1, 'promote onto an account that never set it');
    $r = $api(['action' => 'promoteHomePanels', 'on' => '0'], $TEST_UID);
    check($r['success'] && $r['promoted'] === false && $r['homePanels'] === 1, 'promote never overwrites an account value');
    $r = $api(['action' => 'setHomePanels', 'on' => '0'], $TEST_UID);
    check($r['success'] && $r['homePanels'] === 0, 'set to off');
    check(SWUSimAccountHomePanels($TEST_UID) === false, 'stored as an explicit off (not "never set")');
    $r = $api(['action' => 'setHomePanels', 'on' => '1'], $TEST_UID);
    check($r['success'] && $r['homePanels'] === 1 && SWUSimAccountHomePanels($TEST_UID) === true, 'set to on');
    $r = $api(['action' => 'setHomePanels'], $TEST_UID);
    check(!$r['success'] && ($r['error'] ?? '') === 'missing_on', 'set without a value is refused');
    $r = $api(['action' => 'get'], $TEST_UID);
    check($r['homePanels'] === 1 && array_key_exists('mute', $r) && array_key_exists('hideHealthBars', $r), 'get keeps the other settings and adds homePanels');
    check(SWUSimAccountHideHealthBars($TEST_UID) === null, 'the new actions never touch the other settings');
    $r = $api(['action' => 'get'], 0);
    check($r['success'] && $r['loggedIn'] === false && array_key_exists('homePanels', $r) && $r['homePanels'] === null, 'guest: homePanels => null');
} finally {
    $wipe();
}
echo "ALL PASS\n";

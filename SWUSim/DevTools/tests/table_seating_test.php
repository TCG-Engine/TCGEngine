<?php
// The multiplayer table (docs/superpowers/specs/2026-11-21-swusim-multiplayer-table-design.md, plan Task 1): which games get the
// table, the owner's per-mode revert switch, and who sits where for every seat — the seating tables are the owner's, verbatim.
//   docker exec -w /var/www/html/TCGEngine -e XDEBUG_MODE=off otmtcge-swusim-web-server-1 php -d apc.enable_cli=1 SWUSim/DevTools/tests/table_seating_test.php
require __DIR__ . '/fixtures/bot_test_bootstrap.php';
require_once __DIR__ . '/../../../AppCore/SWU/Formats.php';

// [left bottom, left top, right bottom, right top] per viewer.
$seat = fn(int $lb, int $lt, int $rb, int $rt) => ['left' => ['bottom' => $lb, 'top' => $lt], 'right' => ['bottom' => $rb, 'top' => $rt]];

echo "TwinSunsClockwise:\n";   // P1: [P2][P3] / [P1][P4] — owner, 2026-11-21
$ffa = [1 => $seat(1, 2, 4, 3), 2 => $seat(2, 3, 1, 4), 3 => $seat(3, 4, 2, 1), 4 => $seat(4, 1, 3, 2)];
foreach ($ffa as $v => $want) {
    $got = SWUTableSeating($v, [1, 2, 3, 4], false);
    $check($got === $want, "Twin Suns P$v: " . json_encode($got));
}

echo "TeammatesAtTheBottom:\n";   // P1: [P4][P2] / [P1][P3] — owner, 2026-11-21
$teams = [1 => $seat(1, 4, 3, 2), 2 => $seat(2, 3, 4, 1), 3 => $seat(3, 2, 1, 4), 4 => $seat(4, 1, 2, 3)];
foreach ($teams as $v => $want) {
    $got = SWUTableSeating($v, [1, 2, 3, 4], true);
    $check($got === $want, "Teams P$v: " . json_encode($got));
}

echo "OnlyTheKnownFourSeatFormats:\n";
// A future 4-seat format must be added here on purpose, or it would silently get the table.
$known = ['twinsuns', 'twinsuns-preview', 'teamsuns', 'teamsuns-preview', 'at4p-premier', 'at4p-eternal'];
foreach (SWUFormatDefinitions() as $id => $f) {
    if (intval($f['maxPlayers'] ?? 2) < 4) continue;
    $check(in_array($id, $known, true), "4-seat format '$id' is one the table was designed for");
}

echo "WhichGamesGetTheTable:\n";
$game = function (string $order, array $flags = []) use ($build) {
    return $build(function ($b) use ($order, $flags) {
        if ($order !== '') { $b->WithSeatOrder($order); $b->WithLiveSeats($order); }
        foreach ($flags as $fl) $b->WithGlobalEffectForPlayer(1, $fl);
    });
};
$game('1234');
$check(SWUTableLayoutKind() === 'table4', 'a 4-seat Twin Suns game gets the table: ' . var_export(SWUTableLayoutKind(), true));
$game('1234', ['SWU_MODE_TEAMS']);
$check(SWUTableLayoutKind() === 'table4', 'a Team Suns game gets the table');
$game('1234', ['SWU_MODE_TEAMS', 'SWU_MODE_ALWAYSTWO', 'SWU_ALWAYSTWO_VOTE']);
$check(SWUTableLayoutKind() === 'table4', 'a 4P Always Two game gets the table');
$game('1234', ['SWU_MODE_TEAMS', 'SWU_MODE_ALWAYSTWO', 'SWU_ALWAYSTWO_SHARED_SEAT']);
$check(SWUTableLayoutKind() === '', '1v1 Always Two keeps its own table until Phase 3');
$game('123');
$check(SWUTableLayoutKind() === 'table3', 'a 3-seat Twin Suns game gets the 3P table (Phase 2): ' . var_export(SWUTableLayoutKind(), true));
$game('');
$check(SWUTableLayoutKind() === '', 'a two-player game never gets the table');

echo "PhonesKeepTheHomePanels:\n";   // owner, 2026-11-22: "on mobile, let's revert to the home panel tiles layout ... desktop looks great"
$game('1234');
$ua = $_SERVER['HTTP_USER_AGENT'] ?? null;
$_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.0 Mobile/15E148 Safari/604.1';
$check(SWUTableLayoutKind() === '', 'a phone (iPhone) gets the Home panels, not the table');
$_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (Linux; Android 14; Pixel 8) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0 Mobile Safari/537.36';
$check(SWUTableLayoutKind() === '', 'an Android phone gets the Home panels');
$_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (iPad; CPU OS 17_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.0 Mobile/15E148 Safari/604.1';
$check(SWUTableLayoutKind() === 'table4', 'a tablet is desktop: it gets the table');
$_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0 Safari/537.36';
$check(SWUTableLayoutKind() === 'table4', 'a desktop browser gets the table');
$_GET['swuLayout'] = 'mobile';
$check(SWUTableLayoutKind() === '', 'the forced phone layout (swuLayout=mobile) is a phone too');
unset($_GET['swuLayout']);
if ($ua === null) unset($_SERVER['HTTP_USER_AGENT']); else $_SERVER['HTTP_USER_AGENT'] = $ua;

echo "ThreeSeatsOpponentsAcrossTheTop:\n";   // owner 2026-11-22: "[them1][them2] / [Me]", clockwise
$three = [1 => $seat(1, 2, 0, 3), 2 => $seat(2, 3, 0, 1), 3 => $seat(3, 1, 0, 2)];
foreach ($three as $v => $want) {
    $got = SWUTableSeating($v, [1, 2, 3], false);
    $check($got === $want, "3P P$v: " . json_encode($got));
}
$game('123');
$GLOBALS['SWU_TABLE_SWITCH_OVERRIDE'] = ['table3' => false];
$check(SWUTableLayoutKind() === '', 'switching table3 off restores the Home panels for 3-seat games');
unset($GLOBALS['SWU_TABLE_SWITCH_OVERRIDE']);

echo "PlayerChoosesHomePanels:\n";   // owner 2026-11-22: the "Home Panels Layout" toggle
require_once __DIR__ . '/../../PlayerSettings.php';
$game('1234');
$_COOKIE['swuHomePanels'] = '1';
$check(SWUTableLayoutKind() === '', 'cookie swuHomePanels=1: Home panels');
$_COOKIE['swuHomePanels'] = '0';
$check(SWUTableLayoutKind() === 'table4', 'cookie swuHomePanels=0: the table');
$GLOBALS['SWU_TABLE_SWITCH_OVERRIDE'] = ['table4' => false];
$check(SWUTableLayoutKind() === '', 'the toggle cannot turn the table ON where the owner switched it off');
unset($GLOBALS['SWU_TABLE_SWITCH_OVERRIDE']);
unset($_COOKIE['swuHomePanels']);
$HP_UID = 987654323;
$hpWipe = function () use ($HP_UID) { $c = GetLocalMySQLConnection(); $s = $c->prepare('DELETE FROM savedsettings WHERE playerId = ?'); $s->bind_param('i', $HP_UID); $s->execute(); $s->close(); $c->close(); };
$hpWipe();
$sess = $_SESSION['userid'] ?? null;
$_SESSION['userid'] = $HP_UID;
SWUSimSetSetting($HP_UID, SWUSIM_SET_HOME_PANELS, 1);
$check(SWUTableLayoutKind() === '', 'account setting on, no browser choice: Home panels');
$_COOKIE['swuHomePanels'] = '0';
$check(SWUTableLayoutKind() === 'table4', 'account on but this browser chose the table: the browser wins');
unset($_COOKIE['swuHomePanels']);
$hpWipe();
$check(SWUTableLayoutKind() === 'table4', 'account never set, no cookie: the table (default off)');
if ($sess === null) unset($_SESSION['userid']); else $_SESSION['userid'] = $sess;

echo "OwnerSwitch:\n";
$check(SWUTableLayoutSwitch()['table4'] === true, 'the table is on for 4-seat games');
$game('1234');
$GLOBALS['SWU_TABLE_SWITCH_OVERRIDE'] = ['table4' => false];
$check(SWUTableLayoutKind() === '', 'switching the family off restores the Home panels');
unset($GLOBALS['SWU_TABLE_SWITCH_OVERRIDE']);
$_GET['swuTable'] = 'off';
$devenv = getenv('DEVENV');
putenv('DEVENV=true');
$check(SWUTableLayoutKind() === '', 'dev override swuTable=off pins the Home panels (harnesses)');
putenv('DEVENV=false');
$check(SWUTableLayoutKind() === 'table4', 'outside dev the override is ignored (players cannot opt out by URL)');
putenv($devenv === false ? 'DEVENV' : "DEVENV=$devenv");
unset($_GET['swuTable']);

echo "ClientConfig:\n";
$game('1234', ['SWU_MODE_TEAMS']);
$cfg = SWUTableClientConfig(2);
$check(($cfg['kind'] ?? '') === 'table4' && ($cfg['seating'] ?? null) === $teams[2], 'client config carries the kind and seat 2\'s seating: ' . json_encode($cfg));
$game('');
$check(SWUTableClientConfig(1) === ['kind' => ''], 'two-player: kind is empty, no seating');

$f = $GLOBALS['BOT_TEST_FAILS'];
echo $f === 0 ? "ALL PASS\n" : "$f FAILED\n";
exit($f === 0 ? 0 : 1);

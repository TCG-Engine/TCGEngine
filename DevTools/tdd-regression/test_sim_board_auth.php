<?php
// php -d apc.enable_cli=1 DevTools/tdd-regression/test_sim_board_auth.php
require_once __DIR__ . '/../../Core/GameAuth.php';

putenv('DEVENV=false');
$_SERVER['HTTP_HOST'] = 'upf.talishar.net';
$_SERVER['REMOTE_ADDR'] = '203.0.113.1';
$game = 'board_auth_' . bin2hex(random_bytes(6));
$failures = 0;
function checkBoardAuth($condition, $label) {
    global $failures;
    echo ($condition ? 'PASS ' : 'FAIL ') . $label . "\n";
    if (!$condition) ++$failures;
}

try {
    checkBoardAuth(SimGameWriteAuthKeys('FaBSim', $game, [
        'p1' => 'seat1', 'p2' => 'seat2', 'p3' => 'seat3', 'p4' => 'seat4',
        'isPrivate' => true, 'spectator' => 'watch',
    ]), 'store production-style game credentials');
    $_SESSION = ['p1AuthKey' => 'old-session-1', 'p2AuthKey' => 'old-session-2'];
    $_COOKIE = ['lastAuthKey' => 'parent-domain-key'];
    foreach (range(1, 4) as $seat) {
        $_COOKIE[SimGameSeatCookieName('FaBSim')] = 'seat' . $seat;
        $key = SimGameResolveBoardAuthKey('FaBSim', $game, (string)$seat);
        checkBoardAuth(SimGameValidateSeatAuth('FaBSim', $game, $seat, $key),
            "seat $seat enters despite stale legacy credentials");
        checkBoardAuth(!SimGameValidateSeatAuth('FaBSim', $game, $seat, 'wrong'),
            "seat $seat still rejects incorrect credentials");
    }
    $key = SimGameResolveBoardAuthKey('FaBSim', $game, '1', 'seat1');
    checkBoardAuth(SimGameValidateSeatAuth('FaBSim', $game, 1, $key),
        'explicit rejoin/replay key overrides another seat cookie and session');
    $key = SimGameResolveBoardAuthKey('FaBSim', $game, '4', 'wrong');
    checkBoardAuth(!SimGameValidateSeatAuth('FaBSim', $game, 4, $key),
        'incorrect explicit key cannot fall back to a valid cookie');
    $_COOKIE[SimGameSeatCookieName('FaBSim')] = 'previous-room-key';
    $_COOKIE['lastAuthKey'] = 'seat1';
    $key = SimGameResolveBoardAuthKey('FaBSim', $game, '1');
    checkBoardAuth(SimGameValidateSeatAuth('FaBSim', $game, 1, $key),
        'new legacy-menu game works despite old scoped cookie and stale session');
    $_COOKIE['lastAuthKey'] = 'parent-domain-key';
    $_COOKIE[SimGameSeatCookieName('FaBSim')] = 'seat4';
    checkBoardAuth(SimGameResolveBoardAuthKey('SWUSim', $game, '1') === 'old-session-1',
        'FaBSim cookie does not override another app');
    unset($_COOKIE[SimGameSeatCookieName('FaBSim')]);
    checkBoardAuth(SimGameResolveBoardAuthKey('FaBSim', $game, '1') === 'old-session-1',
        'legacy session fallback remains available');
    checkBoardAuth(SimGameResolveBoardAuthKey('FaBSim', $game, '3') === 'parent-domain-key',
        'legacy cookie fallback remains available');
    $_COOKIE[SimGameSeatCookieName('FaBSim')] = 'seat1';
    $key = SimGameResolveBoardAuthKey('FaBSim', $game, 'S', 'watch');
    checkBoardAuth(SimGameValidateSpectatorAuth('FaBSim', $game, $key),
        'explicit private spectator link works with player credentials present');
    checkBoardAuth(!SimGameValidateSpectatorAuth('FaBSim', $game,
        SimGameResolveBoardAuthKey('FaBSim', $game, 'S')), 'player cookie does not grant private spectator access');
} finally {
    SimGameDeleteAuthKeys('FaBSim', $game);
}
exit($failures ? 1 : 0);

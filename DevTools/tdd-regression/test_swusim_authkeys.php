<?php
// http://localhost:3400/TCGEngine/DevTools/tdd-regression/test_swusim_authkeys.php
header('Content-Type: text/plain');
include_once __DIR__ . '/../../Core/GameAuth.php';

class _TestPlayer {
    private $seat; private $key;
    public function __construct($seat, $key) { $this->seat = $seat; $this->key = $key; }
    public function getGamePlayerID() { return $this->seat; }
    public function getAuthKey() { return $this->key; }
}

$gameName = 'authkeystest_' . uniqid();
$dir = __DIR__ . '/../../SWUSim/Games/' . $gameName;
@mkdir($dir, 0777, true);

$lobby = new stdClass();
$lobby->isPrivate = false;
$lobby->players = [ new _TestPlayer(1, 'KEY_P1_ABC'), new _TestPlayer(2, 'KEY_P2_XYZ') ];

$ok = SimGameWriteAuthKeysFromLobby('SWUSim', $gameName, $lobby);
$read = SimGameReadAuthKeys('SWUSim', $gameName);

$pass = $ok === true
     && is_file($dir . '/AuthKeys.json')
     && $read['p1'] === 'KEY_P1_ABC'
     && $read['p2'] === 'KEY_P2_XYZ';

// cleanup
@unlink($dir . '/AuthKeys.json');
@rmdir($dir);

echo $pass ? "PASS\n" : "FAIL ok=" . var_export($ok, true) . " read=" . json_encode($read) . "\n";

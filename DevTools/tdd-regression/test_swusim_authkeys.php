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

$lobby = new stdClass();
$lobby->isPrivate = false;
$lobby->casterMode = true;
$lobby->players = [ new _TestPlayer(1, 'KEY_P1_ABC'), new _TestPlayer(2, 'KEY_P2_XYZ') ];

$ok = SimGameWriteAuthKeysFromLobby('SWUSim', $gameName, $lobby);
$read = SimGameReadAuthKeys('SWUSim', $gameName);
$legacyLobby = new stdClass();
$legacyLobby->isPrivate = false;
$legacyLobby->players = [];
$legacyAuth = SimGameBuildAuthKeysFromLobby($legacyLobby);
$missingFailsClosed = !SimGameValidateSeatAuth('SWUSim', $gameName . '_missing', 1, 'KEY_P1_ABC');
$assetWithoutLobbyAuthAllowed = SimGameValidateSeatAuth('AzukiDeck', $gameName . '_asset', 1, '');

$pass = $ok === true
     && SimGameHasAuthKeys('SWUSim', $gameName)
     && $read['p1'] === 'KEY_P1_ABC'
     && $read['p2'] === 'KEY_P2_XYZ'
     && $read['casterMode'] === true
     && SimGameValidateSeatAuth('SWUSim', $gameName, 1, 'KEY_P1_ABC') === true
     && SimGameValidateSeatAuth('SWUSim', $gameName, 1, 'WRONG_KEY') === false
     && SimGameIsCasterMode('SWUSim', $gameName) === true
     && SimGameViewerCanSeeHands('SWUSim', $gameName, ['isSpectator' => true]) === true
     && SimGameViewerCanSeeHands('SWUSim', $gameName, ['isSpectator' => false]) === false
     && $missingFailsClosed
     && $assetWithoutLobbyAuthAllowed
     && $legacyAuth['casterMode'] === false;

// cleanup
SimGameDeleteAuthKeys('SWUSim', $gameName);

echo $pass ? "PASS\n" : "FAIL ok=" . var_export($ok, true) . " read=" . json_encode($read) . "\n";

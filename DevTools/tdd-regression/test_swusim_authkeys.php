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
// ⚠ THESE TWO ASSERT PRODUCTION AUTH SEMANTICS, so they must run with the dev short-circuit OFF.
// SimGameValidateSeatAuth's FIRST line is `if (SimGameIsDevelopmentEnvironment()) return true;`, and
// the dev container sets DEVENV=true — so on a dev box a WRONG key validates and a missing game does
// not fail closed. Left alone, this test is unrunnable locally and simply sat red: it was NOT
// detecting an auth bypass, it was detecting its own environment.
// getenv/$_SERVER are re-read on every call, so flipping them around the two calls exercises the real
// path. Restore afterwards — later assertions (asset-without-lobby) DO want the dev behaviour.
$__devWas  = getenv('DEVENV');
$__hostWas = $_SERVER['HTTP_HOST'] ?? null;
$__addrWas = $_SERVER['REMOTE_ADDR'] ?? null;
putenv('DEVENV=false');
$_SERVER['HTTP_HOST'] = 'prod.invalid'; $_SERVER['REMOTE_ADDR'] = '203.0.113.1';

$wrongKeyFails      = SimGameValidateSeatAuth('SWUSim', $gameName, 1, 'WRONG_KEY') === false;
$missingFailsClosed = !SimGameValidateSeatAuth('SWUSim', $gameName . '_missing', 1, 'KEY_P1_ABC');

putenv($__devWas === false ? 'DEVENV' : 'DEVENV=' . $__devWas);
if ($__hostWas === null) unset($_SERVER['HTTP_HOST']); else $_SERVER['HTTP_HOST'] = $__hostWas;
if ($__addrWas === null) unset($_SERVER['REMOTE_ADDR']); else $_SERVER['REMOTE_ADDR'] = $__addrWas;
$assetWithoutLobbyAuthAllowed = SimGameValidateSeatAuth('AzukiDeck', $gameName . '_asset', 1, '');
$memoryGameName = $gameName . '_memory';
$memoryGamestateKey = SimGameGamestateCacheKey('AzukiDeck', $memoryGameName);
$memoryGamestateStored = function_exists('apcu_store') && apcu_store($memoryGamestateKey, 'test-gamestate', 60);
$memoryGameExists = SimGameExists('AzukiDeck', $memoryGameName);

$pass = $ok === true
     && SimGameHasAuthKeys('SWUSim', $gameName)
     && $read['p1'] === 'KEY_P1_ABC'
     && $read['p2'] === 'KEY_P2_XYZ'
     && $read['casterMode'] === true
     && SimGameValidateSeatAuth('SWUSim', $gameName, 1, 'KEY_P1_ABC') === true
     && $wrongKeyFails
     && SimGameIsCasterMode('SWUSim', $gameName) === true
     && SimGameViewerCanSeeHands('SWUSim', $gameName, ['isSpectator' => true]) === true
     && SimGameViewerCanSeeHands('SWUSim', $gameName, ['isSpectator' => false]) === false
     && $missingFailsClosed
     && $assetWithoutLobbyAuthAllowed
     && $memoryGamestateStored
     && $memoryGameExists
     && $legacyAuth['casterMode'] === false;

// cleanup
SimGameDeleteAuthKeys('SWUSim', $gameName);
if (function_exists('apcu_delete')) apcu_delete($memoryGamestateKey);

echo $pass ? "PASS\n" : "FAIL ok=" . var_export($ok, true) . " read=" . json_encode($read) . "\n";

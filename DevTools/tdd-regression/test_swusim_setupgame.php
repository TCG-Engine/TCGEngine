<?php
// http://localhost:3400/TCGEngine/DevTools/tdd-regression/test_swusim_setupgame.php
header('Content-Type: text/plain');

class _SUPlayer {
    private $seat; private $key; private $link; private $pre;
    public function __construct($seat, $link = '', $pre = '') { $this->seat = $seat; $this->link = $link; $this->pre = $pre; $this->key = 'k' . $seat . uniqid(); }
    public function getGamePlayerID() { return $this->seat; }
    public function setGamePlayerID($s) { $this->seat = $s; }
    public function getAuthKey() { return $this->key; }
    public function getDeckLink() { return $this->link; }
    public function getPreconstructedDeck() { return $this->pre; }
}

include __DIR__ . '/../../SWUSim/CreateGame.php'; // defines SWUSetupGame; must NOT auto-run with no ambient $lobby

function _mkLobby() {
    $l = new stdClass();
    $l->isPrivate = false;
    $l->players = [ new _SUPlayer(1), new _SUPlayer(2) ];
    return $l;
}

$checks = [];

// forcedFirstPlayer = 1
$g1 = SWUSetupGame(_mkLobby(), ['forcedFirstPlayer' => 1]);
$dir1 = __DIR__ . '/../../SWUSim/Games/' . $g1;
$checks['game1 dir created']   = is_dir($dir1);
$checks['game1 authkeys']      = is_file($dir1 . '/AuthKeys.json');
$checks['game1 gamestate']     = is_file($dir1 . '/Gamestate.txt');

// forcedFirstPlayer = 2 → distinct game id
$g2 = SWUSetupGame(_mkLobby(), ['forcedFirstPlayer' => 2]);
$checks['distinct game ids']   = ($g1 !== $g2);

echo (count(array_filter($checks)) === count($checks))
   ? "PASS (" . count($checks) . " checks) g1=$g1 g2=$g2\n"
   : "FAIL: " . implode(', ', array_keys(array_filter($checks, fn($v)=>!$v))) . "\n";

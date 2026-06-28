<?php
// http://localhost:3400/TCGEngine/DevTools/tdd-regression/test_swusim_match_from_lobby.php
header('Content-Type: text/plain');
include __DIR__ . '/../../SWUSim/MatchFlow.php';

class _MFLPlayer {
    private $seat; private $key; private $link;
    public function __construct($seat, $link) { $this->seat = $seat; $this->link = $link; $this->key = 'mk' . $seat . uniqid(); }
    public function getGamePlayerID() { return $this->seat; }
    public function setGamePlayerID($s) { $this->seat = $s; }
    public function getAuthKey() { return $this->key; }
    public function getDeckLink() { return $this->link; }
    public function getPreconstructedDeck() { return ''; }
}

// Valid Premier deck: leader + base + 50 (within copy limits via distinct commons).
$cards = ['JTL_100','LOF_100','SEC_100','LAW_100','ASH_100','IBH_010',
          'JTL_101','LOF_101','SEC_101','LAW_101','ASH_101','IBH_011',
          'JTL_102','LOF_102','SEC_102','LAW_102'];
$deckLines = ["Leader","JTL_001","Base","JTL_023","Deck"];
foreach ($cards as $c) $deckLines[] = "3 $c";   // 48
$deckLines[] = "1 JTL_103"; $deckLines[] = "1 LOF_103"; // 50
$deck = implode("\n", $deckLines);

$lobby = new stdClass();
$lobby->isPrivate = false;
$lobby->format = 'premier';
$lobby->queueType = 'bo3';
$lobby->players = [ new _MFLPlayer(1, $deck), new _MFLPlayer(2, $deck) ];

$matchId = SWUCreateMatchFromLobby($lobby);
$checks = [];
$checks['match created'] = is_string($matchId) && $matchId !== '';
$m = $matchId ? SWUReadMatch($matchId) : null;
$checks['game1 linked'] = is_array($m) && isset($m['games'][0]['gameName']);
$g1 = $m['games'][0]['gameName'] ?? '';
$checks['lobby gameName set'] = strval($lobby->gameName ?? '') === $g1 && $g1 !== '';
$checks['matchref written'] = is_array(SWUReadMatchRef($g1)) && SWUReadMatchRef($g1)['matchId'] === $matchId;
$checks['bestOf 3'] = ($m['bestOf'] ?? null) === 3;
$checks['match authkeys stored'] = ($m['players']['1']['authKey'] ?? '') !== '';

echo (count(array_filter($checks)) === count($checks))
   ? "PASS (" . count($checks) . " checks) match=$matchId game1=$g1\n"
   : "FAIL: " . implode(', ', array_keys(array_filter($checks, fn($v)=>!$v))) . "\n  flash=" . GetFlashMessage() . "\n";

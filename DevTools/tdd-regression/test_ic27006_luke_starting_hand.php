<?php
// RUN VIA CLI:
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php -d apc.enable_cli=1 -d xdebug.mode=off DevTools/tdd-regression/test_ic27006_luke_starting_hand.php
//
// IC27_006 Luke Skywalker (I Have to Face Him) — leader front: "Draw 2 more cards in your starting hand."
// Through PRODUCTION: a real SWUSetupGame game (forced first player, so no initiative prompt) deals the opening hands
// via QueuePregameSetup, and a YES to the mulligan runs the real MulliganDecision handler through the action runner.
// The schema suite cannot see either — its pregame is computed, not dealt. Luke's seat holds 8, the other seat 6;
// the mulligan redraws 8 (the official Colossus ruling: a starting-hand modifier also applies to the mulligan hand).
error_reporting(E_ALL & ~E_DEPRECATED & ~E_WARNING & ~E_NOTICE);
chdir(dirname(__DIR__, 2));
$ROOT = getcwd();
require_once $ROOT . '/Core/EngineActionRunner.php';
EngineLoadRootRuntime('SWUSim');
require_once $ROOT . '/SWUSim/CreateGame.php';

class _LukeHandPlayer {
    private $seat; private $key;
    public function __construct($seat) { $this->seat = $seat; $this->key = 'k' . $seat . uniqid(); }
    public function getGamePlayerID() { return $this->seat; }
    public function setGamePlayerID($s) { $this->seat = $s; }
    public function getAuthKey() { return $this->key; }
    public function getDeckLink() { return ''; }
    public function getPreconstructedDeck() { return ''; }
    public function getUserId() { return null; }
}

$fails = 0;
$check = function ($ok, $msg) use (&$fails) { echo ($ok ? 'PASS' : 'FAIL') . ": $msg\n"; if (!$ok) $fails++; };
$main = array_fill(0, 50, 'SOR_095');
$deck = fn(string $leader) => ['success' => true, 'leader' => $leader, 'base' => 'SOR_024', 'mainDeck' => $main, 'sideboard' => []];
$liveHand = fn(int $p) => count(array_filter(GetHand($p), fn($c) => empty($c->removed)));
$load = function (string $g) use ($ROOT) { global $gameName, $playerID; $gameName = $g; $playerID = 1; ParseGamestate($ROOT . '/SWUSim/'); };

$l = new stdClass();
$l->format = 'premier'; $l->isPrivate = true; $l->queueType = 'bo1';
$l->players = [new _LukeHandPlayer(1), new _LukeHandPlayer(2)];
ob_start();
$g = SWUSetupGame($l, ['forcedFirstPlayer' => 1, 'resolvedDecks' => [1 => $deck('IC27_006'), 2 => $deck('SOR_014')]]);
ob_end_clean();
$load($g);
$check($liveHand(1) === 8, 'Luke\'s seat is dealt 8 — got ' . $liveHand(1));
$check($liveHand(2) === 6, 'the other seat is dealt 6 — got ' . $liveHand(2));
$check(count(array_filter(GetDeck(1), fn($c) => empty($c->removed))) === 42, 'Luke\'s deck lost exactly 8');

ob_start();
$r = EngineRunAction(['playerID' => 1, 'mode' => 100, 'cardID' => 'YES', 'buttonInput' => '', 'chkInput' => [], 'inputText' => ''], 'SWUSim', $g);
ob_end_clean();
$load($g);
$check(!empty($r['success']), 'the mulligan answer is accepted');
$check($liveHand(1) === 8, 'the mulligan redraws 8 — got ' . $liveHand(1));

array_map('unlink', glob("$ROOT/SWUSim/Games/$g/*") ?: []); @rmdir("$ROOT/SWUSim/Games/$g");
echo $fails === 0 ? "\nALL PASS\n" : "\n$fails FAILED\n";
exit($fails === 0 ? 0 : 1);

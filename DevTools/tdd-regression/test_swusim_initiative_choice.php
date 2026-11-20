<?php
// RUN VIA CLI:
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php -d apc.enable_cli=1 -d xdebug.mode=off DevTools/tdd-regression/test_swusim_initiative_choice.php
//
// CR 5.2.1c "Determine the first player": a player is chosen at random and THAT player decides who begins with the
// initiative. A 1v1 game used to hand the initiative straight to the coin-flip winner (owner report 2026-10-30).
// Now the winner is asked "Go first / Go second" before anything else — the hands are dealt only after it — and the
// answer goes through the production action path (mode 100). A Bo3 game 2/3 (and a rematch, the same Core spawn) asks
// the prior game's LOSER instead. Twin Suns keeps a random first player (owner), and a plain forced first player
// (tests, self-play) is never asked.
error_reporting(E_ALL & ~E_DEPRECATED & ~E_WARNING & ~E_NOTICE);
chdir(dirname(__DIR__, 2));
$ROOT = getcwd();
require_once $ROOT . '/Core/EngineActionRunner.php';
EngineLoadRootRuntime('SWUSim');
require_once $ROOT . '/SWUSim/CreateGame.php';
require_once $ROOT . '/SWUSim/MatchFlow.php';

class _InitChoicePlayer {
    private $seat; private $key; private $link;
    public function __construct($seat, $link) { $this->seat = $seat; $this->link = $link; $this->key = 'k' . $seat . uniqid(); }
    public function getGamePlayerID() { return $this->seat; }
    public function setGamePlayerID($s) { $this->seat = $s; }
    public function getAuthKey() { return $this->key; }
    public function getDeckLink() { return ''; }
    public function getPreconstructedDeck() { return ''; }
    public function getUserId() { return null; }
}

$mk = function (array $spec) { $o = []; foreach ($spec as $id => $n) for ($i = 0; $i < $n; $i++) $o[] = $id; return $o; };
$main = $mk(['SOR_095' => 8, 'SOR_046' => 8, 'JTL_039' => 8, 'SOR_100' => 8, 'SOR_101' => 8, 'SOR_102' => 10]);   // 50
$deck = ['success' => true, 'leader' => 'JTL_002', 'base' => 'SEC_026', 'mainDeck' => $main, 'sideboard' => []];
$fails = 0;
$check = function ($ok, $msg) use (&$fails) { echo ($ok ? 'PASS' : 'FAIL') . ": $msg\n"; if (!$ok) $fails++; };
$created = [];
$load = function (string $g) use ($ROOT) {
    global $gameName, $playerID;
    $gameName = $g; $playerID = 1;
    ParseGamestate($ROOT . '/SWUSim/');
};
$create = function (string $format, int $seats, array $opts = []) use ($deck, $load, &$created) {
    $l = new stdClass();
    $l->format = $format; $l->isPrivate = true; $l->queueType = 'bo1';
    $l->players = [];
    for ($s = 1; $s <= $seats; $s++) { $l->players[] = new _InitChoicePlayer($s, ''); $opts['resolvedDecks'][$s] = $deck; }
    ob_start(); $g = SWUSetupGame($l, $opts); ob_end_clean();
    $created[] = $g;
    $load($g);
    return $g;
};
$front = function (int $p) { foreach (GetDecisionQueue($p) as $d) if ($d !== null && empty($d->removed)) return $d; return null; };
$desc = fn($d) => $d ? $d->Type . ':' . $d->Param : 'none';
$liveHand = fn(int $p) => count(array_filter(GetHand($p), fn($c) => empty($c->removed)));
$isMulligan = fn($d) => $d !== null && $d->Type === 'YESNO' && strpos((string)$d->Param, 'mulligan') !== false;
$answer = function (string $g, int $seat, string $choice) use ($load) {
    ob_start();
    $r = EngineRunAction(['playerID' => $seat, 'mode' => 100, 'cardID' => $choice, 'buttonInput' => '', 'chkInput' => [], 'inputText' => ''], 'SWUSim', $g);
    ob_end_clean();
    $load($g);
    return $r;
};

foreach (['Go_second', 'Go_first'] as $choice) {
    $g = $create('premier', 2);
    $winner = intval(GetFirstPlayer());
    $other = 3 - $winner;
    $d = $front($winner);
    $check($d !== null && $d->Type === 'OPTIONCHOOSE' && $d->Param === 'Go_first&Go_second',
        "[$choice] the roll winner (P$winner) is asked Go first / Go second — got " . $desc($d));
    $check($front($other) === null, "[$choice] the other seat has nothing to answer yet — got " . $desc($front($other)));
    $check($liveHand(1) === 0 && $liveHand(2) === 0, "[$choice] no opening hand is dealt before the choice (CR 5.2.1c before d)");

    $r = $answer($g, $winner, $choice);
    $check(!empty($r['success']), "[$choice] the answer is accepted by the action runner");
    $want = $choice === 'Go_second' ? $other : $winner;
    $check(intval(GetFirstPlayer()) === $want, "[$choice] first player is P$want — got P" . intval(GetFirstPlayer()));
    $check(intval(GetTurnPlayer()) === $want, "[$choice] P$want is the first active player — got P" . intval(GetTurnPlayer()));
    $check(strval(GetInitiativeCounter()) === "P{$want}_UNCLAIMED", "[$choice] P$want holds the initiative — got " . GetInitiativeCounter());
    $check($liveHand(1) === 6 && $liveHand(2) === 6, "[$choice] both opening hands are dealt after the choice");
    $check($isMulligan($front(1)) && $isMulligan($front(2)), "[$choice] both seats are then on their mulligan — got " . $desc($front(1)) . ' / ' . $desc($front(2)));
    $wantLog = "P{$winner} won the initiative roll and chose to go " . ($choice === 'Go_second' ? 'second' : 'first');
    $check(strpos(strval(GetGameLog()), $wantLog) !== false, "[$choice] the game log says \"$wantLog\"");
}

// A plain forced first player (tests, self-play) — no choice.
$g = $create('premier', 2, ['forcedFirstPlayer' => 2]);
$check(intval(GetFirstPlayer()) === 2, 'forced: P2 opens');
$check($isMulligan($front(1)) && $isMulligan($front(2)), 'forced: no initiative choice, straight to the mulligans — got ' . $desc($front(1)) . ' / ' . $desc($front(2)));

// Bo3 game 2: the game-1 LOSER chooses (owner 2026-10-30). Spawned the production way — both seats submit their
// sideboard and MatchMaybeSpawnAfterSideboard → MatchSpawnNextGameWithDecks → setupGame.
foreach ([[1, 'Go_second', 2], [2, 'Go_first', 2]] as [$loser, $choice, $want]) {
    $matchId = MatchCreate('SWUSim', 'premier', 'bo3', [1 => ['originalDeck' => $deck, 'authKey' => 'k1'], 2 => ['originalDeck' => $deck, 'authKey' => 'k2']]);
    MatchBeginSideboarding('SWUSim', $matchId, $loser);
    MatchSubmitSideboardDeck('SWUSim', $matchId, 1, $deck);
    MatchSubmitSideboardDeck('SWUSim', $matchId, 2, $deck);
    ob_start(); $g = (string)MatchMaybeSpawnAfterSideboard('SWUSim', $matchId); ob_end_clean();
    $created[] = $g;
    $load($g);
    $winner = 3 - $loser;
    $d = $front($loser);
    $check($d !== null && $d->Type === 'OPTIONCHOOSE' && $d->Param === 'Go_first&Go_second'
        && strpos((string)$d->Tooltip, 'lost_the_previous_game') !== false,
        "[Bo3 loser P$loser] the game-1 loser is asked Go first / Go second — got " . $desc($d) . ' ' . ($d->Tooltip ?? ''));
    $check($front($winner) === null, "[Bo3 loser P$loser] the game-1 winner has nothing to answer yet");
    $check($liveHand(1) === 0 && $liveHand(2) === 0, "[Bo3 loser P$loser] no opening hand before the choice");
    $r = $answer($g, $loser, $choice);
    $check(!empty($r['success']), "[Bo3 loser P$loser] the answer is accepted by the action runner");
    $first = $choice === 'Go_second' ? $winner : $loser;
    $check(intval(GetFirstPlayer()) === $first && strval(GetInitiativeCounter()) === "P{$first}_UNCLAIMED",
        "[Bo3 loser P$loser, $choice] P$first opens with the initiative — got P" . intval(GetFirstPlayer()) . ' ' . GetInitiativeCounter());
    $check($isMulligan($front(1)) && $isMulligan($front(2)), "[Bo3 loser P$loser] both seats then mulligan");
    $wantLog = "P{$loser} lost the previous game and chose to go " . ($choice === 'Go_second' ? 'second' : 'first');
    $check(strpos(strval(GetGameLog()), $wantLog) !== false, "[Bo3 loser P$loser] the game log says \"$wantLog\"");
}

// Twin Suns keeps a random first player — no choice.
$g = $create('twinsuns', 4);
$asked = false;
for ($s = 1; $s <= 4; $s++) if (($d = $front($s)) !== null && $d->Type === 'OPTIONCHOOSE') $asked = true;
$check(!$asked, 'Twin Suns: nobody is asked to choose the first player');
$check($isMulligan($front(intval(GetFirstPlayer()))), 'Twin Suns: the random first player is straight on their mulligan');

foreach ($created as $g) {
    if (!$g) continue;
    array_map('unlink', glob("$ROOT/SWUSim/Games/$g/*") ?: []);
    @rmdir("$ROOT/SWUSim/Games/$g");
}
echo $fails === 0 ? "\nALL PASS\n" : "\n$fails FAILED\n";
exit($fails === 0 ? 0 : 1);

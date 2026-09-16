<?php
// RUN VIA CLI:
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php -d apc.enable_cli=1 -d xdebug.mode=off DevTools/tdd-regression/test_swusim_game_setup_pool.php
//
// SWUSetupGame (SWUSim/CreateGame.php), in-process: team rules and the card pool every game records.
// Spec: docs/superpowers/specs/2026-09-16-swusim-format-menu-design.md §2 "Game".
// ⚠ The trap this pins: team rules used to switch on only for the literal id 'teamsuns', so a Team Suns Preview game loaded
// with team rules OFF and played as a four-player free-for-all. Controls: plain Team Suns keeps them; Twin Suns never had them.
error_reporting(E_ALL & ~E_DEPRECATED & ~E_WARNING & ~E_NOTICE);
chdir(dirname(__DIR__, 2));
$ROOT = getcwd();
require_once $ROOT . '/Core/EngineActionRunner.php';
EngineLoadRootRuntime('SWUSim');
require_once $ROOT . '/SWUSim/CreateGame.php';

class _PoolTestPlayer {
    private $seat; private $key; private $link;
    public function __construct($seat, $link) { $this->seat = $seat; $this->link = $link; $this->key = 'k' . $seat . uniqid(); }
    public function getGamePlayerID() { return $this->seat; }
    public function setGamePlayerID($s) { $this->seat = $s; }
    public function getAuthKey() { return $this->key; }
    public function getDeckLink() { return $this->link; }
    public function getPreconstructedDeck() { return ''; }
    public function getUserId() { return null; }
}

$deck = file_get_contents($ROOT . '/SWUSim/Tests/BotFixtures/premier_deck_a.txt');
$checks = [];
$created = [];
// Create a game in-process and read back team rules, the stored card pool and the mode.
$game = function (string $format, int $seats, ?string $cardPool = null, ?string $seat2Deck = null) use ($deck, $ROOT, &$created) {
    $l = new stdClass();
    $l->format = $format; $l->isPrivate = true; $l->queueType = 'bo1';
    if ($cardPool !== null) $l->cardPool = $cardPool;
    $l->players = [];
    for ($s = 1; $s <= $seats; $s++) $l->players[] = new _PoolTestPlayer($s, $s === 2 && $seat2Deck !== null ? $seat2Deck : $deck);
    ob_start();
    $g = SWUSetupGame($l);
    ob_end_clean();
    $created[] = $g;
    global $gameName, $playerID;
    $gameName = $g; $playerID = 1;
    ParseGamestate($ROOT . '/SWUSim/');
    return ['teams' => SWUIsTeamGame(), 'pool' => SWUGameCardPool(), 'mode' => SWUGameMode()];
};

$tsp = $game('teamsuns-preview', 4);
$checks['Team Suns Preview game has team rules ON']      = $tsp['teams'] === true;
$checks['Team Suns Preview records its own card pool']   = $tsp['pool'] === 'teamsuns-preview';
$ts = $game('teamsuns', 4);
$checks['control: Team Suns still has team rules']       = $ts['teams'] === true;
$tw = $game('twinsuns', 4);
$checks['control: Twin Suns has no team rules']          = $tw['teams'] === false;
$checks['Twin Suns records card pool twinsuns']          = $tw['pool'] === 'twinsuns';
$twp = $game('twinsuns-preview', 4);
$checks['control: Twin Suns Preview has no team rules']  = $twp['teams'] === false;
$pr = $game('premier', 2);
$checks['a 2-seat Premier game records premier']         = $pr['pool'] === 'premier';
$bp = $game('botpractice', 2, 'eternal');
$checks['an Arenabot game records its chosen pool']      = $bp['pool'] === 'eternal';
$checks['the Arenabot game keeps mode botpractice']      = $bp['mode'] === 'botpractice';
$bpNone = $game('botpractice', 2);
$checks['an Arenabot lobby without a pool records open'] = $bpNone['pool'] === 'open';
$gf = $game('goldfish', 2);
$checks['a Goldfish game records open']                  = $gf['pool'] === 'open' && $gf['mode'] === 'goldfish';

foreach ($created as $g) {
    if (!$g) continue;
    array_map('unlink', glob("$ROOT/SWUSim/Games/$g/*") ?: []);
    @rmdir("$ROOT/SWUSim/Games/$g");
}
$fails = array_keys(array_filter($checks, fn($v) => $v !== true));
echo empty($fails) ? "PASS (" . count($checks) . " checks)\n" : "FAIL: " . implode(', ', $fails) . "\n";
exit(empty($fails) ? 0 : 1);

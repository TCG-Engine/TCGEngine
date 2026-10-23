<?php
// SWU-PGN writer start: SWUSetupGame starts a recording for human 2-seat PvP games only, with the setup events,
// the post-shuffle deck orders and the meta the finished file needs.
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php DevTools/tdd-regression/test_swupgn_writer_start.php
error_reporting(E_ALL & ~E_DEPRECATED); ini_set('display_errors', 1);
chdir('/var/www/html/TCGEngine');
if (!function_exists('ConvertMzIDToAbsolute')) { function ConvertMzIDToAbsolute($m, $p): string { return ''; } }
foreach (['DeterministicRNG', 'CoreZoneModifiers', 'GameAuth', 'HTTPLibraries', 'NetworkingLibraries'] as $f) @include_once "./Core/$f.php";
include_once './SWUSim/ZoneClasses.php'; include_once './SWUSim/ZoneAccessors.php';
include_once './SWUSim/GeneratedCode/GeneratedCardDictionaries.php'; include_once './SWUSim/GamestateParser.php';
include_once './SWUSim/MatchFlow.php';
include_once './SWUSim/CreateGame.php';
require_once __DIR__ . '/fixtures/swupgn_test_helpers.php';
require_once __DIR__ . '/../../SWUSim/Custom/SwuPgnCardMap.php';
global $gameName;

class _PgnStartPlayer {
    private $seat; private $uid; private $bot;
    public function __construct($seat, $uid = null, $bot = '') { $this->seat = $seat; $this->uid = $uid; $this->bot = $bot; }
    public function getGamePlayerID() { return $this->seat; }
    public function setGamePlayerID($s) { $this->seat = $s; }
    public function getAuthKey() { return 'k' . $this->seat . getmypid(); }
    public function getDeckLink() { return ''; }
    public function getPreconstructedDeck() { return ''; }
    public function getUserId() { return $this->uid; }
    public function getBotProfile(): string { return $this->bot; }
    public function getUsername() { return $this->bot !== '' ? 'Arenabot Gamma' : ''; }
}
$mk = function (array $spec) { $o = []; foreach ($spec as $id => $n) for ($i = 0; $i < $n; $i++) $o[] = $id; return $o; };
$deck = ['success' => true, 'leader' => 'SOR_005', 'base' => 'SOR_026', 'sideboard' => [],
    'mainDeck' => $mk(['SOR_095' => 3, 'SOR_046' => 3, 'SOR_049' => 3, 'SOR_101' => 3, 'SOR_120' => 3, 'SOR_172' => 3,
        'SOR_169' => 3, 'SOR_096' => 3, 'SOR_102' => 3, 'SOR_103' => 3, 'SOR_104' => 3, 'SOR_105' => 3, 'SOR_106' => 3,
        'SOR_107' => 3, 'SOR_108' => 3, 'SOR_109' => 3, 'SOR_110' => 2])];
$games = [];
$setup = function (string $format, int $seats = 2, array $extra = [], array $opts = []) use ($deck, &$games) {
    $l = new stdClass();
    $l->format = $format; $l->isPrivate = false;
    $l->players = [];
    for ($s = 1; $s <= $seats; $s++) $l->players[] = new _PgnStartPlayer($s, 100 + $s, $extra['bots'][$s] ?? '');
    if (isset($extra['botPlayers'])) $l->botPlayers = $extra['botPlayers'];
    if (array_key_exists('noFormat', $extra)) unset($l->format);
    $resolved = []; for ($s = 1; $s <= $seats; $s++) $resolved[$s] = $deck;
    ob_start();
    try { $g = SWUSetupGame($l, $opts + ['forcedFirstPlayer' => 1, 'resolvedDecks' => $resolved]); }
    finally { ob_end_clean(); }
    $games[] = $g;
    return (string)$g;
};
$recorded = fn(string $g) => is_dir(SwuPgnRecDir($g));

// ── A premier PvP game is recorded ───────────────────────────────────────────────────────────────
$g = $setup('premier');
SwuPgnTestCheck($recorded($g), 'a premier PvP game gets a Pgn folder');
$meta = SwuPgnMetaRead($g) ?? [];
SwuPgnTestEq([$meta['recording'] ?? null, $meta['finished'] ?? null, $meta['undos'] ?? null, $meta['recorderErrors'] ?? null],
    [true, false, 0, 0], 'meta starts recording, unfinished, no undos, no errors');
SwuPgnTestEq($meta['format'] ?? null, 'premier', 'meta.format');
SwuPgnTestEq($meta['cardPool'] ?? null, SWUFormatLegalSets('premier'), 'meta.cardPool = the format\'s legal sets');
SwuPgnTestEq($meta['seed'] ?? null, GetSWUVar('RNG_SEED'), 'meta.seed = the game\'s RNG seed');
SwuPgnTestCheck(preg_match('/^\d{4}-\d\d-\d\dT\d\d:\d\d:\d\dZ$/', strval($meta['startedAt'] ?? '')) === 1, 'meta.startedAt is ISO UTC', strval($meta['startedAt'] ?? ''));
SwuPgnTestCheck(array_key_exists('matchId', $meta) && $meta['matchId'] === null && array_key_exists('gameNumber', $meta) && $meta['gameNumber'] === null, 'no match for a lone game');
SwuPgnTestEq($meta['accounts'] ?? null, ['1' => 101, '2' => 102], 'meta.accounts = the seats\' account ids');
// SETUP: the post-shuffle order is what was drawn (in hand order) followed by what is left, top first.
$state = SwuPgnStateRead($g);
foreach ([1, 2] as $seat) {
    $order = $meta['setup']["p{$seat}DeckOrder"] ?? [];
    $ids = array_map(fn($c) => SwuPgnMapCardId($c), $order);
    $hand = array_values(array_map(fn($o) => $o->CardID, array_filter(GetHand($seat), fn($o) => empty($o->removed))));
    $left = array_values(array_map(fn($o) => $o->CardID, array_filter(GetDeck($seat), fn($o) => empty($o->removed))));
    SwuPgnTestEq($ids, array_merge($hand, $left), "SETUP p{$seat}DeckOrder = the post-shuffle deck (opening hand, then the rest)");
    SwuPgnTestEq(count($order), count($deck['mainDeck']), "SETUP p{$seat}DeckOrder has the whole deck");
}
SwuPgnTestEq(count(array_unique(array_merge($meta['setup']['p1DeckOrder'], $meta['setup']['p2DeckOrder']))),
    2 * count($deck['mainDeck']), 'every copy id is unique across both decks');
SwuPgnTestEq($meta['decks'][1]['leader'] ?? null, 'SOR_005', 'meta.decks holds each seat\'s leader');
$ev = SwuPgnEventsRead($g);
SwuPgnTestEq($ev[0] ?? null, ['seq' => 'R0.S.start', 't' => 'PHASE_START', 'phase' => 'setup'], 'the first event is PHASE_START setup');
$draws = array_filter($ev, fn($e) => $e['t'] === 'MOVE' && $e['from'] === 'deck' && $e['to'] === 'hand');
SwuPgnTestEq(count($draws), count(GetHand(1)) + count(GetHand(2)), 'the opening draws are recorded as deck → hand moves');
SwuPgnTestCheck((bool)$ev && !array_filter(array_slice($ev, 1), fn($e) => !preg_match('/^R0\.S\.\d+$/', $e['seq'])), 'setup events are numbered R0.S.<n>');
SwuPgnTestCheck(!empty($state['setup']) && isset($state['prev']['seats'][2]), 'state.json: in setup, with the board snapshot');

// ── Not recorded: modes, bots, Twin Suns, the off switch ─────────────────────────────────────────
SwuPgnTestCheck(!$recorded($setup('goldfish')), 'goldfish is not recorded');
SwuPgnTestCheck(!$recorded($setup('hotseat')), 'hotseat is not recorded');
SwuPgnTestCheck(!$recorded($setup('botpractice', 2, ['botPlayers' => [2]])), 'Arenabot is not recorded');
SwuPgnTestCheck(!$recorded($setup('twinsuns', 3)), 'a Twin Suns 3-seat game is not recorded');
SwuPgnTestCheck(!$recorded($setup('premier', 2, ['bots' => [2 => 'normal']])), 'a room with a bot seat is not recorded');
putenv('SWUPGN_WRITER_OFF=1');
SwuPgnTestCheck(!$recorded($setup('premier')), 'SWUPGN_WRITER_OFF=1 starts no recording');
putenv('SWUPGN_WRITER_OFF');

// Game names are reused: a recording left by an earlier game of the same name is never adopted — not by an unrecorded
// game (whose seat would otherwise export the old game's file), not by a recorded one.
$stale = function (string $g) {
    @mkdir(SwuPgnRecDir($g) . '/out', 0777, true);
    file_put_contents(SwuPgnRecDir($g) . '/meta.json', json_encode(['recording' => true, 'finished' => true]));
    file_put_contents(SwuPgnRecDir($g) . '/out/p1.swupgn', 'the earlier game');
};
$g = $setup('goldfish'); $stale($g); $gameName = $g;
ob_start(); try { SwuPgnRecStart((object)['format' => 'goldfish', 'players' => []], [], true); } finally { ob_end_clean(); }
SwuPgnTestCheck(!$recorded($g), 'an unrecorded game clears a recording left under its name');
$g = $setup('premier'); $stale($g); $gameName = $g;
ob_start(); try { SwuPgnRecStart((object)['format' => 'premier', 'players' => []], [], true); } finally { ob_end_clean(); }
SwuPgnTestCheck(!is_file(SwuPgnRecDir($g) . '/out/p1.swupgn') && empty((SwuPgnMetaRead($g) ?? [])['finished']), 'a recorded game starts from nothing');

// ── Review Focus 4: a Bo3 game 2 (synthetic lobby, no format) takes format and pool from the match ──
$matchId = MatchCreate('SWUSim', 'eternal', 'bo3', [
    1 => ['originalDeck' => $deck, 'authKey' => 'k1', 'userId' => 201],
    2 => ['originalDeck' => $deck, 'authKey' => 'k2', 'userId' => 202],
]);
ob_start();
try { $g2 = (string)MatchSpawnNextGameWithDecks('SWUSim', $matchId, 1, '', [1 => $deck, 2 => $deck]); }
finally { ob_end_clean(); }
$games[] = $g2;
$m2 = SwuPgnMetaRead($g2) ?? [];
SwuPgnTestCheck($recorded($g2), 'a Bo3 child game is recorded');
SwuPgnTestEq([$m2['format'] ?? null, $m2['cardPool'] ?? null], ['eternal', SWUFormatLegalSets('eternal')], 'format and pool come from the match record');
SwuPgnTestEq([$m2['matchId'] ?? null, $m2['gameNumber'] ?? null], [$matchId, 1], 'meta carries the match id and game number');
SwuPgnTestEq($m2['accounts'] ?? null, ['1' => 201, '2' => 202], 'accounts come from the match record when the lobby has none');

foreach ($games as $x) if ($x !== '') exec('rm -rf ' . escapeshellarg('./SWUSim/Games/' . $x));
@unlink(MatchPath('SWUSim', $matchId));
SwuPgnTestFinish();

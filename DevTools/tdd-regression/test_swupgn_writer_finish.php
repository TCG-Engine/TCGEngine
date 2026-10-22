<?php
// SWU-PGN writer finish: at game end the recorder writes the all-seeing file and both players' own-view files, each
// checked by our reader, behind the PII gate — once.
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php -d apc.enable_cli=1 DevTools/tdd-regression/test_swupgn_writer_finish.php
error_reporting(E_ALL & ~E_DEPRECATED); ini_set('display_errors', 1);
chdir('/var/www/html/TCGEngine');
if (!function_exists('ConvertMzIDToAbsolute'))      { function ConvertMzIDToAbsolute($m,$p):string{return '';} }
if (!function_exists('QueueDamageAnimation'))       { function QueueDamageAnimation($t,$a):void{} }
if (!function_exists('QueueRestoreAnimation'))      { function QueueRestoreAnimation($t,$a):void{} }
if (!function_exists('QueuePreventedDamageAnimation')) { function QueuePreventedDamageAnimation($t):void{} }
if (!function_exists('QueueShieldBreakAnimation'))  { function QueueShieldBreakAnimation($t):void{} }
foreach (['DeterministicRNG', 'CoreZoneModifiers', 'GameAuth', 'HTTPLibraries', 'NetworkingLibraries'] as $f) @include_once "./Core/$f.php";
include_once './SWUSim/ZoneClasses.php'; include_once './SWUSim/ZoneAccessors.php';
include_once './SWUSim/GeneratedCode/GeneratedCardDictionaries.php'; include_once './SWUSim/GamestateParser.php';
include_once './SWUSim/MatchFlow.php';
include_once './SWUSim/CreateGame.php';
foreach (['Assertions', 'Cards', 'CommonSetup', 'GameStateBuilder', 'GameTestAdapter'] as $f) include_once "./SWUSim/Tests/Framework/$f.php";
require_once __DIR__ . '/fixtures/swupgn_test_helpers.php';
global $gameName, $playerID;

$health = sys_get_temp_dir() . '/swupgn-finish-health-' . getmypid() . '.jsonl';
$GLOBALS['SWUPGN_HEALTH_FILE'] = $health;
$GLOBALS['SWUPGN_TEST_USERNAMES'] = [1 => 'zorbaxian', 2 => 'Marine'];   // seat 2 is named like a card in the game
$games = [];
$frame = function (callable $fn) {
    SwuPgnRecFrameBegin();
    ob_start();
    try { $fn(); } finally { ob_end_clean(); }
    SwuPgnRecFrameCommit();
};
$files = function (string $g): array {
    $out = [];
    foreach (['all', 'p1', 'p2'] as $k) {
        $f = SwuPgnRecDir($g) . "/out/$k.swupgn";
        $out[$k] = is_file($f) ? file_get_contents($f) : null;
    }
    return $out;
};
$healthLines = fn() => is_file($health) ? array_map(fn($l) => json_decode($l, true), file($health, FILE_IGNORE_NEW_LINES)) : [];
// One base hit from a win: seat 1's ready Battlefield Marine, seat 2's base at 27 of 30.
$lethal = function (string $tag) use (&$games): GameTestAdapter {
    global $gameName;
    $gameName = 'swupgnfin_' . $tag . '_' . getmypid(); $games[] = $gameName;
    @mkdir('./SWUSim/Games/' . $gameName, 0777, true);
    $b = new GameStateBuilder();
    CommonSetup($b, 'grw', 'brk', ['resourceCount' => 4], ['resourceCount' => 4, 'baseDamage' => 27]);
    $b->WithActivePlayer(1)->WithGroundUnitForPlayer(1, 'SOR_095');
    foreach (['SOR_101', 'SOR_102'] as $c) { $b->WithCardInDeckForPlayer(1, $c); $b->WithCardInDeckForPlayer(2, $c); }
    $a = new GameTestAdapter(); $a->loadState($b);
    SwuPgnRecForceForTest($gameName);
    return $a;
};
$checkFile = function (?string $text, string $label) {
    if ($text === null) { SwuPgnTestCheck(false, "$label exists"); return null; }
    $doc = SwuPgnParse($text);
    SwuPgnTestEq(SwuPgnValidate($doc)['errors'], [], "$label validates");
    $kc = SwuPgnCheckKeyframes($doc['events']);
    SwuPgnTestCheck(!empty($kc['ok']), "$label passes its keyframe check", json_encode(array_slice($kc['mismatches'] ?? [], 0, 3)));
    return $doc;
};

// ── A base destroyed: the three files ────────────────────────────────────────────────────────────
$a = $lethal('win');
$frame(fn() => $a->declareAttack(1, 'myGroundArena-0', 'theirBase-0'));
SwuPgnTestEq(SWUGetGameWinner(), 1, 'seat 1 won');
$meta = SwuPgnMetaRead($gameName);
SwuPgnTestEq($meta['finished'] ?? null, true, 'the recording is finished');
$f = $files($gameName);
$all = $checkFile($f['all'], 'all.swupgn');
$p1 = $checkFile($f['p1'], 'p1.swupgn');
$p2 = $checkFile($f['p2'], 'p2.swupgn');
if ($all && $p1 && $p2) {
    $h = $all['headers'];
    SwuPgnTestCheck(preg_match('/^petranaki@([0-9a-f]{7}|unknown)$/', $h['Engine'] ?? '') === 1, 'Engine petranaki@<sha7>', $h['Engine'] ?? '');
    SwuPgnTestEq([$h['Seed'], $p1['headers']['Seed'], $p2['headers']['Seed']], [$meta['seed'], 'unseeded', 'unseeded'], 'Seed real in all, "unseeded" in p1/p2');
    SwuPgnTestEq([$p1['headers']['GameId'], $p2['headers']['GameId']], [$h['GameId'], $h['GameId']], 'one GameId in all three');
    SwuPgnTestCheck(str_starts_with($h['P1Id'], 'sha256:') && str_starts_with($h['P2Id'], 'sha256:') && $h['P1Id'] !== $h['P2Id'], 'P1Id / P2Id are distinct sha256 ids');
    SwuPgnTestEq([$h['P1'], $h['P2']], ['Player 1', 'Player 2'], 'generic player names');
    SwuPgnTestCheck(!array_key_exists('Perspective', $h) && $p1['headers']['Perspective'] === 'P1' && $p2['headers']['Perspective'] === 'P2', 'Perspective only on the players\' files');
    SwuPgnTestEq([$h['Result'], $h['Reason']], ['P1', 'Base Destroyed'], 'Result / Reason');
    SwuPgnTestCheck(preg_match('/^\d+$/', $h['Rounds']) === 1 && isset($h['EndDate']), 'Rounds and EndDate');
    SwuPgnTestCheck(!str_contains($f['all'] . $f['p1'] . $f['p2'], '"ms"'), 'no ms anywhere');
    $end = end($all['events']);
    SwuPgnTestCheck(($end['t'] ?? '') === 'GAME_END' && $end['winner'] === 1 && str_ends_with($end['seq'], '.game-end'), 'the last event is GAME_END at <round>.<phase>.game-end');
    SwuPgnTestCheck(count(array_filter($all['cards'], fn($c) => $c['id'] === 'SOR#95' && $c['name'] === 'Battlefield Marine' && ($c['kind'] ?? null) === 'unit')) === 1, 'CARDS names the cards, with kind');
    SwuPgnTestCheck(is_array($all['story']) && count($all['story']) > 0, 'a STORY');
    SwuPgnTestEq(count($all['decks']), 2, 'DECKS for both seats');
}
$hl = $healthLines();
SwuPgnTestCheck(count($hl) === 1 && ($hl[0]['game'] ?? '') === $gameName && empty($hl[0]['refused']), 'one health line', json_encode($hl));
SwuPgnTestCheck(is_file(SwuPgnRecDir($gameName) . '/out/all.swupgn'), 'seat 2 named "Marine" (a card in this game) did not block the files');

// Finishing again writes nothing new.
$mt = filemtime(SwuPgnRecDir($gameName) . '/out/all.swupgn');
sleep(1);
SwuPgnRecFinishIfOver($gameName);
clearstatcache();
SwuPgnTestEq([filemtime(SwuPgnRecDir($gameName) . '/out/all.swupgn'), count($healthLines())], [$mt, 1], 'a second finish writes nothing');

// ── Task 11 / Review Focus 2: a bookmark from before the end reopens it; finishing again rewrites all three ──
$GLOBALS['SWU_TEST_FORCE_PRIVATE'] = true;
$a = $lethal('reopen');
$frame(fn() => SWUTakeBookmark(1, 'pre', 'SWUSim', $GLOBALS['gameName']));
$frame(fn() => $a->declareAttack(1, 'myGroundArena-0', 'theirBase-0'));
SwuPgnTestCheck(is_file(SwuPgnRecDir($gameName) . '/out/all.swupgn'), 'finished once');
$frame(fn() => SWULoadBookmark(1, 1, 'SWUSim', $GLOBALS['gameName']));
SwuPgnTestCheck(!is_dir(SwuPgnRecDir($gameName) . '/out') && (SwuPgnMetaRead($gameName)['finished'] ?? null) === false, 'the bookmark reopened it');
$frame(fn() => $a->declareAttack(1, 'myGroundArena-0', 'theirBase-0'));
$f = $files($gameName);
$reAll = $checkFile($f['all'], 'the re-finished all.swupgn');
$checkFile($f['p1'], 'the re-finished p1.swupgn');
$checkFile($f['p2'], 'the re-finished p2.swupgn');
SwuPgnTestCheck($reAll && intval($reAll['headers']['Undos'] ?? 0) === 1 && count(array_filter($reAll['events'], fn($e) => $e['t'] === 'GAME_END')) === 1,
    'the re-finished file counts the undo and ends once');
unset($GLOBALS['SWU_TEST_FORCE_PRIVATE']);

// ── Review Focus 3: a Bo3 conceded with mode 10007 (no gamestate winner) ────────────────────────
$mk = function (array $spec) { $o = []; foreach ($spec as $id => $n) for ($i = 0; $i < $n; $i++) $o[] = $id; return $o; };
$deck = ['success' => true, 'leader' => 'SOR_005', 'base' => 'SOR_026', 'sideboard' => $mk(['SOR_046' => 2]),
    'mainDeck' => $mk(['SOR_095' => 10, 'SOR_046' => 10, 'SOR_101' => 10, 'SOR_102' => 10, 'SOR_103' => 10])];
$matchId = MatchCreate('SWUSim', 'premier', 'bo3', [1 => ['originalDeck' => $deck, 'authKey' => 'k1'], 2 => ['originalDeck' => $deck, 'authKey' => 'k2']]);
ob_start();
try { $g3 = (string)MatchSpawnNextGameWithDecks('SWUSim', $matchId, 1, '', [1 => $deck, 2 => $deck]); } finally { ob_end_clean(); }
$games[] = $g3; $gameName = $g3;
$frame(fn() => SWUConcedeMatch($matchId, 2));
SwuPgnTestEq(SWUGetGameWinner(), 0, 'a match concede sets no gamestate winner');
$f = $files($g3);
$c = $checkFile($f['all'], 'the conceded all.swupgn');
$checkFile($f['p1'], 'the conceded p1.swupgn');
$checkFile($f['p2'], 'the conceded p2.swupgn');
if ($c) {
    SwuPgnTestEq([$c['headers']['Result'], $c['headers']['Reason']], ['P1', 'Match conceded'], 'Result = the other seat, Reason "Match conceded"');
    SwuPgnTestCheck(str_starts_with($c['headers']['Match'] ?? '', 'sha256:') && ($c['headers']['GameNumber'] ?? null) === '1', 'a Bo3 game carries Match and GameNumber');
    SwuPgnTestCheck(isset($c['decks'][0]['sideboard']) && $c['decks'][0]['sideboard'] !== [], 'DECKS carry the sideboard when the game has one');
    SwuPgnTestCheck(!str_contains($f['all'], $matchId), 'the raw match id is not in the file');
}

// ── PII gate: a username in a free-text field refuses the files; a username that is a card name does not ──
$a = $lethal('pii');
$frame(fn() => null);
SwuPgnRecFinish($gameName, 1, 'gg zorbaxian');
SwuPgnTestCheck(!is_file(SwuPgnRecDir($gameName) . '/out/all.swupgn') && !is_file(SwuPgnRecDir($gameName) . '/out/p1.swupgn'), 'a username in Reason: no file is written');
$hl = $healthLines();
SwuPgnTestCheck(!empty(end($hl)['refused']), 'and the health line says it was refused', json_encode(end($hl)));
SwuPgnTestCheck(SwuPgnPiiScan("[Reason \"x\"]\n%%% CARDS\n{\"id\":\"SOR#95\",\"name\":\"Battlefield Marine\"}\n%%% EVENTS\n{\"seq\":\"R1.A.1\",\"t\":\"CHOICE\",\"prompt\":\"Battlefield Marine\"}\n", ['Marine']) === false,
    'a player named like a card is no PII hit');
SwuPgnTestCheck(SwuPgnPiiScan("[Reason \"gg Zorbaxian\"]\n%%% EVENTS\n", ['zorbaxian']) === true, 'the scan is case-insensitive');
SwuPgnTestCheck(SwuPgnPiiScan("[Reason \"gg ab\"]\n", ['ab']) === false, 'names under 3 characters are not scanned');

foreach ($games as $x) exec('rm -rf ' . escapeshellarg('./SWUSim/Games/' . $x));
@unlink(MatchPath('SWUSim', $matchId)); @unlink($health);
SwuPgnTestFinish();

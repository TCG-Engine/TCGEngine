<?php
// http://localhost:3400/TCGEngine/DevTools/tdd-regression/test_swusim_getgamelog.php
// The Sideboard's read-only log of the game just played.
//
// ⚠ THE SECURITY ASSERTION IS THE POINT: seat 2 must never receive a "P1"-restricted line. This is a
// FINISHED game whose gamestate is still on disk, reachable by anyone who knows the matchId.
error_reporting(E_ALL & ~E_DEPRECATED); ini_set('display_errors', 1);
set_time_limit(0);
header('Content-Type: text/plain');
$B = 'http://localhost/TCGEngine/';
$FAILS = 0;
function check($cond, $msg, $extra = null) {
    global $FAILS;
    echo ($cond ? '  ok: ' : '  BAD: ') . $msg . (($cond || $extra === null) ? '' : '  ' . json_encode($extra)) . "\n";
    if (!$cond) $FAILS++;
}
function get($url) { $c = curl_init($url); curl_setopt_array($c, [CURLOPT_RETURNTRANSFER=>1, CURLOPT_TIMEOUT=>60]); $r = curl_exec($c); curl_close($c); return (string)$r; }
function post($url, $fields) {
    $c = curl_init($url);
    curl_setopt_array($c, [CURLOPT_RETURNTRANSFER=>1, CURLOPT_TIMEOUT=>60, CURLOPT_POST=>1, CURLOPT_POSTFIELDS=>http_build_query($fields)]);
    $r = curl_exec($c); curl_close($c); return (string)$r;
}

require_once __DIR__ . '/../../Core/Match/Match.php';
require_once __DIR__ . '/../../Core/Match/MatchFlow.php';

// A REAL game, made the sanctioned way. Its gamestate is what GetGameLog will parse.
$schema = "## GIVEN\nCommonSetup: bbw/rrk/{myResources:5; theirResources:5}\nWithGamePhase: ActionPhase\nWithActivePlayer: 1\n\n## WHEN\n\n## EXPECT\nTURNPLAYER:1\n";
$setup  = json_decode(post($B . 'SWUSim/TestSchemaSetup.php', ['schema' => $schema]), true);
$gameName = strval($setup['gameName'] ?? '');
check($gameName !== '', 'created a real game to hold the log', $setup);
if ($gameName === '') { echo "\nCANNOT CONTINUE\n"; exit; }

$fixtureLog = implode('<NL>', [
    'PLAY|ALL|P1 played [[SOR_014]]',
    'DRAW|P1|You drew [[SOR_020]]',
    'DRAW|P2|You drew [[SOR_021]]',
]);
// ⚠ OVER HTTP, NOT exec(). ParseGamestate reads the MEMORY cache before the file, and APCu does not
// exist in the CLI SAPI — a CLI fixture updates Gamestate.txt and leaves the cache stale, so the
// endpoint under test reads the OLD log while the file on disk is correct.
$fx = trim(get($B . 'DevTools/tdd-regression/fixtures/swusim_write_gamelog.php?'
                . http_build_query(['gameName' => $gameName, 'log' => $fixtureLog])));
check($fx === 'OK', 'the gamestate fixture was written', $fx);

// A match whose completed game 1 IS that game.
$players = ['1' => ['originalDeck' => [], 'authKey' => 'kOne'], '2' => ['originalDeck' => [], 'authKey' => 'kTwo']];
$mid = MatchCreate('SWUSim', 'premier', 'bo3', $players, false);
MatchWriteRef('SWUSim', $gameName, $mid, 1);
MatchWithLock('SWUSim', $mid, function (&$m) use ($gameName) {
    $m['games'][] = ['gameName' => strval($gameName), 'gameNumber' => 1, 'winner' => 1];
    $m['currentGameNumber'] = 1;
});

echo "── each seat sees its own restricted lines ──\n";
$one = json_decode(get($B . 'SWUSim/GetGameLog.php?matchId=' . $mid . '&playerID=1&authKey=kOne'), true);
check(($one['gameNumber'] ?? 0) === 1, 'the endpoint names the game it returned', $one);
check(in_array('DRAW|P1|You drew [[SOR_020]]', $one['lines'] ?? [], true), 'seat 1 sees its own draw', $one);
check(!in_array('DRAW|P2|You drew [[SOR_021]]', $one['lines'] ?? [], true), '★ seat 1 does NOT see seat 2 draw', $one);
check(in_array('PLAY|ALL|P1 played [[SOR_014]]', $one['lines'] ?? [], true), 'seat 1 sees the public line', $one);

$two = json_decode(get($B . 'SWUSim/GetGameLog.php?matchId=' . $mid . '&playerID=2&authKey=kTwo'), true);
check(in_array('DRAW|P2|You drew [[SOR_021]]', $two['lines'] ?? [], true), 'seat 2 sees its own draw', $two);
check(!in_array('DRAW|P1|You drew [[SOR_020]]', $two['lines'] ?? [], true), '★ seat 2 does NOT see seat 1 draw', $two);

echo "── auth ──\n";
$bad = json_decode(get($B . 'SWUSim/GetGameLog.php?matchId=' . $mid . '&playerID=1&authKey=wrong'), true);
check(($bad['lines'] ?? null) === [], 'a wrong authKey returns nothing', $bad);
$none = json_decode(get($B . 'SWUSim/GetGameLog.php?matchId=NOPE&playerID=1&authKey=kOne'), true);
check(($none['lines'] ?? null) === [], 'an unknown match returns nothing', $none);

echo "── an UNFINISHED game is not the sideboard's business ──\n";
// A game still in progress has no winner; its log belongs to the live game page.
$mid2 = MatchCreate('SWUSim', 'premier', 'bo3', $players, false);
MatchWithLock('SWUSim', $mid2, function (&$m) use ($gameName) {
    $m['games'][] = ['gameName' => strval($gameName), 'gameNumber' => 1, 'winner' => null];
});
$live = json_decode(get($B . 'SWUSim/GetGameLog.php?matchId=' . $mid2 . '&playerID=1&authKey=kOne'), true);
check(($live['lines'] ?? null) === [], 'a match with no completed game returns nothing', $live);

echo "── READ-ONLY: the endpoint must not rewrite the gamestate ──\n";
$statePath = __DIR__ . '/../../SWUSim/Games/' . $gameName . '/Gamestate.txt';
clearstatcache(true, $statePath);
$before = filemtime($statePath);
$beforeHash = md5_file($statePath);
for ($i = 0; $i < 5; ++$i) get($B . 'SWUSim/GetGameLog.php?matchId=' . $mid . '&playerID=1&authKey=kOne');
clearstatcache(true, $statePath);
check(filemtime($statePath) === $before && md5_file($statePath) === $beforeHash,
      '★ five reads left the gamestate byte-identical');

@unlink(MatchPath('SWUSim', $mid));
@unlink(MatchPath('SWUSim', $mid2));
@unlink(MatchRefPath('SWUSim', $gameName));
echo $FAILS === 0 ? "\nALL PASS\n" : "\n$FAILS FAILED\n";

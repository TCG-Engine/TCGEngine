<?php
// Where a DRAWN game is recorded (docs/superpowers/specs/2026-10-30-swusim-draws-design.md): saved-deck stats (a draws
// count), the rated queue (a drawn match scores 0.5 each), the SWUStats submit (drawn games are skipped), the public
// recent-matches list (a draw marker) and the match-over banner. Decided results must come out exactly as before.
// Test-only account ids and deck links; every row it writes is removed at start and at the end. SWUStats is never
// contacted: SWUSTATS_HTTP_FAKE records what would have been sent.
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php -d xdebug.mode=off -d apc.enable_cli=1 SWUSim/DevTools/tests/draws_records_test.php
error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE); ini_set('display_errors', '1');
chdir(__DIR__ . '/../../..');
include_once './SWUSim/MatchFlow.php';
require_once './SWUSim/MatchHooks.php';
require_once './SWUSim/MetaPremier.php';
require_once './Database/ConnectionManager.php';
require_once './Database/functions.inc.php';

$FAILS = 0;
function check($cond, $msg) { global $FAILS; echo ($cond ? '  ok: ' : '  BAD: ') . $msg . "\n"; if (!$cond) $FAILS++; }

const DU_A = 990000011; const DU_B = 990000012;
const DL_A = 'https://swudb.com/deck/_drawtest_A_'; const DL_B = 'https://swudb.com/deck/_drawtest_B_';
$conn = GetLocalMySQLConnection();
$cleanup = function () use ($conn) {
    $ids = DU_A . ',' . DU_B;
    foreach (['favoritedeck', 'favoritedeckmatchup'] as $t) $conn->query("DELETE FROM $t WHERE usersId IN ($ids)");
    if (DBTableExists($conn, 'glicko_ratings')) {
        $conn->query("DELETE FROM glicko_ratings WHERE userId IN ($ids)");
        $conn->query("DELETE FROM glicko_penalties WHERE userId IN ($ids)");
        $conn->query("DELETE FROM glicko_results WHERE matchId LIKE 'DRAWTEST%'");
    }
};
$cleanup();
$deck = fn(int $u, string $dl) => (LoadSavedDecks($u)[0] ?? []);

// ── Saved-deck stats ────────────────────────────────────────────────────────────────────────────────────────────────
echo "saved-deck stats:\n";
AddSavedDeck(DU_A, DL_A, 'A', 'SOR_014', 'SOR_019', 'premier', null);
AddSavedDeck(DU_B, DL_B, 'B', 'SOR_014', 'SOR_019', 'premier', null);
check(RecordSavedDeckResult(DU_A, DL_A, false, true) === 1, 'a draw updates the saved deck (1 row)');
$a = $deck(DU_A, DL_A);
check(intval($a['draws'] ?? -1) === 1 && intval($a['wins']) === 0 && intval($a['losses']) === 0, 'draws 1; wins and losses untouched: ' . json_encode(array_intersect_key($a, ['wins' => 1, 'losses' => 1, 'draws' => 1])));
check(RecordSavedDeckResult(DU_A, DL_A, true) === 1 && intval($deck(DU_A, DL_A)['wins']) === 1 && intval($deck(DU_A, DL_A)['draws']) === 1, 'a win still counts as a win');
check(RecordSavedDeckMatchup(DU_A, DL_A, 'SOR_014', 'Blue 30HP', false, true) === true, 'a drawn matchup is recorded');
$mu = LoadSavedDeckMatchups(DU_A, DL_A)[0] ?? [];
check(intval($mu['draws'] ?? -1) === 1 && intval($mu['wins']) === 0 && intval($mu['losses']) === 0, 'matchup draws 1: ' . json_encode($mu));

// The match hook: a drawn game (seat 0) records a draw for BOTH seats' saved decks, and their matchups.
$m = ['players' => [
    '1' => ['userId' => DU_A, 'deckIdentity' => DL_A, 'originalDeck' => ['leader' => 'SOR_014', 'base' => 'SOR_019']],
    '2' => ['userId' => DU_B, 'deckIdentity' => DL_B, 'originalDeck' => ['leader' => 'SOR_014', 'base' => 'SOR_019']],
]];
SWURecordDeckStatsForGame($m, 0);
check(intval($deck(DU_A, DL_A)['draws']) === 2 && intval($deck(DU_B, DL_B)['draws']) === 1, 'the match hook records a draw for both seats');
check(intval($deck(DU_B, DL_B)['wins']) === 0 && intval($deck(DU_B, DL_B)['losses']) === 0, 'and no win or loss for either');

// The saved-deck stats endpoint (the deck library's Matchups panel) carries the draws — only when there are some.
$savedDecks = function (int $uid, string $dl): array {
    $_SESSION['userid'] = $uid; $_POST = ['action' => 'matchups', 'decklink' => $dl]; $GLOBALS['__SAVEDECKS_TEST'] = true;
    $de = ini_set('display_errors', '0');
    $r = require './SWUSim/SavedDecks.php';
    ini_set('display_errors', $de);
    return is_array($r) ? $r : [];
};
$sd = $savedDecks(DU_A, DL_A);
check(intval($sd['overall']['draws'] ?? -1) === 2 && array_sum(array_map(fn($x) => intval($x['draws'] ?? 0), $sd['matchups'] ?? [])) === 2,
      'the Matchups panel gets the draws (overall and per matchup): ' . json_encode($sd));
AddSavedDeck(DU_A, DL_A . 'nodraw', 'C', 'SOR_014', 'SOR_019', 'premier', null);
RecordSavedDeckResult(DU_A, DL_A . 'nodraw', true); RecordSavedDeckMatchup(DU_A, DL_A . 'nodraw', 'SOR_014', 'Blue 30HP', true);
$sd = $savedDecks(DU_A, DL_A . 'nodraw');
check(($sd['overall'] ?? null) === ['wins' => 1, 'losses' => 0] && !array_key_exists('draws', $sd['matchups'][0] ?? ['draws' => 1]), 'NEGATIVE: a deck with no draws answers exactly as before');

require_once './SharedUI/Render/DeckLibrary.php';
$lib = RenderDeckLibrary(DU_A, []);
check(preg_match('/data-id="' . preg_quote(DL_A, '/') . '"[^>]*data-draws="2"/', $lib) === 1, 'the deck library option carries data-draws for a deck with draws');
check(preg_match('/data-id="' . preg_quote(DL_A . 'nodraw', '/') . '"[^>]*data-draws=/', $lib) === 0, 'NEGATIVE: and none for a deck without');

// A server without migration 22: the draw is not recorded and nothing fails.
$GLOBALS['DB_COLUMN_EXISTS_FAKE'] = fn($t, $c) => false;
check(RecordSavedDeckResult(DU_B, DL_B, false, true) === 0 && RecordSavedDeckMatchup(DU_B, DL_B, 'SOR_014', 'Blue 30HP', false, true) === false,
      'without the draws column a draw is skipped quietly');
check(RecordSavedDeckResult(DU_B, DL_B, true) === 1, 'and a win is still recorded');
unset($GLOBALS['DB_COLUMN_EXISTS_FAKE']);

// ── Rated queue ──────────────────────────────────────────────────────────────────────────────────────────────────────
echo "rated queue:\n";
$rated = ['matchId' => 'DRAWTEST1', 'createdAt' => 1700000000, 'format' => 'metapremier', 'queueType' => 'bo1', 'state' => 'complete',
          'winner' => 0, 'matchDraw' => true,
          'players' => ['1' => ['userId' => DU_A], '2' => ['userId' => DU_B]],
          'games' => [['gameName' => 'x', 'winner' => 0, 'draw' => true, 'detail' => ['pregameDone' => true, 'turns' => 5]]]];
$c = SWUMetaPremierClassify($rated);
check(is_array($c) && ($c['outcome'] ?? '') === 'draw', 'a drawn rated match classifies as a draw: ' . json_encode($c));
if (is_array($c) && SWUMetaPremierTablesReady($conn)) {
    // Uneven starting ratings, so the draw visibly moves them toward each other.
    foreach ([[DU_A, 1700.0], [DU_B, 1500.0]] as [$u, $r]) {
        $conn->query("INSERT INTO glicko_ratings (userId, format, queueType, season, rating, rd, volatility, updatedAt) VALUES ($u, 'metapremier', 'bo1', " . MP_SEASON . ", $r, 80, 0.06, 1)");
    }
    $res = SWUMetaPremierApply($conn, $c, 1800000000);
    $ra = SWUMetaPremierGetRating($conn, DU_A, 'bo1'); $rb = SWUMetaPremierGetRating($conn, DU_B, 'bo1');
    check($res === 'rated', 'the draw is rated: ' . $res);
    check(floatval($ra['rating']) < 1700.0 && floatval($rb['rating']) > 1500.0, 'the higher-rated player loses rating, the lower gains: ' . $ra['rating'] . ' / ' . $rb['rating']);
    check(intval($ra['draws'] ?? -1) === 1 && intval($rb['draws'] ?? -1) === 1 && intval($ra['wins']) === 0 && intval($ra['losses']) === 0 && intval($rb['wins']) === 0,
          'draws 1 each, no win or loss');
    check(intval($ra['games']) === 1 && intval($ra['abandons']) === 0, 'it counts as a game; no abandon');
    $row = $conn->query("SELECT outcome, penaltyApplied FROM glicko_results WHERE matchId = 'DRAWTEST1'")->fetch_assoc();
    check(($row['outcome'] ?? '') === 'draw' && floatval($row['penaltyApplied']) === 0.0, 'the results log says draw, no penalty');
    $pen = $conn->query("SELECT abandonStrikes FROM glicko_penalties WHERE userId = " . DU_B)->fetch_assoc();
    check(intval($pen['abandonStrikes'] ?? 0) === 0, 'no strike');
    $GLOBALS['DB_COLUMN_EXISTS_FAKE'] = fn($t, $col) => false;
    $c2 = $c; $c2['matchId'] = 'DRAWTEST2';
    check(SWUMetaPremierApply($conn, $c2, 1800000100) === 'skipped', 'without the draws column a drawn match is skipped, not mis-rated');
    unset($GLOBALS['DB_COLUMN_EXISTS_FAKE']);
} else {
    check(false, 'a classified draw and the metapremier tables (migration 17)');
}
$won = $rated; unset($won['matchDraw']); $won['winner'] = 1; $won['games'][0]['winner'] = 1; unset($won['games'][0]['draw']);
check((SWUMetaPremierClassify($won)['outcome'] ?? '') === 'win', 'NEGATIVE: a won rated match still classifies as a win');

// ── SWUStats submit: drawn games are skipped ─────────────────────────────────────────────────────────────────────────
echo "swustats submit:\n";
$sent = [];
$GLOBALS['SWUSTATS_HTTP_FAKE'] = function ($method, $url, $body) use (&$sent) { $sent[] = json_decode((string)$body, true); return ['status' => 200, 'body' => ['success' => true], 'raw' => '{}']; };
$mk = function (array $results) {
    $p = ['originalDeck' => ['leader' => 'SOR_014', 'base' => 'SOR_019', 'cards' => []], 'authKey' => 'k'];
    $mid = MatchCreate('SWUSim', 'premier', count($results) > 1 ? 'bo3' : 'bo1', [1 => $p, 2 => $p], true);
    foreach ($results as $i => $r) {
        $g = "drawsub{$i}_{$mid}_" . getmypid();
        MatchWithLock('SWUSim', $mid, function (&$m) use ($g) { $m['games'][] = ['gameName' => $g, 'gameNumber' => count($m['games']) + 1, 'winner' => null]; });
        if ($r === 'D') MatchRecordGameDraw('SWUSim', $mid, $g, 5); else MatchRecordGameResult('SWUSim', $mid, $g, $r, 5, [$r]);
        MatchWithLock('SWUSim', $mid, function (&$m) use ($g) { foreach ($m['games'] as &$x) if ($x['gameName'] === $g) $x['detail'] = ['turns' => 5]; });
    }
    return $mid;
};
$mids = [];
$sent = []; $mids[] = $mid = $mk(['D']);
SWUSubmitMatchResults($mid);
check(count($sent) === 0 && (SWUReadMatch($mid)['statsStatus'] ?? '') === 'skipped_draw', 'a drawn Bo1 sends nothing; statsStatus skipped_draw: ' . (SWUReadMatch($mid)['statsStatus'] ?? ''));
$sent = []; $mids[] = $mid = $mk([1, 'D', 2]);
SWUSubmitMatchResults($mid);
check(count($sent) === 2 && (SWUReadMatch($mid)['statsStatus'] ?? '') === 'success', 'a Bo3 W-D-L sends its two decided games only; status success: ' . count($sent) . ' / ' . (SWUReadMatch($mid)['statsStatus'] ?? ''));
unset($GLOBALS['SWUSTATS_HTTP_FAKE']);

// ── Recent matches + the banner ──────────────────────────────────────────────────────────────────────────────────────
echo "recent matches + banner:\n";
$recent = function () {
    $_GET = ['rootName' => 'SWUSim', 'limit' => '10'];
    $de = ini_set('display_errors', '0');   // its header() call warns under the CLI; keep that out of the JSON
    ob_start(); include './APIs/Lobbies/GetRecentMatches.php'; $out = json_decode(ob_get_clean(), true);
    ini_set('display_errors', $de);
    return $out['data'] ?? [];
};
$pub = function (array $results) use ($mk) { $mid = $mk($results); MatchWithLock('SWUSim', $mid, function (&$m) { $m['isPrivate'] = false; }); return $mid; };
$mids[] = $dm = $pub(['D']); $mids[] = $wm = $pub([1]);
$byId = [];
foreach ($recent() as $row) $byId[$row['matchId']] = $row;
check(($byId[$dm]['draw'] ?? null) === true, 'a drawn match is marked draw: true');
check(isset($byId[$wm]) && !array_key_exists('draw', $byId[$wm]), 'NEGATIVE: a won match has no draw key');
// The flash rides the gamestate's Flash zone (generated SetFlashMessage); read it back the same way.
SWUFlashMatchResult('g', 0, ['players' => ['1' => [], '2' => []], 'wins' => ['1' => 1, '2' => 1]]);
check(strval(GetFlashMessage()) === 'MATCHOVER:The match is a draw 1-1!', 'the banner: ' . strval(GetFlashMessage()));
SWUFlashMatchResult('g', 2, ['players' => ['1' => [], '2' => []], 'wins' => ['1' => 0, '2' => 1]]);
check(strval(GetFlashMessage()) === 'MATCHOVER:Player 2 wins the match 0-1!', 'NEGATIVE: a won match keeps its banner');
// Team Suns: both teams wiped out together — a draw has no winners, so the banner must not name "Player 0".
$four = ['1' => [], '2' => [], '3' => [], '4' => []];
SWUFlashMatchResult('g', 0, ['players' => $four, 'winners' => []]);
check(strval(GetFlashMessage()) === 'MATCHOVER:The game is a draw!', 'the Team Suns draw banner: ' . strval(GetFlashMessage()));
SWUFlashMatchResult('g', 1, ['players' => $four, 'winners' => [1, 3]]);
check(strval(GetFlashMessage()) === 'MATCHOVER:Player 1, Player 3 win the game!', 'NEGATIVE: a Team Suns win keeps its banner');

foreach ($mids as $x) exec('rm -rf ' . escapeshellarg(MatchesDir('SWUSim') . '/' . $x));
$cleanup();
echo $FAILS === 0 ? "PASS\n" : "$FAILS FAILED\n";
exit($FAILS === 0 ? 0 : 1);

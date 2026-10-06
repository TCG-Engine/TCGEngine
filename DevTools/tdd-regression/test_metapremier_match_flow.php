<?php
// http://localhost:3400/TCGEngine/DevTools/tdd-regression/test_metapremier_match_flow.php
// Meta Premier through the Match layer — docs/superpowers/specs/2026-10-03-swusim-metapremier-ratings-design.md §4.1, §4.5.
// Matches are built directly with MatchCreate (no decks needed). Test-only account ids; every rating row they produce is
// removed at start and in finally.
header('Content-Type: text/plain');
include __DIR__ . '/../../SWUSim/MatchFlow.php';
require_once __DIR__ . '/../../Database/ConnectionManager.php';
require_once __DIR__ . '/../../Database/functions.inc.php';
require_once __DIR__ . '/../../SWUSim/MetaPremier.php';

$conn = GetLocalMySQLConnection();
if (!SWUMetaPremierTablesReady($conn)) { echo "SKIP: run Database/migrations/17_glicko_ratings.sql first.\n"; exit; }
$U1 = 990000011; $U2 = 990000012;
$clean = function () use ($conn, $U1, $U2) {
    foreach (['glicko_ratings', 'glicko_penalties'] as $t) $conn->query("DELETE FROM $t WHERE userId IN ($U1,$U2)");
    $conn->query("DELETE FROM glicko_results WHERE winnerUserId IN ($U1,$U2) OR loserUserId IN ($U1,$U2)");
};
$mk = fn($format, $qt) => MatchCreate('SWUSim', $format, $qt, [1 => ['userId' => $U1], 2 => ['userId' => $U2]]);
$games = fn($u, $qt = 'bo3') => intval((SWUMetaPremierGetRating($conn, $u, $qt) ?? ['games' => 0])['games']);
$checks = [];
$clean();
try {
    // Whole-match concede during game 2 → rated as a concede (the path that never reaches submitResults).
    $mid = $mk('metapremier', 'bo3');
    SWURecordGameResult($mid, 'mpg1', 2);
    MatchWithLock('SWUSim', $mid, function (&$m) { $m['games'][] = ['gameName' => 'mpg2', 'gameNumber' => 2, 'winner' => null]; });
    MatchConcede('SWUSim', $mid, 1, ['pregameDone' => true]);
    $checks['concede rated'] = $games($U2) === 1 && $games($U1) === 1;
    $after = SWUReadMatch($mid);
    $checks['concededBy stamped'] = intval($after['concededBy'] ?? 0) === 1;
    $checks['open game endReason concede'] = ($after['games'][1]['detail']['endReason'] ?? '') === 'concede';
    $checks['result logged as concede'] = $conn->query("SELECT outcome FROM glicko_results WHERE matchId='" . $conn->real_escape_string($mid)
        . "' AND matchCreatedAt=" . intval($after['createdAt']))->fetch_assoc()['outcome'] === 'concede';
    MatchHook('SWUSim', 'rateMatch', $mid);                     // fired a second time
    $checks['rateMatch twice is a no-op'] = $games($U2) === 1;

    // Game 1 ended by base kill through the normal end-of-match path (Bo1): rated via the after-action branch's hook.
    $mid1 = $mk('metapremier', 'bo1');
    SWURecordGameResult($mid1, 'mpg1b', 1);
    MatchWithLock('SWUSim', $mid1, function (&$m) { $m['games'][0]['detail'] = ['pregameDone' => true, 'endReason' => 'win']; });
    MatchHook('SWUSim', 'rateMatch', $mid1);
    $checks['bo1 rated on its own ladder'] = $games($U1, 'bo1') === 1 && $games($U1) === 1;

    // Final review #2: the block-opponent forfeit (SWUSim/BlockedUsers.php → SWUConcedeMatch) runs with NO gamestate
    // loaded, exactly like this test. Forfeiting game 1 that way must be rated, not a free exit.
    $midB = $mk('metapremier', 'bo3');
    MatchWithLock('SWUSim', $midB, function (&$m) { $m['games'][] = ['gameName' => 'mpgB1', 'gameNumber' => 1, 'winner' => null]; });
    $before = $games($U1);
    SWUConcedeMatch($midB, 1);
    $checks['block-forfeit in game 1 is rated'] = $games($U1) === $before + 1;

    // Unrated format → nothing.
    $mid2 = $mk('premier', 'bo3');
    $before2 = $games($U2);
    MatchConcede('SWUSim', $mid2, 1, ['pregameDone' => true]);
    $checks['premier never rated'] = $games($U2) === $before2;

    // Rematch / Quick Rematch / convert refused for rated; still allowed for premier.
    $mid3 = $mk('metapremier', 'bo3');
    MatchConcede('SWUSim', $mid3, 2, ['pregameDone' => true]);
    MatchRequestRematch('SWUSim', $mid3, 1, 3, true); MatchRequestRematch('SWUSim', $mid3, 2, 3, true);
    $checks['rated rematch refused'] = MatchAcceptRematch('SWUSim', $mid3) === null && empty(SWUReadMatch($mid3)['rematchRequests']);
    $mid4 = $mk('metapremier', 'bo1');
    SWURecordGameResult($mid4, 'mpg4', 1);
    MatchRequestConvertToBo3('SWUSim', $mid4, 1); MatchRequestConvertToBo3('SWUSim', $mid4, 2);
    $checks['rated convert refused'] = MatchAcceptConvertToBo3('SWUSim', $mid4) === null && intval(SWUReadMatch($mid4)['bestOf']) === 1;
    $mid5 = $mk('premier', 'bo1');
    SWURecordGameResult($mid5, 'mpg5', 1);
    MatchRequestConvertToBo3('SWUSim', $mid5, 1); MatchRequestConvertToBo3('SWUSim', $mid5, 2);
    $checks['premier convert still works'] = MatchAcceptConvertToBo3('SWUSim', $mid5) === $mid5;
    $mid6 = $mk('premier', 'bo1');
    SWURecordGameResult($mid6, 'mpg6', 1);
    MatchRequestRematch('SWUSim', $mid6, 1, 1, false);
    $checks['premier rematch request still recorded'] = !empty(SWUReadMatch($mid6)['rematchRequests']['1']);
} catch (Throwable $e) {
    $checks['threw: ' . $e->getMessage()] = false;
} finally { $clean(); }
$fails = array_keys(array_filter($checks, fn($v) => $v !== true));
echo empty($fails) ? 'PASS (' . count($checks) . " checks)\n" : 'FAIL: ' . implode(', ', $fails) . "\n";

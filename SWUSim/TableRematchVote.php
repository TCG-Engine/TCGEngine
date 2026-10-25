<?php // SWUSim/TableRematchVote.php — a seat's Quick Rematch / Rematch vote after a finished 3-4 seat (Twin Suns) game.
// POST gameName, playerID, authKey, choice = quick | room | '' (the same choice again withdraws it).
// Answers with the seat's view of the vote (TableRematchView). Fires the rematch when the vote completes — exactly
// once, under the match lock (SWUTableRematchFireIfDue). docs/superpowers/specs/2026-10-20-twinsuns-rematch-design.md
header('Content-Type: application/json');
chdir(__DIR__ . '/..');
include_once './SWUSim/MatchFlow.php';
require_once './SWUSim/TableRematch.php';

$gameName = preg_replace('/[^A-Za-z0-9_]/', '', strval($_POST['gameName'] ?? ''));
$seat     = intval($_POST['playerID'] ?? 0);
$authKey  = strval($_POST['authKey'] ?? '');
$choice   = strval($_POST['choice'] ?? '');
$fail = function ($msg) { echo json_encode(['success' => false, 'message' => $msg]); exit; };

$ref = $gameName !== '' ? SWUReadMatchRef($gameName) : null;
$m = is_array($ref) ? SWUReadMatch($ref['matchId']) : null;
if (!is_array($m)) $fail('No such match.');
if (count($m['players'] ?? []) <= 2) $fail('Table rematch is for 3-4 seat games.');
$expected = strval($m['players'][strval($seat)]['authKey'] ?? '');
if ($expected === '' || !hash_equals($expected, $authKey)) $fail('Authentication failed.');

$matchId = strval($ref['matchId']);
$now = time();
SWUTableRematchStampSeen($matchId, $seat, $now);
$err = null;
MatchWithLock('SWUSim', $matchId, function (&$mm) use ($seat, $choice, $now, &$err) {
    if (($mm['state'] ?? '') !== 'complete') { $err = 'The game is not over yet.'; return; }
    $err = TableRematchSetChoice($mm, $seat, $choice, $now);
});
SWUTableRematchFireIfDue($matchId, $gameName);
$m = SWUReadMatch($matchId);
echo json_encode(['success' => $err === null, 'message' => strval($err ?? ''),
    'tableRematch' => TableRematchView($m, SWUTableRematchSeen($matchId), time(), $seat)]);

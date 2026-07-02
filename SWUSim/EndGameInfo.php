<?php // SWUSim/EndGameInfo.php — match context + stats for the end-game menu
header('Content-Type: application/json');
include_once __DIR__ . '/MatchFlow.php';

$gameName = preg_replace('/[^A-Za-z0-9_]/','', $_GET['gameName'] ?? '');
$seatStr = strval($_GET['playerID'] ?? '');
$authKey = strval($_GET['authKey'] ?? '');
$isSpectator = ($seatStr === 'S');
$seat = intval($seatStr);

$ref = SWUReadMatchRef($gameName);
if ($ref === null) { echo json_encode(['isMatch'=>false]); exit; }
$m = SWUReadMatch($ref['matchId']);
if (!is_array($m)) { echo json_encode(['isMatch'=>false]); exit; }

// Auth (seated players only; spectators get a read-only view with no action buttons).
if (!$isSpectator) {
    if ($seat !== 1 && $seat !== 2) { echo json_encode(['error'=>'bad seat','isMatch'=>false]); exit; }
    $expected = strval($m['players'][strval($seat)]['authKey'] ?? '');
    if ($expected === '' || !hash_equals($expected, $authKey)) { echo json_encode(['error'=>'auth','isMatch'=>false]); exit; }
}

// Find this game's record.
$gameRec = null;
foreach ($m['games'] as $g) { if (($g['gameName'] ?? '') === $gameName) { $gameRec = $g; break; } }
if ($gameRec === null) $gameRec = ['gameNumber'=>0,'winner'=>null,'detail'=>[]];

$gameWinner = intval($gameRec['winner'] ?? 0);
$bestOf = intval($m['bestOf'] ?? 1);
$state = strval($m['state'] ?? '');
$seriesOver = ($state === 'complete');

// Per-seat convert-to-Bo3 requests, so the menu can show a two-step mutual confirmation
// ("Convert" → "Waiting on opponent" for the initiator; "Confirm Convert" for the other).
$convReq = is_array($m['convertRequests'] ?? null) ? $m['convertRequests'] : [];
$oppSeat = ($seat === 1) ? 2 : 1;

// Full-rematch (10016): SWUAcceptRematch writes a Sideboard.json on THIS (completed) game pointing to a
// NEW sideboarding match. Follow that pointer so the menu shows a "Go to Next Game" → the new match's
// sideboard (otherwise the player is stranded on the completed-match menu). A pointer to the SAME match
// is the normal Bo3 sideboarding case, already handled by the bestOf===3 branch — leave it alone.
$navMatchId = $isSpectator ? null : $ref['matchId'];
$sideboardPending = false;
$sidePtr = __DIR__ . '/Games/' . $gameName . '/Sideboard.json';
if (is_file($sidePtr)) {
    $sp = json_decode(file_get_contents($sidePtr), true);
    $sideMatchId = is_array($sp) ? strval($sp['matchId'] ?? '') : '';
    if ($sideMatchId !== '' && $sideMatchId !== strval($ref['matchId'])) {
        $sm = SWUReadMatch($sideMatchId);
        if (is_array($sm) && ($sm['state'] ?? '') === 'sideboarding') {
            $sideboardPending = true;
            if (!$isSpectator) $navMatchId = $sideMatchId;
        }
    }
}

echo json_encode([
    'isMatch'     => true,
    'matchId'     => $navMatchId, // for the "Go to Next Game" → Sideboard.php nav (rematch: the NEW match)
    'sideboardPending' => $sideboardPending,
    'didWin'      => $isSpectator ? null : ($gameWinner === $seat),
    'bestOf'      => $bestOf,
    'matchState'  => $state,
    'gameNumber'  => intval($gameRec['gameNumber'] ?? 0),
    'wins'        => ['1'=>intval($m['wins']['1'] ?? 0), '2'=>intval($m['wins']['2'] ?? 0)],
    'matchWinner' => intval($m['winner'] ?? 0),
    'convertible' => ($bestOf === 1 && $seriesOver),
    'convertRequestedByMe'  => $isSpectator ? false : !empty($convReq[strval($seat)]),
    'convertRequestedByOpp' => $isSpectator ? false : !empty($convReq[strval($oppSeat)]),
    'seriesOver'  => $seriesOver,
    'isSpectator' => $isSpectator,
    'statsStatus' => strval($m['statsStatus'] ?? ''), // '', 'success', 'skipped_early', or 'failed'
    'statsHtml'   => SWUBuildStatsHtml($m, $gameRec, $isSpectator ? null : $seat),
]);

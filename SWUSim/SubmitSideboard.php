<?php // SWUSim/SubmitSideboard.php
header('Content-Type: application/json');
include_once __DIR__ . '/MatchFlow.php';
include_once __DIR__ . '/../Core/GameAuth.php';

$matchId  = isset($_POST['matchId']) ? preg_replace('/[^A-Za-z0-9_]/','',$_POST['matchId']) : '';
$seat     = intval($_POST['playerID'] ?? 0);
$authKey  = strval($_POST['authKey'] ?? '');
$deckText = strval($_POST['deck'] ?? '');

$m = SWUReadMatch($matchId);
if (!is_array($m)) { echo json_encode(['success'=>false,'message'=>'Unknown match.']); exit; }
if ($seat !== 1 && $seat !== 2) { echo json_encode(['success'=>false,'message'=>'Bad seat.']); exit; }
// Auth: the match stores each seat's authKey.
$expected = strval($m['players'][strval($seat)]['authKey'] ?? '');
if ($expected === '' || !hash_equals($expected, $authKey)) { echo json_encode(['success'=>false,'message'=>'Auth failed.']); exit; }
// Sideboard-timeout heartbeat: both players poll THIS endpoint (not GetNextTurn) while sideboarding,
// so the no-submit countdown must be checked here too — otherwise a waiting (already-submitted) player
// hangs forever when the opponent never submits (GetNextTurn's check only fires for a player still on
// the game page). No-op until the deadline passes; may advance the match, so re-read below.
SWUSideboardTimeoutCheck($matchId);
$m = SWUReadMatch($matchId);
if (!is_array($m)) { echo json_encode(['success'=>false,'message'=>'Unknown match.']); exit; }
// The match ended between games (the end screen's "Forfeit Best of 3", a block): there is no next game. Both the waiting
// poll and a late submit are sent back to the last game's board, whose end screen shows the result and the way out.
if (($m['state'] ?? '') === 'complete' && !empty($m['games'])) {
  $last = strval($m['games'][count($m['games']) - 1]['gameName'] ?? '');
  echo json_encode(['success'=>false,'message'=>'The match is over.','matchOver'=>true,'gameName'=>$last]); exit;
}
// statusOnly (additive, High Ground): the page asks whether the host ended the match; nothing is submitted.
if (strval($_POST['statusOnly'] ?? '') === '1') {
  $ended = ($m['state'] ?? '') === 'abandoned' && strval($m['roomId'] ?? '') !== '';
  echo json_encode($ended ? ['success'=>true,'roomLobbyID'=>strval($m['roomId'])] : ['success'=>true]);
  exit;
}
if (($m['state'] ?? '') !== 'sideboarding') {
  // The opponent's submit already spawned the next game and advanced the match. A client
  // still polling this round must be told where to go instead of hanging on an error.
  if (($m['state'] ?? '') === 'in_progress' && !empty($m['games'])) {
    $latest = $m['games'][count($m['games']) - 1]['gameName'] ?? '';
    if ($latest !== '') { echo json_encode(['success'=>true,'message'=>'','bothReady'=>true,'nextGameName'=>strval($latest)]); exit; }
  }
  echo json_encode(['success'=>false,'message'=>'Not in sideboarding.']); exit;
}

// The two-step sideboard (owner, 2026-11-03; additive — no `action` is the one-click submit-and-ready below):
//   save    validate and save (or replace) this seat's list; not ready
//   ready   ready for the next game with the saved list, or the deck from the last game if none was saved
//   cancel  back to editing: not ready, nothing saved — until both seats are ready and the next game starts
//   status  the waiting poll: both seats' state, and the next game once it has started
// Every answer carries mine {submitted, ready} and opponentReady; nextGameName once the next game exists.
$action = strval($_POST['action'] ?? '');
if ($action !== '') {
  $answer = function (bool $ok, string $message = '', ?string $next = null) use ($matchId, $seat) {
    $mm = SWUReadMatch($matchId);
    if (!$next && is_array($mm) && ($mm['state'] ?? '') === 'in_progress' && !empty($mm['games'])) $next = strval($mm['games'][count($mm['games']) - 1]['gameName'] ?? '');
    $sb = is_array($mm) && is_array($mm['sideboard'] ?? null) ? $mm['sideboard'] : [];
    $mine = $sb[strval($seat)] ?? [];
    echo json_encode(['success' => $ok, 'message' => $message,
      'mine' => ['submitted' => ($mine['deck'] ?? null) !== null, 'ready' => !empty($mine['ready'])],
      'opponentReady' => !empty($sb[strval(3 - $seat)]['ready']),
      'nextGameName' => $next ?: null]);
    exit;
  };
  if ($action === 'save') {
    $resolved = SWUResolveDeckInput($deckText);
    if (empty($resolved['success'])) $answer(false, $resolved['message'] ?? 'Bad deck.');
    $errs = SWUCheckFormat($m['format'], $resolved['leader'], $resolved['base'], $resolved['mainDeck'], $resolved['sideboard']);
    if (!empty($errs)) $answer(false, implode('; ', array_slice($errs, 0, 3)));
    if (!SWUSaveSideboardDeck($matchId, $seat, $resolved)) $answer(false, 'You are ready — cancel to change your sideboard.');
    $answer(true);
  }
  if ($action === 'ready') {
    $p = $m['players'][strval($seat)] ?? [];
    SWUReadySideboardSeat($matchId, $seat, $p['currentDeck'] ?? $p['originalDeck'] ?? null);
    $answer(true, '', SWUMaybeSpawnAfterSideboard($matchId) ?: null);
  }
  if ($action === 'cancel') {
    if (!SWUCancelSideboardSeat($matchId, $seat)) $answer(false, 'The next game is already starting.');
    $answer(true);
  }
  if ($action === 'status') $answer(true, '', SWUMaybeSpawnAfterSideboard($matchId) ?: null);
  // forfeit: "Return to Lobby" / "Return to Main Menu" on the sideboard screen — leaving mid-series forfeits the Bo3, as on
  // the end screen. Answers the last game (whose end screen has the room's Return to Lobby) and, for a High Ground hill
  // match, the room to go back to: this seat is authenticated, so its own room id may go to it.
  if ($action === 'forfeit') {
    SWUConcedeMatch($matchId, $seat);
    $mm = SWUReadMatch($matchId);
    $roomId = strval($mm['roomId'] ?? '');
    $hg = !empty($mm['highGround']) && $roomId !== '';
    echo json_encode(['success' => ($mm['state'] ?? '') === 'complete',
      'gameName' => strval($mm['games'][count($mm['games']) - 1]['gameName'] ?? ''),
      'highGround' => $hg, 'roomId' => $roomId !== '' ? $roomId : null]);
    exit;
  }
  $answer(false, 'Unknown action.');
}

$resolved = SWUResolveDeckInput($deckText);
if (empty($resolved['success'])) { echo json_encode(['success'=>false,'message'=>$resolved['message'] ?? 'Bad deck.']); exit; }
$errs = SWUCheckFormat($m['format'], $resolved['leader'], $resolved['base'], $resolved['mainDeck'], $resolved['sideboard']);
if (!empty($errs)) { echo json_encode(['success'=>false,'message'=>implode('; ', array_slice($errs,0,3))]); exit; }

SWUSubmitSideboardDeck($matchId, $seat, $resolved);
$next = SWUMaybeSpawnAfterSideboard($matchId); // spawns + clears sideboard state when both are in
$m = SWUReadMatch($matchId);
// "bothReady" is true once the match is advancing — either it just spawned the next game,
// or both decks are in (a no-op spawn race). Read after spawn, so derive from $next too.
$bothReady = (!empty($next)) || (is_array($m) && SWUSideboardBothReady($m));
echo json_encode([
  'success' => true,
  'message' => '',
  'bothReady' => $bothReady,
  'nextGameName' => $next ?: null,
]);

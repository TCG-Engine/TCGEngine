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
if (($m['state'] ?? '') !== 'sideboarding') {
  // The opponent's submit already spawned the next game and advanced the match. A client
  // still polling this round must be told where to go instead of hanging on an error.
  if (($m['state'] ?? '') === 'in_progress' && !empty($m['games'])) {
    $latest = $m['games'][count($m['games']) - 1]['gameName'] ?? '';
    if ($latest !== '') { echo json_encode(['success'=>true,'message'=>'','bothReady'=>true,'nextGameName'=>strval($latest)]); exit; }
  }
  echo json_encode(['success'=>false,'message'=>'Not in sideboarding.']); exit;
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

<?php
// Toggle (or explicitly set) the caller's Ready flag in a lobby.
//
// Ready is separate from deckOk: deckOk says a deck is LEGAL, Ready says its owner is happy to play
// it against what they can now see in the roster. The host's Start is gated on everyone being ready,
// which is what makes the waiting room a negotiation rather than a countdown.
require_once "../../Core/NetworkingLibraries.php";
require_once "../../Core/HTTPLibraries.php";
require_once "./Classes/Player.php";
require_once "./Classes/LobbyStore.php";

$response = new stdClass();
function _setReadyFail($response, $m) {
  $response->success = false;
  $response->message = $m;
  header('Content-Type: application/json');
  echo json_encode($response);
  exit;
}

$lobbyID = $_POST['lobbyID'] ?? '';
$authKey = $_POST['authKey'] ?? '';
// Omit `ready` to TOGGLE. The client sends the value it wants so a double-click cannot desync the
// button from the seat.
$want    = array_key_exists('ready', $_POST) ? ($_POST['ready'] === '1' || $_POST['ready'] === 'true') : null;

$err = null; $me = null;
$lobby = LobbyMutate($lobbyID, function ($lobby) use ($authKey, $want, &$err, &$me) {
  if (!empty($lobby->gameName) || ($lobby->state ?? '') === 'started') { $err = 'Game already started.'; return false; }
  foreach (($lobby->players ?? []) as $p) {
    if (($p instanceof Player) && hash_equals(strval($p->getAuthKey()), strval($authKey))) { $me = $p; break; }
  }
  if (!$me) { $err = 'Authentication failed.'; return false; }
  // You cannot declare yourself ready with a deck the server rejected.
  if (($want === true || ($want === null && !$me->getReady())) && !$me->getDeckOk()) {
    $err = 'Your deck is missing or invalid.'; $me = null; return false;
  }
  $me->setReady($want === null ? !$me->getReady() : $want);
  return true;
});
if ($lobby === null && $err === null) _setReadyFail($response, 'Room not found or busy — try again.');
if ($err !== null) _setReadyFail($response, $err);

$response->success = true;
$response->ready   = $me->getReady();
header('Content-Type: application/json');
echo json_encode($response);

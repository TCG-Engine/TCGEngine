<?php
// Toggle (or explicitly set) the caller's Ready flag in a lobby.
//
// Ready is separate from deckOk: deckOk says a deck is LEGAL, Ready says its owner is happy to play
// it against what they can now see in the roster. The host's Start is gated on everyone being ready,
// which is what makes the waiting room a negotiation rather than a countdown.
require_once "../../Core/NetworkingLibraries.php";
require_once "../../Core/HTTPLibraries.php";
require_once "./Classes/Player.php";

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

$lobby = $lobbyID ? apcu_fetch($lobbyID) : null;
if (!$lobby) _setReadyFail($response, 'Room not found.');
if (!empty($lobby->gameName) || ($lobby->state ?? '') === 'started') _setReadyFail($response, 'Game already started.');

$me = null;
foreach (($lobby->players ?? []) as $p) {
  if (($p instanceof Player) && hash_equals(strval($p->getAuthKey()), strval($authKey))) { $me = $p; break; }
}
if (!$me) _setReadyFail($response, 'Authentication failed.');

// You cannot declare yourself ready with a deck the server rejected.
if (($want === true || ($want === null && !$me->getReady())) && !$me->getDeckOk()) {
  _setReadyFail($response, 'Your deck is missing or invalid.');
}

$me->setReady($want === null ? !$me->getReady() : $want);
apcu_store($lobbyID, $lobby, 900);

$response->success = true;
$response->ready   = $me->getReady();
header('Content-Type: application/json');
echo json_encode($response);

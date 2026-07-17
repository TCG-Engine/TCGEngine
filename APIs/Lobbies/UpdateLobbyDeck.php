<?php
require_once "../../Core/NetworkingLibraries.php";
require_once "../../Core/HTTPLibraries.php";
require_once "./Classes/Player.php";
$swuDeckImportPath = __DIR__ . '/../../SWUSim/Custom/DeckImport.php';
if (is_file($swuDeckImportPath)) require_once $swuDeckImportPath;

$response = new stdClass();
function _updateDeckFail($response, $m) {
  $response->success = false;
  $response->message = $m;
  header('Content-Type: application/json');
  echo json_encode($response);
  exit;
}

$lobbyID  = $_POST['lobbyID'] ?? '';
$playerID = intval($_POST['playerID'] ?? 0);
$authKey  = $_POST['authKey'] ?? '';
$deckLink = $_POST['deckLink'] ?? '';

$lobby = $lobbyID ? apcu_fetch($lobbyID) : null;
if (!$lobby) _updateDeckFail($response, 'Room not found.');
if (!empty($lobby->gameName) || ($lobby->state ?? '') === 'started') _updateDeckFail($response, 'Game already started.');

$me = null;
foreach (($lobby->players ?? []) as $p) {
  if (($p instanceof Player) && $p->getPlayerID() == $playerID && $p->getAuthKey() === $authKey) { $me = $p; break; }
}
if (!$me) _updateDeckFail($response, 'Authentication failed.');

if (!function_exists('SWUResolveDeckInput') || !function_exists('SWUCheckFormat')) {
  _updateDeckFail($response, 'Deck validation is temporarily unavailable.');
}

$resolved = SWUResolveDeckInput($deckLink);
if (empty($resolved['success'])) {
  $me->setDeckOk(false);
  apcu_store($lobbyID, $lobby, 900);
  $response->success = true;
  $response->deckOk = false;
  $response->message = $resolved['message'] ?? 'Could not read deck.';
  header('Content-Type: application/json');
  echo json_encode($response);
  exit;
}
$errs = SWUCheckFormat('twinsuns', $resolved['leader'] ?? '', $resolved['base'] ?? '', $resolved['mainDeck'] ?? [], $resolved['sideboard'] ?? []);
if (!empty($errs)) {
  $me->setDeckOk(false);
  apcu_store($lobbyID, $lobby, 900);
  $response->success = true;
  $response->deckOk = false;
  $response->message = implode('; ', array_slice($errs, 0, 3));
  header('Content-Type: application/json');
  echo json_encode($response);
  exit;
}

$me->setDeckLink($deckLink);
$me->setDeckOk(true);
apcu_store($lobbyID, $lobby, 900);
$response->success = true;
$response->deckOk = true;
$response->message = '';
header('Content-Type: application/json');
echo json_encode($response);

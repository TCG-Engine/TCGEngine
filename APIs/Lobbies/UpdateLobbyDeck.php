<?php
// Change the deck on your seat in a lobby, before the match starts.
//
// This endpoint no longer knows anything about SWU: the sim's LobbyAdapter owns deck resolution,
// format legality, and the identity cards the roster draws. A sim without a `waitingRoom` block in
// its SiteDef has no adapter and simply cannot reach this flow.
require_once "../../Core/NetworkingLibraries.php";
require_once "../../Core/HTTPLibraries.php";
require_once "./Classes/Player.php";
require_once "./Classes/LobbyAdapter.php";

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
  if (($p instanceof Player) && hash_equals(strval($p->getAuthKey()), strval($authKey))) { $me = $p; break; }  // authKey is the identity; playerID is a SEAT, renumbered at start
}
if (!$me) _updateDeckFail($response, 'Authentication failed.');

$adapter = LobbyAdapterFor(strval($lobby->rootName ?? ''));
if ($adapter === null) _updateDeckFail($response, 'Deck changes are not supported in this lobby.');

// The adapter checks against the LOBBY'S OWN format, not a hardcoded one — a Team Suns player
// editing their deck must be validated as teamsuns.
$v = $adapter->validateDeck($lobby, $deckLink);

// Leaders and base are derived FROM the identity cards, so the card list the roster draws and the
// leader cache the team-conflict check reads can never describe different decks.
// On failure all three are cleared: a stale identity keeps a seat advertising a deck it no longer
// has, and a stale leader cache keeps blocking (or permitting) a start.
$cards   = $v['ok'] ? $v['identity']['cards'] : [];
$leaders = [];
$base    = '';
foreach ($cards as $c) {
  if ($c['kind'] === 'leader')                   $leaders[] = $c['id'];
  elseif ($c['kind'] === 'base' && $base === '') $base      = $c['id'];
}

$me->setDeckOk($v['ok']);
$me->setDeckIdentity($leaders, $base, $cards);
// Loading a deck AUTO-READIES you — bringing a legal deck is the normal way to say "I'm good to go",
// and making everyone press a second button to confirm what they just did is friction for nothing.
// Unready stays available as the explicit "hold on, I'm still tinkering" signal.
// A deck the server REJECTED never readies: you cannot be ready with a deck that cannot be played.
$me->setReady($v['ok']);
if ($v['ok']) $me->setDeckLink($deckLink);
apcu_store($lobbyID, $lobby, 900);

$response->success = true;
$response->deckOk  = $v['ok'];
$response->message = $v['message'];
header('Content-Type: application/json');
echo json_encode($response);

<?php
// Change the deck on your seat in a lobby, before the match starts.
//
// This endpoint no longer knows anything about SWU: the sim's LobbyAdapter owns deck resolution,
// format legality, and the identity cards the roster draws. A sim without a `waitingRoom` block in
// its SiteDef has no adapter and simply cannot reach this flow.
require_once "../../Core/NetworkingLibraries.php";
require_once "../../Core/HTTPLibraries.php";
require_once "./Classes/Player.php";
require_once "./Classes/TeamRooms.php";
require_once "./Classes/LobbyAdapter.php";
require_once "./Classes/LobbyStore.php";

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

// READ-ONLY first pass. We need the room's format to validate against and the caller's identity to
// reject early, and both are stable for the life of the room — so this snapshot is safe to reason
// from even though the authoritative re-check happens inside the mutation below.
$snapshot = $lobbyID ? apcu_fetch($lobbyID) : null;
if (!$snapshot) _updateDeckFail($response, 'Room not found.');
if (!empty($snapshot->gameName) || ($snapshot->state ?? '') === 'started') _updateDeckFail($response, 'Game already started.');
if (SWURoomFindPlayerByAuthKey($snapshot, $authKey) === null) _updateDeckFail($response, 'Authentication failed.');  // authKey is the identity; playerID is a SEAT, renumbered at start

$adapter = LobbyAdapterFor(strval($snapshot->rootName ?? ''));
if ($adapter === null) _updateDeckFail($response, 'Deck changes are not supported in this lobby.');

// ⚠ THE NETWORK CALL HAPPENS HERE, OUTSIDE THE LOCK. This used to sit between the fetch and the
// store: swudb takes 0.4s cold and 3.2-4.2s once it throttles, and for all of that time this request
// held a lobby snapshot it then wrote back wholesale — reverting every heartbeat, ready flag and
// join committed in the window. The adapter checks against the LOBBY'S OWN format, not a hardcoded
// one, so a Team Suns player editing their deck is validated as teamsuns.
$v = $adapter->validateDeck($snapshot, $deckLink);

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

$err = null;
$applied = LobbyMutate($lobbyID, function ($lobby) use ($authKey, $v, $cards, $leaders, $base, $deckLink, &$err) {
  // Re-checked under the lock: the room may have started, or this seat may have been removed, while
  // the deck was resolving.
  if (!empty($lobby->gameName) || ($lobby->state ?? '') === 'started') { $err = 'Game already started.'; return false; }
  $me = SWURoomFindPlayerByAuthKey($lobby, $authKey);
  if ($me === null) { $err = 'Authentication failed.'; return false; }
  $me->setDeckOk($v['ok']);
  $me->setDeckIdentity($leaders, $base, $cards);
  // Loading a deck AUTO-READIES you — bringing a legal deck is the normal way to say "I'm good to
  // go", and making everyone press a second button to confirm what they just did is friction for
  // nothing. A deck the server REJECTED never readies: you cannot be ready with a deck that cannot
  // be played. Unready stays available as the explicit "hold on, I'm still tinkering" signal.
  $me->setReady($v['ok']);
  if ($v['ok']) $me->setDeckLink($deckLink);
  return true;
});
if ($err !== null)     _updateDeckFail($response, $err);
if ($applied === null) _updateDeckFail($response, 'Room is busy — try again.');

$response->success = true;
$response->deckOk  = $v['ok'];
$response->message = $v['message'];
header('Content-Type: application/json');
echo json_encode($response);

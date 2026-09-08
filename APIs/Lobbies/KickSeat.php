<?php
// Remove a seat from a private room. HOST ONLY, and always a deliberate human act.
//
// This endpoint exists because the automatic version did not work. Presence used to delete any seat
// that had not polled for ten seconds, which reliably removed people who were still sitting in the
// room: a hidden tab is throttled to ~1/minute, a locked phone stops polling entirely, and the
// client's own seat key expired fifteen minutes after joining. Absence is now shown (the roster's
// `away` flag) and acted on by a person, who can tell the difference between "gone" and "on the
// other tab".
require_once "../../Core/NetworkingLibraries.php";
require_once "../../Core/HTTPLibraries.php";
require_once "./Classes/Player.php";
require_once "./Classes/TeamRooms.php";
require_once "./Classes/LobbyStore.php";

$response = new stdClass();
function _kickFail($response, $m) {
  $response->success = false;
  $response->message = $m;
  header('Content-Type: application/json');
  echo json_encode($response);
  exit;
}

$lobbyID  = $_POST['lobbyID'] ?? '';
$authKey  = $_POST['authKey'] ?? '';
$targetID = intval($_POST['targetPlayerID'] ?? 0);
if ($lobbyID === '' || $authKey === '' || $targetID <= 0) _kickFail($response, 'Missing required parameters.');

$err = null; $removedName = '';
$lobby = LobbyMutate($lobbyID, function ($lobby) use ($authKey, $targetID, &$err, &$removedName) {
  if (!empty($lobby->gameName) || ($lobby->state ?? '') === 'started') { $err = 'Game already started.'; return false; }

  // Host is an IDENTITY (hostPlayerID) authenticated by authKey — never "playerID === 1", and never
  // the caller's self-reported playerID, which is a public seat number.
  $me = SWURoomFindPlayerByAuthKey($lobby, $authKey);
  if ($me === null) { $err = 'Authentication failed.'; return false; }
  if (intval($me->getPlayerID()) !== intval($lobby->hostPlayerID ?? 0)) { $err = 'Only the host can remove a player.'; return false; }
  if (intval($me->getPlayerID()) === $targetID) { $err = 'You cannot remove yourself — use Leave.'; return false; }

  foreach (($lobby->players ?? []) as $i => $p) {
    if (!($p instanceof Player) || intval($p->getPlayerID()) !== $targetID) continue;
    $removedName = 'P' . $targetID;
    array_splice($lobby->players, $i, 1);
    $lobby->numPlayers = count($lobby->players);
    if ($lobby->numPlayers <= 0) return 'delete';   // cannot happen (the host is still seated), but cheap
    // The host cannot kick themselves, so hostPlayerID still names a seated player — this is belt
    // and braces against a lobby whose host was already unseated by some other path.
    SWUMigrateHostIfNeeded($lobby);
    return true;
  }
  $err = 'That seat is no longer in the room.';
  return false;
});

if ($err !== null)   _kickFail($response, $err);
if ($lobby === null) _kickFail($response, 'Room is busy — try again.');

$response->success    = true;
$response->message    = $removedName . ' was removed from the room.';
$response->numPlayers = intval($lobby->numPlayers ?? 0);
header('Content-Type: application/json');
echo json_encode($response);

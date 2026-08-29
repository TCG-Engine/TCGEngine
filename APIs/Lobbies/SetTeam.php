<?php
require_once "../../Core/NetworkingLibraries.php";
require_once "../../Core/HTTPLibraries.php";
require_once "./Classes/Player.php";
require_once "./Classes/TeamRooms.php";

$response = new stdClass();
function _setTeamFail($response, $m) {
  $response->success = false;
  $response->message = $m;
  header('Content-Type: application/json');
  echo json_encode($response);
  exit;
}

$lobbyID  = $_POST['lobbyID'] ?? '';
$playerID = intval($_POST['playerID'] ?? 0);
$authKey  = $_POST['authKey'] ?? '';
$team     = strtolower(trim($_POST['team'] ?? ''));

$lobby = $lobbyID ? apcu_fetch($lobbyID) : null;
if (!$lobby) _setTeamFail($response, 'Room not found.');
if (!empty($lobby->gameName) || ($lobby->state ?? '') === 'started') _setTeamFail($response, 'Game already started.');
if (!SWURoomIsTeamLobby($lobby)) _setTeamFail($response, 'This room does not use teams.');

// Auth by IDENTITY + authKey. playerID never moves, so this survives a seat change.
$me = null;
foreach (($lobby->players ?? []) as $p) {
  if (($p instanceof Player) && hash_equals(strval($p->getAuthKey()), strval($authKey))) { $me = $p; break; }  // authKey is the identity; playerID is a SEAT, renumbered at start
}
// Two very different situations reach this point, and collapsing them into "Authentication failed."
// misleads the common one. A viewer who has opened an invite link but not yet joined holds NO authKey
// at all — they are not failing authentication, they are simply not in the room yet. Saying "auth
// failed" to them reads as "you need to log in", which is wrong twice over: logged-out players may
// join private lobbies by invite, and logging in would not have helped.
// A non-empty authKey that matches nobody IS a real mismatch — a stale tab, or a seat reaped for
// absence — so that one keeps the blunt message.
if (!$me) {
  _setTeamFail($response, $authKey === ''
    ? 'Join the room with a deck before picking a team.'
    : 'Authentication failed.');
}

if ($team === '') {
  SWURoomAssignTeam($lobby, $me, null);           // unassign
} else {
  if (!in_array($team, SWURoomTeamNames(), true)) _setTeamFail($response, 'Unknown team.');
  if (!SWURoomAssignTeam($lobby, $me, $team))     _setTeamFail($response, 'That team is already full.');
}

apcu_store($lobbyID, $lobby, 900);

$response->success  = true;
$response->team     = $me->getTeam();
$response->seat     = $me->getSeat();
$response->blockers = SWURoomStartBlockers($lobby, SWURoomLeaderSets($lobby));
$response->message  = '';
header('Content-Type: application/json');
echo json_encode($response);

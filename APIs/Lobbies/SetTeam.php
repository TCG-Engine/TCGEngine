<?php
require_once "../../Core/NetworkingLibraries.php";
require_once "../../Core/HTTPLibraries.php";
require_once "./Classes/Player.php";
require_once "./Classes/TeamRooms.php";
require_once "./Classes/LobbyStore.php";

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

$err = null; $me = null;
$lobby = LobbyMutate($lobbyID, function ($lobby) use ($authKey, $team, &$err, &$me) {
  if (!empty($lobby->gameName) || ($lobby->state ?? '') === 'started') { $err = 'Game already started.'; return false; }
  if (!SWURoomIsTeamLobby($lobby)) { $err = 'This room does not use teams.'; return false; }

  // Auth by IDENTITY + authKey. playerID is a SEAT and moves; authKey does not.
  $me = SWURoomFindPlayerByAuthKey($lobby, $authKey);
  // Two very different situations reach this point, and collapsing them into "Authentication failed."
  // misleads the common one. A viewer who has opened an invite link but not yet joined holds NO
  // authKey at all — they are not failing authentication, they are simply not in the room yet. Saying
  // "auth failed" to them reads as "you need to log in", which is wrong twice over: logged-out players
  // may join private lobbies by invite, and logging in would not have helped. A non-empty authKey that
  // matches nobody IS a real mismatch — a stale tab, or a seat the host removed — so that one keeps
  // the blunt message.
  if (!$me) {
    $err = ($authKey === '')
      ? 'Join the room with a deck before picking a team.'
      : 'Authentication failed.';
    return false;
  }

  if ($team === '') { SWURoomAssignTeam($lobby, $me, null); return true; }   // unassign
  if (!in_array($team, SWURoomTeamNames(), true)) { $err = 'Unknown team.'; $me = null; return false; }
  if (!SWURoomAssignTeam($lobby, $me, $team))     { $err = 'That team is already full.'; $me = null; return false; }
  return true;
});
if ($err !== null)   _setTeamFail($response, $err);
if ($lobby === null) _setTeamFail($response, 'Room not found or busy — try again.');

$response->success  = true;
$response->team     = $me->getTeam();
$response->seat     = $me->getSeat();
$response->blockers = SWURoomStartBlockers($lobby, SWURoomLeaderSets($lobby));
$response->message  = '';
header('Content-Type: application/json');
echo json_encode($response);

<?php
// Vote to remove the host of a still-forming room. Exists for the same reason the in-game kick vote
// does — an afk or unresponsive host, seated but not starting the room, holds everyone else hostage,
// and nobody else can Remove them (KickSeat.php is host-only by design). Any non-host seat may cast a
// Yes vote once APIs/Lobbies/Classes/HostVote.php's window has opened; the vote carries and removes
// the host the moment enough Yes votes are recorded from CURRENT legal voters.
require_once "../../Core/NetworkingLibraries.php";
require_once "../../Core/HTTPLibraries.php";
require_once "./Classes/Player.php";
require_once "./Classes/TeamRooms.php";   // SWUMigrateHostIfNeeded
require_once "./Classes/HostVote.php";
require_once "./Classes/LobbyStore.php";

$response = new stdClass();
function _hostVoteFail($response, $m) {
  $response->success = false;
  $response->message = $m;
  header('Content-Type: application/json');
  echo json_encode($response);
  exit;
}

$lobbyID = $_POST['lobbyID'] ?? '';
$authKey = $_POST['authKey'] ?? '';
if ($lobbyID === '' || $authKey === '') _hostVoteFail($response, 'Missing required parameters.');

$now = time();
$err = null; $carried = false; $removedName = '';
$lobby = LobbyMutate($lobbyID, function ($lobby) use ($authKey, $now, &$err, &$carried, &$removedName) {
  if (!empty($lobby->gameName) || in_array($lobby->state ?? '', ['starting', 'started'], true)) {
    $err = 'Game already starting or started.'; return false;
  }

  $me = SWURoomFindPlayerByAuthKey($lobby, $authKey);
  if ($me === null) { $err = 'Authentication failed.'; return false; }
  $voterID = intval($me->getPlayerID());

  // Checked explicitly (rather than reading SWUHostVoteRecordYes's return alone) so a DUPLICATE
  // vote — a legal voter, window open, already recorded — is distinguished from a real refusal.
  // Voting twice is a no-op, not an error (HostVote.php's own contract); collapsing both into one
  // false meant a second click from the same seat reported "You cannot vote in this room."
  if (!SWUHostVoteWindowOpen($lobby, $now)) { $err = 'The vote to remove the host is not open yet.'; return false; }
  if ($voterID === intval($lobby->hostPlayerID ?? 0)) { $err = 'The host cannot vote to remove themselves.'; return false; }
  if (!in_array($voterID, SWUHostVoteVoterSeats($lobby), true)) { $err = 'You cannot vote in this room.'; return false; }

  $alreadyVoted = in_array($voterID, SWUHostVoteYesIDs($lobby), true);
  SWUHostVoteRecordYes($lobby, $voterID, $now);   // no-op when $alreadyVoted; safe to call either way

  $tally = SWUHostVoteTally($lobby);
  if (!$tally['carried']) return $alreadyVoted ? false : true;   // duplicate: nothing new to persist

  // Carried: remove the host, exactly as KickSeat.php does.
  $hostID = intval($lobby->hostPlayerID ?? 0);
  foreach (($lobby->players ?? []) as $i => $p) {
    if (!($p instanceof Player) || intval($p->getPlayerID()) !== $hostID) continue;
    $removedName = 'P' . $hostID;
    array_splice($lobby->players, $i, 1);
    $lobby->numPlayers = count($lobby->players);
    break;
  }
  SWUMigrateHostIfNeeded($lobby);
  // Fresh start for the new host: clear the vote and re-arm the timer under the new signature on the
  // next poll (SWUHostVoteArm sees the changed hostPlayerID and re-stamps armedAt automatically).
  unset($lobby->hostVote, $lobby->hostVoteSig, $lobby->hostVoteArmedAt);
  $carried = true;
  return true;
});

if ($err !== null)   _hostVoteFail($response, $err);
if ($lobby === null) _hostVoteFail($response, 'Room is busy — try again.');

$response->success = true;
$response->carried = $carried;
$response->message = $carried ? ($removedName . ' was removed as host.') : 'Vote recorded.';
header('Content-Type: application/json');
echo json_encode($response);

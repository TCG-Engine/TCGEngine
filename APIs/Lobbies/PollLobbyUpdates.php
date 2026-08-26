<?php

require_once "../../Core/NetworkingLibraries.php";
require_once __DIR__ . "/Classes/TeamRooms.php";
$swuFormatsPath = __DIR__ . '/../../AppCore/SWU/Formats.php';
if (is_file($swuFormatsPath)) require_once $swuFormatsPath;
require_once "../../Core/HTTPLibraries.php";
require_once "./Classes/Player.php";

$response = new stdClass();

if (!isset($_POST['lobbyID'])) {
  $response->success = false;
  $response->message = "Lobby ID is required.";
  header('Content-Type: application/json');
  echo json_encode($response);
  exit;
}

if (!isset($_POST['lobbyID']) || !isset($_POST['rootName']) || !isset($_POST['playerID']) || !isset($_POST['authKey'])) {
  $response->success = false;
  $response->message = "Missing required parameters.";
  header('Content-Type: application/json');
  echo json_encode($response);
  exit;
}

$lobbyID = $_POST['lobbyID'];
$rootName = $_POST['rootName'];
$playerID = $_POST['playerID'];
$authKey = $_POST['authKey'];

$timeout = 30; // Maximum time to wait in seconds
$startTime = time();

while (true) {
  // Fetch the lobby data from the cache
  $lobby = apcu_fetch($lobbyID);

  $isRoom = $lobby && is_object($lobby) && ($lobby->rootName ?? '') === 'SWUSim'
            && function_exists('SWUFormatIsRoomFormat') && SWUFormatIsRoomFormat($lobby->format ?? '');
  if ($isRoom) {
    $roster = [];
    foreach (($lobby->players ?? []) as $p) {
      if (!($p instanceof Player)) continue;
      $roster[] = [
        'playerID' => $p->getPlayerID(),
        'seat'     => $p->getSeat(),
        'team'     => $p->getTeam(),
        'deckOk'   => $p->getDeckOk(),
        // The seat's resolved leaders, cached at deck-validation time. The lobby table shows them so a
        // within-team leader conflict is visible BEFORE anyone tries to start.
        'leaders'  => $p->getLeaders(),
        'isHost'   => $p->getPlayerID() === intval($lobby->hostPlayerID ?? 1),
      ];
    }
    $response->success = true;
    $response->isRoom = true;
    $response->isTeamRoom = SWURoomIsTeamLobby($lobby);
    $response->roster = $roster;
    $response->blockers = SWURoomStartBlockers($lobby, SWURoomLeaderSets($lobby));
    $response->numPlayers = intval($lobby->numPlayers ?? 0);
    $response->maxPlayers = intval($lobby->maxPlayers ?? 4);
    $response->state = $lobby->state ?? 'open';
    $response->inviteCode = $lobby->inviteCode ?? '';
    // Hand the caller back the seat it CURRENTLY holds. StartRoom renumbers playerIDs at start (team
    // rooms sort by picked seat first), so the playerID the client captured at join is stale for anyone
    // the sort moved — and this branch exits before the auth block below, so nothing else corrects it.
    // The client redirects on `started`, so without this it enters the game as its OLD seat and the
    // game rejects it: "this browser is not currently authenticated as player N" (reported 2026-08-26,
    // where every seat except the host's failed).
    $meRoom = SWURoomFindPlayerByAuthKey($lobby, $authKey);
    if ($meRoom !== null) $response->playerID = $meRoom->getPlayerID();
    if (!empty($lobby->gameName)) { $response->started = true; $response->gameName = $lobby->gameName; }
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
  }

  if ($lobby) {
    // Check if the lobby is ready
    if (isset($lobby->ready) && $lobby->ready) {
      $response->success = true;
      $response->ready = true;

      if (isset($lobby->gameName)) {
        $response->gameName = $lobby->gameName;
      }

      // Authenticate the player: verify the authKey matches the Player entry in the lobby.
      // The caller already knows their playerID (they sent it); we just need to confirm
      // the authKey is correct, then echo the playerID back directly.
      $authenticated = false;
      if (isset($lobby->players) && is_array($lobby->players)) {
        foreach ($lobby->players as $player) {
          if (($player instanceof Player) && $player->getPlayerID() == $playerID && $player->getAuthKey() == $authKey) {
            $authenticated = true;
            break;
          }
        }
      }

      if (!$authenticated) {
        $response->success = false;
        $response->message = "Authentication failed.";
        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
      }

      $response->playerID = $playerID;

      header('Content-Type: application/json');
      echo json_encode($response);
      exit;
    }
  }

  // Break the loop if the timeout is reached
  if (time() - $startTime >= $timeout) {
    $response->success = false;
    $response->message = "Timeout reached. No updates available.";
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
  }

  // Sleep for a short interval before checking again
  usleep(100000); // 100ms
}


?>

<?php

require_once "../../Core/NetworkingLibraries.php";
require_once __DIR__ . "/Classes/TeamRooms.php";
$swuFormatsPath = __DIR__ . '/../../AppCore/SWU/Formats.php';
if (is_file($swuFormatsPath)) require_once $swuFormatsPath;
require_once "../../Core/HTTPLibraries.php";
require_once "./Classes/Player.php";
require_once "./Classes/LobbyAdapter.php";

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

// An invite link opens the waiting room BEFORE the visitor has joined, so the page has to be able to
// look a lobby up by its invite code with no lobbyID and no authKey. This is read-only: the roster it
// returns is the same public information the lobby shows everyone once you are in it (and leaders and
// bases are public the moment the game starts anyway). Joining still goes through JoinQueue.
if (($lobbyID === '' || $lobbyID === 'invite') && isset($_POST['inviteCode']) && $_POST['inviteCode'] !== '') {
  $wantCode = strval($_POST['inviteCode']);
  $ci = function_exists('apcu_cache_info') ? apcu_cache_info() : null;
  if (is_array($ci) && isset($ci['cache_list']) && is_array($ci['cache_list'])) {
    foreach ($ci['cache_list'] as $e) {
      if (!isset($e['info']) || !is_string($e['info']) || $e['info'] === '') continue;
      $cand = apcu_fetch($e['info']);
      if ($cand === false || !is_object($cand)) continue;
      if (strval($cand->rootName ?? '') !== $rootName) continue;
      if (empty($cand->isPrivate)) continue;
      if (strval($cand->inviteCode ?? '') !== $wantCode) continue;
      $lobbyID = strval($cand->id ?? '');
      break;
    }
  }
  if ($lobbyID === '' || $lobbyID === 'invite') {
    $response->success = false;
    $response->gone    = true;      // the page renders GONE: expired, or a bad code
    $response->message = 'That invite is invalid or has expired.';
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
  }
}
$playerID = $_POST['playerID'];
$authKey = $_POST['authKey'];

$timeout = 30; // Maximum time to wait in seconds
$startTime = time();

while (true) {
  // Fetch the lobby data from the cache
  $lobby = apcu_fetch($lobbyID);

  // Was SWUSim-only and gated on seat count. The adapter answers the ROUTING question — private and
  // not a solo/local format — so a private 2-player lobby now gets a roster too.
  $pollAdapter = ($lobby && is_object($lobby)) ? LobbyAdapterFor(strval($lobby->rootName ?? '')) : null;
  $isRoom = $pollAdapter !== null && $pollAdapter->wantsWaitingRoom($lobby);
  if ($isRoom) {
    // PRESENCE. The poll IS the heartbeat: stamp the caller, then drop anyone who has stopped
    // polling. Someone is always polling while anyone is in the room, so the sweep always runs.
    // ⚠ There is deliberately NO unload beacon. A refresh fires unload, so a beacon would release
    // the seat and destroy the whole survive-a-refresh property this page exists for — the reaper
    // handles a closed browser instead, a few seconds later.
    $meSeat = SWURoomFindPlayerByAuthKey($lobby, $authKey);
    if ($meSeat !== null) $meSeat->touch();
    $reaped = SWUReapAbsentSeats($lobby);
    if ($reaped > 0) {
      if ($lobby->numPlayers <= 0) {
        apcu_delete($lobbyID);          // last seat gone — nothing left to come back to
      } else {
        SWUMigrateHostIfNeeded($lobby); // the reaped seat may have been the host
        apcu_store($lobbyID, $lobby, 900);
      }
    } elseif ($meSeat !== null) {
      apcu_store($lobbyID, $lobby, 900);   // persist the heartbeat
    }

    $roster = [];
    foreach (($lobby->players ?? []) as $p) {
      if (!($p instanceof Player)) continue;
      $roster[] = [
        'playerID' => $p->getPlayerID(),
        'seat'     => $p->getSeat(),
        'team'     => $p->getTeam(),
        'deckOk'   => $p->getDeckOk(),
        'ready'    => $p->getReady(),
        // The seat's deck identity, cached at deck-validation time. The lobby table shows it so a
        // within-team leader conflict is visible BEFORE anyone tries to start, and so everyone can see
        // what each seat is bringing and swap decks first. Leaders and bases are public information the
        // moment the game starts (both begin face up in play), so this reveals nothing the first turn
        // would not.
        //
        // 'identity.cards' is the GENERIC display list the shared page renders — [{id,name,url,kind}]
        // with art URLs already resolved server-side, so the page needs no card dictionary and no
        // per-sim JavaScript. 'leaders' stays because the team leader-conflict check reads it.
        'identity' => ['cards' => $p->getIdentityCards()],
        'leaders'  => $p->getLeaders(),
        'base'     => $p->getBase(),
        'isHost'   => $p->getPlayerID() === intval($lobby->hostPlayerID ?? 1),
      ];
    }
    $response->success = true;
    $response->isRoom = true;
    $response->isTeamRoom = SWURoomIsTeamLobby($lobby);
    $response->roster = $roster;
    $response->blockers = $pollAdapter->startBlockers($lobby);
    // How many seats to DRAW and whether they split into teams — the rendering question, kept
    // separate from the routing one above. Carries queueType for Spec 2's per-match choice.
    $response->seatModel = $pollAdapter->seatModel($lobby);
    $response->numPlayers = intval($lobby->numPlayers ?? 0);
    $response->maxPlayers = intval($lobby->maxPlayers ?? 4);
    $response->state = $lobby->state ?? 'open';
    $response->inviteCode = $lobby->inviteCode ?? '';
    $response->lobbyID = $lobbyID;   // resolved from ?invite=; the page replaceState()s to ?lobby=<id>
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

  // A lobby that was resolved and then disappeared is GONE (apcu TTL, or everyone left and
  // LeaveQueue deleted it), which the page must distinguish from "no updates yet".
  if (!$lobby && $lobbyID !== '') {
    $response->success = false;
    $response->gone    = true;
    $response->message = 'This lobby has ended.';
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
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

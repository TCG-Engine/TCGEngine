<?php

require_once "../../Core/NetworkingLibraries.php";
require_once __DIR__ . "/Classes/TeamRooms.php";
$swuFormatsPath = __DIR__ . '/../../AppCore/SWU/Formats.php';
if (is_file($swuFormatsPath)) require_once $swuFormatsPath;
require_once "../../Core/HTTPLibraries.php";
require_once "./Classes/Player.php";
require_once "./Classes/LobbyAdapter.php";
require_once "./Classes/LobbyStore.php";

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
  $found = LobbyKeyForInvite($wantCode, $rootName);
  if ($found !== null) {
    $cand = apcu_fetch($found);
    if (is_object($cand) && !empty($cand->isPrivate)) $lobbyID = strval($cand->id ?? '');
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
    // PRESENCE. The poll IS the heartbeat: stamp the caller, then let the roster SHOW who has gone
    // quiet. Nothing here removes anybody.
    //
    // ⚠ This used to delete any seat that had not polled in 10 seconds, and that is the bug this
    // block exists to not have. The 10s budget was measured against the 1.5s poll interval, but the
    // thing that actually breaks a heartbeat is the BROWSER: a hidden tab is throttled to ~1/minute,
    // a locked phone stops entirely, and the client's own localStorage key used to expire 15 minutes
    // after joining — after which it polled with no authKey and could not be stamped at all. All
    // three deleted people who were still sitting in the room.
    //
    // ⚠ There is still deliberately NO unload beacon. A refresh fires unload, so a beacon would
    // release the seat and destroy the survive-a-refresh property this page exists for.
    $meSeat = null;
    $lobbyAfter = LobbyMutate($lobbyID, function ($l) use ($authKey, &$meSeat) {
      $meSeat = SWURoomFindPlayerByAuthKey($l, $authKey);
      if ($meSeat !== null) $meSeat->touch();
      $migrated = SWUMigrateHostIfAway($l);
      $assigned = LobbyEnsureFixedSeats($l);
      return ($meSeat !== null || $migrated || $assigned);   // false = nothing changed, skip the write
    });
    // A busy or vanished lobby must not blank the roster: fall back to the unlocked read we already
    // have. The heartbeat is idempotent and the next poll is 1.5s away.
    if ($lobbyAfter !== null) $lobby = $lobbyAfter;

    $roster = [];
    foreach (($lobby->players ?? []) as $p) {
      if (!($p instanceof Player)) continue;
      $roster[] = [
        'playerID' => $p->getPlayerID(),
        'seat'     => $p->getSeat(),
        'team'     => $p->getTeam(),
        'deckOk'   => $p->getDeckOk(),
        'ready'    => $p->getReady(),
        'botProfile' => $p->getBotProfile(),
        // Presence, for display only. An away seat keeps its seat and does NOT block Start — the
        // host reads this and decides whether to use Remove.
        'away'     => SWUSeatIsAway($p),
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
    $response->botProfiles = $pollAdapter instanceof LobbyBotAdapter ? $pollAdapter->botProfiles($lobby) : (object)[];
    $response->blockers = $pollAdapter->startBlockers($lobby);
    // How many seats to DRAW and whether they split into teams — the rendering question, kept
    // separate from the routing one above. Carries queueType for Spec 2's per-match choice.
    $response->seatModel = $pollAdapter->seatModel($lobby);
    $response->numPlayers = intval($lobby->numPlayers ?? 0);
    $response->maxPlayers = intval($lobby->maxPlayers ?? 4);
    $response->state = $lobby->state ?? 'open';
    // May this viewer SEND chat? Answered by the SAME seam SubmitChat.php enforces
    // (Core/ChatPolicy.php), so the panel can never render a composer whose messages will be refused
    // — on SWUSim a guest reads the room's chat but gets no box (owner, 2026-09-21).
    include_once __DIR__ . '/../../Core/ChatPolicy.php';
    if (session_status() === PHP_SESSION_NONE) @session_start();
    $pollChatRefusal = ChatSendRefusal(
        strval($lobby->rootName ?? ''),
        ['viewerSeat' => 1, 'isSpectator' => false, 'userId' => intval($_SESSION['userid'] ?? 0)],
        'l:' . strval($lobby->id ?? ''));
    $response->canChat = ($pollChatRefusal === null);
    $response->cannotChatReason = $pollChatRefusal ?? '';
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
    // Presenting a key the room does not know means this browser HELD a seat and no longer does —
    // the host removed it, or it left from another tab. Say so. Sending no key at all is a viewer
    // who has not joined yet, which is not the same thing and keeps the plain not-seated state.
    // (SetTeam.php draws exactly this distinction in its error copy.)
    $response->removed = ($authKey !== '' && $meRoom === null);
    if (!empty($lobby->gameName)) { $response->started = true; $response->gameName = $lobby->gameName; }
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
  }

  // A public-queue seat released at pairing (its deck failed, or the match could not start) is told why, once, and the
  // client stops polling. docs/superpowers/specs/2026-09-16-swusim-public-queues-design.md §2.3.
  if ($lobby && is_array($lobby->queueNotices ?? null) && isset($lobby->queueNotices[strval($authKey)])) {
    $response->success = false;
    $response->gone    = true;
    $response->message = strval($lobby->queueNotices[strval($authKey)]);
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
  }

  if ($lobby) {
    // Joining fills the lobby before game creation runs outside the lobby lock.
    // Wait for its committed game name before telling polling players to navigate.
    if (!empty($lobby->ready) && !empty($lobby->gameName)) {
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

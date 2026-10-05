<?php

require_once "../../Core/NetworkingLibraries.php";
require_once __DIR__ . "/Classes/TeamRooms.php";
$swuFormatsPath = __DIR__ . '/../../AppCore/SWU/Formats.php';
if (is_file($swuFormatsPath)) require_once $swuFormatsPath;
require_once "../../Core/HTTPLibraries.php";
require_once "./Classes/Player.php";
require_once "./Classes/LobbyAdapter.php";
require_once "./Classes/HostVote.php";
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
    // ⚠ NO isPrivate GATE. It used to read `!empty($cand->isPrivate)`, which meant a PUBLIC Twin Suns
    // room — the only kind that has a shareable "Copy Link" — never resolved here, and every visitor
    // following that link was told the invite was invalid or expired whether the room was full, had a
    // free seat, or had just been created. JoinQueue.php dropped the identical gate deliberately when
    // the public-room link shipped (see its own note); this was the site that was missed, so the link
    // died one layer before the capacity check that would have explained it.
    //
    // Holding the code is the whole test, and LobbyKeyForInvite has already verified that this lobby's
    // inviteCode equals the code presented and that its rootName matches. Codes are minted only for
    // private lobbies and public WAITING ROOMS, never for a plain public queue, so nothing here is
    // reachable that matchmaking would not already hand this visitor.
    if (is_object($cand)) $lobbyID = strval($cand->id ?? '');
  }
  if ($lobbyID === '' || $lobbyID === 'invite') {
    // ── The lobby's APCu entry is gone. That is the NORMAL state mid-match, not an error. ──
    // LOBBY_TTL_SECONDS is 900 and only this endpoint's own heartbeat refreshes it, so once everyone
    // navigates into the game the lobby and its `invite:` index expire ~15 minutes in — long before a
    // Bo3 finishes. Reported live 2026-09-25: the HOST refreshed during game 2 of a private Bo3 and was
    // told their own invite had expired. Fall back to the durable index written beside the match, which
    // outlives every game in it, and send a participant back into the game they are actually in.
    //
    // ⚠ Resolved through the MATCH, never the lobby: `$lobby->gameName` is only ever game 1, so a
    // lobby-shaped fallback would land a game-2 refresh in a FINISHED game 1.
    $inviteMatch   = null;
    $matchFlowPath = __DIR__ . '/../../Core/Match/MatchFlow.php';
    if (is_file($matchFlowPath)) {
      require_once $matchFlowPath;
      if (function_exists('MatchFindByInviteCode')) $inviteMatch = MatchFindByInviteCode($rootName, $wantCode);
    }
    if (is_array($inviteMatch)) {
      // The waiting room polls with `loadKey(lobbyID)`, and on the invite path lobbyID is still '' —
      // so the presented key is empty and the LASTAUTHKEY COOKIE is what identifies the viewer. That
      // cookie is set for every seat at game start (WaitingRoom.php) and every game of a match reuses
      // the same per-seat key (MatchSpawnNextGameWithDecks), so a game-1 cookie authenticates game 2.
      require_once __DIR__ . '/../../Core/GameAuth.php';
      $inviteKey  = SimGameResolvePresentedAuthKey(strval($_POST['authKey'] ?? ''));
      $inviteSeat = MatchSeatForAuthKey($inviteMatch, $inviteKey);
      $inviteGame = MatchCurrentGameName($inviteMatch);
      $matchOver  = (strval($inviteMatch['state'] ?? '') === 'complete') || MatchIsOver($inviteMatch);

      if ($matchOver) {
        $response->success = false;
        $response->gone    = true;
        $response->message = 'That match has ended.';
        $response->canRequeue = true;
      } elseif ($inviteSeat > 0 && $inviteGame !== '') {
        // A participant: hand back the same shape the started-lobby path does, so the waiting room's
        // existing `r.started && r.gameName` branch redirects with no client change.
        $response->success  = true;
        $response->started  = true;
        $response->gameName = $inviteGame;
        $response->playerID = $inviteSeat;
      } elseif ($inviteGame !== '') {
        // Someone who holds the link but no seat in this match. They are NOT redirected as a player —
        // that rendered as "not currently authenticated as player N", which reads like a broken game
        // rather than a closed door — but they are not dead-ended either: they came here to watch this
        // table, and a spectator needs no seat and no authKey (Core/ViewerIdentity.php, playerID=S).
        // Owner, 2026-09-26: someone waiting for a seat spectates until one opens.
        $response->success   = true;
        $response->started   = true;
        $response->gameName  = $inviteGame;
        $response->spectator = true;
        $response->message   = 'The game started without you — you are watching as a spectator.';
      } else {
        $response->success = false;
        $response->gone    = true;
        $response->message = 'That match is already in progress.';
      }
      header('Content-Type: application/json');
      echo json_encode($response);
      exit;
    }

    // TWO DIFFERENT CAUSES, and the player can act on only one of them. A room that CLOSED was real —
    // the link was fine and there is nothing to re-copy — whereas a code that never resolved is worth
    // checking for a truncated paste, which is the single most common way this happens (chat clients
    // break long URLs). The old message offered both at once and helped with neither.
    $response->success = false;
    $response->gone    = true;
    $response->message = LobbyInviteWasClosed($wantCode)
      ? 'That room has closed — everyone left before the game started.'
      : "That room link isn't valid. Check you copied the whole link, including the code at the end.";
    $response->canRequeue = true;   // the page offers a way back into matchmaking rather than a wall
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
    $now = time();
    $lobbyAfter = LobbyMutate($lobbyID, function ($l) use ($authKey, $now, &$meSeat) {
      $meSeat = SWURoomFindPlayerByAuthKey($l, $authKey);
      if ($meSeat !== null) $meSeat->touch();
      $migrated = SWUMigrateHostIfAway($l);
      $assigned = LobbyEnsureFixedSeats($l);
      // Re-arms the Kick Host timer on every join/leave/kick/host-migration for free — this mutate
      // already runs on every poll, so no other call site needs to know about it.
      $armed = SWUHostVoteArm($l, $now);
      return ($meSeat !== null || $migrated || $assigned || $armed);   // false = nothing changed, skip the write
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
        // ⚠ ACCOUNTS ONLY — null for a guest, NEVER the string "Guest PN". The page builds that label
        // from the seat number it is already drawing, because the seat a tile SHOWS and the seat's
        // playerID diverge once anybody has left (ids go 1, 3, 4 and the next joiner is 5 while
        // sitting on the tile labelled "Seat 4"). Emitting a number from here would print "Guest P5"
        // under "Seat 4". Same contract as window.SWU_SEAT_USERNAMES, where a missing entry is what
        // MEANS "not logged in" — putting guests in it would make them look like accounts.
        'username' => ($p->getUsername() !== '') ? $p->getUsername() : null,
        'team'     => $p->getTeam(),
        'deckOk'   => $p->getDeckOk(),
        'ready'    => $p->getReady(),
        'botProfile' => $p->getBotProfile(),
        // Presence, for display only. An away seat keeps its seat and does NOT block Start — the
        // host reads this and decides whether to use Remove.
        'away'     => SWUSeatIsAway($p),
        // Seconds until the host may Remove this seat (its first minute is protected — SWUSeatKickableIn). Sent as
        // a DURATION, not a timestamp, so the page counts down from its own clock and skew cannot matter.
        'kickableIn' => SWUSeatKickableIn($p),
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
    // Seconds until the host may add a bot (a public Twin Suns room waits a quiet minute — LobbyBotPolicyAdapter).
    // A DURATION, like a seat's kickableIn: the page turns it into a local deadline, outside its redraw signature.
    // Additive: absent for every sim whose adapter has no bot policy.
    if ($pollAdapter instanceof LobbyBotPolicyAdapter) $response->botAddableIn = $pollAdapter->botAddWaitSeconds($lobby, time());
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
    // ADDITIVE (2026-09-26). The page shows a bare "Copy Link" for a PUBLIC room and
    // "Invite: <code> [Copy Invite Link]" for a private one — a public room has no secret to read
    // aloud. Every existing field is untouched; callers that ignore this see no change.
    $response->isPrivate = !empty($lobby->isPrivate);
    $response->lobbyID = $lobbyID;   // resolved from ?invite=; the page replaceState()s to ?lobby=<id>
    // Hand the caller back the seat it CURRENTLY holds. StartRoom renumbers playerIDs at start (team
    // rooms sort by picked seat first), so the playerID the client captured at join is stale for anyone
    // the sort moved — and this branch exits before the auth block below, so nothing else corrects it.
    // The client redirects on `started`, so without this it enters the game as its OLD seat and the
    // game rejects it: "this browser is not currently authenticated as player N" (reported 2026-08-26,
    // where every seat except the host's failed).
    $meRoom = SWURoomFindPlayerByAuthKey($lobby, $authKey);
    if ($meRoom !== null) $response->playerID = $meRoom->getPlayerID();
    // Kick Host: whether the vote window is open, the live tally, and whether THIS viewer may cast
    // or has already cast a Yes. A viewer with no seat gets canVote=false for free (playerID 0).
    $response->hostVote = SWUHostVoteState($lobby, $now, intval($meRoom !== null ? $meRoom->getPlayerID() : 0));
    // Presenting a key the room does not know means this browser HELD a seat and no longer does —
    // the host removed it, or it left from another tab. Say so. Sending no key at all is a viewer
    // who has not joined yet, which is not the same thing and keeps the plain not-seated state.
    // (SetTeam.php draws exactly this distinction in its error copy.)
    $response->removed = ($authKey !== '' && $meRoom === null);
    // What the room is, so the page can offer "find another <format> room" without guessing.
    $response->format = strval($lobby->format ?? '');
    $response->canRequeue = true;
    if (!empty($lobby->gameName)) {
      $response->started  = true;
      $response->gameName = $lobby->gameName;
      // ⚠ A VIEWER WITH NO SEAT MUST BE SENT AS A SPECTATOR, NOT AS PLAYER 0. $response->playerID is
      // set just above only when this authKey holds a seat, so for anyone else the page fell back to
      // its own myPlayerID — which is 0 before you join — and redirected to NextTurn.php?playerID=0.
      // NormalizeViewerIdentity rejects 0 outright (viewerID ''), so watching a room you were waiting
      // in start produced a broken board rather than a spectator view. Reachable today through the
      // ?lobby=<id> URL, which is what people paste out of the address bar.
      $response->spectator = ($meRoom === null);
    }
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

  // Meta Premier (final review #1): a waiting rated quick-match lobby never pairs on its own — only a JOIN pairs. When
  // another waiting rated lobby has come inside the wider of the two windows, tell this seated player to re-queue; their
  // JoinQueue applies the same rule and pairs them. Checked every ~2s: it scans the APCu lobby list.
  if ($lobby && is_object($lobby) && isset($lobby->rating, $lobby->createdAt) && empty($lobby->gameName)
      && microtime(true) >= ($mpNextCheck ?? 0) && strval($lobby->rootName ?? '') === 'SWUSim') {
    $mpNextCheck = microtime(true) + 2.0;
    $mpSeated = false;
    foreach (($lobby->players ?? []) as $mpP) {
      if (($mpP instanceof Player) && $mpP->getAuthKey() == $authKey) { $mpSeated = true; break; }
    }
    if ($mpSeated) {
      require_once __DIR__ . '/../../SWUSim/MetaPremier.php';
      $mpInfo = apcu_cache_info();
      if (SWUMetaPremierShouldRequeue(strval($lobbyID), $mpInfo['cache_list'] ?? [], fn($k) => apcu_fetch($k), time())) {
        $response->success = true;
        $response->requeue = true;    // additive: the SWUSim menu re-submits its last join
        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
      }
    }
  }

  // A lobby that was resolved and then disappeared is GONE (apcu TTL, or everyone left and
  // LeaveQueue deleted it), which the page must distinguish from "no updates yet".
  if (!$lobby && $lobbyID !== '') {
    $response->success = false;
    $response->gone    = true;
    $response->message = 'This lobby has ended.';
    $response->canRequeue = true;
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

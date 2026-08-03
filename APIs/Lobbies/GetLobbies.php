<?php

  require_once "../../Core/NetworkingLibraries.php";
  require_once "../../Core/HTTPLibraries.php";

  $response = new stdClass();
  $response->success = true;
  $response->message = "Successfully fetched lobbies.";

  $rootName = isset($_GET['rootName']) ? $_GET['rootName'] : null;
  $includePrivate = isset($_GET['includePrivate']) && ($_GET['includePrivate'] === '1' || strtolower($_GET['includePrivate']) === 'true');
  // Optional lookup by invite code: returns just that lobby so a visitor following an invite link can
  // SHOW the host's format / match type instead of whatever their own dropdowns defaulted to (the
  // server already adopts the host's settings on join — see JoinQueue.php — this keeps the UI honest).
  // Additive and backward-compatible: absent the param, responses are byte-identical to before.
  // Narrowing, not widening: the caller must already know the secret code, and inviteCode is never
  // echoed back in the payload, so this cannot be used to enumerate or discover private lobbies.
  $inviteCode = isset($_GET['inviteCode']) ? trim($_GET['inviteCode']) : '';
  $response->data = [];
  $response->totalCount = 0;
  $response->publicCount = 0;
  $response->privateCount = 0;
  $cacheInfo = apcu_cache_info();

  if (is_array($cacheInfo) && isset($cacheInfo['cache_list']) && is_array($cacheInfo['cache_list'])) {
    foreach ($cacheInfo['cache_list'] as $entry) {
      if (!isset($entry['info']) || !is_string($entry['info']) || $entry['info'] === '') continue;

      $lobby = apcu_fetch($entry['info']);
      if ($lobby === false || !is_object($lobby)) continue;

      // Matchmaking lobbies are stored as objects with these core fields.
      if (!isset($lobby->id) || !isset($lobby->numPlayers) || !isset($lobby->maxPlayers) || !isset($lobby->ready)) continue;
      if (isset($lobby->state) && $lobby->state === 'matched') continue;

      if ($rootName !== null && $rootName !== '') {
        if (!isset($lobby->rootName) || $lobby->rootName !== $rootName) continue;
      }

      // Invite-code lookup short-circuits every other visibility rule: knowing the code IS the
      // authorization, and the caller wants exactly this lobby.
      if ($inviteCode !== '') {
        if (!isset($lobby->inviteCode) || strval($lobby->inviteCode) !== $inviteCode) continue;
      }

      $isPrivate = isset($lobby->isPrivate) ? boolval($lobby->isPrivate) : false;
      ++$response->totalCount;
      if ($isPrivate) {
        ++$response->privateCount;
      } else {
        ++$response->publicCount;
      }
      // A code lookup is inherently a request for a private lobby, so it implies includePrivate.
      if ($isPrivate && !$includePrivate && $inviteCode === '') continue;

      $response->data[] = [
        'id' => $lobby->id,
        'gameName' => isset($lobby->gameName) ? $lobby->gameName : null,
        'numPlayers' => intval($lobby->numPlayers),
        'maxPlayers' => intval($lobby->maxPlayers),
        'ready' => boolval($lobby->ready),
        'isPrivate' => $isPrivate,
        'rootName' => isset($lobby->rootName) ? $lobby->rootName : null,
        'format' => isset($lobby->format) ? $lobby->format : null,
        'queueType' => isset($lobby->queueType) ? $lobby->queueType : null,
      ];
    }
  }

  header('Content-Type: application/json');
  echo json_encode($response);

?>

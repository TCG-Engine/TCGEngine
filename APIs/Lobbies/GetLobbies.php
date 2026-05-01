<?php

  require_once "../../Core/NetworkingLibraries.php";
  require_once "../../Core/HTTPLibraries.php";

  $response = new stdClass();
  $response->success = true;
  $response->message = "Successfully fetched lobbies.";

  $rootName = isset($_GET['rootName']) ? $_GET['rootName'] : null;
  $response->data = [];
  $cacheInfo = apcu_cache_info();

  if (is_array($cacheInfo) && isset($cacheInfo['cache_list']) && is_array($cacheInfo['cache_list'])) {
    foreach ($cacheInfo['cache_list'] as $entry) {
      if (!isset($entry['info']) || !is_string($entry['info']) || $entry['info'] === '') continue;

      $lobby = apcu_fetch($entry['info']);
      if ($lobby === false || !is_object($lobby)) continue;

      // Matchmaking lobbies are stored as objects with these core fields.
      if (!isset($lobby->id) || !isset($lobby->numPlayers) || !isset($lobby->maxPlayers) || !isset($lobby->ready)) continue;

      if ($rootName !== null && $rootName !== '') {
        if (!isset($lobby->rootName) || $lobby->rootName !== $rootName) continue;
      }

      $response->data[] = [
        'id' => $lobby->id,
        'gameName' => isset($lobby->gameName) ? $lobby->gameName : null,
        'numPlayers' => intval($lobby->numPlayers),
        'maxPlayers' => intval($lobby->maxPlayers),
        'ready' => boolval($lobby->ready),
        'rootName' => isset($lobby->rootName) ? $lobby->rootName : null,
      ];
    }
  }

  header('Content-Type: application/json');
  echo json_encode($response);

?>

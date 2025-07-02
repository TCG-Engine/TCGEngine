<?php

  require_once "../../Core/NetworkingLibraries.php";
  require_once "../../Core/HTTPLibraries.php";

  $response = new stdClass();
  $response->success = true;
  $response->message = "Successfully fetched lobbies.";

  $response->data = apcu_cache_info();

  header('Content-Type: application/json');
  echo json_encode($response);

?>

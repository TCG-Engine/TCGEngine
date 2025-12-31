<?php

include_once '../Core/HTTPLibraries.php';
include_once '../AccountFiles/AccountSessionAPI.php';
include_once '../AccountFiles/AccountDatabaseAPI.php';
include_once '../Database/ConnectionManager.php';
include_once '../Database/functions.inc.php';
include_once '../Assets/patreon-php-master/src/PatreonLibraries.php';
include_once '../Assets/patreon-php-master/src/PatreonDictionary.php';
include_once 'ElasticSearchHelper.php';

$response = new stdClass();

// Authentication: Check if user is logged in via session
if (!IsUserLoggedIn()) {
  if (isset($_COOKIE["rememberMeToken"])) {
    loginFromCookie();
  }
  if(!IsUserLoggedIn()) {
    $response->error = "You must be logged in to use conversational search";
    echo (json_encode($response));
    exit();
  }
}

// Authorization: Check if user is a patron
if(!IsPatron("12163989")) {
  $response->error = "Support <a href='https://www.patreon.com/OotTheMonk' target='_blank'>my patreon</a> to use conversational card search";
  echo (json_encode($response));
  exit();
}

// Get the user's search request
$usersRequest = TryGet("request", "");
$usersRequest = urldecode($usersRequest);

// Perform the conversational search using the shared helper
$response = PerformConversationalSearch($usersRequest);

echo json_encode($response);

?>
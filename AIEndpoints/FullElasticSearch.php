<?php

include_once '../Core/HTTPLibraries.php';
include_once '../AccountFiles/AccountSessionAPI.php';
include_once '../AccountFiles/AccountDatabaseAPI.php';
include_once '../Database/ConnectionManager.php';
include_once '../Database/functions.inc.php';
include_once '../Assets/patreon-php-master/src/PatreonLibraries.php';
include_once '../Assets/patreon-php-master/src/PatreonDictionary.php';
include_once '../APIKeys/APIKeys.php';

$response = new stdClass();


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

if(!IsPatron("12163989")) {
  $response->error = "Support <a href='https://www.patreon.com/OotTheMonk' target='_blank'>my patreon</a> to use conversational card search";
  echo (json_encode($response));
  exit();
}

$usersRequest = TryGet("request", "");
$usersRequest = urldecode($usersRequest);

if($usersRequest == "") {
  $response->error = "You must provide a request to use conversational search";
  echo (json_encode($response));
  exit();
}

$curl = curl_init();

curl_setopt_array($curl, array(
  CURLOPT_URL => "http://142.11.210.6/es/LLMAPIs.php?request=" . urlencode($usersRequest) . "&key=" . $OTMAIKey,
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_ENCODING => "",
  CURLOPT_MAXREDIRS => 10,
  CURLOPT_TIMEOUT => 5,
  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
  CURLOPT_CUSTOMREQUEST => "GET",
));

$responseData = curl_exec($curl);
$err = curl_error($curl);

curl_close($curl);

if ($err) {
  $response->error = "cURL Error #:" . $err;
} else {
  $responseObj = json_decode($responseData);
  if($responseObj->semanticPostfilter != "N/A") {
    $response->message = "specificCards=" . str_replace(' ', '', $responseObj->semanticPostfilter);
  } else {
    $response->message = str_replace(";", " ", $responseObj->staticFilter);
  }
}

echo json_encode($response);


?>
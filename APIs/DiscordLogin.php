<?php

include_once '../Assets/patreon-php-master/src/OAuth.php';
include_once '../Assets/patreon-php-master/src/API.php';
include_once '../Assets/patreon-php-master/src/PatreonLibraries.php';
include_once '../Assets/patreon-php-master/src/PatreonDictionary.php';
include_once "../Database/ConnectionManager.php";
include_once "../Database/functions.inc.php";
include_once "../APIKeys/APIKeys.php";
include_once "../AccountFiles/AccountSessionAPI.php";
include_once "../APIKeys/APIKeys.php";

if (isset($_GET['code'])) {
  $code = $_GET['code'];

  $client_id = $discordClientID;
  $client_secret = $discordClientSecret;
  $redirect_uri = $discordRedirectURI;

  $code = $_GET['code'];
  $state = $_GET['state'];
  # Check if $state == $_SESSION['state'] to verify if the login is legit | CHECK THE FUNCTION get_state($state) FOR MORE INFORMATION.
  $url = "https://discord.com/api/oauth2/token";
  $data = array(
      "client_id" => $client_id,
      "client_secret" => $client_secret,
      "grant_type" => "authorization_code",
      "code" => $code,
      "redirect_uri" => $redirect_uri
  );
  $curl = curl_init();
  curl_setopt($curl, CURLOPT_URL, $url);
  curl_setopt($curl, CURLOPT_POST, true);
  curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($data));
  curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
  $response = curl_exec($curl);
  curl_close($curl);
  if(curl_errno($curl)){
      echo "cURL error: " . curl_error($curl);
  }
  $results = json_decode($response, true);
  $accessToken = $results['access_token'];

  $url = "https://discord.com/api/users/@me";
  $headers = array('Content-Type: application/x-www-form-urlencoded', 'Authorization: Bearer ' . $accessToken);
  $curl = curl_init();
  curl_setopt($curl, CURLOPT_URL, $url);
  curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
  $response = curl_exec($curl);
  if(curl_errno($curl)){
      echo "cURL error: " . curl_error($curl);
  }
  curl_close($curl);
  
  $results = json_decode($response, true);
  $state = json_decode($state, true);

  $SWUStatsID = $state['userId'];
  $discordID = $results['id'];

  $conn = GetLocalMySQLConnection();
  $query = "UPDATE users SET discordID = ? WHERE usersId = ?";
  $stmt = $conn->prepare($query);
  $stmt->bind_param("si", $discordID, $SWUStatsID);
  $stmt->execute();
  $stmt->close();
  $conn->close();

  CheckSession();
  $_SESSION["discordID"] = $discordID;


}

  header("Location: /TCGEngine/SharedUI/Profile.php");
  exit();



?>

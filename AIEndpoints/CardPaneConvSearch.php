<?php

include_once 'FullElasticSearch.php';

exit;

include_once '../Core/HTTPLibraries.php';
include_once '../AccountFiles/AccountSessionAPI.php';
include_once '../AccountFiles/AccountDatabaseAPI.php';
include_once '../Database/ConnectionManager.php';

$response = new stdClass();

if(!IsUserLoggedIn()) {
  $response->error = "You must be logged in to use conversational search";
  echo (json_encode($response));
  exit();
}

$userName = LoggedInUserName();
if($userName != "OotTheMonk" && $userName != "Brubraz" && $userName != "love" && $userName != "ninin" && $userName != "LoopeeL8NG") {
  $response->error = "You must be an approved user to use conversational search beta";
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

//$usersRequest = "I want to see all cards with a cost of 2 or less and a power of 2 or more.";
//$usersRequest = "Show me all green unit cards with a cost of 3 or less";
//$usersRequest = "Show me upgrades that buff hp";
//$usersRequest = "Show me trooper cards";
//$usersRequest = "blue space guys";

$prompt = "You are a helpful assistant converting natural language to a space delimited series of card filters. Each card has a number of properties. Text properties can use ':' or '=' to search for cards where that property contains that text. Numeric properties can use '=', '<', '>', '>=', or '<='. You can filter by the following properties:
- title (string)
- subtitle (string)
- cardNumber (number)
- cost (number)
- hp (number)
- power (number)
- text (string)
- unique (number)
- upgradeHp (number)
- upgradePower (number)
- aspect (string)
- trait (string)
- arena (string)
- type (string)
- rarity (string)

Valid card types are Unit, Upgrade, Event, Leader, and Base. Other card types are traits.

Valid arenas are Ground and Space.

Valid rarities are Common (C), Uncommon (U), Rare (R), Legendary (L), and Special (S).

Aspects are also associated with colors. Green is called Command. Blue is Vigilance. Red is Aggression. Yellow is Cunning. Black is Villainy. White is Heroism.

Unique is 1 if unique or 0 if not.

Start of user request:
$usersRequest
End of user request.

Output a space delimited list of filters that best matches the user's request and nothing else.";

$apiKey = getenv('OPENAI_API_KEY') ?: "NA";

// Data to send in the request body
$url = "https://api.openai.com/v1/chat/completions";
$data = [
    "model" => "gpt-4o-mini",
    "store" => true,
    "messages" => [
        [
            "role" => "user",
            "content" => $prompt
        ]
    ]
];

// Initialize cURL session
$ch = curl_init();

// Set cURL options
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Content-Type: application/json",
    "Authorization: Bearer $apiKey"
]);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

$currentTime = time();

// Execute the cURL request
$curlResponse = curl_exec($ch);

// Check for errors
if (curl_errno($ch)) {
  $response->error = "Curl error: " . curl_error($ch);
  echo (json_encode($response));
} else {
    // Output the response
    $responseObj = json_decode($curlResponse, true);
    if (isset($responseObj['choices'][0]['message']['content'])) {
      $response->message = $responseObj['choices'][0]['message']['content'];
      echo (json_encode($response));
    } else {
      $response->error = "No message content found";
      echo (json_encode($response));
    }
}

// Close the cURL session
curl_close($ch);
?>
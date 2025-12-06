<?php
include_once(__DIR__ . "/../APIKeys/APIKeys.php");

function OpenAICall($prompt, $model="gpt-4o-mini") {
    $apiKey = OPENAI_API_KEY;
    $rv = "";
    // Data to send in the request body
    $url = "https://api.openai.com/v1/chat/completions";
    $data = [
        "model" => $model,
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
      $rv = "Curl error: " . curl_error($ch);
    } else {
        // Output the response
        $responseObj = json_decode($curlResponse, true);
        if (isset($responseObj['choices'][0]['message']['content'])) {
          $rv = $responseObj['choices'][0]['message']['content'];
        } else {
          $rv = "No message content found";
        }
    }
    
    // Close the cURL session
    curl_close($ch);
    return $rv;
}

?>
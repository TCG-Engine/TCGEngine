<?php
include_once(__DIR__ . "/../APIKeys/APIKeys.php");

// Central model choices for OpenAI call sites. Callers name a purpose, not a
// model id; override any of these per environment with the env var shown.
// Changing a default here should be backed by an eval on real data.
const LLM_MODEL_DEFAULTS = [
    // Keyword extraction from card rules text (Data/ProcessKeywordsRB.php).
    "keywords" => ["env" => "TCG_LLM_MODEL_KEYWORDS", "model" => "gpt-4.1"],
];

function LLMModelFor($purpose) {
    if (!isset(LLM_MODEL_DEFAULTS[$purpose])) {
        throw new InvalidArgumentException("Unknown LLM purpose: $purpose");
    }
    $entry = LLM_MODEL_DEFAULTS[$purpose];
    $override = getenv($entry["env"]);
    return ($override !== false && $override !== "") ? $override : $entry["model"];
}

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
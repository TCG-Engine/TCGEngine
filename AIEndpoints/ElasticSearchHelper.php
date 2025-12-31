<?php

/**
 * Elasticsearch Helper for conversational card search
 * 
 * This helper encapsulates the core Elasticsearch query logic
 * used by both session-based and OAuth-based endpoints.
 */

include_once '../APIKeys/APIKeys.php';

/**
 * Perform a conversational search query against Elasticsearch
 * 
 * @param string $usersRequest The user's search query
 * @return stdClass Response object with 'message' or 'error' property
 */
function PerformConversationalSearch($usersRequest) {
    global $OTMAIKey;
    
    $response = new stdClass();
    
    // Validate input
    if ($usersRequest == "") {
        $response->error = "You must provide a request to use conversational search";
        return $response;
    }
    
    // Make the curl request to the AI endpoint
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
    
    // Handle errors
    if ($err) {
        $response->error = "cURL Error #:" . $err;
        return $response;
    }
    
    // Parse the response
    $responseObj = json_decode($responseData);
    if (!$responseObj) {
        $response->error = "Failed to parse response from AI service";
        return $response;
    }
    
    // Format the response message
    if (isset($responseObj->semanticPostfilter) && $responseObj->semanticPostfilter != "N/A") {
        $response->message = "specificCards=" . str_replace(' ', '', $responseObj->semanticPostfilter);
    } elseif (isset($responseObj->staticFilter)) {
        $response->message = str_replace(";", " ", $responseObj->staticFilter);
    } else {
        $response->error = "Unexpected response format from AI service";
    }
    
    return $response;
}

?>

<?php

/**
 * Conversational Elasticsearch Search - OAuth Endpoint
 * 
 * This endpoint provides conversational card search functionality using OAuth authentication
 * instead of session-based authentication. It uses the same search logic as FullElasticSearch.php
 * but is designed for third-party applications.
 * 
 * Authentication: OAuth 2.0 access token with 'search' scope
 * 
 * Expected Parameters:
 *  - access_token: OAuth access token (via Authorization header or request body)
 *  - request: The user's search query (URL encoded)
 */

include_once '../Core/HTTPLibraries.php';
include_once '../Database/ConnectionManager.php';
include_once '../APIs/OAuth/OAuthServer.php';
include_once 'ElasticSearchHelper.php';

$response = new stdClass();

// Initialize the OAuth server
$server = new OAuthServer();

// Extract access token from Authorization header or request parameters
$bearerToken = null;

// Check Authorization header first (preferred method)
if (isset($_SERVER['HTTP_AUTHORIZATION']) && preg_match('/Bearer\s(\S+)/', $_SERVER['HTTP_AUTHORIZATION'], $matches)) {
    $bearerToken = $matches[1];
}
// Fallback to query parameter
elseif (isset($_GET['access_token'])) {
    $bearerToken = $_GET['access_token'];
}
// Fallback to POST body for JSON requests
elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    if (isset($input['access_token'])) {
        $bearerToken = $input['access_token'];
    }
}

// Verify that we have an access token
if (!$bearerToken) {
    http_response_code(401);
    $response->error = "Access token required. Provide via Authorization header, query parameter, or request body.";
    echo json_encode($response);
    exit();
}

// Verify the access token
$tokenInfo = $server->verifyAccessToken($bearerToken);
if (!$tokenInfo) {
    http_response_code(401);
    $response->error = "Invalid or expired access token";
    echo json_encode($response);
    exit();
}

// Check if the token has the required 'search' scope
$scopes = explode(' ', $tokenInfo['scope']);
if (!in_array('search', $scopes)) {
    http_response_code(403);
    $response->error = "Access token does not have required 'search' scope";
    echo json_encode($response);
    exit();
}

// Get the user's search request
$usersRequest = TryGet("request", "");
$usersRequest = urldecode($usersRequest);

// Perform the conversational search using the shared helper
$response = PerformConversationalSearch($usersRequest);

echo json_encode($response);

?>

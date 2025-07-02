<?php
/**
 * OAuth 2.0 UserInfo Endpoint
 * 
 * This endpoint provides user information to clients that have been
 * authorized by the user and have a valid access token with appropriate scopes.
 */

include_once '../../../Core/HTTPLibraries.php';
include_once '../../../Database/ConnectionManager.php';
include_once './OAuthServer.php';

// Initialize the OAuth server
$server = new OAuthServer();

// Set response content type
header('Content-Type: application/json');

// Check for access token
$bearerToken = null;

// Extract the token from the Authorization header
if (isset($_SERVER['HTTP_AUTHORIZATION']) && preg_match('/Bearer\s(\S+)/', $_SERVER['HTTP_AUTHORIZATION'], $matches)) {
    $bearerToken = $matches[1];
} 
// Fallback to query parameter if no Authorization header is present
else if (isset($_GET['access_token'])) {
    $bearerToken = $_GET['access_token'];
}

if (!$bearerToken) {
    http_response_code(401);
    echo json_encode([
        'error' => 'invalid_token',
        'error_description' => 'Access token required'
    ]);
    exit;
}

// Verify the access token
$tokenInfo = $server->verifyAccessToken($bearerToken);
if (!$tokenInfo) {
    http_response_code(401);
    echo json_encode([
        'error' => 'invalid_token',
        'error_description' => 'The access token is invalid or has expired'
    ]);
    exit;
}

// Get user info based on the token's scope
$userInfo = $server->getUserInfo($tokenInfo['user_id'], $tokenInfo['scope']);

// Return the user info
echo json_encode($userInfo);
?>
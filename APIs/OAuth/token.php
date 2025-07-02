<?php
/**
 * OAuth 2.0 Token Endpoint
 * 
 * This endpoint handles token requests from OAuth clients, including:
 * - Authorization code exchange for access token
 * - Refresh token exchange for new access token
 */

include_once '../../../Core/HTTPLibraries.php';
include_once '../../../Database/ConnectionManager.php';
include_once './OAuthServer.php';

// Initialize the OAuth server
$server = new OAuthServer();

// Set response content type
header('Content-Type: application/json');
header('Cache-Control: no-store');
header('Pragma: no-cache');

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'error' => 'invalid_request',
        'error_description' => 'Method not allowed'
    ]);
    exit;
}

// Get request parameters
$grantType = isset($_POST['grant_type']) ? $_POST['grant_type'] : '';
$clientId = isset($_POST['client_id']) ? $_POST['client_id'] : '';
$clientSecret = isset($_POST['client_secret']) ? $_POST['client_secret'] : '';
$redirectUri = isset($_POST['redirect_uri']) ? $_POST['redirect_uri'] : '';
$code = isset($_POST['code']) ? $_POST['code'] : '';
$refreshToken = isset($_POST['refresh_token']) ? $_POST['refresh_token'] : '';

// Validate client credentials
if (!$server->validateClientCredentials($clientId, $clientSecret)) {
    http_response_code(401);
    echo json_encode([
        'error' => 'invalid_client',
        'error_description' => 'Client authentication failed'
    ]);
    exit;
}

// Handle different grant types
switch ($grantType) {
    case 'authorization_code':
        // Validate required parameters
        if (empty($code) || empty($redirectUri)) {
            http_response_code(400);
            echo json_encode([
                'error' => 'invalid_request',
                'error_description' => 'Missing required parameters'
            ]);
            exit;
        }
        
        // Verify the authorization code
        $codeDetails = $server->verifyAuthCode($code, $clientId, $redirectUri);
        if (!$codeDetails) {
            http_response_code(400);
            echo json_encode([
                'error' => 'invalid_grant',
                'error_description' => 'Invalid authorization code'
            ]);
            exit;
        }
        
        // Create access token
        $tokenResponse = $server->createAccessToken($clientId, $codeDetails['user_id'], $codeDetails['scope']);
        
        // Delete the used authorization code
        $stmt = mysqli_prepare($server->conn, "DELETE FROM oauth_authorization_codes WHERE authorization_code = ?");
        mysqli_stmt_bind_param($stmt, "s", $code);
        mysqli_stmt_execute($stmt);
        
        // Return the token response
        echo json_encode($tokenResponse);
        break;
        
    case 'refresh_token':
        // Validate required parameters
        if (empty($refreshToken)) {
            http_response_code(400);
            echo json_encode([
                'error' => 'invalid_request',
                'error_description' => 'Missing refresh token'
            ]);
            exit;
        }
        
        // Refresh the access token
        $tokenResponse = $server->refreshAccessToken($refreshToken, $clientId);
        if (!$tokenResponse) {
            http_response_code(400);
            echo json_encode([
                'error' => 'invalid_grant',
                'error_description' => 'Invalid refresh token'
            ]);
            exit;
        }
        
        // Return the token response
        echo json_encode($tokenResponse);
        break;
        
    default:
        http_response_code(400);
        echo json_encode([
            'error' => 'unsupported_grant_type',
            'error_description' => 'Grant type not supported'
        ]);
        break;
}
?>
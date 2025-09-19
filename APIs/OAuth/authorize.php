<?php
/**
 * OAuth 2.0 Authorization Endpoint
 * 
 * This endpoint handles the initial authorization request from third-party clients.
 * It presents users with a consent screen and generates authorization codes.
 */

include_once $_SERVER['DOCUMENT_ROOT'] . '/TCGEngine/Core/HTTPLibraries.php';
include_once $_SERVER['DOCUMENT_ROOT'] . '/TCGEngine/AccountFiles/AccountSessionAPI.php';
include_once $_SERVER['DOCUMENT_ROOT'] . '/TCGEngine/AccountFiles/AccountDatabaseAPI.php';
include_once $_SERVER['DOCUMENT_ROOT'] . '/TCGEngine/Database/ConnectionManager.php';
include_once './OAuthServer.php';
include_once '../../Database/ConnectionManager.php';
include_once '../../Database/functions.inc.php';
include_once '../../Assets/patreon-php-master/src/PatreonLibraries.php';
include_once '../../Assets/patreon-php-master/src/PatreonDictionary.php';

// Initialize the OAuth server
$server = new OAuthServer();

// Check if user is logged in
if (!IsUserLoggedIn()) {
    if (isset($_COOKIE["rememberMeToken"])) {
      loginFromCookie();
    }
  }
if (!IsUserLoggedIn()) {
    
    // Store the OAuth request in the session to redirect back after login
    CheckSession();
    $_SESSION['oauth_request'] = $_GET;
    
    // Redirect to login page
    header('Location: /TCGEngine/SharedUI/LoginPage.php?redirect=' . urlencode('/TCGEngine/APIs/OAuth/authorize.php'));
    exit;
}

// Get the logged-in user's ID
$userId = LoggedInUser();

// Validate the request
$responseType = isset($_GET['response_type']) ? $_GET['response_type'] : '';
$clientId = isset($_GET['client_id']) ? $_GET['client_id'] : '';
$redirectUri = isset($_GET['redirect_uri']) ? $_GET['redirect_uri'] : '';
$scope = isset($_GET['scope']) ? $_GET['scope'] : '';
$state = isset($_GET['state']) ? $_GET['state'] : '';

// Error response
$error = null;

// Verify the client exists and the redirect URI is valid
$clientDetails = $server->getClientDetails($clientId);
if (!$clientDetails) {
    $error = 'invalid_client';
} else if ($redirectUri && strpos($clientDetails['redirect_uri'], $redirectUri) === false) {
    $error = 'invalid_request';
} else if ($responseType !== 'code') {
    $error = 'unsupported_response_type';
}

// If there's an error, redirect with error parameter
if ($error) {
    $redirectUrl = $redirectUri ?: $clientDetails['redirect_uri'];
    $redirectUrl .= (strpos($redirectUrl, '?') !== false ? '&' : '?') . 'error=' . $error;
    
    // Add state parameter if provided
    if ($state) {
        $redirectUrl .= '&state=' . urlencode($state);
    }
    
    header('Location: ' . $redirectUrl);
    exit;
}

// Use client's redirect URI if not provided in request
$redirectUri = $clientDetails['redirect_uri'];

// Get and validate scopes
if (empty($scope)) {
    $scope = $server->getDefaultScope();
} else {
    $scope = $server->validateScope($scope);
}

// Handle the form submission (user consent)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['approve'])) {
        // User approved the authorization
        $code = $server->createAuthCode($clientId, $userId, $redirectUri, $scope);
        
        if ($code) {
            $redirectUrl = $redirectUri;
            $redirectUrl .= (strpos($redirectUrl, '?') !== false ? '&' : '?') . 'code=' . $code;
            
            // Add state parameter if provided
            if ($state) {
                $redirectUrl .= '&state=' . urlencode($state);
            }
            
            header('Location: ' . $redirectUrl);
            exit;
        } else {
            $error = 'server_error';
        }
    } else {
        // User denied the authorization
        $redirectUrl = $redirectUri;
        $redirectUrl .= (strpos($redirectUrl, '?') !== false ? '&' : '?') . 'error=access_denied';
        
        // Add state parameter if provided
        if ($state) {
            $redirectUrl .= '&state=' . urlencode($state);
        }
        
        header('Location: ' . $redirectUrl);
        exit;
    }
}

// Display the authorization form
$scopes = explode(' ', $scope);
$scopeDescriptions = [];

foreach ($scopes as $requestedScope) {
    $stmt = mysqli_prepare($server->conn, "SELECT description FROM oauth_scopes WHERE scope = ?");
    mysqli_stmt_bind_param($stmt, "s", $requestedScope);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if ($row = mysqli_fetch_assoc($result)) {
        $scopeDescriptions[$requestedScope] = $row['description'];
    } else {
        $scopeDescriptions[$requestedScope] = "Access to " . $requestedScope;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SWUDeck OAuth Authorization</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #222;
            color: #fff;
        }
        .container {
            max-width: 600px;
            margin: 50px auto;
            padding: 20px;
            background-color: #333;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.5);
        }
        h1 {
            color: #007bff;
            text-align: center;
        }
        .app-info {
            margin-bottom: 20px;
            padding: 15px;
            background-color: #444;
            border-radius: 5px;
        }
        .scopes {
            margin-bottom: 20px;
        }
        .scope-item {
            padding: 10px;
            margin-bottom: 5px;
            background-color: #444;
            border-radius: 3px;
        }
        .buttons {
            display: flex;
            justify-content: space-between;
            gap: 10px;
        }
        button {
            padding: 10px;
            border: none;
            border-radius: 3px;
            cursor: pointer;
            width: 100%;
            font-weight: bold;
        }
        .approve {
            background-color: #007bff;
            color: white;
        }
        .deny {
            background-color: #dc3545;
            color: white;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Authorization Request</h1>
        
        <div class="app-info">
            <h2><?php echo htmlspecialchars($clientDetails['client_name']); ?></h2>
            <p>is requesting access to your SWUDeck account</p>
        </div>
        
        <div class="scopes">
            <h3>This application will be able to:</h3>
            <?php foreach ($scopeDescriptions as $scopeName => $description): ?>
                <div class="scope-item">
                    <strong><?php echo htmlspecialchars($description); ?></strong>
                </div>
            <?php endforeach; ?>
        </div>
        
        <form method="post">
            <div class="buttons">
                <button type="submit" name="approve" class="approve">Approve</button>
                <button type="submit" name="deny" class="deny">Deny</button>
            </div>
        </form>
    </div>
</body>
</html>
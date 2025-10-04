<?php
/**
 * OAuth 2.0 Client Management
 * 
 * This page allows users to register and manage OAuth client applications.
 */

include_once '../../Core/HTTPLibraries.php';
include_once '../../AccountFiles/AccountSessionAPI.php';
include_once '../../AccountFiles/AccountDatabaseAPI.php';
include_once '../../Database/ConnectionManager.php';
include_once './OAuthServer.php';

// Check if user is logged in
if (!IsUserLoggedIn()) {
    header('Location: /TCGEngine/SharedUI/Sites/SWUDeck/LoginPage.php?redirect=' . urlencode('/TCGEngine/APIs/OAuth/manage_clients.php'));
    exit;
}

// Get the logged-in user's ID
$userId = LoggedInUser();
$username = LoggedInUserName();

// Initialize the OAuth server
$server = new OAuthServer();

// Handle form submissions
$message = '';
$error = '';
$newClient = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['register'])) {
        // Register a new client
        $clientName = trim($_POST['client_name']);
        $redirectUri = trim($_POST['redirect_uri']);
        $scope = isset($_POST['scope']) ? implode(' ', $_POST['scope']) : '';
        
        if (empty($clientName) || empty($redirectUri)) {
            $error = 'Application name and redirect URI are required';
        } else {
            $newClient = $server->registerClient($clientName, $redirectUri, $userId, $scope);
            if ($newClient) {
                $message = 'Application registered successfully. Make sure to save your client secret as it won\'t be displayed again.';
            } else {
                $error = 'Failed to register application';
            }
        }
    } else if (isset($_POST['delete'])) {
        // Delete a client
        $clientId = $_POST['client_id'];
        if ($server->deleteClient($clientId, $userId)) {
            $message = 'Application deleted successfully';
        } else {
            $error = 'Failed to delete application';
        }
    }
}

// Get user's clients
$clients = $server->getUserClients($userId);

// Get available scopes for the form
$availableScopes = $server->getScopes();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SWUDeck - Manage OAuth Applications</title>
    <link rel="stylesheet" href="/TCGEngine/SharedUI/css/buttons.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #222;
            color: #fff;
        }
        .container {
            max-width: 1000px;
            margin: 30px auto;
            padding: 20px;
        }
        h1, h2 {
            color: #007bff;
        }
        .card {
            background-color: #333;
            border-radius: 5px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.3);
        }
        .message {
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .success {
            background-color: #28a745;
            color: white;
        }
        .error {
            background-color: #dc3545;
            color: white;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #444;
        }
        th {
            background-color: #444;
        }
        tr:hover {
            background-color: #444;
        }
        form {
            margin-top: 20px;
        }
        label {
            display: block;
            margin: 10px 0 5px;
            font-weight: bold;
        }
        input[type="text"], textarea {
            width: 100%;
            padding: 8px;
            border-radius: 4px;
            border: 1px solid #555;
            background-color: #444;
            color: #fff;
            box-sizing: border-box;
        }
        .scope-options {
            margin: 10px 0;
            display: flex;
            flex-wrap: wrap;
        }
        .scope-option {
            margin-right: 20px;
            margin-bottom: 10px;
        }
        /* Button styles are loaded from /SharedUI/css/buttons.css */
        .client-secret {
            background-color: #444;
            padding: 10px;
            border-radius: 4px;
            margin-top: 10px;
            word-wrap: break-word;
            font-family: monospace;
        }
        .nav {
            background-color: #333;
            padding: 10px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .nav a {
            color: #fff;
            text-decoration: none;
        }
        .hidden {
            display: none;
        }
        #copyMessage {
            color: #28a745;
            margin-left: 10px;
            display: none;
        }
    </style>
</head>
<body>
    <div class="nav">
        <h2>SWUDeck</h2>
        <div>
            <span>Logged in as: <?php echo htmlspecialchars($username); ?></span>
            <a href="/TCGEngine/SharedUI/Profile.php" style="margin-left: 15px;">Profile</a>
        </div>
    </div>
    
    <div class="container">
        <h1>Manage OAuth Applications</h1>
        
        <?php if ($message): ?>
            <div class="message success"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="message error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <?php if ($newClient): ?>
            <div class="card">
                <h2>New Application Credentials</h2>
                <p><strong>Important:</strong> Please save your client secret now. It will not be displayed again.</p>
                
                <div>
                    <strong>Client ID:</strong>
                    <div class="client-secret" id="clientId"><?php echo htmlspecialchars($newClient['client_id']); ?></div>
                    <button class="primary" onclick="copyToClipboard('clientId')">Copy Client ID</button>
                    <span id="copyIdMessage" class="hidden">Copied!</span>
                </div>
                
                <div style="margin-top: 20px;">
                    <strong>Client Secret:</strong>
                    <div class="client-secret" id="clientSecret"><?php echo htmlspecialchars($newClient['client_secret']); ?></div>
                    <button class="primary" onclick="copyToClipboard('clientSecret')">Copy Client Secret</button>
                    <span id="copySecretMessage" class="hidden">Copied!</span>
                </div>
            </div>
        <?php endif; ?>
        
        <div class="card">
            <h2>Your Applications</h2>
            <?php if (empty($clients)): ?>
                <p>You haven't registered any applications yet.</p>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Client ID</th>
                            <th>Redirect URI</th>
                            <th>Scope</th>
                            <th>Created</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($clients as $client): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($client['client_name']); ?></td>
                                <td><?php echo htmlspecialchars($client['client_id']); ?></td>
                                <td><?php echo htmlspecialchars($client['redirect_uri']); ?></td>
                                <td><?php echo htmlspecialchars($client['scope']); ?></td>
                                <td><?php echo htmlspecialchars($client['created_at']); ?></td>
                                <td>
                                    <form method="post" onsubmit="return confirm('Are you sure you want to delete this application?');">
                                        <input type="hidden" name="client_id" value="<?php echo htmlspecialchars($client['client_id']); ?>">
                                        <button type="submit" name="delete" class="danger">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
        
        <div class="card">
            <h2>Register New Application</h2>
            <form method="post">
                <div>
                    <label for="client_name">Application Name:</label>
                    <input type="text" id="client_name" name="client_name" required>
                </div>
                
                <div>
                    <label for="redirect_uri">Redirect URI:</label>
                    <input type="text" id="redirect_uri" name="redirect_uri" required placeholder="https://example.com/callback">
                    <small style="display: block; margin-top: 5px; color: #aaa;">The URI where users will be redirected after authorization.</small>
                </div>
                
                <div>
                    <label>Requested Scopes:</label>
                    <div class="scope-options">
                        <?php foreach ($availableScopes as $scope): ?>
                            <div class="scope-option">
                                <input type="checkbox" id="scope_<?php echo $scope['scope']; ?>" name="scope[]" value="<?php echo $scope['scope']; ?>" <?php echo $scope['is_default'] ? 'checked' : ''; ?>>
                                <label for="scope_<?php echo $scope['scope']; ?>" style="display: inline; font-weight: normal;">
                                    <?php echo $scope['scope']; ?> - <?php echo $scope['description']; ?>
                                </label>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <button type="submit" name="register" class="primary">Register Application</button>
            </form>
        </div>
        
        <div class="card">
            <h2>OAuth Integration Guide</h2>
            <p>Follow these steps to integrate your application with SWUDeck:</p>
            
            <ol>
                <li>Register your application using the form above. This will provide you with a client ID and client secret.</li>
                <li>Implement the OAuth 2.0 authorization code flow in your application:</li>
                <ul>
                    <li><strong>Authorization endpoint:</strong> <code>/TCGEngine/APIs/OAuth/authorize.php</code></li>
                    <li><strong>Token endpoint:</strong> <code>/TCGEngine/APIs/OAuth/token.php</code></li>
                    <li><strong>User info endpoint:</strong> <code>/TCGEngine/APIs/OAuth/userinfo.php</code></li>
                </ul>
                <li>Direct users to the authorization endpoint with your client ID, redirect URI, and requested scope.</li>
                <li>After the user authorizes your app, they will be redirected back to your application with an authorization code.</li>
                <li>Exchange the code for an access token using the token endpoint.</li>
                <li>Use the access token to make API requests to access the user's information.</li>
            </ol>
            
            <p>For more detailed implementation instructions, please refer to the <a href="#" style="color: #007bff;">API documentation</a>.</p>
        </div>
    </div>
    
    <script>
    function copyToClipboard(elementId) {
        const element = document.getElementById(elementId);
        const text = element.innerText;
        const messageId = elementId === 'clientId' ? 'copyIdMessage' : 'copySecretMessage';
        const message = document.getElementById(messageId);
        
        navigator.clipboard.writeText(text).then(() => {
            message.classList.remove('hidden');
            message.style.display = 'inline';
            
            setTimeout(() => {
                message.style.display = 'none';
            }, 2000);
        });
    }
    </script>
</body>
</html>
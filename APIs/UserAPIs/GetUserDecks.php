<?php
/**
 * Get User Decks API
 * 
 * This endpoint provides a list of decks owned by the user,
 * including those marked as favorites. It requires OAuth
 * authentication with the 'decks' scope.
 */

include_once '../../Core/HTTPLibraries.php';
include_once '../../Database/ConnectionManager.php';
include_once '../OAuth/OAuthServer.php';

// Initialize the OAuth server
$server = new OAuthServer();

// Set up logging
$logFile = '../../logs/api_access.log';
$logDir = dirname($logFile);
if (!file_exists($logDir)) {
    mkdir($logDir, 0755, true);
}

function writeLog($message) {
    global $logFile, $server;
    // Only log if debug mode is enabled
    if (!$server->debugMode) return;
    
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[$timestamp] $message\n";
    file_put_contents($logFile, $logMessage, FILE_APPEND);
}

// Log request details
writeLog("API Call: GetUserDecks.php");
writeLog("Request Method: " . $_SERVER['REQUEST_METHOD']);
writeLog("User Agent: " . ($_SERVER['HTTP_USER_AGENT'] ?? 'Not provided'));
writeLog("Request Headers: " . json_encode(getallheaders()));
writeLog("GET Params: " . json_encode($_GET));

// Set response content type
header('Content-Type: application/json');
header('Cache-Control: no-store');
header('Pragma: no-cache');

// Check for access token
$bearerToken = null;
$refreshToken = null;

// Function to get all headers in a case-insensitive way
function getAuthorizationHeader() {
    $headers = null;
    
    // Check for direct Apache headers
    if (isset($_SERVER['Authorization'])) {
        return $_SERVER['Authorization'];
    }
    
    // Check normalized headers
    if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
        return $_SERVER['HTTP_AUTHORIZATION'];
    }
    
    // Try to get from getAllHeaders or apache_request_headers
    if (function_exists('getallheaders')) {
        $headers = getallheaders();
    } elseif (function_exists('apache_request_headers')) {
        $headers = apache_request_headers();
    }
    
    // Headers array exists
    if (!empty($headers)) {
        // Check with various case variations
        $authHeaders = ['authorization', 'Authorization', 'AUTHORIZATION'];
        foreach ($authHeaders as $header) {
            if (isset($headers[$header])) {
                return $headers[$header];
            }
        }
    }
    
    // Try to get from REDIRECT_HTTP_AUTHORIZATION (Apache CGI/FastCGI specific)
    if (isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
        return $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
    }
    
    return null;
}

// Log server variables
writeLog("SERVER variables relevant to auth: " . 
    json_encode(array_filter($_SERVER, function($key) {
        return strpos(strtolower($key), 'auth') !== false;
    }, ARRAY_FILTER_USE_KEY)));

// Get the authorization header
$authHeader = getAuthorizationHeader();
writeLog("Authorization header found: " . ($authHeader ? 'YES' : 'NO'));

if ($authHeader && preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
    $bearerToken = $matches[1];
    writeLog("Token found in Authorization header: " . substr($bearerToken, 0, 10) . '...');
} 
// Fallback to query parameter if no Authorization header is present
else if (isset($_GET['access_token'])) {
    $bearerToken = $_GET['access_token'];
    writeLog("Token found in query parameter: " . substr($bearerToken, 0, 10) . '...');
}
// Check for refresh token in parameters
if (isset($_GET['refresh_token'])) {
    $refreshToken = $_GET['refresh_token'];
    writeLog("Refresh token found in parameters");
}

if (!$bearerToken) {
    writeLog("ERROR: No access token found");
    http_response_code(401);
    echo json_encode([
        'error' => 'invalid_token',
        'error_description' => 'Access token required',
        'hint' => 'Include a valid access token in the Authorization header or access_token parameter'
    ]);
    exit;
}

// Attempt to verify the access token
$tokenInfo = $server->verifyAccessToken($bearerToken);

// If token is not valid and a refresh token is provided, try to refresh
if (!$tokenInfo && $refreshToken) {
    writeLog("Access token invalid or expired, attempting to use refresh token");
    
    // Try to find client ID associated with the expired access token
    $stmt = mysqli_prepare($server->conn, "SELECT client_id FROM oauth_access_tokens WHERE access_token = ?");
    if (!$stmt) {
        writeLog("Error preparing client_id lookup statement: " . mysqli_error($server->conn));
    } else {
        mysqli_stmt_bind_param($stmt, "s", $bearerToken);
        $execResult = mysqli_stmt_execute($stmt);
        
        if (!$execResult) {
            writeLog("Error executing client_id lookup: " . mysqli_error($server->conn));
        } else {
            $result = mysqli_stmt_get_result($stmt);
            writeLog("Client ID lookup query returned " . mysqli_num_rows($result) . " rows");
            
            if ($row = mysqli_fetch_assoc($result)) {
                $clientId = $row['client_id'];
                writeLog("Found client ID for expired token: " . $clientId);
                
                // Try to refresh the token
                $newTokens = $server->refreshAccessToken($refreshToken, $clientId);
                if ($newTokens) {
                    writeLog("Successfully refreshed token");
                    
                    // Use the new access token
                    $bearerToken = $newTokens['access_token'];
                    $tokenInfo = $server->verifyAccessToken($bearerToken);
                    
                    // Return the new tokens in the response headers
                    header('X-New-Access-Token: ' . $newTokens['access_token']);
                    header('X-New-Refresh-Token: ' . $newTokens['refresh_token']);
                    header('X-Token-Expires-In: ' . $newTokens['expires_in']);
                } else {
                    writeLog("Failed to refresh token");
                }
            } else {
                writeLog("ERROR: Could not find client ID for the expired access token");
                
                // Try a fallback - maybe we can extract the client ID from another source
                if (isset($_GET['client_id'])) {
                    $clientId = $_GET['client_id'];
                    writeLog("Using client_id provided in request parameters: " . $clientId);
                    
                    // Try to refresh with the provided client ID
                    $newTokens = $server->refreshAccessToken($refreshToken, $clientId);
                    if ($newTokens) {
                        writeLog("Successfully refreshed token using provided client_id");
                        
                        // Use the new access token
                        $bearerToken = $newTokens['access_token'];
                        $tokenInfo = $server->verifyAccessToken($bearerToken);
                        
                        // Return the new tokens in the response headers
                        header('X-New-Access-Token: ' . $newTokens['access_token']);
                        header('X-New-Refresh-Token: ' . $newTokens['refresh_token']);
                        header('X-Token-Expires-In: ' . $newTokens['expires_in']);
                    } else {
                        writeLog("Failed to refresh token even with provided client_id");
                    }
                } else {
                    writeLog("No client_id parameter provided in request");
                }
            }
        }
    }
}

// If we still have no valid token, return error
if (!$tokenInfo) {
    writeLog("ERROR: Invalid or expired access token");
    http_response_code(401);
    echo json_encode([
        'error' => 'invalid_token',
        'error_description' => 'The access token is invalid or has expired',
        'hint' => 'Use your refresh token to obtain a new access token via the /token endpoint',
        'expire_status' => 'expired'
    ]);
    exit;
}

writeLog("Token verified successfully for user ID: " . $tokenInfo['user_id']);

// Check if the token has the 'decks' scope
$scopes = explode(' ', $tokenInfo['scope']);
if (!in_array('decks', $scopes)) {
    writeLog("ERROR: Missing required scope 'decks'. Available scopes: " . $tokenInfo['scope']);
    http_response_code(403);
    echo json_encode([
        'error' => 'insufficient_scope',
        'error_description' => 'The access token does not have the required scope (decks)'
    ]);
    exit;
}

$userId = $tokenInfo['user_id'];
$conn = GetLocalMySQLConnection();

// Get parameters for filtering/sorting
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 100;
$offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'name'; // Options: name, date
$order = isset($_GET['order']) && strtolower($_GET['order']) === 'desc' ? 'DESC' : 'ASC';
$favorites_only = isset($_GET['favorites']) && $_GET['favorites'] === 'true';

// Validate sort parameter
if (!in_array($sort, ['name', 'date'])) {
    $sort = 'name';
}

// Build the query based on the assetFolder column (favorites)
$sql = "SELECT 
            o.assetIdentifier as id, 
            o.assetName as name, 
            o.assetVisibility as visibility, 
            o.assetFolder as folder,
            (o.assetFolder = 1) as is_favorite,
            o.numLikes as likes,
            o.keyIndicator1,
            o.keyIndicator2 
        FROM ownership o 
        WHERE o.assetOwner = ? AND o.assetType = 1 AND o.assetStatus != 0";

// Add favorites filter if requested
if ($favorites_only) {
    $sql .= " AND o.assetFolder = 1";
    writeLog("Filtering to show only favorited decks (assetFolder = 1)");
}

// Add sorting
if ($sort === 'name') {
    $sql .= " ORDER BY o.assetName " . $order;
} else if ($sort === 'date') {
    // Use assetIdentifier as a proxy for creation date since that likely indicates deck age
    // (assuming higher IDs are newer)
    $sql .= " ORDER BY o.assetIdentifier " . $order;
}

// Add limits
$sql .= " LIMIT ? OFFSET ?";

// Prepare and execute the statement
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "iii", $userId, $limit, $offset);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$decks = [];
while ($row = mysqli_fetch_assoc($result)) {
    // Parse metadata if it exists
    if (!empty($row['metadata'])) {
        try {
            $metadata = json_decode($row['metadata'], true);
            if ($metadata && is_array($metadata)) {
                // Add relevant metadata to the deck object
                foreach ($metadata as $key => $value) {
                    $row[$key] = $value;
                }
            }
        } catch (Exception $e) {
            // Ignore errors in JSON parsing
        }
    }
    
    // Remove the raw metadata from the response to clean it up
    unset($row['metadata']);
    
    // Convert the boolean flag for favorites
    $row['is_favorite'] = (bool)$row['is_favorite'];
    
    $decks[] = $row;
}

// Get total count for pagination
if ($favorites_only) {
    $countSql = "SELECT COUNT(*) as total FROM ownership o WHERE o.assetOwner = ? AND o.assetType = 1 AND o.assetFolder = 1 AND o.assetStatus != 0";
} else {
    $countSql = "SELECT COUNT(*) as total FROM ownership o WHERE o.assetOwner = ? AND o.assetType = 1 AND o.assetStatus != 0";
}
$countStmt = mysqli_prepare($conn, $countSql);
mysqli_stmt_bind_param($countStmt, "i", $userId);
mysqli_stmt_execute($countStmt);
$countResult = mysqli_stmt_get_result($countStmt);
$countRow = mysqli_fetch_assoc($countResult);
$total = $countRow['total'];

// Prepare pagination info
$pagination = [
    'total' => (int)$total,
    'limit' => $limit,
    'offset' => $offset,
    'has_more' => ($offset + $limit < $total)
];

// Return the decks and pagination info
echo json_encode([
    'decks' => $decks,
    'pagination' => $pagination
]);

// Close the database connection
mysqli_close($conn);
?>
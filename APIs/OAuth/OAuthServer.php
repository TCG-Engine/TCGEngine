<?php
/**
 * OAuth 2.0 Server Implementation for SWUDeck
 * 
 * This file contains the core OAuth server functionality for SWUDeck
 * allowing third-party applications to authenticate with SWUDeck.
 */

include_once '../../Core/HTTPLibraries.php';
include_once '../../AccountFiles/AccountSessionAPI.php';
include_once '../../Database/ConnectionManager.php';

class OAuthServer {
    public $conn; // Changed from private to public to allow access from authorize.php
    private $requestType;
    private $responseType;
    public $debugMode = false; // Debug mode flag, set to false by default
    
    /**
     * Initialize the OAuth server
     */
    public function __construct() {
        $this->conn = GetLocalMySQLConnection();
        $this->requestType = $_SERVER['REQUEST_METHOD'];
        $this->responseType = 'json'; // Default response type
    }
    
    /**
     * Clean up resources
     */
    public function __destruct() {
        if ($this->conn) {
            mysqli_close($this->conn);
        }
    }
    
    /**
     * Generate a secure random token
     * 
     * @param int $length Length of the token
     * @return string Generated token
     */
    public function generateToken($length = 40) {
        return bin2hex(random_bytes($length / 2));
    }
    
    /**
     * Validate a client's credentials
     * 
     * @param string $clientId Client ID
     * @param string $clientSecret Client secret
     * @return bool True if valid, false otherwise
     */
    public function validateClientCredentials($clientId, $clientSecret) {
        $stmt = mysqli_prepare($this->conn, "SELECT client_secret FROM oauth_clients WHERE client_id = ?");
        mysqli_stmt_bind_param($stmt, "s", $clientId);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if ($row = mysqli_fetch_assoc($result)) {
            // Verify client secret using constant time comparison to prevent timing attacks
            return hash_equals($row['client_secret'], $clientSecret);
        }
        
        return false;
    }
    
    /**
     * Get client details by client ID
     * 
     * @param string $clientId Client ID
     * @return array|bool Client details or false if not found
     */
    public function getClientDetails($clientId) {
        $stmt = mysqli_prepare($this->conn, "SELECT * FROM oauth_clients WHERE client_id = ?");
        mysqli_stmt_bind_param($stmt, "s", $clientId);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if ($row = mysqli_fetch_assoc($result)) {
            return $row;
        }
        
        return false;
    }
    
    /**
     * Create a new authorization code
     * 
     * @param string $clientId Client ID
     * @param int $userId User ID
     * @param string $redirectUri Redirect URI
     * @param string $scope Requested scope
     * @return string|bool Authorization code or false on failure
     */
    public function createAuthCode($clientId, $userId, $redirectUri, $scope = '') {
        $code = $this->generateToken();
        $expires = date('Y-m-d H:i:s', time() + 600); // Code expires in 10 minutes
        
        $stmt = mysqli_prepare($this->conn, 
            "INSERT INTO oauth_authorization_codes (authorization_code, client_id, user_id, redirect_uri, expires, scope) 
             VALUES (?, ?, ?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt, "ssssss", $code, $clientId, $userId, $redirectUri, $expires, $scope);
        
        if (mysqli_stmt_execute($stmt)) {
            return $code;
        }
        
        return false;
    }
    
    /**
     * Verify an authorization code
     * 
     * @param string $code Authorization code
     * @param string $clientId Client ID
     * @param string $redirectUri Redirect URI
     * @return array|bool Code details or false if invalid
     */
    public function verifyAuthCode($code, $clientId, $redirectUri = null) {
        $stmt = mysqli_prepare($this->conn, 
            "SELECT * FROM oauth_authorization_codes 
             WHERE authorization_code = ? AND client_id = ? AND expires > NOW()");
        mysqli_stmt_bind_param($stmt, "ss", $code, $clientId);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if ($row = mysqli_fetch_assoc($result)) {
            if ($redirectUri === null || $row['redirect_uri'] == $redirectUri) {
                return $row;
            }
        }
        
        return false;
    }
    
    /**
     * Create an access token
     * 
     * @param string $clientId Client ID
     * @param int $userId User ID
     * @param string $scope Requested scope
     * @return array Token details
     */
    public function createAccessToken($clientId, $userId, $scope = '') {
        $accessToken = $this->generateToken();
        $refreshToken = $this->generateToken();
        $expires = date('Y-m-d H:i:s', time() + 3600); // Token expires in 1 hour
        $refreshExpires = date('Y-m-d H:i:s', time() + 2592000); // Refresh token expires in 30 days
        
        // Create access token
        $stmt = mysqli_prepare($this->conn, 
            "INSERT INTO oauth_access_tokens (access_token, client_id, user_id, expires, scope) 
             VALUES (?, ?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt, "sssss", $accessToken, $clientId, $userId, $expires, $scope);
        mysqli_stmt_execute($stmt);
        
        // Create refresh token
        $stmt = mysqli_prepare($this->conn, 
            "INSERT INTO oauth_refresh_tokens (refresh_token, client_id, user_id, expires, scope) 
             VALUES (?, ?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt, "sssss", $refreshToken, $clientId, $userId, $refreshExpires, $scope);
        mysqli_stmt_execute($stmt);
        
        return [
            'access_token' => $accessToken,
            'refresh_token' => $refreshToken,
            'token_type' => 'Bearer',
            'expires_in' => 3600,
            'scope' => $scope
        ];
    }
    
    /**
     * Refresh an access token
     * 
     * @param string $refreshToken Refresh token
     * @param string $clientId Client ID
     * @return array|bool New token details or false on failure
     */
    public function refreshAccessToken($refreshToken, $clientId) {
        $this->logOAuth("Attempting to refresh token. Refresh Token: " . substr($refreshToken, 0, 10) . "... | Client ID: " . substr($clientId, 0, 10) . "...");
        
        // First check if the refresh token exists at all (ignoring expiration)
        $tokenExistsSql = "SELECT refresh_token, expires, client_id, user_id FROM oauth_refresh_tokens WHERE refresh_token = ?";
        $this->logOAuth("Checking if refresh token exists: " . $tokenExistsSql);
        
        $existsStmt = mysqli_prepare($this->conn, $tokenExistsSql);
        if (!$existsStmt) {
            $this->logOAuth("Error preparing token exists statement: " . mysqli_error($this->conn));
            return false;
        }
        
        mysqli_stmt_bind_param($existsStmt, "s", $refreshToken);
        $existsResult = mysqli_stmt_execute($existsStmt);
        
        if (!$existsResult) {
            $this->logOAuth("Error executing token exists query: " . mysqli_error($this->conn));
            return false;
        }
        
        $existsResultSet = mysqli_stmt_get_result($existsStmt);
        $tokenExists = mysqli_num_rows($existsResultSet) > 0;
        
        if ($tokenExists) {
            $tokenData = mysqli_fetch_assoc($existsResultSet);
            $this->logOAuth("Refresh token found in database:");
            $this->logOAuth("  - Client ID: " . $tokenData['client_id']);
            $this->logOAuth("  - User ID: " . $tokenData['user_id']);
            $this->logOAuth("  - Expires: " . $tokenData['expires']);
            $this->logOAuth("  - Current time: " . date('Y-m-d H:i:s'));
            
            $tokenClientId = $tokenData['client_id'];
            $tokenExpires = strtotime($tokenData['expires']);
            $now = time();
            
            if ($tokenClientId !== $clientId) {
                $this->logOAuth("ERROR: Client ID mismatch! Token's client ID: " . $tokenClientId . ", Provided client ID: " . $clientId);
            }
            
            if ($tokenExpires < $now) {
                $this->logOAuth("ERROR: Refresh token has expired. Expired at: " . date('Y-m-d H:i:s', $tokenExpires));
            }
        } else {
            $this->logOAuth("ERROR: Refresh token does not exist in the database.");
        }
        
        // Now proceed with the actual refresh operation
        $sql = "SELECT * FROM oauth_refresh_tokens WHERE refresh_token = ? AND client_id = ? AND expires > NOW()";
        $this->logOAuth("Executing full refresh query: " . $sql);
        
        $stmt = mysqli_prepare($this->conn, $sql);
        if (!$stmt) {
            $this->logOAuth("Error preparing refresh token statement: " . mysqli_error($this->conn));
            return false;
        }
        
        mysqli_stmt_bind_param($stmt, "ss", $refreshToken, $clientId);
        $execResult = mysqli_stmt_execute($stmt);
        
        if (!$execResult) {
            $this->logOAuth("Error executing refresh token statement: " . mysqli_error($this->conn));
            return false;
        }
        
        $result = mysqli_stmt_get_result($stmt);
        if (!$result) {
            $this->logOAuth("No result from refresh token query. Error: " . mysqli_error($this->conn));
            return false;
        }
        
        $this->logOAuth("Refresh token query returned " . mysqli_num_rows($result) . " rows");
        
        if ($row = mysqli_fetch_assoc($result)) {
            $userId = $row['user_id'];
            $scope = $row['scope'];
            $this->logOAuth("Found valid refresh token for user ID: " . $userId);
            
            // Revoke the old refresh token
            $stmt = mysqli_prepare($this->conn, "DELETE FROM oauth_refresh_tokens WHERE refresh_token = ?");
            if (!$stmt) {
                $this->logOAuth("Error preparing delete refresh token statement: " . mysqli_error($this->conn));
                return false;
            }
            
            mysqli_stmt_bind_param($stmt, "s", $refreshToken);
            $deleteResult = mysqli_stmt_execute($stmt);
            
            if (!$deleteResult) {
                $this->logOAuth("Error deleting old refresh token: " . mysqli_error($this->conn));
                // Continue anyway since this is not critical
            } else {
                $this->logOAuth("Successfully deleted old refresh token");
            }
            
            // Create a new token
            $newTokens = $this->createAccessToken($clientId, $userId, $scope);
            $this->logOAuth("Created new access token: " . substr($newTokens['access_token'], 0, 10) . "...");
            return $newTokens;
        } else {
            $this->logOAuth("No valid refresh token found matching all criteria");
            return false;
        }
    }
    
    /**
     * Verify an access token
     * 
     * @param string $accessToken Access token
     * @return array|bool Token details or false if invalid
     */
    public function verifyAccessToken($accessToken) {
        // Set up logging
        $logFile = '../../logs/oauth_debug.log';
        $logDir = dirname($logFile);
        if (!file_exists($logDir)) {
            mkdir($logDir, 0755, true);
        }
        
        $this->logOAuth("Verifying access token: " . substr($accessToken, 0, 10) . '...');
        $this->logOAuth("Executing query: SELECT * FROM oauth_access_tokens WHERE access_token = ?");
        
        $stmt = mysqli_prepare($this->conn, "SELECT * FROM oauth_access_tokens WHERE access_token = ?");
        if (!$stmt) {
            $this->logOAuth("Error preparing statement: " . mysqli_error($this->conn));
            return false;
        }
        
        mysqli_stmt_bind_param($stmt, "s", $accessToken);
        $execResult = mysqli_stmt_execute($stmt);
        
        if (!$execResult) {
            $this->logOAuth("Error executing statement: " . mysqli_error($this->conn));
            return false;
        }
        
        $result = mysqli_stmt_get_result($stmt);
        if (!$result) {
            $this->logOAuth("No result from query. Error: " . mysqli_error($this->conn));
            return false;
        }
        
        $this->logOAuth("Rows found: " . mysqli_num_rows($result));
        
        if ($row = mysqli_fetch_assoc($result)) {
            // Check if token has expired
            $expires = strtotime($row['expires']);
            $now = time();
            $this->logOAuth("Token expire time: " . date('Y-m-d H:i:s', $expires) . " | Current time: " . date('Y-m-d H:i:s', $now));
            
            if ($expires > $now) {
                $this->logOAuth("Token is valid and not expired");
                return $row;
            } else {
                $this->logOAuth("Token has expired");
                return false;
            }
        } else {
            $this->logOAuth("Token not found in database");
            return false;
        }
    }
    
    /**
     * Log OAuth debug information
     * 
     * @param string $message Message to log
     */
    private function logOAuth($message) {
        // Only log if debug mode is enabled
        if (!$this->debugMode) return;
        
        $logFile = '../../logs/oauth_debug.log';
        $timestamp = date('Y-m-d H:i:s');
        $logMessage = "[$timestamp] $message\n";
        file_put_contents($logFile, $logMessage, FILE_APPEND);
    }
    
    /**
     * Get available scopes
     * 
     * @param bool $defaultsOnly Only return default scopes
     * @return array List of available scopes
     */
    public function getScopes($defaultsOnly = false) {
        $sql = "SELECT * FROM oauth_scopes";
        if ($defaultsOnly) {
            $sql .= " WHERE is_default = 1";
        }
        
        $result = mysqli_query($this->conn, $sql);
        $scopes = [];
        
        while ($row = mysqli_fetch_assoc($result)) {
            $scopes[] = $row;
        }
        
        return $scopes;
    }
    
    /**
     * Validate requested scopes
     * 
     * @param string $scope Requested scope
     * @return string Valid scope string
     */
    public function validateScope($scope) {
        $requestedScopes = explode(' ', $scope);
        $validScopes = [];
        
        // Get all available scopes from database
        $result = mysqli_query($this->conn, "SELECT scope FROM oauth_scopes");
        $availableScopes = [];
        
        while ($row = mysqli_fetch_assoc($result)) {
            $availableScopes[] = $row['scope'];
        }
        
        // Check each requested scope
        foreach ($requestedScopes as $requestedScope) {
            if (in_array($requestedScope, $availableScopes)) {
                $validScopes[] = $requestedScope;
            }
        }
        
        return implode(' ', $validScopes);
    }
    
    /**
     * Get default scope if none specified
     * 
     * @return string Default scope string
     */
    public function getDefaultScope() {
        $result = mysqli_query($this->conn, "SELECT scope FROM oauth_scopes WHERE is_default = 1");
        $scopes = [];
        
        while ($row = mysqli_fetch_assoc($result)) {
            $scopes[] = $row['scope'];
        }
        
        return implode(' ', $scopes);
    }
    
    /**
     * Get user information based on scope
     * 
     * @param int $userId User ID
     * @param string $scope Requested scope
     * @return array User information
     */
    public function getUserInfo($userId, $scope) {
        $scopes = explode(' ', $scope);
        $userInfo = [];
        
        // Get basic user data
        $stmt = mysqli_prepare($this->conn, "SELECT usersId, usersUid, usersEmail FROM users WHERE usersId = ?");
        mysqli_stmt_bind_param($stmt, "i", $userId);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $userData = mysqli_fetch_assoc($result);
        
        // Include data based on scope
        if (in_array('profile', $scopes)) {
            $userInfo['id'] = $userData['usersId'];
            $userInfo['username'] = $userData['usersUid'];
        }
        
        if (in_array('email', $scopes)) {
            $userInfo['email'] = $userData['usersEmail'];
        }
        
        if (in_array('decks', $scopes)) {
            // Get user's decks
            $stmt = mysqli_prepare($this->conn, 
                "SELECT * FROM ownership 
                 WHERE assetOwner = ? AND assetType = 1 AND assetVisibility > 0");
            mysqli_stmt_bind_param($stmt, "i", $userId);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            
            $userInfo['decks'] = [];
            while ($row = mysqli_fetch_assoc($result)) {
                $userInfo['decks'][] = [
                    'id' => $row['assetIdentifier'],
                    'name' => $row['assetName']
                ];
            }
        }
        
        if (in_array('stats', $scopes)) {
            // Get user's stats (basic implementation)
            $stmt = mysqli_prepare($this->conn, 
                "SELECT COUNT(*) as total_games, 
                        SUM(CASE WHEN WinningPID = ? THEN 1 ELSE 0 END) as won_games
                 FROM completedgame 
                 WHERE WinningPID = ? OR LosingPID = ?");
            mysqli_stmt_bind_param($stmt, "iii", $userId, $userId, $userId);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $statsData = mysqli_fetch_assoc($result);
            
            $userInfo['stats'] = [
                'total_games' => $statsData['total_games'],
                'won_games' => $statsData['won_games'],
                'win_rate' => $statsData['total_games'] > 0 ? 
                    round(($statsData['won_games'] / $statsData['total_games']) * 100, 2) : 0
            ];
        }
        
        return $userInfo;
    }
    
    /**
     * Register a new OAuth client
     * 
     * @param string $clientName Client name
     * @param string $redirectUri Redirect URI
     * @param int $userId User ID of the owner
     * @param string $scope Allowed scope
     * @return array Client details including ID and secret
     */
    public function registerClient($clientName, $redirectUri, $userId, $scope = '') {
        $clientId = $this->generateToken(24);
        $clientSecret = $this->generateToken();
        
        $stmt = mysqli_prepare($this->conn, 
            "INSERT INTO oauth_clients (client_id, client_secret, client_name, redirect_uri, grant_types, scope, user_id) 
             VALUES (?, ?, ?, ?, ?, ?, ?)");
        
        $grantTypes = 'authorization_code refresh_token';
        mysqli_stmt_bind_param($stmt, "ssssssi", $clientId, $clientSecret, $clientName, $redirectUri, $grantTypes, $scope, $userId);
        
        if (mysqli_stmt_execute($stmt)) {
            return [
                'client_id' => $clientId,
                'client_secret' => $clientSecret,
                'client_name' => $clientName,
                'redirect_uri' => $redirectUri
            ];
        }
        
        return false;
    }
    
    /**
     * Get clients registered by a user
     * 
     * @param int $userId User ID
     * @return array List of clients
     */
    public function getUserClients($userId) {
        $stmt = mysqli_prepare($this->conn, "SELECT client_id, client_name, redirect_uri, scope, created_at FROM oauth_clients WHERE user_id = ?");
        mysqli_stmt_bind_param($stmt, "i", $userId);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        $clients = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $clients[] = $row;
        }
        
        return $clients;
    }
    
    /**
     * Delete a client
     * 
     * @param string $clientId Client ID
     * @param int $userId User ID (for verification)
     * @return bool True on success, false on failure
     */
    public function deleteClient($clientId, $userId) {
        // Verify ownership
        $stmt = mysqli_prepare($this->conn, "SELECT user_id FROM oauth_clients WHERE client_id = ?");
        mysqli_stmt_bind_param($stmt, "s", $clientId);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($result);
        
        if (!$row || $row['user_id'] != $userId) {
            return false;
        }
        
        // Delete related tokens first
        $stmt = mysqli_prepare($this->conn, "DELETE FROM oauth_access_tokens WHERE client_id = ?");
        mysqli_stmt_bind_param($stmt, "s", $clientId);
        mysqli_stmt_execute($stmt);
        
        $stmt = mysqli_prepare($this->conn, "DELETE FROM oauth_refresh_tokens WHERE client_id = ?");
        mysqli_stmt_bind_param($stmt, "s", $clientId);
        mysqli_stmt_execute($stmt);
        
        $stmt = mysqli_prepare($this->conn, "DELETE FROM oauth_authorization_codes WHERE client_id = ?");
        mysqli_stmt_bind_param($stmt, "s", $clientId);
        mysqli_stmt_execute($stmt);
        
        // Delete client
        $stmt = mysqli_prepare($this->conn, "DELETE FROM oauth_clients WHERE client_id = ?");
        mysqli_stmt_bind_param($stmt, "s", $clientId);
        return mysqli_stmt_execute($stmt);
    }
}
?>
<?php

require_once "../Database/ConnectionManager.php";

include_once '../AccountFiles/AccountSessionAPI.php';
include_once '../AccountFiles/AccountDatabaseAPI.php';

$response = new stdClass();

if(!IsUserLoggedIn()) {
  $response->error = "You must be logged in to use this API";
  echo (json_encode($response));
  exit();
}

$userName = LoggedInUserName();
if($userName != "OotTheMonk") {
  $response->error = "Error: You must be an approved user to use this API";
  echo (json_encode($response));
  exit();
}

/**
 * Generates a new API key
 * 
 * @param int $length Length of the key to generate
 * @param string $prefix Optional prefix for the key
 * @return string The generated API key
 */
function generateAPIKey($length = 32, $prefix = "") {
    $bytes = random_bytes($length);
    $key = bin2hex($bytes);
    
    // Add prefix if provided
    if (!empty($prefix)) {
        $key = $prefix . "_" . $key;
    }
    
    return $key;
}

/**
 * Saves an API key to the database
 * 
 * @param string $key The API key
 * @param string $name Key name/description
 * @param string $owner Owner of the key
 * @param array $permissions Array of permissions this key grants
 * @param string $expiresAt Expiration date in MySQL datetime format (YYYY-MM-DD HH:MM:SS)
 * @return bool True if successful, false otherwise
 */
function saveAPIKey($key, $name, $owner, $permissions = [], $expiresAt = null) {
    $conn = GetLocalMySQLConnection();
    
    // Convert permissions array to JSON string
    $permissionsJson = json_encode($permissions);
    
    // Prepare SQL statement
    $sql = "INSERT INTO api_keys (api_key, name, owner, permissions, created_at, expires_at) 
            VALUES (?, ?, ?, ?, NOW(), ?)";
    
    $stmt = mysqli_stmt_init($conn);
    if (!mysqli_stmt_prepare($stmt, $sql)) {
        echo "SQL Error: " . mysqli_error($conn);
        return false;
    }
    
    mysqli_stmt_bind_param($stmt, "sssss", $key, $name, $owner, $permissionsJson, $expiresAt);
    $result = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    mysqli_close($conn);
    
    return $result;
}

/**
 * Gets all API keys from the database
 * 
 * @param bool $includeExpired Whether to include expired keys
 * @return array Array of API keys and their metadata
 */
function getAPIKeys($includeExpired = false) {
    $conn = GetLocalMySQLConnection();
    
    $sql = "SELECT * FROM api_keys";
    if (!$includeExpired) {
        $sql .= " WHERE expires_at IS NULL OR expires_at > NOW()";
    }
    $sql .= " ORDER BY created_at DESC";
    
    $result = mysqli_query($conn, $sql);
    $keys = [];
    
    if ($result && mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            // Decode permissions back to array
            $row['permissions'] = json_decode($row['permissions'], true);
            $keys[] = $row;
        }
    }
    
    mysqli_close($conn);
    return $keys;
}

/**
 * Revokes an API key
 * 
 * @param string $key The API key to revoke
 * @return bool True if successful, false otherwise
 */
function revokeAPIKey($key) {
    $conn = GetLocalMySQLConnection();
    
    $sql = "DELETE FROM api_keys WHERE api_key = ?";
    
    $stmt = mysqli_stmt_init($conn);
    if (!mysqli_stmt_prepare($stmt, $sql)) {
        echo "SQL Error: " . mysqli_error($conn);
        return false;
    }
    
    mysqli_stmt_bind_param($stmt, "s", $key);
    $result = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    mysqli_close($conn);
    
    return $result;
}

/**
 * Create required database table if it doesn't exist
 */
function ensureAPIKeyTable() {
    $conn = GetLocalMySQLConnection();
    
    $sql = "CREATE TABLE IF NOT EXISTS api_keys (
        id INT AUTO_INCREMENT PRIMARY KEY,
        api_key VARCHAR(255) NOT NULL UNIQUE,
        name VARCHAR(255) NOT NULL,
        owner VARCHAR(255) NOT NULL,
        permissions TEXT,
        created_at DATETIME NOT NULL,
        expires_at DATETIME NULL,
        last_used_at DATETIME NULL
    )";
    
    if (!mysqli_query($conn, $sql)) {
        echo "Error creating table: " . mysqli_error($conn);
    }
    
    mysqli_close($conn);
}

// Handle form submission
$message = "";
$generatedKey = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Create table if needed
    ensureAPIKeyTable();
    
    if (isset($_POST["action"])) {
        switch ($_POST["action"]) {
            case "generate":
                $length = isset($_POST["length"]) ? (int)$_POST["length"] : 32;
                $prefix = isset($_POST["prefix"]) ? $_POST["prefix"] : "";
                $generatedKey = generateAPIKey($length, $prefix);
                break;
                
            case "save":
                if (isset($_POST["key"]) && isset($_POST["name"]) && isset($_POST["owner"])) {
                    $permissions = isset($_POST["permissions"]) ? explode(",", $_POST["permissions"]) : [];
                    $expiresAt = !empty($_POST["expires_at"]) ? $_POST["expires_at"] : null;
                    
                    if (saveAPIKey($_POST["key"], $_POST["name"], $_POST["owner"], $permissions, $expiresAt)) {
                        $message = "API key saved successfully.";
                        $generatedKey = "";
                    } else {
                        $message = "Failed to save API key.";
                    }
                }
                break;
                
            case "revoke":
                if (isset($_POST["revoke_key"])) {
                    if (revokeAPIKey($_POST["revoke_key"])) {
                        $message = "API key revoked successfully.";
                    } else {
                        $message = "Failed to revoke API key.";
                    }
                }
                break;
        }
    }
}

// Create table on initial load
ensureAPIKeyTable();

// Get existing keys
$existingKeys = getAPIKeys();

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>API Key Generator</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            line-height: 1.6;
        }
        .container {
            max-width: 1000px;
            margin: 0 auto;
        }
        .card {
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 20px;
            margin-bottom: 20px;
        }
        h1, h2 {
            color: #333;
        }
        .form-group {
            margin-bottom: 15px;
        }
        label {
            display: block;
            margin-bottom: 5px;
        }
        input, select, button {
            padding: 8px;
            width: 100%;
            box-sizing: border-box;
        }
        button {
            background-color: #4CAF50;
            color: white;
            border: none;
            cursor: pointer;
        }
        button:hover {
            background-color: #45a049;
        }
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border: 1px solid transparent;
            border-radius: 4px;
        }
        .alert-success {
            color: #3c763d;
            background-color: #dff0d8;
            border-color: #d6e9c6;
        }
        .alert-danger {
            color: #a94442;
            background-color: #f2dede;
            border-color: #ebccd1;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        table, th, td {
            border: 1px solid #ddd;
        }
        th, td {
            padding: 12px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
        }
        .key-value {
            font-family: monospace;
            word-break: break-all;
        }
        .revoke-form {
            display: inline;
        }
        .revoke-button {
            background-color: #f44336;
            padding: 5px 10px;
            width: auto;
        }
        .revoke-button:hover {
            background-color: #d32f2f;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>API Key Generator</h1>
        
        <?php if (!empty($message)): ?>
        <div class="alert <?php echo strpos($message, "success") !== false ? "alert-success" : "alert-danger"; ?>">
            <?php echo htmlspecialchars($message); ?>
        </div>
        <?php endif; ?>
        
        <div class="card">
            <h2>Generate New API Key</h2>
            <form method="post">
                <input type="hidden" name="action" value="generate">
                <div class="form-group">
                    <label for="prefix">Key Prefix (optional):</label>
                    <input type="text" id="prefix" name="prefix" placeholder="e.g. SWUSTATS">
                </div>
                
                <div class="form-group">
                    <label for="length">Key Length:</label>
                    <select id="length" name="length">
                        <option value="16">16 characters</option>
                        <option value="32" selected>32 characters</option>
                        <option value="48">48 characters</option>
                        <option value="64">64 characters</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <button type="submit">Generate Key</button>
                </div>
            </form>
        </div>
        
        <?php if (!empty($generatedKey)): ?>
        <div class="card">
            <h2>Generated API Key</h2>
            <p class="key-value"><?php echo htmlspecialchars($generatedKey); ?></p>
            
            <h3>Save this key</h3>
            <form method="post">
                <input type="hidden" name="action" value="save">
                <input type="hidden" name="key" value="<?php echo htmlspecialchars($generatedKey); ?>">
                
                <div class="form-group">
                    <label for="name">Key Name:</label>
                    <input type="text" id="name" name="name" required placeholder="e.g. Discord Bot Key">
                </div>
                
                <div class="form-group">
                    <label for="owner">Owner:</label>
                    <input type="text" id="owner" name="owner" required placeholder="e.g. admin@swustats.net">
                </div>
                
                <div class="form-group">
                    <label for="permissions">Permissions (comma-separated):</label>
                    <input type="text" id="permissions" name="permissions" placeholder="e.g. read,write,stats">
                </div>
                
                <div class="form-group">
                    <label for="expires_at">Expires At (optional):</label>
                    <input type="datetime-local" id="expires_at" name="expires_at">
                </div>
                
                <div class="form-group">
                    <button type="submit">Save Key</button>
                </div>
            </form>
        </div>
        <?php endif; ?>
        
        <div class="card">
            <h2>Existing API Keys</h2>
            
            <?php if (empty($existingKeys)): ?>
                <p>No API keys found.</p>
            <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Key</th>
                        <th>Owner</th>
                        <th>Created At</th>
                        <th>Expires At</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($existingKeys as $key): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($key['name']); ?></td>
                        <td class="key-value"><?php echo htmlspecialchars($key['api_key']); ?></td>
                        <td><?php echo htmlspecialchars($key['owner']); ?></td>
                        <td><?php echo htmlspecialchars($key['created_at']); ?></td>
                        <td><?php echo $key['expires_at'] ? htmlspecialchars($key['expires_at']) : 'Never'; ?></td>
                        <td>
                            <form method="post" class="revoke-form" onsubmit="return confirm('Are you sure you want to revoke this API key?');">
                                <input type="hidden" name="action" value="revoke">
                                <input type="hidden" name="revoke_key" value="<?php echo htmlspecialchars($key['api_key']); ?>">
                                <button type="submit" class="revoke-button">Revoke</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>

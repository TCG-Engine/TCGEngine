<?php
require_once "./Database/ConnectionManager.php";
include_once './AccountFiles/AccountSessionAPI.php';
include_once './AccountFiles/AccountDatabaseAPI.php';

$response = new stdClass();

if(!IsUserLoggedIn()) {
  $response->error = "You must be logged in to use this";
  echo (json_encode($response));
    exit();
}

$userName = LoggedInUserName();

if($userName != "OotTheMonk") {
  $response->error = "Error: You must be an approved user to use this";
  echo (json_encode($response));
  exit();
}

// Handle arbitrary SQL execution (admin only)
if (isset($_POST['adminExecuteSQL']) && $_POST['adminExecuteSQL'] === '1') {
    $sql = isset($_POST['sql']) ? trim($_POST['sql']) : '';
    $response = new stdClass();
    if ($sql !== '') {
        $conn = GetLocalMySQLConnection();
        if ($conn) {
            // Only allow single statement for safety
            if (preg_match('/;.*\S/', $sql)) {
                $response->success = false;
                $response->error = "Only single SQL statements are allowed.";
            } else {
                $result = mysqli_query($conn, $sql);
                if ($result === TRUE) {
                    $response->success = true;
                    $response->message = "Query executed successfully.";
                } else if ($result) {
                    // SELECT or similar: fetch rows
                    $rows = [];
                    while ($row = mysqli_fetch_assoc($result)) {
                        $rows[] = $row;
                    }
                    $response->success = true;
                    $response->rows = $rows;
                } else {
                    $response->success = false;
                    $response->error = mysqli_error($conn);
                }
            }
            mysqli_close($conn);
        } else {
            $response->success = false;
            $response->error = "Database connection failed.";
        }
    } else {
        $response->success = false;
        $response->error = "No SQL provided.";
    }
    header('Content-Type: application/json');
    echo json_encode($response);
    exit();
}

// Handle mod password reset by usersId
if (isset($_POST['modResetUserPassword']) && $_POST['modResetUserPassword'] === '1') {
    $usersId = isset($_POST['usersId']) ? intval($_POST['usersId']) : 0;
    $newPassword = isset($_POST['newPassword']) ? $_POST['newPassword'] : '';
    $response = new stdClass();
    if ($usersId > 0 && !empty($newPassword)) {
        $conn = GetLocalMySQLConnection();
        if ($conn) {
            $newPwdHash = password_hash($newPassword, PASSWORD_DEFAULT);
            $stmt = mysqli_prepare($conn, "UPDATE users SET usersPwd=? WHERE usersId=?");
            mysqli_stmt_bind_param($stmt, 'si', $newPwdHash, $usersId);
            if (mysqli_stmt_execute($stmt)) {
                if (mysqli_stmt_affected_rows($stmt) > 0) {
                    $response->success = true;
                    $response->message = "Password reset successfully.";
                } else {
                    $response->success = false;
                    $response->error = "No user updated. Check usersId.";
                }
            } else {
                $response->success = false;
                $response->error = mysqli_stmt_error($stmt);
            }
            mysqli_stmt_close($stmt);
            mysqli_close($conn);
        } else {
            $response->success = false;
            $response->error = "Database connection failed.";
        }
    } else {
        $response->success = false;
        $response->error = "Please provide a valid usersId and new password.";
    }
    header('Content-Type: application/json');
    echo json_encode($response);
    exit();
}


// Handle truncate request
if (isset($_POST['truncateMetaStats']) && $_POST['truncateMetaStats'] === '1') {
    $conn = GetLocalMySQLConnection();
    $success = true;
    $errorMsg = '';
    if ($conn) {
        mysqli_begin_transaction($conn);
        $q1 = mysqli_query($conn, "TRUNCATE TABLE cardmetastats");
        $q2 = mysqli_query($conn, "TRUNCATE TABLE deckmetastats");
        if ($q1 && $q2) {
            mysqli_commit($conn);
        } else {
            mysqli_rollback($conn);
            $success = false;
            $errorMsg = mysqli_error($conn);
        }
        mysqli_close($conn);
    } else {
        $success = false;
        $errorMsg = "Database connection failed.";
    }
    $response = new stdClass();
    if ($success) {
        $response->success = true;
        $response->message = "Tables truncated successfully.";
    } else {
        $response->success = false;
        $response->error = $errorMsg;
    }
    header('Content-Type: application/json');
    echo json_encode($response);
    exit();
}

// Handle view ownership row request
if (isset($_POST['viewOwnershipRow']) && $_POST['viewOwnershipRow'] === '1') {
    $assetType = isset($_POST['assetType']) ? intval($_POST['assetType']) : null;
    $assetIdentifier = isset($_POST['assetIdentifier']) ? intval($_POST['assetIdentifier']) : null;
    $conn = GetLocalMySQLConnection();
    $response = new stdClass();
    if ($conn && $assetType !== null && $assetIdentifier !== null) {
        $stmt = mysqli_prepare($conn, "SELECT * FROM ownership WHERE assetType=? AND assetIdentifier=? LIMIT 1");
        mysqli_stmt_bind_param($stmt, 'ii', $assetType, $assetIdentifier);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        if ($row = mysqli_fetch_assoc($result)) {
            $response->success = true;
            $response->row = $row;
        } else {
            $response->success = false;
            $response->error = "No row found.";
        }
        mysqli_stmt_close($stmt);
        mysqli_close($conn);
    } else {
        $response->success = false;
        $response->error = "Invalid input or database connection failed.";
    }
    header('Content-Type: application/json');
    echo json_encode($response);
    exit();
}

// Handle update ownership owner request
if (isset($_POST['updateOwnershipOwner']) && $_POST['updateOwnershipOwner'] === '1') {
    $assetType = isset($_POST['assetType']) ? intval($_POST['assetType']) : null;
    $assetIdentifier = isset($_POST['assetIdentifier']) ? intval($_POST['assetIdentifier']) : null;
    $newOwner = isset($_POST['newOwner']) ? intval($_POST['newOwner']) : null;
    $conn = GetLocalMySQLConnection();
    $response = new stdClass();
    if ($conn && $assetType !== null && $assetIdentifier !== null && $newOwner !== null) {
        $stmt = mysqli_prepare($conn, "UPDATE ownership SET assetOwner=? WHERE assetType=? AND assetIdentifier=?");
        mysqli_stmt_bind_param($stmt, 'iii', $newOwner, $assetType, $assetIdentifier);
        if (mysqli_stmt_execute($stmt)) {
            if (mysqli_stmt_affected_rows($stmt) > 0) {
                $response->success = true;
                $response->message = "Owner updated successfully.";
            } else {
                $response->success = false;
                $response->error = "No row updated. Check if the row exists.";
            }
        } else {
            $response->success = false;
            $response->error = mysqli_stmt_error($stmt);
        }
        mysqli_stmt_close($stmt);
        mysqli_close($conn);
    } else {
        $response->success = false;
        $response->error = "Invalid input or database connection failed.";
    }
    header('Content-Type: application/json');
    echo json_encode($response);
    exit();
}

// Handle user account lookup by usersUid or email
if (isset($_POST['lookupUserAccount']) && $_POST['lookupUserAccount'] === '1') {
    $usersUid = isset($_POST['usersUid']) ? trim($_POST['usersUid']) : '';
    $usersEmail = isset($_POST['usersEmail']) ? trim($_POST['usersEmail']) : '';
    $conn = GetLocalMySQLConnection();
    $response = new stdClass();
    if ($conn && ($usersUid !== '' || $usersEmail !== '')) {
        if ($usersUid !== '') {
            $stmt = mysqli_prepare($conn, "SELECT * FROM users WHERE usersUid = ? LIMIT 1");
            mysqli_stmt_bind_param($stmt, 's', $usersUid);
        } else {
            $stmt = mysqli_prepare($conn, "SELECT * FROM users WHERE usersEmail = ? LIMIT 1");
            mysqli_stmt_bind_param($stmt, 's', $usersEmail);
        }
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        if ($row = mysqli_fetch_assoc($result)) {
            $response->success = true;
            // Remove sensitive fields
            unset($row['usersPwd']);
            $row['hasRememberMeToken'] = !empty($row['rememberMeToken']) ? true : false;
            unset($row['rememberMeToken']);
            $row['hasPatreonAccessToken'] = !empty($row['patreonAccessToken']) ? true : false;
            unset($row['patreonAccessToken']);
            $row['hasPatreonRefreshToken'] = !empty($row['patreonRefreshToken']) ? true : false;
            unset($row['patreonRefreshToken']);
            $response->user = $row;
        } else {
            $response->success = false;
            $response->error = "No user found.";
        }
        mysqli_stmt_close($stmt);
        mysqli_close($conn);
    } else {
        $response->success = false;
        $response->error = "Please provide either usersUid or usersEmail.";
    }
    header('Content-Type: application/json');
    echo json_encode($response);
    exit();
}

// HTML and JS for truncate button
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Mod Page</title>
</head>
<body>
    <button id="truncateBtn">Truncate Meta Stats Tables</button>
    <div id="result" style="margin-top:10px;"></div>

    <hr style="margin:20px 0;">
    <h3>View Ownership Row</h3>
    <form id="viewOwnershipForm" onsubmit="return false;">
        <label>Asset Type: <input type="number" id="viewAssetType" required value="1"></label>
        <label>Asset Identifier: <input type="number" id="viewAssetIdentifier" required></label>
        <button id="viewOwnershipBtn">View Row</button>
    </form>
    <pre id="ownershipRowResult" style="background:#f0f0f0;padding:10px;"></pre>

    <h3>Change Ownership Owner</h3>
    <form id="updateOwnershipForm" onsubmit="return false;">
        <label>Asset Type: <input type="number" id="updateAssetType" required value="1"></label>
        <label>Asset Identifier: <input type="number" id="updateAssetIdentifier" required></label>
        <label>New Owner: <input type="number" id="newOwner" required></label>
        <button id="updateOwnershipBtn">Update Owner</button>
    </form>
    <div id="updateOwnershipResult" style="margin-top:10px;"></div>


    <h3>Lookup User Account</h3>
    <form id="lookupUserForm" onsubmit="return false;">
        <label>usersUid: <input type="text" id="lookupUsersUid"></label>
        <label>or Email: <input type="email" id="lookupUsersEmail"></label>
        <button id="lookupUserBtn">Lookup</button>
    </form>
    <pre id="lookupUserResult" style="background:#f0f0f0;padding:10px;"></pre>

    <h3>Reset User Password</h3>
    <form id="modResetPasswordForm" onsubmit="return false;">
        <label>usersId: <input type="number" id="modResetUsersId" required></label>
        <label>New Password: <input type="password" id="modResetNewPassword" required></label>
        <button id="modResetPasswordBtn">Reset Password</button>
    </form>
    <div id="modResetPasswordResult" style="margin-top:10px;"></div>

    <hr style="margin:20px 0;">
    <h3>Admin SQL Executor</h3>
    <form id="adminSQLForm" onsubmit="return false;">
        <label>SQL Statement:<br>
            <textarea id="adminSQLInput" rows="3" cols="80" style="font-family:monospace;"></textarea>
        </label><br>
        <button id="adminSQLBtn">Execute SQL</button>
    </form>
    <pre id="adminSQLResult" style="background:#f0f0f0;padding:10px;"></pre>

    <script>
    document.getElementById('adminSQLBtn').onclick = function() {
        var sql = document.getElementById('adminSQLInput').value.trim();
        if (!sql) {
            document.getElementById('adminSQLResult').innerText = 'Please enter SQL.';
            return;
        }
        document.getElementById('adminSQLResult').innerText = 'Processing...';
        var params = 'adminExecuteSQL=1&sql=' + encodeURIComponent(sql);
        fetch(window.location.pathname, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: params
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                if (data.rows) {
                    document.getElementById('adminSQLResult').innerText = JSON.stringify(data.rows, null, 2);
                } else {
                    document.getElementById('adminSQLResult').innerText = data.message || 'Success.';
                }
            } else {
                document.getElementById('adminSQLResult').innerText = data.error || 'Unknown error.';
            }
        })
        .catch(e => {
            document.getElementById('adminSQLResult').innerText = 'Request failed.';
        });
    };
    document.getElementById('truncateBtn').onclick = function() {
        if (!confirm('Are you sure you want to truncate cardmetastats and deckmetastats? This cannot be undone!')) return;
        var btn = this;
        btn.disabled = true;
        document.getElementById('result').innerText = 'Processing...';
        fetch(window.location.pathname, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'truncateMetaStats=1'
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                document.getElementById('result').innerText = data.message;
            } else {
                document.getElementById('result').innerText = data.error || 'Unknown error.';
            }
            btn.disabled = false;
        })
        .catch(e => {
            document.getElementById('result').innerText = 'Request failed.';
            btn.disabled = false;
        });
    };

    document.getElementById('viewOwnershipBtn').onclick = function() {
        var type = document.getElementById('viewAssetType').value;
        var id = document.getElementById('viewAssetIdentifier').value;
        document.getElementById('ownershipRowResult').innerText = 'Loading...';
        fetch(window.location.pathname, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'viewOwnershipRow=1&assetType=' + encodeURIComponent(type) + '&assetIdentifier=' + encodeURIComponent(id)
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                document.getElementById('ownershipRowResult').innerText = JSON.stringify(data.row, null, 2);
            } else {
                document.getElementById('ownershipRowResult').innerText = data.error || 'Unknown error.';
            }
        })
        .catch(e => {
            document.getElementById('ownershipRowResult').innerText = 'Request failed.';
        });
    };

    document.getElementById('updateOwnershipBtn').onclick = function() {
        var type = document.getElementById('updateAssetType').value;
        var id = document.getElementById('updateAssetIdentifier').value;
        var newOwner = document.getElementById('newOwner').value;
        document.getElementById('updateOwnershipResult').innerText = 'Processing...';
        fetch(window.location.pathname, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'updateOwnershipOwner=1&assetType=' + encodeURIComponent(type) + '&assetIdentifier=' + encodeURIComponent(id) + '&newOwner=' + encodeURIComponent(newOwner)
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                document.getElementById('updateOwnershipResult').innerText = data.message;
            } else {
                document.getElementById('updateOwnershipResult').innerText = data.error || 'Unknown error.';
            }
        })
        .catch(e => {
            document.getElementById('updateOwnershipResult').innerText = 'Request failed.';
        });
    };

    document.getElementById('lookupUserBtn').onclick = function() {
        var usersUid = document.getElementById('lookupUsersUid').value.trim();
        var usersEmail = document.getElementById('lookupUsersEmail').value.trim();
        if (!usersUid && !usersEmail) {
            document.getElementById('lookupUserResult').innerText = 'Please enter usersUid or email.';
            return;
        }
        document.getElementById('lookupUserResult').innerText = 'Loading...';
        var params = 'lookupUserAccount=1';
        if (usersUid) params += '&usersUid=' + encodeURIComponent(usersUid);
        if (usersEmail) params += '&usersEmail=' + encodeURIComponent(usersEmail);
        fetch(window.location.pathname, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: params
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                document.getElementById('lookupUserResult').innerText = JSON.stringify(data.user, null, 2);
            } else {
                document.getElementById('lookupUserResult').innerText = data.error || 'Unknown error.';
            }
        })
        .catch(e => {
            document.getElementById('lookupUserResult').innerText = 'Request failed.';
        });
    };
    document.getElementById('modResetPasswordBtn').onclick = function() {
        var usersId = document.getElementById('modResetUsersId').value;
        var newPassword = document.getElementById('modResetNewPassword').value;
        if (!usersId || !newPassword) {
            document.getElementById('modResetPasswordResult').innerText = 'Please enter both usersId and new password.';
            return;
        }
        document.getElementById('modResetPasswordResult').innerText = 'Processing...';
        var params = 'modResetUserPassword=1&usersId=' + encodeURIComponent(usersId) + '&newPassword=' + encodeURIComponent(newPassword);
        fetch(window.location.pathname, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: params
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                document.getElementById('modResetPasswordResult').innerText = data.message;
            } else {
                document.getElementById('modResetPasswordResult').innerText = data.error || 'Unknown error.';
            }
        })
        .catch(e => {
            document.getElementById('modResetPasswordResult').innerText = 'Request failed.';
        });
    };
    </script>
</body>
</html>
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

    <script>
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
    </script>
</body>
</html>
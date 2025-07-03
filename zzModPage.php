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
    </script>
</body>
</html>
<?php
require_once "./Database/ConnectionManager.php";
include_once './AccountFiles/AccountSessionAPI.php';
include_once './AccountFiles/AccountDatabaseAPI.php';

$response = new stdClass();
$error = CheckLoggedInUserMod();
if($error !== "") {
  $response->error = $error;
  echo json_encode($response);
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

// Fill a SWUDeck game with a supplied deck JSON (mirrors SWUDeck/CreateDeck.php's parse+write, but
// targets a caller-supplied gameName and takes the JSON directly). A SWUDeck game file is a full
// gamestate, but only the leader/base/mainDeck/sideboard zones carry deck content — the rest are
// empty scaffolding written by WriteGamestate. Deck JSON shape: {leader:{id},base:{id},
// deck:[{id,count}],sideboard:[{id,count}]} with SET_NNN ids.
if (isset($_POST['fillSWUDeckGame']) && $_POST['fillSWUDeckGame'] === '1') {
    header('Content-Type: application/json');
    $gameNameIn = isset($_POST['gameName']) ? preg_replace('/[^0-9]/', '', $_POST['gameName']) : '';
    $deckJsonIn = isset($_POST['deckJson']) ? trim($_POST['deckJson']) : '';
    if ($gameNameIn === '' || $deckJsonIn === '') {
        echo json_encode(['success' => false, 'error' => 'gameName (numeric) and deckJson are both required.']);
        exit();
    }
    $deckObj = json_decode($deckJsonIn);
    if ($deckObj === null) {
        echo json_encode(['success' => false, 'error' => 'deckJson is not valid JSON.']);
        exit();
    }
    include_once './SWUDeck/GamestateParser.php';
    include_once './SWUDeck/ZoneAccessors.php';
    include_once './SWUDeck/ZoneClasses.php';
    include_once './AppCore/SWU/Overrides.php';
    include_once './Core/CoreZoneModifiers.php';
    include_once './SWUDeck/GeneratedCode/GeneratedCardDictionaries.php';
    include_once './SWUDeck/Custom/CardIdentifiers.php';
    global $gameName, $p1Leader, $p1Base, $p1MainDeck, $p1Sideboard;
    $gameName = $gameNameIn;
    InitializeGamestate();
    if (isset($deckObj->leader->id)) array_push($p1Leader, new Leader(UUIDLookup($deckObj->leader->id)));
    if (isset($deckObj->base->id))   array_push($p1Base,   new Base(UUIDLookup($deckObj->base->id)));
    foreach (($deckObj->deck ?? []) as $c) {
        if (!isset($c->id)) continue;
        $cardID = UUIDLookup(CardIDOverride($c->id));
        for ($j = 0; $j < intval($c->count ?? 1); ++$j) array_push($p1MainDeck, new MainDeck($cardID));
    }
    foreach (($deckObj->sideboard ?? []) as $c) {
        if (!isset($c->id)) continue;
        $cardID = UUIDLookup(CardIDOverride($c->id));
        for ($j = 0; $j < intval($c->count ?? 1); ++$j) array_push($p1Sideboard, new Sideboard($cardID));
    }
    @mkdir('./SWUDeck/Games/' . $gameName, 0777, true); // ensure the dir exists for the file-mode write
    WriteGamestate('./SWUDeck/');
    echo json_encode([
        'success' => true,
        'message' => 'Filled SWUDeck game ' . $gameName . ': leader ' . (isset($deckObj->leader->id) ? '✓' : '—')
            . ', base ' . (isset($deckObj->base->id) ? '✓' : '—')
            . ', ' . count($p1MainDeck) . ' main deck, ' . count($p1Sideboard) . ' sideboard.',
        'link' => '/TCGEngine/NextTurn.php?gameName=' . $gameName . '&playerID=1&folderPath=SWUDeck',
    ]);
    exit();
}

// HTML and JS for truncate button
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SWUDeck — Mod Page</title>
    <style>
      /* SWUDeck-themed mod page (dark navy + blue accents, matching the SWUDeck site palette). */
      :root { color-scheme: dark; }
      * { box-sizing: border-box; }
      body {
        margin: 0 auto; max-width: 920px; padding: 28px 22px 64px; min-height: 100vh;
        font-family: Roboto, Barlow, system-ui, -apple-system, sans-serif;
        background: radial-gradient(circle at 50% -12%, #0a2452 0%, #000022 62%) fixed;
        color: #dfe8ff;
      }
      h1.mod-title {
        font-size: 26px; font-weight: 700; letter-spacing: .4px; color: #cfe0ff;
        margin: 0 0 4px; display: flex; align-items: center; gap: 10px;
      }
      h1.mod-title::before { content: '◆'; color: #4f8bff; text-shadow: 0 0 10px #2a4b8d; }
      .mod-sub { color: #7d97c8; font-size: 13px; margin: 0 0 26px; }
      h3 {
        color: #aac8ff; font-size: 16px; margin: 28px 0 12px;
        padding-bottom: 6px; border-bottom: 1px solid #2a4b8d;
      }
      hr { border: none; border-top: 1px solid #143062; margin: 26px 0; }
      form {
        background: #001833; border: 1px solid #23407d; border-radius: 8px;
        padding: 14px 16px; box-shadow: 0 2px 14px rgba(0,20,60,.5);
        display: flex; flex-wrap: wrap; gap: 10px 16px; align-items: center;
      }
      form label { color: #c3d4f5; font-size: 13px; display: flex; align-items: center; gap: 6px; }
      input, textarea, select {
        background: #00102a; color: #e8f0ff; border: 1px solid #2a4b8d; border-radius: 5px;
        padding: 6px 8px; font-size: 13px; font-family: inherit;
      }
      textarea { font-family: ui-monospace, Menlo, Consolas, monospace; width: 100%; }
      input:focus, textarea:focus, select:focus {
        outline: none; border-color: #4f8bff; box-shadow: 0 0 0 2px rgba(79,139,255,.3);
      }
      button {
        background: linear-gradient(180deg, #2a4b8d, #001f4d); color: #fff;
        border: 1px solid #3a5b9d; border-radius: 6px; padding: 7px 16px;
        font-size: 13px; font-weight: 600; cursor: pointer;
        transition: filter .12s, box-shadow .12s;
      }
      button:hover { filter: brightness(1.25); box-shadow: 0 0 8px rgba(79,139,255,.5); }
      button:active { filter: brightness(.9); }
      pre.mod-pre {
        background: #00112b; border: 1px solid #143062; border-radius: 6px;
        color: #bcd0f5; padding: 10px 12px; margin-top: 10px; font-size: 12px;
        white-space: pre-wrap; word-break: break-word;
      }
      [id$="Result"], #result { margin-top: 10px; color: #bcd0f5; font-size: 13px; }
      [id$="Result"] a, #result a { color: #7fb0ff; }
    </style>
</head>
<body>
    <h1 class="mod-title">SWUDeck Mod Tools</h1>
    <p class="mod-sub">Moderator utilities — ownership, accounts, SQL, and SWUDeck game tooling.</p>

    <h3>Truncate Meta Stats</h3>
    <button id="truncateBtn">Truncate Meta Stats Tables</button>
    <div id="result" style="margin-top:10px;"></div>

    <hr style="margin:20px 0;">
    <h3>View Ownership Row</h3>
    <form id="viewOwnershipForm" onsubmit="return false;">
        <label>Asset Type: <input type="number" id="viewAssetType" required value="1"></label>
        <label>Asset Identifier: <input type="number" id="viewAssetIdentifier" required></label>
        <button id="viewOwnershipBtn">View Row</button>
    </form>
    <pre id="ownershipRowResult" class="mod-pre"></pre>

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
    <pre id="lookupUserResult" class="mod-pre"></pre>

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
    <pre id="adminSQLResult" class="mod-pre"></pre>

    <hr style="margin:20px 0;">
    <h3>Fill SWUDeck Game with Deck JSON</h3>
    <form id="fillSWUDeckForm" onsubmit="return false;">
        <label>gameName: <input type="number" id="fillGameName" required></label><br>
        <label>Deck JSON:<br>
            <textarea id="fillDeckJson" rows="6" cols="80" style="font-family:monospace;"
                placeholder='{"leader":{"id":"ASH_011"},"base":{"id":"ASH_020"},"deck":[{"id":"SOR_033","count":3}],"sideboard":[]}'></textarea>
        </label><br>
        <button id="fillSWUDeckBtn">Fill Game</button>
    </form>
    <div id="fillSWUDeckResult" style="margin-top:10px;"></div>

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

    document.getElementById('fillSWUDeckBtn').onclick = function() {
        var gameName = document.getElementById('fillGameName').value.trim();
        var deckJson = document.getElementById('fillDeckJson').value.trim();
        var out = document.getElementById('fillSWUDeckResult');
        if (!gameName || !deckJson) { out.innerText = 'Please enter a gameName and deck JSON.'; return; }
        try { JSON.parse(deckJson); } catch (err) { out.innerText = 'Deck JSON is not valid JSON.'; return; }
        out.innerText = 'Processing...';
        var params = 'fillSWUDeckGame=1&gameName=' + encodeURIComponent(gameName) + '&deckJson=' + encodeURIComponent(deckJson);
        fetch(window.location.pathname, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: params
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                out.innerHTML = '';
                out.appendChild(document.createTextNode(data.message + ' '));
                if (data.link) {
                    var a = document.createElement('a');
                    a.href = data.link; a.target = '_blank'; a.textContent = 'Open game';
                    out.appendChild(a);
                }
            } else {
                out.innerText = data.error || 'Unknown error.';
            }
        })
        .catch(e => { out.innerText = 'Request failed.'; });
    };
    </script>
</body>
</html>
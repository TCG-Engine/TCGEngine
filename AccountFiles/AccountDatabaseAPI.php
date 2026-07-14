<?php

function LoadUserData($username) {
	$conn = GetLocalMySQLConnection();
  $sql = "SELECT * FROM users WHERE usersUid = ?";
	$stmt = mysqli_stmt_init($conn);
	if (!mysqli_stmt_prepare($stmt, $sql)) {
	 	return NULL;
	}
	mysqli_stmt_bind_param($stmt, "s", $username);
	mysqli_stmt_execute($stmt);
	$result = mysqli_stmt_get_result($stmt);
  $row = mysqli_fetch_assoc($result);
	mysqli_stmt_close($stmt);
	mysqli_close($conn);

  return $row;
}

function LoadUserDataFromId($userId) {
	$conn = GetLocalMySQLConnection();

	// Using LEFT JOIN so that if the user has no team, team columns will be null.
	$sql = "SELECT u.*,
								 t.teamID AS team_teamID,
								 t.teamName,
								 t.ownerID
					FROM users u
					LEFT JOIN team t ON u.teamID = t.teamID
					WHERE u.usersId = ?";

	$stmt = mysqli_stmt_init($conn);
	if (!mysqli_stmt_prepare($stmt, $sql)) {
		mysqli_close($conn);
		return NULL;
	}

	mysqli_stmt_bind_param($stmt, "i", $userId);
	mysqli_stmt_execute($stmt);
	$result = mysqli_stmt_get_result($stmt);
	$row = mysqli_fetch_assoc($result);
	mysqli_stmt_close($stmt);

	if ($row) {
		// If team data is available, collect it in a nested array.
		$team = null;
		if ($row['team_teamID'] !== null) {
			$team = [
				'teamID'          => $row['team_teamID'],
				'teamName'        => $row['teamName'],
				'ownerID' => $row['ownerID']
			];
		}
		// Remove the team columns from the main row and nest them.
		unset($row['team_teamID'], $row['teamName'], $row['teamDescription']);
		$row['team'] = $team;

		// Load team invites using the existing function.
		$row['teamInvites'] = LoadUserTeamInvites($userId, $conn);
	}

	mysqli_close($conn);
	return $row;
}

function AccountRequestIsHttps() {
	if (!empty($_SERVER['HTTPS']) && strtolower((string)$_SERVER['HTTPS']) !== 'off') return true;
	return strtolower((string)($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')) === 'https';
}

function SetAccountRememberMeCookie($rawToken, $expiresAt) {
	setcookie('rememberMeToken', $rawToken, [
		'expires' => $expiresAt,
		'path' => '/',
		'secure' => AccountRequestIsHttps(),
		'httponly' => true,
		'samesite' => 'Lax',
	]);
}

function EstablishUserSessionFromData($userData, $rememberMe = false) {
	if (!is_array($userData) || empty($userData['usersId']) || empty($userData['usersUid'])) return false;
	if (session_status() !== PHP_SESSION_ACTIVE) session_start();

	$_SESSION['userid'] = (int)$userData['usersId'];
	$_SESSION['useruid'] = $userData['usersUid'];
	$_SESSION['discordID'] = $userData['discordID'] ?? '';
	$_SESSION['useremail'] = $userData['usersEmail'] ?? '';
	$_SESSION['userspwd'] = $userData['usersPwd'] ?? '';
	$_SESSION['patreonEnum'] = $userData['patreonEnum'] ?? null;
	$_SESSION['isBanned'] = $userData['isBanned'] ?? 0;

	$patreonAccessToken = $userData['patreonAccessToken'] ?? '';
	if ($patreonAccessToken !== '' && function_exists('PatreonLogin')) {
		try { PatreonLogin($patreonAccessToken); } catch (\Exception $e) { }
	}

	if ($rememberMe) {
		$rawToken = bin2hex(random_bytes(32));
		$storedToken = hash('sha256', $rawToken);
		$conn = GetLocalMySQLConnection();
		$stmt = $conn->prepare('UPDATE users SET rememberMeToken = ? WHERE usersId = ?');
		if ($stmt) {
			$userId = (int)$userData['usersId'];
			$stmt->bind_param('si', $storedToken, $userId);
			$stmt->execute();
			$stmt->close();
			SetAccountRememberMeCookie($rawToken, time() + (86400 * 90));
		}
		$conn->close();
	}

	session_write_close();
	return true;
}

function LoadUserTeamInvites($userId, $conn) {
	$sql = "SELECT ti.*, t.teamName, u.usersUid as invitedByUserUid
			FROM teaminvite ti
			LEFT JOIN team t ON ti.teamID = t.teamID
			LEFT JOIN users u ON ti.invitedBy = u.usersId
			WHERE ti.userID = ?";
	$stmt = mysqli_stmt_init($conn);
	if (!mysqli_stmt_prepare($stmt, $sql)) {
		return [];
	}
	mysqli_stmt_bind_param($stmt, "i", $userId);
	mysqli_stmt_execute($stmt);
	$result = mysqli_stmt_get_result($stmt);

	$invites = [];
	while ($row = mysqli_fetch_assoc($result)) {
		$invites[] = $row;
	}
	mysqli_stmt_close($stmt);
	return $invites;
}


function PasswordLogin($username, $password, $rememberMe) {
	try {
		$userData = LoadUserData($username);
	}
	catch (\Exception $e) { }

  if($userData == NULL) return false;

  $passwordHash = $userData['usersPwd'] ?? null;
  $passwordValid = is_string($passwordHash) && $passwordHash !== '' && password_verify($password, $passwordHash);

  if($passwordValid)
  {
		return EstablishUserSessionFromData($userData, $rememberMe);
  }
  return false;
}

function IsBanned($username)
{
	$userData = LoadUserData($username);
	$_SESSION["isBanned"] = $userData["isBanned"];
	return intval($userData["isBanned"]) == 1;
}

function AccountSafeRedirect($redirect, $fallback) {
	if($redirect == null || $redirect == "") return $fallback;
	$parts = parse_url($redirect);
	if($parts === false) return $fallback;
	if(isset($parts["scheme"]) || isset($parts["host"])) return $fallback;
	$path = isset($parts["path"]) ? $parts["path"] : "";
	if(strpos($path, "/TCGEngine/") !== 0) return $fallback;
	return $redirect;
}

function AccountLoginPageRedirect($redirect) {
	$location = "../SharedUI/LoginPage.php";
	$safeRedirect = AccountSafeRedirect($redirect, "");
	if($safeRedirect != "") $location .= "?redirect=" . urlencode($safeRedirect);
	return $location;
}

function AttemptPasswordLogin($username, $password, $rememberMe, $redirect = "") {
	$userData = LoadUserData($username);

  if($userData != NULL)
  {

  }
  else {
		header("location: " . AccountLoginPageRedirect($redirect));
		exit();
  }


  $passwordHash = $userData['usersPwd'] ?? null;
  $passwordValid = is_string($passwordHash) && $passwordHash !== '' && password_verify($password, $passwordHash);

  if($passwordValid)
  {
		EstablishUserSessionFromData($userData, $rememberMe);

		header("location: " . AccountSafeRedirect($redirect, "../SharedUI/MainMenu.php"));
		exit();
  }
  else {
    header("location: " . AccountLoginPageRedirect($redirect));
    exit();
  }
}

function LoadAssetData($assetType, $assetID) {
	$conn = GetLocalMySQLConnection();
	$sql = "SELECT * FROM ownership WHERE assetType = ? AND assetIdentifier = ?";
	$stmt = mysqli_stmt_init($conn);
	if (!mysqli_stmt_prepare($stmt, $sql)) {
		$response->message = "There was an error loading the asset.";
		echo(json_encode($response));
		mysqli_close($conn);
		exit;
	} else {
		mysqli_stmt_bind_param($stmt, "ss", $assetType, $assetID);
		mysqli_stmt_execute($stmt);
		$result = mysqli_stmt_get_result($stmt);
		$asset = mysqli_fetch_assoc($result);
		mysqli_stmt_close($stmt);
		mysqli_close($conn);
		return $asset;
	}
}

function LoadAssetsByType($userID, $assetType) {
	$conn = GetLocalMySQLConnection();
	$sql = "SELECT * FROM ownership WHERE assetOwner = ? AND assetType = ?";
	$stmt = mysqli_stmt_init($conn);
	if (!mysqli_stmt_prepare($stmt, $sql)) {
	  $response->message = "There was an error loading the assets.";
	  echo(json_encode($response));
	  mysqli_close($conn);
	  exit;
	} else {
	  mysqli_stmt_bind_param($stmt, "ss", $userID, $assetType);
	  mysqli_stmt_execute($stmt);
	  $result = mysqli_stmt_get_result($stmt);
	  $assets = array();
	  while ($row = mysqli_fetch_assoc($result)) {
	    array_push($assets, $row);
	  }
	  mysqli_stmt_close($stmt);
	  mysqli_close($conn);
	  return $assets;
	}
}

function SaveAssetOwnership($assetType, $assetID, $userID, $assetSource=null, $assetSourceID=null) {
	if ($userID == "") return;
	$conn = GetLocalMySQLConnection();
	$sql = "INSERT INTO ownership (assetType, assetIdentifier, assetOwner, assetStatus, assetSource, assetSourceID) VALUES (?, ?, ?, ?, ?, ?)";
	$stmt = mysqli_stmt_init($conn);
	if (!mysqli_stmt_prepare($stmt, $sql)) {
		$response->message = "There was an error saving the asset ownership token.";
		echo(json_encode($response));
		mysqli_close($conn);
		exit;
	} else {
		$status = 1; // Status 1 = active
		mysqli_stmt_bind_param($stmt, "ssssss", $assetType, $assetID, $userID, $status, $assetSource, $assetSourceID);
		mysqli_stmt_execute($stmt);
		mysqli_stmt_close($stmt);
		mysqli_close($conn);
	}
}

function UpdateAssetName($assetType, $assetID, $newName) {
  $conn = GetLocalMySQLConnection();
  $stmt = $conn->prepare("UPDATE ownership SET assetName = ? WHERE assetIdentifier = ? AND assetType = ?");
  $stmt->bind_param("sii", $newName, $assetID, $assetType);
  $result = $stmt->execute();
  $stmt->close();
  $conn->close();
  return $result;
}

function storeRememberMeCookie($conn, $uuid, $cookie)
{
  $sql = "UPDATE users SET rememberMeToken=? WHERE usersUid=?";
	$stmt = mysqli_stmt_init($conn);
	if (mysqli_stmt_prepare($stmt, $sql)) {
		mysqli_stmt_bind_param($stmt, "ss", $cookie, $uuid);
		mysqli_stmt_execute($stmt);
		mysqli_stmt_close($stmt);
	}
}

function TouchOwnershipLastUpdated($assetID, $assetType = 1) {
  $conn = GetLocalMySQLConnection();
  if (!$conn) return;
  $stmt = $conn->prepare("UPDATE ownership SET lastUpdated = NOW() WHERE assetIdentifier = ? AND assetType = ?");
  $stmt->bind_param("ii", $assetID, $assetType);
  $stmt->execute();
  $stmt->close();
  $conn->close();
}

function SetAssetKeyIdentifier($assetType, $assetID, $keyIndicator, $keyValue, $conn=null) {
	$connWasNull = $conn == null;
    if($conn == null) $conn = GetLocalMySQLConnection();
    if($keyIndicator == 1) $stmt = $conn->prepare("UPDATE ownership SET keyIndicator1 = ? WHERE assetIdentifier = ? AND assetType = ?");
	else if($keyIndicator == 2) $stmt = $conn->prepare("UPDATE ownership SET keyIndicator2 = ? WHERE assetIdentifier = ? AND assetType = ?");
    if (!$stmt) {
        $conn->close();
        return true;
    }
    $stmt->bind_param("sii", $keyValue, $assetID, $assetType);
    $stmt->execute();
    $stmt->close();
    if($connWasNull) $conn->close();
}

 ?>

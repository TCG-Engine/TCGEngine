<?php
  // APIs/EditDeckCard.php
  // Modify a deck's gamestate (add/remove cards) for decks owned by the token's user.

  require_once "../Core/HTTPLibraries.php";
  require_once "../Database/ConnectionManager.php";

  // Read JSON input
  $input = file_get_contents('php://input');
  $data = json_decode($input, true);

  // Accept token via Authorization header (Bearer) or access_token field
  $authHeader = isset($_SERVER['HTTP_AUTHORIZATION']) ? $_SERVER['HTTP_AUTHORIZATION'] : (isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION']) ? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] : '');
  $accessToken = "";
  if ($authHeader && preg_match('/Bearer\s+(\S+)/', $authHeader, $m)) {
    $accessToken = $m[1];
  }
  if ($accessToken === "" && isset($data['access_token'])) {
    $accessToken = $data['access_token'];
  }

  $deckID = isset($data['deckID']) ? intval($data['deckID']) : 0;
  $action = isset($data['action']) ? strtolower($data['action']) : '';// add | remove
  $cardID = isset($data['cardID']) ? $data['cardID'] : '';
  $count = isset($data['count']) ? intval($data['count']) : 1;
  $zone = isset($data['zone']) ? strtolower($data['zone']) : 'main'; // main | side

  header('Content-Type: application/json');

  if ($deckID <= 0) {
    http_response_code(400);
    echo json_encode(["success" => false, "error" => "Missing or invalid deckID"]);
    exit;
  }
  if (!in_array($action, ['add','remove'])) {
    http_response_code(400);
    echo json_encode(["success" => false, "error" => "Invalid action; must be 'add' or 'remove'"]);
    exit;
  }
  if ($cardID === '') {
    http_response_code(400);
    echo json_encode(["success" => false, "error" => "Missing cardID"]);
    exit;
  }
  if ($count < 1) $count = 1;

  // Validate access token against oauth_access_tokens table
  function ValidateTokenForUser($conn, $token) {
    if ($token === null || $token === "") return false;
    $sql = "SELECT user_id, expires, scope FROM oauth_access_tokens WHERE access_token = ?";
    $stmt = mysqli_stmt_init($conn);
    if (!mysqli_stmt_prepare($stmt, $sql)) return false;
    mysqli_stmt_bind_param($stmt, "s", $token);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
    if (!$row) return false;
    if (!isset($row['user_id']) || $row['user_id'] === null) return false;
    if (isset($row['expires']) && $row['expires'] !== null && $row['expires'] !== '') {
      $expires = strtotime($row['expires']);
      if ($expires !== false && time() > $expires) return false;
    }
    return [
      'user_id' => intval($row['user_id']),
      'scope' => isset($row['scope']) ? $row['scope'] : ''
    ];
  }

  $conn = GetLocalMySQLConnection();
  $tokenInfo = ValidateTokenForUser($conn, $accessToken);
  if ($accessToken !== "" && $tokenInfo === false) {
    http_response_code(401);
    echo json_encode(["success" => false, "errors" => ["access_token" => "Invalid or expired"]]);
    mysqli_close($conn);
    exit;
  }
  if ($accessToken === "") {
    http_response_code(401);
    echo json_encode(["success" => false, "errors" => ["access_token" => "Missing access token"]]);
    mysqli_close($conn);
    exit;
  }

  $userId = intval($tokenInfo['user_id']);
  $tokenScope = isset($tokenInfo['scope']) ? $tokenInfo['scope'] : '';

  // Enforce required scope for deck edits
  $requiredScope = 'editdecks';
  $scopes = preg_split('/[\s,]+/', trim($tokenScope));
  if (!in_array($requiredScope, $scopes)) {
    http_response_code(403);
    echo json_encode(["success" => false, "error" => "insufficient_scope", "required" => $requiredScope]);
    mysqli_close($conn);
    exit;
  }

  // Verify ownership of the deck
  $sql = "SELECT assetOwner FROM ownership WHERE assetType = 1 AND assetIdentifier = ?";
  $stmt = mysqli_stmt_init($conn);
  if (!mysqli_stmt_prepare($stmt, $sql)) {
    http_response_code(500);
    echo json_encode(["success" => false, "error" => "DB error"]);
    mysqli_close($conn);
    exit;
  }
  mysqli_stmt_bind_param($stmt, "i", $deckID);
  mysqli_stmt_execute($stmt);
  $result = mysqli_stmt_get_result($stmt);
  $row = mysqli_fetch_assoc($result);
  mysqli_stmt_close($stmt);
  if (!$row) {
    http_response_code(404);
    echo json_encode(["success" => false, "error" => "Deck not found"]);
    mysqli_close($conn);
    exit;
  }
  $owner = intval($row['assetOwner']);
  if ($owner !== $userId) {
    http_response_code(403);
    echo json_encode(["success" => false, "error" => "Not deck owner"]);
    mysqli_close($conn);
    exit;
  }

  // Load gamestate and edit the deck
  // We operate on p1MainDeck/p1Sideboard for the stored deck
  require_once __DIR__ . '/../SWUDeck/GamestateParser.php';
  require_once __DIR__ . '/../SWUDeck/ZoneClasses.php';
  require_once __DIR__ . '/../SWUDeck/ZoneAccessors.php';

  // Use the SWUDeck folder
  $gameName = $deckID;
  // Parse gamestate from SWUDeck/Games/{gameName}/Gamestate.txt
  ParseGamestate(__DIR__ . '/../SWUDeck/');

  // Choose target zone
  $targetArray = null;
  if ($zone === 'main') {
    global $p1MainDeck;
    $targetArray = &$p1MainDeck;
  } else {
    global $p1Sideboard;
    $targetArray = &$p1Sideboard;
  }

  $modified = false;
  if ($action === 'add') {
    for ($i = 0; $i < $count; $i++) {
      // Create a new Deck entry using same constructor used by parser
      array_push($targetArray, new MainDeck($cardID));
      $modified = true;
    }
  } else if ($action === 'remove') {
    // Remove up to $count occurrences using exact CardID match
    $removed = 0;
    for ($i = 0; $i < count($targetArray) && $removed < $count; $i++) {
      $obj = $targetArray[$i];
      // Use the object's CardID property for exact matching when available
      $objCardID = isset($obj->CardID) ? $obj->CardID : trim($obj->Serialize());
      if ($objCardID === $cardID) {
        array_splice($targetArray, $i, 1);
        $i--; // adjust index after removal
        $removed++;
        $modified = true;
      }
    }
    // If nothing was removed, return an error instead of a silent success
    if ($removed === 0) {
      mysqli_close($conn);
      http_response_code(404);
      echo json_encode(["success" => false, "error" => "Card not found in specified zone"]);
      exit;
    }
  }

  if ($modified) {
    // Persist gamestate
    WriteGamestate(__DIR__ . '/../SWUDeck/');
    mysqli_close($conn);
    $response = ["success" => true, "deckID" => $deckID, "action" => $action, "cardID" => $cardID, "zone" => $zone];
    if ($action === 'remove') $response['removed'] = $removed;
    else $response['added'] = $count;
    echo json_encode($response);
    exit;
  } else {
    mysqli_close($conn);
    echo json_encode(["success" => false, "error" => "No changes made"]);
    exit;
  }

?>

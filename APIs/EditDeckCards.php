<?php
// Maintenance gate — must precede any write.
// Rewrites a deck gamestate file. The deck-file rewrite walks those files, and autosave racing a
// format change is exactly what caused the Leader2 sideboard data loss.
require_once __DIR__ . '/../AppCore/SWU/Maintenance.php';
SWUMaintenanceRequire('SWUDeck', 'deck');
require_once __DIR__ . '/../AppCore/SWU/DeckEditCardID.php';

  // APIs/EditDeckCards.php
  // Modify several of a deck's cards at once for decks owned by the token's user.
  // Same permissions/requirements as APIs/EditDeckCard.php (OAuth token with the
  // 'editdecks' scope + deck ownership). When "overwrite" is truthy the deck's
  // main deck and sideboard are cleared before the supplied cards are applied.

  require_once "../Core/HTTPLibraries.php";
  require_once "../Database/ConnectionManager.php";

  // Read JSON input
  $input = file_get_contents('php://input');
  $data = json_decode($input, true);
  if (!is_array($data)) $data = [];

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
  // Overwrite flag: any truthy value (true, 1, "1", "true") clears the deck first
  $overwriteRaw = isset($data['overwrite']) ? $data['overwrite'] : false;
  $overwrite = ($overwriteRaw === true || $overwriteRaw === 1 || $overwriteRaw === "1" || (is_string($overwriteRaw) && strtolower($overwriteRaw) === "true"));
  $cards = isset($data['cards']) ? $data['cards'] : null;

  header('Content-Type: application/json');

  if ($deckID <= 0) {
    http_response_code(400);
    echo json_encode(["success" => false, "error" => "Missing or invalid deckID"]);
    exit;
  }
  if ($cards === null && !$overwrite) {
    http_response_code(400);
    echo json_encode(["success" => false, "error" => "Missing cards array"]);
    exit;
  }
  if ($cards === null) $cards = [];
  if (!is_array($cards)) {
    http_response_code(400);
    echo json_encode(["success" => false, "error" => "Invalid cards; must be an array"]);
    exit;
  }
  if (count($cards) > 200) {
    http_response_code(400);
    echo json_encode(["success" => false, "error" => "Too many cards; maximum 200 entries per request"]);
    exit;
  }

  // Normalise + validate every entry BEFORE touching the deck (atomic: all or nothing)
  $changes = [];
  foreach ($cards as $index => $entry) {
    if (!is_array($entry)) {
      http_response_code(400);
      echo json_encode(["success" => false, "error" => "Invalid cards[$index]; must be an object"]);
      exit;
    }
    // Resolve FFG UIDs and alternate printings to the earliest SET_NNN ID for storage.
    $cardID = isset($entry['cardID']) ? SWUDeckEditCardID($entry['cardID']) : '';
    $action = isset($entry['action']) ? strtolower($entry['action']) : 'add'; // add | remove
    $count = isset($entry['count']) ? intval($entry['count']) : 1;
    $zone = isset($entry['zone']) ? strtolower($entry['zone']) : 'main'; // main | side
    if ($cardID === '') {
      http_response_code(400);
      echo json_encode(["success" => false, "error" => "Missing cardID in cards[$index]"]);
      exit;
    }
    if (!in_array($action, ['add','remove'])) {
      http_response_code(400);
      echo json_encode(["success" => false, "error" => "Invalid action in cards[$index]; must be 'add' or 'remove'"]);
      exit;
    }
    if (!in_array($zone, ['main','side'])) {
      http_response_code(400);
      echo json_encode(["success" => false, "error" => "Invalid zone in cards[$index]; must be 'main' or 'side'"]);
      exit;
    }
    if ($count < 1) $count = 1;
    if ($overwrite && $action === 'remove') {
      http_response_code(400);
      echo json_encode(["success" => false, "error" => "Invalid action in cards[$index]; only 'add' is allowed with overwrite"]);
      exit;
    }
    $changes[] = ["cardID" => $cardID, "action" => $action, "count" => $count, "zone" => $zone];
  }

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

  global $p1MainDeck, $p1Sideboard;
  if (!is_array($p1MainDeck)) $p1MainDeck = [];
  if (!is_array($p1Sideboard)) $p1Sideboard = [];

  if ($overwrite) {
    $p1MainDeck = [];
    $p1Sideboard = [];
  }

  $ZoneFor = function($zone) {
    global $p1MainDeck, $p1Sideboard;
    if ($zone === 'main') return $p1MainDeck;
    return $p1Sideboard;
  };

  // Phase 1 (non-overwrite removes only): verify every remove can be satisfied
  // so a partially-applied batch never gets persisted.
  if (!$overwrite) {
    foreach ($changes as $index => $change) {
      if ($change['action'] !== 'remove') continue;
      $targetArray = $ZoneFor($change['zone']);
      $available = 0;
      foreach ($targetArray as $obj) {
        $objCardID = isset($obj->CardID) ? $obj->CardID : trim($obj->Serialize());
        if (SWUDeckEditCardID($objCardID) === $change['cardID']) $available++;
      }
      if ($available < $change['count']) {
        mysqli_close($conn);
        http_response_code(404);
        echo json_encode(["success" => false, "error" => "Card not found in specified zone", "index" => $index, "cardID" => $change['cardID'], "zone" => $change['zone']]);
        exit;
      }
    }
  }

  // Phase 2: apply all changes in memory, then persist once
  $results = [];
  foreach ($changes as $index => $change) {
    if ($change['zone'] === 'main') {
      $targetArray = &$p1MainDeck;
    } else {
      $targetArray = &$p1Sideboard;
    }
    if ($change['action'] === 'add') {
      for ($i = 0; $i < $change['count']; $i++) {
        // Create a new Deck entry using same constructor used by parser
        array_push($targetArray, new MainDeck($change['cardID']));
      }
      $results[] = ["index" => $index, "action" => "add", "cardID" => $change['cardID'], "zone" => $change['zone'], "added" => $change['count']];
    } else {
      // Remove up to count occurrences using canonical card identity (pre-verified above).
      $removed = 0;
      for ($i = 0; $i < count($targetArray) && $removed < $change['count']; $i++) {
        $obj = $targetArray[$i];
        // Use the object's CardID property when available.
        $objCardID = isset($obj->CardID) ? $obj->CardID : trim($obj->Serialize());
        if (SWUDeckEditCardID($objCardID) === $change['cardID']) {
          array_splice($targetArray, $i, 1);
          $i--; // adjust index after removal
          $removed++;
        }
      }
      $results[] = ["index" => $index, "action" => "remove", "cardID" => $change['cardID'], "zone" => $change['zone'], "removed" => $removed];
    }
    unset($targetArray); // break the reference before the next zone selection
  }

  // Persist gamestate (single write for the whole batch)
  WriteGamestate(__DIR__ . '/../SWUDeck/');
  mysqli_close($conn);
  echo json_encode(["success" => true, "deckID" => $deckID, "overwrite" => $overwrite, "changes" => $results]);
  exit;

?>

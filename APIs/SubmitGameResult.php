<?php

  require_once "../Core/HTTPLibraries.php";
  require_once "../Database/ConnectionManager.php";
  require_once "../APIKeys/APIKeys.php";
  require_once "../Core/StatsHelpers.php";
  require_once "../SWUDeck/GeneratedCode/GeneratedCardDictionaries.php";
  require_once "../Core/StatsBaseRegistry.php";
  require_once "../AccountFiles/AccountDatabaseAPI.php"; // ResolveFriendlyCode()
  require_once "../AppCore/SWU/Formats.php"; // SWUFormatIsPreview()
  require_once "../AppCore/SWU/Maintenance.php"; // SWUMaintenanceRequire()
  require_once "../AppCore/SWU/StatsIngress.php"; // SWUStatsIngressNormalize()

  $input = file_get_contents('php://input');
  $data = json_decode($input, true);

  $apiKey = isset($data["apiKey"]) ? $data["apiKey"] : "";
  if($apiKey != $petranakiAPIKey && $apiKey != $karabastAPIKey) {
	http_response_code(403);
	header('Content-Type: application/json');
	echo json_encode([
		"success" => false,
		"error" => "Invalid API key."
	]);
	exit;
  }

  // Freeze point. The SET_NNN migration rebuilds these tables from a snapshot and then RENAMEs the
  // originals away, so a row written between the build and the swap is discarded with no error.
  // 503 + Retry-After tells Karabast to retry rather than drop the submission. Placed after the key
  // check (do not advertise ops state to strangers) and before any write.
  SWUMaintenanceRequire('SWUDeck', 'stats');

  // Translate every incoming identifier to SET_NNN BEFORE anything is read or written. Karabast
  // sends FFG UIDs by contract; the tables are SET_NNN-keyed. Without this, each submission writes
  // UUID rows back into the tables the migration merged and re-fragments them within hours.
  // Accepts either shape, so a client can conform whenever it likes.
  $swuIngress = SWUStatsIngressNormalize($data);

	// Meta stats (the premier meta aggregate tables) are disabled if:
	// - It's a shared team deck
	// - The format is not 'premier'
	// - One or more players opts out of meta stats collection
	// The raw completedgame log is gated separately below ($recordCompletedGame): it records every
	// game EXCEPT open / opt-out / private-deck, tagged with its format.
	$format = isset($data['format']) ? strtolower($data['format']) : 'premier';
	$explicitOptOut = false;
	if (isset($data['disableMetaStats'])) {
		$explicitDisable = $data['disableMetaStats'];
		if (is_string($explicitDisable)) {
			$explicitDisable = strtolower($explicitDisable);
			$explicitDisable = ($explicitDisable === 'true' || $explicitDisable === '1');
		}
		if ($explicitDisable) {
			$explicitOptOut = true;
		}
	}
	$privateDeck = false;
	// Premier meta aggregates stay gated to premier + not-opted-out + not-private (UNCHANGED behavior).
	// $privateDeck may be set true in the deck-link blocks below; the completedgame gate is recomputed
	// after those blocks.
	// Derived from the format registry — see SWUStatsFormats() in AppCore/SWU/Formats.php. The
	// literal list this replaces was retyped in eight places and had already drifted from the UI
	// (padawan was accepted here but absent from both meta dropdowns, so it was unselectable).
	$disableMetaStats = !in_array($format, SWUStatsFormats(), true) || $explicitOptOut;
	// Preview formats used to write NO stats at all, on the reasoning that mock cards can be wrong,
	// mid-errata, or deleted on release day. They now record under their OWN format key — `format` is
	// part of the PRIMARY KEY on the meta tables, so preview rows can never merge with released-format
	// rows, and orphaned rows are cleaned up at sunset. Nothing here special-cases preview any more;
	// SWUStatsFormats() includes them like any other format.
	// See docs/superpowers/specs/2026-08-06-swu-preview-format-stats-design.md

  // Unresolvable identifiers are REJECTED, not partially recorded — but ONLY for a game that would
  // actually be recorded.
  //
  // ⚠ BREAKING, changed 2026-08-06 by explicit decision. This endpoint previously dropped an
  // unresolvable id at the narrowest granularity that still yielded a keyable row and returned
  // success — so a submission naming one bad card silently lost that card's stats while the caller
  // saw {"success": true}. Every affected column is a PRIMARY KEY component, so a row keyed on an
  // unmappable identifier can never aggregate with anything; a 400 the caller can act on beats a
  // quiet partial write.
  //
  // The preview/open exemption is NOT a softening — it repairs a regression. The gate was first
  // placed above, ahead of $format, so it rejected submissions this endpoint would have accepted and
  // then deliberately ignored: a PREVIEW game is played with hand-curated MOCK cards that need not
  // exist in SWUDeck's dictionary, and the Open/Goldfish/Hotseat wildcard pool includes preview sets
  // too. Both write NO stats by design, so an unresolvable id in them is expected and harmless.
  // Petranaki ships preview sets and so failed on every such game while Karabast — premier, real
  // cards — was unaffected. Rejecting a submission we were never going to record is wrong regardless
  // of which client hits it.
  //
  // A 400 is not retried, so a rejected game is lost rather than deferred. That is why this still
  // sits ahead of every write: a rejected submission must leave nothing behind. Contrast the 503
  // maintenance mode returns, which exists precisely so submissions ARE retried.
  // Three tiers, all decided by the registry (design doc §4):
  //   in SWUStatsFormats()     → record everything
  //   registered but not in it → 200, record nothing (open, goldfish, hotseat)
  //   not registered at all    → 400 below
  // Preview formats are IN the list: they are first-class formats separated by their own format key,
  // so no preview branch survives here. An absent format already defaulted to 'premier' above, so
  // absence never reaches the rejection.
  $swuRecordsNothing = !in_array($format, SWUStatsFormats(), true);
  if (!SWUFormatIsRegistered($format)) {
    http_response_code(400);
    header('Content-Type: application/json');
    error_log("SWU stats: rejected unregistered format '" . $format . "'");
    echo json_encode([
      "success" => false,
      "error"   => "Unrecognized format '" . $format . "'. Register it in AppCore/SWU/Formats.php "
                 . "or send a supported format. Nothing was recorded for this game.",
    ]);
    exit;
  }
  if (!$swuRecordsNothing
      && ($swuIngress['skipCompletedGame'] || $swuIngress['skipPlayer'][1]
          || $swuIngress['skipPlayer'][2] || $swuIngress['droppedCards'] > 0)) {
    http_response_code(400);
    header('Content-Type: application/json');
    echo json_encode([
      "success" => false,
      "error"   => "Unrecognized card identifier(s). Send SET_NNN codes (e.g. \"SOR_005\") or known "
                 . "FFG UIDs. Nothing was recorded for this game.",
      "details" => $swuIngress['notes']
    ]);
    exit;
  }

  $conn = GetLocalMySQLConnection();
		// Validate SWU tokens (if provided). Returns:
		//  - null  => no token provided
		//  - int   => user_id for valid token
		//  - false => token provided but invalid/expired
		function ValidateSWUToken($conn, $token) {
			if ($token === "" || $token === null) return null;
			$sql = "SELECT user_id, expires FROM oauth_access_tokens WHERE access_token = ?";
			$stmt = mysqli_stmt_init($conn);
			if (!mysqli_stmt_prepare($stmt, $sql)) return false;
			mysqli_stmt_bind_param($stmt, "s", $token);
			mysqli_stmt_execute($stmt);
			$result = mysqli_stmt_get_result($stmt);
			$row = mysqli_fetch_assoc($result);
			mysqli_stmt_close($stmt);
			if (!$row) return false;
			// Treat tokens without an associated user as invalid for this flow
			if (!isset($row['user_id']) || $row['user_id'] === null) return false;
			// Enforce expiry using the `expires` column
			if (isset($row['expires']) && $row['expires'] !== null && $row['expires'] !== '') {
				$expires = strtotime($row['expires']);
				if ($expires !== false && time() > $expires) return false;
			}
			return intval($row['user_id']);
		}
  $winner = $data["winner"];
  $firstPlayer = $data["firstPlayer"];
  $p1id = isset($data["p1id"]) ? $data["p1id"] : "";
  $p2id = isset($data["p2id"]) ? $data["p2id"] : "";

  $p1DeckLink = isset($data["p1DeckLink"]) ? $data["p1DeckLink"] : "";
  $p2DeckLink = isset($data["p2DeckLink"]) ? $data["p2DeckLink"] : "";
  // A deck link yields deck-level stats when it points at a SWUStats/SWUDeck game (…?gameName=<id>).
  // Prod uses swustats.net; in local dev the deck link is a loopback host (localhost:3100 /
  // host.docker.internal). Accepting loopback is safe here regardless of env — this endpoint never
  // dials the host, it only parses the ?gameName= id for a DB lookup, and prod links are swustats.net.
  // (DEVENV isn't set on the SWUDeck web container anyway, so it can't be the gate here.)
  $statsDeckLinkOK = function($link) {
    if (strpos((string)$link, 'swustats.net') !== false) return true;
    if (preg_match('#^https?://(localhost|127\.0\.0\.1|host\.docker\.internal)(:\d+)?/#i', (string)$link)) return true;
    return false;
  };
  // Resolve the ?gameName= identifier to a numeric deckID. A numeric id passes through unchanged
  // (existing consumers stay byte-identical — this endpoint is a forceteki contract), while a
  // 12-letter friendly code is resolved via ResolveFriendlyCode(), exactly as LoadDeck.php does.
  // Returns null when a friendly code can't be resolved so the caller can skip deck stats instead
  // of feeding a non-numeric string into integer deckID columns (mysqli STRICT fatals otherwise).
  $resolveDeckID = function($raw) {
    if (preg_match('/^[A-Za-z]{12}$/', (string)$raw)) {
      return ResolveFriendlyCode($raw); // int deckID, or null if unknown
    }
    return $raw; // numeric id -> unchanged
  };
  $p1SWUStatsToken = isset($data["p1SWUStatsToken"]) ? $data["p1SWUStatsToken"] : "";
  $p2SWUStatsToken = isset($data["p2SWUStatsToken"]) ? $data["p2SWUStatsToken"] : "";

	// Validate provided tokens and collect failures (don't exit on the first)
	$p1SWUUserId = ValidateSWUToken($conn, $p1SWUStatsToken);
	$p2SWUUserId = ValidateSWUToken($conn, $p2SWUStatsToken);
	$errors = [];
	if ($p1SWUStatsToken !== "" && $p1SWUUserId === false) {
		$errors[] = "Invalid or expired p1SWUStatsToken.";
	}
	if ($p2SWUStatsToken !== "" && $p2SWUUserId === false) {
		$errors[] = "Invalid or expired p2SWUStatsToken.";
	}
	if (count($errors) > 0) {
		http_response_code(401);
		header('Content-Type: application/json');
		$structured = [];
		if (in_array("Invalid or expired p1SWUStatsToken.", $errors)) {
			$structured['p1SWUStatsToken'] = 'Invalid or expired';
		}
		if (in_array("Invalid or expired p2SWUStatsToken.", $errors)) {
			$structured['p2SWUStatsToken'] = 'Invalid or expired';
		}
		echo json_encode([
			"success" => false,
			"errors" => $structured
		]);
		exit;
	}

  /*
  $logFile = '../logs/game_results.log';
  $logDir = dirname($logFile);
  if (!file_exists($logDir)) {
	mkdir($logDir, 0755, true);
  }
	*/

  function writeLog($message) {
	global $logFile;
	// Only log if debug mode is enabled
	if(true) return;
	$timestamp = date('Y-m-d H:i:s');
	$logMessage = "[$timestamp] $message\n";
	file_put_contents($logFile, $logMessage, FILE_APPEND);
  }

  writeLog("Received game result. P1 token: " . $p1SWUStatsToken . ", P2 token: " . $p2SWUStatsToken);

  if($statsDeckLinkOK($p1DeckLink)) {
	$arr = explode("gameName=", $p1DeckLink);
	if(count($arr) >= 2)
	{
		$arr = explode("&", $arr[1]);
		$deckID = $resolveDeckID($arr[0]);
	  if($deckID !== null && $deckID !== "") {
		$sql = "SELECT * FROM ownership WHERE assetType = 1 AND assetIdentifier = ?";
		$stmt = mysqli_stmt_init($conn);
		if (mysqli_stmt_prepare($stmt, $sql)) {
			mysqli_stmt_bind_param($stmt, "i", $deckID);
			mysqli_stmt_execute($stmt);
			$result = mysqli_stmt_get_result($stmt);
			$deckAsset = mysqli_fetch_assoc($result);
			mysqli_stmt_close($stmt);
		}
		$deckVisibility = isset($deckAsset["assetVisibility"]) ? $deckAsset["assetVisibility"] : -1;
		if($deckVisibility < 1000000 && $deckVisibility > 1000) { $disableMetaStats = true; $privateDeck = true; }
		$isDeckOwner = false;
		if($p1SWUStatsToken != "") {
			if ($p1SWUUserId !== null && isset($deckAsset["assetOwner"]) && $p1SWUUserId == $deckAsset["assetOwner"]) {
				$isDeckOwner = true;
			}
		}
		// If tie, $won is null so SaveDeckStats can handle it as a tie
		$won = ($winner == 1 ? true : ($winner == 2 ? false : null));
		$opponentData = isset($data["player2"]) ? $data["player2"] : null;
		// leaderID/baseID are PRIMARY KEY components — an unresolvable one cannot be keyed at all,
		// so that player's stat writes are skipped wholesale (the other player is unaffected).
		if(!$swuRecordsNothing && !$swuIngress['skipPlayer'][1]) SaveDeckStats($deckID, $data["player1"], $won, $firstPlayer == 1, $data["round"], $data["winnerHealth"], $data["gameName"], $disableMetaStats, $isDeckOwner, $opponentData, $format);
	  }
	}
  }
  if($statsDeckLinkOK($p2DeckLink)) {
	$arr = explode("gameName=", $p2DeckLink);
	if(count($arr) >= 2)
	{
		$arr = explode("&", $arr[1]);
		$deckID = $resolveDeckID($arr[0]);
	  if($deckID !== null && $deckID !== "") {
		$sql = "SELECT * FROM ownership WHERE assetType = 1 AND assetIdentifier = ?";
		$stmt = mysqli_stmt_init($conn);
		if (mysqli_stmt_prepare($stmt, $sql)) {
			mysqli_stmt_bind_param($stmt, "i", $deckID);
			mysqli_stmt_execute($stmt);
			$result = mysqli_stmt_get_result($stmt);
			$deckAsset = mysqli_fetch_assoc($result);
			mysqli_stmt_close($stmt);
		}
		$deckVisibility = isset($deckAsset["assetVisibility"]) ? $deckAsset["assetVisibility"] : -1;
		if($deckVisibility < 1000000 && $deckVisibility > 1000) { $disableMetaStats = true; $privateDeck = true; }
		$isDeckOwner = false;
		if($p2SWUStatsToken != "") {
			if ($p2SWUUserId !== null && isset($deckAsset["assetOwner"]) && $p2SWUUserId == $deckAsset["assetOwner"]) {
				$isDeckOwner = true;
			}
		}
		// If tie, $won is null so SaveDeckStats can handle it as a tie
		$won = ($winner == 2 ? true : ($winner == 1 ? false : null));
		$opponentData = isset($data["player1"]) ? $data["player1"] : null;
		if(!$swuRecordsNothing && !$swuIngress['skipPlayer'][2]) SaveDeckStats($deckID, $data["player2"], $won, $firstPlayer == 2, $data["round"], $data["winnerHealth"], $data["gameName"], $disableMetaStats, $isDeckOwner, $opponentData, $format);
	  }
	}
  }

  // Record the raw per-game row for every game EXCEPT open / preview / opt-out / private-deck.
  // Decoupled from $disableMetaStats so non-premier (eternal/twinsuns) games are logged with their
  // format, while premier meta aggregates (in SaveDeckStats) stay premier-gated as before. Preview is
  // excluded because this log feeds meta and matchup browsing, and rows referencing mock CardIDs
  // would linger after the mocks are deleted on release day.
  $recordCompletedGame = !$swuRecordsNothing && !$explicitOptOut && !$privateDeck;
  if($recordCompletedGame) {

	// Check for null winHero or loseHero
	if (!isset($data["winHero"]) || $data["winHero"] === null || !isset($data["loseHero"]) || $data["loseHero"] === null) {
	  http_response_code(400);
	  header('Content-Type: application/json');
	  // Remove API key if present before logging/serializing
	  $dataToLog = $data;
	  if (isset($dataToLog['apiKey'])) {
		unset($dataToLog['apiKey']);
	  }
	  echo json_encode([
		"success" => false,
		"error" => "Missing winHero or loseHero in request data.",
		"rawData" => $dataToLog
	  ]);
	  exit;
	}

	// WinningHero/LosingHero is exactly where the unreadable history accumulated — zzzzzzz001,
	// abcdefgMTL, blanks, a stray ms timestamp. Letting a new unresolvable value in would re-open
	// that hole, so the row is skipped and logged instead of written verbatim.
	if ($swuIngress['skipCompletedGame']) {
	  error_log("SWU stats ingress: completedgame row skipped for game " . ($data['gameName'] ?? '?'));
	} else {

	$columns = "WinningHero, LosingHero, NumTurns, WinnerDeck, LoserDeck, WinnerHealth, FirstPlayer, WinningPlayer, Format";
	$values = "?, ?, ?, ?, ?, ?, ?, ?, ?";

	if($p1id != "" && $p1id != "-") {
	  $columns .= ", " . ($winner == 1 ? "WinningPID" : "LosingPID");
	  $values .= ", " . $p1id;
	}
	if($p2id != "" && $p2id != "-") {
	  $columns .= ", " . ($winner == 2 ? "WinningPID" : "LosingPID");
	  $values .= ", " . $p2id;
	}

	$sql = "INSERT INTO completedgame (" . $columns . ") VALUES (" . $values . ");";
	$stmt = mysqli_stmt_init($conn);
	$gameResultID = 0;
	if(mysqli_stmt_prepare($stmt, $sql)) {
  $winnerDeck = isset($data["winnerDeck"]) ? $data["winnerDeck"] : "";
  if (is_array($winnerDeck)) {
	// Special handling for array winnerDeck (for now, just set to '...')
	$winnerDeck = '...';
  } else {
	$winnerDeck = substr($winnerDeck, 0, 999);
  }
  $loserDeck = isset($data["loserDeck"]) ? $data["loserDeck"] : "";
  if (is_array($loserDeck)) {
	// Special handling for array loserDeck (for now, just set to '...')
	$loserDeck = '...';
  } else {
	$loserDeck = substr($loserDeck, 0, 999);
  }
  mysqli_stmt_bind_param($stmt, "sssssssss", $data["winHero"], $data["loseHero"], $data["round"], $winnerDeck, $loserDeck, $data["winnerHealth"], $firstPlayer, $winner, $format);
  mysqli_stmt_execute($stmt);
  $gameResultID = mysqli_insert_id($conn);
  mysqli_stmt_close($stmt);
  } // end: completedgame row not skipped
	}
	mysqli_close($conn);
  }

	// Return success JSON for the main request processing. Include the inserted gameResultID when present.
	header('Content-Type: application/json');
	$resp = ["success" => true];
	echo json_encode($resp);
	// Finish the request; SaveDeckStats is defined below and may be used by other callers.
	exit;

// NormalizeBaseID() now lives in Core/StatsBaseRegistry.php (single source of truth for
// base canonicalization + color/type resolution), included at the top of this file.

  //Parameters:
  // won: true if this player won the game, false if they lost
  // wasFirstPlayer: true if this player was the first player in the game, false if they were the second player
function SaveDeckStats($deckID, $playerData, $won, $wasFirstPlayer, $numRounds, $winnerHealth, $gameName, $disableMetaStats, $isDeckOwner, $opponentData = null, $format = 'premier') {
	global $input;
	// "open" games produce no deck stats (consistent with the completedgame exclusion).
	// Only formats the registry lists as stats-producing write deck stats.
	if (!in_array($format, SWUStatsFormats(), true)) { return; }
	if (is_string($playerData)) {
		$playerJSON = json_decode($playerData, true);
	} else {
		$playerJSON = $playerData;
	}
	if (is_string($opponentData)) {
		$opponentJSON = json_decode($opponentData, true);
	} else {
		$opponentJSON = $opponentData;
	}
	$leaderID = $playerJSON["leader"];
	$baseID = NormalizeBaseID($playerJSON["base"]);
	// Opponent identity is derived by cross-referencing the other player's own
	// leader/base GUIDs (the standard). Clients may still send the per-player
	// opposingHero/opposingBase/opposingBaseColor fields, but they are ignored.
	$opponentLeaderID = isset($opponentJSON["leader"]) ? $opponentJSON["leader"] : "";
	$opponentBaseGuid = isset($opponentJSON["base"]) ? $opponentJSON["base"] : "";
	$source = $isDeckOwner ? 1 : 0;

	$conn = GetLocalMySQLConnection();

	$sql = "SELECT COUNT(*) FROM deckstats WHERE deckID = ? AND source = ? AND format = ?";
	$stmt = mysqli_stmt_init($conn);
	if (mysqli_stmt_prepare($stmt, $sql)) {
			mysqli_stmt_bind_param($stmt, "iis", $deckID, $source, $format);
			mysqli_stmt_execute($stmt);
			mysqli_stmt_bind_result($stmt, $count);
			mysqli_stmt_fetch($stmt);
			mysqli_stmt_close($stmt);
	}
	if ($count == 0) {
		$sql = "INSERT INTO deckstats (deckID, source, format) VALUES (?, ?, ?)";
		$stmt = mysqli_stmt_init($conn);
		if (mysqli_stmt_prepare($stmt, $sql)) {
				mysqli_stmt_bind_param($stmt, "iis", $deckID, $source, $format);
				mysqli_stmt_execute($stmt);
				mysqli_stmt_close($stmt);
		}
	}

	if(!$disableMetaStats) {
		// Compute the week number (number of full weeks since 2025-09-20).
		// Week 0 is historical; nearby logic lives in GetWeekSinceRef().
		$week = GetWeekSinceRef('2025-09-20');
		// deckmetastats
		$sql = "SELECT COUNT(*) FROM deckmetastats WHERE leaderID = ? AND baseID = ? AND week = ? AND format = ?";
		$stmt = mysqli_stmt_init($conn);
		if (mysqli_stmt_prepare($stmt, $sql)) {
				mysqli_stmt_bind_param($stmt, "ssis", $leaderID, $baseID, $week, $format);
				mysqli_stmt_execute($stmt);
				mysqli_stmt_bind_result($stmt, $count);
				mysqli_stmt_fetch($stmt);
				mysqli_stmt_close($stmt);
		}
		if ($count == 0) {
				$sql = "INSERT INTO deckmetastats (leaderID, baseID, week, format) VALUES (?, ?, ?, ?)";
				$stmt = mysqli_stmt_init($conn);
				if (mysqli_stmt_prepare($stmt, $sql)) {
						mysqli_stmt_bind_param($stmt, "ssis", $leaderID, $baseID, $week, $format);
						mysqli_stmt_execute($stmt);
						mysqli_stmt_close($stmt);
				}
		}


	}

	$cardsResourced = 0;
	//cardResults and turnResults
	$cardResults = $playerJSON["cardResults"];
	$turnResults = $playerJSON["turnResults"];
	for($i = 0; $i < count($cardResults); $i++) {
		$card = $cardResults[$i];
		$sql = "SELECT COUNT(*) FROM carddeckstats WHERE cardID = ? AND deckID = ? AND source = ? AND format = ?";
		$stmt = mysqli_stmt_init($conn);
		if (mysqli_stmt_prepare($stmt, $sql)) {
			mysqli_stmt_bind_param($stmt, "siis", $card["cardId"], $deckID, $source, $format);
			mysqli_stmt_execute($stmt);
			mysqli_stmt_bind_result($stmt, $cardCount);
			mysqli_stmt_fetch($stmt);
			mysqli_stmt_close($stmt);
		}

		if ($cardCount == 0) {
			$sql = "INSERT INTO carddeckstats (cardID, deckID, source, format) VALUES (?, ?, ?, ?)";
			$stmt = mysqli_stmt_init($conn);
			if (mysqli_stmt_prepare($stmt, $sql)) {
				mysqli_stmt_bind_param($stmt, "siis", $card["cardId"], $deckID, $source, $format);
				mysqli_stmt_execute($stmt);
				mysqli_stmt_close($stmt);
			}
		}
		$cardsResourced += $card["resourced"];
		$timesIncluded = 1;
		$timesIncludedInWins = ($won === true) ? 1 : 0;
		$timesPlayed = $card["played"];
		$timesPlayedInWins = ($won === true) ? $card["played"] : 0;
		$timesResourced = $card["resourced"];
		$timesResourcedInWins = ($won === true) ? $card["resourced"] : 0;
		$timesDiscarded = $card["discarded"];
		$timesDiscardedInWins = ($won === true) ? $card["discarded"] : 0;
		$timesDrawn = $card["drawn"];
		$timesDrawnInWins = ($won === true) ? $card["drawn"] : 0;
		$sql = "UPDATE carddeckstats SET
				timesIncluded = timesIncluded + ?,
				timesIncludedInWins = timesIncludedInWins + ?,
				timesPlayed = timesPlayed + ?,
				timesPlayedInWins = timesPlayedInWins + ?,
				timesResourced = timesResourced + ?,
				timesResourcedInWins = timesResourcedInWins + ?,
				timesDiscarded = timesDiscarded + ?,
				timesDiscardedInWins = timesDiscardedInWins + ?,
				timesDrawn = timesDrawn + ?,
				timesDrawnInWins = timesDrawnInWins + ?
				WHERE cardID = ? AND deckID = ? AND source = ? AND format = ?";
		$stmt = mysqli_stmt_init($conn);
		if (mysqli_stmt_prepare($stmt, $sql)) {
			// 10 integer stats, then cardID (STRING — varchar(16); SWUSim sends the raw SET_NNN CardID
			// for any card without an FFG UID), then deckID (int), source (int), format (string).
			// This was "iiiiiiiiiiisis" — one i too many, so cardID bound as an int and a non-numeric
			// id fataled the whole request ("Truncated incorrect DOUBLE value") before completedgame.
			mysqli_stmt_bind_param($stmt, "iiiiiiiiiisiis", $timesIncluded, $timesIncludedInWins, $timesPlayed, $timesPlayedInWins, $timesResourced, $timesResourcedInWins, $timesDiscarded, $timesDiscardedInWins, $timesDrawn, $timesDrawnInWins, $card["cardId"], $deckID, $source, $format);
			mysqli_stmt_execute($stmt);
			mysqli_stmt_close($stmt);
		}

		if(!$disableMetaStats) {
		// Update cardmetastats
			$sql = "SELECT COUNT(*) FROM cardmetastats WHERE cardID = ? AND week = ? AND format = ?";
			$stmt = mysqli_stmt_init($conn);
			if (mysqli_stmt_prepare($stmt, $sql)) {
				mysqli_stmt_bind_param($stmt, "sis", $card["cardId"], $week, $format);
				mysqli_stmt_execute($stmt);
				mysqli_stmt_bind_result($stmt, $metaCount);
				mysqli_stmt_fetch($stmt);
				mysqli_stmt_close($stmt);
			}

			if ($metaCount == 0) {
				$sql = "INSERT INTO cardmetastats (cardID, week, format) VALUES (?, ?, ?)";
				$stmt = mysqli_stmt_init($conn);
				if (mysqli_stmt_prepare($stmt, $sql)) {
					mysqli_stmt_bind_param($stmt, "sis", $card["cardId"], $week, $format);
					mysqli_stmt_execute($stmt);
					mysqli_stmt_close($stmt);
				}
			}

			$sql = "UPDATE cardmetastats SET
					timesIncluded = timesIncluded + ?,
					timesIncludedInWins = timesIncludedInWins + ?,
					timesPlayed = timesPlayed + ?,
					timesPlayedInWins = timesPlayedInWins + ?,
					timesResourced = timesResourced + ?,
					timesResourcedInWins = timesResourcedInWins + ?
					WHERE cardID = ? AND week = ? AND format = ?";
			$stmt = mysqli_stmt_init($conn);
			if (mysqli_stmt_prepare($stmt, $sql)) {
				// 6 integer stats, then cardID (string), then week (int), then format (string)
				mysqli_stmt_bind_param($stmt, "iiiiiisis", $timesIncluded, $timesIncludedInWins, $timesPlayed, $timesPlayedInWins, $timesResourced, $timesResourcedInWins, $card["cardId"], $week, $format);
				mysqli_stmt_execute($stmt);
				mysqli_stmt_close($stmt);
			}
		}
	}

	$sql = "UPDATE deckstats SET
			numPlays = numPlays + 1,
			totalTurns = totalTurns + ?,
			totalCardsResourced = totalCardsResourced + ?";

       if ($wasFirstPlayer) {
	       $sql .= ", playsGoingFirst = playsGoingFirst + 1";
       }
       if ($won === true) {
	       $sql .= ", numWins = numWins + 1,
			 turnsInWins = turnsInWins + ?,
			 cardsResourcedInWins = cardsResourcedInWins + ?,
			 remainingHealthInWins = remainingHealthInWins + ?";
	       if ($wasFirstPlayer) {
		       $sql .= ", winsGoingFirst = winsGoingFirst + 1";
	       } else {
		       $sql .= ", winsGoingSecond = winsGoingSecond + 1";
	       }
       }
       $sql .= " WHERE deckID = ? AND source = ? AND format = ?";
       $stmt = mysqli_stmt_init($conn);
       if (mysqli_stmt_prepare($stmt, $sql)) {
	       if ($won === true) {
		       mysqli_stmt_bind_param($stmt, "iiiiiiis", $numRounds, $cardsResourced, $numRounds, $cardsResourced, $winnerHealth, $deckID, $source, $format);
	       } else {
		       mysqli_stmt_bind_param($stmt, "iiiis", $numRounds, $cardsResourced, $deckID, $source, $format);
	       }
	       mysqli_stmt_execute($stmt);
	       mysqli_stmt_close($stmt);
       }

	   if(!$disableMetaStats) {

		  $sql = "UPDATE deckmetastats SET
			  numPlays = numPlays + 1,
			  totalTurns = totalTurns + ?,
			  totalCardsResourced = totalCardsResourced + ?";
		  if ($won === true) {
			  $sql .= ", numWins = numWins + 1,
				  turnsInWins = turnsInWins + ?,
				  cardsResourcedInWins = cardsResourcedInWins + ?,
				  remainingHealthInWins = remainingHealthInWins + ?";
		  }
		  $sql .= " WHERE leaderID = ? AND baseID = ? AND week = ? AND format = ?";
		  $stmt = mysqli_stmt_init($conn);
		  if (mysqli_stmt_prepare($stmt, $sql)) {
			  if ($won === true) {
			  mysqli_stmt_bind_param($stmt, "iiiiissis",
				  $numRounds,
				  $cardsResourced,
				  $numRounds,
				  $cardsResourced,
				  $winnerHealth,
				  $leaderID,
				  $baseID,
				  $week,
				  $format
			  );
			  } else {
			  mysqli_stmt_bind_param($stmt, "iissis",
				  $numRounds,
				  $cardsResourced,
				  $leaderID,
				  $baseID,
				  $week,
				  $format
			  );
			  }
			  mysqli_stmt_execute($stmt);
			  mysqli_stmt_close($stmt);
		  }

	   // deckmetamatchupstats update (moved here)
	   // $opponentLeaderID / $opponentBaseGuid come from the opponent player's own
	   // leader/base GUIDs (set at the top of this function).
	   $opponentBaseID = $opponentBaseGuid !== "" ? NormalizeBaseID($opponentBaseGuid) : "";
	   if ($opponentLeaderID !== "" && $opponentBaseID !== "") {
		   $sql = "SELECT COUNT(*) FROM deckmetamatchupstats WHERE leaderID = ? AND baseID = ? AND opponentLeaderID = ? AND opponentBaseID = ? AND week = ? AND format = ?";
		   $stmt = mysqli_stmt_init($conn);
		   if (mysqli_stmt_prepare($stmt, $sql)) {
			   mysqli_stmt_bind_param($stmt, "ssssis", $leaderID, $baseID, $opponentLeaderID, $opponentBaseID, $week, $format);
			   mysqli_stmt_execute($stmt);
			   mysqli_stmt_bind_result($stmt, $matchupCount);
			   mysqli_stmt_fetch($stmt);
			   mysqli_stmt_close($stmt);
		   }
		   if ($matchupCount == 0) {
			   $sql = "INSERT INTO deckmetamatchupstats (leaderID, baseID, opponentLeaderID, opponentBaseID, week, format) VALUES (?, ?, ?, ?, ?, ?)";
			   $stmt = mysqli_stmt_init($conn);
			   if (mysqli_stmt_prepare($stmt, $sql)) {
				   mysqli_stmt_bind_param($stmt, "ssssis", $leaderID, $baseID, $opponentLeaderID, $opponentBaseID, $week, $format);
				   mysqli_stmt_execute($stmt);
				   mysqli_stmt_close($stmt);
			   }
		   }
		  $updateSql = "UPDATE deckmetamatchupstats SET
			  numPlays = numPlays + 1,
			  totalTurns = totalTurns + ?,
			  totalCardsResourced = totalCardsResourced + ?";
		  if ($won === true) {
			  $updateSql .= ", numWins = numWins + 1,
				  turnsInWins = turnsInWins + ?,
				  cardsResourcedInWins = cardsResourcedInWins + ?,
				  remainingHealthInWins = remainingHealthInWins + ?";
			  if ($wasFirstPlayer) {
				  $updateSql .= ", winsGoingFirst = winsGoingFirst + 1";
			  } else {
				  $updateSql .= ", winsGoingSecond = winsGoingSecond + 1";
			  }
		  }
		  if ($wasFirstPlayer) {
			  $updateSql .= ", playsGoingFirst = playsGoingFirst + 1";
		  }
		  $updateSql .= " WHERE leaderID = ? AND baseID = ? AND opponentLeaderID = ? AND opponentBaseID = ? AND week = ? AND format = ?";
		  $stmt = mysqli_stmt_init($conn);
		  if (mysqli_stmt_prepare($stmt, $updateSql)) {
			  if ($won === true) {
				  // If won, 5 stats + 5 keys = 10 args
				  mysqli_stmt_bind_param($stmt, "iiiiissssis",
					  $numRounds,
					  $cardsResourced,
					  $numRounds,
					  $cardsResourced,
					  $winnerHealth,
					  $leaderID,
					  $baseID,
					  $opponentLeaderID,
					  $opponentBaseID,
					  $week,
					  $format
				  );
			  } else {
				  // If not won, 2 stats + 5 keys = 7 args
				  mysqli_stmt_bind_param($stmt, "iissssis",
					  $numRounds,
					  $cardsResourced,
					  $leaderID,
					  $baseID,
					  $opponentLeaderID,
					  $opponentBaseID,
					  $week,
					  $format
				  );
			  }
			  mysqli_stmt_execute($stmt);
			  mysqli_stmt_close($stmt);
		  }
	   }
	}

	$leaderID = $opponentLeaderID;

	// Classify the opponent base from the opponent player's own base GUID.
	$resolved = $opponentBaseGuid !== "" ? ResolveOpponentBase($opponentBaseGuid) : null;
	$wins = ($won === true) ? 1 : 0;
	$total = 1;

	if ($resolved && $resolved["kind"] === "named") {
		// Rare/Special base — tracked individually by base identity.
		$namedBaseID = $resolved["baseID"];
		$sql = "INSERT INTO opponentnamedbasestats (deckID, leaderID, baseID, source, format, wins, total) VALUES (?, ?, ?, ?, ?, ?, ?)
		        ON DUPLICATE KEY UPDATE wins = wins + VALUES(wins), total = total + VALUES(total)";
		$stmt = mysqli_stmt_init($conn);
		if (mysqli_stmt_prepare($stmt, $sql)) {
			mysqli_stmt_bind_param($stmt, "issisii", $deckID, $leaderID, $namedBaseID, $source, $format, $wins, $total);
			mysqli_stmt_execute($stmt);
			mysqli_stmt_close($stmt);
		}
	} else {
		// Common base -> color x type wide columns; unresolved -> skipped (no color).
		if ($resolved) {
			$color = $resolved["color"];
			$typeSuffix = StatsTypeColumnSuffix($resolved["type"]);
		} else {
			$color = "";
			$typeSuffix = "";
		}

		if ($color != "") {
			$winColumn   = "winsVs"  . ucfirst($color) . $typeSuffix;
			$totalColumn = "totalVs" . ucfirst($color) . $typeSuffix;

			$sql = "SELECT COUNT(*) FROM opponentdeckstats WHERE deckID = ? AND leaderID = ? AND source = ? AND format = ?";
			$stmt = mysqli_stmt_init($conn);
			if (mysqli_stmt_prepare($stmt, $sql)) {
				mysqli_stmt_bind_param($stmt, "isis", $deckID, $leaderID, $source, $format);
				mysqli_stmt_execute($stmt);
				mysqli_stmt_bind_result($stmt, $count);
				mysqli_stmt_fetch($stmt);
				mysqli_stmt_close($stmt);
			}

			if ($count == 0) {
				$sql = "INSERT INTO opponentdeckstats (deckID, leaderID, source, format) VALUES (?, ?, ?, ?)";
				$stmt = mysqli_stmt_init($conn);
				if (mysqli_stmt_prepare($stmt, $sql)) {
					mysqli_stmt_bind_param($stmt, "isis", $deckID, $leaderID, $source, $format);
					mysqli_stmt_execute($stmt);
					mysqli_stmt_close($stmt);
				}
			}

			$sql = "UPDATE opponentdeckstats SET $winColumn = $winColumn + ?, $totalColumn = $totalColumn + ? WHERE deckID = ? AND leaderID = ? AND source = ? AND format = ?";
			$stmt = mysqli_stmt_init($conn);
			if (mysqli_stmt_prepare($stmt, $sql)) {
				mysqli_stmt_bind_param($stmt, "iiisis", $wins, $total, $deckID, $leaderID, $source, $format);
				mysqli_stmt_execute($stmt);
				mysqli_stmt_close($stmt);
			}
		}
	}

	mysqli_close($conn);
}

?>

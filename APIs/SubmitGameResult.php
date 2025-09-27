<?php

  require_once "../Core/HTTPLibraries.php";
  require_once "../Database/ConnectionManager.php";
  require_once "../APIKeys/APIKeys.php";

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

  $disableMetaStats = false;

  $conn = GetLocalMySQLConnection();

  $winner = $data["winner"];
  $firstPlayer = $data["firstPlayer"];
  $p1id = $data["p1id"];
  $p2id = $data["p2id"];

  $p1DeckLink = $data["p1DeckLink"];
  $p2DeckLink = $data["p2DeckLink"];
  $p1SWUStatsToken = isset($data["p1SWUStatsToken"]) ? $data["p1SWUStatsToken"] : "";
  $p2SWUStatsToken = isset($data["p2SWUStatsToken"]) ? $data["p2SWUStatsToken"] : "";

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

  if(strpos($p1DeckLink, 'swustats.net') !== false) {
	$arr = explode("gameName=", $p1DeckLink);
	if(count($arr) >= 2)
	{
		$arr = explode("&", $arr[1]);
		$deckID = $arr[0];
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
		if($deckVisibility < 1000000 && $deckVisibility > 1000) $disableMetaStats = true;
		$isDeckOwner = false;
		if($p1SWUStatsToken != "") {
			$sql = "SELECT user_id FROM oauth_access_tokens WHERE access_token = ?";
			$stmt = mysqli_stmt_init($conn);
			if (mysqli_stmt_prepare($stmt, $sql)) {
				mysqli_stmt_bind_param($stmt, "s", $p1SWUStatsToken);
				mysqli_stmt_execute($stmt);
				$result = mysqli_stmt_get_result($stmt);
				$token = mysqli_fetch_assoc($result);
				mysqli_stmt_close($stmt);
				if($token && isset($deckAsset["assetOwner"]) && $token["user_id"] == $deckAsset["assetOwner"]) {
					$isDeckOwner = true;
				}
			}
		}
		SaveDeckStats($deckID, $data["player1"], $winner == 1, $firstPlayer == 1, $data["round"], $data["winnerHealth"], $data["gameName"], $disableMetaStats, $isDeckOwner);
	}
  }
  if(strpos($p2DeckLink, 'swustats.net') !== false) {
	$arr = explode("gameName=", $p2DeckLink);
	if(count($arr) >= 2)
	{
		$arr = explode("&", $arr[1]);
		$deckID = $arr[0];
		
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
		if($deckVisibility < 1000000 && $deckVisibility > 1000) $disableMetaStats = true;
		$isDeckOwner = false;
		if($p2SWUStatsToken != "") {
			$sql = "SELECT user_id FROM oauth_access_tokens WHERE access_token = ?";
			$stmt = mysqli_stmt_init($conn);
			if (mysqli_stmt_prepare($stmt, $sql)) {
				mysqli_stmt_bind_param($stmt, "s", $p2SWUStatsToken);
				mysqli_stmt_execute($stmt);
				$result = mysqli_stmt_get_result($stmt);
				$token = mysqli_fetch_assoc($result);
				mysqli_stmt_close($stmt);
				if($token && isset($deckAsset["assetOwner"]) && $token["user_id"] == $deckAsset["assetOwner"]) {
					$isDeckOwner = true;
				}
			}
		}
		SaveDeckStats($deckID, $data["player2"], $winner == 2, $firstPlayer == 2, $data["round"], $data["winnerHealth"], $data["gameName"], $disableMetaStats, $isDeckOwner);
	}
  }

  if(!$disableMetaStats) {

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
	
	$columns = "WinningHero, LosingHero, NumTurns, WinnerDeck, LoserDeck, WinnerHealth, FirstPlayer, WinningPlayer";
	$values = "?, ?, ?, ?, ?, ?, ?, ?";
  
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
  $winnerDeck = $data["winnerDeck"];
  if (is_array($winnerDeck)) {
	// Special handling for array winnerDeck (for now, just set to '...')
	$winnerDeck = '...';
  } else {
	$winnerDeck = substr($winnerDeck, 0, 999);
  }
  $loserDeck = $data["loserDeck"];
  if (is_array($loserDeck)) {
	// Special handling for array loserDeck (for now, just set to '...')
	$loserDeck = '...';
  } else {
	$loserDeck = substr($loserDeck, 0, 999);
  }
  mysqli_stmt_bind_param($stmt, "ssssssss", $data["winHero"], $data["loseHero"], $data["round"], $winnerDeck, $loserDeck, $data["winnerHealth"], $firstPlayer, $winner);
  mysqli_stmt_execute($stmt);
  $gameResultID = mysqli_insert_id($conn);
  mysqli_stmt_close($stmt);
	}
	mysqli_close($conn);
  }

  //Parameters:
  // won: true if this player won the game, false if they lost
  // wasFirstPlayer: true if this player was the first player in the game, false if they were the second player
function SaveDeckStats($deckID, $playerData, $won, $wasFirstPlayer, $numRounds, $winnerHealth, $gameName, $disableMetaStats, $isDeckOwner) {
	global $input;
	if (is_string($playerData)) {
		$playerJSON = json_decode($playerData, true);
	} else {
		$playerJSON = $playerData;
	}
	$leaderID = $playerJSON["leader"];
	$baseID = $playerJSON["base"];
	$source = $isDeckOwner ? 1 : 0;

	$conn = GetLocalMySQLConnection();

	$sql = "SELECT COUNT(*) FROM deckstats WHERE deckID = ? AND source = ?";
	$stmt = mysqli_stmt_init($conn);
	if (mysqli_stmt_prepare($stmt, $sql)) {
			mysqli_stmt_bind_param($stmt, "ii", $deckID, $source);
			mysqli_stmt_execute($stmt);
			mysqli_stmt_bind_result($stmt, $count);
			mysqli_stmt_fetch($stmt);
			mysqli_stmt_close($stmt);
	}
	if ($count == 0) {
		$sql = "INSERT INTO deckstats (deckID, source) VALUES (?, ?)";
		$stmt = mysqli_stmt_init($conn);
		if (mysqli_stmt_prepare($stmt, $sql)) {
				mysqli_stmt_bind_param($stmt, "ii", $deckID, $source);
				mysqli_stmt_execute($stmt);
				mysqli_stmt_close($stmt);
		}
	}
	
	if(!$disableMetaStats) {
		// Compute the week number (number of full weeks since 2025-09-20).
		// Week 0 is historical; nearby logic lives in GetWeekSinceRef().
		$week = GetWeekSinceRef('2025-09-20');
		// deckmetastats
		$sql = "SELECT COUNT(*) FROM deckmetastats WHERE leaderID = ? AND baseID = ? AND week = ?";
		$stmt = mysqli_stmt_init($conn);
		if (mysqli_stmt_prepare($stmt, $sql)) {
				mysqli_stmt_bind_param($stmt, "ssi", $leaderID, $baseID, $week);
				mysqli_stmt_execute($stmt);
				mysqli_stmt_bind_result($stmt, $count);
				mysqli_stmt_fetch($stmt);
				mysqli_stmt_close($stmt);
		}
		if ($count == 0) {
				$sql = "INSERT INTO deckmetastats (leaderID, baseID, week) VALUES (?, ?, ?)";
				$stmt = mysqli_stmt_init($conn);
				if (mysqli_stmt_prepare($stmt, $sql)) {
						mysqli_stmt_bind_param($stmt, "ssi", $leaderID, $baseID, $week);
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
		$sql = "SELECT COUNT(*) FROM carddeckstats WHERE cardID = ? AND deckID = ? AND source = ?";
		$stmt = mysqli_stmt_init($conn);
		if (mysqli_stmt_prepare($stmt, $sql)) {
			mysqli_stmt_bind_param($stmt, "sii", $card["cardId"], $deckID, $source);
			mysqli_stmt_execute($stmt);
			mysqli_stmt_bind_result($stmt, $cardCount);
			mysqli_stmt_fetch($stmt);
			mysqli_stmt_close($stmt);
		}

		if ($cardCount == 0) {
			$sql = "INSERT INTO carddeckstats (cardID, deckID, source) VALUES (?, ?, ?)";
			$stmt = mysqli_stmt_init($conn);
			if (mysqli_stmt_prepare($stmt, $sql)) {
				mysqli_stmt_bind_param($stmt, "sii", $card["cardId"], $deckID, $source);
				mysqli_stmt_execute($stmt);
				mysqli_stmt_close($stmt);
			}
		}
		$cardsResourced += $card["resourced"];
		$timesIncluded = 1;
		$timesIncludedInWins = $won ? 1 : 0;
		$timesPlayed = $card["played"];
		$timesPlayedInWins = $won ? $card["played"] : 0;
		$timesResourced = $card["resourced"];
		$timesResourcedInWins = $won ? $card["resourced"] : 0;
		$timesDiscarded = $card["discarded"];
		$timesDiscardedInWins = $won ? $card["discarded"] : 0;
		$timesDrawn = $card["drawn"];
		$timesDrawnInWins = $won ? $card["drawn"] : 0;
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
				WHERE cardID = ? AND deckID = ? AND source = ?";
		$stmt = mysqli_stmt_init($conn);
		if (mysqli_stmt_prepare($stmt, $sql)) {
			mysqli_stmt_bind_param($stmt, "iiiiiiiiiiisi", $timesIncluded, $timesIncludedInWins, $timesPlayed, $timesPlayedInWins, $timesResourced, $timesResourcedInWins, $timesDiscarded, $timesDiscardedInWins, $timesDrawn, $timesDrawnInWins, $card["cardId"], $deckID, $source);
			mysqli_stmt_execute($stmt);
			mysqli_stmt_close($stmt);
		}

		if(!$disableMetaStats) {
		// Update cardmetastats
			$sql = "SELECT COUNT(*) FROM cardmetastats WHERE cardID = ? AND week = ?";
			$stmt = mysqli_stmt_init($conn);
			if (mysqli_stmt_prepare($stmt, $sql)) {
				mysqli_stmt_bind_param($stmt, "si", $card["cardId"], $week);
				mysqli_stmt_execute($stmt);
				mysqli_stmt_bind_result($stmt, $metaCount);
				mysqli_stmt_fetch($stmt);
				mysqli_stmt_close($stmt);
			}

			if ($metaCount == 0) {
				$sql = "INSERT INTO cardmetastats (cardID, week) VALUES (?, ?)";
				$stmt = mysqli_stmt_init($conn);
				if (mysqli_stmt_prepare($stmt, $sql)) {
					mysqli_stmt_bind_param($stmt, "si", $card["cardId"], $week);
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
					WHERE cardID = ? AND week = ?";
			$stmt = mysqli_stmt_init($conn);
			if (mysqli_stmt_prepare($stmt, $sql)) {
				// 6 integer stats, then cardID (string), then week (int)
				mysqli_stmt_bind_param($stmt, "iiiiiisi", $timesIncluded, $timesIncludedInWins, $timesPlayed, $timesPlayedInWins, $timesResourced, $timesResourcedInWins, $card["cardId"], $week);
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
	if ($won) {
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
	$sql .= " WHERE deckID = ? AND source = ?";
	$stmt = mysqli_stmt_init($conn);
	if (mysqli_stmt_prepare($stmt, $sql)) {
		if ($won) {
			mysqli_stmt_bind_param($stmt, "iiiiiii", $numRounds, $cardsResourced, $numRounds, $cardsResourced, $winnerHealth, $deckID, $source);
		} else {
			mysqli_stmt_bind_param($stmt, "iiii", $numRounds, $cardsResourced, $deckID, $source);
		}
		mysqli_stmt_execute($stmt);
		mysqli_stmt_close($stmt);
	}

	if(!$disableMetaStats) {

	   $sql = "UPDATE deckmetastats SET 
		   numPlays = numPlays + 1, 
		   totalTurns = totalTurns + ?, 
		   totalCardsResourced = totalCardsResourced + ?";
	   if ($won) {
		   $sql .= ", numWins = numWins + 1, 
			   turnsInWins = turnsInWins + ?, 
			   cardsResourcedInWins = cardsResourcedInWins + ?, 
			   remainingHealthInWins = remainingHealthInWins + ?";
	   }
	   $sql .= " WHERE leaderID = ? AND baseID = ? AND week = ?";
	   $stmt = mysqli_stmt_init($conn);
	   if (mysqli_stmt_prepare($stmt, $sql)) {
		   if ($won) {
		   mysqli_stmt_bind_param($stmt, "iiiiissi", 
			   $numRounds, 
			   $cardsResourced, 
			   $numRounds, 
			   $cardsResourced, 
			   $winnerHealth, 
			   $leaderID, 
			   $baseID, 
			   $week
		   );
		   } else {
		   mysqli_stmt_bind_param($stmt, "iissi", 
			   $numRounds, 
			   $cardsResourced, 
			   $leaderID, 
			   $baseID, 
			   $week
		   );
		   }
		   mysqli_stmt_execute($stmt);
		   mysqli_stmt_close($stmt);
	   }

	   // deckmetamatchupstats update (moved here)
	   $opponentLeaderID = isset($playerJSON["opposingHero"]) ? $playerJSON["opposingHero"] : "";
	   $opponentBaseID = isset($playerJSON["opposingBaseColor"]) ? $playerJSON["opposingBaseColor"] : "";
	   if ($opponentLeaderID !== "" && $opponentBaseID !== "") {
		   $sql = "SELECT COUNT(*) FROM deckmetamatchupstats WHERE leaderID = ? AND baseID = ? AND opponentLeaderID = ? AND opponentBaseID = ? AND week = ?";
		   $stmt = mysqli_stmt_init($conn);
		   if (mysqli_stmt_prepare($stmt, $sql)) {
			   mysqli_stmt_bind_param($stmt, "ssssi", $leaderID, $baseID, $opponentLeaderID, $opponentBaseID, $week);
			   mysqli_stmt_execute($stmt);
			   mysqli_stmt_bind_result($stmt, $matchupCount);
			   mysqli_stmt_fetch($stmt);
			   mysqli_stmt_close($stmt);
		   }
		   if ($matchupCount == 0) {
			   $sql = "INSERT INTO deckmetamatchupstats (leaderID, baseID, opponentLeaderID, opponentBaseID, week) VALUES (?, ?, ?, ?, ?)";
			   $stmt = mysqli_stmt_init($conn);
			   if (mysqli_stmt_prepare($stmt, $sql)) {
				   mysqli_stmt_bind_param($stmt, "ssssi", $leaderID, $baseID, $opponentLeaderID, $opponentBaseID, $week);
				   mysqli_stmt_execute($stmt);
				   mysqli_stmt_close($stmt);
			   }
		   }
		   $updateSql = "UPDATE deckmetamatchupstats SET 
			   numPlays = numPlays + 1, 
			   totalTurns = totalTurns + ?, 
			   totalCardsResourced = totalCardsResourced + ?";
		   if ($won) {
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
		   $updateSql .= " WHERE leaderID = ? AND baseID = ? AND opponentLeaderID = ? AND opponentBaseID = ? AND week = ?";
		   $stmt = mysqli_stmt_init($conn);
		   if (mysqli_stmt_prepare($stmt, $updateSql)) {
			   if ($won) {
				   // If won, 5 stats + 5 keys = 10 args
				   mysqli_stmt_bind_param($stmt, "iiiiissssi", 
					   $numRounds, 
					   $cardsResourced, 
					   $numRounds, 
					   $cardsResourced, 
					   $winnerHealth, 
					   $leaderID, 
					   $baseID, 
					   $opponentLeaderID, 
					   $opponentBaseID, 
					   $week
				   );
			   } else {
				   // If not won, 2 stats + 5 keys = 7 args
				   mysqli_stmt_bind_param($stmt, "iissssi", 
					   $numRounds, 
					   $cardsResourced, 
					   $leaderID, 
					   $baseID, 
					   $opponentLeaderID, 
					   $opponentBaseID, 
					   $week
				   );
			   }
			   mysqli_stmt_execute($stmt);
			   mysqli_stmt_close($stmt);
		   }
	   }
	}

	$leaderID = $playerJSON["opposingHero"];
	$opponentColor = $playerJSON["opposingBaseColor"];
	if($opponentColor != "") {
		$winColumn = "winsVs" . ucfirst($opponentColor);
		$totalColumn = "totalVs" . ucfirst($opponentColor);
	
	$sql = "SELECT COUNT(*) FROM opponentdeckstats WHERE deckID = ? AND leaderID = ? AND source = ?";
		$stmt = mysqli_stmt_init($conn);
		if (mysqli_stmt_prepare($stmt, $sql)) {
			mysqli_stmt_bind_param($stmt, "isi", $deckID, $leaderID, $source);
			mysqli_stmt_execute($stmt);
			mysqli_stmt_bind_result($stmt, $count);
			mysqli_stmt_fetch($stmt);
			mysqli_stmt_close($stmt);
		}
	
		if ($count == 0) {
			$sql = "INSERT INTO opponentdeckstats (deckID, leaderID, source) VALUES (?, ?, ?)";
			$stmt = mysqli_stmt_init($conn);
			if (mysqli_stmt_prepare($stmt, $sql)) {
				mysqli_stmt_bind_param($stmt, "isi", $deckID, $leaderID, $source);
				mysqli_stmt_execute($stmt);
				mysqli_stmt_close($stmt);
			}
		}
	
		$sql = "UPDATE opponentdeckstats SET $winColumn = $winColumn + ?, $totalColumn = $totalColumn + ? WHERE deckID = ? AND leaderID = ? AND source = ?";
		$stmt = mysqli_stmt_init($conn);
		if (mysqli_stmt_prepare($stmt, $sql)) {
			$wins = $won ? 1 : 0;
			$total = 1;
			mysqli_stmt_bind_param($stmt, "iiisi", $wins, $total, $deckID, $leaderID, $source);
			mysqli_stmt_execute($stmt);
			mysqli_stmt_close($stmt);
		}
	}

	mysqli_close($conn);
}

/**
 * Return the number of full weeks elapsed since the given reference date.
 *
 * - The reference date string should be in a format parseable by DateTime.
 * - Returns 0 on error or if $now is before the reference date.
 * - Example: reference = '2025-09-20'. If today is 2025-09-27, returns 1.
 *
 * @param string $refDateString
 * @param DateTime|null $now
 * @return int
 */
function GetWeekSinceRef($refDateString = '2025-09-20', DateTime $now = null) {
	try {
		$refDate = new DateTime($refDateString);
		$now = $now ?: new DateTime();
		if ($now < $refDate) {
			return 0;
		}
		$days = (int)$refDate->diff($now)->format('%a');
		$week = (int) floor($days / 7);
		return max(0, $week);
	} catch (Exception $e) {
		return 0;
	}
}

?>

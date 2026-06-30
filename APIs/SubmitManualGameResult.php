<?php

  require_once "../Core/HTTPLibraries.php";
  require_once "../Database/ConnectionManager.php";
  require_once "../Core/StatsBaseRegistry.php";

  $input = file_get_contents('php://input');
  $data = json_decode($input, true);
 
  $won = $data["won"];
  $wasFirstPlayer = $data["firstPlayer"];
  $numRounds = $data["rounds"];
  $winnerHealth = $data["winnerHealth"];
  $gameName = 1;

  $deckID = $data["deckID"];//gameName
  
  SaveDeckStats($deckID, $data["player"], $won, $wasFirstPlayer, $numRounds, $winnerHealth, $gameName);
  
  //Parameters:
  // won: true if this player won the game, false if they lost
  // wasFirstPlayer: true if this player was the first player in the game, false if they were the second player
function SaveDeckStats($deckID, $playerData, $won, $wasFirstPlayer, $numRounds, $winnerHealth, $gameName) {
	$playerJSON = json_decode($playerData, true);

	$conn = GetLocalMySQLConnection();

	$sql = "SELECT COUNT(*) FROM deckstats WHERE deckID = ?";
	$stmt = mysqli_stmt_init($conn);
	if (mysqli_stmt_prepare($stmt, $sql)) {
			mysqli_stmt_bind_param($stmt, "i", $deckID);
			mysqli_stmt_execute($stmt);
			mysqli_stmt_bind_result($stmt, $count);
			mysqli_stmt_fetch($stmt);
			mysqli_stmt_close($stmt);
	}
	if ($count == 0) {
		$sql = "INSERT INTO deckstats (deckID) VALUES (?)";
		$stmt = mysqli_stmt_init($conn);
		if (mysqli_stmt_prepare($stmt, $sql)) {
				mysqli_stmt_bind_param($stmt, "i", $deckID);
				mysqli_stmt_execute($stmt);
				mysqli_stmt_close($stmt);
		}
	}

	$cardsResourced = 0;
	//cardResults
	$cardResults = $playerJSON["cardResults"];
	for($i = 0; $i < count($cardResults); $i++) {
		$card = $cardResults[$i];
		$sql = "SELECT COUNT(*) FROM carddeckstats WHERE cardID = ? AND deckID = ?";
		$stmt = mysqli_stmt_init($conn);
		if (mysqli_stmt_prepare($stmt, $sql)) {
			mysqli_stmt_bind_param($stmt, "si", $card["cardID"], $deckID);
			mysqli_stmt_execute($stmt);
			mysqli_stmt_bind_result($stmt, $cardCount);
			mysqli_stmt_fetch($stmt);
			mysqli_stmt_close($stmt);
		}

		if ($cardCount == 0) {
			$sql = "INSERT INTO carddeckstats (cardID, deckID) VALUES (?, ?)";
			$stmt = mysqli_stmt_init($conn);
			if (mysqli_stmt_prepare($stmt, $sql)) {
				mysqli_stmt_bind_param($stmt, "si", $card["cardID"], $deckID);
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
		$sql = "UPDATE carddeckstats SET 
				timesIncluded = timesIncluded + ?, 
				timesIncludedInWins = timesIncludedInWins + ?, 
				timesPlayed = timesPlayed + ?, 
				timesPlayedInWins = timesPlayedInWins + ?, 
				timesResourced = timesResourced + ?, 
				timesResourcedInWins = timesResourcedInWins + ? 
				WHERE cardID = ? AND deckID = ?";
		$stmt = mysqli_stmt_init($conn);
		if (mysqli_stmt_prepare($stmt, $sql)) {
			mysqli_stmt_bind_param($stmt, "iiiiiiii", $timesIncluded, $timesIncludedInWins, $timesPlayed, $timesPlayedInWins, $timesResourced, $timesResourcedInWins, $card["cardID"], $deckID);
			mysqli_stmt_execute($stmt);
			mysqli_stmt_close($stmt);
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
	$sql .= " WHERE deckID = ?";
	$stmt = mysqli_stmt_init($conn);
	if (mysqli_stmt_prepare($stmt, $sql)) {
		if ($won) {
			mysqli_stmt_bind_param($stmt, "iiiiii", $numRounds, $cardsResourced, $numRounds, $cardsResourced, $winnerHealth, $deckID);
		} else {
			mysqli_stmt_bind_param($stmt, "iii", $numRounds, $cardsResourced, $deckID);
		}
		mysqli_stmt_execute($stmt);
		mysqli_stmt_close($stmt);
	}
	$leaderID = $playerJSON["opposingHero"];

	$resolved = isset($playerJSON["opposingBase"]) ? ResolveOpponentBase($playerJSON["opposingBase"]) : null;
	$wins = $won ? 1 : 0;
	$total = 1;

	if ($resolved && $resolved["kind"] === "named") {
		// Rare/Special base — tracked individually by base identity.
		$namedBaseID = $resolved["baseID"];
		$sql = "INSERT INTO opponentnamedbasestats (deckID, leaderID, baseID, wins, total) VALUES (?, ?, ?, ?, ?)
		        ON DUPLICATE KEY UPDATE wins = wins + VALUES(wins), total = total + VALUES(total)";
		$stmt = mysqli_stmt_init($conn);
		if (mysqli_stmt_prepare($stmt, $sql)) {
			mysqli_stmt_bind_param($stmt, "issii", $deckID, $leaderID, $namedBaseID, $wins, $total);
			mysqli_stmt_execute($stmt);
			mysqli_stmt_close($stmt);
		}
	} else {
		if ($resolved) {
			$color = $resolved["color"];
			$typeSuffix = StatsTypeColumnSuffix($resolved["type"]);
		} else {
			// Common base reported by color + group (Standard/Force/Splash). Absent an
			// explicit group, fall back to the Legacy color-only bucket (suffix '').
			$color = isset($playerJSON["opposingBaseColor"]) ? $playerJSON["opposingBaseColor"] : "";
			$group = isset($playerJSON["opposingBaseGroup"]) ? $playerJSON["opposingBaseGroup"] : "Legacy";
			$typeSuffix = StatsTypeColumnSuffix($group);
		}

		if ($color != "") {
			$winColumn   = "winsVs"  . ucfirst($color) . $typeSuffix;
			$totalColumn = "totalVs" . ucfirst($color) . $typeSuffix;

			$sql = "SELECT COUNT(*) FROM opponentdeckstats WHERE deckID = ? AND leaderID = ?";
			$stmt = mysqli_stmt_init($conn);
			if (mysqli_stmt_prepare($stmt, $sql)) {
				mysqli_stmt_bind_param($stmt, "is", $deckID, $leaderID);
				mysqli_stmt_execute($stmt);
				mysqli_stmt_bind_result($stmt, $count);
				mysqli_stmt_fetch($stmt);
				mysqli_stmt_close($stmt);
			}

			if ($count == 0) {
				$sql = "INSERT INTO opponentdeckstats (deckID, leaderID) VALUES (?, ?)";
				$stmt = mysqli_stmt_init($conn);
				if (mysqli_stmt_prepare($stmt, $sql)) {
					mysqli_stmt_bind_param($stmt, "is", $deckID, $leaderID);
					mysqli_stmt_execute($stmt);
					mysqli_stmt_close($stmt);
				}
			}

			$sql = "UPDATE opponentdeckstats SET $winColumn = $winColumn + ?, $totalColumn = $totalColumn + ? WHERE deckID = ? AND leaderID = ?";
			$stmt = mysqli_stmt_init($conn);
			if (mysqli_stmt_prepare($stmt, $sql)) {
				mysqli_stmt_bind_param($stmt, "iiis", $wins, $total, $deckID, $leaderID);
				mysqli_stmt_execute($stmt);
				mysqli_stmt_close($stmt);
			}
		}
	}
	mysqli_close($conn);
}

?>

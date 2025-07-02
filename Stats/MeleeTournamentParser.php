<?php

// Include the card identifier helper functions
include_once '../SWUDeck/Custom/CardIdentifiers.php';

set_time_limit(1800); // Set the maximum execution time to 30 minutes (1800 seconds)

include_once '../Core/HTTPLibraries.php';
include_once "../Core/UILibraries.php";
include_once '../Database/ConnectionManager.php';

// Initialize database connection
$conn = GetLocalMySQLConnection();
if ($conn === false) {
  die('Error connecting to the database.');
} 

// Get the roundId from GET parameter or use default
$roundId = isset($_GET['roundId']) ? (int)$_GET['roundId'] : 977630;

function parseMeleeTournament($roundId, $conn, $progressCallback = null) {
    // Check if this tournament round already exists in the database
    $checkQuery = "SELECT mt.tournamentId, mt.tournamentName FROM meleetournament mt WHERE mt.roundId = ?";
    $checkStmt = $conn->prepare($checkQuery);
    $checkStmt->bind_param("i", $roundId);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();
    if ($checkResult->num_rows > 0) {
        $tournamentData = $checkResult->fetch_assoc();
        if ($progressCallback) $progressCallback(['message' => 'Tournament already loaded', 'tournament_id' => $tournamentData['tournamentId']]);
        $checkStmt->close();
        return $tournamentData['tournamentId'];
    }
    $checkStmt->close();

    $targetLength = 1000;
    $incrementLength = 500;
    $batch = 0;
    $fetchedCount = 0;
    $noMoreData = false;
    $deckArray = [];
    $url = 'https://melee.gg/Standing/GetRoundStandings';
    while (!$noMoreData) {
        $data = [
            'draw' => 2,
            'columns' => [
                ['data' => 'Rank', 'name' => 'Rank', 'searchable' => true, 'orderable' => true, 'search' => ['value' => '', 'regex' => false]],
                ['data' => 'Player', 'name' => 'Player', 'searchable' => false, 'orderable' => false, 'search' => ['value' => '', 'regex' => false]],
                ['data' => 'Decklists', 'name' => 'Decklists', 'searchable' => false, 'orderable' => false, 'search' => ['value' => '', 'regex' => false]],
                ['data' => 'MatchRecord', 'name' => 'MatchRecord', 'searchable' => false, 'orderable' => false, 'search' => ['value' => '', 'regex' => false]],
                ['data' => 'GameRecord', 'name' => 'GameRecord', 'searchable' => false, 'orderable' => false, 'search' => ['value' => '', 'regex' => false]],
                ['data' => 'Points', 'name' => 'Points', 'searchable' => true, 'orderable' => true, 'search' => ['value' => '', 'regex' => false]],
                ['data' => 'OpponentMatchWinPercentage', 'name' => 'OpponentMatchWinPercentage', 'searchable' => false, 'orderable' => true, 'search' => ['value' => '', 'regex' => false]],
                ['data' => 'TeamGameWinPercentage', 'name' => 'TeamGameWinPercentage', 'searchable' => false, 'orderable' => true, 'search' => ['value' => '', 'regex' => false]],
                ['data' => 'OpponentGameWinPercentage', 'name' => 'OpponentGameWinPercentage', 'searchable' => false, 'orderable' => true, 'search' => ['value' => '', 'regex' => false]],
                ['data' => 'FinalTiebreaker', 'name' => 'FinalTiebreaker', 'searchable' => false, 'orderable' => true, 'search' => ['value' => '', 'regex' => false]],
                ['data' => 'OpponentCount', 'name' => 'OpponentCount', 'searchable' => true, 'orderable' => true, 'search' => ['value' => '', 'regex' => false]],
            ],
            'order' => [['column' => 0, 'dir' => 'asc']],
            'start' => $batch * $incrementLength,
            'length' => $incrementLength,
            'search' => ['value' => '', 'regex' => false],
            'roundId' => $roundId,
        ];
        $options = [
            'http' => [
                'header'  => "Content-Type: application/x-www-form-urlencoded\r\n" .
                             "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36\r\n",
                'method'  => 'POST',
                'content' => http_build_query($data),
            ],
        ];
        $context  = stream_context_create($options);
        $response = file_get_contents($url, false, $context);
        if ($response === FALSE) {
            if ($progressCallback) $progressCallback(['error' => 'Error occurred while making the request.']);
            return false;
        }
        $jsonResponse = json_decode($response, true);
        if (isset($jsonResponse['data']) && is_array($jsonResponse['data'])) {
            $fetchedCount += count($jsonResponse['data']);
            foreach ($jsonResponse['data'] as $record) {
                $deckArray[] = $record;
            }
            if ($fetchedCount >= $targetLength) {
                $noMoreData = true;
            } else if (count($jsonResponse['data']) < $incrementLength) {
                $noMoreData = true;
            } else {
                $batch++;
            }
        } else {
            if ($progressCallback) $progressCallback(['error' => 'No data found in the response.']);
            break;
        }
    }

    $tournamentRecordExists = false;

    $playerDeckMap = [];
    $deckListIdArray = [];

    if (count($deckArray) > 0) {
      $tournamentLink = -1;
      foreach ($deckArray as $record) {
        $points = $record['Points'] ?? 0;
        $OMWP = $record['OpponentMatchWinPercentage'] ?? 0;
        $TGWP = $record['TeamGameWinPercentage'] ?? 0;
        $OGWP = $record['OpponentGameWinPercentage'] ?? 0;
        // Exclude rows with 0 points and all tiebreakers exactly 33.33
        if ((floatval($points) === 0.0) && (floatval($OMWP) === 33.33) && (floatval($TGWP) === 33.33) && (floatval($OGWP) === 33.33)) {
          continue;
        }
        $playerName = $record['Team']['Players'][0]['Username'] ?? 'Unknown Player';
        $deckName = $record['Decklists'][0]['DecklistName'] ?? 'Unknown Deck';
        $decklistId = $record['Decklists'][0]['DecklistId'] ?? null;
        // Ensure we keep the decklist ID as a string, not an integer
        $deckListIdArray[] = $decklistId;
        $matchRecord = $record['MatchRecord'] ?? 'Unknown Record';
        
        // Extract leader and base from DecklistName which follows the format "Leader Name, Title - Base Name"
        $leader = null;
        $base = null;
        $leaderName = null;
        $baseName = null;
        if (!empty($deckName) && $deckName != 'Unknown Deck') {
          $parts = explode(' - ', $deckName);
          if (count($parts) === 2) {
            $leaderName = trim($parts[0]); // Leader Name, Title
            $baseName = trim($parts[1]);   // Base Name
            
            // Convert the names to UUIDs using the helper functions
            $leader = GetLeaderUUID($leaderName);
            $base = GetBaseUUID($baseName);
            
            // Debug output to identify problematic leader names
            if ($progressCallback) {
                $progressCallback([
                    'type' => 'leader_debug',
                    'player' => $playerName,
                    'leaderName' => $leaderName,
                    'leaderUUID' => $leader,
                    'baseName' => $baseName,
                    'baseUUID' => $base
                ]);
            }
            // If leader UUID not found, try alternative approaches
            if (!$leader) {
                // Try with variations of the leader name
                if (strpos($leaderName, ',') !== false) {
                    $baseCharacterName = trim(explode(',', $leaderName)[0]);
                    if ($progressCallback) {
                        $progressCallback([
                            'type' => 'leader_attempt',
                            'player' => $playerName,
                            'attempt' => 'base_character',
                            'baseCharacterName' => $baseCharacterName
                        ]);
                    }
                    $leader = GetLeaderUUID($baseCharacterName);
                    if ($leader && $progressCallback) {
                        $progressCallback([
                            'type' => 'leader_found',
                            'player' => $playerName,
                            'method' => 'base_character',
                            'leaderUUID' => $leader
                        ]);
                    }
                }
                // Try pipe format (Character | Subtitle) that melee.gg sometimes uses
                $pipeFormat = str_replace(',', ' | ', $leaderName);
                if ($progressCallback) {
                    $progressCallback([
                        'type' => 'leader_attempt',
                        'player' => $playerName,
                        'attempt' => 'pipe_format',
                        'pipeFormat' => $pipeFormat
                    ]);
                }
                $leader = GetLeaderUUID($pipeFormat);
                if ($leader && $progressCallback) {
                    $progressCallback([
                        'type' => 'leader_found',
                        'player' => $playerName,
                        'method' => 'pipe_format',
                        'leaderUUID' => $leader
                    ]);
                }
            }
          }
        }

        $tournamentLink = $record['TournamentId'] ?? 0;
        if (!$tournamentRecordExists) {
          // only get the tournament name and date if tournamentLink doesn't exist in the database
          $query = "SELECT TournamentId FROM meleetournament WHERE TournamentLink = ?";
          $stmt = $conn->prepare($query);
          $stmt->bind_param("i", $tournamentLink);
          $stmt->execute();
          $result = $stmt->get_result();
          if ($row = $result->fetch_assoc()) {
            $tournamentId = $row['TournamentId'];
            $tournamentRecordExists = true;
          } else {
            $tournamentRecordExists = false;
          }

          if (!$tournamentRecordExists) {
            // Here we will create a new tournament record in table meleetournament
            $tournamentName = $record['FormatName'] . " " . $record['PhaseName'] ?? 'Unknown Tournament';
            $tournamentDate = $record['DateCreated'] ?? '1900-01-01 00:00:00';
            $tournamentDate = date('Y-m-d', strtotime($tournamentDate)); // Format the date
            $insertQuery = "INSERT INTO meleetournament (TournamentLink, TournamentName, TournamentDate, roundId) VALUES (?, ?, ?, ?)";
            $insertStmt = $conn->prepare($insertQuery);
            $insertStmt->bind_param("issi", $tournamentLink, $tournamentName, $tournamentDate, $roundId);
            $insertStmt->execute();
            $tournamentId = $insertStmt->insert_id; // Get the last inserted ID
            $insertStmt->close();
            $tournamentRecordExists = true;
          }
          $stmt->close();
           
        }
        
        $rank = $record['Rank'] ?? 0;
        $matchWins = $record['MatchWins'] ?? 0;
        $matchLosses = $record['MatchLosses'] ?? 0;
        $matchDraws = $record['MatchDraws'] ?? 0;
        $gameWins = $record['GameWins'] ?? 0;
        $gameLosses = $record['GameLosses'] ?? 0;
        $gameDraws = $record['GameDraws'] ?? 0;
        $points = $record['Points'] ?? 0;
        $OMWP = $record['OpponentMatchWinPercentage'] ?? 0;
        $TGWP = $record['TeamGameWinPercentage'] ?? 0;
        $OGWP = $record['OpponentGameWinPercentage'] ?? 0;

        // I think we have enough data to add a record to table meleetournamentdeck
        $insertDeckQuery = "INSERT INTO meleetournamentdeck (tournamentId, rank, player, leader, base, matchWins, matchLosses, matchDraws, gameWins, gameLosses, gameDraws, points, OMWP, TGWP, OGWP, sourceID) 
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $insertDeckStmt = $conn->prepare($insertDeckQuery);
        // Don't overwrite our extracted leader and base values - we've already set them above from DecklistName
        // Only try to get them from Decklists if they're not already set
        if ($leader === null) {
          $leader = $record['Decklists'][0]['Leader'] ?? null;
        }
        if ($base === null) {
          $base = $record['Decklists'][0]['Base'] ?? null;
        }
        $insertDeckStmt->bind_param("iisssiiiiiidddds",
          $tournamentId,$rank,$playerName,$leader,$base,$matchWins,$matchLosses,$matchDraws,$gameWins,$gameLosses,$gameDraws,$points,$OMWP,$TGWP,$OGWP, $decklistId);
        $insertDeckStmt->execute();
        $deckId = $insertDeckStmt->insert_id; // Get the last inserted ID
        $playerDeckMap[$playerName] = $deckId; // Store the mapping of playerName to decklistId
        $insertDeckStmt->close();
      }
      if ($tournamentLink!=-1) {
        $tournamentViewUrl = "https://melee.gg/Tournament/View/".$tournamentLink;
        $tournamentOptions = [
            'http' => [
                'header' => "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36\r\n",
                'method' => 'GET',
            ],
        ];
        $tournamentContext = stream_context_create($tournamentOptions);
        $tournamentPageContent = file_get_contents($tournamentViewUrl, false, $tournamentContext);
        
        if ($tournamentPageContent !== FALSE) {
          preg_match('/<title>(.*?)<\/title>/', $tournamentPageContent, $matches);
          if (!empty($matches[1])) {
            $tournamentNameFromTitle = $matches[1];
            // Now we need to update the tournament link in table meleetournamentdeck
            $updateDeckQuery = "UPDATE meleetournament SET TournamentName = ? WHERE TournamentLink = ?";
            $updateDeckStmt = $conn->prepare($updateDeckQuery);
            $updateDeckStmt->bind_param("si", $tournamentNameFromTitle, $tournamentLink);
            $updateDeckStmt->execute();
            $updateDeckStmt->close();
          } else {
            echo "Could not extract the tournament name from the title tag.<br>";
          }
        } else {
          echo "Error occurred while fetching the tournament page.<br>";
        }
        
      }
    }

    foreach ($deckListIdArray as $decklistId) {
      // Perform any additional operations on each decklist ID
      //$decksCount++;
      //if($decksCount > 10) continue;
      $decklistUrl = "https://melee.gg/Decklist/GetTournamentViewData/".$decklistId;

      $decklistOptions = [
          'http' => [
              'header' => "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36\r\n",
              'method' => 'GET',
          ],
      ];
      $decklistContext = stream_context_create($decklistOptions);
      $decklistResponse = file_get_contents($decklistUrl, false, $decklistContext);
      if ($decklistResponse === FALSE) {
        echo "Error occurred while fetching the decklist.<br>";
        echo "Decklist URL: <a href=\"$decklistUrl\" target=\"_blank\">$decklistUrl</a><br>";
      } else {
        $decklistjsonResponse = json_decode($decklistResponse, true);
        $decklistjsonResponse = json_decode($decklistjsonResponse["Json"], true);
        $playerName = $decklistjsonResponse["Team"]["Username"] ?? 'Unknown Player';
        
        // Extract leader and base information from the decklist
        $leader = null;
        $base = null;
        
        // Check multiple possible locations for leader and base in the decklist data
        // Location 1: Decklist > MetaData
        if (isset($decklistjsonResponse["Decklist"]) && isset($decklistjsonResponse["Decklist"]["MetaData"])) {
          $metaData = $decklistjsonResponse["Decklist"]["MetaData"];
          $leader = $metaData["Leader"] ?? null;
          $base = $metaData["Base"] ?? null;
        }
        
        // Location 2: Decklist > DecklistProperties
        if ((!$leader || !$base) && isset($decklistjsonResponse["Decklist"]) && isset($decklistjsonResponse["Decklist"]["DecklistProperties"])) {
          $properties = $decklistjsonResponse["Decklist"]["DecklistProperties"];
          $leader = $leader ?? $properties["Leader"] ?? null; 
          $base = $base ?? $properties["Base"] ?? null;
        }
        
        // Location 3: Decklist > LeaderCardId or LeaderName
        if (!$leader && isset($decklistjsonResponse["Decklist"])) {
          $decklist = $decklistjsonResponse["Decklist"];
          $leader = $leader ?? $decklist["LeaderCardId"] ?? $decklist["LeaderName"] ?? null;
        }
        
        // Location 4: Check for CardClass which might contain base information
        if (!$base && isset($decklistjsonResponse["Decklist"]) && isset($decklistjsonResponse["Decklist"]["CardClass"])) {
          $base = $decklistjsonResponse["Decklist"]["CardClass"];
        }
        
        // Location 5: Direct properties at the root level
        $leader = $leader ?? $decklistjsonResponse["Leader"] ?? null;
        $base = $base ?? $decklistjsonResponse["Base"] ?? null;
        
        // Debug output
        if ($progressCallback) {
          $progressCallback([
            'type' => 'deck_leader_base',
            'player' => $playerName,
            'leader' => $leader,
            'base' => $base
          ]);
        }
        
        // If leader and base are found, update the record in meleetournamentdeck
        if ($leader !== null || $base !== null) {
          $deckId = $playerDeckMap[$playerName] ?? null;
          if ($deckId) {
            $updateDeckQuery = "UPDATE meleetournamentdeck SET leader = ?, base = ? WHERE deckID = ?";
            $updateDeckStmt = $conn->prepare($updateDeckQuery);
            $updateDeckStmt->bind_param("ssi", $leader, $base, $deckId);
            $updateDeckStmt->execute();
            $updateDeckStmt->close();
            if ($progressCallback) {
              $progressCallback([
                'type' => 'deck_update',
                'player' => $playerName,
                'leader' => $leader,
                'base' => $base
              ]);
            }
          }
        }
        
        //Get ["Matches"] from $decklistjsonResponse
        $matches = $decklistjsonResponse["Matches"] ?? null;
        if ($matches === null) {
          if ($progressCallback) {
            $progressCallback([
                'type' => 'no_matches',
                'player' => $playerName,
                'decklistId' => $decklistId
            ]);
          }
          continue;
        }
        // Loop through each match and extract the relevant data
        foreach ($matches as $match) {
          $opponentName = $match['OpponentUsername'] ?? 'Unknown Opponent';
          $opponentLeader = null;
          
          // Try to extract opponent's leader from their decklist name if available
          if (isset($match['OpponentDecklistName']) && !empty($match['OpponentDecklistName'])) {
            $opponentDeckName = $match['OpponentDecklistName'];
            $parts = explode(' - ', $opponentDeckName);
            if (count($parts) === 2) {
                $opponentLeaderName = trim($parts[0]); // Leader Name, Title
                // More robust logging for opponent leader resolution
                if ($progressCallback) {
                    $progressCallback([
                        'type' => 'opponent_leader_debug',
                        'opponent' => $opponentName,
                        'opponentDeckName' => $opponentDeckName,
                        'extractedLeaderName' => $opponentLeaderName
                    ]);
                }
                // Convert the opponent leader name to UUID using our improved function
                $opponentLeaderUUID = GetLeaderUUID($opponentLeaderName);
                if ($progressCallback) {
                    $progressCallback([
                        'type' => 'opponent_leader_uuid',
                        'opponent' => $opponentName,
                        'leaderUUID' => $opponentLeaderUUID
                    ]);
                }
                // Update the opponent's record in the database with their leader UUID
                if ($opponentLeaderUUID && isset($playerDeckMap[$opponentName])) {
                    $updateOpponentQuery = "UPDATE meleetournamentdeck SET leader = ? WHERE deckID = ?";
                    $updateOpponentStmt = $conn->prepare($updateOpponentQuery);
                    $updateOpponentStmt->bind_param("si", $opponentLeaderUUID, $playerDeckMap[$opponentName]);
                    $updateOpponentStmt->execute();
                    $updateOpponentStmt->close();
                    if ($progressCallback) {
                        $progressCallback([
                            'type' => 'opponent_leader_update',
                            'opponent' => $opponentName,
                            'leaderUUID' => $opponentLeaderUUID
                        ]);
                    }
                }
            }
          }
          
          $winningPlayerName = "";
          $matchResult = $match['Result'];
          if ($progressCallback) {
              $progressCallback([
                  'type' => 'match_result',
                  'player' => $playerName,
                  'opponent' => $opponentName,
                  'result' => $matchResult,
                  'matchWins' => $matchWins ?? null,
                  'matchLosses' => $matchLosses ?? null,
                  'matchDraws' => $matchDraws ?? null
              ]);
          }
          //$matchResult is a space delimited string, 
          // there are 4 format: 
          //    "Avestator won 2-0-0", 
          //    "0-0-3 Draw", 
          //    "fziki was assigned a bye",
          //    "Fireshow forfeited the match, Servetz forfeited the match",
          //    "Not reported"
          // for the first and third format, I need to get the last element of the string
          // for the second format, I need to get the first element of the string
          // for the last format, set matchWins, matchLosses, matchDraws to 0
          if (strpos($matchResult, "forfeited") !== false || strpos($matchResult, "Not reported") !== false) {
            list($matchWins, $matchLosses, $matchDraws) = [0, 0, 0];
          } else {
            $matchResult = explode(' ', $matchResult);
            if (end($matchResult) == "Draw") {
              // $matchResult should be the first element of the array         
              $matchResult = $matchResult[0];
            } else {
              //get the last element of the array
              $winningPlayerName = $matchResult[0];
              $matchResult = end($matchResult);
            }

            if ($matchResult == "bye") {
              list($matchWins, $matchLosses, $matchDraws) = [2, 0, 0];
            } else {
              if ($winningPlayerName == $playerName) {
                list($matchWins, $matchLosses, $matchDraws) = explode('-', $matchResult);
              } else {
                list($matchLosses, $matchWins, $matchDraws) = explode('-', $matchResult);
              }
            }
          }
          // Insert a record to table meleetournamentmatchup with decklistid, opponentDeckId, matchWins, matchLosses, matchDraws
          $insertMatchupQuery = "INSERT INTO meleetournamentmatchup (player, opponent, wins, losses, draws) 
                     VALUES (?, ?, ?, ?, ?)";
          $insertMatchupStmt = $conn->prepare($insertMatchupQuery);
          if ($progressCallback) {
              $progressCallback([
                  'type' => 'matchup_insert',
                  'player' => $playerName,
                  'playerDeckId' => $playerDeckMap[$playerName] ?? null,
                  'opponent' => $opponentName,
                  'opponentDeckId' => $playerDeckMap[$opponentName] ?? null,
                  'matchWins' => $matchWins ?? null,
                  'matchLosses' => $matchLosses ?? null,
                  'matchDraws' => $matchDraws ?? null
              ]);
          }
          $insertMatchupStmt->bind_param("iiiii", $playerDeckMap[$playerName], $playerDeckMap[$opponentName], $matchWins, $matchLosses, $matchDraws);
          $insertMatchupStmt->execute();
          $insertMatchupStmt->close();
        }
      }
    }

    return true;
}

$conn->close(); // Close the database connection

// Only output HTML if run directly, not when called as a function
if (php_sapi_name() === 'cli' || (isset($_SERVER['SCRIPT_FILENAME']) && basename($_SERVER['SCRIPT_FILENAME']) === basename(__FILE__))) {
    echo "Done.<br>";
    include_once '../SharedUI/Disclaimer.php';
}

?>
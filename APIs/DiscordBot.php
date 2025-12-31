<?php
//Interactions Endpoint URL method

include_once "../APIKeys/APIKeys.php";

$publicKey = $discordPublicKey;

// Retrieve the signature and timestamp from the HTTP headers.
$signature = $_SERVER['HTTP_X_SIGNATURE_ED25519'] ?? '';
$timestamp = $_SERVER['HTTP_X_SIGNATURE_TIMESTAMP'] ?? '';

// Read the raw POST body.
$rawBody = file_get_contents('php://input');

// Verify that the request actually came from Discord.
// The signature is expected to be a hex-encoded string.
// The verification uses sodium_crypto_sign_verify_detached which expects binary data.
if (
    empty($signature) ||
    empty($timestamp) ||
    !sodium_crypto_sign_verify_detached(
        hex2bin($signature),
        $timestamp . $rawBody,
        hex2bin($publicKey)
    )
) {
    http_response_code(401);
    echo "invalid request signature";
    exit;
}
// Decode the incoming JSON payload.
$interaction = json_decode($rawBody, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(400);
    echo "Bad Request: Invalid JSON";
    exit;
}

// Handle the Discord PING request (Interaction Type 1).
if (isset($interaction['type']) && $interaction['type'] === 1) {
    header('Content-Type: application/json');
    echo json_encode(['type' => 1]); // PONG response
    exit;
}

include_once "../SWUDeck/GeneratedCode/GeneratedCardDictionaries.php";
include_once "../Database/ConnectionManager.php";

// Handle an Application Command (Interaction Type 2).
if (isset($interaction['type']) && $interaction['type'] === 2) {
    // Get the command name
    $command = $interaction['data']['name'] ?? '';

    // Initialize response content
    $responseText = "Unknown command.";

    // Check which command was used
    if ($command === "ping") {
		$response = [
			'type' => 4, // Respond with a message
			'data' => [
				'content' => "Pong!"
			]
		];
    } else if($command === "rules") {
		$discordID = $interaction['member']['user']['id'] ?? ($interaction['user']['id'] ?? 'Unknown');
		$SWUStatsUsername = SWUStatsUserName($discordID);
		if($SWUStatsUsername == -1) {
			$responseText = "\nNo linked SWUStats user found. Link your discord account in your SWUStats profile.";
		} else if($SWUStatsUsername != "OotTheMonk" && $SWUStatsUsername != "macfergusson" && $SWUStatsUsername != "MrDragonfox" && $SWUStatsUsername != "Mobyus1") {
			$responseText = "\nYou are not a member of the SWUStats rules search beta.";
		} else {
			$query = "Unknown";
			$options = $interaction['data']['options'] ?? [];
			if (!empty($options)) {
				foreach ($options as $option) {
					if ($option['name'] === 'query') {
						$query = strtolower($option['value']);
						break;
					}
				}
			}
			$responseText = "\n" . $SWUStatsUsername . " searched the rules for: " . $query;
			// Initialize cURL session
			$ch = curl_init();

			// Set cURL options
			curl_setopt($ch, CURLOPT_URL, "http://142.11.210.6/es/swuRules_search.php");
			curl_setopt($ch, CURLOPT_POST, true);
			curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query(['searchInput' => $query]));
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

			// Execute the request and fetch the response
			$response = curl_exec($ch);

			// Check for cURL errors
			if (curl_errno($ch)) {
				$responseText = "\nAn error occurred while searching the rules.";
			} else {
				$responseArr = explode("</strong></h2><ul><li>", $response);
				$response = count($responseArr) > 1 ? $responseArr[1] : $responseArr[0];
				$response = explode("</li></ul>", $response)[0];
				$responseArr = explode("</li><li>", $response);
				$responseText .= "\nRules search result:\n";
				$responseLen = strlen($responseText);
				foreach ($responseArr as $rule) {
					$ruleLen = strlen($rule) + 8;
					if($responseLen + $ruleLen < 1990) {
						$responseLen += $ruleLen;
						$responseText .= "```" . $rule . "```\n";
					}
				}
			}

			// Close the cURL session
			curl_close($ch);
		}
		$response = [
			'type' => 4, // Respond with a message
			'data' => [
				'content' => $responseText,
			]
		];
	} else if($command === "me") {
		$userId = $interaction['member']['user']['id'] ?? ($interaction['user']['id'] ?? 'Unknown');
		//$username = $interaction['member']['user']['username'] ?? ($interaction['user']['username'] ?? 'Unknown');
		//$discriminator = $interaction['member']['user']['discriminator'] ?? ($interaction['user']['discriminator'] ?? 'Unknown');
		$conn = GetLocalMySQLConnection();
		$query = $conn->prepare("SELECT usersId, usersUid FROM users WHERE discordID = ?");
		$query->bind_param("s", $userId);
		$query->execute();
		$result = $query->get_result();
		if ($result && $result->num_rows > 0) {
			$userRecord = $result->fetch_assoc();
			$responseText = "\nYour SWUStats ID is: " . $userRecord['usersUid'];
		} else {
			$responseText = "\nNo linked SWUStats user found.";
		}
		$query->close();
		$conn->close();
		$response = [
			'type' => 4, // Respond with a message
			'data' => [
				'content' => $responseText,
				'flags' => 64 // Ephemeral message (only visible to the user)
			]
		];
	} else if($command === "deck") {
		$userId = $interaction['member']['user']['id'] ?? ($interaction['user']['id'] ?? 'Unknown');
		// Extract deck name from command options
		$options = $interaction['data']['options'] ?? [];
		$deckName = "Unknown";
		if (!empty($options)) {
			foreach ($options as $option) {
				if ($option['name'] === 'name') {
					$deckName = strtolower($option['value']);
					break;
				}
			}
		}
		$conn = GetLocalMySQLConnection();
		$query = $conn->prepare("SELECT usersId FROM users WHERE discordID = ?");
		$query->bind_param("s", $userId);
		$query->execute();
		$swuStatsId = null;
		$result = $query->get_result();
		if ($result && $result->num_rows > 0) {
			$userRecord = $result->fetch_assoc();
			$swuStatsId = $userRecord['usersId'];

			$queryDecks = $conn->prepare("SELECT assetIdentifier, assetName FROM ownership WHERE assetOwner = ? AND assetType = 1");
			$queryDecks->bind_param("i", $swuStatsId);
			$queryDecks->execute();
			$decksResult = $queryDecks->get_result();
			$deckFound = null;

			while ($deck = $decksResult->fetch_assoc()) {
				// Check if deck name contains deckName (case-insensitive)
				if (stripos(strtolower($deck['assetName']), $deckName) !== false) {
					$deckFound = $deck['assetIdentifier'];
					break;
				}
			}

			$queryDecks->close();

			if($deckFound != null) {
				$responseText = "";
				global $fromBot, $gameName;
				$fromBot = true;
				$gameName = $deckFound;
				include_once "../SWUDeck/CreateImage.php";
				
				$imageUrl = "https://swustats.net/TCGEngine/SWUDeck/Games/" . $gameName . "/DeckImage.jpg";
				$response = [
					'type' => 4,
					'data' => [
						'content' => $responseText,
						'embeds' => [
							[
								'image' => [
									'url' => $imageUrl
								]
							]
						]
					]
				];
			} else {
				$responseText = "\nNo deck found with that name. Make sure you type a substring of the deck name. Case doesn't matter.";
				$response = [
					'type' => 4, // Respond with a message
					'data' => [
						'content' => $responseText,
						'flags' => 64 // Ephemeral message (only visible to the user)
					]
				];
			}
		} else {
			$responseText = "\nNo linked SWUStats user found.";
			$response = [
				'type' => 4, // Respond with a message
				'data' => [
					'content' => $responseText,
					'flags' => 64 // Ephemeral message (only visible to the user)
				]
			];
		}
		$query->close();
		$conn->close();
	} elseif ($command === "card") {
		$discordID = $interaction['member']['user']['id'] ?? ($interaction['user']['id'] ?? 'Unknown');
		$SWUStatsUsername = SWUStatsUserName($discordID);

        // Extract card name from command options
        $options = $interaction['data']['options'] ?? [];
        $cardName = "Unknown";

        if (!empty($options)) {
            foreach ($options as $option) {
                if ($option['name'] === 'name') {
                    $cardName = $option['value'];
                    break;
                }
            }
        }
		//$cardName = substr_replace($cardName, '_', 3, 0);
		$matches = FindCard($cardName, $SWUStatsUsername == "OotTheMonk");
		if(count($matches) == 1) {
			$uuid = $matches[0];
			$responseText = "Fetching details for: " . CardTitle($uuid);
			$imageUrl = "https://swustats.net/TCGEngine/SWUDeck/WebpImages/" . $uuid . ".webp";
			$response = [
				'type' => 4, // Respond with a message
				'data' => [
					'content' => $responseText,
					'embeds' => [
						[
							'image' => [
								'url' => $imageUrl
							]
						]
					]
				]
			];
		} elseif(count($matches) > 1) {
			$responseText = "Multiple matches found for: " . htmlspecialchars($cardName) . "\nPlease select one:";

			// Create buttons for each match (limit 5 per row)
			$actionRows = [];
			$buttons = [];
			
			foreach ($matches as $match) {
				$setID = CardIDLookup($match);
				$subtitle = CardSubtitle($match);
				$display = $subtitle != null ? $subtitle . " (" . $setID . ")" : CardTitle($match) . " (" . $setID . ")";
				$button = [
					'type' => 2, // Button type
					'label' => $display, // Display card title
					'style' => 1, // Primary button (blue)
					'custom_id' => 'select_card_' . $setID // Unique ID
				];
			
				$buttons[] = $button;
			
				// Discord only allows 5 buttons per action row
				if (count($buttons) === 5) {
					$actionRows[] = ['type' => 1, 'components' => $buttons];
					$buttons = []; // Reset for the next row
				}
			}
			
			// Add the remaining buttons (if any)
			if (!empty($buttons)) {
				$actionRows[] = ['type' => 1, 'components' => $buttons];
			}
			
			// Build the response with action rows
			$response = [
				'type' => 4, // Respond with a message
				'data' => [
					'content' => $responseText,
					'components' => $actionRows, // Multiple rows
					'flags' => 64 // Ephemeral message (only visible to the user)
				]
			];
		} else {
			$response = [
				'type' => 4, // Respond with a message
				'data' => [
					'content' => "Card not found."
				]
			];
		}
    } elseif ($command === "cardstats") {
		$discordID = $interaction['member']['user']['id'] ?? ($interaction['user']['id'] ?? 'Unknown');
		$SWUStatsUsername = SWUStatsUserName($discordID);
		// Extract card name from command options
        $options = $interaction['data']['options'] ?? [];
        $cardName = "Unknown";

        if (!empty($options)) {
            foreach ($options as $option) {
                if ($option['name'] === 'name') {
                    $cardName = $option['value'];
                    break;
                }
            }
        }
		//$cardName = substr_replace($cardName, '_', 3, 0);
		$matches = FindCard($cardName, $SWUStatsUsername == "OotTheMonk");
		if(count($matches) == 1) {
			$uuid = $matches[0];
			$conn = GetLocalMySQLConnection();
			$query = $conn->prepare("SELECT * FROM cardmetastats WHERE cardID = ? ORDER BY week DESC LIMIT 1");
			$query->bind_param("s", $uuid);
			$query->execute();
			$result = $query->get_result();

			if ($result->num_rows > 0) {
				$stats = $result->fetch_assoc();
				$responseText = "Stats for " . CardTitle($uuid) . ":\n";
				$responseText .= "Times Included: " . $stats['timesIncluded'] . " (" . ($stats['timesIncluded'] > 0 ? round(($stats['timesIncludedInWins'] / $stats['timesIncluded']) * 100, 2) : 0) . "% in wins)\n";
				$responseText .= "Times Played: " . $stats['timesPlayed'] . " (" . ($stats['timesPlayed'] > 0 ? round(($stats['timesPlayedInWins'] / $stats['timesPlayed']) * 100, 2) : 0) . "% in wins)\n";
				$responseText .= "Times Resourced: " . $stats['timesResourced'] . " (" . ($stats['timesResourced'] > 0 ? round(($stats['timesResourcedInWins'] / $stats['timesResourced']) * 100, 2) : 0) . "% in wins)\n";

				$response = [
					'type' => 4, // Respond with a message
					'data' => [
						'content' => $responseText
					]
				];
			} else {
				$response = [
					'type' => 4, // Respond with a message
					'data' => [
						'content' => "No stats found for this card."
					]
				];
			}

			$query->close();
			$conn->close();
		} elseif(count($matches) > 1) {
			$responseText = "Multiple matches found for: " . htmlspecialchars($cardName) . "\nPlease select one:";

			// Create buttons for each match (limit 5 per row)
			$actionRows = [];
			$buttons = [];
			
			foreach ($matches as $match) {
				$setID = CardIDLookup($match);
				$subtitle = CardSubtitle($match);
				$display = $subtitle != null ? $subtitle . " (" . $setID . ")" : CardTitle($match) . " (" . $setID . ")";
				$button = [
					'type' => 2, // Button type
					'label' => $display, // Display card title
					'style' => 1, // Primary button (blue)
					'custom_id' => 'card_stats_' . $setID // Unique ID
				];
			
				$buttons[] = $button;
			
				// Discord only allows 5 buttons per action row
				if (count($buttons) === 5) {
					$actionRows[] = ['type' => 1, 'components' => $buttons];
					$buttons = []; // Reset for the next row
				}
			}
			
			// Add the remaining buttons (if any)
			if (!empty($buttons)) {
				$actionRows[] = ['type' => 1, 'components' => $buttons];
			}
			
			// Build the response with action rows
			$response = [
				'type' => 4, // Respond with a message
				'data' => [
					'content' => $responseText,
					'components' => $actionRows, // Multiple rows
					'flags' => 64 // Ephemeral message (only visible to the user)
				]
			];
		} else {
			$response = [
				'type' => 4, // Respond with a message
				'data' => [
					'content' => "Card not found."
				]
			];
		}
	}

    // Send the response back to Discord
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
} else if (isset($interaction['type']) && $interaction['type'] === 3) {
    // 3. Get the custom ID for the clicked button
    $customId = $interaction['data']['custom_id'] ?? '';
	
    // 4. Identify which button was clicked
    if (strpos($customId, 'select_card_') === 0) {
        // Extract the setID or card ID
        $setID = substr($customId, strlen('select_card_'));

		$matches = FindCard($setID, false);

		if(count($matches) > 0) {
			$uuid = $matches[0];
			$responseText = "Fetching details for: " . CardTitle($uuid);
			$imageUrl = "https://swustats.net/TCGEngine/SWUDeck/WebpImages/" . $uuid . ".webp";
			$response = [
				'type' => 4, // Respond with a message
				'data' => [
					'content' => $responseText,
					'embeds' => [
						[
							'image' => [
								'url' => $imageUrl
							]
						]
					]
				]
			];
		}
		header('Content-Type: application/json');
		echo json_encode($response);
		exit;
    } else if (strpos($customId, 'card_stats_') === 0) {
        $setID = substr($customId, strlen('card_stats_'));
		$matches = FindCard($setID, false);
		if(count($matches) > 0) {
			$uuid = $matches[0];
			$response = StatsResponse($uuid);
		} else {
			$response = [
				'type' => 4, // Respond with a message
				'data' => [
					'content' => "Card not found." . $customId
				]
			];
		}
		header('Content-Type: application/json');
		echo json_encode($response);
		exit;
	}
}


// Optionally handle other interaction types here.
http_response_code(400);
echo "Unhandled interaction type";
exit;

function FindCard($cardName, $semanticAllowed=false) {
	$cardName = trim($cardName);
	$cardName = str_replace('_', '', $cardName);
	$uuid = UUIDLookup(substr_replace(strtoupper($cardName), '_', 3, 0));
	if($uuid != null) {
		return [ $uuid ];
	}
	else {
		$cardName = strtolower(CardNicknames($cardName));
		global $titleData;
		$matches = [];
		foreach ($titleData as $uuid => $title) {
			if (stripos($title, $cardName) !== false) {
				$matches[] = $uuid;
			}
		}
		if($semanticAllowed && count($matches) == 0) {
			//If we STILL found no matches, treat this as a semantic search
			include_once "../AIEndpoints/ElasticSearchHelper.php";
			$searchResponse = PerformConversationalSearch($cardName);
			if(isset($searchResponse->message)) {
				// Parse the message to extract UUIDs from "specificCards=uuid1,uuid2,uuid3" format
				if(strpos($searchResponse->message, 'specificCards=') === 0) {
					$cardIds = substr($searchResponse->message, strlen('specificCards='));
					$matches = array_filter(explode(',', $cardIds));
				}
			}
		}
		return $matches;
	}		
}

function StatsResponse($uuid) {
	$conn = GetLocalMySQLConnection();
	$query = $conn->prepare("SELECT * FROM cardmetastats WHERE cardID = ? ORDER BY week DESC LIMIT 1");
	$query->bind_param("s", $uuid);
	$query->execute();
	$result = $query->get_result();

	if ($result->num_rows > 0) {
		$stats = $result->fetch_assoc();
		$responseText = "Stats for " . CardTitle($uuid) . ":\n";
		$responseText .= "Times Included: " . $stats['timesIncluded'] . " (" . ($stats['timesIncluded'] > 0 ? round(($stats['timesIncludedInWins'] / $stats['timesIncluded']) * 100, 2) : 0) . "% in wins)\n";
		$responseText .= "Times Played: " . $stats['timesPlayed'] . " (" . ($stats['timesPlayed'] > 0 ? round(($stats['timesPlayedInWins'] / $stats['timesPlayed']) * 100, 2) : 0) . "% in wins)\n";
		$responseText .= "Times Resourced: " . $stats['timesResourced'] . " (" . ($stats['timesResourced'] > 0 ? round(($stats['timesResourcedInWins'] / $stats['timesResourced']) * 100, 2) : 0) . "% in wins)\n";

		$response = [
			'type' => 4, // Respond with a message
			'data' => [
				'content' => $responseText
			]
		];
	} else {
		$response = [
			'type' => 4, // Respond with a message
			'data' => [
				'content' => "No stats found for this card."
			]
		];
	}
	return $response;
}

function CardNicknames($cardName)
{
	switch($cardName) {
		case "chewie":
			return "Chewbacca";
		case "flyboy":
			return "Han Solo";
		case "threepio":
			return "C-3PO";
		case "artoo":
			return "R2-D2";
		case "beebee":
			return "BB-8";
		case "baby yoda":
			return "Grogu";
		case "uwing":
			return "U-Wing Reinforcements";
		default: return $cardName;
	}
}

function SWUStatsUserName($discordID) {
	$conn = GetLocalMySQLConnection();
	$query = $conn->prepare("SELECT usersId, usersUid FROM users WHERE discordID = ?");
	$query->bind_param("s", $discordID);
	$query->execute();
	$result = $query->get_result();
	$swuStatsId = -1;
	if ($result && $result->num_rows > 0) {
		$userRecord = $result->fetch_assoc();
		$swuStatsId = $userRecord['usersUid'];
	}
	$query->close();
	$conn->close();
	return $swuStatsId;
}

?>

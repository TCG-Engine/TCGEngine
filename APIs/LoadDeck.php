<?php

// A Twin Suns deck stores BOTH leaders in the ordinary Leader zone — element [1] is the second one.
// This mirrors SWUDeck's own validator (SWUDeck/ValidateDeckState.php), which builds its leader list by
// iterating GetLeader(1); it is the authoritative reader for what a deck's leaders are.
// ⚠ Do NOT use the separate Leader2 zone. It is legacy and its contents are garbage on real decks —
// measured locally: it holds a default SOR_005 on dozens of ordinary Premier decks and even a Unit
// ("Cell Block Guard") on others, while only 6 of 81 decks have a genuine second entry in Leader[].
// Trusting Leader2 silently turns valid Premier decks into invalid 2-leader ones.
function _LoadDeckSecondLeaderID($leaderZone) {
  if (!is_array($leaderZone) || count($leaderZone) < 2) return '';
  if (!isset($leaderZone[1]->CardID)) return '';
  $cid = trim((string)$leaderZone[1]->CardID);
  if ($cid === '') return '';
  // Defensive: a deck file that has drifted shouldn't be able to publish a non-Leader as a leader.
  if (function_exists('CardType') && CardType($cid) !== 'Leader') return '';
  return $cid;
}


  // CORS headers for public API
  header("Access-Control-Allow-Origin: *");
  header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
  header("Access-Control-Allow-Headers: Content-Type");

  require_once "../Core/HTTPLibraries.php";

  $response = new stdClass();

  $deckID = TryGet("deckID", default: "");
  $format = TryGet("format", default: "json");
  $folderPath = TryGet("folderPath", default: "SWUDeck");
  $setId = TryGet("setId", default: false);
  if($setId == "true") $setId = true;
  else $setId = false;

  if($deckID == "") {
	$response->error = "Missing parameters";
	echo (json_encode($response));
	exit();
  }

  require_once "../" . $folderPath . "/GeneratedCode/GeneratedCardDictionaries.php";
  require_once "../" . $folderPath . "/GamestateParser.php";
  require_once "../" . $folderPath . "/ZoneClasses.php";
  require_once "../" . $folderPath . "/ZoneAccessors.php";
  
  require_once "../Database/ConnectionManager.php";
  require_once "../AccountFiles/AccountDatabaseAPI.php";

  // Friendly-code support (additive): a 12-letter code resolves to the numeric deck id.
  // Numeric deckID is untouched -> existing responses are byte-identical.
  if (preg_match('/^[A-Za-z]{12}$/', $deckID)) {
    $resolved = ResolveFriendlyCode($deckID);
    if ($resolved !== null) { $deckID = (string)$resolved; }
  }

  $gameName = $deckID;
  ParseGamestate("../" . $folderPath . "/");

  $conn = GetLocalMySQLConnection();

  $query = $conn->prepare("SELECT assetName FROM ownership WHERE assetType = 1 AND assetIdentifier = ?");
  $query->bind_param("i", $deckID);
  $query->execute();
  $query->bind_result($assetName);
  $query->fetch();
  $query->close();

  if($assetName == "") $assetName = "Deck #" . $deckID;

  if($format == "sha256") {
	
	$deckJson = new stdClass();
	ParseGamestate("../SWUDeck/");
	$leader = &GetLeader(1);
	$base = &GetBase(1);
	$mainDeck = &GetMainDeck(1);
	$sideboard = &GetSideboard(1);
	$deckJson->leader = $leader[0]->CardID;
	$deckJson->base = $base[0]->CardID;
	$deckJson->mainDeck = array();
	$deckJson->sideboard = array();
	foreach ($mainDeck as $card) {
		$deckJson->mainDeck[] = $card->CardID;
	}
	foreach ($sideboard as $card) {
		$deckJson->sideboard[] = $card->CardID;
	}
	sort($deckJson->mainDeck);
	sort($deckJson->sideboard);

	$deckHash = hash('sha256', json_encode($deckJson));
	echo($deckHash);
  }
  else if($format == "json") {
	if($folderPath == "SoulMastersDB"){
		$response = SoulMastersDeckJSON();
	} else {
		$response = new stdClass();
		$response->metadata = new stdClass();
		$response->metadata->name = $assetName;
		$response->leader = new stdClass();
		$leader = &GetLeader(1);
		$response->leader = new stdClass();
		$response->leader->id = $setId ? CardIDLookup($leader[0]->CardID) : $leader[0]->CardID;
		$response->leader->count = 1;
		// Twin Suns decks carry a SECOND leader as element [1] of the same Leader zone. Emit it as
		// `secondleader` — the same shape as `leader`, and the field name swudb.com uses, which SWUSim's
		// importer (SWUNormalizeStandardJSON) already reads. Without it a Twin Suns deck imports with one
		// leader and fails validation as "requires exactly 2 leaders; found 1".
		// ADDITIVE: only present when the deck genuinely has two leaders, so every single-leader (Premier,
		// Eternal, …) response stays byte-identical and existing consumers are unaffected.
		$secondLeaderID = _LoadDeckSecondLeaderID($leader);
		if ($secondLeaderID !== '') {
			$response->secondleader = new stdClass();
			$response->secondleader->id = $setId ? CardIDLookup($secondLeaderID) : $secondLeaderID;
			$response->secondleader->count = 1;
		}
		$base = &GetBase(1);
		$response->base = new stdClass();
		$response->base->id = $setId ? CardIDLookup($base[0]->CardID) : $base[0]->CardID;
		$response->base->count = 1;
		$response->deck = array();
		$cards = &GetMainDeck(1);
		$mainQuantityIndex = [];
		foreach ($cards as $card) {
			$cardID = $card->CardID;
			if (isset($mainQuantityIndex[$cardID])) {
				$mainQuantityIndex[$cardID]++;
			} else {
				$mainQuantityIndex[$cardID] = 1;
			}
		}
		$sideboardQuantityIndex = [];
		$cards = &GetSideboard(1);
		foreach ($cards as $card) {
			$cardID = $card->CardID;
			if (isset($sideboardQuantityIndex[$cardID])) {
				$sideboardQuantityIndex[$cardID]++;
			} else {
				$sideboardQuantityIndex[$cardID] = 1;
			}
		}
		$response->deck = array();
		foreach ($mainQuantityIndex as $cardID => $quantity) {
			$cardObj = new stdClass();
			$cardObj->id = $setId ? CardIDLookup($cardID) : $cardID;
			$cardObj->count = $quantity;
			$response->deck[] = $cardObj;
		}
		$response->sideboard = array();
		if(count($sideboardQuantityIndex) > 0) {
			$response->sideboard = array();
			foreach ($sideboardQuantityIndex as $cardID => $quantity) {
				$cardObj = new stdClass();
				$cardObj->id = $setId ? CardIDLookup($cardID) : $cardID;
				$cardObj->count = $quantity;
				$response->sideboard[] = $cardObj;
			}
		}
	}
	echo(json_encode($response));
  } else if($format == "text") {
	if($folderPath == "SoulMastersDB"){
		SoulMastersDeckText();
	} else if($folderPath == "SWUDeck"){
		SWUDeckText();
	}
  }
  else {
	$base = &GetBase(1);
	$leader = &GetLeader(1);
	echo($base[0]->CardID . " " . $leader[0]->CardID . "\r\n");
  
	$cards = &GetMainDeck(1);
	for($i=0; $i<count($cards); ++$i) {
	  if($i > 0) echo(" ");
	  $obj = $cards[$i];
	  echo($obj->CardID);
	}
  }

  function SoulMastersDeckJSON() {
	$deckJson = new stdClass();
	$commanders = &GetCommander(1);
	$deckJson->Commanders = [];
	foreach ($commanders as $commander) {
		$deckJson->Commanders[] = new stdClass();
		$deckJson->Commanders[count($deckJson->Commanders) - 1]->id = $commander->CardID;
		$deckJson->Commanders[count($deckJson->Commanders) - 1]->count = 1;
	}
	$mainDeck = &GetMainDeck(1);
	$deckJson->deck = [];
	$quantityIndex = [];
	foreach ($mainDeck as $card) {
		$cardID = $card->CardID;
		if (isset($quantityIndex[$cardID])) {
			$quantityIndex[$cardID]++;
		} else {
			$quantityIndex[$cardID] = 1;
		}
	}
	foreach ($quantityIndex as $cardID => $quantity) {
		$cardObj = new stdClass();
		$cardObj->id = $cardID;
		$cardObj->count = $quantity;
		$deckJson->deck[] = $cardObj;
	}

	$reserves = &GetReserveDeck(1);
	$deckJson->reserves = [];
	$quantityIndex = [];
	foreach ($reserves as $card) {
		$cardID = $card->CardID;
		if (isset($quantityIndex[$cardID])) {
			$quantityIndex[$cardID]++;
		} else {
			$quantityIndex[$cardID] = 1;
		}
	}
	foreach ($quantityIndex as $cardID => $quantity) {
		$cardObj = new stdClass();
		$cardObj->id = $cardID;
		$cardObj->count = $quantity;
		$deckJson->reserves[] = $cardObj;
	}
	return $deckJson;
  }

  function SWUDeckText() {
	$deckText = "";
	$leader = &GetLeader(1);
	$deckText .= "Leader\r\n1 " . CardTitle($leader[0]->CardID) . " | " . CardSubtitle($leader[0]->CardID) . "\r\n";
	// Twin Suns decks carry a second leader in the separate Leader2 zone — list it under the same
	// "Leader" header so an exported deck list round-trips instead of silently losing a leader.
	$secondLeaderID = _LoadDeckSecondLeaderID($leader);
	if ($secondLeaderID !== '') {
		$deckText .= "1 " . CardTitle($secondLeaderID) . " | " . CardSubtitle($secondLeaderID) . "\r\n";
	}
	$deckText .= "\r\n";
	$base = &GetBase(1);
	$deckText .= "Base\r\n1 " . CardTitle($base[0]->CardID) . "\r\n\r\n";

	$deckText .= "Main Deck\r\n";
	$cards = &GetMainDeck(1);
	$mainQuantityIndex = [];
	foreach ($cards as $card) {
		$cardID = $card->CardID;
		if (isset($mainQuantityIndex[$cardID])) {
			$mainQuantityIndex[$cardID]++;
		} else {
			$mainQuantityIndex[$cardID] = 1;
		}
	}
	foreach ($mainQuantityIndex as $cardID => $quantity) {
		$subtitle = CardSubtitle($cardID);
		$deckText .= $quantity . " " . CardTitle($cardID);
		if($subtitle != "") $deckText .= " | " . CardSubtitle($cardID);
		$deckText .= "\r\n";
	}
	$deckText .= "\r\n";

	$deckText .= "Sideboard\r\n";
	$sideboardQuantityIndex = [];
	$cards = &GetSideboard(1);
	foreach ($cards as $card) {
		$cardID = $card->CardID;
		if (isset($sideboardQuantityIndex[$cardID])) {
			$sideboardQuantityIndex[$cardID]++;
		} else {
			$sideboardQuantityIndex[$cardID] = 1;
		}
	}
	foreach ($sideboardQuantityIndex as $cardID => $quantity) {
		$subtitle = CardSubtitle($cardID);
		$deckText .= $quantity . " " . CardTitle($cardID);
		if($subtitle != "") $deckText .= " | " . CardSubtitle($cardID);
		$deckText .= "\r\n";
	}
	$deckText .= "\r\n";

	echo($deckText);
  }

  function SoulMastersDeckText() {
	$arr = &GetCommander(1);
	$commanderName = count($arr) > 0 ? CardName($arr[0]->CardID) : "";
	echo("Commander: $commanderName\r\n");
	$quantityIndex = [];
	$arr = &GetMainDeck(1);
	foreach ($arr as $card) {
		$cardID = $card->CardID;
		if (isset($quantityIndex[$cardID])) {
			$quantityIndex[$cardID]++;
		} else {
			$quantityIndex[$cardID] = 1;
		}
	}

	$numUnits = 0;
	$numEvents = 0;
	$numUpgrades = 0;
	$units = [];
	$events = [];
	$upgrades = [];
	foreach ($quantityIndex as $cardID => $quantity) {
		$cardID = strval($cardID);
		$cardFaction = CardFaction($cardID);
		$cardString = CardName($cardID) . " x{$quantity} (Rarity: " . CardRarity($cardID) . ", Type: " . CardSubType($cardID);
		if (strpos($cardFaction, "Mercenary") !== false) {
			$cardString .= ", Faction: Mercenary";
		}
		$cardString .= ")";
		$units[] = $cardString;
		$numUnits += $quantity;
	}

	// Sort each section alphabetically
	sort($units);

	echo("\r\nMain Deck (" . $numUnits . "):\r\n");
	foreach ($units as $unit) {
		echo($unit . "\r\n");
	}


	$reserves = [];
	$numReserves = 0;
	$arr = &GetReserveDeck(1);
	$reservesQuantityIndex = [];
	foreach ($arr as $card) {
		$cardID = $card->CardID;
		if (isset($reservesQuantityIndex[$cardID])) {
			$reservesQuantityIndex[$cardID]++;
		} else {
			$reservesQuantityIndex[$cardID] = 1;
		}
	}
	foreach ($reservesQuantityIndex as $cardID => $quantity) {
		$cardID = strval($cardID);
		$cardString = CardName($cardID) . " x{$quantity} (Rarity: " . CardRarity($cardID) . ", Type: " . CardSubType($cardID) . ")";
		$numReserves += $quantity;
		$reserves[] = $cardString;
	}
	// Sort reserves alphabetically
	sort($reserves);
	echo("\r\nReserve Deck (" . count($reserves) . "):\r\n");
	foreach ($reserves as $reserve) {
		echo($reserve . "\r\n");
	}
  }

?>

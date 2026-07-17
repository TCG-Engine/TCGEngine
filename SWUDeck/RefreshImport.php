<?php
  include_once './GamestateParser.php';
  include_once './ZoneAccessors.php';
  include_once './ZoneClasses.php';
  include_once __DIR__ . '/../AppCore/SWU/Overrides.php';
  include_once '../Core/CoreZoneModifiers.php';
  include_once './GeneratedCode/GeneratedCardDictionaries.php';
  include_once '../Core/HTTPLibraries.php';
  include_once './Custom/CardIdentifiers.php';

  include_once '../Core/NetworkingLibraries.php';

  include_once '../Database/ConnectionManager.php';
  include_once '../AccountFiles/AccountDatabaseAPI.php';
  include_once '../AccountFiles/AccountSessionAPI.php';

  if(!IsUserLoggedIn()) {
    header("location: ../SharedUI/LoginPage.php");
    exit();
  }

  $gameName = TryGet("deckID", "");
  $assetSource = TryGet("source", null);
  $assetSourceID = TryGet("sourceID", "");
  $playerID = TryGet("playerID", null);

  if($gameName == "" || $assetSource == null || $assetSourceID == "" || $playerID == null) {
    header("location: ../SharedUI/ErrorPage.php?error=MissingParameters");
    exit();
  }

  $deckLink = "https://swudb.com/api/getDeckJson/" . $assetSourceID;
  if($assetSource == 2) {
      $deckLink = "https://melee.gg/Decklist/View/" . $assetSourceID;
  }
  else if($assetSource == 3) {
      $deckLink = "https://swubase.com/api/deck/" . $assetSourceID . "/json";
  }
  $curl = curl_init();
  curl_setopt($curl, CURLOPT_URL, $deckLink);
  curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
  $apiDeck = curl_exec($curl);
  $apiInfo = curl_getinfo($curl);
  $errorMessage = curl_error($curl);
  curl_close($curl);

  if($apiDeck === false) {
    header("location: ../SharedUI/ErrorPage.php?error=DeckFetchFailed");
    exit();
  }

  $json = $apiDeck;
  $deckObj = json_decode($json);

  if($deckObj === null) {
    header("location: ../SharedUI/ErrorPage.php?error=InvalidDeckData");
    exit();
  }

  ParseGamestate();

  // Save a version snapshot before overwriting with the refreshed deck
  SaveVersion($playerID);

  // Clear existing deck data
  $p1Leader = [];
  $p1Base = [];
  $p1MainDeck = [];
  $p1Sideboard = [];

  // Update deck with new data
  $leader = UUIDLookup(NormalizeCardID($deckObj->leader->id));
  SetAssetKeyIdentifier(1, $gameName, 1, $leader);
  array_push($p1Leader, new Leader($leader));
  if(isset($deckObj->secondleader)) {
    $secondLeader = UUIDLookup(NormalizeCardID($deckObj->secondleader->id));
    SetAssetKeyIdentifier(1, $gameName, 3, $secondLeader);
    array_push($p1Leader, new Leader($secondLeader));
  } else {
    SetAssetKeyIdentifier(1, $gameName, 3, null); // clear a stale 2nd-leader thumbnail if refreshed data no longer has one
  }
  $base = UUIDLookup(NormalizeCardID($deckObj->base->id));
  SetAssetKeyIdentifier(1, $gameName, 2, $base);
  array_push($p1Base, new Base($base));
  $deck = $deckObj->deck;
  for($i=0; $i<count($deck); ++$i) {
    $cardID = CardIDOverride(NormalizeCardID($deck[$i]->id ?? null));
    $cardID = UUIDLookup($cardID);
    // A lookup miss (unknown/retired/not-yet-added set code) must not become a phantom zone
    // entry with a blank CardID — that renders as a broken card image client-side.
    if ($cardID === null) {
      error_log("RefreshImport: main deck card not found for id '" . ($deck[$i]->id ?? '') . "' — skipping.");
      continue;
    }
    for($j=0; $j<$deck[$i]->count; ++$j) {
      array_push($p1MainDeck, new MainDeck($cardID));
    }
  }
  $sideboard = $deckObj->sideboard ?? [];
  for($i=0; $i<count($sideboard); ++$i) {
    $cardID = CardIDOverride(NormalizeCardID($sideboard[$i]->id ?? null));
    $cardID = UUIDLookup($cardID);
    if ($cardID === null) {
      error_log("RefreshImport: sideboard card not found for id '" . ($sideboard[$i]->id ?? '') . "' — skipping.");
      continue;
    }
    for($j=0; $j<$sideboard[$i]->count; ++$j) {
      array_push($p1Sideboard, new Sideboard($cardID));
    }
  }

  ++$updateNumber;
  WriteGamestate();
  GamestateUpdated($gameName);
  if (is_numeric($gameName)) TouchOwnershipLastUpdated(intval($gameName));

?>
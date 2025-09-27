<?php
  include_once './GamestateParser.php';
  include_once './ZoneAccessors.php';
  include_once './ZoneClasses.php';
  include_once './Overrides.php';
  include_once '../Core/CoreZoneModifiers.php';
  include_once './GeneratedCode/GeneratedCardDictionaries.php';
  include_once '../Core/HTTPLibraries.php';

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

  if($gameName == "" || $assetSource == null || $assetSourceID == "") {
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

  // Clear existing deck data
  $p1Leader = [];
  $p1Base = [];
  $p1MainDeck = [];
  $p1Sideboard = [];

  // Update deck with new data
  $leader = UUIDLookup($deckObj->leader->id);
  SetAssetKeyIdentifier(1, $gameName, 1, $leader);
  array_push($p1Leader, new Leader($leader));
  $base = UUIDLookup($deckObj->base->id);
  SetAssetKeyIdentifier(1, $gameName, 2, $base);
  array_push($p1Base, new Base($base));
  $deck = $deckObj->deck;
  for($i=0; $i<count($deck); ++$i) {
    $cardID = CardIDOverride($deck[$i]->id);
    $cardID = UUIDLookup($cardID);
    for($j=0; $j<$deck[$i]->count; ++$j) {
      array_push($p1MainDeck, new MainDeck($cardID));
    }
  }
  $sideboard = $deckObj->sideboard ?? [];
  for($i=0; $i<count($sideboard); ++$i) {
    $cardID = CardIDOverride($sideboard[$i]->id);
    $cardID = UUIDLookup($cardID);
    for($j=0; $j<$sideboard[$i]->count; ++$j) {
      array_push($p1Sideboard, new Sideboard($cardID));
    }
  }

  WriteGamestate();

?>
<?php
// Maintenance gate — must precede any write.
// Rewrites a deck gamestate file from its source list.
require_once __DIR__ . '/../AppCore/SWU/Maintenance.php';
SWUMaintenanceRequire('SWUDeck', 'deck');

  include_once './GamestateParser.php';
  include_once './ZoneAccessors.php';
  include_once './ZoneClasses.php';
  include_once __DIR__ . '/../AppCore/SWU/Overrides.php';
  include_once '../Core/CoreZoneModifiers.php';
  include_once './GeneratedCode/GeneratedCardDictionaries.php';
  include_once '../Core/HTTPLibraries.php';
  include_once './Custom/CardIdentifiers.php';
  include_once __DIR__ . '/../AppCore/SWU/DeckLinkImport.php';

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

  // Every source (incl. melee.gg, which is scraped) comes back in the standard deck shape — see
  // AppCore/SWU/DeckLinkImport.php. Melee ids stored before the shared importer were cut to 31
  // chars and cannot be fetched; those refreshes fail here rather than load the wrong deck.
  $fetched = SWUDeckLinkFetchBySource($assetSource, $assetSourceID);
  if(!$fetched['success']) {
    error_log("RefreshImport: source $assetSource id '$assetSourceID' failed: " . $fetched['message']);
    header("location: ../SharedUI/ErrorPage.php?error=DeckFetchFailed");
    exit();
  }

  $deckObj = json_decode(json_encode($fetched['deck']));

  ParseGamestate();

  // Save a version snapshot before overwriting with the refreshed deck
  SaveVersion($playerID);

  // Clear existing deck data
  $p1Leader = [];
  $p1Base = [];
  $p1MainDeck = [];
  $p1Sideboard = [];

  // Update deck with new data
  $leader = SWUDeckImportCardID($deckObj->leader->id);
  SetAssetKeyIdentifier(1, $gameName, 1, $leader);
  array_push($p1Leader, new Leader($leader));
  if(isset($deckObj->secondleader)) {
    $secondLeader = SWUDeckImportCardID($deckObj->secondleader->id);
    SetAssetKeyIdentifier(1, $gameName, 3, $secondLeader);
    array_push($p1Leader, new Leader($secondLeader));
  } else {
    SetAssetKeyIdentifier(1, $gameName, 3, null); // clear a stale 2nd-leader thumbnail if refreshed data no longer has one
  }
  $base = SWUDeckImportCardID($deckObj->base->id);
  SetAssetKeyIdentifier(1, $gameName, 2, $base);
  array_push($p1Base, new Base($base));
  $deck = $deckObj->deck;
  for($i=0; $i<count($deck); ++$i) {
    $cardID = CardIDOverride(NormalizeCardID($deck[$i]->id ?? null));
    $cardID = SWUDeckImportCardID($cardID);
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
    $cardID = SWUDeckImportCardID($cardID);
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
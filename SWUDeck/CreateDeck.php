<?php
// Maintenance gate — must precede any write.
// Writes a new deck gamestate file.
require_once __DIR__ . '/../AppCore/SWU/Maintenance.php';
SWUMaintenanceRequire('SWUDeck', 'deck');

  include_once './GamestateParser.php';
  include_once './ZoneAccessors.php';
  include_once './ZoneClasses.php';
  include_once __DIR__ . '/../AppCore/SWU/Overrides.php';
  include_once '../Core/CoreZoneModifiers.php';
  include_once './GeneratedCode/GeneratedCardDictionaries.php';
  include_once '../Core/HTTPLibraries.php';
  // Include the new helper file with card identifier functions
  include_once './Custom/CardIdentifiers.php';
  include_once './Custom/DeckFormats.php';
  include_once __DIR__ . '/../AppCore/SWU/DeckLinkImport.php';

  include_once '../Database/ConnectionManager.php';
  include_once '../AccountFiles/AccountDatabaseAPI.php';
  include_once '../AccountFiles/AccountSessionAPI.php';

  if(!IsUserLoggedIn()) {
    header("location: ../SharedUI/LoginPage.php");
    exit();
  }

  $deckLink = TryGet("deckLink", "");
  $format = TryGet("format", "premier");
  if (!array_key_exists($format, SWUDeckBuildableFormats())) $format = "premier"; // guard unknown/garbage input
  $deckName = substr(trim(TryGet("name", "")), 0, 100); // optional user-supplied name; blank keeps the default "Deck #<id>"

  $gameName = GetGameCounter();

  InitializeGamestate();
  $userID = LoggedInUser();

  $assetSource = null;
  $assetSourceID = null;

  // Note: The helper functions for card identification have been moved to ./Custom/CardIdentifiers.php

  if($deckLink != "") {
    if(str_contains($deckLink, "swudb.com/deck")) {
      $decklinkArr = explode("/", $deckLink);
      $assetSource = 0;
      $assetSourceID = trim($decklinkArr[count($decklinkArr) - 1]);
      $deckLink = "https://swudb.com/api/getDeckJson/" . $assetSourceID;
      $curl = curl_init();
      curl_setopt($curl, CURLOPT_URL, $deckLink);
      curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
      $apiDeck = curl_exec($curl);
      $apiInfo = curl_getinfo($curl);
      $errorMessage = curl_error($curl);
      curl_close($curl);
      $json = $apiDeck;
    } else if(str_contains($deckLink, "swustats.net")) {
      $decklinkArr = explode("gameName=", $deckLink);
      $decklinkArr = explode("&", $decklinkArr[1]);
      $assetSource = 1;
      $assetSourceID = trim($decklinkArr[0]);
      $deckLink = "https://swustats.net/TCGEngine/APIs/LoadDeck.php?deckID=$assetSourceID&format=json&setId=true";
      $curl = curl_init();
      curl_setopt($curl, CURLOPT_URL, $deckLink);
      curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
      $apiDeck = curl_exec($curl);
      $apiInfo = curl_getinfo($curl);
      $errorMessage = curl_error($curl);
      curl_close($curl);
      $json = $apiDeck;
    } else if(SWUDeckLinkParse($deckLink) !== null) {
      // melee.gg, swubase, protectthepod, swucardhub, swuforge, swumetastats, sw-unlimited-db —
      // see AppCore/SWU/DeckLinkImport.php. On a failed fetch $json stays unset (no import).
      $fetched = SWUDeckLinkFetch($deckLink);
      if($fetched['success']) {
        $assetSource = $fetched['source'];
        $assetSourceID = $fetched['sourceID'];
        $json = json_encode($fetched['deck']);
      } else {
        error_log("CreateDeck: deck link import failed for '" . $deckLink . "': " . $fetched['message']);
      }
    } else $json = $deckLink;
    if(isset($json) && $json != "") {
      SaveAssetOwnership(1, $gameName, $userID, $assetSource, $assetSourceID, $format);//assetType 1 = Deck
      AssignFriendlyCode(1, $gameName);
      $deckObj = json_decode($json);
      if ($deckName !== "") {
        UpdateAssetName(1, $gameName, $deckName); // user-supplied name wins over the imported list's name
      } else if (isset($deckObj->metadata->name)) {
        UpdateAssetName(1, $gameName, $deckObj->metadata->name); // Update deck name if available
      }
      if(isset($deckObj->leader)) {
        $leader = SWUDeckImportCardID($deckObj->leader->id);
        SetAssetKeyIdentifier(1, $gameName, 1, $leader);
        array_push($p1Leader, new Leader($leader));
      }
      if(isset($deckObj->secondleader)) {
        $secondLeader = SWUDeckImportCardID($deckObj->secondleader->id);
        SetAssetKeyIdentifier(1, $gameName, 3, $secondLeader);
        array_push($p1Leader, new Leader($secondLeader));
      }
      if(isset($deckObj->base)) {
        $base = SWUDeckImportCardID($deckObj->base->id);
        SetAssetKeyIdentifier(1, $gameName, 2, $base);
        array_push($p1Base, new Base($base));
      }
      $deck = $deckObj->deck ?? [];
      if($deck != null) {
        for($i=0; $i<count($deck); ++$i) {
          $cardID = CardIDOverride(NormalizeCardID($deck[$i]->id ?? null));
          $cardID = SWUDeckImportCardID($cardID);
          // A lookup miss (unknown/retired/not-yet-added set code) must not become a phantom
          // zone entry with a blank CardID — that renders as a broken card image client-side.
          if ($cardID === null) {
            error_log("CreateDeck: main deck card not found for id '" . ($deck[$i]->id ?? '') . "' — skipping.");
            continue;
          }
          for($j=0; $j<$deck[$i]->count; ++$j) {
            array_push($p1MainDeck, new MainDeck($cardID));
          }
        }
      }
      $sideboard = $deckObj->sideboard ?? [];
      if($sideboard != null) {
        for($i=0; $i<count($sideboard); ++$i) {
          $cardID = CardIDOverride(NormalizeCardID($sideboard[$i]->id ?? null));
          $cardID = SWUDeckImportCardID($cardID);
          if ($cardID === null) {
            error_log("CreateDeck: sideboard card not found for id '" . ($sideboard[$i]->id ?? '') . "' — skipping.");
            continue;
          }
          for($j=0; $j<$sideboard[$i]->count; ++$j) {
            array_push($p1Sideboard, new Sideboard($cardID));
          }
        }
      }
    }
  } else {
    SaveAssetOwnership(1, $gameName, $userID, $assetSource, $assetSourceID, $format);//assetType 1 = Deck
    AssignFriendlyCode(1, $gameName);
    if ($deckName !== "") UpdateAssetName(1, $gameName, $deckName); // optional name for a blank new deck
  }

  WriteGamestate();

  $params = "?gameName=" . $gameName . "&playerID=1" . "&folderPath=SWUDeck";
  header("location: ../NextTurn.php" . $params);

?>
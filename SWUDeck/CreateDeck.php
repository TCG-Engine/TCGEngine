<?php
  include_once './GamestateParser.php';
  include_once './ZoneAccessors.php';
  include_once './ZoneClasses.php';
  include_once '../Core/CoreZoneModifiers.php';
  include_once './GeneratedCode/GeneratedCardDictionaries.php';
  include_once '../Core/HTTPLibraries.php';
  // Include the new helper file with card identifier functions
  include_once './Custom/CardIdentifiers.php';

  include_once '../Database/ConnectionManager.php';
  include_once '../AccountFiles/AccountDatabaseAPI.php';
  include_once '../AccountFiles/AccountSessionAPI.php';

  if(!IsUserLoggedIn()) {
    header("location: ../SharedUI/LoginPage.php");
    exit();
  }

  $deckLink = TryGet("deckLink", "");

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
    } else if(str_contains($deckLink, "melee.gg/Decklist")) {
      // Use the original URL for melee.gg decklists
      $decklinkArr = explode("/", $deckLink);
      $assetSource = 2;
      $assetSourceID = substr(trim($decklinkArr[count($decklinkArr) - 1]), 0, 31);
      
      // Fetch the HTML content from melee.gg
      $curl = curl_init();
      curl_setopt($curl, CURLOPT_URL, $deckLink);
      curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
      curl_setopt($curl, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36');
      $htmlContent = curl_exec($curl);
      $apiInfo = curl_getinfo($curl);
      $errorMessage = curl_error($curl);
      curl_close($curl);
      
      if(!empty($htmlContent)) {
        // Create a DOM parser
        $dom = new DOMDocument();
        @$dom->loadHTML($htmlContent);
        $xpath = new DOMXPath($dom);
        
        // Create the structure that matches our expected format
        $deckObj = new stdClass();
        
        // Extract deck title
        $deckTitleNodes = $xpath->query("//div[@class='decklist-title']");
        $deckTitle = "";
        if($deckTitleNodes->length > 0) {
          $deckTitle = trim($deckTitleNodes->item(0)->nodeValue);
          
          // Add metadata with the deck name
          $deckObj->metadata = new stdClass();
          $deckObj->metadata->name = $deckTitle;
          
          // Parse deck name to identify leader and base
          $deckNameParts = explode(" - ", $deckTitle);
          
          if(count($deckNameParts) >= 2) {
            // Create leader object - from title
            $deckObj->leader = new stdClass();
            $leaderName = $deckNameParts[0];
            // Use the shared helper function
            $leaderSetCode = FindCardSetCode($leaderName);
            if($leaderSetCode != null) {
              $deckObj->leader->id = $leaderSetCode;
              $deckObj->leader->count = 1;
            }
            
            // Create base object - from title
            $deckObj->base = new stdClass();
            $baseName = $deckNameParts[1];
            // Use the shared helper function
            $baseSetCode = FindCardSetCode($baseName);
            if($baseSetCode != null) {
              $deckObj->base->id = $baseSetCode;
              $deckObj->base->count = 1;
            }
          }
        }
        
        // Extract cards from the HTML
        $deckObj->deck = [];
        $deckObj->sideboard = [];
        
        // Find all card categories
        $categoryNodes = $xpath->query("//div[@class='decklist-category']");
        foreach($categoryNodes as $categoryNode) {
          // Get category title
          $categoryTitleNodes = $xpath->query(".//div[@class='decklist-category-title']", $categoryNode);
          $categoryTitle = "";
          if($categoryTitleNodes->length > 0) {
            $categoryTitle = trim($categoryTitleNodes->item(0)->nodeValue);
          }
          
          // Skip processing if we already have leader and base from title
          if($categoryTitle == "Leader (1)" || $categoryTitle == "Base (1)") {
            // If leader wasn't found in the title, try to extract it here
            if($categoryTitle == "Leader (1)" && (!isset($deckObj->leader) || !isset($deckObj->leader->id))) {
              $cardNodes = $xpath->query(".//div[@class='decklist-record']", $categoryNode);
              if($cardNodes->length > 0) {
                $cardNode = $cardNodes->item(0);
                $nameNodes = $xpath->query(".//a[@class='decklist-record-name']", $cardNode);
                if($nameNodes->length > 0) {
                  $cardName = trim($nameNodes->item(0)->nodeValue);
                  $deckObj->leader = new stdClass();
                  // Use the shared helper function
                  $leaderSetCode = FindCardSetCode($cardName);
                  if($leaderSetCode != null) {
                    $deckObj->leader->id = $leaderSetCode;
                    $deckObj->leader->count = 1;
                  }
                }
              }
            }
            
            // If base wasn't found in the title, try to extract it here
            if($categoryTitle == "Base (1)" && (!isset($deckObj->base) || !isset($deckObj->base->id))) {
              $cardNodes = $xpath->query(".//div[@class='decklist-record']", $categoryNode);
              if($cardNodes->length > 0) {
                $cardNode = $cardNodes->item(0);
                $nameNodes = $xpath->query(".//a[@class='decklist-record-name']", $cardNode);
                if($nameNodes->length > 0) {
                  $cardName = trim($nameNodes->item(0)->nodeValue);
                  $deckObj->base = new stdClass();
                  // Use the shared helper function
                  $baseSetCode = FindCardSetCode($cardName);
                  if($baseSetCode != null) {
                    $deckObj->base->id = $baseSetCode;
                    $deckObj->base->count = 1;
                  }
                }
              }
            }
            continue;
          }
          
          // Process cards in this category
          $cardNodes = $xpath->query(".//div[@class='decklist-record']", $categoryNode);
          foreach($cardNodes as $cardNode) {
            $quantityNodes = $xpath->query(".//span[@class='decklist-record-quantity']", $cardNode);
            $nameNodes = $xpath->query(".//a[@class='decklist-record-name']", $cardNode);
            
            if($quantityNodes->length > 0 && $nameNodes->length > 0) {
              $quantity = intval(trim($quantityNodes->item(0)->nodeValue));
              $cardName = trim($nameNodes->item(0)->nodeValue);
              
              // Find card ID - use the shared helper function
              $cardSetCode = FindCardSetCode($cardName);
              if($cardSetCode != null) {
                $cardObject = new stdClass();
                $cardObject->id = $cardSetCode;
                $cardObject->count = $quantity;
                
                // Add to appropriate list based on category
                if(stripos($categoryTitle, "Sideboard") !== false) {
                  $deckObj->sideboard[] = $cardObject;
                } else {
                  $deckObj->deck[] = $cardObject;
                }
              } else {
                // Log cards that weren't found (for debugging)
                error_log("Card not found: " . $cardName . " in category: " . $categoryTitle);
              }
            }
          }
        }
        
        // Convert back to JSON string
        $json = json_encode($deckObj);
      }
    } else $json = $deckLink;
    if(isset($json) && $json != "") {
      SaveAssetOwnership(1, $gameName, $userID, $assetSource, $assetSourceID);//assetType 1 = Deck
      $deckObj = json_decode($json);
      if(isset($deckObj->leader)) {
        $leader = UUIDLookup($deckObj->leader->id);
        SetAssetKeyIdentifier(1, $gameName, 1, $leader);
        array_push($p1Leader, new Leader($leader));
      }
      if(isset($deckObj->base)) {
        $base = UUIDLookup($deckObj->base->id);
        SetAssetKeyIdentifier(1, $gameName, 2, $base);
        array_push($p1Base, new Base($base));
      }
      $deck = $deckObj->deck ?? [];
      if($deck != null) {
        for($i=0; $i<count($deck); ++$i) {
          $cardID = UUIDLookup($deck[$i]->id);
          for($j=0; $j<$deck[$i]->count; ++$j) {
            array_push($p1MainDeck, new MainDeck($cardID));
          }
        }
      }
      $sideboard = $deckObj->sideboard ?? [];
      if($sideboard != null) {
        for($i=0; $i<count($sideboard); ++$i) {
          $cardID = UUIDLookup($sideboard[$i]->id);
          for($j=0; $j<$sideboard[$i]->count; ++$j) {
            array_push($p1Sideboard, new Sideboard($cardID));
          }
        }
      }
    }
  } else {
    SaveAssetOwnership(1, $gameName, $userID, $assetSource, $assetSourceID);//assetType 1 = Deck
  }

  WriteGamestate();

  $params = "?gameName=" . $gameName . "&playerID=1" . "&folderPath=SWUDeck";
  header("location: ../NextTurn.php" . $params);

?>
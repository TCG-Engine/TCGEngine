<?php
  include_once './GamestateParser.php';
  include_once './ZoneAccessors.php';
  include_once './ZoneClasses.php';
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

  $deckLink = TryGet("deckLink", "");

  $gameName = GetGameCounter();

  InitializeGamestate();

  $userID = LoggedInUser();
  SaveAssetOwnership(1, $gameName, $userID);//assetType 1 = Deck

  if($deckLink != "") {
    if(str_contains($deckLink, "soulmastersdb.net")) {
      $decklinkArr = explode("gameName=", $deckLink);
      $decklinkArr = explode("&", $decklinkArr[1]);
      $assetSource = 1;
      $assetSourceID = trim($decklinkArr[0]);
      $deckLink = "https://soulmastersdb.net/TCGEngine/APIs/LoadDeck.php?deckID=$assetSourceID&format=json&folderPath=SoulMastersDB";
      $curl = curl_init();
      curl_setopt($curl, CURLOPT_URL, $deckLink);
      curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
      $apiDeck = curl_exec($curl);
      $apiInfo = curl_getinfo($curl);
      $errorMessage = curl_error($curl);
      curl_close($curl);
      $json = $apiDeck;
    }

    $deckObj = json_decode($json);
    $commanders = $deckObj->Commanders ?? [];
    if(count($commanders) > 0) {
      SetAssetKeyIdentifier(1, $gameName, 1, $commanders[0]->id);
    }
    for($i=0; $i<count($commanders); ++$i) {
      $commanderID = $commanders[$i]->id;
      array_push($p1Commander, new Commander($commanderID));
    }
    
    $deck = $deckObj->deck;
    for($i=0; $i<count($deck); ++$i) {
      $cardID = $deck[$i]->id;
      for($j=0; $j<$deck[$i]->count; ++$j) {
        array_push($p1MainDeck, new MainDeck($cardID));
      }
    }

    $reserves = $deckObj->reserves ?? [];
    for($i=0; $i<count($reserves); ++$i) {
      $reserveID = $reserves[$i]->id;
      for($j=0; $j<$reserves[$i]->count; ++$j) {
      array_push($p1ReserveDeck, new ReserveDeck($reserveID));
      }
    }
  }
  
  //Set up the list of cards to choose from
  for ($i = 1; $i <= 30; $i++) {
    $cardId = sprintf("SM-SD-01-%03d", $i);
    echo(CardName($cardId) . " " . CardType($cardId) . "<BR>");
    if(CardType($cardId) == "C") {
      array_push($p1Commanders, new Commanders($cardId));
    } else if(CardType($cardId) == "R") {
      array_push($p1Reserves, new Reserves($cardId));
    } else {
      array_push($p1Cards, new Cards($cardId));
    }
  }

  WriteGamestate();

  $params = "?gameName=" . $gameName . "&playerID=1" . "&folderPath=SoulMastersDB";
  header("location: ../NextTurn.php" . $params);

?>
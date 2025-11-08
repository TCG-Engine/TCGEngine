<?php

error_reporting(E_ALL);
ob_start();

include_once './Core/CoreZoneModifiers.php';
include_once "./Core/NetworkingLibraries.php";
include_once "./Core/HTTPLibraries.php";
include_once "./AccountFiles/AccountSessionAPI.php";
include_once "./AccountFiles/AccountDatabaseAPI.php";
include_once "./Database/ConnectionManager.php";

//We should always have a player ID as a URL parameter
$gameName = TryGET("gameName", "");
if ($gameName == "" || !IsGameNameValid($gameName)) {
  echo ("Invalid game name.");
  exit;
}
$playerID = $_GET["playerID"];
$authKey = $_GET["authKey"];
$folderPath = $_GET["folderPath"];

//We should also have some information on the type of command
$inputMode = $_GET["mode"];
$mode = $inputMode;
$buttonInput = $_GET["buttonInput"] ?? ""; //The player that is the target of the command - e.g. for changing health total
$cardID = $_GET["cardID"] ?? "";
$chkCount = $_GET["chkCount"] ?? 0;
$chkInput = [];
for ($i = 0; $i < $chkCount; ++$i) {
  $chk = $_GET[("chk" . $i)] ?? "";
  if ($chk != "") $chkInput[] = $chk;
}
$inputText = $_GET["inputText"] ?? "";

//First we need to parse the game state from the file
include "./" . $folderPath . "/GeneratedCode/GeneratedCardDictionaries.php";
include "./" . $folderPath . "/GamestateParser.php";
include "./" . $folderPath . "/ZoneAccessors.php";
include "./" . $folderPath . "/ZoneClasses.php";

if(GetEditAuth() == "AssetOwner") {
  
  //Quit if user is not logged in
  if(!IsUserLoggedIn()) {
    if (isset($_COOKIE["rememberMeToken"])) {
      include_once './Assets/patreon-php-master/src/OAuth.php';
      include_once './Assets/patreon-php-master/src/PatreonLibraries.php';
      include_once './Assets/patreon-php-master/src/API.php';
      include_once './Assets/patreon-php-master/src/PatreonDictionary.php';
      include_once './Database/functions.inc.php';
      include_once './Database/dbh.inc.php';
      loginFromCookie();
    }
    if(!IsUserLoggedIn()) {
      echo("You must be logged in to edit this asset.");
      exit;
    }
  }
  $loggedInUser = LoggedInUser();
  $assetData = LoadAssetData(1, $gameName);
  $assetOwner = $assetData["assetOwner"];
  $assetVisibility = $assetData["assetVisibility"];
  if($loggedInUser != $assetOwner) { //Owner can always edit
    if($assetVisibility >= 1000) {
      $userData = LoadUserDataFromId($loggedInUser);
      if($userData['teamID'] == null || $userData['teamID'] != $assetVisibility - 1000) {
        echo("You must be on the correct team to edit this asset.");
        exit;
      }
    } else {
      echo("You must own this asset to edit it.");
      exit;
    }
  }
}

ParseGamestate("./" . $folderPath . "/");

function CardExists($mzid) {
  $zone = &GetZone($mzid);
  $mzArr = explode("-", $mzid);
  return $mzArr[1] < count($zone);
}

switch($mode) {
  case 10000://Execute Zone Macro
    $macro = $buttonInput;
    $zone = &GetZone($inputText);
    switch($macro) {
      case "Shuffle":
        Shuffle($zone);
        break;
      default: break;
    }
    break;
  case 10001://Execute card widget
    $inpArr = explode("!", $cardID);
    $actionCard = $inpArr[0];
    $widgetType = $inpArr[1];
    $action = $inpArr[2];
    if($widgetType == "CustomInput") {
      CustomWidgetInput($playerID, $actionCard, $action);
      break;
    }
    switch($action) {
      case "-1": case "+1":
        if(!CardExists($actionCard)) break;
        $card = GetZoneObject($actionCard);
        $card->$widgetType += intval($action);
        break;
      case "Notes":
        if(!CardExists($actionCard)) break;
        $noteText = str_replace(' ', '_', $inpArr[3]);
        $card = GetZoneObject($actionCard);
        $cardID = $card->CardID;
        //Search for the card in the CardNotes zone
        $card = SearchZoneForCard("myCardNotes", $card->CardID, $playerID);
        if($card != null) {
          $card->Notes = $noteText;
        } else {
          MZAddZone($playerID, "myCardNotes", $cardID);
          $card = SearchZoneForCard("myCardNotes", $cardID, $playerID);
          $card->Notes = $noteText;
        }
        break;
      default:
        if(!CardExists($actionCard)) break;
        $card = GetZoneObject($actionCard);
        if($card->$widgetType == $action) $card->$widgetType = "-";
        else $card->$widgetType = $action;
        break;
    }
    break;
  case 10002://Execute click action
    $inpArr = explode("!", $cardID);
    $actionCard = $inpArr[0];
    $action = $inpArr[1];
    $parameterArr = explode(",", $inpArr[2]);
    if(!CardExists($actionCard)) break;
    $card = GetZoneObject($actionCard);
    switch($action) {
      case "Move":
        $card->Remove();
        $destination = $parameterArr[0];
        MZAddZone($playerID, $destination, $card->CardID);
        break;
      case "Add":
        $destination = $parameterArr[0];
        MZAddZone($playerID, $destination, $card->CardID);
        break;
      case "Remove":
        $card->Remove();
        break;
      case "Swap":
        $destination = $parameterArr[0];
        MZClearZone($playerID, $destination);
        MZAddZone($playerID, $destination, $card->CardID);
        break;
      case "FSM":
        ActionMap($actionCard);
        break;
      default: break;
    }
    break;
  case 10003://Version Changed
    $version = $cardID;
    $versions = &GetZone("myVersions");
    if($version == "current") { 
      //Do nothing, we should already be on current
    }
    else if($version == "new") {
      $zones = Versions::GetSerializedZones();
      AddVersions($playerID, $zones);
    } else {
      //Switch to a different version
      if($folderPath == "SoulMastersDB") {
        SoulMastersSwitchVersion($version);
        break;
      }
      $versionNum = intval($version);
      $copyFrom = $versions[$versionNum];
      $zones = explode("<v0>", $copyFrom->Version);
      if(count($zones) > 0) {
        $data = explode("<v1>", $zones[0]);
        if(count($data) > 0) {
          $zone = &GetZone("myLeader");
          $zone = [];
          array_push($zone, new Leader($data[0]));
        }
      }
      if(count($zones) > 1) {
        $data = explode("<v1>", $zones[1]);
        if(count($data) > 0) {
          $zone = &GetZone("myBase");
          $zone = [];
          for($i=0; $i<count($data); ++$i) {
            array_push($zone, new Base($data[$i]));
          }
        }
      }
      if(count($zones) > 2) {
        $data = explode("<v1>", $zones[2]);
        if(count($data) > 0) {
          $zone = &GetZone("myMainDeck");
          $zone = [];
          for($i=0; $i<count($data); ++$i) {
            array_push($zone, new MainDeck($data[$i]));
          }
        }
      }
      if(count($zones) > 3) {
        $data = explode("<v1>", $zones[3]);
        if(count($data) > 0) {
          $zone = &GetZone("mySideboard");
          $zone = [];
          for($i=0; $i<count($data); ++$i) {
            array_push($zone, new Sideboard($data[$i]));
          }
        }
      }
    }
    break;
  case 10014://Manual mode drag and drop
    $inpArr = explode("!", $cardID);
    $moveCard = $inpArr[0];
    $destination = $inpArr[1];
    if(!CardExists($moveCard)) break;
    $card = GetZoneObject($moveCard);
    if($card->DragMode() != "Clone") $card->Remove();
    MZAddZone($playerID, $destination, $card->CardID);
    break;
  default: break;
}

++$updateNumber;
WriteGamestate("./" . $folderPath . "/");
GamestateUpdated($gameName);

exit;

$otherPlayer = $currentPlayer == 1 ? 2 : 1;
$skipWriteGamestate = false;
$mainPlayerGamestateStillBuilt = 0;
$makeCheckpoint = 0;
$makeBlockBackup = 0;
$MakeStartTurnBackup = false;
$MakeStartGameBackup = false;
$targetAuth = ($playerID == 1 ? $p1Key : $p2Key);
$conceded = false;
$randomSeeded = false;

if(!IsReplay()) {
  if (($playerID == 1 || $playerID == 2) && $authKey == "") {
    if (isset($_COOKIE["lastAuthKey"])) $authKey = $_COOKIE["lastAuthKey"];
  }
  if ($playerID != 3 && $authKey != $targetAuth) { echo("Invalid auth key"); exit; }
  if ($playerID == 3 && !IsModeAllowedForSpectators($mode)) ExitProcessInput();
  if (!IsModeAsync($mode) && $currentPlayer != $playerID) {
    $currentTime = round(microtime(true) * 1000);
    SetCachePiece($gameName, 2, $currentTime);
    SetCachePiece($gameName, 3, $currentTime);
    ExitProcessInput();
  }
}

$afterResolveEffects = [];

$animations = [];
$events = [];//Clear events each time so it's only updated ones that get sent

if ((IsPatron(1) || IsPatron(2)) && !IsReplay()) {
  $commandFile = fopen("./Games/" . $gameName . "/commandfile.txt", "a");
  fwrite($commandFile, $playerID . " " . $mode . " " . $buttonInput . " " . $cardID . " " . $chkCount . " " . implode("|", $chkInput) . "\r\n");
  fclose($commandFile);
}

if($initiativeTaken > 2 && $mode != 99 && $mode != 34 && !IsModeAsync($mode)) $initiativeTaken = 0;

//Now we can process the command
ProcessInput($playerID, $mode, $buttonInput, $cardID, $chkCount, $chkInput, false, $inputText);

ProcessMacros();
if ($inGameStatus == $GameStatus_Rematch) {
  $origDeck = "./Games/" . $gameName . "/p1DeckOrig.txt";
  if (file_exists($origDeck)) copy($origDeck, "./Games/" . $gameName . "/p1Deck.txt");
  $origDeck = "./Games/" . $gameName . "/p2DeckOrig.txt";
  if (file_exists($origDeck)) copy($origDeck, "./Games/" . $gameName . "/p2Deck.txt");
  include "MenuFiles/ParseGamefile.php";
  include "MenuFiles/WriteGamefile.php";
  $gameStatus = (IsPlayerAI(2) ? $MGS_ReadyToStart : $MGS_ChooseFirstPlayer);
  SetCachePiece($gameName, 14, $gameStatus);
  $firstPlayer = 1;
  $firstPlayerChooser = ($winner == 1 ? 2 : 1);
  $p1SideboardSubmitted = "0";
  $p2SideboardSubmitted = (IsPlayerAI(2) ? "1" : "0");
  WriteLog("Player $firstPlayerChooser lost and will choose first player for the rematch.");
  WriteGameFile();
  $turn[0] = "REMATCH";
  include "WriteGamestate.php";
  $currentTime = round(microtime(true) * 1000);
  SetCachePiece($gameName, 2, $currentTime);
  SetCachePiece($gameName, 3, $currentTime);
  GamestateUpdated($gameName);
  exit;
} else if ($winner != 0 && $turn[0] != "YESNO") {
  $inGameStatus = $GameStatus_Over;
  $turn[0] = "OVER";
  $currentPlayer = 1;
}

CacheCombatResult();
CombatDummyAI(); //Only does anything if applicable
//EncounterAI();

if (!IsGameOver()) {
  if ($playerID == 1) $p1TotalTime += time() - intval($lastUpdateTime);
  else if ($playerID == 2) $p2TotalTime += time() - intval($lastUpdateTime);
  $lastUpdateTime = time();
}

//Now write out the game state
if (!$skipWriteGamestate) {
  //if($mainPlayerGamestateStillBuilt) UpdateMainPlayerGamestate();
  //else UpdateGameState(1);
  if(!IsModeAsync($mode))
  {
    if(GetCachePiece($gameName, 12) == "1") WriteLog("Current player is active again.");
    SetCachePiece($gameName, 12, "0");
    $currentPlayerActivity = 0;
  }
  DoGamestateUpdate();
  include "WriteGamestate.php";
}

if ($makeCheckpoint) MakeGamestateBackup();
if ($makeBlockBackup) MakeGamestateBackup("preBlockBackup.txt");
if ($MakeStartTurnBackup) MakeStartTurnBackup();
if ($MakeStartGameBackup) MakeGamestateBackup("origGamestate.txt");

GamestateUpdated($gameName);

ExitProcessInput();

function SoulMastersSwitchVersion($version) {
  //Switch to a different version
  $versions = &GetZone("myVersions");
  $versionNum = intval($version);
  $copyFrom = $versions[$versionNum];
  $zones = explode("<v0>", $copyFrom->Version);
  if (count($zones) > 0) {
    $data = explode("<v1>", $zones[0]);
    if (count($data) > 0) {
      $zone = &GetZone("myCommander");
      $zone = [];
      for ($i = 0; $i < count($data); ++$i) {
        if($data[$i] != "") array_push($zone, new Commander($data[$i]));
      }
    }
  }
  if (count($zones) > 1) {
    $data = explode("<v1>", $zones[1]);
    if (count($data) > 0) {
      $zone = &GetZone("myReserveDeck");
      $zone = [];
      for ($i = 0; $i < count($data); ++$i) {
        if($data[$i] != "") array_push($zone, new ReserveDeck($data[$i]));
      }
    }
  }
  if (count($zones) > 2) {
    $data = explode("<v1>", $zones[2]);
    if (count($data) > 0) {
      $zone = &GetZone("myMainDeck");
      $zone = [];
      for ($i = 0; $i < count($data); ++$i) {
        if($data[$i] != "") array_push($zone, new MainDeck($data[$i]));
      }
    }
  }
}
<?php

error_reporting(E_ALL);
ob_start();

// Limit script execution time to 1 second to avoid long-running requests
@set_time_limit(1);
@ini_set('max_execution_time', '1');

include_once './Core/CoreZoneModifiers.php';
include_once './Core/EngineActionRunner.php';
include_once "./Core/NetworkingLibraries.php";
include_once "./Core/HTTPLibraries.php";
include_once "./Core/ViewerIdentity.php";
include_once "./AccountFiles/AccountSessionAPI.php";
include_once "./AccountFiles/AccountDatabaseAPI.php";
include_once "./Database/ConnectionManager.php";

$processInputResponseFormat = strtolower(strval($_GET["responseFormat"] ?? ""));

function ProcessInputWantsJsonResponse() {
  global $processInputResponseFormat;
  return $processInputResponseFormat === "json";
}

function ProcessInputBuildJsonPayload($success, $message, $extra = []) {
  $payload = [
    "success" => (bool)$success,
    "message" => strval($message),
  ];
  foreach ($extra as $key => $value) {
    $payload[$key] = $value;
  }
  return $payload;
}

function ProcessInputReply($success, $message, $extra = []) {
  if (ProcessInputWantsJsonResponse()) {
    if (!headers_sent()) header("Content-Type: application/json");
    echo(json_encode(ProcessInputBuildJsonPayload($success, $message, $extra), JSON_UNESCAPED_SLASHES));
  } else if ($message !== "") {
    echo($message);
  }
  exit;
}

function ProcessInputPlaybackStateForResponse() {
  if (function_exists("MatchReplayPlaybackState")) return MatchReplayPlaybackState();
  return null;
}

//We should always have a player ID as a URL parameter
$gameName = TryGET("gameName", "");
if ($gameName == "" || !IsGameNameValid($gameName)) {
  ProcessInputReply(false, "Invalid game name.");
}
$viewerInfo = NormalizeViewerIdentity($_GET["playerID"] ?? "");
if ($viewerInfo['viewerID'] === '') {
  ProcessInputReply(false, "Invalid player ID.");
}
$playerID = $viewerInfo['viewerID'];
$authKey = $_GET["authKey"] ?? "";
$folderPath = $_GET["folderPath"] ?? "";

if (!$viewerInfo['isSpectator'] && !SimGameValidateSeatAuth($folderPath, $gameName, $playerID, $authKey)) {
  ProcessInputReply(false, "Invalid auth key");
}

//We should also have some information on the type of command
$inputMode = $_GET["mode"];
// 'DECISION' is a string mode sent by the client for interactive DQ responses (YESNO, CHOOSEZONE, etc.).
// The engine normalizes mode via intval(), which converts 'DECISION' to 0 (unhandled).
// Remap to 100 here: mode 100 pops index 0 and calls ExecuteStaticMethods, which is correct
// as long as interactive decisions are always at index 0 (enforced by block priorities).
$mode = ($inputMode === 'DECISION') ? 100 : $inputMode;
$buttonInput = $_GET["buttonInput"] ?? ""; //The player that is the target of the command - e.g. for changing health total
$cardID = $_GET["cardID"] ?? "";
$chkCount = $_GET["chkCount"] ?? 0;
$chkInput = [];
for ($i = 0; $i < $chkCount; ++$i) {
  $chk = $_GET[("chk" . $i)] ?? "";
  if ($chk != "") $chkInput[] = $chk;
}
$inputText = $_GET["inputText"] ?? "";

$botControllerStepLock = null;
if (intval($mode) === 10017) {
  @set_time_limit(15);
  @ini_set('max_execution_time', '15');
  $botControllerStepLock = AcquireBotControllerStepLock($folderPath, $gameName);
  if ($botControllerStepLock === null) {
    ProcessInputReply(false, "Another bot step is already in progress.", [
      "botStepApplied" => false,
      "botStepRetryable" => true,
    ]);
  }
}

//First we need to load the root runtime
EngineLoadRootRuntime($folderPath);

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
  if(!is_array($assetData)) {
    echo("This deck does not have an ownership record. Please create a new deck from the deck builder.");
    exit;
  }
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

global $gameName;
$gameName = strval($gameName);
ParseGamestate("./" . $folderPath . "/");

if ($viewerInfo['isSpectator']) {
  ProcessInputReply(false, "Spectators are view-only.", [
    "playbackState" => ProcessInputPlaybackStateForResponse(),
  ]);
}

$actionResult = EngineExecuteLoadedAction([
  'playerID' => $playerID,
  'mode' => $mode,
  'buttonInput' => $buttonInput,
  'cardID' => $cardID,
  'chkInput' => $chkInput,
  'inputText' => $inputText,
], $folderPath, $gameName, [
  'updateCache' => true,
  'versionName' => $_GET["versionName"] ?? $inputText,
  'createdBy' => function_exists('LoggedInUser') && IsUserLoggedIn() ? strval(LoggedInUser()) : 'anonymous',
]);

if (ProcessInputWantsJsonResponse()) {
  $jsonExtra = [
    "playbackState" => ProcessInputPlaybackStateForResponse(),
  ];
  if (array_key_exists('botStepApplied', $actionResult)) {
    $jsonExtra["botStepApplied"] = !empty($actionResult['botStepApplied']);
    $jsonExtra["botStepRetryable"] = !empty($actionResult['botStepRetryable']);
    $jsonExtra["botController"] = $actionResult['botControllerState'] ?? BuildBotControllerClientState($folderPath, $gameName);
  }
  ProcessInputReply(!empty($actionResult['success']), $actionResult['message'] ?? "", $jsonExtra);
}

if (!empty($actionResult['message'])) echo($actionResult['message']);

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
  if (!$viewerInfo['isSpectator'] && $authKey != $targetAuth) { echo("Invalid auth key"); exit; }
  if ($viewerInfo['isSpectator'] && !IsModeAllowedForSpectators($mode)) ExitProcessInput();
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

<?php
include '../Core/UILibraries.php';
include '../Core/NetworkingLibraries.php';
include './GamestateParser.php';
include './ZoneAccessors.php';
include './ZoneClasses.php';
$gameName = TryGet("gameName");
$playerID = TryGet("playerID");
$lastUpdate = TryGet("lastUpdate", 0);
$count = 0;
while(!CheckUpdate($gameName, $lastUpdate) && $count < 100) {
  usleep(100000); //100 milliseconds
  ++$count;
}
ParseGamestate();
SetCachePiece($gameName, 1, $updateNumber);
echo($updateNumber . "<~>");
include_once '../AccountFiles/AccountSessionAPI.php';
include_once '../AccountFiles/AccountDatabaseAPI.php';
include_once '../Database/ConnectionManager.php';
include_once '../Database/functions.inc.php';
include_once '../Assets/patreon-php-master/src/PatreonLibraries.php';
include_once '../Assets/patreon-php-master/src/PatreonDictionary.php';
$assetData = LoadAssetData(1, $gameName);
if($assetData["assetVisibility"] == 0 || $assetData["assetVisibility"] > 1000) {
  if (!IsUserLoggedIn()) {
    if (isset($_COOKIE["rememberMeToken"])) {
      loginFromCookie();
    }
  }
  if(!IsUserLoggedIn()) {
    echo("You must be logged in to view this asset.");
    exit;
  }
  $loggedInUser = LoggedInUser();
  $assetOwner = $assetData["assetOwner"];
  if($loggedInUser != $assetOwner) {
    if($assetData["assetVisibility"] > 1000000) {
      if(!IsPatron($assetData["assetVisibility"])){
        echo("You must be a patron to view this.");
        exit;
      }
    } else if($assetData["assetVisibility"] > 1000) {
      $userData = LoadUserDataFromId($loggedInUser);
      if($userData["teamID"] == null || $assetData["assetVisibility"] != $userData["teamID"]+1000) {
        echo("You must be on this team to view this.");
        exit;
      }
    } else {
      echo("You must own this asset view it.");
      exit;
    }
  }
}
  $arr = &GetCommander(1);
  for($i=0; $i<count($arr); ++$i) {
    if($i > 0) echo("<|>");
    $obj = $arr[$i];
    $displayID = isset($obj->CardID) ? $obj->CardID : "-";
    if($playerID == 1) echo(ClientRenderedCard($displayID, cardJSON:json_encode($obj)));
    else echo(ClientRenderedCard("CardBack"));
  }
echo("<~>");
  $arr = &GetReserveDeck(1);
  for($i=0; $i<count($arr); ++$i) {
    if($i > 0) echo("<|>");
    $obj = $arr[$i];
    $displayID = isset($obj->CardID) ? $obj->CardID : "-";
    if($playerID == 1) echo(ClientRenderedCard($displayID, cardJSON:json_encode($obj)));
    else echo(ClientRenderedCard("CardBack"));
  }
echo("<~>");
  $arr = &GetMainDeck(1);
  for($i=0; $i<count($arr); ++$i) {
    if($i > 0) echo("<|>");
    $obj = $arr[$i];
    $displayID = isset($obj->CardID) ? $obj->CardID : "-";
    if($playerID == 1) echo(ClientRenderedCard($displayID, cardJSON:json_encode($obj)));
    else echo(ClientRenderedCard("CardBack"));
  }
echo("<~>");
echo("<~>");
  $arr = &GetCommanders(1);
  for($i=0; $i<count($arr); ++$i) {
    if($i > 0) echo("<|>");
    $obj = $arr[$i];
    $displayID = isset($obj->CardID) ? $obj->CardID : "-";
    if($playerID == 1) echo(ClientRenderedCard($displayID, cardJSON:json_encode($obj)));
    else echo(ClientRenderedCard("CardBack"));
  }
echo("<~>");
  $arr = &GetReserves(1);
  for($i=0; $i<count($arr); ++$i) {
    if($i > 0) echo("<|>");
    $obj = $arr[$i];
    $displayID = isset($obj->CardID) ? $obj->CardID : "-";
    if($playerID == 1) echo(ClientRenderedCard($displayID, cardJSON:json_encode($obj)));
    else echo(ClientRenderedCard("CardBack"));
  }
echo("<~>");
  $arr = &GetCards(1);
  for($i=0; $i<count($arr); ++$i) {
    if($i > 0) echo("<|>");
    $obj = $arr[$i];
    $displayID = isset($obj->CardID) ? $obj->CardID : "-";
    if($playerID == 1) echo(ClientRenderedCard($displayID, cardJSON:json_encode($obj)));
    else echo(ClientRenderedCard("CardBack"));
  }
echo("<~>");
echo("<~>");
echo("<~>");
  $arr = &GetSort(1);
  echo($arr[0]->Value);
echo("<~>");
  $arr = &GetCardNotes(1);
  for($i=0; $i<count($arr); ++$i) {
    if($i > 0) echo("<|>");
    $obj = $arr[$i];
    $displayID = isset($obj->CardID) ? $obj->CardID : "-";
    if($playerID == 1) echo(ClientRenderedCard($displayID, cardJSON:json_encode($obj)));
    else echo(ClientRenderedCard("CardBack"));
  }
echo("<~>");
  $arr = &GetVersions(1);
  for($i=0; $i<count($arr); ++$i) {
    if($i > 0) echo("<|>");
    $obj = $arr[$i];
    $displayID = isset($obj->CardID) ? $obj->CardID : "-";
    if($playerID == 1) echo(ClientRenderedCard($displayID, cardJSON:json_encode($obj)));
    else echo(ClientRenderedCard("CardBack"));
  }

echo("<~>");
  $arr = &GetCommander(2);
  for($i=0; $i<count($arr); ++$i) {
    if($i > 0) echo("<|>");
    $obj = $arr[$i];
    $displayID = isset($obj->CardID) ? $obj->CardID : "-";
    if($playerID == 2) echo(ClientRenderedCard($displayID, cardJSON:json_encode($obj)));
    else echo(ClientRenderedCard("CardBack"));
  }
echo("<~>");
  $arr = &GetReserveDeck(2);
  for($i=0; $i<count($arr); ++$i) {
    if($i > 0) echo("<|>");
    $obj = $arr[$i];
    $displayID = isset($obj->CardID) ? $obj->CardID : "-";
    if($playerID == 2) echo(ClientRenderedCard($displayID, cardJSON:json_encode($obj)));
    else echo(ClientRenderedCard("CardBack"));
  }
echo("<~>");
  $arr = &GetMainDeck(2);
  for($i=0; $i<count($arr); ++$i) {
    if($i > 0) echo("<|>");
    $obj = $arr[$i];
    $displayID = isset($obj->CardID) ? $obj->CardID : "-";
    if($playerID == 2) echo(ClientRenderedCard($displayID, cardJSON:json_encode($obj)));
    else echo(ClientRenderedCard("CardBack"));
  }
echo("<~>");
echo("<~>");
  $arr = &GetCommanders(2);
  for($i=0; $i<count($arr); ++$i) {
    if($i > 0) echo("<|>");
    $obj = $arr[$i];
    $displayID = isset($obj->CardID) ? $obj->CardID : "-";
    if($playerID == 2) echo(ClientRenderedCard($displayID, cardJSON:json_encode($obj)));
    else echo(ClientRenderedCard("CardBack"));
  }
echo("<~>");
  $arr = &GetReserves(2);
  for($i=0; $i<count($arr); ++$i) {
    if($i > 0) echo("<|>");
    $obj = $arr[$i];
    $displayID = isset($obj->CardID) ? $obj->CardID : "-";
    if($playerID == 2) echo(ClientRenderedCard($displayID, cardJSON:json_encode($obj)));
    else echo(ClientRenderedCard("CardBack"));
  }
echo("<~>");
  $arr = &GetCards(2);
  for($i=0; $i<count($arr); ++$i) {
    if($i > 0) echo("<|>");
    $obj = $arr[$i];
    $displayID = isset($obj->CardID) ? $obj->CardID : "-";
    if($playerID == 2) echo(ClientRenderedCard($displayID, cardJSON:json_encode($obj)));
    else echo(ClientRenderedCard("CardBack"));
  }
echo("<~>");
echo("<~>");
echo("<~>");
  $arr = &GetSort(2);
  echo($arr[0]->Value);
echo("<~>");
  $arr = &GetCardNotes(2);
  for($i=0; $i<count($arr); ++$i) {
    if($i > 0) echo("<|>");
    $obj = $arr[$i];
    $displayID = isset($obj->CardID) ? $obj->CardID : "-";
    if($playerID == 2) echo(ClientRenderedCard($displayID, cardJSON:json_encode($obj)));
    else echo(ClientRenderedCard("CardBack"));
  }
echo("<~>");
  $arr = &GetVersions(2);
  for($i=0; $i<count($arr); ++$i) {
    if($i > 0) echo("<|>");
    $obj = $arr[$i];
    $displayID = isset($obj->CardID) ? $obj->CardID : "-";
    if($playerID == 2) echo(ClientRenderedCard($displayID, cardJSON:json_encode($obj)));
    else echo(ClientRenderedCard("CardBack"));
  }

?>
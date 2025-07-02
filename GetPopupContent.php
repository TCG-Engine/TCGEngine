<?php

include './Core/NetworkingLibraries.php';
include './Core/HTTPLibraries.php';

$gameName = $_GET["gameName"];
if (!IsGameNameValid($gameName)) {
  echo ("Invalid game name.");
  exit;
}
$playerID = $_GET["playerID"];
$authKey = TryGet("authKey", "");
$folderPath = TryGet("folderPath", "");
$popupType = $_GET["popupType"];
$chainLinkIndex = TryGet("chainLinkIndex", "");

ob_start();
include './Core/UILibraries.php';
include './' . $folderPath . '/GamestateParser.php';
include './' . $folderPath . '/ZoneAccessors.php';
include './' . $folderPath . '/ZoneClasses.php';
ob_end_clean();

session_start();

ParseGamestate("./" . $folderPath . "/");
$cardSize = 120;
$params = explode("-", $popupType);
$popupType = $params[0];
switch ($popupType) {
  default://Zone popups can be the default
    $arr = &GetZone($popupType);
    $popup = (count($arr) > 0 ? implode(",", $arr[0]->GetMacros()) : "") . "</>";
    for($i=0; $i<count($arr); ++$i) {
      if($i > 0) $popup .= "<|>";
      $obj = $arr[$i];
      $popup .= ClientRenderedCard($obj->CardID, cardJSON: json_encode($obj));
    }
    echo($popup);
    break;
}


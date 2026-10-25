<?php

include './Core/NetworkingLibraries.php';
include './Core/HTTPLibraries.php';
include './Core/CoreZoneModifiers.php';
include './Core/ViewerIdentity.php';
include './Core/PopupZoneVisibility.php';

$gameName = $_GET["gameName"];
if (!IsGameNameValid($gameName)) {
  echo ("Invalid game name.");
  exit;
}
$folderPath = TryGet("folderPath", "");
$popupMaxSeats = SimGameMaxSeats($folderPath);
$viewerInfo = NormalizeViewerIdentity($_GET["playerID"] ?? "", $popupMaxSeats);
if ($viewerInfo['viewerID'] === '') {
  echo ("Invalid player.");
  exit;
}
$playerID = $_GET["playerID"];
$viewerPerspective = NormalizeViewerPerspective($viewerInfo, TryGet("viewerPerspective", ""), $popupMaxSeats);
$authKey = TryGet("authKey", "");
$popupType = $_GET["popupType"];
$chainLinkIndex = TryGet("chainLinkIndex", "");

if (!SimGameValidateViewerAuth($folderPath, $gameName, $viewerInfo, $authKey)) {
  echo("Invalid auth key.");
  exit;
}

ob_start();
include './Core/UILibraries.php';
include './' . $folderPath . '/GamestateParser.php';
include './' . $folderPath . '/ZoneAccessors.php';
include './' . $folderPath . '/ZoneClasses.php';
include './' . $folderPath . '/GeneratedCode/GeneratedCardDictionaries.php';
ob_end_clean();

session_start();

ParseGamestate("./" . $folderPath . "/");
$playerID = $viewerPerspective;
$cardSize = 120;
$params = explode("-", $popupType);
$popupType = $params[0];
// Who may see which zone: Core/PopupZoneVisibility.php (the schema's visibility, by owning seat).
$popupSpectatorCanSeeHands = function_exists('SimGameViewerCanSeeHands') && SimGameViewerCanSeeHands($folderPath, $gameName, $viewerInfo);
switch ($popupType) {
  default://Zone popups can be the default
    $arr = &GetZone($popupType);
    $shouldMaskContents = !PopupMayShowZone(strval($folderPath), strval($popupType), $viewerInfo, intval($viewerPerspective), $popupSpectatorCanSeeHands);
    $popup = ($shouldMaskContents || count($arr) === 0 ? "" : implode(",", $arr[0]->GetMacros())) . "</>";
    if ($shouldMaskContents) {
      $visibleCount = 0;
      for($i=0; $i<count($arr); ++$i) {
        if(!isset($arr[$i])) continue;
        ++$visibleCount;
        if($visibleCount > 1) $popup .= "<|>";
        $popup .= ClientRenderedCard("CardBack");
      }
      echo($popup);
      break;
    }
    for($i=0; $i<count($arr); ++$i) {
      if($i > 0) $popup .= "<|>";
      $obj = $arr[$i];
      ComputeVirtualProperties($obj);
      $popup .= ClientRenderedCard($obj->CardID, cardJSON: json_encode($obj));
    }
    echo($popup);
    break;
}


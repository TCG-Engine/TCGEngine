<?php

include './Core/NetworkingLibraries.php';
include './Core/HTTPLibraries.php';
include './Core/CoreZoneModifiers.php';
include './Core/GameAuth.php';
include './Core/ViewerIdentity.php';

$gameName = $_GET["gameName"];
if (!IsGameNameValid($gameName)) {
  echo ("Invalid game name.");
  exit;
}
$viewerInfo = NormalizeViewerIdentity($_GET["playerID"] ?? "");
if ($viewerInfo['viewerID'] === '') {
  echo ("Invalid player.");
  exit;
}
$playerID = $_GET["playerID"];
$viewerPerspective = NormalizeViewerPerspective($viewerInfo, TryGet("viewerPerspective", ""));
$authKey = TryGet("authKey", "");
$folderPath = TryGet("folderPath", "");
$popupType = $_GET["popupType"];
$chainLinkIndex = TryGet("chainLinkIndex", "");

if (!$viewerInfo['isSpectator'] && !SimGameValidateSeatAuth($folderPath, $gameName, $viewerInfo['viewerSeat'], $authKey)) {
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
$hiddenZonePrefixes = [
  'Hand',
  'Memory',
  'Material',
  'TempZone',
  'DecisionQueue',
  'Versions',
];
switch ($popupType) {
  default://Zone popups can be the default
    $arr = &GetZone($popupType);
    $isOpponentZone = strpos($popupType, "their") === 0;
    $zoneSuffix = preg_replace('/^(my|their)/', '', $popupType);
    $isHiddenZone = in_array($zoneSuffix, $hiddenZonePrefixes, true);
    $shouldMaskContents = $isHiddenZone && ($viewerInfo['isSpectator'] || $isOpponentZone);
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


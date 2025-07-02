<?php

require_once '../vendor/autoload.php';
include_once './GamestateParser.php';
include_once './ZoneAccessors.php';
include_once './ZoneClasses.php';
include_once './GeneratedCode/GeneratedCardDictionaries.php';
include_once '../Core/HTTPLibraries.php';
include_once '../Database/ConnectionManager.php';
include_once '../AccountFiles/AccountDatabaseAPI.php';
include_once '../AccountFiles/AccountSessionAPI.php';
include_once '../Assets/patreon-php-master/src/PatreonLibraries.php';
include_once '../Assets/patreon-php-master/src/PatreonDictionary.php';


$gameName = TryGet("gameName", "");

if($gameName == "") {
  echo("You must provide a game name to generate this image.");
  exit;
}

if(!IsUserLoggedIn()) {
  echo("You must be logged in to generate this image.");
  exit;
}
$loggedInUser = LoggedInUser();
$assetData = LoadAssetData(1, $gameName);
if($assetData == null) {
  echo("This game asset does not exist.");
  exit;
}
$assetOwner = $assetData["assetOwner"];
if($loggedInUser != $assetOwner) {
  if($assetData["assetVisibility"] > 10000) {
    if(!IsPatron($assetData["assetVisibility"])){
      echo("You must be a patron to generate this image.");
      exit;
    }
  } else if($assetData["assetVisibility"] == 0) {
    echo("You must own this asset to generate this image.");
    exit;
  }
}

ParseGamestate();
$width = 800;
$height = 600;
$image = imagecreatetruecolor($width, $height);
$green = imagecolorallocate($image, 0, 255, 0);
imagefilledrectangle($image, 0, 0, $width, $height, $green);
header('Content-Type: image/jpeg');
imagejpeg($image);
imagedestroy($image);
exit;

header("Content-Type: image/webp");
readfile('./Games/' . $gameName . '/DeckImage.webp');

//$pdf->Write(0, 'Deck based on ' . $assetName . ' by ' . $ownerName, '', 0, 'L', true);

// Disclaimer
//$pdf->MultiCell(0, 0, "Document prepared at swustats.net. SWU Stats is fan made and is in no way affiliated with Disney or Fantasy Flight Games. Star Wars characters, cards, logos, and art are property of Disney and/or Fantasy Flight Games.", 0, 'L');

?>
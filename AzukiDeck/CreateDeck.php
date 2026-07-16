<?php

include_once './GamestateParser.php';
include_once './ZoneAccessors.php';
include_once './ZoneClasses.php';
include_once '../Core/CoreZoneModifiers.php';
include_once '../AzukiSim/GeneratedCode/GeneratedCardDictionaries.php';
include_once '../Core/HTTPLibraries.php';
include_once '../Database/ConnectionManager.php';
include_once '../AccountFiles/AccountDatabaseAPI.php';
include_once '../AccountFiles/AccountSessionAPI.php';
include_once '../AzukiSim/Custom/DeckImport.php';

if (!IsUserLoggedIn()) {
  header('location: /TCGEngine/SharedUI/Sites/AzukiSim/LoginPage.php?redirect=%2FTCGEngine%2FAzukiDeck%2F');
  exit();
}

$deckLink = trim((string)TryGet('deckLink', ''));
$resolved = null;
if ($deckLink !== '') {
  $resolved = AzukiResolveDeckInput($deckLink);
  if (!$resolved['success']) {
    header('location: ./index.php?error=' . rawurlencode($resolved['message']));
    exit();
  }
}

$gameName = GetGameCounter(__DIR__ . '/Games');
InitializeGamestate();
$userID = LoggedInUser();
$assetSource = null;
$assetSourceID = null;

if (!SaveAssetOwnership(1, $gameName, $userID, $assetSource, $assetSourceID)) {
  header('location: ./index.php?error=' . rawurlencode('Could not reserve an ownership record for this deck. Please try again.'));
  exit();
}

if ($deckLink !== '') {
  if ($resolved['leader'] !== '') {
    SetAssetKeyIdentifier(1, $gameName, 1, $resolved['leader']);
    $p1Leader[] = new Leader($resolved['leader']);
  }
  if ($resolved['gate'] !== '') {
    SetAssetKeyIdentifier(1, $gameName, 2, $resolved['gate']);
    $p1Gate[] = new Gate($resolved['gate']);
  }
  foreach ($resolved['mainDeck'] as $cardID) {
    $p1MainDeck[] = new MainDeck($cardID);
  }
  UpdateAssetName(1, $gameName, 'Imported Azuki Deck');
}

WriteGamestate();

$params = '?gameName=' . rawurlencode($gameName) . '&playerID=1&folderPath=AzukiDeck';
header('location: ../NextTurn.php' . $params);

?>

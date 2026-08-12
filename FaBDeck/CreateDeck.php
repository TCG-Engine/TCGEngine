<?php
include_once './GamestateParser.php';
include_once './ZoneAccessors.php';
include_once './ZoneClasses.php';
include_once '../Core/CoreZoneModifiers.php';
include_once '../FaBSim/GeneratedCode/GeneratedCardDictionaries.php';
include_once '../Core/HTTPLibraries.php';
include_once '../Database/ConnectionManager.php';
include_once '../AccountFiles/AccountDatabaseAPI.php';
include_once '../AccountFiles/AccountSessionAPI.php';
include_once '../FaBSim/Custom/DeckImport.php';
if (!IsUserLoggedIn()) { header('location: ../SharedUI/Sites/FaBSim/LoginPage.php'); exit(); }
$resolved = null; $deckInput = trim((string)TryGet('deckLink', ''));
if ($deckInput !== '') { $resolved = FaBResolveDeckInput($deckInput, LoggedInUser()); if (empty($resolved['success'])) exit($resolved['message']); }
$gameName = GetGameCounter(__DIR__ . '/Games'); InitializeGamestate(); $userID = LoggedInUser();
$assetSource=null; $assetSourceID=null;
if (!SaveAssetOwnership(1, $gameName, $userID, $assetSource, $assetSourceID, 'standard')) exit('Could not create FaB deck.');
if (function_exists('AssignFriendlyCode')) AssignFriendlyCode(1, $gameName);
if ($resolved) {
  if ($resolved['hero'] !== '') $p1Hero[] = new Hero($resolved['hero']);
  foreach ($resolved['weapons'] as $id) $p1Weapons[] = new Weapons($id);
  foreach ($resolved['equipment'] as $id) $p1Equipment[] = new Equipment($id);
  foreach ($resolved['mainDeck'] as $id) $p1MainDeck[] = new MainDeck($id);
  foreach ($resolved['inventory'] as $id) $p1Inventory[] = new Inventory($id);
}
WriteGamestate();
header('location: ../NextTurn.php?gameName=' . rawurlencode($gameName) . '&playerID=1&folderPath=FaBDeck');
?>

<?php

include_once './GamestateParser.php';
include_once './ZoneAccessors.php';
include_once './ZoneClasses.php';
include_once '../Core/CoreZoneModifiers.php';
include_once '../HellbreakSim/GeneratedCode/GeneratedCardDictionaries.php';
include_once '../Core/HTTPLibraries.php';
include_once '../Database/ConnectionManager.php';
include_once '../AccountFiles/AccountDatabaseAPI.php';
include_once '../AccountFiles/AccountSessionAPI.php';

if (!IsUserLoggedIn()) {
    header('location: ../SharedUI/Sites/HellbreakSim/LoginPage.php?redirect=' . rawurlencode('/TCGEngine/HellbreakDeck/CreateDeck.php'));
    exit();
}

$gameName = GetGameCounter(__DIR__ . '/Games');
InitializeGamestate();
$userID = LoggedInUser();
$assetSource = null;
$assetSourceID = null;

if (!SaveAssetOwnership(1, $gameName, $userID, $assetSource, $assetSourceID, 'standard')) {
    http_response_code(500);
    exit('Could not create the Hellbreak deck. Please try again.');
}

if (function_exists('AssignFriendlyCode')) AssignFriendlyCode(1, $gameName);
WriteGamestate();

$params = '?gameName=' . rawurlencode((string)$gameName) . '&playerID=1&folderPath=HellbreakDeck';
header('location: ../NextTurn.php' . $params);

?>

<?php
  include_once './GamestateParser.php';
  include_once './ZoneAccessors.php';
  include_once './ZoneClasses.php';
  include_once '../Core/CoreZoneModifiers.php';
  include_once '../SWUDeck/GeneratedCode/GeneratedCardDictionaries.php';
  include_once '../Core/HTTPLibraries.php';

  include_once '../Database/ConnectionManager.php';
  include_once '../AccountFiles/AccountDatabaseAPI.php';
  include_once '../AccountFiles/AccountSessionAPI.php';

  if(!IsUserLoggedIn()) {
    header("location: ../SharedUI/LoginPage.php");
    exit();
  }

  $gameName = 1;

  InitializeGamestate();

  WriteGamestate();

  $params = "?gameName=" . $gameName . "&playerID=1" . "&folderPath=SWUCardList";
  header("location: ../NextTurn.php" . $params);

?>
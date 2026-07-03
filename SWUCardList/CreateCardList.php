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

  // Reset zones, then seed the browseable card grid: every card lands in player 1's
  // "Cards" zone (the pane the SWUCardList page renders). Without this the game writes
  // out empty and the browser shows a blank grid.
  InitializeGamestate();

  global $p1Cards;
  foreach (GetAllCardIds() as $cardId) {
    $p1Cards[] = new Cards($cardId);
  }

  // WriteGamestate() fopen()s Games/<gameName>/Gamestate.txt and won't create the dir.
  if (!is_dir("./Games/$gameName")) {
    mkdir("./Games/$gameName", 0777, true);
  }

  WriteGamestate();

  $params = "?gameName=" . $gameName . "&playerID=1" . "&folderPath=SWUCardList";
  header("location: ../NextTurn.php" . $params);

?>
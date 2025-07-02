<?php

include_once './SWUDeck/GamestateParser.php';
include_once './SWUDeck/ZoneAccessors.php';
include_once './SWUDeck/ZoneClasses.php';
include_once './Database/ConnectionManager.php';
include_once './AccountFiles/AccountDatabaseAPI.php';

$conn = GetLocalMySQLConnection();
for($i=12192; $i<15927; ++$i) {
  $gameName = $i;
  $filepath = "./SWUDeck/Games/$gameName/";
  $filename = "./SWUDeck/Games/$gameName/Gamestate.txt";
  if (!file_exists($filepath) || !file_exists($filename)) {
    continue;
  }
  ParseGamestate("./SWUDeck/");
  $leaderZone = &GetLeader(1);
  if(count($leaderZone) > 0) {
    $leader = $leaderZone[0]->CardID;
    SetAssetKeyIdentifier(1, $gameName, 1, $leader, $conn);
  }
  $baseZone = &GetBase(1);
  if(count($baseZone) > 0) {
    $base = $baseZone[0]->CardID;
    SetAssetKeyIdentifier(1, $gameName, 2, $base, $conn);
  }
}


?>
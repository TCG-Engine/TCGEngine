<?php
  include_once './GamestateParser.php';
  include_once './ZoneAccessors.php';
  include_once './ZoneClasses.php';
  include_once './Overrides.php';
  include_once '../Core/CoreZoneModifiers.php';
  include_once '../Core/HTTPLibraries.php';
  include_once './GeneratedCode/GeneratedCardDictionaries.php';
  include_once '../Core/NetworkingLibraries.php';
  include_once '../Database/ConnectionManager.php';
  include_once '../AccountFiles/AccountDatabaseAPI.php';
  include_once '../AccountFiles/AccountSessionAPI.php';

  if(!IsUserLoggedIn()) {
    http_response_code(401);
    exit();
  }

  $gameName = TryGet("deckID", "");
  $playerID = intval(TryGet("playerID", 1));
  $versionIndex = intval(TryGet("versionIndex", -1));

  if($gameName == "" || $versionIndex < 0) {
    http_response_code(400);
    exit();
  }

  ParseGamestate();

  $versions = &GetVersions($playerID);
  if($versionIndex >= count($versions)) {
    http_response_code(400);
    exit();
  }

  array_splice($versions, $versionIndex, 1);

  // Re-sync mzIndex for remaining entries
  for($i = 0; $i < count($versions); ++$i) {
    $versions[$i]->mzIndex = $i;
  }

  ++$updateNumber;
  WriteGamestate();
  GamestateUpdated($gameName);
?>

<?php

  include_once '../Core/HTTPLibraries.php';
  include_once './AccountSessionAPI.php';
  include_once './AccountDatabaseAPI.php';
  include_once '../Database/ConnectionManager.php';
  include_once '../SWUDeck/Custom/DeckFormats.php';

  $response = new stdClass();

  if(!IsUserLoggedIn()) {
    $response->error = "You must be logged in to change the format of a deck";
    echo (json_encode($response));
    exit();
  }

  $assetType = TryGet("assetType", default: "");
  $format = TryGet("format", default: "");
  $assetID = TryGet("assetID", default: "");

  if($assetType == "" || $format == "" || $assetID == "") {
    $response->error = "Missing parameters";
    echo (json_encode($response));
    exit();
  }

  if (!array_key_exists($format, SWUDeckBuildableFormats())) {
    $response->error = "Unknown format";
    echo (json_encode($response));
    exit();
  }

  $userid = LoggedInUser();

  $asset = LoadAssetData($assetType, $assetID);
  if($asset["assetOwner"] != $userid) {
    $response->error = "You do not own this asset";
    echo (json_encode($response));
    exit();
  }

  $updateResult = UpdateAssetFormat($assetType, $assetID, $format);

  if ($updateResult) {
    $response->success = "Deck format updated successfully";
  } else {
    $response->error = "Failed to update deck format";
  }

  echo json_encode($response);

?>
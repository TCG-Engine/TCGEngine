<?php

  include_once '../Core/HTTPLibraries.php';
  include_once './AccountSessionAPI.php';
  include_once './AccountDatabaseAPI.php';
  include_once '../Database/ConnectionManager.php';

  $response = new stdClass();

  if(!IsUserLoggedIn()) {
    $response->error = "You must be logged in to change the name of a deck";
    echo (json_encode($response));
    exit();
  }

  $assetType = TryGet("assetType", default: "");
  $assetID = TryGet("assetID", default: "");
  $folderID = TryGet("folderID", default: "");

  if($assetType == "" || $assetID == "" || $folderID == "") {
    $response->error = "Missing parameters";
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

  $result = MoveAsset($assetType, $assetID, $folderID);

  if ($result) {
    $response->success = "Asset folder updated successfully";
  } else {
    $response->error = "Failed to update asset folder";
  }

  echo json_encode($response);

  function MoveAsset($assetType, $assetID, $folderID) {
    $conn = GetLocalMySQLConnection();
    $stmt = $conn->prepare("UPDATE ownership SET assetFolder = ? WHERE assetIdentifier = ? AND assetType = ?");
    $stmt->bind_param("iii", $folderID, $assetID, $assetType);
    $result = $stmt->execute();
    $stmt->close();
    $conn->close();
    return $result;
  }

?>
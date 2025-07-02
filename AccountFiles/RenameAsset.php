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
  $newName = TryGet("newName", default: "");
  $assetID = TryGet("assetID", default: "");

  if($assetType == "" || $newName == "" || $assetID == "") {
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

  $updateResult = UpdateAssetName($assetType, $assetID, $newName);

  if ($updateResult) {
    $response->success = "Asset name updated successfully";
  } else {
    $response->error = "Failed to update asset name";
  }

  echo json_encode($response);

  function UpdateAssetName($assetType, $assetID, $newName) {
    $conn = GetLocalMySQLConnection();
    $stmt = $conn->prepare("UPDATE ownership SET assetName = ? WHERE assetIdentifier = ? AND assetType = ?");
    $stmt->bind_param("sii", $newName, $assetID, $assetType);
    $result = $stmt->execute();
    $stmt->close();
    $conn->close();
    return $result;
  }

?>
<?php

include_once "../Core/HTTPLibraries.php";
include_once "../Database/ConnectionManager.php";
include_once "../AccountFiles/AccountSessionAPI.php";
include_once "../AccountFiles/AccountDatabaseAPI.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	$gameName = $_POST['gameName'] ?? null;
	$assetVisibility = $_POST['visibility'] ?? null;
	$assetType = $_POST['assetType'] ?? null;

	if($gameName && $assetVisibility) {
		  //Quit if user is not logged in
		  if(!IsUserLoggedIn()) {
			echo("You must be logged in to edit this asset.");
			exit;
		  }
		  $loggedInUser = LoggedInUser();
		  $assetOwner = LoadAssetData(1, $gameName)["assetOwner"];
		  if($loggedInUser != $assetOwner) {
			echo("You must own this asset to edit it.");
			exit;
		  }

		$conn = GetLocalMySQLConnection();
		if ($conn->connect_error) {
			die("Connection failed: " . $conn->connect_error);
		}

		if($assetVisibility == "private") {
			$assetVisibility = 0;
		} else if($assetVisibility == "link only") {
			$assetVisibility = 1;
		} else if($assetVisibility == "public") {
			$assetVisibility = 2;
		} else if($assetVisibility == "team") {
			$userData = LoadUserDataFromId($loggedInUser);
			if($userData['teamID'] == null) {
				$assetVisibility = 0;
			} else {
				$assetVisibility = 1000 + $userData['teamID'];
			}
		}

		$stmt = $conn->prepare("UPDATE ownership SET assetVisibility = ? WHERE assetType=? AND assetIdentifier = ?");
		$stmt->bind_param("iii", $assetVisibility, $assetType, $gameName);

		if ($stmt->execute()) {
			echo "Record updated successfully";
		} else {
			echo "Error updating record: " . $stmt->error;
		}

		$stmt->close();
		$conn->close();
	} else {
		echo "Invalid input";
	}
} else {
	echo "Invalid request method";
}

?>

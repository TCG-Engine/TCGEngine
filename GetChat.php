<?php
header('Content-Type: application/json');

include_once './Core/HTTPLibraries.php';
include_once './Core/NetworkingLibraries.php';

$gameName   = TryGET("gameName", "");
$lastChatID = intval(TryGET("lastChatID", "0"));

if ($gameName === "" || !IsGameNameValid($gameName)) { echo "[]"; exit; }
echo json_encode(GetChatMessagesSince($gameName, $lastChatID));

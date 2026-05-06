<?php
header('Content-Type: application/json');

include_once './Core/HTTPLibraries.php';

$gameName   = TryGET("gameName", "");
$lastChatID = intval(TryGET("lastChatID", "0"));

if ($gameName === "" || !IsGameNameValid($gameName)) { echo "[]"; exit; }
if (!extension_loaded('apcu') || !apcu_enabled())    { echo "[]"; exit; }

$cacheKey = "chat_" . $gameName;
$messages = apcu_fetch($cacheKey);

if ($messages === false) { echo "[]"; exit; }

$newMessages = array_values(array_filter($messages, function ($m) use ($lastChatID) {
    return $m['id'] > $lastChatID;
}));

echo json_encode($newMessages);

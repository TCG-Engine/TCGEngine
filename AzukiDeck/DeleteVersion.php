<?php

require_once __DIR__ . '/../Core/HTTPLibraries.php';
require_once __DIR__ . '/../AccountFiles/AccountSessionAPI.php';
require_once __DIR__ . '/AutoVersioning.php';

header('Content-Type: application/json; charset=utf-8');

if(!IsUserLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'You must be logged in.']);
    exit;
}

$deckID = intval(TryGet('deckID', 0));
$versionID = intval(TryGet('versionID', 0));
if($deckID <= 0 || $versionID <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Missing deck or version.']);
    exit;
}

if(!AzukiDeckLoadOwnedDeck($deckID, LoggedInUser())) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Only the deck owner can delete versions.']);
    exit;
}

if(!AzukiAutoVersioningDelete($deckID, $versionID)) {
    http_response_code(404);
    echo json_encode(['success' => false, 'error' => 'The version could not be deleted.']);
    exit;
}

echo json_encode(['success' => true]);

?>

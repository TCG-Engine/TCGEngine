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
if($deckID <= 0 || !AzukiDeckLoadOwnedDeck($deckID, LoggedInUser())) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Only the deck owner can view its version history.']);
    exit;
}

$versions = AzukiAutoVersioningTreeOrder(AzukiAutoVersioningList($deckID));
$payload = [];
foreach($versions as $version) {
    $payload[] = [
        'versionID' => intval($version['versionID']),
        'versionNumber' => intval($version['versionNumber']),
        'versionName' => (string)$version['versionName'],
        'parentVersionID' => $version['parentVersionID'] === null ? null : intval($version['parentVersionID']),
        'depth' => intval($version['displayDepth'] ?? 0),
        'distance' => intval($version['distanceFromParent'] ?? 0),
        'delta' => $version['delta'],
        'gamesPlayed' => intval($version['gamesPlayed'] ?? 0),
        'wins' => intval($version['wins'] ?? 0),
        'losses' => intval($version['losses'] ?? 0)
    ];
}

echo json_encode(['success' => true, 'versions' => $payload]);

?>

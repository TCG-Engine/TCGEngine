<?php
// APIs/DeckMetaMatchupStatsAPI.php
header('Content-Type: application/json');
include_once '../Database/ConnectionManager.php';

$leaderID = isset($_GET['leaderID']) ? $_GET['leaderID'] : '';
$baseID = isset($_GET['baseID']) ? $_GET['baseID'] : '';

if ($leaderID === '' || $baseID === '') {
    echo json_encode([]);
    exit;
}

$conn = GetLocalMySQLConnection();
$stmt = $conn->prepare("SELECT opponentLeaderID, opponentBaseID, numWins, numPlays, playsGoingFirst, turnsInWins, totalTurns, cardsResourcedInWins, totalCardsResourced, remainingHealthInWins, winsGoingFirst, winsGoingSecond FROM deckmetamatchupstats WHERE leaderID = ? AND baseID = ? AND week = 0 ORDER BY numPlays DESC");
$stmt->bind_param('ss', $leaderID, $baseID);
$stmt->execute();
$result = $stmt->get_result();

$rows = [];
while ($row = $result->fetch_assoc()) {
    $rows[] = $row;
}
$stmt->close();
$conn->close();
echo json_encode($rows);

<?php
// APIs/MetaMatchupStatsAPI.php
header('Content-Type: application/json');
include_once '../Database/ConnectionManager.php';

$conn = GetLocalMySQLConnection();
$stmt = $conn->prepare("SELECT leaderID, baseID, opponentLeaderID, opponentBaseID, numWins, numPlays, playsGoingFirst, turnsInWins, totalTurns, cardsResourcedInWins, totalCardsResourced, remainingHealthInWins, winsGoingFirst, winsGoingSecond FROM deckmetamatchupstats WHERE week = 0 ORDER BY numPlays DESC");
$stmt->execute();
$result = $stmt->get_result();

$rows = [];
while ($row = $result->fetch_assoc()) {
    $rows[] = $row;
}
$stmt->close();
$conn->close();
echo json_encode($rows);

?>

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

// Optional week filter (mirrors Stats/DeckMetaStatsAPI.php). Values are intval()'d, so
// the resulting clause is safe to inline. Default (no params) stays week = 0 for the
// existing matchup-modal drilldown. A range returns one row per opponent PER WEEK so
// callers can track change over time.
$startWeek = isset($_GET['startWeek']) ? intval($_GET['startWeek']) : null;
$endWeek = isset($_GET['endWeek']) ? intval($_GET['endWeek']) : null;

if ($startWeek === null && $endWeek === null) {
    $weekWhere = 'week = 0';
} elseif ($startWeek !== null && $endWeek === null) {
    $weekWhere = 'week = ' . $startWeek;
} else {
    if ($startWeek === null) $startWeek = $endWeek;
    if ($endWeek === null) $endWeek = $startWeek;
    if ($startWeek > $endWeek) { $tmp = $startWeek; $startWeek = $endWeek; $endWeek = $tmp; }
    $weekWhere = 'week BETWEEN ' . $startWeek . ' AND ' . $endWeek;
}

$conn = GetLocalMySQLConnection();
$stmt = $conn->prepare("SELECT opponentLeaderID, opponentBaseID, week, numWins, numPlays, playsGoingFirst, turnsInWins, totalTurns, cardsResourcedInWins, totalCardsResourced, remainingHealthInWins, winsGoingFirst, winsGoingSecond FROM deckmetamatchupstats WHERE leaderID = ? AND baseID = ? AND " . $weekWhere . " ORDER BY week, numPlays DESC");
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

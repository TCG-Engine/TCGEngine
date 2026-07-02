<?php
// APIs/MetaMatchupStatsAPI.php
// Whole-meta matchup grid (every leader/base vs every opponent leader/base).
header('Content-Type: application/json');
include_once '../Database/ConnectionManager.php';

// Week filter (uniform across the meta-stats family). intval()'d, so safe to inline:
//   none           -> all weeks
//   startWeek only -> week >= startWeek  (that week to the end)
//   endWeek only   -> week <= endWeek    (from 0 to that week)
//   both           -> week BETWEEN start AND end (auto-swapped)
$startWeek = isset($_GET['startWeek']) ? intval($_GET['startWeek']) : null;
$endWeek   = isset($_GET['endWeek'])   ? intval($_GET['endWeek'])   : null;

if ($startWeek === null && $endWeek === null) {
    $weekWhere = '1';                                  // all weeks
} elseif ($startWeek !== null && $endWeek === null) {
    $weekWhere = 'week >= ' . $startWeek;
} elseif ($startWeek === null && $endWeek !== null) {
    $weekWhere = 'week <= ' . $endWeek;
} else {
    if ($startWeek > $endWeek) { $tmp = $startWeek; $startWeek = $endWeek; $endWeek = $tmp; }
    $weekWhere = 'week BETWEEN ' . $startWeek . ' AND ' . $endWeek;
}

// Default: aggregate the selected weeks into one row per matchup (all-time / range
// total). groupByWeek=1 returns one row per matchup PER WEEK (time series).
$groupByWeek = isset($_GET['groupByWeek']) && $_GET['groupByWeek'] == '1';

$conn = GetLocalMySQLConnection();

if ($groupByWeek) {
    $sql = "SELECT leaderID, baseID, opponentLeaderID, opponentBaseID, week,
                   numWins, numPlays, playsGoingFirst, turnsInWins, totalTurns,
                   cardsResourcedInWins, totalCardsResourced, remainingHealthInWins,
                   winsGoingFirst, winsGoingSecond
            FROM deckmetamatchupstats
            WHERE $weekWhere
            ORDER BY week, numPlays DESC";
} else {
    // Every metric is additive, so SUM() across weeks keeps ratio math correct.
    $sql = "SELECT leaderID, baseID, opponentLeaderID, opponentBaseID,
                   SUM(numWins) AS numWins, SUM(numPlays) AS numPlays,
                   SUM(playsGoingFirst) AS playsGoingFirst,
                   SUM(turnsInWins) AS turnsInWins, SUM(totalTurns) AS totalTurns,
                   SUM(cardsResourcedInWins) AS cardsResourcedInWins,
                   SUM(totalCardsResourced) AS totalCardsResourced,
                   SUM(remainingHealthInWins) AS remainingHealthInWins,
                   SUM(winsGoingFirst) AS winsGoingFirst,
                   SUM(winsGoingSecond) AS winsGoingSecond
            FROM deckmetamatchupstats
            WHERE $weekWhere
            GROUP BY leaderID, baseID, opponentLeaderID, opponentBaseID
            ORDER BY numPlays DESC";
}

$result = $conn->query($sql);
$rows = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $rows[] = $row;
    }
}
$conn->close();
echo json_encode($rows);

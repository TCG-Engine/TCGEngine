<?php
// APIs/DeckMetaMatchupStatsAPI.php
header('Content-Type: application/json');
include_once '../Database/ConnectionManager.php';
include_once '../SWUDeck/GeneratedCode/GeneratedCardDictionaries.php';
include_once '../Core/StatsBaseRegistry.php';

$leaderID = isset($_GET['leaderID']) ? $_GET['leaderID'] : '';
$baseID = isset($_GET['baseID']) ? $_GET['baseID'] : '';
// Opt-in: treat baseID as a color+type bucket and aggregate mechanically-equivalent common
// bases (own and opponent). Default off keeps the documented exact-baseID response unchanged.
$consolidate = isset($_GET['consolidate']) && $_GET['consolidate'] == '1';

if ($leaderID === '' || $baseID === '') {
    echo json_encode([]);
    exit;
}

// Week filter (uniform across the meta-stats family). Values are intval()'d, so the
// resulting clause is safe to inline:
//   none           -> all weeks
//   startWeek only -> week >= startWeek  (from that week to the end)
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

// By default the selected weeks are AGGREGATED into one row per opponent (all-time
// or range total) -- what the matchup modal wants. groupByWeek=1 instead returns
// one row per opponent PER WEEK, for callers charting change over time.
$groupByWeek = isset($_GET['groupByWeek']) && $_GET['groupByWeek'] == '1';

$conn = GetLocalMySQLConnection();

if ($groupByWeek) {
    $sql = "SELECT opponentLeaderID, opponentBaseID, week,
                   numWins, numPlays, playsGoingFirst, turnsInWins, totalTurns,
                   cardsResourcedInWins, totalCardsResourced, remainingHealthInWins,
                   winsGoingFirst, winsGoingSecond
            FROM deckmetamatchupstats
            WHERE leaderID = ? AND baseID = ? AND $weekWhere
            ORDER BY week, numPlays DESC";
} else {
    // Aggregate across the selected weeks. Every metric is additive, so SUM() keeps
    // the modal's ratio math (wins/plays, turns/wins, ...) correct.
    $sql = "SELECT opponentLeaderID, opponentBaseID,
                   SUM(numWins) AS numWins, SUM(numPlays) AS numPlays,
                   SUM(playsGoingFirst) AS playsGoingFirst,
                   SUM(turnsInWins) AS turnsInWins, SUM(totalTurns) AS totalTurns,
                   SUM(cardsResourcedInWins) AS cardsResourcedInWins,
                   SUM(totalCardsResourced) AS totalCardsResourced,
                   SUM(remainingHealthInWins) AS remainingHealthInWins,
                   SUM(winsGoingFirst) AS winsGoingFirst,
                   SUM(winsGoingSecond) AS winsGoingSecond
            FROM deckmetamatchupstats
            WHERE leaderID = ? AND baseID = ? AND $weekWhere
            GROUP BY opponentLeaderID, opponentBaseID
            ORDER BY numPlays DESC";
}

if ($consolidate && !$groupByWeek) {
    // Consolidated drilldown: the incoming baseID is a representative for a bucket.
    // Match rows by leader + own-base bucket, aggregate by opponent-base bucket.
    $wantKey = StatsBaseBucket($baseID)['key'];
    $sql = "SELECT baseID, opponentLeaderID, opponentBaseID,
                   numWins, numPlays, playsGoingFirst, turnsInWins, totalTurns,
                   cardsResourcedInWins, totalCardsResourced, remainingHealthInWins,
                   winsGoingFirst, winsGoingSecond
            FROM deckmetamatchupstats
            WHERE leaderID = ? AND $weekWhere";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('s', $leaderID);
    $stmt->execute();
    $res = $stmt->get_result();
    $sumCols = ['numWins','numPlays','playsGoingFirst','turnsInWins','totalTurns',
                'cardsResourcedInWins','totalCardsResourced','remainingHealthInWins',
                'winsGoingFirst','winsGoingSecond'];
    $agg = [];
    while ($row = $res->fetch_assoc()) {
        if (StatsBaseBucket($row['baseID'])['key'] !== $wantKey) continue;  // wrong own-base bucket
        $oppB = StatsBaseBucket($row['opponentBaseID']);
        $k = $row['opponentLeaderID'] . '|' . $oppB['key'];
        if (!isset($agg[$k])) {
            $agg[$k] = ['opponentLeaderID' => $row['opponentLeaderID'],
                        'opponentBaseID' => $oppB['displayBase']];
            foreach ($sumCols as $c) $agg[$k][$c] = 0;
        }
        foreach ($sumCols as $c) $agg[$k][$c] += intval($row[$c]);
    }
    $stmt->close();
    $conn->close();
    $rows = array_values($agg);
    usort($rows, function ($a, $b) { return $b['numPlays'] - $a['numPlays']; });
    echo json_encode($rows);
    exit;
}

$stmt = $conn->prepare($sql);
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

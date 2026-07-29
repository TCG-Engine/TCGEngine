<?php

require_once __DIR__ . '/../Database/ConnectionManager.php';

function MatchHistoryNormalizeRootName($rootName) {
    $rootName = trim((string)$rootName);
    return preg_match('/^[A-Za-z0-9_-]{1,64}$/', $rootName) ? $rootName : '';
}

function MatchHistoryEnsureSchema($conn) {
    if(!$conn) return false;

    $sql = "CREATE TABLE IF NOT EXISTS matchhistory (
        matchHistoryID bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        rootName varchar(64) NOT NULL,
        gameName varchar(64) NOT NULL,
        userID int(11) NOT NULL,
        userSeat tinyint(4) NOT NULL,
        opponentUserID int(11) DEFAULT NULL,
        opponentName varchar(128) NOT NULL DEFAULT 'Guest',
        result char(1) NOT NULL,
        gameMode varchar(24) NOT NULL DEFAULT 'pvp',
        deckID int(11) DEFAULT NULL,
        deckName varchar(128) NOT NULL DEFAULT '',
        keyCard1ID varchar(128) NOT NULL DEFAULT '',
        keyCard2ID varchar(128) NOT NULL DEFAULT '',
        keyCard3ID varchar(128) NOT NULL DEFAULT '',
        opponentKeyCard1ID varchar(128) NOT NULL DEFAULT '',
        opponentKeyCard2ID varchar(128) NOT NULL DEFAULT '',
        opponentKeyCard3ID varchar(128) NOT NULL DEFAULT '',
        wentFirst tinyint(1) NOT NULL DEFAULT 0,
        turnCount int(11) NOT NULL DEFAULT 0,
        endReason varchar(32) NOT NULL DEFAULT '',
        completedAt timestamp NOT NULL DEFAULT current_timestamp(),
        PRIMARY KEY (matchHistoryID),
        UNIQUE KEY uq_match_history_seat (rootName, gameName, userID, userSeat),
        KEY idx_match_history_user_time (rootName, userID, completedAt),
        KEY idx_match_history_user_deck (rootName, userID, deckID, completedAt)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";

    return $conn->query($sql) === true;
}

function MatchHistoryVariableKey($rootName, $player) {
    $normalized = strtoupper(preg_replace('/[^A-Za-z0-9_]/', '_', (string)$rootName));
    return 'P' . intval($player) . '_MATCH_HISTORY_' . $normalized;
}

function MatchHistoryCaptureSeat($rootName, $player, $userID, $deckID, $deckName, $keyCardIDs = [], $displayName = 'Guest') {
    $rootName = MatchHistoryNormalizeRootName($rootName);
    $player = intval($player);
    if($rootName === '' || ($player !== 1 && $player !== 2)) return false;

    $cards = array_values(is_array($keyCardIDs) ? $keyCardIDs : []);
    $snapshot = [
        'userID' => max(0, intval($userID)),
        'deckID' => max(0, intval($deckID)),
        'deckName' => trim((string)$deckName),
        'displayName' => trim((string)$displayName),
        'keyCards' => [
            trim((string)($cards[0] ?? '')),
            trim((string)($cards[1] ?? '')),
            trim((string)($cards[2] ?? '')),
        ],
    ];
    if($snapshot['displayName'] === '') $snapshot['displayName'] = 'Guest';
    DecisionQueueController::StoreVariable(
        MatchHistoryVariableKey($rootName, $player),
        json_encode($snapshot, JSON_UNESCAPED_SLASHES)
    );
    return true;
}

function MatchHistorySeatSnapshot($rootName, $player) {
    $raw = DecisionQueueController::GetVariable(MatchHistoryVariableKey($rootName, $player));
    $snapshot = json_decode(is_string($raw) ? $raw : '', true);
    if(!is_array($snapshot)) $snapshot = [];
    $cards = array_values(is_array($snapshot['keyCards'] ?? null) ? $snapshot['keyCards'] : []);
    return [
        'userID' => max(0, intval($snapshot['userID'] ?? 0)),
        'deckID' => max(0, intval($snapshot['deckID'] ?? 0)),
        'deckName' => trim((string)($snapshot['deckName'] ?? '')),
        'displayName' => trim((string)($snapshot['displayName'] ?? 'Guest')),
        'keyCards' => [
            trim((string)($cards[0] ?? '')),
            trim((string)($cards[1] ?? '')),
            trim((string)($cards[2] ?? '')),
        ],
    ];
}

function MatchHistoryUsername($conn, $userID) {
    $userID = intval($userID);
    if(!$conn || $userID <= 0) return null;

    $stmt = $conn->prepare('SELECT usersUid FROM users WHERE usersId = ? LIMIT 1');
    if(!$stmt) return null;
    $stmt->bind_param('i', $userID);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result ? $result->fetch_assoc() : null;
    $stmt->close();
    $username = trim((string)($row['usersUid'] ?? ''));
    return $username !== '' ? $username : null;
}

function MatchHistoryRecord($rootName, $gameName, $winner, $gameMode, $firstPlayer, $turnCount, $endReason) {
    $rootName = MatchHistoryNormalizeRootName($rootName);
    $gameName = trim((string)$gameName);
    $winner = intval($winner);
    if($rootName === '' || $gameName === '' || ($winner !== 1 && $winner !== 2)) return false;

    $recordedKey = 'MATCH_HISTORY_RECORDED_' . strtoupper($rootName);
    if(DecisionQueueController::GetVariable($recordedKey) === '1') return true;

    $conn = GetLocalMySQLConnection();
    if(!$conn || !MatchHistoryEnsureSchema($conn)) {
        if($conn) $conn->close();
        return false;
    }

    $sql = "INSERT IGNORE INTO matchhistory
        (rootName, gameName, userID, userSeat, opponentUserID, opponentName, result,
         gameMode, deckID, deckName, keyCard1ID, keyCard2ID, keyCard3ID,
         opponentKeyCard1ID, opponentKeyCard2ID, opponentKeyCard3ID,
         wentFirst, turnCount, endReason)
        VALUES (?, ?, ?, ?, NULLIF(?, 0), ?, ?, ?, NULLIF(?, 0), ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    if(!$stmt) {
        $conn->close();
        return false;
    }

    $success = true;
    $inserted = 0;
    $gameMode = trim((string)$gameMode);
    if($gameMode === '') $gameMode = 'pvp';
    $firstPlayer = intval($firstPlayer);
    $turnCount = max(0, intval($turnCount));
    $endReason = trim((string)$endReason);

    foreach([1, 2] as $player) {
        $seat = MatchHistorySeatSnapshot($rootName, $player);
        if($seat['userID'] <= 0) continue;

        $opponent = $player === 1 ? 2 : 1;
        $opponentSeat = MatchHistorySeatSnapshot($rootName, $opponent);
        $opponentName = MatchHistoryUsername($conn, $opponentSeat['userID']);
        if($opponentName === null) $opponentName = $opponentSeat['displayName'] !== '' ? $opponentSeat['displayName'] : 'Guest';
        $result = $player === $winner ? 'W' : 'L';
        $wentFirst = $firstPlayer === $player ? 1 : 0;

        $stmt->bind_param(
            'ssiiisssisssssssiis',
            $rootName,
            $gameName,
            $seat['userID'],
            $player,
            $opponentSeat['userID'],
            $opponentName,
            $result,
            $gameMode,
            $seat['deckID'],
            $seat['deckName'],
            $seat['keyCards'][0],
            $seat['keyCards'][1],
            $seat['keyCards'][2],
            $opponentSeat['keyCards'][0],
            $opponentSeat['keyCards'][1],
            $opponentSeat['keyCards'][2],
            $wentFirst,
            $turnCount,
            $endReason
        );
        if(!$stmt->execute()) {
            $success = false;
            break;
        }
        $inserted += max(0, intval($stmt->affected_rows));
    }

    $stmt->close();
    if($success) DecisionQueueController::StoreVariable($recordedKey, '1');
    $conn->close();
    return $success && $inserted > 0;
}

function MatchHistoryLoad($rootName, $userID, $limit = 100, $deckID = null) {
    $rootName = MatchHistoryNormalizeRootName($rootName);
    $userID = intval($userID);
    $limit = max(1, min(250, intval($limit)));
    $deckID = $deckID === null ? null : intval($deckID);
    $history = ['wins' => 0, 'losses' => 0, 'draws' => 0, 'matches' => []];
    if($rootName === '' || $userID <= 0 || ($deckID !== null && $deckID <= 0)) return $history;

    $conn = GetLocalMySQLConnection();
    if(!$conn || !MatchHistoryEnsureSchema($conn)) {
        if($conn) $conn->close();
        return $history;
    }

    $deckWhere = $deckID === null ? '' : ' AND deckID = ?';
    $summaryStmt = $conn->prepare(
        "SELECT
            SUM(CASE WHEN result = 'W' THEN 1 ELSE 0 END) AS wins,
            SUM(CASE WHEN result = 'L' THEN 1 ELSE 0 END) AS losses,
            SUM(CASE WHEN result NOT IN ('W', 'L') THEN 1 ELSE 0 END) AS draws
         FROM matchhistory
         WHERE rootName = ? AND userID = ?" . $deckWhere
    );
    if($summaryStmt) {
        if($deckID === null) $summaryStmt->bind_param('si', $rootName, $userID);
        else $summaryStmt->bind_param('sii', $rootName, $userID, $deckID);
        $summaryStmt->execute();
        $summaryResult = $summaryStmt->get_result();
        $summary = $summaryResult ? $summaryResult->fetch_assoc() : null;
        $history['wins'] = intval($summary['wins'] ?? 0);
        $history['losses'] = intval($summary['losses'] ?? 0);
        $history['draws'] = intval($summary['draws'] ?? 0);
        $summaryStmt->close();
    }

    $stmt = $conn->prepare(
        "SELECT gameName, opponentName, result, gameMode, deckID, deckName,
                keyCard1ID, keyCard2ID, keyCard3ID,
                opponentKeyCard1ID, opponentKeyCard2ID, opponentKeyCard3ID,
                wentFirst, turnCount, endReason, completedAt
         FROM matchhistory
         WHERE rootName = ? AND userID = ?" . $deckWhere . "
         ORDER BY completedAt DESC, matchHistoryID DESC
         LIMIT ?"
    );
    if($stmt) {
        if($deckID === null) $stmt->bind_param('sii', $rootName, $userID, $limit);
        else $stmt->bind_param('siii', $rootName, $userID, $deckID, $limit);
        $stmt->execute();
        $result = $stmt->get_result();
        while($row = $result->fetch_assoc()) $history['matches'][] = $row;
        $stmt->close();
    }

    $conn->close();
    return $history;
}

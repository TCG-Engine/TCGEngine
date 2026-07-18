<?php

require_once __DIR__ . '/../../Database/ConnectionManager.php';

function AzukiStatsEnsureSchema($conn) {
    if(!$conn) return false;

    $sql = "CREATE TABLE IF NOT EXISTS azukicarddeckstats (
        deckID int(11) NOT NULL,
        cardID varchar(128) NOT NULL,
        gamesIncluded int(11) NOT NULL DEFAULT 0,
        gamesIncludedInWins int(11) NOT NULL DEFAULT 0,
        copiesIncluded int(11) NOT NULL DEFAULT 0,
        copiesIncludedInWins int(11) NOT NULL DEFAULT 0,
        timesPlayed int(11) NOT NULL DEFAULT 0,
        timesPlayedInWins int(11) NOT NULL DEFAULT 0,
        timesDrawn int(11) NOT NULL DEFAULT 0,
        timesDrawnInWins int(11) NOT NULL DEFAULT 0,
        timesAttacks int(11) NOT NULL DEFAULT 0,
        timesAttacksInWins int(11) NOT NULL DEFAULT 0,
        timesTargetedByAttacks int(11) NOT NULL DEFAULT 0,
        timesTargetedByAttacksInWins int(11) NOT NULL DEFAULT 0,
        PRIMARY KEY (deckID, cardID)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";

    return $conn->query($sql) === true;
}

function AzukiStatsSavedDeckID($deckLink) {
    $deckLink = trim((string)$deckLink);
    if(!preg_match('/^azukideck:(\d+)$/i', $deckLink, $matches)) return 0;
    return intval($matches[1]);
}

function AzukiStatsCaptureDeck($player, $deckLink, $mainDeck) {
    $player = intval($player);
    $deckID = AzukiStatsSavedDeckID($deckLink);
    if(($player !== 1 && $player !== 2) || $deckID <= 0 || !is_array($mainDeck)) return;

    $cardIDs = [];
    foreach($mainDeck as $card) {
        $cardID = is_object($card) ? strval($card->CardID ?? '') : strval($card);
        if($cardID !== '') $cardIDs[] = $cardID;
    }

    DecisionQueueController::StoreVariable('P' . $player . '_AzukiStatsDeckID', strval($deckID));
    DecisionQueueController::StoreVariable('P' . $player . '_AzukiStatsDeckCards', json_encode($cardIDs));
}

function AzukiStatsDeckSnapshot($player) {
    $player = intval($player);
    $deckID = intval(DecisionQueueController::GetVariable('P' . $player . '_AzukiStatsDeckID'));
    $rawCards = DecisionQueueController::GetVariable('P' . $player . '_AzukiStatsDeckCards');
    $cards = json_decode(is_string($rawCards) ? $rawCards : '', true);
    if($deckID <= 0 || !is_array($cards)) return null;

    $counts = [];
    foreach($cards as $cardID) {
        $cardID = trim((string)$cardID);
        if($cardID === '') continue;
        $counts[$cardID] = intval($counts[$cardID] ?? 0) + 1;
    }
    return empty($counts) ? null : ['deckID' => $deckID, 'counts' => $counts];
}

function AzukiStatsTrackGameCardEvent($bucketName, $player, $cardID, $amount = 1) {
    $player = intval($player);
    $cardID = trim((string)$cardID);
    $amount = max(0, intval($amount));
    if(($player !== 1 && $player !== 2) || $cardID === '' || $amount <= 0) return;
    if(function_exists('IncrementMacroGameIndexBucket')) {
        IncrementMacroGameIndexBucket($bucketName, $player, $cardID, $amount);
    }
}

function AzukiStatsGameCardCounts($bucketName, $player) {
    $index = function_exists('GetMacroGameIndexArray') ? GetMacroGameIndexArray() : [];
    $bucket = $index[$bucketName][intval($player)] ?? $index[$bucketName][strval(intval($player))] ?? [];
    if(!is_array($bucket)) return [];

    $counts = [];
    foreach($bucket as $cardID => $amount) {
        $cardID = trim((string)$cardID);
        $amount = max(0, intval($amount));
        if($cardID !== '' && $amount > 0) $counts[$cardID] = $amount;
    }
    return $counts;
}

function AzukiStatsRecordDeck($conn, $deckID, $includedCards, $playedCards, $drawnCards, $attackCards, $targetedCards, $won) {
    $sql = "INSERT INTO azukicarddeckstats
        (deckID, cardID, gamesIncluded, gamesIncludedInWins, copiesIncluded, copiesIncludedInWins,
         timesPlayed, timesPlayedInWins, timesDrawn, timesDrawnInWins, timesAttacks, timesAttacksInWins,
         timesTargetedByAttacks, timesTargetedByAttacksInWins)
        VALUES (?, ?, 1, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE
          gamesIncluded = gamesIncluded + 1,
          gamesIncludedInWins = gamesIncludedInWins + VALUES(gamesIncludedInWins),
          copiesIncluded = copiesIncluded + VALUES(copiesIncluded),
          copiesIncludedInWins = copiesIncludedInWins + VALUES(copiesIncludedInWins),
          timesPlayed = timesPlayed + VALUES(timesPlayed),
          timesPlayedInWins = timesPlayedInWins + VALUES(timesPlayedInWins),
          timesDrawn = timesDrawn + VALUES(timesDrawn),
          timesDrawnInWins = timesDrawnInWins + VALUES(timesDrawnInWins),
          timesAttacks = timesAttacks + VALUES(timesAttacks),
          timesAttacksInWins = timesAttacksInWins + VALUES(timesAttacksInWins),
          timesTargetedByAttacks = timesTargetedByAttacks + VALUES(timesTargetedByAttacks),
          timesTargetedByAttacksInWins = timesTargetedByAttacksInWins + VALUES(timesTargetedByAttacksInWins)";
    $stmt = $conn->prepare($sql);
    if(!$stmt) return false;

    $success = true;
    $winValue = $won ? 1 : 0;
    foreach($includedCards as $cardID => $copies) {
        $copies = max(0, intval($copies));
        $played = max(0, intval($playedCards[$cardID] ?? 0));
        $drawn = max(0, intval($drawnCards[$cardID] ?? 0));
        $attacks = max(0, intval($attackCards[$cardID] ?? 0));
        $targeted = max(0, intval($targetedCards[$cardID] ?? 0));
        $copiesInWins = $won ? $copies : 0;
        $playedInWins = $won ? $played : 0;
        $drawnInWins = $won ? $drawn : 0;
        $attacksInWins = $won ? $attacks : 0;
        $targetedInWins = $won ? $targeted : 0;
        $stmt->bind_param(
            'isiiiiiiiiiii',
            $deckID, $cardID, $winValue, $copies, $copiesInWins,
            $played, $playedInWins, $drawn, $drawnInWins,
            $attacks, $attacksInWins, $targeted, $targetedInWins
        );
        if(!$stmt->execute()) $success = false;
    }
    $stmt->close();
    return $success;
}

function AzukiRecordGameStats($winner) {
    $winner = intval($winner);
    if($winner !== 1 && $winner !== 2) return false;
    if(DecisionQueueController::GetVariable('AZUKI_STATS_RECORDED') === '1') return true;
    // Match SWU's protection against concede/abandon noise during the opening turn.
    if(function_exists('GetTurnNumber') && intval(GetTurnNumber()) < 2) return false;

    $snapshots = [];
    foreach([1, 2] as $player) {
        $snapshot = AzukiStatsDeckSnapshot($player);
        if($snapshot !== null) $snapshots[$player] = $snapshot;
    }
    if(empty($snapshots)) return false;

    $conn = GetLocalMySQLConnection();
    if(!$conn || !AzukiStatsEnsureSchema($conn)) {
        if($conn) $conn->close();
        return false;
    }

    $conn->begin_transaction();
    $success = true;
    foreach($snapshots as $player => $snapshot) {
        if(!AzukiStatsRecordDeck(
            $conn,
            $snapshot['deckID'],
            $snapshot['counts'],
            AzukiStatsGameCardCounts('PlayCard', $player),
            AzukiStatsGameCardCounts('AzukiDrawn', $player),
            AzukiStatsGameCardCounts('AzukiAttacks', $player),
            AzukiStatsGameCardCounts('AzukiTargetedByAttacks', $player),
            intval($player) === $winner
        )) {
            $success = false;
            break;
        }
    }

    if($success) {
        $conn->commit();
        DecisionQueueController::StoreVariable('AZUKI_STATS_RECORDED', '1');
    } else {
        $conn->rollback();
    }
    $conn->close();
    return $success;
}

function AzukiLoadDeckCardStats($deckID) {
    $deckID = intval($deckID);
    if($deckID <= 0) return [];

    $conn = GetLocalMySQLConnection();
    if(!$conn || !AzukiStatsEnsureSchema($conn)) {
        if($conn) $conn->close();
        return [];
    }

    $stmt = $conn->prepare('SELECT cardID, gamesIncluded, gamesIncludedInWins, timesPlayed, timesPlayedInWins,
        timesDrawn, timesAttacks, timesTargetedByAttacks FROM azukicarddeckstats WHERE deckID = ?');
    if(!$stmt) {
        $conn->close();
        return [];
    }
    $stmt->bind_param('i', $deckID);
    $stmt->execute();
    $result = $stmt->get_result();
    $stats = [];
    while($row = $result->fetch_assoc()) {
        $stats[$row['cardID']] = [
            'playWinRate' => intval($row['timesPlayed']) > 0 ? round(intval($row['timesPlayedInWins']) / intval($row['timesPlayed']), 4) : -1,
            'inclusionWinRate' => intval($row['gamesIncluded']) > 0 ? round(intval($row['gamesIncludedInWins']) / intval($row['gamesIncluded']), 4) : -1,
            'playFrequency' => intval($row['timesDrawn']) > 0 ? round(intval($row['timesPlayed']) / intval($row['timesDrawn']), 4) : -1,
            'attackFrequency' => intval($row['timesDrawn']) > 0 ? round(intval($row['timesAttacks']) / intval($row['timesDrawn']), 4) : -1,
            'attackedFrequency' => intval($row['timesDrawn']) > 0 ? round(intval($row['timesTargetedByAttacks']) / intval($row['timesDrawn']), 4) : -1,
        ];
    }
    $stmt->close();
    $conn->close();
    return $stats;
}

?>

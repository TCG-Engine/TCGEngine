<?php

require_once __DIR__ . '/../../Database/ConnectionManager.php';
require_once __DIR__ . '/../../AzukiDeck/AutoVersioning.php';

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
        t1TimesPlayed int(11) NOT NULL DEFAULT 0,
        t1TimesPlayedInWins int(11) NOT NULL DEFAULT 0,
        t2TimesPlayed int(11) NOT NULL DEFAULT 0,
        t2TimesPlayedInWins int(11) NOT NULL DEFAULT 0,
        t3TimesPlayed int(11) NOT NULL DEFAULT 0,
        t3TimesPlayedInWins int(11) NOT NULL DEFAULT 0,
        t4TimesPlayed int(11) NOT NULL DEFAULT 0,
        t4TimesPlayedInWins int(11) NOT NULL DEFAULT 0,
        t5TimesPlayed int(11) NOT NULL DEFAULT 0,
        t5TimesPlayedInWins int(11) NOT NULL DEFAULT 0,
        t6TimesPlayed int(11) NOT NULL DEFAULT 0,
        t6TimesPlayedInWins int(11) NOT NULL DEFAULT 0,
        t7TimesPlayed int(11) NOT NULL DEFAULT 0,
        t7TimesPlayedInWins int(11) NOT NULL DEFAULT 0,
        t8TimesPlayed int(11) NOT NULL DEFAULT 0,
        t8TimesPlayedInWins int(11) NOT NULL DEFAULT 0,
        t9TimesPlayed int(11) NOT NULL DEFAULT 0,
        t9TimesPlayedInWins int(11) NOT NULL DEFAULT 0,
        t10PlusTimesPlayed int(11) NOT NULL DEFAULT 0,
        t10PlusTimesPlayedInWins int(11) NOT NULL DEFAULT 0,
        PRIMARY KEY (deckID, cardID)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";

    if($conn->query($sql) !== true) return false;

    $versionStatsSQL = "CREATE TABLE IF NOT EXISTS azukideckversionstats (
        deckID int(11) NOT NULL,
        versionID bigint(20) UNSIGNED NOT NULL,
        gamesPlayed int(11) NOT NULL DEFAULT 0,
        wins int(11) NOT NULL DEFAULT 0,
        losses int(11) NOT NULL DEFAULT 0,
        lastUpdated timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
        PRIMARY KEY (deckID, versionID),
        KEY idx_azukideckversionstats_version (versionID)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";

    return $conn->query($versionStatsSQL) === true;
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

function AzukiStatsTurnCycleBucket($rawTurnNumber) {
    // TurnNumber advances after each individual player's turn. Pair 1/2 into
    // cycle 1, 3/4 into cycle 2, and cap the final analytics bucket at 10+.
    $rawTurnNumber = max(1, intval($rawTurnNumber));
    return min(10, intdiv($rawTurnNumber + 1, 2));
}

function AzukiStatsTrackPlay($player, $cardID, $amount = 1) {
    $player = intval($player);
    $cardID = trim((string)$cardID);
    $amount = max(0, intval($amount));
    if(($player !== 1 && $player !== 2) || $cardID === '' || $amount <= 0) return;
    if(!function_exists('GetMacroGameIndexArray') || !function_exists('SetMacroGameIndex')) return;

    $turnNumber = function_exists('GetTurnNumber') ? GetTurnNumber() : 1;
    $turnBucket = AzukiStatsTurnCycleBucket($turnNumber);
    $index = GetMacroGameIndexArray();

    if(!isset($index['AzukiPlays']) || !is_array($index['AzukiPlays'])) $index['AzukiPlays'] = [];
    if(!isset($index['AzukiPlays'][$player]) || !is_array($index['AzukiPlays'][$player])) {
        $index['AzukiPlays'][$player] = [];
    }
    $index['AzukiPlays'][$player][$cardID] = intval($index['AzukiPlays'][$player][$cardID] ?? 0) + $amount;

    if(!isset($index['AzukiPlaysByTurn']) || !is_array($index['AzukiPlaysByTurn'])) {
        $index['AzukiPlaysByTurn'] = [];
    }
    if(!isset($index['AzukiPlaysByTurn'][$player]) || !is_array($index['AzukiPlaysByTurn'][$player])) {
        $index['AzukiPlaysByTurn'][$player] = [];
    }
    if(!isset($index['AzukiPlaysByTurn'][$player][$turnBucket]) || !is_array($index['AzukiPlaysByTurn'][$player][$turnBucket])) {
        $index['AzukiPlaysByTurn'][$player][$turnBucket] = [];
    }
    $index['AzukiPlaysByTurn'][$player][$turnBucket][$cardID] =
        intval($index['AzukiPlaysByTurn'][$player][$turnBucket][$cardID] ?? 0) + $amount;

    SetMacroGameIndex(json_encode($index));
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

function AzukiStatsGameCardTurnCounts($player) {
    $index = function_exists('GetMacroGameIndexArray') ? GetMacroGameIndexArray() : [];
    $player = intval($player);
    $buckets = $index['AzukiPlaysByTurn'][$player] ?? $index['AzukiPlaysByTurn'][strval($player)] ?? [];
    $counts = [];
    for($turn = 1; $turn <= 10; ++$turn) {
        $counts[$turn] = [];
        $bucket = $buckets[$turn] ?? $buckets[strval($turn)] ?? [];
        if(!is_array($bucket)) continue;
        foreach($bucket as $cardID => $amount) {
            $cardID = trim((string)$cardID);
            $amount = max(0, intval($amount));
            if($cardID !== '' && $amount > 0) $counts[$turn][$cardID] = $amount;
        }
    }
    return $counts;
}

function AzukiStatsRecordDeck($conn, $deckID, $includedCards, $playedCards, $playedCardsByTurn, $drawnCards, $attackCards, $targetedCards, $won) {
    $sql = "INSERT INTO azukicarddeckstats
        (deckID, cardID, gamesIncluded, gamesIncludedInWins, copiesIncluded, copiesIncludedInWins,
         timesPlayed, timesPlayedInWins, timesDrawn, timesDrawnInWins, timesAttacks, timesAttacksInWins,
         timesTargetedByAttacks, timesTargetedByAttacksInWins,
         t1TimesPlayed, t1TimesPlayedInWins, t2TimesPlayed, t2TimesPlayedInWins,
         t3TimesPlayed, t3TimesPlayedInWins, t4TimesPlayed, t4TimesPlayedInWins,
         t5TimesPlayed, t5TimesPlayedInWins, t6TimesPlayed, t6TimesPlayedInWins,
         t7TimesPlayed, t7TimesPlayedInWins, t8TimesPlayed, t8TimesPlayedInWins,
         t9TimesPlayed, t9TimesPlayedInWins, t10PlusTimesPlayed, t10PlusTimesPlayedInWins)
        VALUES (?, ?, 1, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,
                ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
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
          timesTargetedByAttacksInWins = timesTargetedByAttacksInWins + VALUES(timesTargetedByAttacksInWins),
          t1TimesPlayed = t1TimesPlayed + VALUES(t1TimesPlayed),
          t1TimesPlayedInWins = t1TimesPlayedInWins + VALUES(t1TimesPlayedInWins),
          t2TimesPlayed = t2TimesPlayed + VALUES(t2TimesPlayed),
          t2TimesPlayedInWins = t2TimesPlayedInWins + VALUES(t2TimesPlayedInWins),
          t3TimesPlayed = t3TimesPlayed + VALUES(t3TimesPlayed),
          t3TimesPlayedInWins = t3TimesPlayedInWins + VALUES(t3TimesPlayedInWins),
          t4TimesPlayed = t4TimesPlayed + VALUES(t4TimesPlayed),
          t4TimesPlayedInWins = t4TimesPlayedInWins + VALUES(t4TimesPlayedInWins),
          t5TimesPlayed = t5TimesPlayed + VALUES(t5TimesPlayed),
          t5TimesPlayedInWins = t5TimesPlayedInWins + VALUES(t5TimesPlayedInWins),
          t6TimesPlayed = t6TimesPlayed + VALUES(t6TimesPlayed),
          t6TimesPlayedInWins = t6TimesPlayedInWins + VALUES(t6TimesPlayedInWins),
          t7TimesPlayed = t7TimesPlayed + VALUES(t7TimesPlayed),
          t7TimesPlayedInWins = t7TimesPlayedInWins + VALUES(t7TimesPlayedInWins),
          t8TimesPlayed = t8TimesPlayed + VALUES(t8TimesPlayed),
          t8TimesPlayedInWins = t8TimesPlayedInWins + VALUES(t8TimesPlayedInWins),
          t9TimesPlayed = t9TimesPlayed + VALUES(t9TimesPlayed),
          t9TimesPlayedInWins = t9TimesPlayedInWins + VALUES(t9TimesPlayedInWins),
          t10PlusTimesPlayed = t10PlusTimesPlayed + VALUES(t10PlusTimesPlayed),
          t10PlusTimesPlayedInWins = t10PlusTimesPlayedInWins + VALUES(t10PlusTimesPlayedInWins)";
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
        $turnPlays = [];
        $turnPlaysInWins = [];
        for($turn = 1; $turn <= 10; ++$turn) {
            $turnPlays[$turn] = max(0, intval($playedCardsByTurn[$turn][$cardID] ?? 0));
            $turnPlaysInWins[$turn] = $won ? $turnPlays[$turn] : 0;
        }
        $stmt->bind_param(
            'isiiiiiiiiiiiiiiiiiiiiiiiiiiiiiii',
            $deckID, $cardID, $winValue, $copies, $copiesInWins,
            $played, $playedInWins, $drawn, $drawnInWins,
            $attacks, $attacksInWins, $targeted, $targetedInWins,
            $turnPlays[1], $turnPlaysInWins[1], $turnPlays[2], $turnPlaysInWins[2],
            $turnPlays[3], $turnPlaysInWins[3], $turnPlays[4], $turnPlaysInWins[4],
            $turnPlays[5], $turnPlaysInWins[5], $turnPlays[6], $turnPlaysInWins[6],
            $turnPlays[7], $turnPlaysInWins[7], $turnPlays[8], $turnPlaysInWins[8],
            $turnPlays[9], $turnPlaysInWins[9], $turnPlays[10], $turnPlaysInWins[10]
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
        $version = AzukiAutoVersioningResolve($conn, $snapshot['deckID']);
        if($version === null) {
            $success = false;
            break;
        }
        if(!AzukiStatsRecordDeck(
            $conn,
            $snapshot['deckID'],
            $snapshot['counts'],
            AzukiStatsGameCardCounts('AzukiPlays', $player),
            AzukiStatsGameCardTurnCounts($player),
            AzukiStatsGameCardCounts('AzukiDrawn', $player),
            AzukiStatsGameCardCounts('AzukiAttacks', $player),
            AzukiStatsGameCardCounts('AzukiTargetedByAttacks', $player),
            intval($player) === $winner
        )) {
            $success = false;
            break;
        }
        if(!AzukiAutoVersioningRecordAggregate(
            $conn,
            $snapshot['deckID'],
            intval($version['versionID']),
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
        timesDrawn, timesAttacks, timesTargetedByAttacks,
        t1TimesPlayed, t1TimesPlayedInWins, t2TimesPlayed, t2TimesPlayedInWins,
        t3TimesPlayed, t3TimesPlayedInWins, t4TimesPlayed, t4TimesPlayedInWins,
        t5TimesPlayed, t5TimesPlayedInWins, t6TimesPlayed, t6TimesPlayedInWins,
        t7TimesPlayed, t7TimesPlayedInWins, t8TimesPlayed, t8TimesPlayedInWins,
        t9TimesPlayed, t9TimesPlayedInWins, t10PlusTimesPlayed, t10PlusTimesPlayedInWins
        FROM azukicarddeckstats WHERE deckID = ?');
    if(!$stmt) {
        $conn->close();
        return [];
    }
    $stmt->bind_param('i', $deckID);
    $stmt->execute();
    $result = $stmt->get_result();
    $stats = [];
    while($row = $result->fetch_assoc()) {
        $playWinRate = intval($row['timesPlayed']) > 0
            ? round(intval($row['timesPlayedInWins']) / intval($row['timesPlayed']), 4)
            : -1;
        $playWinRateByTurn = [];
        $playWinRateDeltaByTurn = [];
        $playsByTurn = [];
        for($turn = 1; $turn <= 10; ++$turn) {
            $prefix = $turn === 10 ? 't10Plus' : 't' . $turn;
            $label = $turn === 10 ? '10+' : strval($turn);
            $plays = intval($row[$prefix . 'TimesPlayed']);
            $wins = intval($row[$prefix . 'TimesPlayedInWins']);
            $turnRate = $plays > 0 ? round($wins / $plays, 4) : -1;
            $playsByTurn[$label] = $plays;
            $playWinRateByTurn[$label] = $turnRate;
            $playWinRateDeltaByTurn[$label] = $turnRate >= 0 && $playWinRate >= 0
                ? round($turnRate - $playWinRate, 4)
                : -1;
        }
        $stats[$row['cardID']] = [
            'playWinRate' => $playWinRate,
            'playWinRateByTurn' => $playWinRateByTurn,
            'playWinRateDeltaByTurn' => $playWinRateDeltaByTurn,
            'playsByTurn' => $playsByTurn,
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

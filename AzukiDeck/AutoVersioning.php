<?php

require_once __DIR__ . '/../Core/AssetVersioning.php';
require_once __DIR__ . '/../Database/ConnectionManager.php';
require_once __DIR__ . '/DeckService.php';

function AzukiAutoVersioningEnabled() {
    return true;
}

function AzukiAutoVersioningAppKey() {
    return 'AzukiDeck';
}

function AzukiAutoVersioningConfigFromDeckState($deckState) {
    if(!is_array($deckState) || empty($deckState['success'])) return null;
    $leader = trim((string)($deckState['leader'] ?? ''));
    $gate = trim((string)($deckState['gate'] ?? ''));
    $mainDeck = (array)($deckState['mainDeck'] ?? []);
    if($leader === '' || $gate === '' || empty($mainDeck)) return null;

    $counts = [];
    foreach($mainDeck as $cardID) {
        $cardID = trim((string)$cardID);
        if($cardID === '') continue;
        $counts[$cardID] = intval($counts[$cardID] ?? 0) + 1;
    }
    if(empty($counts)) return null;

    return AssetVersionCanonicalizeConfig([
        'identities' => ['leader' => $leader, 'gate' => $gate],
        'zones' => ['mainDeck' => $counts]
    ]);
}

function AzukiAutoVersioningCurrentConfig($deckID) {
    return AzukiAutoVersioningConfigFromDeckState(AzukiDeckReadDeckState($deckID));
}

function AzukiAutoVersioningResolve($conn, $deckID) {
    $deckID = intval($deckID);
    if($deckID <= 0) return null;
    $config = AzukiAutoVersioningCurrentConfig($deckID);
    if($config === null) return null;
    return AssetVersionFindOrCreate(
        $conn,
        AzukiAutoVersioningAppKey(),
        1,
        $deckID,
        $config
    );
}

function AzukiAutoVersioningRecordAggregate($conn, $deckID, $versionID, $won) {
    $deckID = intval($deckID);
    $versionID = intval($versionID);
    if(!$conn || $deckID <= 0 || $versionID <= 0) return false;
    $winIncrement = $won ? 1 : 0;
    $lossIncrement = $won ? 0 : 1;
    $stmt = $conn->prepare(
        'INSERT INTO azukideckversionstats (deckID, versionID, gamesPlayed, wins, losses)
         VALUES (?, ?, 1, ?, ?)
         ON DUPLICATE KEY UPDATE
           gamesPlayed = gamesPlayed + 1,
           wins = wins + VALUES(wins),
           losses = losses + VALUES(losses)'
    );
    if(!$stmt) return false;
    $stmt->bind_param('iiii', $deckID, $versionID, $winIncrement, $lossIncrement);
    $success = $stmt->execute();
    $stmt->close();
    return $success;
}

function AzukiAutoVersioningList($deckID) {
    $deckID = intval($deckID);
    if($deckID <= 0) return [];
    $conn = GetLocalMySQLConnection();
    if(!$conn) return [];
    $versions = AssetVersionList($conn, AzukiAutoVersioningAppKey(), 1, $deckID);

    $stats = [];
    $stmt = $conn->prepare(
        'SELECT versionID, gamesPlayed, wins, losses
         FROM azukideckversionstats WHERE deckID = ?'
    );
    if($stmt) {
        $stmt->bind_param('i', $deckID);
        $stmt->execute();
        $result = $stmt->get_result();
        while($row = $result->fetch_assoc()) $stats[intval($row['versionID'])] = $row;
        $stmt->close();
    }
    $conn->close();

    foreach($versions as &$version) {
        $versionID = intval($version['versionID']);
        $row = $stats[$versionID] ?? [];
        $version['gamesPlayed'] = intval($row['gamesPlayed'] ?? 0);
        $version['wins'] = intval($row['wins'] ?? 0);
        $version['losses'] = intval($row['losses'] ?? 0);
        $version['delta'] = json_decode((string)($version['deltaJSON'] ?? ''), true);
        if(!is_array($version['delta'])) {
            $version['delta'] = ['distance' => 0, 'identities' => [], 'zones' => []];
        }
    }
    unset($version);
    return $versions;
}

function AzukiAutoVersioningTreeOrder($versions) {
    $byParent = [];
    $knownIDs = [];
    foreach((array)$versions as $version) $knownIDs[intval($version['versionID'])] = true;
    foreach((array)$versions as $version) {
        $parentID = $version['parentVersionID'] === null
            ? 0
            : intval($version['parentVersionID']);
        if($parentID !== 0 && !isset($knownIDs[$parentID])) $parentID = 0;
        $byParent[$parentID][] = $version;
    }
    foreach($byParent as &$siblings) {
        usort($siblings, function($left, $right) {
            return intval($left['versionNumber']) <=> intval($right['versionNumber']);
        });
    }
    unset($siblings);

    $ordered = [];
    $appendChildren = function($parentID, $displayDepth) use (&$appendChildren, &$ordered, $byParent) {
        foreach($byParent[$parentID] ?? [] as $version) {
            $version['displayDepth'] = $displayDepth;
            $ordered[] = $version;
            $appendChildren(intval($version['versionID']), $displayDepth + 1);
        }
    };
    $appendChildren(0, 0);
    return $ordered;
}

function AzukiAutoVersioningDelete($deckID, $versionID) {
    $deckID = intval($deckID);
    $versionID = intval($versionID);
    if($deckID <= 0 || $versionID <= 0) return false;
    $conn = GetLocalMySQLConnection();
    if(!$conn) return false;

    $conn->begin_transaction();
    $statsDelete = $conn->prepare(
        'DELETE FROM azukideckversionstats WHERE deckID = ? AND versionID = ?'
    );
    if(!$statsDelete) {
        $conn->rollback();
        $conn->close();
        return false;
    }
    $statsDelete->bind_param('ii', $deckID, $versionID);
    $success = $statsDelete->execute();
    $statsDelete->close();

    if($success) {
        $success = AssetVersionDeleteAndReparent(
            $conn,
            AzukiAutoVersioningAppKey(),
            1,
            $deckID,
            $versionID
        );
    }

    if($success) $conn->commit();
    else $conn->rollback();
    $conn->close();
    return $success;
}

function AzukiAutoVersioningGetConfig($deckID, $versionID) {
    $conn = GetLocalMySQLConnection();
    if(!$conn) return null;
    $row = AssetVersionGet(
        $conn,
        AzukiAutoVersioningAppKey(),
        1,
        intval($deckID),
        intval($versionID)
    );
    $conn->close();
    return $row ? AssetVersionDecodeConfig($row['assetJSON']) : null;
}

?>

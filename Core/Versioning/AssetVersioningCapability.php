<?php

require_once __DIR__ . '/AssetVersioningService.php';
require_once __DIR__ . '/../../Database/ConnectionManager.php';

/**
 * Engine-level orchestration around the version graph.
 *
 * An app opts in by exposing GetAssetVersioningAdapter(), returning:
 * - appKey: stable namespace
 * - assetType: ownership asset type
 * - enabled: boolean
 * - snapshot($assetID): canonicalizable configuration
 * - applySnapshot($assetID, $playerID, $configuration): bool
 * - authorize($assetID, $userID, $action): bool
 * - describeItem($itemID): optional display-name resolver
 */

function AssetVersioningGetLoadedAdapter() {
    if(!function_exists('GetAssetVersioningAdapter')) return null;
    $adapter = GetAssetVersioningAdapter();
    return is_array($adapter) ? $adapter : null;
}

function AssetVersioningAdapterEnabled($adapter) {
    return is_array($adapter)
        && !empty($adapter['enabled'])
        && trim((string)($adapter['appKey'] ?? '')) !== ''
        && intval($adapter['assetType'] ?? 0) > 0
        && is_callable($adapter['snapshot'] ?? null)
        && is_callable($adapter['applySnapshot'] ?? null)
        && is_callable($adapter['authorize'] ?? null);
}

function AssetVersioningAdapterAppKey($adapter) {
    return trim((string)($adapter['appKey'] ?? ''));
}

function AssetVersioningAdapterAssetType($adapter) {
    return intval($adapter['assetType'] ?? 0);
}

function AssetVersioningAuthorize($adapter, $assetID, $userID, $action) {
    if(!AssetVersioningAdapterEnabled($adapter)) return false;
    $authorize = $adapter['authorize'] ?? null;
    return is_callable($authorize)
        ? (bool)$authorize(intval($assetID), $userID, (string)$action)
        : false;
}

function AssetVersioningEnsureStatsSchema($conn) {
    if(!$conn) return false;
    $sql = "CREATE TABLE IF NOT EXISTS assetversionstats (
        appKey varchar(32) NOT NULL,
        assetType int(11) NOT NULL,
        assetID int(11) NOT NULL,
        versionID bigint(20) UNSIGNED NOT NULL,
        gamesPlayed int(11) NOT NULL DEFAULT 0,
        wins int(11) NOT NULL DEFAULT 0,
        losses int(11) NOT NULL DEFAULT 0,
        lastUpdated timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
        PRIMARY KEY (appKey, assetType, assetID, versionID),
        KEY idx_assetversionstats_version (versionID)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
    return $conn->query($sql) === true;
}

function AssetVersioningResolveCurrent($conn, $adapter, $assetID) {
    $assetID = intval($assetID);
    if(!$conn || $assetID <= 0 || !AssetVersioningAdapterEnabled($adapter)) return null;
    $snapshot = $adapter['snapshot']($assetID);
    if(!is_array($snapshot)) return null;
    return AssetVersionFindOrCreate(
        $conn,
        AssetVersioningAdapterAppKey($adapter),
        AssetVersioningAdapterAssetType($adapter),
        $assetID,
        $snapshot
    );
}

function AssetVersioningRecordAggregate($conn, $adapter, $assetID, $versionID, $won) {
    $assetID = intval($assetID);
    $versionID = intval($versionID);
    if(!$conn || $assetID <= 0 || $versionID <= 0 || !AssetVersioningAdapterEnabled($adapter)) {
        return false;
    }
    $appKey = AssetVersioningAdapterAppKey($adapter);
    $assetType = AssetVersioningAdapterAssetType($adapter);
    $winIncrement = $won ? 1 : 0;
    $lossIncrement = $won ? 0 : 1;
    $stmt = $conn->prepare(
        'INSERT INTO assetversionstats
         (appKey, assetType, assetID, versionID, gamesPlayed, wins, losses)
         VALUES (?, ?, ?, ?, 1, ?, ?)
         ON DUPLICATE KEY UPDATE
           gamesPlayed = gamesPlayed + 1,
           wins = wins + VALUES(wins),
           losses = losses + VALUES(losses)'
    );
    if(!$stmt) return false;
    $stmt->bind_param(
        'siiiii',
        $appKey,
        $assetType,
        $assetID,
        $versionID,
        $winIncrement,
        $lossIncrement
    );
    $success = $stmt->execute();
    $stmt->close();
    return $success;
}

function AssetVersioningRecordResult($conn, $adapter, $assetID, $won) {
    $version = AssetVersioningResolveCurrent($conn, $adapter, $assetID);
    if($version === null) return null;
    return AssetVersioningRecordAggregate(
        $conn,
        $adapter,
        $assetID,
        intval($version['versionID']),
        (bool)$won
    ) ? $version : null;
}

function AssetVersioningListWithStats($adapter, $assetID) {
    $assetID = intval($assetID);
    if($assetID <= 0 || !AssetVersioningAdapterEnabled($adapter)) return [];
    $conn = GetLocalMySQLConnection();
    if(!$conn) return [];

    $appKey = AssetVersioningAdapterAppKey($adapter);
    $assetType = AssetVersioningAdapterAssetType($adapter);
    $versions = AssetVersionList($conn, $appKey, $assetType, $assetID);
    $stats = [];
    $stmt = $conn->prepare(
        'SELECT versionID, gamesPlayed, wins, losses FROM assetversionstats
         WHERE appKey = ? AND assetType = ? AND assetID = ?'
    );
    if($stmt) {
        $stmt->bind_param('sii', $appKey, $assetType, $assetID);
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

function AssetVersioningTreeOrder($versions) {
    $byParent = [];
    $knownIDs = [];
    foreach((array)$versions as $version) $knownIDs[intval($version['versionID'])] = true;
    foreach((array)$versions as $version) {
        $parentID = $version['parentVersionID'] === null ? 0 : intval($version['parentVersionID']);
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

function AssetVersioningDescribeItem($adapter, $itemID) {
    $describe = $adapter['describeItem'] ?? null;
    if(is_callable($describe)) {
        $description = trim((string)$describe((string)$itemID));
        if($description !== '') return $description;
    }
    return (string)$itemID;
}

function AssetVersioningPreviewItem($adapter, $itemID) {
    $itemID = (string)$itemID;
    if($itemID === '') return '';
    $preview = $adapter['previewItem'] ?? null;
    if(!is_callable($preview)) return '';
    return trim((string)$preview($itemID));
}

function AssetVersioningDeltaSegments($adapter, $version) {
    if($version['parentVersionID'] === null) {
        return [['text' => 'Root configuration']];
    }

    $distance = intval($version['distanceFromParent'] ?? 0);
    $segments = [[
        'text' => $distance . ' edit' . ($distance === 1 ? '' : 's')
    ]];
    $changes = [];
    $delta = is_array($version['delta'] ?? null) ? $version['delta'] : [];

    $itemSegment = function($itemID) use ($adapter) {
        $itemID = (string)$itemID;
        return [
            'text' => AssetVersioningDescribeItem($adapter, $itemID),
            'itemID' => $itemID,
            'previewURL' => AssetVersioningPreviewItem($adapter, $itemID)
        ];
    };

    foreach((array)($delta['identities'] ?? []) as $slot => $change) {
        $changes[] = [
            ['text' => ucfirst((string)$slot) . ': '],
            $itemSegment($change['from'] ?? ''),
            ['text' => ' → '],
            $itemSegment($change['to'] ?? '')
        ];
    }
    foreach((array)($delta['zones'] ?? []) as $zoneDelta) {
        foreach((array)($zoneDelta['added'] ?? []) as $itemID => $quantity) {
            $changes[] = [
                ['text' => '+' . intval($quantity) . ' '],
                $itemSegment($itemID)
            ];
        }
        foreach((array)($zoneDelta['removed'] ?? []) as $itemID => $quantity) {
            $changes[] = [
                ['text' => '−' . intval($quantity) . ' '],
                $itemSegment($itemID)
            ];
        }
    }

    foreach($changes as $change) {
        $segments[] = ['text' => count($segments) === 1 ? ' · ' : ', '];
        foreach($change as $segment) $segments[] = $segment;
    }
    return $segments;
}

function AssetVersioningDescribeDelta($adapter, $version) {
    return implode('', array_column(AssetVersioningDeltaSegments($adapter, $version), 'text'));
}

function AssetVersioningBuildClientPayload($adapter, $assetID) {
    $payload = [];
    foreach(AssetVersioningTreeOrder(AssetVersioningListWithStats($adapter, $assetID)) as $version) {
        $deltaSegments = AssetVersioningDeltaSegments($adapter, $version);
        $payload[] = [
            'versionID' => intval($version['versionID']),
            'versionNumber' => intval($version['versionNumber']),
            'versionName' => (string)$version['versionName'],
            'parentVersionID' => $version['parentVersionID'] === null
                ? null
                : intval($version['parentVersionID']),
            'depth' => intval($version['displayDepth'] ?? 0),
            'distance' => intval($version['distanceFromParent'] ?? 0),
            'deltaText' => implode('', array_column($deltaSegments, 'text')),
            'deltaSegments' => $deltaSegments,
            'gamesPlayed' => intval($version['gamesPlayed'] ?? 0),
            'wins' => intval($version['wins'] ?? 0),
            'losses' => intval($version['losses'] ?? 0)
        ];
    }
    return $payload;
}

function AssetVersioningApplyVersion($adapter, $assetID, $playerID, $versionID) {
    if(!AssetVersioningAdapterEnabled($adapter) || !is_callable($adapter['applySnapshot'] ?? null)) {
        return false;
    }
    $conn = GetLocalMySQLConnection();
    if(!$conn) return false;
    $row = AssetVersionGet(
        $conn,
        AssetVersioningAdapterAppKey($adapter),
        AssetVersioningAdapterAssetType($adapter),
        intval($assetID),
        intval($versionID)
    );
    $conn->close();
    $snapshot = $row ? AssetVersionDecodeConfig($row['assetJSON']) : null;
    return is_array($snapshot)
        && (bool)$adapter['applySnapshot'](intval($assetID), intval($playerID), $snapshot);
}

function AssetVersioningRenameVersion($adapter, $assetID, $versionID, $versionName) {
    $assetID = intval($assetID);
    $versionID = intval($versionID);
    $versionName = trim((string)$versionName);
    $nameLength = function_exists('mb_strlen')
        ? mb_strlen($versionName, 'UTF-8')
        : strlen($versionName);
    if($assetID <= 0 || $versionID <= 0 || $versionName === '' || $nameLength > 255) {
        return false;
    }
    if(!AssetVersioningAdapterEnabled($adapter)) return false;

    $conn = GetLocalMySQLConnection();
    if(!$conn) return false;
    $success = AssetVersionRename(
        $conn,
        AssetVersioningAdapterAppKey($adapter),
        AssetVersioningAdapterAssetType($adapter),
        $assetID,
        $versionID,
        $versionName
    );
    $conn->close();
    return $success;
}

function AssetVersioningDeleteVersion($adapter, $assetID, $versionID) {
    $assetID = intval($assetID);
    $versionID = intval($versionID);
    if($assetID <= 0 || $versionID <= 0 || !AssetVersioningAdapterEnabled($adapter)) return false;
    $conn = GetLocalMySQLConnection();
    if(!$conn) return false;
    $conn->begin_transaction();

    $appKey = AssetVersioningAdapterAppKey($adapter);
    $assetType = AssetVersioningAdapterAssetType($adapter);
    $statsDelete = $conn->prepare(
        'DELETE FROM assetversionstats
         WHERE appKey = ? AND assetType = ? AND assetID = ? AND versionID = ?'
    );
    $success = $statsDelete !== false;
    if($statsDelete) {
        $statsDelete->bind_param('siii', $appKey, $assetType, $assetID, $versionID);
        $success = $statsDelete->execute();
        $statsDelete->close();
    }

    $deleteAppStats = $adapter['deleteVersionStats'] ?? null;
    if($success && is_callable($deleteAppStats)) {
        $success = (bool)$deleteAppStats($conn, $assetID, $versionID);
    }
    if($success) {
        $success = AssetVersionDeleteAndReparent(
            $conn,
            $appKey,
            $assetType,
            $assetID,
            $versionID
        );
    }

    if($success) $conn->commit();
    else $conn->rollback();
    $conn->close();
    return $success;
}

?>

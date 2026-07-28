<?php

/**
 * Shared immutable asset-version graph storage and comparison primitives.
 *
 * Apps opt in by translating their live asset into the canonical shape:
 * [
 *   'identities' => ['leader' => '...', 'gate' => '...'],
 *   'zones' => ['mainDeck' => ['card-id' => quantity]]
 * ]
 *
 * The shared layer deliberately knows nothing about deck zones, card IDs, or
 * app-specific stats. Parentage is locked when a version is first created.
 */

function AssetVersionCanonicalizeConfig($config) {
    $identities = [];
    foreach((array)($config['identities'] ?? []) as $slot => $value) {
        $slot = trim((string)$slot);
        $value = trim((string)$value);
        if($slot === '') continue;
        $identities[$slot] = $value;
    }
    ksort($identities, SORT_STRING);

    $zones = [];
    foreach((array)($config['zones'] ?? []) as $zoneName => $rawCards) {
        $zoneName = trim((string)$zoneName);
        if($zoneName === '') continue;
        $counts = [];
        foreach((array)$rawCards as $cardID => $quantity) {
            if(is_int($cardID)) {
                $cardID = trim((string)$quantity);
                $quantity = 1;
            } else {
                $cardID = trim((string)$cardID);
                $quantity = intval($quantity);
            }
            if($cardID === '' || $quantity <= 0) continue;
            $counts[$cardID] = intval($counts[$cardID] ?? 0) + $quantity;
        }
        ksort($counts, SORT_STRING);
        $zones[$zoneName] = $counts;
    }
    ksort($zones, SORT_STRING);

    return ['identities' => $identities, 'zones' => $zones];
}

function AssetVersionEncodeConfig($config) {
    return json_encode(
        AssetVersionCanonicalizeConfig($config),
        JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
    );
}

function AssetVersionConfigHash($config) {
    return hash('sha256', AssetVersionEncodeConfig($config));
}

function AssetVersionDifference($parentConfig, $childConfig) {
    $parent = AssetVersionCanonicalizeConfig($parentConfig);
    $child = AssetVersionCanonicalizeConfig($childConfig);
    $distance = 0;
    $identityChanges = [];

    $identitySlots = array_unique(array_merge(
        array_keys($parent['identities']),
        array_keys($child['identities'])
    ));
    sort($identitySlots, SORT_STRING);
    foreach($identitySlots as $slot) {
        $before = (string)($parent['identities'][$slot] ?? '');
        $after = (string)($child['identities'][$slot] ?? '');
        if($before === $after) continue;
        ++$distance;
        $identityChanges[$slot] = ['from' => $before, 'to' => $after];
    }

    $zoneChanges = [];
    $zoneNames = array_unique(array_merge(
        array_keys($parent['zones']),
        array_keys($child['zones'])
    ));
    sort($zoneNames, SORT_STRING);
    foreach($zoneNames as $zoneName) {
        $beforeCounts = (array)($parent['zones'][$zoneName] ?? []);
        $afterCounts = (array)($child['zones'][$zoneName] ?? []);
        $cardIDs = array_unique(array_merge(array_keys($beforeCounts), array_keys($afterCounts)));
        sort($cardIDs, SORT_STRING);
        $added = [];
        $removed = [];
        $addedTotal = 0;
        $removedTotal = 0;

        foreach($cardIDs as $cardID) {
            $change = intval($afterCounts[$cardID] ?? 0) - intval($beforeCounts[$cardID] ?? 0);
            if($change > 0) {
                $added[$cardID] = $change;
                $addedTotal += $change;
            } elseif($change < 0) {
                $removed[$cardID] = abs($change);
                $removedTotal += abs($change);
            }
        }

        if($addedTotal > 0 || $removedTotal > 0) {
            // One removal paired with one addition is one replacement.
            $distance += max($addedTotal, $removedTotal);
            $zoneChanges[$zoneName] = ['added' => $added, 'removed' => $removed];
        }
    }

    return [
        'distance' => $distance,
        'identities' => $identityChanges,
        'zones' => $zoneChanges
    ];
}

function AssetVersionDecodeConfig($assetJSON) {
    $decoded = json_decode((string)$assetJSON, true);
    return is_array($decoded) ? AssetVersionCanonicalizeConfig($decoded) : null;
}

function AssetVersionSelectParent($versions, $newConfig) {
    $best = null;
    $bestKey = null;
    foreach((array)$versions as $row) {
        $candidateConfig = AssetVersionDecodeConfig($row['assetJSON'] ?? '');
        if($candidateConfig === null) continue;
        $delta = AssetVersionDifference($candidateConfig, $newConfig);
        $key = [
            intval($delta['distance']),
            intval($row['depth'] ?? 0),
            intval($row['versionNumber'] ?? PHP_INT_MAX),
            intval($row['versionID'] ?? PHP_INT_MAX)
        ];
        if($bestKey === null || $key < $bestKey) {
            $bestKey = $key;
            $best = ['row' => $row, 'delta' => $delta];
        }
    }
    return $best;
}

function AssetVersionFindOrCreate($conn, $appKey, $assetType, $assetID, $config) {
    if(!$conn) return null;
    $appKey = trim((string)$appKey);
    $assetType = intval($assetType);
    $assetID = intval($assetID);
    $canonical = AssetVersionCanonicalizeConfig($config);
    $assetJSON = AssetVersionEncodeConfig($canonical);
    $assetHash = AssetVersionConfigHash($canonical);

    $existing = $conn->prepare(
        'SELECT * FROM assetautoversions WHERE appKey = ? AND assetType = ? AND assetID = ? AND assetHash = ? LIMIT 1'
    );
    if(!$existing) return null;
    $existing->bind_param('siis', $appKey, $assetType, $assetID, $assetHash);
    $existing->execute();
    $row = $existing->get_result()->fetch_assoc();
    $existing->close();
    if($row) {
        $row['created'] = false;
        return $row;
    }

    $list = $conn->prepare(
        'SELECT * FROM assetautoversions WHERE appKey = ? AND assetType = ? AND assetID = ? ORDER BY versionNumber, versionID FOR UPDATE'
    );
    if(!$list) return null;
    $list->bind_param('sii', $appKey, $assetType, $assetID);
    $list->execute();
    $result = $list->get_result();
    $versions = [];
    while($candidate = $result->fetch_assoc()) $versions[] = $candidate;
    $list->close();

    // A concurrent transaction may have inserted the hash before our locking read.
    foreach($versions as $candidate) {
        if(hash_equals((string)$candidate['assetHash'], $assetHash)) {
            $candidate['created'] = false;
            return $candidate;
        }
    }

    $parent = AssetVersionSelectParent($versions, $canonical);
    $versionNumber = empty($versions)
        ? 1
        : max(array_map(function($version) { return intval($version['versionNumber']); }, $versions)) + 1;
    $parentVersionID = $parent ? intval($parent['row']['versionID']) : null;
    $depth = $parent ? intval($parent['row']['depth']) + 1 : 0;
    $distance = $parent ? intval($parent['delta']['distance']) : 0;
    $deltaJSON = json_encode(
        $parent ? $parent['delta'] : ['distance' => 0, 'identities' => [], 'zones' => []],
        JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
    );
    $versionName = 'Version ' . $versionNumber;

    $insert = $conn->prepare(
        'INSERT INTO assetautoversions
         (appKey, assetType, assetID, assetHash, versionNumber, versionName, assetJSON,
          parentVersionID, depth, distanceFromParent, deltaJSON)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
    );
    if(!$insert) return null;
    $insert->bind_param(
        'siisissiiis',
        $appKey,
        $assetType,
        $assetID,
        $assetHash,
        $versionNumber,
        $versionName,
        $assetJSON,
        $parentVersionID,
        $depth,
        $distance,
        $deltaJSON
    );
    if(!$insert->execute()) {
        $duplicate = intval($insert->errno) === 1062;
        $insert->close();
        if(!$duplicate) return null;
        return AssetVersionFindOrCreate($conn, $appKey, $assetType, $assetID, $canonical);
    }
    $versionID = intval($conn->insert_id);
    $insert->close();

    return [
        'versionID' => $versionID,
        'appKey' => $appKey,
        'assetType' => $assetType,
        'assetID' => $assetID,
        'assetHash' => $assetHash,
        'versionNumber' => $versionNumber,
        'versionName' => $versionName,
        'assetJSON' => $assetJSON,
        'parentVersionID' => $parentVersionID,
        'depth' => $depth,
        'distanceFromParent' => $distance,
        'deltaJSON' => $deltaJSON,
        'created' => true
    ];
}

function AssetVersionList($conn, $appKey, $assetType, $assetID) {
    if(!$conn) return [];
    $stmt = $conn->prepare(
        'SELECT * FROM assetautoversions WHERE appKey = ? AND assetType = ? AND assetID = ? ORDER BY versionNumber, versionID'
    );
    if(!$stmt) return [];
    $stmt->bind_param('sii', $appKey, $assetType, $assetID);
    $stmt->execute();
    $result = $stmt->get_result();
    $versions = [];
    while($row = $result->fetch_assoc()) $versions[] = $row;
    $stmt->close();
    return $versions;
}

function AssetVersionGet($conn, $appKey, $assetType, $assetID, $versionID) {
    if(!$conn) return null;
    $stmt = $conn->prepare(
        'SELECT * FROM assetautoversions WHERE appKey = ? AND assetType = ? AND assetID = ? AND versionID = ? LIMIT 1'
    );
    if(!$stmt) return null;
    $stmt->bind_param('siii', $appKey, $assetType, $assetID, $versionID);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc() ?: null;
    $stmt->close();
    return $row;
}

function AssetVersionRename($conn, $appKey, $assetType, $assetID, $versionID, $versionName) {
    if(!$conn) return false;
    $appKey = trim((string)$appKey);
    $assetType = intval($assetType);
    $assetID = intval($assetID);
    $versionID = intval($versionID);
    $versionName = trim((string)$versionName);
    $nameLength = function_exists('mb_strlen')
        ? mb_strlen($versionName, 'UTF-8')
        : strlen($versionName);
    if($appKey === '' || $assetType <= 0 || $assetID <= 0 || $versionID <= 0) return false;
    if($versionName === '' || $nameLength > 255) return false;

    $stmt = $conn->prepare(
        'UPDATE assetautoversions
         SET versionName = ?
         WHERE appKey = ? AND assetType = ? AND assetID = ? AND versionID = ?'
    );
    if(!$stmt) return false;
    $stmt->bind_param('ssiii', $versionName, $appKey, $assetType, $assetID, $versionID);
    $success = $stmt->execute();
    $affectedRows = $stmt->affected_rows;
    $stmt->close();
    if(!$success) return false;
    if($affectedRows === 1) return true;

    // MySQL reports zero affected rows when the submitted name already matches.
    $existing = AssetVersionGet($conn, $appKey, $assetType, $assetID, $versionID);
    return $existing !== null && (string)$existing['versionName'] === $versionName;
}

function AssetVersionDeleteAndReparent($conn, $appKey, $assetType, $assetID, $versionID) {
    $versions = AssetVersionList($conn, $appKey, $assetType, $assetID);
    $byID = [];
    foreach($versions as $version) $byID[intval($version['versionID'])] = $version;
    $versionID = intval($versionID);
    if(!isset($byID[$versionID])) return false;

    $target = $byID[$versionID];
    $newParentID = $target['parentVersionID'] === null ? null : intval($target['parentVersionID']);
    $newParent = $newParentID !== null && isset($byID[$newParentID]) ? $byID[$newParentID] : null;
    $newParentConfig = $newParent ? AssetVersionDecodeConfig($newParent['assetJSON']) : null;

    $childrenByParent = [];
    foreach($versions as $version) {
        if($version['parentVersionID'] === null) continue;
        $parentID = intval($version['parentVersionID']);
        $childrenByParent[$parentID][] = intval($version['versionID']);
    }

    $descendantIDs = [];
    $queue = $childrenByParent[$versionID] ?? [];
    while(!empty($queue)) {
        $descendantID = array_shift($queue);
        $descendantIDs[] = $descendantID;
        foreach($childrenByParent[$descendantID] ?? [] as $childID) $queue[] = $childID;
    }

    $updateChild = $conn->prepare(
        'UPDATE assetautoversions
         SET parentVersionID = ?, depth = ?, distanceFromParent = ?, deltaJSON = ?
         WHERE versionID = ? AND appKey = ? AND assetType = ? AND assetID = ?'
    );
    if(!$updateChild) return false;
    foreach($childrenByParent[$versionID] ?? [] as $childID) {
        $child = $byID[$childID];
        $childConfig = AssetVersionDecodeConfig($child['assetJSON']);
        $delta = $newParentConfig === null
            ? ['distance' => 0, 'identities' => [], 'zones' => []]
            : AssetVersionDifference($newParentConfig, $childConfig);
        $newDepth = $newParent ? intval($newParent['depth']) + 1 : 0;
        $newDistance = intval($delta['distance']);
        $deltaJSON = json_encode($delta, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $updateChild->bind_param(
            'iiisisii',
            $newParentID,
            $newDepth,
            $newDistance,
            $deltaJSON,
            $childID,
            $appKey,
            $assetType,
            $assetID
        );
        if(!$updateChild->execute()) {
            $updateChild->close();
            return false;
        }
    }
    $updateChild->close();

    // Every descendant becomes one level shallower. Direct children were set above.
    $updateDepth = $conn->prepare(
        'UPDATE assetautoversions SET depth = depth - 1
         WHERE versionID = ? AND appKey = ? AND assetType = ? AND assetID = ?'
    );
    if(!$updateDepth) return false;
    foreach($descendantIDs as $descendantID) {
        if(in_array($descendantID, $childrenByParent[$versionID] ?? [], true)) continue;
        $updateDepth->bind_param('isii', $descendantID, $appKey, $assetType, $assetID);
        if(!$updateDepth->execute()) {
            $updateDepth->close();
            return false;
        }
    }
    $updateDepth->close();

    $delete = $conn->prepare(
        'DELETE FROM assetautoversions WHERE versionID = ? AND appKey = ? AND assetType = ? AND assetID = ?'
    );
    if(!$delete) return false;
    $delete->bind_param('isii', $versionID, $appKey, $assetType, $assetID);
    $success = $delete->execute() && $delete->affected_rows === 1;
    $delete->close();
    return $success;
}

?>

<?php

function ValidateMainDeckAddition($cardID) {
    if (!HellbreakDeckHasValidImage($cardID)) return false;
    $type = strtolower(trim((string)CardType($cardID)));
    return $type !== 'monster' && $type !== 'location';
}

function ValidateMonsterAddition($cardID) {
    $imageStatus = HellbreakDeckImageStatus($cardID);
    if (!$imageStatus['valid']) {
        HellbreakDeckLogSelection('Monster', $cardID, 'rejected-image', $imageStatus);
        return false;
    }
    $type = strtolower(trim((string)CardType($cardID)));
    if ($type !== 'monster') {
        HellbreakDeckLogSelection('Monster', $cardID, 'rejected-type', ['type' => $type]);
        return false;
    }
    global $gameName;
    try {
        SetAssetKeyIdentifier(1, $gameName, 1, $cardID);
    } catch (Throwable $e) {
        HellbreakDeckLogSelection('Monster', $cardID, 'key-indicator-exception', [
            'exception' => get_class($e),
            'message' => $e->getMessage(),
        ]);
        throw $e;
    }
    HellbreakDeckLogSelection('Monster', $cardID, 'accepted', ['type' => $type] + $imageStatus);
    return true;
}

function ValidateLocationAddition($cardID) {
    $imageStatus = HellbreakDeckImageStatus($cardID);
    if (!$imageStatus['valid']) {
        HellbreakDeckLogSelection('Location', $cardID, 'rejected-image', $imageStatus);
        return false;
    }
    $type = strtolower(trim((string)CardType($cardID)));
    if ($type !== 'location') {
        HellbreakDeckLogSelection('Location', $cardID, 'rejected-type', ['type' => $type]);
        return false;
    }
    $locations = &GetLocation(1);
    $activeLocations = 0;
    foreach ($locations as $location) {
        if ($location === null || !empty($location->removed)) continue;
        ++$activeLocations;
    }
    if ($activeLocations >= 2) {
        HellbreakDeckLogSelection('Location', $cardID, 'rejected-limit', ['activeLocations' => $activeLocations]);
        return false;
    }
    global $gameName;
    try {
        SetAssetKeyIdentifier(1, $gameName, 2, $cardID);
    } catch (Throwable $e) {
        HellbreakDeckLogSelection('Location', $cardID, 'key-indicator-exception', [
            'exception' => get_class($e),
            'message' => $e->getMessage(),
        ]);
        throw $e;
    }
    HellbreakDeckLogSelection('Location', $cardID, 'accepted', [
        'type' => $type,
        'activeLocations' => $activeLocations,
    ] + $imageStatus);
    return true;
}

function HellbreakDeckHasValidImage($cardID) {
    $status = HellbreakDeckImageStatus($cardID);
    return $status['valid'];
}

function HellbreakDeckImageStatus($cardID) {
    $safeID = preg_match('/^[A-Za-z0-9_-]+$/', (string)$cardID) === 1;
    $reviewStatus = function_exists('CardReviewStatus') ? (string)CardReviewStatus($cardID) : '';
    $image = __DIR__ . '/../../HellbreakSim/concat/' . $cardID . '.webp';
    $exists = $safeID && is_file($image);
    $size = $exists ? intval(filesize($image)) : 0;
    return [
        'valid' => $safeID && $reviewStatus !== 'rejected' && $exists && $size >= 8000,
        'safeID' => $safeID,
        'reviewStatus' => $reviewStatus,
        'imageExists' => $exists,
        'imageSize' => $size,
    ];
}

function HellbreakDeckLogSelection($zone, $cardID, $result, $details = []) {
    global $gameName;
    $payload = [
        'gameName' => (string)$gameName,
        'zone' => (string)$zone,
        'cardID' => (string)$cardID,
        'result' => (string)$result,
        'details' => is_array($details) ? $details : [],
    ];
    $encoded = json_encode($payload, JSON_UNESCAPED_SLASHES);
    error_log('[HellbreakDeck selection] ' . ($encoded === false ? 'encode-failed' : $encoded));
}

function GameAfterEngineAction($action, $result) {
    if (intval($action['mode'] ?? 0) !== 10002) return;
    $parts = explode('!', (string)($action['cardID'] ?? ''));
    $operation = (string)($parts[1] ?? '');
    $destination = (string)($parts[2] ?? '');
    if (!in_array($destination, ['myMonster', 'myLocation'], true)) return;

    $zone = GetZone($destination);
    $cards = [];
    if (is_array($zone)) {
        foreach ($zone as $card) {
            if (!is_object($card) || !empty($card->removed)) continue;
            $cards[] = (string)($card->CardID ?? '');
        }
    }
    HellbreakDeckLogSelection($destination, '', 'post-action-zone', [
        'operation' => $operation,
        'actionSuccess' => !empty($result['success']),
        'activeCards' => $cards,
    ]);
}

?>

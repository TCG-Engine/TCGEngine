<?php

function ValidateMainDeckAddition($cardID) {
    if (!HellbreakDeckHasValidImage($cardID)) return false;
    $type = strtolower(trim((string)CardType($cardID)));
    return $type !== 'monster' && $type !== 'location';
}

function ValidateMonsterAddition($cardID) {
    if (!HellbreakDeckHasValidImage($cardID)) return false;
    if (strtolower(trim((string)CardType($cardID))) !== 'monster') return false;
    global $gameName;
    SetAssetKeyIdentifier(1, $gameName, 1, $cardID);
    return true;
}

function ValidateLocationAddition($cardID) {
    if (!HellbreakDeckHasValidImage($cardID)) return false;
    if (strtolower(trim((string)CardType($cardID))) !== 'location') return false;
    global $gameName;
    SetAssetKeyIdentifier(1, $gameName, 2, $cardID);
    return true;
}

function HellbreakDeckHasValidImage($cardID) {
    if (!preg_match('/^[A-Za-z0-9_-]+$/', (string)$cardID)) return false;
    if (function_exists('CardReviewStatus') && CardReviewStatus($cardID) === 'rejected') return false;
    $image = __DIR__ . '/../../HellbreakSim/concat/' . $cardID . '.webp';
    return is_file($image) && filesize($image) >= 8000;
}

?>

<?php

function ValidateMainDeckAddition($cardID) {
    $imageStatus = HellbreakDeckImageStatus($cardID);
    if (!$imageStatus['valid']) {
        HellbreakDeckLogSelection('MainDeck', $cardID, 'rejected-image', $imageStatus);
        return false;
    }
    $type = strtolower(trim((string)CardType($cardID)));
    // Fail CLOSED on an unknown type. This was a purely NEGATIVE test ("not a monster and not a
    // location"), so when the card dictionary came back empty and every CardType() returned '',
    // every card became a legal main-deck card while the Monster/Location slots -- which use
    // positive tests -- silently refused everything. The builder half-worked for three days.
    // Rejecting here means blank card data breaks the FIRST click on ANY card, loudly.
    if ($type === '') {
        HellbreakDeckLogSelection('MainDeck', $cardID, 'rejected-unknown-type', [
            'hint' => 'CardType() returned nothing - the card dictionary is empty or was not loaded.',
        ]);
        return false;
    }
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
    $locations = &GetLocation(1);
    $activeLocations = 0;
    foreach ($locations as $location) {
        if ($location === null || !empty($location->removed)) continue;
        ++$activeLocations;
    }
    if ($activeLocations >= 2) return false;
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

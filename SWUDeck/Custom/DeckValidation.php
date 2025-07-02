<?php

function ValidateMainDeckAddition($cardID) {
    $deck = &GetMainDeck(1);
    $numCard = 0;
    $cardMax = 3;
    if($cardID == "2177194044") {
        $cardMax = 15;
    }
    foreach($deck as $card) {
        if($card->CardID == $cardID && !$card->Removed()) {
            $numCard++;
        }
    }
    $sideboard = &GetSideboard(1);
    foreach($sideboard as $card) {
        if($card->CardID == $cardID && !$card->Removed()) {
            $numCard++;
        }
    }
    return $numCard < $cardMax;
}

function ValidateLeaderAddition($cardID) {
    global $gameName;
    SetAssetKeyIdentifier(1, $gameName, 1, $cardID);
    return true; 
}

function ValidateBaseAddition($cardID) {
    global $gameName;
    SetAssetKeyIdentifier(1, $gameName, 2, $cardID);
    return true;
}

?>
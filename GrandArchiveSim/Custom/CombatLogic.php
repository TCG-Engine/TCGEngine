<?php
/**
 * Combat logic for handling attacks, damage calculation, and combat-related effects
 * This file contains functions that determine how combat interactions resolve
 */

function BeginCombatPhase($actionCard) {
    $turnPlayer = GetTurnPlayer();
    $obj = &GetZoneObject($actionCard);
    //Combat 1.c: Check if the attacking card has 0 or less power. If it does, it cannot attack.
    if(ObjectCurrentPower($obj) <= 0) {
        SetFlashMessage("Cannot attack with a card that has 0 or less power.");
        return false;
    }
    //Combat 1.d: Check if the attacking card is exhausted. If it is, it cannot attack.
    if($obj->Status != 2) {
        SetFlashMessage("Card must be exhausted to attack.");
        return false;
    }
    ExhaustCard($turnPlayer, $actionCard);
    return true;
}

?>
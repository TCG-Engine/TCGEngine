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
    //Combat 1.e: If there are any additional costs imposed for declaring attacks, they must be paid as attacks are being declared. If they can’t be paid, the attack can’t be declared.
    
    //Combat 1.h: Players can't declare attacks on their first turn unless they are the last player in the first turn cycle
    
    //Combat 2.a: Attack cards may be played without a valid target, but they will immediately fizzle

    //Combat 2.b: Attack declarations from allies and champions must specify the attack target during declaration. If there is no valid target, the attack cannot be declared.
    ChooseValidAttackTarget($actionCard);
    return true;
}

function ChooseValidAttackTarget($actionCard) {
    $obj = &GetZoneObject($actionCard);
    $player = GetTurnPlayer();
    DecisionQueueController::AddDecision($player, "MZCHOOSE", implode("&", ZoneSearch("theirField", ["ALLY", "CHAMPION"])), 100);
    DecisionQueueController::AddDecision($player, "CUSTOM", "AttackTargetChosen|" . $actionCard, 100);
}

$customDQHandlers["AttackTargetChosen"] = function($player, $parts, $lastDecision) {
    $attacker = &GetZoneObject($parts[0]);
    $target = &GetZoneObject($lastDecision);
    DealDamage($player, $parts[0], $lastDecision, CardPower($attacker->CardID));
};

function OnDealDamage($player, $source, $target, $amount) {
    $targetObj = &GetZoneObject($target);
    $targetObj->Damage += $amount;
}

?>
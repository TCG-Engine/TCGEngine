<?php
/**
 * Materialize logic for handling card materialization, resource generation, and related effects
 * This file contains functions that determine how materialization interactions resolve
 */

function MaterializePhase() {
    // Materialize phase
    SetFlashMessage("Materialize Phase");
    MaterializeChoice();
}

function MaterializeChoice($ignoreCost = false) {
    $turnPlayer = GetTurnPlayer();
    $material = &GetMaterial($turnPlayer);
    DecisionQueueController::AddDecision($turnPlayer, "MZMAYCHOOSE", ZoneObjMZIndices($material, "myMaterial"), 1);
    $handlerParam = $ignoreCost ? "MATERIALIZE|NOCOST" : "MATERIALIZE";
    DecisionQueueController::AddDecision($turnPlayer, "CUSTOM", $handlerParam, 1);
}

$customDQHandlers["MATERIALIZE"] = function($player, $parts, $lastDecision)
{
    $ignoreCost = isset($parts[0]) && $parts[0] === "NOCOST";
    //First pay memory cost (unless cost is being ignored)
    $materializeCard = &GetZoneObject($lastDecision);
    $memoryCost = $ignoreCost ? 0 : CardMemoryCost($materializeCard);
    if($memoryCost > 0) {
        DecisionQueueController::StoreVariable("MemoryCost", $memoryCost);
        $floatingIndices = implode("&", ZoneSearch("myGraveyard", floatingMemoryOnly:true));
        if($floatingIndices != "") {
            DecisionQueueController::AddDecision($player, "MZMAYCHOOSE", $floatingIndices, 1);
            DecisionQueueController::AddDecision($player, "CUSTOM", "PAYFLOATING|" . $memoryCost, 1);
        }
        DecisionQueueController::AddDecision($player, "CUSTOM", "FINISHPAYMATERIALIZE", 2, dontSkipOnPass:1);
    }
    //Then materialize the card
    Materialize($player, $lastDecision);
};

$customDQHandlers["PAYFLOATING"] = function($player, $parts, $lastDecision) {
    MZMove($player, $lastDecision, "myBanish");
    $toPay = $parts[0];
    --$toPay;
    DecisionQueueController::StoreVariable("MemoryCost", $toPay);
    if($toPay > 0) {
        $floatingIndices = implode("&", ZoneSearch("myGraveyard", floatingMemoryOnly:true));
        DecisionQueueController::AddDecision($player, "MZMAYCHOOSE", $floatingIndices, 1);
        DecisionQueueController::AddDecision($player, "CUSTOM", "PAYFLOATING|" . $toPay, 1);
    }
    return $toPay;
};

$customDQHandlers["FINISHPAYMATERIALIZE"] = function($player, $parts, $lastDecision) {
    $memoryCost = DecisionQueueController::GetVariable("MemoryCost");
    echo("Finished paying memory cost, remaining cost: " . $memoryCost);
    for($i = 0; $i < $memoryCost; ++$i) {
        MZMove($player, "myMemory-" . $i, "myBanish");//TODO: Make random
    }
    DecisionQueueController::ClearVariable("MemoryCost");
};

function DoMaterialize($player, $mzCard) {
    global $customDQHandlers;
    $sourceObject = &GetZoneObject($mzCard);
    $sourceId = $sourceObject->CardID;
    //TODO: Handle lineage mechanics
    MZMove($player, $mzCard, "myField");
    // Track that a champion leveled up this turn (for Invigorated Slash etc.)
    // Only set the flag when materializing a champion
    if(PropertyContains(CardType($sourceId), "CHAMPION")) {
        AddGlobalEffects($player, "LEVELED_UP_THIS_TURN");
    }
}
?>
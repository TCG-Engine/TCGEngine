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

    if(PropertyContains(CardType($sourceId), "CHAMPION")) {
        // Champion lineage: find existing champion on the field
        $field = &GetField($player);
        $existingChampionIdx = -1;
        $existingSubcards = [];
        $existingChampionCardID = null;
        $existingDamage = 0;
        $existingCounters = [];

        for($i = 0; $i < count($field); ++$i) {
            if(!$field[$i]->removed && PropertyContains(CardType($field[$i]->CardID), "CHAMPION") && $field[$i]->Controller == $player) {
                $existingChampionIdx = $i;
                $existingChampionCardID = $field[$i]->CardID;
                $existingSubcards = is_array($field[$i]->Subcards) ? $field[$i]->Subcards : [];
                $existingDamage = $field[$i]->Damage;
                $existingCounters = is_array($field[$i]->Counters) ? $field[$i]->Counters : [];
                break;
            }
        }

        // Build new lineage: old champion's CardID prepended to its subcards
        $newSubcards = [];
        if($existingChampionCardID !== null) {
            $newSubcards = array_merge([$existingChampionCardID], $existingSubcards);
            // Remove old champion from field without triggering OnLeaveField/AllyDestroyed
            $field[$existingChampionIdx]->removed = true;
        }

        // Move new champion to field
        $newObj = MZMove($player, $mzCard, "myField");

        // Transfer lineage (subcards), damage, and counters from old champion
        if(!empty($newSubcards)) {
            $newObj->Subcards = $newSubcards;
        }
        if($existingDamage > 0) {
            $newObj->Damage = $existingDamage;
        }
        if(!empty($existingCounters)) {
            // Merge counters from old champion (e.g. enlighten counters carry over)
            foreach($existingCounters as $counterType => $counterVal) {
                if(!isset($newObj->Counters[$counterType])) {
                    $newObj->Counters[$counterType] = $counterVal;
                } else {
                    $newObj->Counters[$counterType] += $counterVal;
                }
            }
        }

        // Track that a champion leveled up this turn (for Invigorated Slash etc.)
        AddGlobalEffects($player, "LEVELED_UP_THIS_TURN");
    } else {
        MZMove($player, $mzCard, "myField");
    }
}
?>
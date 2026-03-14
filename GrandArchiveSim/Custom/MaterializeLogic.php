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

    // The Elysian Astrolabe (4nmxqsm4o9): can only materialize if it's the last card in material deck
    if($materializeCard->CardID === "4nmxqsm4o9") {
        $matZone = GetZone("myMaterial");
        $remaining = 0;
        foreach($matZone as $mObj) { if(!$mObj->removed) $remaining++; }
        if($remaining > 1) return; // Not the last card — block materialize
    }

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

        // Tristan, Shadowreaver (4upufooz13) — Tristan Lineage: when she levels up, draw 2 cards
        // The new champion is already on the field; check if the lineage contains Shadowreaver
        $field = &GetField($player);
        for($tli = 0; $tli < count($field); ++$tli) {
            if(!$field[$tli]->removed && PropertyContains(EffectiveCardType($field[$tli]), "CHAMPION") && $field[$tli]->Controller == $player) {
                if(ChampionHasInLineage($player, "4upufooz13")) {
                    Draw($player, amount: 2);
                }
                break;
            }
        }

        // Sanctum of Esoteric Truth (k45swaf8ur): whenever your champion levels up,
        // you may put two cards from hand/memory on bottom of deck, then draw two.
        $field = &GetField($player);
        for($si = 0; $si < count($field); ++$si) {
            if(!$field[$si]->removed && $field[$si]->CardID === "k45swaf8ur" && !HasNoAbilities($field[$si])) {
                $handAndMemory = array_merge(ZoneSearch("myHand", forPlayer: $player), ZoneSearch("myMemory", forPlayer: $player));
                if(count($handAndMemory) >= 2) {
                    DecisionQueueController::AddDecision($player, "MZMAYCHOOSE", implode("&", $handAndMemory), 1,
                        tooltip:"Sanctum:_Put_card_on_bottom_of_deck_(1/2)?");
                    DecisionQueueController::AddDecision($player, "CUSTOM", "SanctumOfEsotericTruth1", 1);
                }
                break;
            }
        }
    } else {
        MZMove($player, $mzCard, "myField");
    }

    // --- Domain Upkeep: "Whenever you materialize a card, sacrifice [domain]" ---
    // After any materialize, check if the player controls domains with materialize-sacrifice upkeep.
    // Domains tagged with NO_UPKEEP (via Right of Realm) skip this trigger.
    DomainMaterializeSacrifice($player);
}

/**
 * Domains with "Whenever you materialize a card, sacrifice [domain name]" upkeep.
 * Maps domain CardID → true for all domains that have this trigger.
 */
$DOMAIN_MATERIALIZE_SACRIFICE = [
    "41WnFOT5YS" => true, // Avalon, Cursed Isle
    "IyM7IBCQeb" => true, // Varuck, Smoldering Spire
    "R9UFbI4Fsh" => true, // Camelot, Impenetrable
];

/**
 * After a materialize action, sacrifice any domains the player controls that have
 * "Whenever you materialize a card, sacrifice [domain]" upkeep.
 * Domains tagged with the NO_UPKEEP TurnEffect are exempt (Right of Realm).
 */
function DomainMaterializeSacrifice($player) {
    global $DOMAIN_MATERIALIZE_SACRIFICE;
    $field = &GetField($player);
    // Iterate backwards since sacrifice removes cards from the field
    for($i = count($field) - 1; $i >= 0; --$i) {
        if($field[$i]->removed) continue;
        $cardID = $field[$i]->CardID;
        if(isset($DOMAIN_MATERIALIZE_SACRIFICE[$cardID])) {
            // Check for NO_UPKEEP tag (Right of Realm exemption — permanent)
            if(in_array("NO_UPKEEP", $field[$i]->TurnEffects)) {
                continue; // NO_UPKEEP persists — domain is permanently exempt from materialize-sacrifice
            }
            DoSacrificeFighter($player, "myField-" . $i);
        }
    }
    DecisionQueueController::CleanupRemovedCards();
}
?>
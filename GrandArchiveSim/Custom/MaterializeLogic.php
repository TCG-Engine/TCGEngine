<?php
/**
 * Materialize logic for handling card materialization, resource generation, and related effects
 * This file contains functions that determine how materialization interactions resolve
 */

function MaterializePhase() {
    // Materialize phase
    SetFlashMessage("Materialize Phase");
    MaterializeChoice();
    // Eventide Spear (xjkdokzfd9): [CB:Warrior] may also activate from material deck if opponent has 2+ rested units
    DecisionQueueController::AddDecision(GetTurnPlayer(), "CUSTOM", "EVENTIDE_MATERIAL_CHECK", 1);
}

function MaterializeChoice($ignoreCost = false) {
    $turnPlayer = GetTurnPlayer();
    $material = &GetMaterial($turnPlayer);
    DecisionQueueController::AddDecision($turnPlayer, "MZMAYCHOOSE", ZoneObjMZIndices($material, "myMaterial"), 1);
    $handlerParam = $ignoreCost ? "MATERIALIZE|NOCOST" : "MATERIALIZE";
    DecisionQueueController::AddDecision($turnPlayer, "CUSTOM", $handlerParam, 1);
}

$customDQHandlers["EVENTIDE_MATERIAL_CHECK"] = function($player, $parts, $lastDecision) {
    // Eventide Spear (xjkdokzfd9): [CB:Warrior] if opponent controls 2+ rested units, offer extra materialize
    if(!IsClassBonusActive($player, ["WARRIOR"])) return;
    $material = GetMaterial($player);
    $eventideMZ = null;
    for($i = 0; $i < count($material); ++$i) {
        if(!$material[$i]->removed && $material[$i]->CardID === "xjkdokzfd9") {
            $eventideMZ = "myMaterial-" . $i;
            break;
        }
    }
    if($eventideMZ === null) return;
    global $playerID;
    $oppFieldZone = ($player == $playerID) ? "theirField" : "myField";
    $oppField = GetZone($oppFieldZone);
    $restedCount = 0;
    foreach($oppField as $rObj) {
        if(!$rObj->removed && $rObj->Status == 1) $restedCount++;
    }
    if($restedCount < 2) return;
    DecisionQueueController::AddDecision($player, "MZMAYCHOOSE", $eventideMZ, 1,
        tooltip: "Activate_Eventide_Spear_from_material_deck?");
    DecisionQueueController::AddDecision($player, "CUSTOM", "MATERIALIZE|NOCOST", 1);
};

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

        // Ravenous Pyre (pjdsfqbgit): Whenever a champion you don't control levels up, deal 2 damage to it.
        $opponent = ($player == 1) ? 2 : 1;
        $oppField = &GetField($opponent);
        for($rp = 0; $rp < count($oppField); ++$rp) {
            if(!$oppField[$rp]->removed && $oppField[$rp]->CardID === "pjdsfqbgit" && !HasNoAbilities($oppField[$rp])) {
                // Find the champion that just leveled up (the player's champion)
                $champField = &GetField($player);
                for($ci = 0; $ci < count($champField); ++$ci) {
                    if(!$champField[$ci]->removed && PropertyContains(EffectiveCardType($champField[$ci]), "CHAMPION")
                       && $champField[$ci]->Controller == $player) {
                        global $playerID;
                        $champMZ = ($player == $playerID) ? "myField-" . $ci : "theirField-" . $ci;
                        DealDamage($opponent, "pjdsfqbgit", $champMZ, 2);
                        break;
                    }
                }
                break; // Only one Ravenous Pyre triggers
            }
        }

        // Liminal Guide (0xayo7mk1w): whenever your champion levels up, return from GY to field as ephemeral + draw
        {
            global $playerID;
            $gyZone = ($player == $playerID) ? "myGraveyard" : "theirGraveyard";
            $gy = &GetZone($gyZone);
            for($lgi = count($gy) - 1; $lgi >= 0; --$lgi) {
                if(!$gy[$lgi]->removed && $gy[$lgi]->CardID === "0xayo7mk1w") {
                    $lgMZ = $gyZone . "-" . $lgi;
                    $newObj = MZMove($player, $lgMZ, "myField");
                    if($newObj !== null) {
                        MakeEphemeral($newObj->GetMzID());
                        Draw($player, 1);
                    }
                    break; // Only one Liminal Guide triggers per level-up
                }
            }
        }

        // Recurring Invocation (iyhlctxcrq): [CB MAGE] whenever your champion levels up,
        // may banish from GY and pay (1) → Empower 2
        if(IsClassBonusActive($player, ["MAGE"])) {
            global $playerID;
            $gyZone = ($player == $playerID) ? "myGraveyard" : "theirGraveyard";
            $gy = &GetZone($gyZone);
            for($ri = count($gy) - 1; $ri >= 0; --$ri) {
                if(!$gy[$ri]->removed && $gy[$ri]->CardID === "iyhlctxcrq") {
                    $riMZ = $gyZone . "-" . $ri;
                    DecisionQueueController::AddDecision($player, "YESNO", "-", 1,
                        tooltip:"Banish_Recurring_Invocation_and_pay_(1)_to_Empower_2?");
                    DecisionQueueController::AddDecision($player, "CUSTOM",
                        "RecurringInvocationLevelUp|$riMZ", 1);
                    break;
                }
            }
        }

        // Devoted Martyr (p16w5j93mk): [CB CLERIC] whenever your champion levels up,
        // may banish from GY → Recover 2
        if(IsClassBonusActive($player, ["CLERIC"])) {
            global $playerID;
            $gyZone = ($player == $playerID) ? "myGraveyard" : "theirGraveyard";
            $gy = &GetZone($gyZone);
            for($dmi = count($gy) - 1; $dmi >= 0; --$dmi) {
                if(!$gy[$dmi]->removed && $gy[$dmi]->CardID === "p16w5j93mk") {
                    $dmMZ = $gyZone . "-" . $dmi;
                    DecisionQueueController::AddDecision($player, "YESNO", "-", 1,
                        tooltip:"Banish_Devoted_Martyr_from_graveyard_to_Recover_2?");
                    DecisionQueueController::AddDecision($player, "CUSTOM",
                        "DevotedMartyrLevelUp|$dmMZ", 1);
                    break;
                }
            }
        }
    } else {
        MZMove($player, $mzCard, "myField");
    }

    // Duplicitous Replication (owq8s5fefw): if the opponent has this effect active and
    // the card that just entered the field is REGALIA, summon a token copy on the opponent's field.
    $opponent = ($player == 1) ? 2 : 1;
    if(GlobalEffectCount($opponent, "owq8s5fefw") > 0) {
        if(PropertyContains(CardType($sourceId), "REGALIA")) {
            RemoveGlobalEffect($opponent, "owq8s5fefw");
            MZAddZone($opponent, "myField", $sourceId);
        }
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
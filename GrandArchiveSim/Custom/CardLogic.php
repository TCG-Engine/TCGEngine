<?php

/**
 * Card-specific logic for special abilities and effects
 * This file handles individual card mechanics that don't fit into standard game flow
 */

/**
 * Get extended adjacent zones based on card-specific adjacency rules
 * Some cards can treat non-adjacent zones as adjacent
 */
function GetCardSpecificAdjacentZones($cardZone, $cardID, $includeDiagonals) {
    // Start with standard adjacent zones
    $zones = AdjacentZones($cardZone, $includeDiagonals);
    
    switch($cardID) {
        case "DNBF2HDRDV": // Deeprock Delver - treats other non-Gate back row squares as adjacent
            $cardZoneName = explode("-", $cardZone)[0];
            $backRowZones = GetBackRow();
            $gatesZone1 = GetGates(1);
            $gatesZone2 = GetGates(2);
            $nonGateBackRowZones = array_filter($backRowZones, function($zone) use ($gatesZone1, $gatesZone2) {
                return $zone !== $gatesZone1 && $zone !== $gatesZone2;
            });
            
            if(in_array($cardZoneName, $nonGateBackRowZones)) {
                foreach($nonGateBackRowZones as $zone) {
                    if($zone !== $cardZoneName && !in_array($zone, $zones)) {
                        array_push($zones, $zone);
                    }
                }
            }
            break;
        default:
            break;
    }
    
    return $zones;
}

/**
 * Get additional attack targets based on card-specific abilities
 * Returns an array of zone names that can be attacked due to card effects
 */
function GetCardSpecificAttackTargets($player, $cardZone, $cardID, $includeAttack, $includeDiagonals) {
    $additionalTargets = [];
    
    switch($cardID) {
        case "DNBF3HNLKS": // Nihl'othrakis - can attack enemy fighters adjacent to ANY friendly fighter
            if(!$includeAttack) break;
            
            $allBattlefields = ["BG1", "BG2", "BG3", "BG4", "BG5", "BG6", "BG7", "BG8", "BG9"];
            foreach($allBattlefields as $bf) {
                $bfTop = GetTopCard($bf);
                if($bfTop !== null && $bfTop->Controller == $player && $bf !== $cardZone) {
                    $adjacentToBF = AdjacentZones($bf, $includeDiagonals);
                    foreach($adjacentToBF as $zone) {
                        $zoneArr = &GetZone($zone);
                        if(count($zoneArr) > 1) {
                            $defenderCard = $zoneArr[count($zoneArr) - 1];
                            if($defenderCard->Controller != $player) {
                                if(CanAttack($player, $cardZone, $zone)) {
                                    if(!in_array($zone, $additionalTargets)) {
                                        array_push($additionalTargets, $zone);
                                    }
                                }
                            }
                        }
                    }
                }
            }
            break;
        
        default:
            break;
    }
    
    return $additionalTargets;
}

/**
 * Handle Stoneseeker's draw ability
 * Player looks at top 2 cards, chooses one to draw, puts other on bottom of deck
 */
function DoStoneSeekerDraw($player, $amount=1) {
    $zone = &GetDeck($player);
    $hand = &GetHand($player);
    for($i = 0; $i < $amount; ++$i) {
        if(count($zone) == 0) {
            return;
        }
        // We need at least 1 card to draw
        if(count($zone) == 1) {
            $card = array_shift($zone);
            array_push($hand, $card);
            continue;
        }
        // Add top 2 cards to temp zone so they can be displayed for selection
        MZMove($player, "myDeck-0", "myTempZone");
        MZMove($player, "myDeck-1", "myTempZone");
        DecisionQueueController::AddDecision($player, "MZCHOOSE", "myTempZone-0&myTempZone-1", 1, "Choose_a_card_to_draw");
        DecisionQueueController::AddDecision($player, "CUSTOM", "StoneSeekerDrawChoice", 1);
    }
}

// Spirit Blade: Dispersion (7Rsid05Cf6): Remove durability counters from Sword weapons,
// banish them, split damage equal to total counters among chosen units.
function SpiritBladeDispersion($player) {
    global $playerID;
    $zone = $player == $playerID ? "myField" : "theirField";
    $field = GetZone($zone);
    $swords = [];
    for($i = 0; $i < count($field); ++$i) {
        if($field[$i]->removed) continue;
        if(PropertyContains(EffectiveCardType($field[$i]), "WEAPON")
            && PropertyContains(EffectiveCardSubtypes($field[$i]), "SWORD")
            && GetCounterCount($field[$i], "durability") > 0) {
            $swords[] = $zone . "-" . $i;
        }
    }
    if(empty($swords)) return;
    DecisionQueueController::AddDecision($player, "MZMAYCHOOSE", implode("&", $swords), 1,
        tooltip:"Choose_Sword_weapon_to_remove_durability_counters_(Spirit_Blade:_Dispersion)");
    DecisionQueueController::AddDecision($player, "CUSTOM", "SpiritBladeChooseSword|0", 1);
}

// Pole-armed Steed (7j79KANWEP): [Jin Bonus] On Enter: Materialize a Polearm regalia from material deck.
function PoleArmedSteedEnter($player) {
    global $playerID;
    // Check Jin Bonus: champion name starts with "Jin"
    $zone = $player == $playerID ? "myField" : "theirField";
    $field = GetZone($zone);
    $isJin = false;
    foreach($field as $fObj) {
        if(!$fObj->removed && PropertyContains(EffectiveCardType($fObj), "CHAMPION")) {
            if(strpos(CardName($fObj->CardID), "Jin") === 0) $isJin = true;
            break;
        }
    }
    if(!$isJin) return;
    $matZone = $player == $playerID ? "myMaterial" : "theirMaterial";
    $material = GetZone($matZone);
    $polearms = [];
    for($i = 0; $i < count($material); ++$i) {
        if($material[$i]->removed) continue;
        if(PropertyContains(CardType($material[$i]->CardID), "REGALIA")
            && PropertyContains(CardSubtypes($material[$i]->CardID), "POLEARM")) {
            $polearms[] = $matZone . "-" . $i;
        }
    }
    if(empty($polearms)) return;
    DecisionQueueController::AddDecision($player, "MZCHOOSE", implode("&", $polearms), 1,
        tooltip:"Materialize_a_Polearm_regalia_(Pole-armed_Steed)");
    DecisionQueueController::AddDecision($player, "CUSTOM", "MATERIALIZE|NOCOST", 1);
}

// Seep Into the Mind (7mHiO4YySz): Target opponent puts 3 sheen counters on a unit they control.
// [Merlin Bonus][Sheen 6+] Look at that opponent's memory and discard a card from it.
function SeepIntoTheMind($player) {
    $opponent = ($player == 1) ? 2 : 1;
    $oppUnits = array_merge(
        ZoneSearch("theirField", ["ALLY", "CHAMPION"])
    );
    if(empty($oppUnits)) return;
    DecisionQueueController::AddDecision($opponent, "MZCHOOSE", implode("&", $oppUnits), 1,
        tooltip:"Put_3_sheen_counters_on_a_unit_you_control_(Seep_Into_the_Mind)");
    DecisionQueueController::AddDecision($opponent, "CUSTOM", "SeepIntoTheMindSheen|$player", 1);
}


// =============================================================================
// Foster Keyword
// =============================================================================

/**
 * Check whether a field object has the Foster keyword.
 * Foster: "At the beginning of your recollection phase, if this ally hasn't been
 * dealt damage since the end of your previous turn, it becomes fostered."
 */
function HasFoster($obj) {
    if(HasNoAbilities($obj)) return false;
    // Unconditional Foster cards
    static $fosterCards = [
        "z4pyx8bd7o" => true, // Young Peacekeeper
        "kuz07nk45s" => true, // Forgelight Shieldmaiden
        "bqjdmthh88" => true, // City Protector
        "22tk3ir1o0" => true, // Peacekeeper Sentinel (alt)
        "lzsmw3rrii" => true, // Guardian Bulwark
        "xhi5jnsl7d" => true, // Embershield Keeper
        "zihslnhzj4" => true, // Cell Generator
        "8sugly4wif" => true, // Krustallan Patrol
    ];
    if(isset($fosterCards[$obj->CardID])) return true;
    // [Class Bonus] Foster cards
    static $fosterCBCards = [
        "mnu1xhs5jw" => ["GUARDIAN"], // Awakened Frostguard
        "1x97n2jnlt" => ["GUARDIAN"], // Guardian Scout
        "a3v1ybmvpb" => ["GUARDIAN"], // Sunglory Sentinel
        "3kwkn38b7v" => ["GUARDIAN"], // Tidebreaker Sentinel
        "y1utsihaxv" => ["GUARDIAN"], // Rilewind Sentinel
    ];
    if(isset($fosterCBCards[$obj->CardID])) {
        return IsClassBonusActive($obj->Controller, $fosterCBCards[$obj->CardID]);
    }
    return false;
}

/**
 * Check whether a field object is currently in the fostered state.
 */
function IsFostered($obj) {
    return in_array("FOSTERED", $obj->TurnEffects);
}

/**
 * Make an ally become fostered by adding the FOSTERED TurnEffect.
 * @param int    $player The controller.
 * @param string $mzID   The mzID of the ally.
 */
function BecomeFostered($player, $mzID) {
    AddTurnEffect($mzID, "FOSTERED");
}

/**
 * Dispatch OnFoster triggered abilities for a card that just became fostered.
 * Called by RecollectionPhase when a Foster ally transitions to fostered state.
 */
function OnFosterTrigger($player, $mzID) {
    global $onFosterAbilities;
    $obj = GetZoneObject($mzID);
    if($obj === null) return;
    $CardID = $obj->CardID;
    if(HasNoAbilities($obj)) return;
    if(isset($onFosterAbilities) && isset($onFosterAbilities[$CardID . ":0"])) {
        $onFosterAbilities[$CardID . ":0"]($player);
    }
}

/**
 * Check whether a field object is currently distant.
 * Distant: "Units stay distant until the end of their controller's turn."
 * Sources:
 *   - TurnEffect "DISTANT" (until end of turn, standard source)
 *   - Freydis permanent distant: global forever effect makes all Ranger units always distant
 */
function IsDistant($obj) {
    if(HasNoAbilities($obj)) return false;
    if(in_array("DISTANT", $obj->TurnEffects)) return true;
    // Freydis permanent distant: Ranger units are always distant for the rest of the game
    if(GlobalEffectCount($obj->Controller, "FREYDIS_PERMANENT_DISTANT") > 0) {
        if(PropertyContains(EffectiveCardClasses($obj), "RANGER")) return true;
    }
    return false;
}

/**
 * Make a unit become distant by adding the DISTANT TurnEffect.
 * @param int    $player The acting player.
 * @param string $mzID   The mzID of the unit (e.g. "myField-2").
 */
function BecomeDistant($player, $mzID) {
    AddTurnEffect($mzID, "DISTANT");
    // Imperial Scout (nrow8iopvc): when this becomes distant, may mill 2
    $obj = GetZoneObject($mzID);
    if($obj !== null && $obj->CardID === "nrow8iopvc" && !HasNoAbilities($obj)) {
        $deck = ZoneSearch("myDeck");
        if(count($deck) >= 2) {
            DecisionQueueController::AddDecision($player, "YESNO", "-", 1, tooltip:"Put_top_2_cards_of_deck_into_graveyard?");
            DecisionQueueController::AddDecision($player, "CUSTOM", "ImperialScoutMill", 1);
        }
    }
    // Liu Bei, Oathkeeper (a53rqmuqxf): whenever another unit you control becomes distant,
    // Liu Bei becomes distant.
    if($obj === null || $obj->CardID !== "a53rqmuqxf") {
        global $playerID;
        $zone = $player == $playerID ? "myField" : "theirField";
        $field = GetZone($zone);
        foreach($field as $fi => $fObj) {
            if(!$fObj->removed && $fObj->CardID === "a53rqmuqxf" && !HasNoAbilities($fObj)) {
                $liuBeiMZ = $zone . "-" . $fi;
                if(!in_array("DISTANT", $fObj->TurnEffects)) {
                    AddTurnEffect($liuBeiMZ, "DISTANT");
                }
            }
        }
    }
    // Renascent Sharpshooter (gbnvtkm7rf): [Class Bonus] Whenever this becomes distant, draw a card into memory
    if($obj !== null && $obj->CardID === "gbnvtkm7rf" && !HasNoAbilities($obj)) {
        if(IsClassBonusActive($player, ["RANGER"])) {
            DrawIntoMemory($player, 1);
        }
    }
    // Whisperwind Compass (UXqhPZEq0X): [Class Bonus] Whenever a Ranger ally you control becomes distant,
    // if that ally has no buff counters, put a buff counter on it.
    if($obj !== null && PropertyContains(EffectiveCardType($obj), "ALLY")
        && PropertyContains(EffectiveCardSubtypes($obj), "RANGER")) {
        global $playerID;
        $compassZone = $obj->Controller == $playerID ? "myField" : "theirField";
        $compassField = GetZone($compassZone);
        foreach($compassField as $ci => $cObj) {
            if(!$cObj->removed && $cObj->CardID === "UXqhPZEq0X" && !HasNoAbilities($cObj)
                && IsClassBonusActive($obj->Controller, ["RANGER"])) {
                if(GetCounterCount($obj, "buff") == 0) {
                    AddCounters($obj->Controller, $mzID, "buff", 1);
                }
                break;
            }
        }
    }
    // Alizarin Longbowman (inQV2nZfdJ): [CB] Whenever this becomes distant, may have each player draw a card
    if($obj !== null && $obj->CardID === "inQV2nZfdJ" && !HasNoAbilities($obj)) {
        if(IsClassBonusActive($player, ["RANGER"])) {
            DecisionQueueController::AddDecision($player, "YESNO", "-", 1, tooltip:"Each_player_draws_a_card?");
            DecisionQueueController::AddDecision($player, "CUSTOM", "AlizarinLongbowmanDraw", 1);
        }
    }
}

/**
 * Dahlia OnAttack: finish after YesNo — put water card to GY or back on top of deck.
 */
function DahliaLookFinish($player, $answer) {
    if($answer === "YES") {
        $tempCards = ZoneSearch("myTempZone");
        if(!empty($tempCards)) {
            MZMove($player, $tempCards[0], "myGraveyard");
        }
    } else {
        PutTempZoneOnTopOfDeck($player);
    }
}

/**
 * Misteye Archer (m6c8xy4cje): look at top card of deck.
 * If water, offer to put into GY — if yes, become distant + prevent next 2 damage.
 */
function MisteyeArcherLook($player, $mzID) {
    $tempCards = ZoneSearch("myTempZone");
    if(empty($tempCards)) return;
    $topCard = GetZoneObject($tempCards[count($tempCards) - 1]);
    if($topCard !== null && CardElement($topCard->CardID) === "WATER") {
        DecisionQueueController::AddDecision($player, "YESNO", "-", 1, tooltip:"Put_water_card_into_graveyard?");
        DecisionQueueController::AddDecision($player, "CUSTOM", "MisteyeArcherFinish|" . $mzID, 1);
    } else {
        PutTempZoneOnTopOfDeck($player);
    }
}

?>


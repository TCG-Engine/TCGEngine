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

?>


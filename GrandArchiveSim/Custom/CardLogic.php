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
    // Radiant Origin of Ranger (tp7eVOsAHU): whenever a Ranger unit you control becomes distant,
    // put a training counter on each copy you control.
    if($obj !== null && (PropertyContains(EffectiveCardType($obj), "ALLY") || PropertyContains(EffectiveCardType($obj), "CHAMPION"))
        && PropertyContains(EffectiveCardClasses($obj), "RANGER")) {
        global $playerID;
        $trialZone = $obj->Controller == $playerID ? "myField" : "theirField";
        $trialField = GetZone($trialZone);
        foreach($trialField as $ti => $tObj) {
            if(!$tObj->removed && $tObj->CardID === "tp7eVOsAHU" && !HasNoAbilities($tObj)) {
                AddCounters($obj->Controller, $trialZone . "-" . $ti, "training", 1);
            }
        }
    }
    // Diana, Moonpiercer (v3vfjtwm7g): when Diana becomes distant, choose negate mode or Glimpse 2.
    if($obj !== null && $obj->CardID === "v3vfjtwm7g" && !HasNoAbilities($obj)) {
        DecisionQueueController::StoreVariable("DianaMoonpiercerMZ", $mzID);
        DecisionQueueController::AddDecision($player, "YESNO", "-", 1,
            tooltip:"Negate_targeting_activations?_(No=Glimpse_2)");
        DecisionQueueController::AddDecision($player, "CUSTOM", "DianaMoonpiercerChoice", 1);
    }
}

$customDQHandlers["DianaMoonpiercerChoice"] = function($player, $parts, $lastDecision) {
    $mzID = DecisionQueueController::GetVariable("DianaMoonpiercerMZ") ?? "";
    if($lastDecision !== "YES") {
        Glimpse($player, 2);
        return;
    }

    $currentTarget = DecisionQueueController::GetVariable("target") ?? "";
    if($mzID !== "" && $currentTarget === $mzID) {
        QueueNegateActivation($player, ['excludeController' => $player], "default", 2);
    }

    if($mzID !== "" && IsUnitDefending($mzID)) {
        $attackingPlayer = ($player == 1) ? 2 : 1;
        if(CountAvailableReservePayments($attackingPlayer) < 2) {
            EndCombat($player);
            return;
        }
        DecisionQueueController::AddDecision($attackingPlayer, "YESNO", "-", 1,
            tooltip:"Pay_(2)_to_continue_combat?");
        DecisionQueueController::AddDecision($attackingPlayer, "CUSTOM", "DianaMoonpiercerCombatPay|" . $player, 1);
    }
};

$customDQHandlers["DianaMoonpiercerCombatPay"] = function($attackingPlayer, $parts, $lastDecision) {
    $defendingPlayer = intval($parts[0] ?? (($attackingPlayer == 1) ? 2 : 1));
    if($lastDecision === "YES" && CountAvailableReservePayments($attackingPlayer) >= 2) {
        DecisionQueueController::AddDecision($attackingPlayer, "CUSTOM", "ReserveCard", 1);
        DecisionQueueController::AddDecision($attackingPlayer, "CUSTOM", "ReserveCard", 1);
        return;
    }
    EndCombat($defendingPlayer);
};

$customDQHandlers["CheetahOfBoundFuryTransform"] = function($player, $parts, $lastDecision) {
    if($lastDecision !== "YES") return;
    $mzID = DecisionQueueController::GetVariable("mzID");
    if($mzID === null || $mzID === "" || $mzID === "-") return;
    $obj = GetZoneObject($mzID);
    if($obj === null || $obj->removed || $obj->CardID !== "vvfwwa13y9") return;

    $owner = intval($obj->Owner ?? $obj->Controller ?? $player);
    global $playerID;
    $banishZone = $owner == $playerID ? "myBanish" : "theirBanish";
    $fieldZone = $owner == $playerID ? "myField" : "theirField";

    MZMove($owner, $mzID, $banishZone);
    DecisionQueueController::CleanupRemovedCards();

    $banishment = GetZone($banishZone);
    for($i = count($banishment) - 1; $i >= 0; --$i) {
        if($banishment[$i]->removed || $banishment[$i]->CardID !== "vvfwwa13y9") continue;
        $returned = MZMove($owner, $banishZone . "-" . $i, $fieldZone);
        if($returned !== null) {
            $fieldArr = GetZone($fieldZone);
            $newIdx = count($fieldArr) - 1;
            TransformCard($owner, $fieldZone . "-" . $newIdx);
        }
        break;
    }
};

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

// ============================================================================
// Coiled Fatestone (ulh4lplwqe) helpers
// ============================================================================

function CoiledFatestoneEnter($player) {
    // Discard two cards at random. For each fire element card discarded this way, draw a card.
    $hand = &GetHand($player);
    if(count($hand) === 0) return;
    $fireCount = 0;
    $discardCount = min(2, count($hand));
    for($i = 0; $i < $discardCount; ++$i) {
        $hand = &GetHand($player);
        if(count($hand) === 0) break;
        $randIdx = EngineRandomInt(0, count($hand) - 1);
        $discObj = $hand[$randIdx];
        $element = CardElement($discObj->CardID);
        if($element === "FIRE") ++$fireCount;
        DoDiscardCard($player, "myHand-" . $randIdx);
    }
    if($fireCount > 0) {
        Draw($player, $fireCount);
    }
}

function CoiledFatestoneActivated($player, $mzID) {
    // As a Spell, deal 1 damage to each champion. Put an age counter.
    // If 3+ age counters, remove all and transform.
    DealChampionDamage(1, 1);
    DealChampionDamage(2, 1);
    $obj = GetZoneObject($mzID);
    if($obj === null || $obj->removed) return;
    AddCounters($player, $mzID, "age", 1);
    $obj = GetZoneObject($mzID);
    if($obj === null || $obj->removed) return;
    $ageCount = GetCounterCount($obj, "age");
    if($ageCount >= 3) {
        RemoveCounters($player, $mzID, "age", $ageCount);
        TransformCard($player, $mzID);
    }
}

// ============================================================================
// Idle Fatestone (qiv63tpshe) helper
// ============================================================================

function IdleFatestoneActivated($player, $mzID) {
    // Reveal the top card of your deck. If reserve cost is even, put a buff counter.
    // Otherwise, transform.
    $deck = GetDeck($player);
    if(empty($deck)) return;
    $topCard = $deck[0];
    $cardID = $topCard->CardID;
    // Reveal the top card
    SetFlashMessage('REVEAL:' . $cardID);
    $reserveCost = CardCost_reserve($cardID);
    if($reserveCost === null || $reserveCost < 0) $reserveCost = 0;
    if($reserveCost % 2 === 0) {
        // Even: put a buff counter
        AddCounters($player, $mzID, "buff", 1);
    } else {
        // Odd: transform
        TransformCard($player, $mzID);
    }
}

// ============================================================================
// Fatestone of Heaven (al6pqkmgmz) helpers
// ============================================================================

function FatestoneOfHeavenEnter($player) {
    // Destroy target non-champion object with memory cost 1 or less, or reserve cost 5 or less.
    $targets = [];
    foreach(["myField", "theirField"] as $z) {
        $field = GetZone($z);
        for($i = 0; $i < count($field); ++$i) {
            if($field[$i]->removed) continue;
            if(PropertyContains(EffectiveCardType($field[$i]), "CHAMPION")) continue;
            $memCost = CardCost_memory($field[$i]->CardID);
            $resCost = CardCost_reserve($field[$i]->CardID);
            $qualifies = false;
            if($memCost !== null && $memCost >= 0 && $memCost <= 1) $qualifies = true;
            if($resCost !== null && $resCost >= 0 && $resCost <= 5) $qualifies = true;
            if($qualifies) $targets[] = $z . "-" . $i;
        }
    }
    if(empty($targets)) return;
    DecisionQueueController::AddDecision($player, "MZCHOOSE", implode("&", $targets), 1,
        tooltip:"Destroy_a_non-champion_object");
    DecisionQueueController::AddDecision($player, "CUSTOM", "FatestoneOfHeavenDestroy", 1);
}

$customDQHandlers["FatestoneOfHeavenDestroy"] = function($player, $parts, $lastDecision) {
    if($lastDecision === "-" || $lastDecision === "" || $lastDecision === "PASS") return;
    $obj = GetZoneObject($lastDecision);
    if($obj === null || $obj->removed) return;
    $type = EffectiveCardType($obj);
    if(PropertyContains($type, "ALLY") || PropertyContains($type, "TOKEN")) {
        AllyDestroyed($player, $lastDecision);
    } else {
        OnLeaveField($player, $lastDecision);
        $controller = $obj->Controller ?? $player;
        global $playerID;
        $dest = $controller == $playerID ? "myGraveyard" : "theirGraveyard";
        MZMove($player, $lastDecision, $dest);
    }
};

function FatestoneOfHeavenActivated($player, $mzID) {
    // Reveal all cards in your memory. If 3+ luxem element, transform. Once per turn, slow speed.
    $memory = &GetMemory($player);
    $revealIDs = [];
    $luxemCount = 0;
    foreach($memory as $mObj) {
        if($mObj->removed) continue;
        $revealIDs[] = $mObj->CardID;
        if(CardElement($mObj->CardID) === "LUXEM") ++$luxemCount;
    }
    if(!empty($revealIDs)) {
        SetFlashMessage('REVEAL:' . implode('|', $revealIDs));
    }
    if($luxemCount >= 3) {
        TransformCard($player, $mzID);
    }
}

// ============================================================================
// Fatestone of Unrelenting (o37qtuvlxa) helper
// ============================================================================

function FatestoneOfUnrelentingActivated($player, $mzID) {
    // Banish two fire element cards from your graveyard, then transform and wake up.
    $fireGY = ZoneSearch("myGraveyard", cardElements: ["FIRE"]);
    if(count($fireGY) < 2) return;
    $fireStr = implode("&", $fireGY);
    DecisionQueueController::AddDecision($player, "MZCHOOSE", $fireStr, 1,
        tooltip:"Banish_fire_card_from_GY_(1/2)");
    DecisionQueueController::AddDecision($player, "CUSTOM", "FatestoneUnrelentingBanish1|" . $mzID, 1);
}

$customDQHandlers["FatestoneUnrelentingBanish1"] = function($player, $parts, $lastDecision) {
    if($lastDecision === "-" || $lastDecision === "" || $lastDecision === "PASS") return;
    $mzID = $parts[0];
    MZMove($player, $lastDecision, "myBanish");
    $fireGY = ZoneSearch("myGraveyard", cardElements: ["FIRE"]);
    if(empty($fireGY)) return;
    $fireStr = implode("&", $fireGY);
    DecisionQueueController::AddDecision($player, "MZCHOOSE", $fireStr, 1,
        tooltip:"Banish_fire_card_from_GY_(2/2)");
    DecisionQueueController::AddDecision($player, "CUSTOM", "FatestoneUnrelentingBanish2|" . $mzID, 1);
};

$customDQHandlers["FatestoneUnrelentingBanish2"] = function($player, $parts, $lastDecision) {
    if($lastDecision === "-" || $lastDecision === "" || $lastDecision === "PASS") return;
    $mzID = $parts[0];
    MZMove($player, $lastDecision, "myBanish");
    $obj = GetZoneObject($mzID);
    if($obj === null || $obj->removed) return;
    TransformCard($player, $mzID);
    WakeupCard($player, $mzID);
};

// ============================================================================
// Lavaplume Fatestone (0w5xyjuczy) helper
// ============================================================================

function LavaplumeFatestoneEnter($player, $mzID) {
    // As a Spell, deal X unpreventable damage to target unit,
    // where X is the amount of other Fatestone/Fatebound objects you control.
    $otherCount = CountFatestoneOrFateboundObjects($player) - 1;
    if($otherCount <= 0) return;
    $targets = array_merge(
        ZoneSearch("myField", ["ALLY", "CHAMPION"]),
        ZoneSearch("theirField", ["ALLY", "CHAMPION"])
    );
    $targets = FilterSpellshroudTargets($targets);
    if(empty($targets)) return;
    DecisionQueueController::StoreVariable("lavaplumeMzID", $mzID);
    DecisionQueueController::AddDecision($player, "MZCHOOSE", implode("&", $targets), 1,
        tooltip:"Deal_unpreventable_damage_to_target_unit");
    DecisionQueueController::AddDecision($player, "CUSTOM", "LavaplumeFatestoneResolve", 1);
}

$customDQHandlers["LavaplumeFatestoneResolve"] = function($player, $parts, $lastDecision) {
    if($lastDecision === "-" || $lastDecision === "" || $lastDecision === "PASS") return;
    $mzID = DecisionQueueController::GetVariable("lavaplumeMzID");
    $dmg = CountFatestoneOrFateboundObjects($player) - 1;
    if($dmg > 0) {
        DealUnpreventableDamage($player, $mzID, $lastDecision, $dmg);
    }
};

// ============================================================================
// Tidefate Brooch (vubaywkr69) helper
// ============================================================================

function TidefateBroochActivated($player, $mzID) {
    // Banish Tidefate Brooch, put the top ten cards of your deck into your graveyard.
    OnLeaveField($player, $mzID);
    MZMove($player, $mzID, "myBanish");
    $deck = GetDeck($player);
    $millCount = min(10, count($deck));
    for($i = 0; $i < $millCount; ++$i) {
        MZMove($player, "myDeck-0", "myGraveyard");
    }
}

// ============================================================================
// Submerged Fatestone (zfb0pzm6qp) — recollection trigger helper
// ============================================================================

function SubmergedFatestoneRecollectionTrigger($turnPlayer) {
    if(!IsGuoJiaBonus($turnPlayer)) return;
    global $playerID;
    $zone = $turnPlayer == $playerID ? "myField" : "theirField";
    $field = GetZone($zone);
    for($i = 0; $i < count($field); ++$i) {
        if(!$field[$i]->removed && $field[$i]->CardID === "zfb0pzm6qp" && !HasNoAbilities($field[$i])) {
            $floatingGY = ZoneSearch("myGraveyard", floatingMemoryOnly: true);
            if(!empty($floatingGY)) {
                DecisionQueueController::AddDecision($turnPlayer, "MZMAYCHOOSE", implode("&", $floatingGY), 1,
                    tooltip:"Banish_floating_memory_from_GY_to_transform_Submerged_Fatestone?");
                DecisionQueueController::AddDecision($turnPlayer, "CUSTOM", "SubmergedFatestoneRecollection|" . $i, 1);
            }
            break;
        }
    }
}

$customDQHandlers["SubmergedFatestoneRecollection"] = function($player, $parts, $lastDecision) {
    if($lastDecision === "-" || $lastDecision === "" || $lastDecision === "PASS") return;
    $fieldIdx = intval($parts[0]);
    // Banish the chosen floating memory card
    MZMove($player, $lastDecision, "myBanish");
    NicoOnFloatingMemoryBanished($player);
    // Transform Submerged Fatestone
    global $playerID;
    $zone = $player == $playerID ? "myField" : "theirField";
    $mzCard = $zone . "-" . $fieldIdx;
    $obj = GetZoneObject($mzCard);
    if($obj !== null && !$obj->removed && $obj->CardID === "zfb0pzm6qp") {
        TransformCard($player, $mzCard);
    }
};

// ============================================================================
// Wildgrowth Fatestone (x2oydmfcre) — enter trigger helper
// ============================================================================

function WildgrowthFatestoneOnEnterCheck($player, $enteredMZ) {
    if(!IsGuoJiaBonus($player)) return;
    $enteredObj = GetZoneObject($enteredMZ);
    if($enteredObj === null || $enteredObj->removed) return;
    $enteredElement = CardElement($enteredObj->CardID);
    if($enteredElement !== "WIND") return;
    global $playerID;
    $zone = $player == $playerID ? "myField" : "theirField";
    $field = &GetField($player);
    for($i = 0; $i < count($field); ++$i) {
        if(!$field[$i]->removed && $field[$i]->CardID === "x2oydmfcre" && !HasNoAbilities($field[$i])) {
            $wgMZ = $zone . "-" . $i;
            if($wgMZ === $enteredMZ) continue; // "another" — skip self
            AddCounters($player, $wgMZ, "buff", 1);
            $obj = GetZoneObject($wgMZ);
            if($obj !== null && !$obj->removed && GetCounterCount($obj, "buff") >= 6) {
                DecisionQueueController::AddDecision($player, "YESNO", "-", 1,
                    tooltip:"Transform_Wildgrowth_Fatestone?");
                DecisionQueueController::AddDecision($player, "CUSTOM", "WildgrowthFatestoneTransform|" . $i, 1);
            }
        }
    }
}

$customDQHandlers["WildgrowthFatestoneTransform"] = function($player, $parts, $lastDecision) {
    if($lastDecision !== "YES") return;
    $fieldIdx = intval($parts[0]);
    global $playerID;
    $zone = $player == $playerID ? "myField" : "theirField";
    $mzCard = $zone . "-" . $fieldIdx;
    $obj = GetZoneObject($mzCard);
    if($obj === null || $obj->removed || $obj->CardID !== "x2oydmfcre") return;
    TransformCard($player, $mzCard);
};

// ============================================================================
// Fatestone of Balance (v4gtq1ibth) — opponent activation trigger
// ============================================================================

function FatestoneOfBalanceOnOpponentActivated($activatingPlayer) {
    if(!IsGuoJiaBonus($activatingPlayer)) return; // The owner has Guo Jia, not the activating player
    // Check if the OPPONENT of the activating player has a Fatestone of Balance
    $owner = ($activatingPlayer == 1) ? 2 : 1;
    if(!IsGuoJiaBonus($owner)) return;
    // Check if activating player has exactly 3 cards in memory
    $memory = &GetMemory($activatingPlayer);
    $memCount = 0;
    foreach($memory as $m) { if(!$m->removed) ++$memCount; }
    if($memCount !== 3) return;
    global $playerID;
    $zone = $owner == $playerID ? "myField" : "theirField";
    $field = GetZone($zone);
    for($i = 0; $i < count($field); ++$i) {
        if(!$field[$i]->removed && $field[$i]->CardID === "v4gtq1ibth" && !HasNoAbilities($field[$i])) {
            $mzCard = $zone . "-" . $i;
            TransformCard($owner, $mzCard);
            break;
        }
    }
}

// ============================================================================
// Huaji of Heaven's Rise (v1iyt8rugx) — end phase transform helper
// ============================================================================

$customDQHandlers["HuajiEndPhaseTransform"] = function($player, $parts, $lastDecision) {
    if($lastDecision !== "YES") return;
    $fieldIdx = intval($parts[0]);
    global $playerID;
    $zone = $player == $playerID ? "myField" : "theirField";
    $mzCard = $zone . "-" . $fieldIdx;
    $obj = GetZoneObject($mzCard);
    if($obj === null || $obj->removed || $obj->CardID !== "v1iyt8rugx") return;
    // Pay (3): check if player has 3 cards in hand for reserve payment
    $hand = &GetHand($player);
    if(count($hand) < 3) return;
    for($r = 0; $r < 3; ++$r) {
        $hand = &GetHand($player);
        if(count($hand) === 0) break;
        MZMove($player, "myHand-0", "myMemory");
    }
    TransformCard($player, $mzCard);
};

// ============================================================================
// Pelagic Fatestone (tqkkyf4ktr) — floating memory banish hook
// ============================================================================

function PelagicFatestoneOnFloatingBanished($player, $banishedCardID) {
    // [Guo Jia Bonus] Whenever this card is banished from your graveyard to pay for a memory cost,
    // put it onto the field transformed.
    if($banishedCardID !== "tqkkyf4ktr") return;
    if(!IsGuoJiaBonus($player)) return;
    // Find the card in banishment and move it to field transformed
    global $playerID;
    $banishZone = $player == $playerID ? "myBanish" : "theirBanish";
    $banish = GetZone($banishZone);
    for($i = count($banish) - 1; $i >= 0; --$i) {
        if(!$banish[$i]->removed && $banish[$i]->CardID === "tqkkyf4ktr") {
            $mzBanish = $banishZone . "-" . $i;
            $newObj = MZMove($player, $mzBanish, "myField");
            if($newObj !== null) {
                $fieldArr = &GetField($player);
                $newIdx = count($fieldArr) - 1;
                $fieldZone = $player == $playerID ? "myField" : "theirField";
                TransformCard($player, $fieldZone . "-" . $newIdx);
            }
            break;
        }
    }
}


?>


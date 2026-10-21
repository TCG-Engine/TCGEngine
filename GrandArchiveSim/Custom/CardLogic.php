<?php
// Database-authored abilities granted by continuous effects to other objects.
function GADatabaseGrantedAbilities($obj) {
    if($obj === null || $obj->removed || $obj->Location !== 'Field' || $obj->Status != 2 || HasNoAbilities($obj)) return [];
    $grants = [];
    if(GAStaticProperty($obj->Controller, 'grantElysianEmpower', $obj) > 0) {
        $grants[] = ['cardID'=>'Ss991TWKR9', 'name'=>'REST: Empower 1'];
    }
    // Numinous Monk only grants to regalia, including transformed objects.
    if(PropertyContains(EffectiveCardType($obj), 'REGALIA')
        && GAStaticProperty($obj->Controller, 'grantNuminousSpell', $obj) > 0) {
        $grants[] = ['cardID'=>'NBf2d3iqXp', 'name'=>'REST: As a Spell, deal 1 damage to a unit'];
    }
    return $grants;
}
$customDQHandlers['GADatabaseGrantedAbility'] = function($player, $parts, $lastDecision) {
    global $grantedAbilityAbilities;
    $key = ($parts[0] ?? '') . ':0';
    if(($parts[0] ?? '') === 'NBf2d3iqXp') DecisionQueueController::StoreVariable('NuminousSpellSource', $parts[1] ?? '');
    if(isset($grantedAbilityAbilities[$key])) $grantedAbilityAbilities[$key]($player);
};
function GAElysianGraveyardTargets() {
    return ZoneSearch('myGraveyard', cardSubtypes: ['ELYSIAN']);
}
function GAPutGraveyardCardOnDeck($player, $mzID) {
    $obj = GetZoneObject($mzID);
    if($obj === null || $obj->removed) return;
    $moved = MZMove($player, $mzID, 'myDeck');
    if($moved === null) return;
    $deck = &GetDeck($player);
    foreach($deck as $index => $card) {
        if($card !== $moved) continue;
        array_splice($deck, $index, 1);
        array_unshift($deck, $moved);
        break;
    }
}


// Chronowarp's durable effect is authored in the database; the turn engine asks
// this hook for the two phase boundaries affected by distorted time.
function EngineTransitionOverride($phase, $input) {
    if(GlobalEffectCount(1, "TIME_DISTORTED") === 0 && GlobalEffectCount(2, "TIME_DISTORTED") === 0) return null;
    $input = strtoupper(trim($input));
    if($phase === "WU" && $input === "AUTO") return "BREC";
    if($phase === "MAIN" && $input === "PASS") return "AMAT";
    return null;
}

function DistortedMaterializePhase() {
    MaterializePhase(true);
}

// Continuous card text remains in the card database. Runtime consumers ask for
// a property, evaluating only live, unsuppressed sources controlled by the seat.
function GAStaticProperty($player, $property, $subjectObj = null) {
    if(!in_array(intval($player), [1, 2], true) || !function_exists('EvaluateStaticPropertyModifier')) return 0;
    $value = 0;
    foreach(GetField($player) as $source) {
        // Most field cards have no continuous modifier. Avoid suppression and
        // evaluator work for them on every legality/observation query.
        if($source === null || $source->removed || CardStaticPropertyModifierCount($source->CardID) <= 0 || HasNoAbilities($source)) continue;
        $value += EvaluateStaticPropertyModifier($source->CardID, $player, $property, $subjectObj, $source);
    }
    foreach(GetGlobalEffects($player) as $effect) {
        if($effect === null || $effect->removed || CardStaticPropertyModifierCount($effect->CardID) <= 0) continue;
        $source = clone $effect;
        $source->Controller = $player;
        $source->_sourceZone = "GlobalEffects";
        $value += EvaluateStaticPropertyModifier($source->CardID, $player, $property, $subjectObj, $source);
    }
    return $value;
}

function GATricastlesHandCosts($player) {
    $refs = [];
    foreach(GetHand($player) as $i => $obj) {
        if(!$obj->removed && (PropertyContains(CardElement($obj->CardID), "NORM") || IsAdvancedElementCard($obj->CardID))) $refs[] = "myHand-" . $i;
    }
    return $refs;
}
function GAHasPublicActivatedAbility($obj) {
    return $obj !== null && !$obj->removed && !HasNoAbilities($obj)
        && EvaluateStaticPropertyModifier($obj->CardID, $obj->Controller, "publicActivatedAbility", $obj, $obj) > 0;
}

function GALinkedProperty($subject, $property) {
    static $evaluating = [];
    if($subject === null || !function_exists('EvaluateStaticPropertyModifier')) return 0;
    $key = intval($subject->UniqueID ?? 0) . ':' . $property;
    if(isset($evaluating[$key])) return 0;
    $evaluating[$key] = true;
    $total = 0;
    try {
        foreach(GetLinkedCards($subject) as $source) {
            if($source->removed || HasNoAbilities($source)) continue;
            $total += EvaluateStaticPropertyModifier($source->CardID, $source->Controller, $property, $subject, $source);
        }
    } finally { unset($evaluating[$key]); }
    return $total;
}

function GAGrowingBoonReserve($player, $mzID) {
    $source = GetZoneObject($mzID);
    $index = json_decode(GetMacroGameIndex() ?: '{}', true) ?: [];
    return 2 + intval($index['GrowingBoonUses'][$player][$source->CardID ?? ''] ?? 0);
}
function GARecordGrowingBoonActivation($player, $cardID) {
    $index = json_decode(GetMacroGameIndex() ?: '{}', true) ?: [];
    $index['GrowingBoonUses'][$player][$cardID] = intval($index['GrowingBoonUses'][$player][$cardID] ?? 0) + 1;
    SetMacroGameIndex(json_encode($index));
}
function GAAllyLinkSacrificeCandidates() {
    global $AllyLink_Cards;
    return array_values(array_filter(ZoneSearch("myField"), function($mz) use ($AllyLink_Cards) {
        $obj = GetZoneObject($mz);
        return isset($AllyLink_Cards[$obj->CardID]) && !HasNoAbilities($obj);
    }));
}

function GABanishResolvedSpellForSeat($seat, $cardID) {
    global $playerID;
    $previous = $playerID;
    $playerID = $seat;
    try { BanishResolvedSpellFromGraveyard($seat, $cardID); }
    finally { $playerID = $previous; }
}

function GALiveHandRefs($seat) {
    $refs = [];
    foreach(GetHand($seat) as $i => $card) if(!$card->removed) $refs[] = "p" . $seat . "Hand-" . $i;
    return $refs;
}
function GADiscardHandSelection($seat, $selected) {
    global $playerID;
    $cards = array_values(array_intersect(explode("&", $selected), GALiveHandRefs($seat)));
    usort($cards, fn($a, $b) => intval(explode("-", $b)[1]) <=> intval(explode("-", $a)[1]));
    $prior = $playerID;
    $playerID = $seat;
    try {
        foreach($cards as $ref) DiscardCard($seat, "myHand-" . intval(explode("-", $ref)[1]));
        DecisionQueueController::CleanupRemovedCards();
    } finally { $playerID = $prior; }
}

function GALiveMemoryRefs($seat) {
    $refs = [];
    foreach(GetMemory($seat) as $i => $card) if(!$card->removed) $refs[] = "p" . $seat . "Memory-" . $i;
    return $refs;
}

function GABanishMemorySelectionAndReplace($player, $seat, $selected) {
    $cards = array_values(array_intersect(explode("&", $selected), GALiveMemoryRefs($seat)));
    usort($cards, fn($a, $b) => intval(explode("-", $b)[1]) <=> intval(explode("-", $a)[1]));
    $count = 0;
    foreach($cards as $ref) {
        $moved = MZMove($seat, "myMemory-" . intval(explode("-", $ref)[1]), "myBanish");
        if($moved !== null) ++$count;
    }
    DrawIntoMemory($seat, $count);
}

// The source zone is copied by MZMove before this hook runs. A replacement
// into banishment never reaches this hook, and direct graveyard seeding has no source.
function GAGraveyardAfterAdd($player, $cardID = "-") {
    $graveyard = GetGraveyard($player);
    $added = end($graveyard);
    if($added === false || $added->removed || !in_array($added->_sourceZone ?? "", ["myDeck", "theirDeck"], true)) return;
    DispatchMacroListeners("CardMilled", $player, ["cardID"=>$cardID, "type"=>EffectiveCardType($added), "mzID"=>"myGraveyard-" . $added->mzIndex], ["Graveyard"]);
}

// Damage replaced by Seize Fate is not dealt: do this before damage tracking,
// on-damage triggers and lethal-damage checks, including unpreventable damage.
function GAReplaceChampionDamageWithRecovery($player, $target, $amount) {
    $obj = GetZoneObject($target);
    if($obj === null || $obj->removed || intval($amount) <= 0) return false;
    $controller = intval($obj->Controller ?? $player);
    if(GAStaticProperty($controller, "damageBecomesRecovery", $obj) <= 0) return false;
    $obj->Damage = max(0, intval($obj->Damage) - intval($amount));
    if($obj->Damage === 0) {
        $lineage = $obj->Subcards ?? [];
        $uid = intval($obj->UniqueID);
        OnLeaveField($player, $target);
        $currentMZ = FindFieldMzByUniqueID($uid);
        if($currentMZ !== "") {
            $moved = MZMove($controller, NormalizeMZForPlayerPerspective($controller, $currentMZ), "myBanish");
            if($moved !== null) foreach($lineage as $id) MZAddZone($controller, "myBanish", $id);
            if(FindChampionMZ($controller) === null) TriggerGameOver($controller);
        }
    }
    return true;
}

// A change of controller is not a field entry or departure. Preserve identity,
// owner, counters, links and status while making the object usable by its new seat.
function GAChangeFieldController($mzID, $controller) {
    $obj = GetZoneObject($mzID);
    if($obj === null || $obj->removed || ($obj->Location ?? "") !== "Field"
        || !in_array(intval($controller), [1, 2], true)) return false;
    if(intval($obj->Controller) === intval($controller)) return false;
    $destination = &GetField($controller);
    $copy = clone $obj;
    $obj->Remove();
    $copy->Controller = intval($controller);
    $copy->PlayerID = intval($controller);
    $copy->mzIndex = count($destination);
    $copy->removed = false;
    $destination[] = $copy;
    $copy->BuildIndex();
    SyncCombatStateToFieldUniqueIDs();
    return true;
}

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
    $oppUnits = ZoneSearch("theirField", ["ALLY", "CHAMPION"]);
    
    if(empty($oppUnits)) return;
    
    // Convert to opponent's perspective: theirField-X becomes myField-X from opponent's POV
    $targetIndices = [];
    foreach($oppUnits as $mzID) {
        if (preg_match('/theirField-(\d+)/', $mzID, $matches)) {
            $targetIndices[] = "myField-" . $matches[1];
        }
    }
    
    DecisionQueueController::AddDecision($opponent, "MZCHOOSE", implode("&", $targetIndices), 1,
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
    if(GADTRObjectProperty($obj, "foster") > 0) return true;
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
        "ioLmt0S7op" => true, // Sanctified Paladin
        "GRlUlcYRmV" => true, // Sasha, Purifying Acolyte (printed Foster; missing from this list, so it could never become fostered)
        "46neis2lho" => true, // Imperial Panzer (printed Foster, likewise missing)
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
    $fosterObj = GetZoneObject($mzID);
    $wasFostered = $fosterObj !== null && IsFostered($fosterObj);
    AddTurnEffect($mzID, "FOSTERED");
    if(!$wasFostered && function_exists("GreaterBoonOfDuxOnFostered")) GreaterBoonOfDuxOnFostered($fosterObj->Controller ?? $player);
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
    $dtrObject = GetZoneObject($mzID);
    $dtrBecame = $dtrObject !== null && !IsDistant($dtrObject);
    AddTurnEffect($mzID, "DISTANT");
    if($dtrBecame && !HasNoAbilities($dtrObject) && empty($dtrObject->Counters['dtrDistantTriggered'])) {
        if($dtrObject->CardID === 'yC1TexeQwH') $dtrObject->Counters['dtrDistantTriggered'] = 1;
        GADTRQueueEvent($dtrObject,'BecameDistant');
    }
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
                    BecomeDistant($fObj->Controller, $liuBeiMZ);
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
        DecisionQueueController::AddDecision($attackingPlayer, "CUSTOM", "ReserveCard", 100);
        DecisionQueueController::AddDecision($attackingPlayer, "CUSTOM", "ReserveCard", 100);
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
    $fieldZone = $owner == $playerID ? "myField" : "theirField";

    MZMove($owner, NormalizeMZForPlayerPerspective($owner, $mzID), "myBanish");
    DecisionQueueController::CleanupRemovedCards();

    $banishment = GetBanish($owner);
    for($i = count($banishment) - 1; $i >= 0; --$i) {
        if($banishment[$i]->removed || $banishment[$i]->CardID !== "vvfwwa13y9") continue;
        $returned = MZMove($owner, "myBanish-" . $i, "myField");
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
    if($topCard !== null && !$topCard->removed && PropertyContains(EffectiveCardElement($topCard), "WATER")) {
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
    $fireCount = 0;
    for($i = 0; $i < 2; ++$i) {
        $hand = &GetHand($player);
        $liveIndices = [];
        foreach($hand as $index => $card) if(!$card->removed) $liveIndices[] = $index;
        if(empty($liveIndices)) break;
        $randIdx = $liveIndices[EngineRandomInt(0, count($liveIndices) - 1)];
        $discObj = $hand[$randIdx];
        $element = EffectiveCardElement($discObj);
        if(PropertyContains($element, "FIRE")) ++$fireCount;
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
            $resCost = EffectiveCardReserveCost($field[$i]);
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
    // Route every destroy through the shared AllyDestroyed() dispatcher regardless of target
    // type. AllyDestroyed()/DoAllyDestroyed() (GrandArchiveSim/Custom/GameLogic.php) is not
    // actually ally-specific -- it's the universal "object destroyed" pipeline (champion-loss
    // check, Immortality/Link Shield/Renewable/Ephemeral replacement effects, and every
    // "whenever a permanent is destroyed" trigger, e.g. Diao Chan, Idyll Corsage). A prior
    // version of this handler only routed ALLY/TOKEN targets through it and fell back to a raw
    // OnLeaveField()+MZMove() for ITEM/WEAPON targets, silently skipping all of that -- the same
    // bypass bug already fixed for other cards' destroy effects (see
    // GrandArchiveSim/Custom/GeneratedAbilityOverrides.php's header comment).
    AllyDestroyed($player, $lastDecision);
};

function FatestoneOfHeavenActivated($player, $mzID) {
    // Reveal all cards in your memory. If 3+ luxem element, transform. Once per turn, slow speed.
    $memory = &GetMemory($player);
    $luxemCount = 0;
    for($i = 0; $i < count($memory); ++$i) {
        if($memory[$i]->removed) continue;
        Reveal($player, "myMemory-" . $i);
        if(CardElement($memory[$i]->CardID) === "LUXEM") ++$luxemCount;
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
    DecisionQueueController::CleanupRemovedCards(); // the move above only flags the slot: offer exact indexes
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
    $enteredElement = EffectiveCardElement($enteredObj);
    if(!PropertyContains($enteredElement, "WIND")) return;
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
    if(empty($parts) || $parts[0] === "") return;
    $mzCard = $parts[0];
    $obj = GetZoneObject($mzCard);
    if($obj === null || $obj->removed || $obj->CardID !== "v1iyt8rugx") return;
    // Pay (3) reserve with normal reserve prompts, then transform.
    if(CountAvailableReservePayments($player) < 3) return;
    DecisionQueueController::AddDecision($player, "CUSTOM", "ReserveCard", 100);
    DecisionQueueController::AddDecision($player, "CUSTOM", "ReserveCard", 100);
    DecisionQueueController::AddDecision($player, "CUSTOM", "ReserveCard", 100);
    DecisionQueueController::AddDecision($player, "CUSTOM", "HuajiEndPhaseTransformResolve|" . $mzCard, 1);
};

$customDQHandlers["HuajiEndPhaseTransformResolve"] = function($player, $parts, $lastDecision) {
    if(empty($parts) || $parts[0] === "") return;
    $mzCard = $parts[0];
    $obj = GetZoneObject($mzCard);
    if($obj === null || $obj->removed || $obj->CardID !== "v1iyt8rugx") return;
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
            $mzBanish = "myBanish-" . $i;
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

function WeavingManastreamOnFloatingBanished($player, $banishedCardID) {
    // [Diana Bonus] [Element Bonus] Whenever this card is banished from your graveyard
    // to pay for a memory cost, you may load it into an Aetherwing weapon you control.
    if($banishedCardID !== "wi4f59furp") return;
    if(!IsDianaBonus($player)) return;
    if(!IsElementBonusActive($player, "wi4f59furp")) return;
    MayLoadIntoAetherwingFromBanish($player, "wi4f59furp");
}




// Carry the ability's Spell classification and last-known source through priority
// and deferred damage choices without changing the regalia's printed types.
function GAAsSpellSource($obj) {
    return 'GASPELL:' . base64_encode(json_encode([
        'cardID' => $obj->CardID, 'controller' => intval($obj->Controller),
        'uid' => intval($obj->UniqueID ?? 0),
    ]));
}

function GABanishFaceDown($obj) {
    return $obj !== null && ($obj->Location ?? '') === 'Banish'
        && (!empty($obj->Counters['_sealFaceDown']) || !empty($obj->Counters['_shadowreaverFaceDown']));
}
function GAOrbTargets($zone = null) {
    $targets = [];
    foreach($zone === null ? ['myBanish', 'theirBanish'] : [$zone] as $name) {
        if(!in_array($name, ['myBanish', 'theirBanish'], true)) continue;
        foreach(ZoneSearch($name) as $ref) {
            $obj = GetZoneObject($ref);
            if($obj === null || $obj->removed || GABanishFaceDown($obj)) continue;
            $type = EffectiveCardType($obj);
            if(PropertyContains($type, 'CHAMPION') || PropertyContains($type, 'REGALIA')) continue;
            $targets[] = $ref;
        }
    }
    return $targets;
}
function GAOrbSeal($player, $selection) {
    $refs = array_values(array_unique(array_filter(explode('&', $selection))));
    if(count($refs) > 2) return false;
    $zone = null;
    $legal = GAOrbTargets();
    foreach($refs as $ref) {
        if(!in_array($ref, $legal, true)) return false;
        $current = explode('-', $ref)[0];
        if($zone !== null && $zone !== $current) return false;
        $zone = $current;
    }
    foreach($refs as $ref) {
        AddCounters($player, $ref, 'seal', 1);
        GetZoneObject($ref)->Counters['_sealFaceDown'] = 1;
    }
    return true;
}
function GAOrbUnseal() {
    foreach([1, 2] as $seat) foreach(GetBanish($seat) as $obj) {
        if($obj->removed || GetCounterCount($obj, 'seal') <= 0) continue;
        unset($obj->Counters['seal'], $obj->Counters['_sealFaceDown'], $obj->Counters['_shadowreaverFaceDown']);
    }
}

function GASoulGainClasses($player) {
    $ownRef = FindChampionMZ($player);
    $otherRef = FindChampionMZ(3 - $player);
    $own = $ownRef === null ? null : GetZoneObject($ownRef);
    $other = $otherRef === null ? null : GetZoneObject($otherRef);
    if($own === null || $other === null) return;
    $classes = array_values(array_unique(array_filter(array_merge(
        $own->Counters['soulClasses'] ?? [], explode(',', EffectiveCardClasses($other) ?? '')
    ))));
    $own->Counters['soulClasses'] = $classes;
}
function GASoulCanLookMaterial($viewer, $owner) {
    return in_array(intval($viewer), [1, 2], true)
        && DecisionQueueController::GetVariable('soulMaterialLook') === $viewer . ':' . $owner;
}
function GASoulMaterialChoices($player) {
    $choices = [];
    foreach(ZoneSearch('theirMaterial', ['REGALIA']) as $ref) {
        $obj = GetZoneObject($ref);
        if($obj === null || $obj->removed || !GAMaterialCanLeave($obj)) continue;
        $cost = CardMemoryCost($obj);
        if($cost > CountAvailableMaterializeMemoryPayments($player, $ref)) continue;
        if($cost === 0) {
            $blocked = false;
            foreach(GetField(3 - $player) as $enemy) {
                if(!$enemy->removed && !HasNoAbilities($enemy) && in_array($enemy->CardID, ['40oe1wf79p', 'WZKo8sYPxS'], true)) $blocked = true;
            }
            if($blocked) continue;
        }
        $choices[] = $ref;
    }
    return $choices;
}
function GASoulMaterialize($player, $ref) {
    if(!in_array($ref, GASoulMaterialChoices($player), true)) return;
    QueueMaterializePayment($player, $ref, CardMemoryCost(GetZoneObject($ref)));
}

function GARegulusNegateOpposing($player) {
    $targets = [];
    foreach(GetEffectStack() as $obj) {
        if($obj === null || $obj->removed || intval($obj->Controller) === intval($player)) continue;
        if(!in_array($obj->TriggerType ?? '', ['', '-'], true) || in_array('CANT_BE_NEGATED', $obj->TurnEffects ?? [], true)) continue;
        $serial = intval(GetUniqueIDCounter()) + 1;
        SetUniqueIDCounter($serial);
        $obj->Counters['regulusTarget'] = $serial;
        $targets[] = 'REGULUS:' . $serial;
    }
    if(empty($targets)) return;
    DecisionQueueController::StoreVariable('NegateByCardTargets', implode(',', $targets));
    DecisionQueueController::StoreVariable('NegateByCardDestination', 'default');
    DecisionQueueController::StoreVariable('NegateByCardPay', '2');
    DecisionQueueController::StoreVariable('NegateByCardPlayer', strval($player));
    DecisionQueueController::AddDecision($player, 'CUSTOM', 'NegateByCardContinue|0', 1);
}
function GARegulusStackRef($token) {
    if(!str_starts_with($token, 'REGULUS:')) return $token;
    $serial = intval(substr($token, 8));
    foreach(GetEffectStack() as $i => $obj) {
        if(!$obj->removed && intval($obj->Counters['regulusTarget'] ?? 0) === $serial) return 'EffectStack-' . $i;
    }
    return '';
}
function GARegulusMarkActivation($player, $obj) {
    if($obj === null || !PropertyContains(EffectiveCardType($obj), 'ATTACK') || PropertyContains(EffectiveCardSubtypes($obj), 'COMMAND')) return;
    if(GlobalEffectCount($player, 'REGULUS_NEXT_ATTACK') <= 0) return;
    while(RemoveGlobalEffect($player, 'REGULUS_NEXT_ATTACK')) {}
    $obj->TurnEffects[] = 'REGULUS_NO_REST';
}
function GARegulusIntentNoRest($player, $consume = false) {
    $found = false;
    foreach(GetIntent($player) as $obj) {
        if($obj->removed || !in_array('REGULUS_NO_REST', $obj->TurnEffects ?? [], true)) continue;
        $found = true;
        if($consume) $obj->TurnEffects = array_values(array_diff($obj->TurnEffects, ['REGULUS_NO_REST']));
    }
    return $found;
}
$doesGlobalEffectApply['REGULUS_NEXT_ATTACK'] = function($obj) { return false; };

function GARevealedCardIdentity($authoredID) {
    return DecisionQueueController::GetVariable('revealAbilityID') === $authoredID
        ? (DecisionQueueController::GetVariable('revealIdentityCardID') ?: $authoredID) : $authoredID;
}
function GALightweaverResetRevealGroup() {
    $GLOBALS['gaMemoryRevealGroup'] = [];
}
function GALightweaverCopyReveal($player, $recipient, $donor) {
    global $revealAbilities;
    if(!isset($revealAbilities[$donor['cardID'] . ':0'])) return;
    QueueTriggeredAbility($player, $donor['cardID'], 'REVEAL_CARD', [
        'revealedMZ'=>NormalizeMzIDForController($recipient['ref'], $player),
        'revealSourceZone'=>'myMemory', 'revealAbilityID'=>$donor['cardID'],
        'revealIdentityCardID'=>$recipient['cardID'],
    ]);
}
function GALightweaverObserveReveal($player, $ref, $obj) {
    if(($obj->Location ?? '') !== 'Memory' || intval($obj->PlayerID ?? 0) !== intval($player)
        || HasNoAbilities($obj) || GAStaticProperty($player, 'shareMemoryReveals') <= 0) return;
    $group = $GLOBALS['gaMemoryRevealGroup'][$player] ?? [];
    // Revealing the same card again starts another event, rather than copying itself.
    foreach($group as $previous) if($previous['ref'] === $ref) { $group = []; break; }
    $current = ['ref'=>$ref, 'cardID'=>$obj->CardID];
    foreach($group as $previous) {
        GALightweaverCopyReveal($player, $current, $previous);
        GALightweaverCopyReveal($player, $previous, $current);
    }
    $group[] = $current;
    $GLOBALS['gaMemoryRevealGroup'][$player] = $group;
}
function GARevealMemoryGroup($player) {
    global $playerID;
    GALightweaverResetRevealGroup();
    BeginTriggeredAbilityBatch();
    $zone = $player == $playerID ? 'myMemory' : 'theirMemory';
    foreach(ZoneSearch($zone) as $ref) DoRevealCard($player, $ref);
    EndTriggeredAbilityBatch();
}

function IsZanderBonusActive($player) {
    foreach(GetField($player) as $obj) {
        if(!$obj->removed && PropertyContains(EffectiveCardType($obj), 'CHAMPION') && str_starts_with(CardName($obj->CardID), 'Zander,')) return true;
    }
    return false;
}

function GALightweaverNeedsOrderChoice($group) {
    $copied = false;
    $distinct = [];
    foreach($group as $entry) {
        if(isset($entry['context']['revealIdentityCardID'])) $copied = true;
        $distinct[$entry['cardID'] . ':' . ($entry['context']['revealIdentityCardID'] ?? $entry['cardID'])] = true;
    }
    return $copied && count($distinct) > 1;
}
function GALightweaverOrderPick($player) {
    $group = json_decode(base64_decode(DecisionQueueController::GetVariable('PendingTriggerOrderGroup') ?? ''), true) ?: [];
    if(count($group) <= 1) { FinishTriggerOrderChoice($player); return; }
    $labels = [];
    foreach($group as $entry) {
        $recipient = $entry['context']['revealIdentityCardID'] ?? $entry['cardID'];
        $labels[] = str_replace([' ', '&', '|'], ['_', '', ''], CardName($entry['cardID']) . ' ability on ' . CardName($recipient));
    }
    DecisionQueueController::AddDecision($player, 'MZMODAL', '1|1|' . implode('&', $labels), 100, 'Choose_the_reveal_ability_to_resolve_next');
    DecisionQueueController::AddDecision($player, 'CUSTOM', 'LightweaverOrderPick', 100);
}
$customDQHandlers['LightweaverOrderPick'] = function($player, $parts, $answer) {
    $group = json_decode(base64_decode(DecisionQueueController::GetVariable('PendingTriggerOrderGroup') ?? ''), true) ?: [];
    $chosen = json_decode(base64_decode(DecisionQueueController::GetVariable('PendingTriggerOrderChosen') ?? ''), true) ?: [];
    if(!ctype_digit(strval($answer)) || !isset($group[intval($answer)])) { GALightweaverOrderPick($player); return; }
    $chosen[] = $group[intval($answer)];
    array_splice($group, intval($answer), 1);
    DecisionQueueController::StoreVariable('PendingTriggerOrderGroup', base64_encode(json_encode($group)));
    DecisionQueueController::StoreVariable('PendingTriggerOrderChosen', base64_encode(json_encode($chosen)));
    GALightweaverOrderPick($player);
};

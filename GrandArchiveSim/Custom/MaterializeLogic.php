<?php
/**
 * Materialize logic for handling card materialization, resource generation, and related effects
 * This file contains functions that determine how materialization interactions resolve
 */

function MaterializePhase() {
    $currentTurn = intval(GetTurnNumber());
    if($currentTurn === 1) return;

    // Materialize phase
    SetFlashMessage("Materialize Phase");
    MaterializeChoice();
    // Eventide Spear (xjkdokzfd9): [CB:Warrior] may also activate from material deck if opponent has 2+ rested units
    DecisionQueueController::AddDecision(GetTurnPlayer(), "CUSTOM", "EVENTIDE_MATERIAL_CHECK", 1);
    // Varuckan Soulknife (9ox7u6wzh9): [Class Bonus][Element Bonus] may activate from material deck by banishing 3 fire from graveyard
    DecisionQueueController::AddDecision(GetTurnPlayer(), "CUSTOM", "VARUCKAN_MATERIAL_CHECK", 1);
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

$customDQHandlers["VARUCKAN_MATERIAL_CHECK"] = function($player, $parts, $lastDecision) {
    if(!IsClassBonusActive($player, ["ASSASSIN"])) return;
    if(!IsElementBonusActive($player, "9ox7u6wzh9")) return;

    $material = GetMaterial($player);
    $varuckanMZ = null;
    for($i = 0; $i < count($material); ++$i) {
        if(!$material[$i]->removed && $material[$i]->CardID === "9ox7u6wzh9") {
            $varuckanMZ = "myMaterial-" . $i;
            break;
        }
    }
    if($varuckanMZ === null) return;

    $fireGY = ZoneSearch("myGraveyard", cardElements: ["FIRE"]);
    if(count($fireGY) < 3) return;

    DecisionQueueController::AddDecision($player, "MZMAYCHOOSE", $varuckanMZ, 1,
        tooltip: "Banish_3_fire_cards_to_activate_Varuckan_from_material_deck?");
    DecisionQueueController::AddDecision($player, "CUSTOM", "VaruckanMaterialFromDeck", 1);
};

$customDQHandlers["VaruckanMaterialFromDeck"] = function($player, $parts, $lastDecision) {
    if($lastDecision === "-" || $lastDecision === "" || $lastDecision === "PASS") return;
    $varuckanMZ = $lastDecision;
    $fireGY = ZoneSearch("myGraveyard", cardElements: ["FIRE"]);
    if(count($fireGY) < 3) return;

    DecisionQueueController::StoreVariable("VaruckanMaterialMZ", $varuckanMZ);
    DecisionQueueController::AddDecision($player, "MZCHOOSE", implode("&", $fireGY), 1,
        tooltip: "Banish_fire_card_from_graveyard_(1/3)");
    DecisionQueueController::AddDecision($player, "CUSTOM", "VaruckanMaterialBanish|1", 1);
};

$customDQHandlers["VaruckanMaterialBanish"] = function($player, $parts, $lastDecision) {
    if($lastDecision === "-" || $lastDecision === "" || $lastDecision === "PASS") {
        DecisionQueueController::ClearVariable("VaruckanMaterialMZ");
        return;
    }

    $count = intval($parts[0] ?? "1");
    MZMove($player, $lastDecision, "myBanish");
    DecisionQueueController::CleanupRemovedCards();

    if($count >= 3) {
        $varuckanMZ = DecisionQueueController::GetVariable("VaruckanMaterialMZ");
        DecisionQueueController::ClearVariable("VaruckanMaterialMZ");
        if($varuckanMZ === null || $varuckanMZ === "") return;
        DecisionQueueController::AddDecision($player, "MZCHOOSE", $varuckanMZ, 1);
        DecisionQueueController::AddDecision($player, "CUSTOM", "MATERIALIZE|NOCOST", 1);
        return;
    }

    $next = $count + 1;
    $fireGY = ZoneSearch("myGraveyard", cardElements: ["FIRE"]);
    if(count($fireGY) < (3 - $count)) {
        DecisionQueueController::ClearVariable("VaruckanMaterialMZ");
        return;
    }

    DecisionQueueController::AddDecision($player, "MZCHOOSE", implode("&", $fireGY), 1,
        tooltip: "Banish_fire_card_from_graveyard_(" . $next . "/3)");
    DecisionQueueController::AddDecision($player, "CUSTOM", "VaruckanMaterialBanish|" . $next, 1);
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

    // Nico, Rapture's Embrace (29lqrve8fz): can only level up into another "Nico" champion
    if(PropertyContains(CardType($materializeCard->CardID), "CHAMPION")) {
        $field = &GetField($player);
        foreach($field as &$fObj) {
            if(!$fObj->removed && $fObj->CardID === "29lqrve8fz" && $fObj->Controller == $player) {
                if(strpos(CardName($materializeCard->CardID), "Nico") !== 0) return;
                break;
            }
        }
    }

    $memoryCost = $ignoreCost ? 0 : CardMemoryCost($materializeCard);

    // Dragon's Dawn (9f92917r84): additional cost to materialize — banish 3 fire from graveyard
    if($materializeCard->CardID === "9f92917r84" && !$ignoreCost) {
        $fireGY = ZoneSearch("myGraveyard", cardElements: ["FIRE"]);
        if(count($fireGY) < 3) return; // Can't pay cost
        DecisionQueueController::AddDecision($player, "MZCHOOSE", implode("&", $fireGY), 1,
            tooltip:"Banish_fire_card_from_graveyard_(1/3)");
        DecisionQueueController::AddDecision($player, "CUSTOM", "DragonsDawnBanish|" . $lastDecision . "|1|" . $memoryCost, 1);
        return;
    }

    // Dusksoul Stone (u25fuv184p): additional cost to materialize — banish 2 ally cards from a single graveyard
    if($materializeCard->CardID === "u25fuv184p" && !$ignoreCost) {
        $eligible = [];
        $myAllies = ZoneSearch("myGraveyard", ["ALLY"]);
        $theirAllies = ZoneSearch("theirGraveyard", ["ALLY"]);
        if(count($myAllies) >= 2) $eligible = array_merge($eligible, $myAllies);
        if(count($theirAllies) >= 2) $eligible = array_merge($eligible, $theirAllies);
        if(empty($eligible)) return;
        DecisionQueueController::AddDecision($player, "MZCHOOSE", implode("&", $eligible), 1,
            tooltip:"Banish_ally_card_from_a_single_graveyard_(1/2)");
        DecisionQueueController::AddDecision($player, "CUSTOM", "DusksoulStoneMaterializeCost|" . $lastDecision . "|" . $memoryCost, 1);
        return;
    }

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

// Dragon's Dawn (9f92917r84): sequential banish of fire cards from graveyard as materialize cost
$customDQHandlers["DragonsDawnBanish"] = function($player, $parts, $lastDecision) {
    // $parts[0] = mzCard (Dragon's Dawn in material deck), $parts[1] = current banish# (1-3), $parts[2] = memoryCost
    if($lastDecision === "-" || $lastDecision === "") return;
    MZMove($player, $lastDecision, "myBanish");
    $mzCard = $parts[0];
    $count = intval($parts[1]);
    $memoryCost = intval($parts[2]);
    if($count < 3) {
        $fireGY = ZoneSearch("myGraveyard", cardElements: ["FIRE"]);
        $remaining = 3 - $count;
        if(count($fireGY) < $remaining) return; // Can't pay remaining cost
        $next = $count + 1;
        DecisionQueueController::AddDecision($player, "MZCHOOSE", implode("&", $fireGY), 1,
            tooltip:"Banish_fire_card_from_graveyard_(" . $next . "/3)");
        DecisionQueueController::AddDecision($player, "CUSTOM", "DragonsDawnBanish|" . $mzCard . "|" . $next . "|" . $memoryCost, 1);
    } else {
        // All 3 banished — handle memory cost then materialize
        if($memoryCost > 0) {
            DecisionQueueController::StoreVariable("MemoryCost", $memoryCost);
            $floatingIndices = implode("&", ZoneSearch("myGraveyard", floatingMemoryOnly:true));
            if($floatingIndices != "") {
                DecisionQueueController::AddDecision($player, "MZMAYCHOOSE", $floatingIndices, 1);
                DecisionQueueController::AddDecision($player, "CUSTOM", "PAYFLOATING|" . $memoryCost, 1);
            }
            DecisionQueueController::AddDecision($player, "CUSTOM", "FINISHPAYMATERIALIZE", 2, dontSkipOnPass:1);
        }
        Materialize($player, $mzCard);
    }
};

$customDQHandlers["DusksoulStoneMaterializeCost"] = function($player, $parts, $lastDecision) {
    if($lastDecision === "-" || $lastDecision === "" || $lastDecision === "PASS") return;
    $mzCard = $parts[0];
    $memoryCost = intval($parts[1]);
    $gyRef = strpos($lastDecision, "theirGraveyard-") === 0 ? "theirGraveyard" : "myGraveyard";
    MZMove($player, $lastDecision, "myBanish");
    $remaining = ZoneSearch($gyRef, ["ALLY"]);
    if(empty($remaining)) return;
    DecisionQueueController::AddDecision($player, "MZCHOOSE", implode("&", $remaining), 1,
        tooltip:"Banish_ally_card_from_the_same_graveyard_(2/2)");
    DecisionQueueController::AddDecision($player, "CUSTOM", "DusksoulStoneMaterializeCostFinish|" . $mzCard . "|" . $memoryCost, 1);
};

$customDQHandlers["DusksoulStoneMaterializeCostFinish"] = function($player, $parts, $lastDecision) {
    if($lastDecision === "-" || $lastDecision === "" || $lastDecision === "PASS") return;
    $mzCard = $parts[0];
    $memoryCost = intval($parts[1]);
    MZMove($player, $lastDecision, "myBanish");
    if($memoryCost > 0) {
        DecisionQueueController::StoreVariable("MemoryCost", $memoryCost);
        $floatingIndices = implode("&", ZoneSearch("myGraveyard", floatingMemoryOnly:true));
        if($floatingIndices != "") {
            DecisionQueueController::AddDecision($player, "MZMAYCHOOSE", $floatingIndices, 1);
            DecisionQueueController::AddDecision($player, "CUSTOM", "PAYFLOATING|" . $memoryCost, 1);
        }
        DecisionQueueController::AddDecision($player, "CUSTOM", "FINISHPAYMATERIALIZE", 2, dontSkipOnPass:1);
    }
    Materialize($player, $mzCard);
};

$customDQHandlers["PAYFLOATING"] = function($player, $parts, $lastDecision) {
    $banishedObj = GetZoneObject($lastDecision);
    $banishedCardID = $banishedObj ? $banishedObj->CardID : null;
    MZMove($player, $lastDecision, "myBanish");
    // Pelagic Fatestone (tqkkyf4ktr): if this card was banished from GY to pay memory cost, put on field transformed
    if($banishedCardID !== null) PelagicFatestoneOnFloatingBanished($player, $banishedCardID);
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
    for($i = 0; $i < $memoryCost; ++$i) {
        MZMove($player, "myMemory-" . $i, "myBanish");//TODO: Make random
    }
    DecisionQueueController::ClearVariable("MemoryCost");
};

function DoMaterialize($player, $mzCard) {
    global $customDQHandlers;
    $sourceObject = &GetZoneObject($mzCard);
    $sourceId = $sourceObject->CardID;

    // Obstinate Cragback: opponents can't materialize cards with memory cost 0.
    if(CardMemoryCost($sourceObject) === 0) {
        $opponent = ($player == 1) ? 2 : 1;
        $oppField = &GetField($opponent);
        foreach($oppField as $oppObj) {
            if($oppObj->removed || HasNoAbilities($oppObj)) continue;
            if($oppObj->CardID === "40oe1wf79p" || $oppObj->CardID === "WZKo8sYPxS") {
                return;
            }
        }
    }

    if(PropertyContains(CardType($sourceId), "CHAMPION")) {
        // Champion lineage: find existing champion on the field
        $field = &GetField($player);
        $existingChampionIdx = -1;
        $existingSubcards = [];
        $existingChampionCardID = null;
        $existingDamage = 0;
        $existingCounters = [];
        $existingTurnEffects = [];

        for($i = 0; $i < count($field); ++$i) {
            if(!$field[$i]->removed && PropertyContains(CardType($field[$i]->CardID), "CHAMPION") && $field[$i]->Controller == $player) {
                $existingChampionIdx = $i;
                $existingChampionCardID = $field[$i]->CardID;
                $existingSubcards = is_array($field[$i]->Subcards) ? $field[$i]->Subcards : [];
                $existingDamage = $field[$i]->Damage;
                $existingCounters = is_array($field[$i]->Counters) ? $field[$i]->Counters : [];
                $existingTurnEffects = is_array($field[$i]->TurnEffects) ? $field[$i]->TurnEffects : [];
                break;
            }
        }

        // Snow White, Weiss Queen (5u5m8xblmd): [Level 1+] If a rested champion you
        // don't control would level up, return that card to its owner's material deck instead.
        if($existingChampionIdx >= 0 && intval(CardLevel($existingChampionCardID)) === 2) {
            $fdOpponent = ($player == 1) ? 2 : 1;
            $fdField = &GetField($fdOpponent);
            foreach($fdField as $fdObj) {
                if(!$fdObj->removed && $fdObj->CardID === "1PrDQ1EX0F" && !HasNoAbilities($fdObj)) {
                    MZMove($player, $mzCard, "myMaterial");
                    return;
                }
            }
        }

        if($existingChampionIdx >= 0 && $field[$existingChampionIdx]->Status == 1) {
            $swOpponent = ($player == 1) ? 2 : 1;
            $swField = &GetField($swOpponent);
            foreach($swField as &$swObj) {
                if(!$swObj->removed && $swObj->CardID === "5u5m8xblmd" && !HasNoAbilities($swObj)
                   && PlayerLevel($swOpponent) >= 1) {
                    MZMove($player, $mzCard, "myMaterial");
                    return;
                }
            }
        }

        // Nameless Champion (0794z3ffck): This champion can't level up.
        if($existingChampionIdx >= 0 && $existingChampionCardID === "0794z3ffck"
           && !HasNoAbilities($field[$existingChampionIdx])) {
            MZMove($player, $mzCard, "myMaterial");
            return;
        }

        // Ignis Deus (rxdon8uwza): for the rest of the game, non-Spirit champions you control can't level up.
        if($existingChampionIdx >= 0 && GlobalEffectCount($player, "IGNIS_DEUS_LOCK") > 0
           && !PropertyContains(EffectiveCardClasses($field[$existingChampionIdx]), "SPIRIT")) {
            MZMove($player, $mzCard, "myMaterial");
            return;
        }

        // Build new lineage: old champion's CardID prepended to its subcards
        $newSubcards = [];
        if($existingChampionCardID !== null) {
            $newSubcards = array_merge([$existingChampionCardID], $existingSubcards);
            // Remove old champion from field without triggering OnLeaveField/AllyDestroyed
            $field[$existingChampionIdx]->removed = true;
        }

        // Pre-populate TurnEffects onto the incoming card BEFORE MZMove so that
        // OnEnter fires with the correct effects already present (e.g. IsDistant
        // in Diana's Enter ability reads TurnEffects during the MZMove call).
        if(!empty($existingTurnEffects)) {
            $incomingObj = &GetZoneObject($mzCard);
            if($incomingObj !== null) {
                $existingIncomingTurnEffects = [];
                if(isset($incomingObj->TurnEffects) && is_array($incomingObj->TurnEffects)) {
                    $existingIncomingTurnEffects = $incomingObj->TurnEffects;
                }
                $incomingObj->TurnEffects = array_values(array_unique(array_merge(
                    $existingIncomingTurnEffects,
                    $existingTurnEffects
                )));
            }
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
        // TurnEffects already transferred above (pre-MZMove) so OnEnter sees them.

        // Track that a champion leveled up this turn (for Invigorated Slash etc.)
        AddGlobalEffects($player, "LEVELED_UP_THIS_TURN");

        // Frozen Divinity: when your champion levels up into a base level 3 champion,
        // sacrifice Frozen Divinity and draw a card into memory.
        $fdField = &GetField($player);
        for($fdi = count($fdField) - 1; $fdi >= 0; --$fdi) {
            if(!$fdField[$fdi]->removed && $fdField[$fdi]->CardID === "1PrDQ1EX0F" && !HasNoAbilities($fdField[$fdi])
                && intval(CardLevel($sourceId)) === 3) {
                DoSacrificeFighter($player, "myField-" . $fdi);
                DecisionQueueController::CleanupRemovedCards();
                DrawIntoMemory($player, 1);
                break;
            }
        }

        // Lesser Boon of Isis (GlqhpkmflM): when your champion levels up into a
        // base level 3 champion, draw a card into your memory. Trigger only once.
        if(intval(CardLevel($sourceId)) === 3) {
            for($bi = 0; $bi < count($fdField); ++$bi) {
                if($fdField[$bi]->removed || $fdField[$bi]->CardID !== "GlqhpkmflM" || HasNoAbilities($fdField[$bi])) continue;
                if(isset($fdField[$bi]->Counters["isis_once"]) && intval($fdField[$bi]->Counters["isis_once"]) > 0) continue;
                if(!is_array($fdField[$bi]->Counters)) $fdField[$bi]->Counters = [];
                $fdField[$bi]->Counters["isis_once"] = 1;
                DrawIntoMemory($player, 1);
                break;
            }
        }

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

        // Suspicious Concoction (5tphi6xl26): whenever your champion levels up,
        // you may banish Suspicious Concoction. If you do, draw a card into memory and recover 2.
        {
            global $playerID;
            $fZone = ($player == $playerID) ? "myField" : "theirField";
            $field = GetZone($fZone);
            for($sci = 0; $sci < count($field); ++$sci) {
                if(!$field[$sci]->removed && $field[$sci]->CardID === "5tphi6xl26" && !HasNoAbilities($field[$sci])) {
                    $scMZ = $fZone . "-" . $sci;
                    DecisionQueueController::AddDecision($player, "YESNO", "-", 1,
                        tooltip:"Banish_Suspicious_Concoction_to_draw_into_memory_and_recover_2?");
                    DecisionQueueController::AddDecision($player, "CUSTOM",
                        "SuspiciousConcoctionLevelUp|$scMZ", 1);
                    break;
                }
            }
        }

        // Wavekeeper's Bond (WWlknyTxGA): whenever your champion levels up, recover 2
        {
            global $playerID;
            $fZone = ($player == $playerID) ? "myField" : "theirField";
            $field = GetZone($fZone);
            for($wbi = 0; $wbi < count($field); ++$wbi) {
                if(!$field[$wbi]->removed && $field[$wbi]->CardID === "WWlknyTxGA" && !HasNoAbilities($field[$wbi])) {
                    RecoverChampion($player, 2);
                    break;
                }
            }
        }

        // Scepter of Lumina (e5o3cm9lbe): whenever your champion levels up, deal 4 damage to target champion you don't control
        {
            global $playerID;
            $fZone = ($player == $playerID) ? "myField" : "theirField";
            $field = GetZone($fZone);
            for($sli = 0; $sli < count($field); ++$sli) {
                if(!$field[$sli]->removed && $field[$sli]->CardID === "e5o3cm9lbe" && !HasNoAbilities($field[$sli])) {
                    $opponent = ($player == 1) ? 2 : 1;
                    DealChampionDamage($opponent, 4);
                    break;
                }
            }
        }

        // Quiet Refraction (4vZN8JlY2k): whenever your champion levels up, put 2 sheen counters on Fractured Memories
        {
            global $playerID;
            $fZone = ($player == $playerID) ? "myField" : "theirField";
            $field = GetZone($fZone);
            for($qri = 0; $qri < count($field); ++$qri) {
                if(!$field[$qri]->removed && $field[$qri]->CardID === "4vZN8JlY2k" && !HasNoAbilities($field[$qri])) {
                    AddSheenToMastery($player, 2);
                    break;
                }
            }
        }

        // Tasershot (4x7e22tk3i): [CB] On Champion Hit — whenever the hit champion levels up,
        // deal 4 unpreventable damage to them (until beginning of attacker's next turn).
        {
            $opponent = ($player == 1) ? 2 : 1;
            if(GlobalEffectCount($opponent, "4x7e22tk3i") > 0) {
                global $playerID;
                $champZone = ($player == $playerID) ? "myField" : "theirField";
                $champField = GetZone($champZone);
                for($tsi = 0; $tsi < count($champField); ++$tsi) {
                    if(!$champField[$tsi]->removed && PropertyContains(EffectiveCardType($champField[$tsi]), "CHAMPION")
                       && $champField[$tsi]->Controller == $player) {
                        $champMZ = $champZone . "-" . $tsi;
                        DealUnpreventableDamage($opponent, "4x7e22tk3i", $champMZ, 4);
                        break;
                    }
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

    // Excalibur, Cursed Sword (4sm14RaEkg): whenever you materialize a card, deal 2 damage to your champion
    {
        global $playerID;
        $fZone = ($player == $playerID) ? "myField" : "theirField";
        $field = GetZone($fZone);
        foreach($field as $excObj) {
            if(!$excObj->removed && $excObj->CardID === "4sm14RaEkg" && !HasNoAbilities($excObj)
                && $excObj->Controller == $player) {
                DealChampionDamage($player, 2);
                break;
            }
        }
    }

    // --- Domain Upkeep: "Whenever you materialize a card, sacrifice [domain]" ---
    // After any materialize, check if the player controls domains with materialize-sacrifice upkeep.
    // Domains tagged with NO_UPKEEP (via Right of Realm) skip this trigger.
    DomainMaterializeSacrifice($player);

    // Craggy Fatestone (h8n1520m2d): [Guo Jia Bonus] whenever opponent materializes a card
    // with memory cost 0, put a buff counter on Craggy Fatestone
    {
        $matMemCost = CardCost_memory($sourceId);
        if($matMemCost !== null && $matMemCost == 0) {
            $opponent = ($player == 1) ? 2 : 1;
            if(IsGuoJiaBonus($opponent)) {
                global $playerID;
                $oppZone = $opponent == $playerID ? "myField" : "theirField";
                $oppField = GetZone($oppZone);
                for($ci = 0; $ci < count($oppField); ++$ci) {
                    if(!$oppField[$ci]->removed && $oppField[$ci]->CardID === "h8n1520m2d" && !HasNoAbilities($oppField[$ci])) {
                        AddCounters($opponent, $oppZone . "-" . $ci, "buff", 1);
                    }
                }
            }
        }
    }
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

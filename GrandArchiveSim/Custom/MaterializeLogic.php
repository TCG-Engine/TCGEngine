<?php
/**
 * Materialize logic for handling card materialization, resource generation, and related effects
 * This file contains functions that determine how materialization interactions resolve
 */

function MaterializePhase() {
    $currentTurn = intval(GetTurnNumber());
    if($currentTurn === 1) return;
    
    // Orchestrated Seizure (pwscn0esog): prepare one-turn materialize floating access window.
    while(GlobalEffectCount(GetTurnPlayer(), "pwscn0esog_ACTIVE") > 0) {
        RemoveGlobalEffect(GetTurnPlayer(), "pwscn0esog_ACTIVE");
    }
    if(GlobalEffectCount(GetTurnPlayer(), "pwscn0esog_PENDING") > 0) {
        while(GlobalEffectCount(GetTurnPlayer(), "pwscn0esog_PENDING") > 0) {
            RemoveGlobalEffect(GetTurnPlayer(), "pwscn0esog_PENDING");
        }
        AddGlobalEffects(GetTurnPlayer(), "pwscn0esog_ACTIVE");
    }


    $hasMaterial = MaterializeChoice();
    // Eventide Spear (xjkdokzfd9): [CB:Warrior] may also activate from material deck if opponent has 2+ rested units
    DecisionQueueController::AddDecision(GetTurnPlayer(), "CUSTOM", "EVENTIDE_MATERIAL_CHECK", 1);
    // Varuckan Soulknife (9ox7u6wzh9): [Class Bonus][Element Bonus] may activate from material deck by banishing 3 fire from graveyard
    DecisionQueueController::AddDecision(GetTurnPlayer(), "CUSTOM", "VARUCKAN_MATERIAL_CHECK", 1);
    DecisionQueueController::AddDecision(GetTurnPlayer(), "CUSTOM", "FRAMEWORK_SIDEARM_MATERIAL_CHECK", 1);
    // Reciprocity, Dorumegia's Call (mSOHJGjrIu): [Tonoris Bonus] activate from material deck while controlling 2+ domains
    DecisionQueueController::AddDecision(GetTurnPlayer(), "CUSTOM", "RECIPROCITY_MATERIAL_CHECK", 1);
    
    // If the player has no cards left in material, run the same path as a skipped
    // materialize callback (lastDecision="-") so phase progression remains consistent.
    if(!$hasMaterial) {
        $turnPlayer = GetTurnPlayer();
        DecisionQueueController::AddDecision($turnPlayer, "PASSPARAMETER", "-", 1);
        DecisionQueueController::AddDecision($turnPlayer, "CUSTOM", "MATERIALIZE", 1);
        $dqController = new DecisionQueueController();
        $dqController->ExecuteStaticMethods($turnPlayer, "-");
        return;
    }
}

function GetMaterializeFloatingChoices($player) {
    $choices = ZoneSearch("myGraveyard", floatingMemoryOnly:true);
    if(GlobalEffectCount($player, "pwscn0esog_ACTIVE") > 0) {
        global $playerID;
        $oppGY = ($player == $playerID) ? "theirGraveyard" : "myGraveyard";
        $choices = array_merge($choices, ZoneSearch($oppGY, floatingMemoryOnly:true));
    }
    // Art of War (fjne9ri261): while paying memory cost, you may banish it to pay for 1.
    global $playerID;
    $myField = ($player == $playerID) ? "myField" : "theirField";
    $field = GetZone($myField);
    for($i = 0; $i < count($field); ++$i) {
        if($field[$i]->removed) continue;
        if($field[$i]->CardID !== "fjne9ri261") continue;
        if(HasNoAbilities($field[$i])) continue;
        $choices[] = $myField . "-" . $i;
    }
    return implode("&", $choices);
}

function QueueMaterializeFloatingPaymentChoice($player, $memoryCost) {
    if($memoryCost <= 0) return;
    $floatingIndices = GetMaterializeFloatingChoices($player);
    if($floatingIndices === "") return;
    $floatingChoices = explode("&", $floatingIndices);
    $maxChoices = min($memoryCost, count($floatingChoices));
    if($maxChoices <= 0) return;
    DecisionQueueController::AddDecision($player, "MZMULTICHOOSE", "0|".$maxChoices."|".$floatingIndices, 1,
        tooltip:"Banish_up_to_".$maxChoices."_floating_memory_cards");
    DecisionQueueController::AddDecision($player, "CUSTOM", "PAYFLOATING|" . $memoryCost, 1);
}

function CountAvailableMaterializeMemoryPayments($player) {
    $memoryCount = 0;
    $memoryZone = GetMemory($player);
    foreach($memoryZone as $memoryObj) {
        if(!$memoryObj->removed) ++$memoryCount;
    }

    $floatingIndices = GetMaterializeFloatingChoices($player);
    $floatingCount = $floatingIndices === "" ? 0 : count(explode("&", $floatingIndices));
    return $memoryCount + $floatingCount;
}

function QueueMaterializePayment($player, $mzCard, $memoryCost, $extraReserveCost = 0) {
    $memoryCost = intval($memoryCost);
    $extraReserveCost = intval($extraReserveCost);
    if($mzCard === "") return false;

    if($memoryCost > 0 && CountAvailableMaterializeMemoryPayments($player) < $memoryCost) {
        AutoUndoMaterializeCostFailure($player);
        return false;
    }

    DecisionQueueController::StoreVariable("MemoryCost", $memoryCost);
    DecisionQueueController::StoreVariable("PendingMatCard", $mzCard);
    for($i = 0; $i < $extraReserveCost; ++$i) {
        DecisionQueueController::AddDecision($player, "CUSTOM", "ReserveCard", 100);
    }

    if($memoryCost > 0) {
        QueueMaterializeFloatingPaymentChoice($player, $memoryCost);
    }
    DecisionQueueController::AddDecision($player, "CUSTOM", "FINISHPAYMATERIALIZE", 2, dontSkipOnPass:1);
    return true;
}

function GetLegalMaterializeChoices($player) {
    $material = GetMaterial($player);
    $choices = [];
    for($i = 0; $i < count($material); ++$i) {
        if($material[$i]->removed) continue;
        if(!CanPlayerUseCardElement($player, $material[$i]->CardID, false, false)) continue;
        if(PropertyContains(CardType($material[$i]->CardID), "CHAMPION")
            && !CanMaterializeChampion($player, $material[$i]->CardID)) continue;
        $choices[] = "myMaterial-" . $i;
    }
    return $choices;
}

function AutoUndoMaterializeCostFailure($player, $message = "Cannot pay costs for the selected material card. Action undone.") {
    LoadVersion($player);
    SetFlashMessage($message);
}

function MaterializeChoice($ignoreCost = false) {
    $turnPlayer = GetTurnPlayer();
    $legalChoices = GetLegalMaterializeChoices($turnPlayer);
    $handlerParam = $ignoreCost ? "MATERIALIZE|NOCOST" : "MATERIALIZE";
    if(empty($legalChoices)) {
        DecisionQueueController::AddDecision($turnPlayer, "PASSPARAMETER", "-", 1);
        DecisionQueueController::AddDecision($turnPlayer, "CUSTOM", $handlerParam, 1);
        return false;
    }
    DecisionQueueController::AddDecision($turnPlayer, "MZMAYCHOOSE", implode("&", $legalChoices), 1);
    DecisionQueueController::AddDecision($turnPlayer, "CUSTOM", $handlerParam, 1);
    // Snapshot after enqueueing the materialize prompt so undo restores with
    // the pending choice still in the decision queue.
    // Use turn player identity explicitly so undo ownership is correct even if
    // perspective/global player tracking still reflects the prior priority pass.
    SaveUndoVersion($turnPlayer, "Materialize Choice");
    return true;
}

$customDQHandlers["EVENTIDE_MATERIAL_CHECK"] = function($player, $parts, $lastDecision) {
    // Eventide Spear (xjkdokzfd9): [CB:Warrior] if opponent controls 2+ rested units, offer extra materialize
    if(!IsClassBonusActive($player, ["WARRIOR"])) return;
    $material = GetMaterial($player);
    $eventideMZ = null;
    for($i = 0; $i < count($material); ++$i) {
        if(!$material[$i]->removed && $material[$i]->CardID === "xjkdokzfd9") {
            if(!CanPlayerUseCardElement($player, $material[$i]->CardID, false, false)) return;
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
            if(!CanPlayerUseCardElement($player, $material[$i]->CardID, false, false)) return;
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

$customDQHandlers["FRAMEWORK_SIDEARM_MATERIAL_CHECK"] = function($player, $parts, $lastDecision) {
    if(!IsClassBonusActive($player, ["RANGER"])) return;
    if(count(GetHand($player)) < 3) return;
    $material = GetMaterial($player);
    $sidearmMZ = null;
    for($i = 0; $i < count($material); ++$i) {
        if(!$material[$i]->removed && $material[$i]->CardID === "p4lgdlx7md") {
            if(!CanPlayerUseCardElement($player, $material[$i]->CardID, false, false)) return;
            $sidearmMZ = "myMaterial-" . $i;
            break;
        }
    }
    if($sidearmMZ === null) return;
    DecisionQueueController::AddDecision($player, "MZMAYCHOOSE", $sidearmMZ, 1, tooltip:"Pay_3_to_activate_Framework_Sidearm_from_material_deck?");
    DecisionQueueController::AddDecision($player, "CUSTOM", "FrameworkSidearmMaterialActivate", 1);
};

$customDQHandlers["FrameworkSidearmMaterialActivate"] = function($player, $parts, $lastDecision) {
    if($lastDecision === "-" || $lastDecision === "" || $lastDecision === "PASS") return;
    for($i = 0; $i < 3; ++$i) {
        DecisionQueueController::AddDecision($player, "CUSTOM", "ReserveCard", 100);
    }
    DecisionQueueController::AddDecision($player, "CUSTOM", "FrameworkSidearmAfterPay|" . $lastDecision, 1);
};

$customDQHandlers["FrameworkSidearmAfterPay"] = function($player, $parts, $lastDecision) {
    $materialMZ = $parts[0] ?? "";
    if($materialMZ === "") return;
    $obj = GetZoneObject($materialMZ);
    if($obj === null || $obj->removed || $obj->CardID !== "p4lgdlx7md") return;
    $handObj = MZMove($player, $materialMZ, "myHand");
    if($handObj === null) return;
    $hand = GetHand($player);
    $handIdx = count($hand) - 1;
    DecisionQueueController::AddDecision($player, "MZCHOOSE", "myHand-" . $handIdx, 1);
    DecisionQueueController::AddDecision($player, "CUSTOM", "ActivateFrameworkSidearmFromHand", 1);
};

$customDQHandlers["ActivateFrameworkSidearmFromHand"] = function($player, $parts, $lastDecision) {
    if($lastDecision === "-" || $lastDecision === "" || $lastDecision === "PASS") return;
    ActivateCard($player, $lastDecision, true);
};

// Reciprocity, Dorumegia's Call (mSOHJGjrIu): [Tonoris Bonus] activate from material deck while controlling 2+ domains
$customDQHandlers["RECIPROCITY_MATERIAL_CHECK"] = function($player, $parts, $lastDecision) {
    if(!IsTonorisBonusActive($player)) return;
    if(CountDomainsControlled($player) < 2) return;
    $material = GetMaterial($player);
    $reciprocityMZ = null;
    for($i = 0; $i < count($material); ++$i) {
        if(!$material[$i]->removed && $material[$i]->CardID === "mSOHJGjrIu") {
            if(!CanPlayerUseCardElement($player, "mSOHJGjrIu", false, false)) return;
            $reciprocityMZ = "myMaterial-" . $i;
            break;
        }
    }
    if($reciprocityMZ === null) return;
    DecisionQueueController::AddDecision($player, "MZMAYCHOOSE", $reciprocityMZ, 1,
        tooltip:"Activate_Reciprocity_from_material_deck?");
    DecisionQueueController::AddDecision($player, "CUSTOM", "MATERIALIZE", 1);
};

$customDQHandlers["MATERIALIZE"] = function($player, $parts, $lastDecision)
{
    $continueMaterialize = isset($parts[0]) && $parts[0] === "CONTINUE";
    if(!$continueMaterialize && ($lastDecision === "-" || $lastDecision === "" || $lastDecision === "PASS")) return;
    $mzCard = $continueMaterialize ? ($parts[1] ?? "") : $lastDecision;
    if($mzCard === "") return;
    $ignoreCost = $continueMaterialize
        ? (isset($parts[2]) && $parts[2] === "NOCOST")
        : (isset($parts[0]) && $parts[0] === "NOCOST");
    //First pay memory cost (unless cost is being ignored)
    $materializeCard = &GetZoneObject($mzCard);
    if($materializeCard === null || $materializeCard->removed) return;
    if(!CanPlayerUseCardElement($player, $materializeCard->CardID)) return;

    global $AllyLink_Cards;
    if(isset($AllyLink_Cards[$materializeCard->CardID]) && !$continueMaterialize) {
        $allyTargets = GetAllyLinkTargets($player);
        if(empty($allyTargets)) return;
        DecisionQueueController::StoreVariable("linkTargetMZ", "");
        DecisionQueueController::StoreVariable("linkTargetCardID", "");
        DecisionQueueController::AddDecision($player, "MZCHOOSE", implode("&", $allyTargets), 1,
            tooltip:"Choose_ally_to_link");
        DecisionQueueController::AddDecision($player, "CUSTOM", "DeclareAllyLinkTarget", 1);
        $continueParam = $ignoreCost
            ? "MATERIALIZE|CONTINUE|" . $mzCard . "|NOCOST"
            : "MATERIALIZE|CONTINUE|" . $mzCard;
        DecisionQueueController::AddDecision($player, "CUSTOM", $continueParam, 1);
        return;
    }

    global $ChampionLink_Cards;
    if(isset($ChampionLink_Cards[$materializeCard->CardID]) && !$continueMaterialize) {
        $championTargets = ZoneSearch("myField", ["CHAMPION"]);
        if(empty($championTargets)) return;
        DecisionQueueController::StoreVariable("championLinkTargetMZ", "");
        DecisionQueueController::StoreVariable("championLinkTargetCardID", "");
        DecisionQueueController::AddDecision($player, "MZCHOOSE", implode("&", $championTargets), 1,
            tooltip:"Choose_champion_to_link");
        DecisionQueueController::AddDecision($player, "CUSTOM", "DeclareChampionLinkTarget", 1);
        $continueParam = $ignoreCost
            ? "MATERIALIZE|CONTINUE|" . $mzCard . "|NOCOST"
            : "MATERIALIZE|CONTINUE|" . $mzCard;
        DecisionQueueController::AddDecision($player, "CUSTOM", $continueParam, 1);
        return;
    }

    global $UnitLink_Cards;
    if(isset($UnitLink_Cards[$materializeCard->CardID]) && !$continueMaterialize) {
        $unitTargets = array_merge(ZoneSearch("myField", ["ALLY"]), ZoneSearch("myField", ["CHAMPION"]));
        if(empty($unitTargets)) return;
        DecisionQueueController::StoreVariable("unitLinkTargetMZ", "");
        DecisionQueueController::StoreVariable("unitLinkTargetCardID", "");
        DecisionQueueController::AddDecision($player, "MZCHOOSE", implode("&", $unitTargets), 1,
            tooltip:"Choose_unit_to_link");
        DecisionQueueController::AddDecision($player, "CUSTOM", "DeclareUnitLinkTarget", 1);
        $continueParam = $ignoreCost
            ? "MATERIALIZE|CONTINUE|" . $mzCard . "|NOCOST"
            : "MATERIALIZE|CONTINUE|" . $mzCard;
        DecisionQueueController::AddDecision($player, "CUSTOM", $continueParam, 1);
        return;
    }

    // Preserve replacement (temporary rule): when you would materialize, return the
    // selected card to hand instead if it is not CHAMPION or REGALIA.
    // This is not a materialization.
    $materializeCardType = CardType($materializeCard->CardID);
    if(!PropertyContains($materializeCardType, "CHAMPION")
        && !PropertyContains($materializeCardType, "REGALIA")) {
        MZMove($player, $mzCard, "myHand");
        return;
    }

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

        // Flawless Spirit of Mordred (cXEI5vo6iG): can only level up into a "Mordred" champion
        foreach($field as &$fObj) {
            if(!$fObj->removed && $fObj->CardID === "cXEI5vo6iG" && $fObj->Controller == $player) {
                if(strpos(CardName($materializeCard->CardID), "Mordred") !== 0) return;
                break;
            }
        }
    }

    $declaredMemoryCost = CardCost_memory($materializeCard->CardID);
    $memoryCost = $ignoreCost ? 0 : ($declaredMemoryCost < 0 ? $declaredMemoryCost : CardMemoryCost($materializeCard));
    $extraReserveCost = 0;

    // Inert Sword (2s08hssegf): "pay (2)" is reserve payment, not memory payment.
    // The generated modifier currently contributes +2 to materialize memory cost,
    // so convert that portion into reserve cost here.
    if($materializeCard->CardID === "2s08hssegf" && !$ignoreCost) {
        $extraReserveCost = 2;
        $memoryCost = max(0, $memoryCost - 2);
        if(CountAvailableReservePayments($player) < $extraReserveCost) {
            AutoUndoMaterializeCostFailure($player);
            return;
        }
    }

    // Dragon's Dawn (9f92917r84): additional cost to materialize — banish 3 fire from graveyard
    if($materializeCard->CardID === "9f92917r84" && !$ignoreCost) {
        $fireGY = ZoneSearch("myGraveyard", cardElements: ["FIRE"]);
        if(count($fireGY) < 3) {
            AutoUndoMaterializeCostFailure($player);
            return;
        }
        DecisionQueueController::AddDecision($player, "MZCHOOSE", implode("&", $fireGY), 1,
            tooltip:"Banish_fire_card_from_graveyard_(1/3)");
        DecisionQueueController::AddDecision($player, "CUSTOM", "DragonsDawnBanish|" . $mzCard . "|1|" . $memoryCost, 1);
        return;
    }

    // Dusksoul Stone (u25fuv184p): additional cost to materialize — banish 2 ally cards from a single graveyard
    if($materializeCard->CardID === "u25fuv184p" && !$ignoreCost) {
        $eligible = [];
        $myAllies = ZoneSearch("myGraveyard", ["ALLY"]);
        $theirAllies = ZoneSearch("theirGraveyard", ["ALLY"]);
        if(count($myAllies) >= 2) $eligible = array_merge($eligible, $myAllies);
        if(count($theirAllies) >= 2) $eligible = array_merge($eligible, $theirAllies);
        if(empty($eligible)) {
            AutoUndoMaterializeCostFailure($player);
            return;
        }
        DecisionQueueController::AddDecision($player, "MZCHOOSE", implode("&", $eligible), 1,
            tooltip:"Banish_ally_card_from_a_single_graveyard_(1/2)");
        DecisionQueueController::AddDecision($player, "CUSTOM", "DusksoulStoneMaterializeCost|" . $mzCard . "|" . $memoryCost, 1);
        return;
    }

    // Vernal Talisman (dW5uyngvJW): additional cost to materialize — banish 2 preserved cards from material deck
    if($materializeCard->CardID === "dW5uyngvJW" && !$ignoreCost) {
        $preserved = GetPreservedMaterialChoices($player, "myMaterial");
        if(count($preserved) < 2) {
            AutoUndoMaterializeCostFailure($player);
            return;
        }
        DecisionQueueController::AddDecision($player, "MZCHOOSE", implode("&", $preserved), 1,
            tooltip:"Banish_preserved_card_from_material_(1/2)");
        DecisionQueueController::AddDecision($player, "CUSTOM",
            "VernalTalismanMaterializeCost|" . $mzCard . "|" . $memoryCost, 1);
        return;
    }

    // Coronal of Rejuvenation (uvgflagxbb): additional cost to materialize — banish 1 preserved card from material deck
    if($materializeCard->CardID === "uvgflagxbb" && !$ignoreCost) {
        $preserved = GetPreservedMaterialChoices($player, "myMaterial");
        if(empty($preserved)) {
            AutoUndoMaterializeCostFailure($player);
            return;
        }
        DecisionQueueController::AddDecision($player, "MZCHOOSE", implode("&", $preserved), 1,
            tooltip:"Banish_preserved_card_from_material_(Coronal)");
        DecisionQueueController::AddDecision($player, "CUSTOM",
            "CoronalMaterializeCost|" . $mzCard . "|" . $memoryCost, 1);
        return;
    }

    // Clarent, Reimagined (kINobk9KQA): [Lorraine Bonus] may banish Clarent, Sword of Peace and up to one other Sword Regalia from material to pay memory.
    if($materializeCard->CardID === "kINobk9KQA" && !$ignoreCost && $memoryCost > 0 && IsLorraineBonusActive($player)) {
        $clarentChoices = GetClarentReimaginedMaterialChoices($mzCard);
        if($clarentChoices !== "") {
            $maxChoices = min(2, count(explode("&", $clarentChoices)));
            DecisionQueueController::AddDecision($player, "MZMULTICHOOSE",
                "0|" . $maxChoices . "|" . $clarentChoices, 1,
                tooltip:"Banish_Clarent,_Sword_of_Peace_and_up_to_1_other_Sword_Regalia");
            DecisionQueueController::AddDecision($player, "CUSTOM", "ClarentReimaginedMatCost|" . $mzCard . "|" . $memoryCost, 1);
            return;
        }
    }

    // Shardforged Blade (Y34Imzlr0n): you may sacrifice a Memorite object to pay for 1 memory.
    if($materializeCard->CardID === "Y34Imzlr0n" && !$ignoreCost && $memoryCost > 0) {
        $memorites = [];
        global $playerID;
        $myField = ($player == $playerID) ? "myField" : "theirField";
        $field = GetZone($myField);
        for($i = 0; $i < count($field); ++$i) {
            $fieldObj = $field[$i];
            if($fieldObj->removed) continue;
            if($fieldObj->Controller != $player) continue;
            if(!PropertyContains(EffectiveCardSubtypes($fieldObj), "MEMORITE")) continue;
            $memorites[] = $myField . "-" . $i;
        }
        if(!empty($memorites)) {
            DecisionQueueController::AddDecision($player, "MZMAYCHOOSE", implode("&", $memorites), 1,
                tooltip:"Sacrifice_a_Memorite_object_to_pay_1_memory?");
            DecisionQueueController::AddDecision($player, "CUSTOM",
                "ShardforgedBladeMaterializeCost|" . $mzCard . "|" . $memoryCost . "|" . $extraReserveCost, 1);
            return;
        }
    }

    if($memoryCost < 0) {
        $maxX = CountAvailableMaterializeMemoryPayments($player);
        DecisionQueueController::AddDecision($player, "NUMBERCHOOSE", "0|" . $maxX, 1,
            tooltip:"Choose_X_memory_to_pay_for_materialize");
        DecisionQueueController::AddDecision($player, "CUSTOM",
            "MaterializeXCost|" . $mzCard . "|" . $extraReserveCost . "|" . $maxX, 1);
        return;
    }

    if($memoryCost > 0 || $extraReserveCost > 0) {
        if($materializeCard->CardID === "mDN1CI9IEe") {
            $floating = ZoneSearch("myGraveyard", floatingMemoryOnly:true);
            if(count($floating) < $memoryCost) return;
            DecisionQueueController::AddDecision($player, "MZCHOOSE", implode("&", $floating), 1,
                tooltip:"Banish_floating_memory_for_Sealed_Blade_(1_of_" . $memoryCost . ")");
            DecisionQueueController::AddDecision($player, "CUSTOM", "SealedBladeFloatingCost|" . $lastDecision . "|" . $memoryCost . "|1", 1);
            return;
        }
        QueueMaterializePayment($player, $mzCard, $memoryCost, $extraReserveCost);
        return; // Materialize() will be called by FINISHPAYMATERIALIZE after cost is paid
    }
    //Then materialize the card (cost is 0, so it resolves immediately)
    Materialize($player, $mzCard);
};

$customDQHandlers["ShardforgedBladeMaterializeCost"] = function($player, $parts, $lastDecision) {
    $mzCard = $parts[0] ?? "";
    $memoryCost = intval($parts[1] ?? 0);
    $extraReserveCost = intval($parts[2] ?? 0);
    if($mzCard === "") return;

    if($lastDecision !== "-" && $lastDecision !== "" && $lastDecision !== "PASS") {
        $chosenObj = GetZoneObject($lastDecision);
        if($chosenObj !== null && !$chosenObj->removed && $chosenObj->Controller == $player
            && PropertyContains(EffectiveCardSubtypes($chosenObj), "MEMORITE")) {
            DoSacrificeFighter($player, $lastDecision);
            $memoryCost = max(0, $memoryCost - 1);
        }
    }

    if($memoryCost > 0 || $extraReserveCost > 0) {
        $materializeCard = GetZoneObject($mzCard);
        if($materializeCard === null || $materializeCard->removed) return;

        if($materializeCard->CardID === "mDN1CI9IEe") {
            $floating = ZoneSearch("myGraveyard", floatingMemoryOnly:true);
            if(count($floating) < $memoryCost) return;
            DecisionQueueController::AddDecision($player, "MZCHOOSE", implode("&", $floating), 1,
                tooltip:"Banish_floating_memory_for_Sealed_Blade_(1_of_" . $memoryCost . ")");
            DecisionQueueController::AddDecision($player, "CUSTOM", "SealedBladeFloatingCost|" . $mzCard . "|" . $memoryCost . "|1", 1);
            return;
        }
        QueueMaterializePayment($player, $mzCard, $memoryCost, $extraReserveCost);
        return;
    }

    Materialize($player, $mzCard);
};

$customDQHandlers["SealedBladeFloatingCost"] = function($player, $parts, $lastDecision) {
    if($lastDecision === "-" || $lastDecision === "" || $lastDecision === "PASS") return;
    $mzCard = $parts[0] ?? "";
    $memoryCost = isset($parts[1]) ? intval($parts[1]) : 0;
    $paid = isset($parts[2]) ? intval($parts[2]) : 1;
    MZMove($player, $lastDecision, "myBanish");
    if($paid >= $memoryCost) {
        Materialize($player, $mzCard);
        return;
    }
    $floating = ZoneSearch("myGraveyard", floatingMemoryOnly:true);
    if(empty($floating)) return;
    DecisionQueueController::AddDecision($player, "MZCHOOSE", implode("&", $floating), 1,
        tooltip:"Banish_floating_memory_for_Sealed_Blade_(" . ($paid + 1) . "_of_" . $memoryCost . ")");
    DecisionQueueController::AddDecision($player, "CUSTOM", "SealedBladeFloatingCost|" . $mzCard . "|" . $memoryCost . "|" . ($paid + 1), 1);
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
            QueueMaterializeFloatingPaymentChoice($player, $memoryCost);
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
        QueueMaterializeFloatingPaymentChoice($player, $memoryCost);
        DecisionQueueController::AddDecision($player, "CUSTOM", "FINISHPAYMATERIALIZE", 2, dontSkipOnPass:1);
    }
    Materialize($player, $mzCard);
};

$customDQHandlers["VernalTalismanMaterializeCost"] = function($player, $parts, $lastDecision) {
    if($lastDecision === "-" || $lastDecision === "" || $lastDecision === "PASS") return;
    $mzCard = $parts[0];
    $memoryCost = intval($parts[1]);
    MZMove($player, $lastDecision, "myBanish");

    $preserved = GetPreservedMaterialChoices($player, "myMaterial");
    if(empty($preserved)) return;

    DecisionQueueController::AddDecision($player, "MZCHOOSE", implode("&", $preserved), 1,
        tooltip:"Banish_preserved_card_from_material_(2/2)");
    DecisionQueueController::AddDecision($player, "CUSTOM",
        "VernalTalismanMaterializeCostFinish|" . $mzCard . "|" . $memoryCost, 1);
};

$customDQHandlers["VernalTalismanMaterializeCostFinish"] = function($player, $parts, $lastDecision) {
    if($lastDecision === "-" || $lastDecision === "" || $lastDecision === "PASS") return;
    $mzCard = $parts[0];
    $memoryCost = intval($parts[1]);
    MZMove($player, $lastDecision, "myBanish");
    if($memoryCost > 0) {
        DecisionQueueController::StoreVariable("MemoryCost", $memoryCost);
        QueueMaterializeFloatingPaymentChoice($player, $memoryCost);
        DecisionQueueController::AddDecision($player, "CUSTOM", "FINISHPAYMATERIALIZE", 2, dontSkipOnPass:1);
    }
    Materialize($player, $mzCard);
};

$customDQHandlers["CoronalMaterializeCost"] = function($player, $parts, $lastDecision) {
    if($lastDecision === "-" || $lastDecision === "" || $lastDecision === "PASS") return;
    $mzCard = $parts[0];
    $memoryCost = intval($parts[1]);
    MZMove($player, $lastDecision, "myBanish");
    if($memoryCost > 0) {
        DecisionQueueController::StoreVariable("MemoryCost", $memoryCost);
        QueueMaterializeFloatingPaymentChoice($player, $memoryCost);
        DecisionQueueController::AddDecision($player, "CUSTOM", "FINISHPAYMATERIALIZE", 2, dontSkipOnPass:1);
    }
    Materialize($player, $mzCard);
};

$customDQHandlers["ClarentReimaginedMatCost"] = function($player, $parts, $lastDecision) {
    $mzCard = $parts[0] ?? "";
    $remainingCost = intval($parts[1] ?? 0);
    if($mzCard === "") return;

    $selected = [];
    if($lastDecision !== "-" && $lastDecision !== "" && $lastDecision !== "PASS") {
        $selected = explode("&", $lastDecision);
    }

    $validSelections = GetClarentReimaginedValidSelections($mzCard, $selected);
    foreach($validSelections as $selectedMZ) {
        MZMove($player, $selectedMZ, "myBanish");
        $remainingCost = max(0, $remainingCost - 1);
    }

    ClarentReimaginedFinalizeMaterialize($player, $mzCard, $remainingCost);
};

function ClarentReimaginedFinalizeMaterialize($player, $mzCard, $memoryCost) {
    if($memoryCost > 0) {
        DecisionQueueController::StoreVariable("MemoryCost", $memoryCost);
        QueueMaterializeFloatingPaymentChoice($player, $memoryCost);
        DecisionQueueController::AddDecision($player, "CUSTOM", "FINISHPAYMATERIALIZE", 2, dontSkipOnPass:1);
    }
    Materialize($player, $mzCard);
}

function GetClarentReimaginedMaterialChoices($mzCard) {
    $material = GetZone("myMaterial");
    $choices = [];
    for($i = 0; $i < count($material); ++$i) {
        if($material[$i]->removed) continue;
        $candidateMZ = "myMaterial-" . $i;
        if($candidateMZ === $mzCard) continue;
        if($material[$i]->CardID === "m31WVJ9F04") {
            $choices[] = $candidateMZ;
            continue;
        }
        if(!PropertyContains(CardType($material[$i]->CardID), "REGALIA")) continue;
        if(!PropertyContains(CardSubtypes($material[$i]->CardID), "SWORD")) continue;
        $choices[] = $candidateMZ;
    }
    return implode("&", $choices);
}

function GetClarentReimaginedValidSelections($mzCard, $selected) {
    if(empty($selected)) return [];

    $candidateSet = [];
    $candidates = GetClarentReimaginedMaterialChoices($mzCard);
    if($candidates !== "") {
        foreach(explode("&", $candidates) as $candidateMZ) {
            if($candidateMZ === "") continue;
            $candidateSet[$candidateMZ] = true;
        }
    }

    $clarentChoice = null;
    $otherChoice = null;
    foreach($selected as $selectedMZ) {
        if($selectedMZ === "" || $selectedMZ === "-" || $selectedMZ === "PASS") continue;
        if(!isset($candidateSet[$selectedMZ])) continue;
        $selectedObj = GetZoneObject($selectedMZ);
        if($selectedObj === null || $selectedObj->removed) continue;
        if($selectedObj->CardID === "m31WVJ9F04") {
            if($clarentChoice === null) $clarentChoice = $selectedMZ;
            continue;
        }
        if($otherChoice === null) $otherChoice = $selectedMZ;
    }

    if($clarentChoice === null) return [];

    $validSelections = [$clarentChoice];
    if($otherChoice !== null) $validSelections[] = $otherChoice;

    usort($validSelections, function($left, $right) {
        $leftIndex = intval(substr($left, strrpos($left, "-") + 1));
        $rightIndex = intval(substr($right, strrpos($right, "-") + 1));
        return $rightIndex <=> $leftIndex;
    });

    return $validSelections;
}

$customDQHandlers["PAYFLOATING"] = function($player, $parts, $lastDecision) {
    $selected = [];
    if($lastDecision !== "-" && $lastDecision !== "" && $lastDecision !== "PASS") {
        // MZMULTICHOOSE returns "&"-delimited selections; tolerate legacy single-select fallback.
        $selected = explode("&", $lastDecision);
    }
    $paid = 0;
    foreach($selected as $mzCard) {
        if($mzCard === "" || $mzCard === "-" || $mzCard === "PASS") continue;
        $banishedObj = GetZoneObject($mzCard);
        if($banishedObj === null || $banishedObj->removed) continue;
        $banishedCardID = $banishedObj->CardID;
        MZMove($player, $mzCard, "myBanish");
        if($banishedCardID !== null) {
            PelagicFatestoneOnFloatingBanished($player, $banishedCardID);
            WeavingManastreamOnFloatingBanished($player, $banishedCardID);
        }
        ++$paid;
    }
    $toPay = max(0, intval($parts[0] ?? 0) - $paid);
    DecisionQueueController::StoreVariable("MemoryCost", $toPay);
    return $toPay;
};

$customDQHandlers["MaterializeXCost"] = function($player, $parts, $lastDecision) {
    $mzCard = $parts[0] ?? "";
    $extraReserveCost = intval($parts[1] ?? 0);
    $maxX = intval($parts[2] ?? 0);
    if($mzCard === "") return;

    $x = intval($lastDecision);
    if($x < 0) $x = 0;
    if($x > $maxX) $x = $maxX;
    DecisionQueueController::StoreVariable("PendingMaterializeXCost", strval($x));

    if($x > 0 || $extraReserveCost > 0) {
        QueueMaterializePayment($player, $mzCard, $x, $extraReserveCost);
        return;
    }

    Materialize($player, $mzCard);
};

$customDQHandlers["FINISHPAYMATERIALIZE"] = function($player, $parts, $lastDecision) {
    $memoryCost = DecisionQueueController::GetVariable("MemoryCost");
    for($i = 0; $i < $memoryCost; ++$i) {
        MZMove($player, "myMemory-" . $i, "myBanish");//TODO: Make random
    }
    DecisionQueueController::ClearVariable("MemoryCost");
    // If the MATERIALIZE handler deferred the Materialize() call (standard cost path),
    // complete it now so Enter abilities fire only after the memory cost is fully paid.
    $pendingMatCard = DecisionQueueController::GetVariable("PendingMatCard");
    if($pendingMatCard !== null && $pendingMatCard !== "") {
        DecisionQueueController::ClearVariable("PendingMatCard");
        Materialize($player, $pendingMatCard);
    }
};

function DoMaterialize($player, $mzCard) {
    global $customDQHandlers;
    $sourceObject = &GetZoneObject($mzCard);
    $sourceId = $sourceObject->CardID;
    $pendingMaterializeXCost = DecisionQueueController::GetVariable("PendingMaterializeXCost");
    DecisionQueueController::ClearVariable("PendingMaterializeXCost");
    $chosenMaterializeX = ($pendingMaterializeXCost === null || $pendingMaterializeXCost === "")
        ? null
        : intval($pendingMaterializeXCost);
    $resolvedMaterializeMemoryCost = $chosenMaterializeX ?? CardMemoryCost($sourceObject);
    $polarisFromMaterial = ($sourceId === "41t71u4bzz"
        && DecisionQueueController::GetVariable("polarisActivateFromMaterial") === "YES");
    if($polarisFromMaterial) {
        DecisionQueueController::ClearVariable("polarisActivateFromMaterial");
    }

    // Obstinate Cragback: opponents can't materialize cards with memory cost 0.
    if($resolvedMaterializeMemoryCost === 0) {
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
        $existingStatus = null;
        $existingDamage = 0;
        $existingCounters = [];
        $existingTurnEffects = [];

        for($i = 0; $i < count($field); ++$i) {
            if(!$field[$i]->removed && PropertyContains(CardType($field[$i]->CardID), "CHAMPION") && $field[$i]->Controller == $player) {
                $existingChampionIdx = $i;
                $existingChampionCardID = $field[$i]->CardID;
                $existingSubcards = is_array($field[$i]->Subcards) ? $field[$i]->Subcards : [];
                $existingStatus = intval($field[$i]->Status ?? 0);
                $existingDamage = $field[$i]->Damage;
                $existingCounters = is_array($field[$i]->Counters) ? $field[$i]->Counters : [];
                $existingTurnEffects = is_array($field[$i]->TurnEffects) ? $field[$i]->TurnEffects : [];
                break;
            }
        }

        if($existingChampionCardID !== null && !CanChampionLevelUpIntoCard($player, $sourceId)) {
            return;
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

        // Pre-populate inherited champion state onto the incoming card BEFORE MZMove.
        // AddField triggers Enter during MZMove via FieldAfterAdd, so Enter must see
        // lineage/counters/damage/turn effects already present.
        if(!empty($newSubcards) || $existingDamage > 0 || !empty($existingCounters) || !empty($existingTurnEffects)) {
            $incomingObj = &GetZoneObject($mzCard);
            if($incomingObj !== null) {
                if(!empty($newSubcards)) {
                    $incomingObj->Subcards = $newSubcards;
                }

                if($existingDamage > 0) {
                    $incomingObj->Damage = intval($incomingObj->Damage ?? 0) + $existingDamage;
                }

                if(!isset($incomingObj->Counters) || !is_array($incomingObj->Counters)) {
                    $incomingObj->Counters = [];
                }
                foreach($existingCounters as $counterType => $counterVal) {
                    if(!isset($incomingObj->Counters[$counterType])) {
                        $incomingObj->Counters[$counterType] = $counterVal;
                    } else {
                        $incomingObj->Counters[$counterType] += $counterVal;
                    }
                }

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

        // Preserve rested/awake state when leveling up an existing champion.
        if($newObj !== null && $existingStatus !== null) {
            $newObj->Status = $existingStatus;
        }

        // Inherited state has already been transferred pre-MZMove so Enter saw it.

        // Track that a champion leveled up this turn (for Invigorated Slash etc.)
        AddGlobalEffects($player, "LEVELED_UP_THIS_TURN");
        if(intval(CardLevel($sourceId)) === 3) {
            AngelicChannelingLevel3($player);
        }

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
                if(ChampionHasInLineage($player, "4upufooz13") || ChampionHasInLineage($player, "KqBosnU7pU")) {
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
                    $scUniqueID = intval($field[$sci]->UniqueID ?? 0);
                    if($scUniqueID <= 0) continue;
                    DecisionQueueController::AddDecision($player, "YESNO", "-", 1,
                        tooltip:"Banish_Suspicious_Concoction_to_draw_into_memory_and_recover_2?");
                    DecisionQueueController::AddDecision($player, "CUSTOM",
                        "SuspiciousConcoctionLevelUp|$scUniqueID", 1);
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

        // Fractal of Waves (qVtWCAx3zb): when your champion levels up into base level 3,
        // you may sacrifice it. If you do, draw two cards into memory.
        if(intval(CardLevel($sourceId)) === 3) {
            global $playerID;
            $fZone = ($player == $playerID) ? "myField" : "theirField";
            $field = GetZone($fZone);
            // Iterate backwards so queued mzIDs remain stable if an earlier Fractal
            // gets sacrificed and the field compacts before later triggers resolve.
            for($fwi = count($field) - 1; $fwi >= 0; --$fwi) {
                if($field[$fwi]->removed || $field[$fwi]->CardID !== "qVtWCAx3zb" || HasNoAbilities($field[$fwi])) continue;
                $fractalMZ = $fZone . "-" . $fwi;
                DecisionQueueController::AddDecision($player, "YESNO", "-", 1,
                    tooltip:"Sacrifice_Fractal_of_Waves_to_draw_2_into_memory?");
                DecisionQueueController::AddDecision($player, "CUSTOM", "FractalOfWavesLevel3|" . $fractalMZ, 1);
            }
        }

        // Nuriel, Seraphic Paladin (b9lli2PE7I): whenever your champion levels up,
        // if Nuriel is imbued, put a bulwark counter on it.
        {
            global $playerID;
            $fZone = ($player == $playerID) ? "myField" : "theirField";
            $field = GetZone($fZone);
            for($ni = 0; $ni < count($field); ++$ni) {
                if(!$field[$ni]->removed && $field[$ni]->CardID === "b9lli2PE7I" && !HasNoAbilities($field[$ni])
                    && in_array("IMBUED", $field[$ni]->TurnEffects ?? [])) {
                    AddCounters($player, $fZone . "-" . $ni, "bulwark", 1);
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
        if($polarisFromMaterial) {
            $sourceObject->Status = 1;
        }
        $newObj = MZMove($player, $mzCard, "myField");
        if($newObj !== null && $sourceId === "1keruycrwi" && $chosenMaterializeX !== null && $chosenMaterializeX > 0) {
            AddCounters($player, $newObj->GetMzID(), "gem", $chosenMaterializeX);
        }
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

    // Alice, Phantom Monarch (emqOANitoD): whenever you play an advanced element card
    // while no Curse in Alice's lineage, deal 7 unpreventable damage to your champion.
    if(ChampionHasInLineage($player, "emqOANitoD")) {
        if(IsAdvancedElementCard($sourceId) && CountCursesInLineage($player) === 0) {
            DealChampionDamage($player, 7);
        }
    }

    // --- Domain Upkeep: "Whenever you materialize a card, sacrifice [domain]" ---
    // After any materialize, check if the player controls domains with materialize-sacrifice upkeep.
    // Domains tagged with NO_UPKEEP (via Right of Realm) skip this trigger.
    DomainMaterializeSacrifice($player);

    // Craggy Fatestone (h8n1520m2d): [Guo Jia Bonus] whenever opponent materializes a card
    // with memory cost 0, put a buff counter on Craggy Fatestone
    {
        $matMemCost = $resolvedMaterializeMemoryCost;
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

$customDQHandlers["FractalOfWavesLevel3"] = function($player, $parts, $lastDecision) {
    if($lastDecision !== "YES") return;
    $fractalMZ = $parts[0] ?? "";
    if($fractalMZ === "") return;
    $obj = GetZoneObject($fractalMZ);
    if($obj === null || $obj->removed || $obj->CardID !== "qVtWCAx3zb") return;
    DoSacrificeFighter($player, $fractalMZ);
    DecisionQueueController::CleanupRemovedCards();
    DrawIntoMemory($player, 2);
};

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

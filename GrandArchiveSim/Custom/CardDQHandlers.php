<?php

/**
 * Custom DQ handler: Blazing Throw (iohZMWh5v5) — move the chosen weapon to the graveyard.
 */
$customDQHandlers["BT_SacrificeWeapon"] = function($player, $parts, $lastDecision) {
    if($lastDecision === "-" || $lastDecision === "") return;
    $obj = GetZoneObject($lastDecision);
    if($obj === null) return;
    OnLeaveField($player, $lastDecision);
    MZMove($player, $lastDecision, "myGraveyard");
    DecisionQueueController::CleanupRemovedCards();
};

/**
 * Custom DQ handler: Intangible Geist (Zu53izIFTX) Enter — recursively put regalia from banishment to material.
 */
$customDQHandlers["Zu53izIFTX_RecurseRegalia"] = function($player, $parts, $lastDecision) {
    if($lastDecision !== "-" && $lastDecision !== "") {
        MZMove($player, $lastDecision, "myMaterial");
        DecisionQueueController::CleanupRemovedCards();
    } else {
        return; // Player passed — stop loop
    }
    $regalias = ZoneSearch("myBanish", ["REGALIA"]);
    if(empty($regalias)) return;
    $choices = implode("&", $regalias);
    DecisionQueueController::AddDecision($player, "MZMAYCHOOSE", $choices, 1);
    DecisionQueueController::AddDecision($player, "CUSTOM", "Zu53izIFTX_RecurseRegalia", 1);
};

/**
 * Custom DQ handlers: Bestial Frenzy (HsaWNAsmAQ)
 * Option A: champion +1 level (AddGlobalEffects)
 * Option B: target Beast ally +1 POWER (AddTurnEffect HsaWNAsmAQ_POWER)
 * Option C: target Beast ally gains cleave (AddTurnEffect HsaWNAsmAQ_CLEAVE)
 * Choose one; [Class Bonus] choose up to two instead.
 */
$customDQHandlers["HsaWNAsmAQ_OptionA"] = function($player, $parts, $lastDecision) {
    $chosen = 0;
    if($lastDecision === "YES") {
        AddGlobalEffects($player, "HsaWNAsmAQ");
        $chosen = 1;
    }
    DecisionQueueController::StoreVariable("BF_chosen", "$chosen");
    $maxChoices = IsClassBonusActive($player) ? 2 : 1;
    if($chosen < $maxChoices) {
        $beasts = ZoneSearch("myField", ["ALLY"], cardSubtypes: ["BEAST"]);
        if(!empty($beasts)) {
            DecisionQueueController::AddDecision($player, "YESNO", "-", 1, "Use_B:_Beast_ally_+1_POWER?");
            DecisionQueueController::AddDecision($player, "CUSTOM", "HsaWNAsmAQ_OptionB", 1);
        } else {
            // No beasts for B — check for C
            DecisionQueueController::AddDecision($player, "YESNO", "-", 1, "Use_C:_Beast_ally_gains_cleave?");
            DecisionQueueController::AddDecision($player, "CUSTOM", "HsaWNAsmAQ_OptionC", 1);
        }
    }
};

$customDQHandlers["HsaWNAsmAQ_OptionB"] = function($player, $parts, $lastDecision) {
    $chosen = intval(DecisionQueueController::GetVariable("BF_chosen"));
    $maxChoices = IsClassBonusActive($player) ? 2 : 1;
    if($lastDecision === "YES") {
        $beasts = ZoneSearch("myField", ["ALLY"], cardSubtypes: ["BEAST"]);
        if(!empty($beasts)) {
            $chosen++;
            DecisionQueueController::StoreVariable("BF_chosen", "$chosen");
            $choices = implode("&", $beasts);
            DecisionQueueController::AddDecision($player, "MZCHOOSE", $choices, 1);
            DecisionQueueController::AddDecision($player, "CUSTOM", "HsaWNAsmAQ_TargetB", 1);
        }
    }
    if($chosen < $maxChoices) {
        $beasts = ZoneSearch("myField", ["ALLY"], cardSubtypes: ["BEAST"]);
        if(!empty($beasts)) {
            DecisionQueueController::AddDecision($player, "YESNO", "-", 1, "Use_C:_Beast_ally_gains_cleave?");
            DecisionQueueController::AddDecision($player, "CUSTOM", "HsaWNAsmAQ_OptionC", 1);
        }
    }
};

$customDQHandlers["HsaWNAsmAQ_TargetB"] = function($player, $parts, $lastDecision) {
    if($lastDecision !== "-" && $lastDecision !== "") {
        AddTurnEffect($lastDecision, "HsaWNAsmAQ_POWER");
    }
};

$customDQHandlers["HsaWNAsmAQ_OptionC"] = function($player, $parts, $lastDecision) {
    if($lastDecision === "YES") {
        $beasts = ZoneSearch("myField", ["ALLY"], cardSubtypes: ["BEAST"]);
        if(!empty($beasts)) {
            $choices = implode("&", $beasts);
            DecisionQueueController::AddDecision($player, "MZCHOOSE", $choices, 1);
            DecisionQueueController::AddDecision($player, "CUSTOM", "HsaWNAsmAQ_TargetC", 1);
        }
    }
};

$customDQHandlers["HsaWNAsmAQ_TargetC"] = function($player, $parts, $lastDecision) {
    if($lastDecision !== "-" && $lastDecision !== "") {
        AddTurnEffect($lastDecision, "HsaWNAsmAQ_CLEAVE");
    }
};

// =============================================================================
// Decree Card Handlers (Imbue modal pattern)
// =============================================================================

/**
 * Verdigris Decree (7cx66hjlgx) — WIND
 * Modes: A=Suppress ally, B=+2 POWER ally, C=Destroy phantasia
 */
$customDQHandlers["VerdigrisDecree_Process"] = function($player, $parts, $lastDecision) {
    $choices = DecisionQueueController::GetVariable("VD_choices");
    if($choices === "-" || $choices === "") return;
    $modes = explode(",", $choices);
    foreach($modes as $modeIdx) {
        $modeIdx = trim($modeIdx);
        switch($modeIdx) {
            case "0": // Suppress up to one target ally
                $allies = array_merge(
                    ZoneSearch("myField", ["ALLY"]),
                    ZoneSearch("theirField", ["ALLY"])
                );
                $allies = FilterSpellshroudTargets($allies);
                if(!empty($allies)) {
                    $targets = implode("&", $allies);
                    DecisionQueueController::AddDecision($player, "MZMAYCHOOSE", $targets, 1, "Suppress_target_ally");
                    DecisionQueueController::AddDecision($player, "CUSTOM", "VerdigrisDecree_ExecA", 1);
                }
                break;
            case "1": // Target ally gets +2 POWER until end of turn
                $allies = array_merge(
                    ZoneSearch("myField", ["ALLY"]),
                    ZoneSearch("theirField", ["ALLY"])
                );
                $allies = FilterSpellshroudTargets($allies);
                if(!empty($allies)) {
                    $targets = implode("&", $allies);
                    DecisionQueueController::AddDecision($player, "MZMAYCHOOSE", $targets, 1, "Target_ally_+2_power");
                    DecisionQueueController::AddDecision($player, "CUSTOM", "VerdigrisDecree_ExecB", 1);
                }
                break;
            case "2": // Destroy up to one target phantasia
                $phantasias = array_merge(
                    ZoneSearch("myField", ["PHANTASIA"]),
                    ZoneSearch("theirField", ["PHANTASIA"])
                );
                $phantasias = FilterSpellshroudTargets($phantasias);
                if(!empty($phantasias)) {
                    $targets = implode("&", $phantasias);
                    DecisionQueueController::AddDecision($player, "MZMAYCHOOSE", $targets, 1, "Destroy_target_phantasia");
                    DecisionQueueController::AddDecision($player, "CUSTOM", "VerdigrisDecree_ExecC", 1);
                }
                break;
        }
    }
};

$customDQHandlers["VerdigrisDecree_ExecA"] = function($player, $parts, $lastDecision) {
    if($lastDecision === "-" || $lastDecision === "") return;
    SuppressAlly($player, $lastDecision);
    DecisionQueueController::CleanupRemovedCards();
};

$customDQHandlers["VerdigrisDecree_ExecB"] = function($player, $parts, $lastDecision) {
    if($lastDecision === "-" || $lastDecision === "") return;
    AddTurnEffect($lastDecision, "7cx66hjlgx");
};

$customDQHandlers["VerdigrisDecree_ExecC"] = function($player, $parts, $lastDecision) {
    if($lastDecision === "-" || $lastDecision === "") return;
    $obj = GetZoneObject($lastDecision);
    if($obj === null) return;
    $owner = $obj->Owner;
    OnLeaveField($player, $lastDecision);
    $gravZone = ($player == $owner) ? "myGraveyard" : "theirGraveyard";
    MZMove($player, $lastDecision, $gravZone);
    DecisionQueueController::CleanupRemovedCards();
};

/**
 * Vermilion Decree (tjej4mcnqs) — FIRE
 * Modes: A=3 dmg to champion, B=2 dmg to ally, C=Each player draws
 */
$customDQHandlers["VermilionDecree_Process"] = function($player, $parts, $lastDecision) {
    $choices = DecisionQueueController::GetVariable("VM_choices");
    if($choices === "-" || $choices === "") return;
    $modes = explode(",", $choices);
    foreach($modes as $modeIdx) {
        $modeIdx = trim($modeIdx);
        switch($modeIdx) {
            case "0": // Deal 3 damage to up to one target champion
                $champions = array_merge(
                    ZoneSearch("myField", ["CHAMPION"]),
                    ZoneSearch("theirField", ["CHAMPION"])
                );
                $champions = FilterSpellshroudTargets($champions);
                if(!empty($champions)) {
                    $targets = implode("&", $champions);
                    DecisionQueueController::AddDecision($player, "MZMAYCHOOSE", $targets, 1, "Deal_3_damage_to_champion");
                    DecisionQueueController::AddDecision($player, "CUSTOM", "VermilionDecree_ExecA", 1);
                }
                break;
            case "1": // Deal 2 damage to up to one target ally
                $allies = array_merge(
                    ZoneSearch("myField", ["ALLY"]),
                    ZoneSearch("theirField", ["ALLY"])
                );
                $allies = FilterSpellshroudTargets($allies);
                if(!empty($allies)) {
                    $targets = implode("&", $allies);
                    DecisionQueueController::AddDecision($player, "MZMAYCHOOSE", $targets, 1, "Deal_2_damage_to_ally");
                    DecisionQueueController::AddDecision($player, "CUSTOM", "VermilionDecree_ExecB", 1);
                }
                break;
            case "2": // Each player draws a card
                Draw($player, 1);
                Draw($player == 1 ? 2 : 1, 1);
                break;
        }
    }
};

$customDQHandlers["VermilionDecree_ExecA"] = function($player, $parts, $lastDecision) {
    if($lastDecision === "-" || $lastDecision === "") return;
    DealDamage($player, "tjej4mcnqs", $lastDecision, 3);
};

$customDQHandlers["VermilionDecree_ExecB"] = function($player, $parts, $lastDecision) {
    if($lastDecision === "-" || $lastDecision === "") return;
    DealDamage($player, "tjej4mcnqs", $lastDecision, 2);
};

/**
 * Cerulean Decree (ipl6gt7lh9) — WATER
 * Modes: A=-3 POWER on unit attacks, B=Draw into memory
 * NOTE: Negate mode is NOT implemented (engine lacks negate support).
 */
$customDQHandlers["CeruleanDecree_Process"] = function($player, $parts, $lastDecision) {
    $choices = DecisionQueueController::GetVariable("CD_choices");
    if($choices === "-" || $choices === "") return;
    $modes = explode(",", $choices);
    foreach($modes as $modeIdx) {
        $modeIdx = trim($modeIdx);
        switch($modeIdx) {
            case "0": // Target unit's attacks get -3 POWER until end of turn
                $units = array_merge(
                    ZoneSearch("myField", ["ALLY", "CHAMPION", "PHANTASIA"]),
                    ZoneSearch("theirField", ["ALLY", "CHAMPION", "PHANTASIA"])
                );
                $units = FilterSpellshroudTargets($units);
                if(!empty($units)) {
                    $targets = implode("&", $units);
                    DecisionQueueController::AddDecision($player, "MZMAYCHOOSE", $targets, 1, "Target_unit_-3_power_to_attacks");
                    DecisionQueueController::AddDecision($player, "CUSTOM", "CeruleanDecree_ExecA", 1);
                }
                break;
            case "1": // Draw a card into your memory
                DrawIntoMemory($player, 1);
                break;
        }
    }
};

$customDQHandlers["CeruleanDecree_ExecA"] = function($player, $parts, $lastDecision) {
    if($lastDecision === "-" || $lastDecision === "") return;
    AddTurnEffect($lastDecision, "ipl6gt7lh9-debuff");
};

/**
 * Custom DQ handler: Call the Pack (mdiK8UC78c) — loop once per Animal ally,
 * offering the player the chance to put a Beast from hand onto the field.
 */
$customDQHandlers["CTP_BeastLoop"] = function($player, $parts, $lastDecision) {
    $remaining = intval(DecisionQueueController::GetVariable("CTP_remaining")) - 1;
    DecisionQueueController::StoreVariable("CTP_remaining", "$remaining");
    if($lastDecision !== "-" && $lastDecision !== "") {
        MZMove($player, $lastDecision, "myField");
        DecisionQueueController::CleanupRemovedCards();
    }
    if($remaining <= 0) return;
    $beasts = ZoneSearch("myHand", ["ALLY"], cardSubtypes: ["BEAST"]);
    if(empty($beasts)) return;
    $choices = implode("&", $beasts);
    DecisionQueueController::AddDecision($player, "MZMAYCHOOSE", $choices, 1);
    DecisionQueueController::AddDecision($player, "CUSTOM", "CTP_BeastLoop", 1);
};

/**
 * Anthem of Vitality Harmonize: if harmonize is active, choose Animal/Beast ally for buff counters.
 */
$customDQHandlers["AnthemOfVitalityHarmonize"] = function($player, $parts, $lastDecision) {
    if(!IsHarmonizeActive($player)) return;
    $harmTargets = ZoneSearch("myField", ["ALLY"], cardSubtypes: ["ANIMAL", "BEAST"]);
    if(empty($harmTargets)) return;
    $harmChoices = implode("&", $harmTargets);
    DecisionQueueController::AddDecision($player, "MZCHOOSE", $harmChoices, 1, "Choose_Animal/Beast_ally_for_buff_counters");
    DecisionQueueController::AddDecision($player, "CUSTOM", "AnthemOfVitalityBuff", 1);
};

$customDQHandlers["AnthemOfVitalityBuff"] = function($player, $parts, $lastDecision) {
    if($lastDecision !== "PASS" && $lastDecision !== "-" && !empty($lastDecision)) {
        AddCounters($player, $lastDecision, "buff", 2);
    }
};

/**
 * Rally the Peasants: process MZREARRANGE result.
 * lastDecision format: "ToHand=cardA;Reveal=cardB,cardC;"
 * If player dragged a Human to ToHand, pull it from the deck (already on bottom) into hand.
 */
$customDQHandlers["RallyPeasantsApply"] = function($player, $parts, $lastDecision) {
    $toHandIDs = [];
    foreach(explode(";", $lastDecision) as $pile) {
        $pile = trim($pile);
        if(empty($pile) || strpos($pile, "=") === false) continue;
        [$pileName, $cardStr] = explode("=", $pile, 2);
        $cardStr = trim($cardStr);
        if($pileName === "ToHand" && !empty($cardStr)) {
            $toHandIDs = explode(",", $cardStr);
        }
    }
    if(empty($toHandIDs)) return;
    $chosenID = $toHandIDs[0]; // only take the first one regardless of how many player moved
    $deck = &GetDeck($player);
    $hand = &GetHand($player);
    foreach($deck as $i => $card) {
        if($card->CardID === $chosenID) {
            SetFlashMessage('REVEAL:' . $chosenID);
            $obj = array_splice($deck, $i, 1)[0];
            array_push($hand, $obj);
            return;
        }
    }
};

/**
 * Slay the King On Attack: process YesNo answer to banish from material deck.
 */
$customDQHandlers["SlayTheKingOnAttack"] = function($player, $parts, $lastDecision) {
    if($lastDecision !== "YES") {
        DecisionQueueController::StoreVariable("SlayTheKing_BanishedCardID", "");
        return;
    }
    $matChoices = DecisionQueueController::GetVariable("slayTheKingMats");
    DecisionQueueController::AddDecision($player, "MZCHOOSE", $matChoices, 1, "Choose_card_to_banish");
    DecisionQueueController::AddDecision($player, "CUSTOM", "SlayTheKingBanish", 1);
};

$customDQHandlers["SlayTheKingBanish"] = function($player, $parts, $lastDecision) {
    if($lastDecision === "PASS" || $lastDecision === "-" || empty($lastDecision)) {
        DecisionQueueController::StoreVariable("SlayTheKing_BanishedCardID", "");
        return;
    }
    $obj = GetZoneObject($lastDecision);
    DecisionQueueController::StoreVariable("SlayTheKing_BanishedCardID", $obj->CardID);
    MZMove($player, $lastDecision, "myBanish");
};

/**
 * Slay the King On Kill: process YesNo to play the banished card.
 */
$customDQHandlers["SlayTheKingOnKill"] = function($player, $parts, $lastDecision) {
    $banishedCardID = DecisionQueueController::GetVariable("SlayTheKing_BanishedCardID");
    DecisionQueueController::StoreVariable("SlayTheKing_BanishedCardID", "");
    if($lastDecision !== "YES") return;
    if(empty($banishedCardID) || $banishedCardID === "-") return;
    $banish = GetZone("myBanish");
    for($i = 0; $i < count($banish); ++$i) {
        if(!$banish[$i]->removed && $banish[$i]->CardID === $banishedCardID) {
            DoActivateCard($player, "myBanish-" . $i, true);
            break;
        }
    }
};

/**
 * Innervate Agility: apply stealth or spellshroud to all units you control.
 */
$customDQHandlers["InnervateAgilityApply"] = function($player, $parts, $lastDecision) {
    if($lastDecision === "YES") {
        // Stealth: add TurnEffect to all units (champion + allies)
        AddGlobalEffects($player, "INNERVATE_STEALTH");
    } else {
        // Spellshroud: add TurnEffect to all units
        AddGlobalEffects($player, "INNERVATE_SPELLSHROUD");
    }
};

/**
 * Song of Frost: process YesNo to banish floating-memory GY card instead of reserve cost.
 */
$customDQHandlers["SongOfFrostAltCost"] = function($player, $parts, $lastDecision) {
    $reserveCost = intval($parts[0]);
    if($lastDecision === "YES") {
        // Banish the floating-memory GY card instead of paying reserve
        $floatingGY = [];
        $gy = GetZone("myGraveyard");
        for($i = 0; $i < count($gy); ++$i) {
            if(!$gy[$i]->removed && HasFloatingMemory($gy[$i])) {
                $floatingGY[] = "myGraveyard-" . $i;
            }
        }
        if(!empty($floatingGY)) {
            $choices = implode("&", $floatingGY);
            DecisionQueueController::AddDecision($player, "MZCHOOSE", $choices, 1, "Banish_a_floating-memory_card");
            DecisionQueueController::AddDecision($player, "CUSTOM", "SongOfFrostBanish", 1);
        }
    } else {
        // Pay normal reserve cost
        for($i = 0; $i < $reserveCost; ++$i) {
            DecisionQueueController::AddDecision($player, "CUSTOM", "ReserveCard", 100);
        }
    }
    DecisionQueueController::AddDecision($player, "CUSTOM", "EffectStackOpportunity", 100);
};

$customDQHandlers["SongOfFrostBanish"] = function($player, $parts, $lastDecision) {
    if($lastDecision !== "PASS" && $lastDecision !== "-" && !empty($lastDecision)) {
        MZMove($player, $lastDecision, "myBanish");
        NicoOnFloatingMemoryBanished($player);
    }
};

$customDQHandlers["BrusqueNeigeAltCost"] = function($player, $parts, $lastDecision) {
    $reserveCost = intval($parts[0]);
    if($lastDecision === "YES") {
        $allies = ZoneSearch("myField", ["ALLY"]);
        if(!empty($allies)) {
            $choices = implode("&", $allies);
            DecisionQueueController::AddDecision($player, "MZCHOOSE", $choices, 1, "Sacrifice_an_ally");
            DecisionQueueController::AddDecision($player, "CUSTOM", "BrusqueNeigeSacrifice", 1);
        }
    } else {
        for($i = 0; $i < $reserveCost; ++$i) {
            DecisionQueueController::AddDecision($player, "CUSTOM", "ReserveCard", 100);
        }
    }
    DecisionQueueController::AddDecision($player, "CUSTOM", "EffectStackOpportunity", 100);
};

$customDQHandlers["BrusqueNeigeSacrifice"] = function($player, $parts, $lastDecision) {
    if($lastDecision !== "PASS" && $lastDecision !== "-" && !empty($lastDecision)) {
        DoSacrificeFighter($player, $lastDecision);
    }
};

// Awaken Ombre (OVoHxVwodU): pay X+X additional reserve (variable X chosen via NUMBERCHOOSE)
$customDQHandlers["AwakenOmbreCost"] = function($player, $parts, $lastDecision) {
    $baseReserve = intval($parts[0]);
    $x = intval($lastDecision);
    DecisionQueueController::StoreVariable("awakenOmbreX", strval($x));
    DecisionQueueController::StoreVariable("additionalCostPaid", "NO");
    $totalCost = $baseReserve + (2 * $x);
    for($i = 0; $i < $totalCost; ++$i) {
        DecisionQueueController::AddDecision($player, "CUSTOM", "ReserveCard", 100);
    }
    DecisionQueueController::StoreVariable("isImbued", "NO");
    DecisionQueueController::AddDecision($player, "CUSTOM", "EffectStackOpportunity", 100);
};

// Awaken Ombre (OVoHxVwodU): put up to X ally omens onto field rested, wake if matching element
function AwakenOmbre_Start($player) {
    $x = intval(DecisionQueueController::GetVariable("awakenOmbreX"));
    if($x <= 0) return;
    $allyOmenMZs = GetOmenMZIDs($player);
    // Filter to ally omens only
    $validOmens = [];
    foreach($allyOmenMZs as $mz) {
        $obj = GetZoneObject($mz);
        if($obj !== null && !$obj->removed && PropertyContains(CardType($obj->CardID), "ALLY")) {
            $validOmens[] = $mz;
        }
    }
    if(empty($validOmens)) return;
    DecisionQueueController::StoreVariable("awakenOmbreRemaining", strval($x));
    $omenStr = implode("&", $validOmens);
    DecisionQueueController::AddDecision($player, "MZMAYCHOOSE", $omenStr, 1, tooltip:"Put_an_ally_omen_onto_the_field?");
    DecisionQueueController::AddDecision($player, "CUSTOM", "AwakenOmbrePick", 1);
}

function AwakenOmbre_GetChampionElements($player) {
    $field = &GetField($player);
    $elements = [];
    foreach($field as $obj) {
        if(!$obj->removed && PropertyContains(EffectiveCardType($obj), "CHAMPION") && $obj->Controller == $player) {
            $el = EffectiveCardElement($obj);
            if($el !== null && $el !== "NORM") $elements[] = $el;
            break;
        }
    }
    return $elements;
}

$customDQHandlers["AwakenOmbrePick"] = function($player, $parts, $lastDecision) {
    if($lastDecision === "-" || $lastDecision === "" || $lastDecision === null) return;
    $obj = GetZoneObject($lastDecision);
    if($obj === null || $obj->removed) return;
    $cardElement = CardElement($obj->CardID);
    // Move from banishment to field rested
    MZMove($player, $lastDecision, "myField");
    // Find the newly placed card on field (last entry with matching CardID)
    $field = GetZone("myField");
    $newMZ = null;
    for($i = count($field) - 1; $i >= 0; --$i) {
        if(!$field[$i]->removed && $field[$i]->CardID === $obj->CardID) {
            $newMZ = "myField-" . $i;
            $field[$i]->Status = 1; // Rested
            break;
        }
    }
    // Wake if non-norm element matches champion element
    if($newMZ !== null && $cardElement !== null && $cardElement !== "NORM") {
        $champElements = AwakenOmbre_GetChampionElements($player);
        if(in_array($cardElement, $champElements)) {
            WakeupCard($player, $newMZ);
        }
    }
    $remaining = intval(DecisionQueueController::GetVariable("awakenOmbreRemaining")) - 1;
    if($remaining <= 0) return;
    DecisionQueueController::StoreVariable("awakenOmbreRemaining", strval($remaining));
    // Get fresh list of remaining ally omens
    $allyOmenMZs = GetOmenMZIDs($player);
    $validOmens = [];
    foreach($allyOmenMZs as $mz) {
        $oObj = GetZoneObject($mz);
        if($oObj !== null && !$oObj->removed && PropertyContains(CardType($oObj->CardID), "ALLY")) {
            $validOmens[] = $mz;
        }
    }
    if(empty($validOmens)) return;
    $omenStr = implode("&", $validOmens);
    DecisionQueueController::AddDecision($player, "MZMAYCHOOSE", $omenStr, 1, tooltip:"Put_an_ally_omen_onto_the_field?");
    DecisionQueueController::AddDecision($player, "CUSTOM", "AwakenOmbrePick", 1);
};

/**
 * Custom DQ handler: DeclareAllyLinkTarget — stores the chosen ally mzID
 * and CardID as DQ variables for Ally Link resolution.
 */
$customDQHandlers["DeclareAllyLinkTarget"] = function($player, $parts, $lastDecision) {
    if($lastDecision === "PASS" || $lastDecision === "-" || empty($lastDecision)) {
        DecisionQueueController::StoreVariable("linkTargetMZ", "");
        DecisionQueueController::StoreVariable("linkTargetCardID", "");
        return;
    }
    $targetObj = GetZoneObject($lastDecision);
    DecisionQueueController::StoreVariable("linkTargetMZ", $lastDecision);
    DecisionQueueController::StoreVariable("linkTargetCardID", $targetObj !== null ? $targetObj->CardID : "");
};

/**
 * Custom DQ handler: DeclareWeaponLinkTarget — stores the chosen weapon mzID
 * and CardID as DQ variables for Weapon Link resolution.
 */
$customDQHandlers["DeclareWeaponLinkTarget"] = function($player, $parts, $lastDecision) {
    if($lastDecision === "PASS" || $lastDecision === "-" || empty($lastDecision)) {
        DecisionQueueController::StoreVariable("weaponLinkTargetMZ", "");
        DecisionQueueController::StoreVariable("weaponLinkTargetCardID", "");
        return;
    }
    $targetObj = GetZoneObject($lastDecision);
    DecisionQueueController::StoreVariable("weaponLinkTargetMZ", $lastDecision);
    DecisionQueueController::StoreVariable("weaponLinkTargetCardID", $targetObj !== null ? $targetObj->CardID : "");
};

/**
 * Custom DQ handler: RazorgaleCallingDamage — deal 1 damage to target champion.
 * YES = your champion, NO = opponent's champion.
 */
$customDQHandlers["RazorgaleCallingDamage"] = function($player, $parts, $lastDecision) {
    if($lastDecision === "YES") {
        DealChampionDamage($player, 1);
    } else {
        $opponent = ($player == 1) ? 2 : 1;
        DealChampionDamage($opponent, 1);
    }
};

// Fervent Lancer: may banish the exia card as it resolves
$customDQHandlers["FerventLancerBanish"] = function($player, $parts, $lastDecision) {
    if($lastDecision !== "YES") return;
    $lancerIdx = DecisionQueueController::GetVariable("FerventLancerIdx");
    // The card just resolved — it should be on the effect stack or in graveyard.
    // Find the card that just resolved (top of effect stack or last resolved)
    $mzID = DecisionQueueController::GetVariable("mzID");
    if($mzID === null || $mzID === "" || $mzID === "-") return;
    $obj = GetZoneObject($mzID);
    if($obj === null) return;
    // Banish the resolved card
    MZMove($player, $mzID, "myBanish");
    DecisionQueueController::CleanupRemovedCards();
    // Mark this Fervent Lancer as having banished a card (for +2 POWER)
    $lancerMZ = "myField-" . $lancerIdx;
    $lancerObj = &GetZoneObject($lancerMZ);
    if($lancerObj !== null && $lancerObj->CardID === "aws20fsihd") {
        if(!is_array($lancerObj->Counters)) $lancerObj->Counters = [];
        $lancerObj->Counters['banished_card'] = true;
    }
};

// Molten Arrow GY ability: banish 3 fire GY cards sequentially, then load into bow
$customDQHandlers["MoltenArrowGYBanish1"] = function($player, $parts, $lastDecision) {
    if($lastDecision === "-" || $lastDecision === "") return;
    MZMove($player, $lastDecision, "myBanish");
    DecisionQueueController::CleanupRemovedCards();
    $fireGY = [];
    $gy = GetZone("myGraveyard");
    for($gi = 0; $gi < count($gy); ++$gi) {
        if(!$gy[$gi]->removed && CardElement($gy[$gi]->CardID) === "FIRE"
            && $gy[$gi]->CardID !== "mvfcd0ukk6") {
            $fireGY[] = "myGraveyard-" . $gi;
        }
    }
    if(count($fireGY) < 2) return;
    DecisionQueueController::AddDecision($player, "MZCHOOSE", implode("&", $fireGY), 1, tooltip:"Banish_fire_card_2_of_3");
    DecisionQueueController::AddDecision($player, "CUSTOM", "MoltenArrowGYBanish2", 1);
};

$customDQHandlers["MoltenArrowGYBanish2"] = function($player, $parts, $lastDecision) {
    if($lastDecision === "-" || $lastDecision === "") return;
    MZMove($player, $lastDecision, "myBanish");
    DecisionQueueController::CleanupRemovedCards();
    $fireGY = [];
    $gy = GetZone("myGraveyard");
    for($gi = 0; $gi < count($gy); ++$gi) {
        if(!$gy[$gi]->removed && CardElement($gy[$gi]->CardID) === "FIRE"
            && $gy[$gi]->CardID !== "mvfcd0ukk6") {
            $fireGY[] = "myGraveyard-" . $gi;
        }
    }
    if(count($fireGY) < 1) return;
    DecisionQueueController::AddDecision($player, "MZCHOOSE", implode("&", $fireGY), 1, tooltip:"Banish_fire_card_3_of_3");
    DecisionQueueController::AddDecision($player, "CUSTOM", "MoltenArrowGYBanish3", 1);
};

$customDQHandlers["MoltenArrowGYBanish3"] = function($player, $parts, $lastDecision) {
    if($lastDecision === "-" || $lastDecision === "") return;
    MZMove($player, $lastDecision, "myBanish");
    DecisionQueueController::CleanupRemovedCards();
    // Now load Molten Arrow from GY into an unloaded Bow
    $arrowMZ = DecisionQueueController::GetVariable("MoltenArrowGYMZ");
    $bows = GetUnloadedBows($player);
    if(empty($bows)) return;
    if(count($bows) == 1) {
        LoadArrowIntoBow($player, $arrowMZ, $bows[0]);
    } else {
        DecisionQueueController::AddDecision($player, "MZCHOOSE", implode("&", $bows), 1, tooltip:"Choose_Bow_to_load");
        DecisionQueueController::AddDecision($player, "CUSTOM", "LoadArrow|" . $arrowMZ, 1);
    }
};


$customDQHandlers["FightForCrown_SacActivator"] = function($player, $parts, $lastDecision) {
    DoSacrificeFighter($player, $lastDecision);
    DecisionQueueController::CleanupRemovedCards();
    FightForCrown_QueueOpponent($player);
};
$customDQHandlers["FightForCrown_SacOpponent"] = function($player, $parts, $lastDecision) {
    // $player is the opponent (their queue ran this handler)
    DoSacrificeFighter($player, $lastDecision);
    DecisionQueueController::CleanupRemovedCards();
};

// --- Overlapping Visages (PYAnl70edq): each player sacrifices an ally ---
// If at least one non-Distortion ally AND one Distortion ally were sacrificed, summon Lost Being (duzpl7mqXl)
function OverlappingVisages_Start($player) {
    DecisionQueueController::StoreVariable("OV_HasDistortion", "0");
    DecisionQueueController::StoreVariable("OV_HasNonDistortion", "0");
    $myAllies = ZoneSearch("myField", ["ALLY"]);
    if(!empty($myAllies)) {
        DecisionQueueController::AddDecision($player, "MZCHOOSE", implode("&", $myAllies), 1, tooltip:"Sacrifice_an_ally");
        DecisionQueueController::AddDecision($player, "CUSTOM", "OV_SacActivator", 1);
    } else {
        OverlappingVisages_QueueOpponent($player);
    }
}
function OverlappingVisages_QueueOpponent($activatingPlayer) {
    $opponent = ($activatingPlayer == 1) ? 2 : 1;
    $opField = GetField($opponent);
    $opAllies = [];
    for($i = 0; $i < count($opField); ++$i) {
        if(!$opField[$i]->removed && PropertyContains(EffectiveCardType($opField[$i]), "ALLY")) {
            $opAllies[] = "myField-" . $i;
        }
    }
    if(!empty($opAllies)) {
        DecisionQueueController::AddDecision($opponent, "MZCHOOSE", implode("&", $opAllies), 1, tooltip:"Sacrifice_an_ally");
        DecisionQueueController::AddDecision($opponent, "CUSTOM", "OV_SacOpponent|$activatingPlayer", 1);
    } else {
        OverlappingVisages_Finish($activatingPlayer);
    }
}
function OverlappingVisages_Finish($activatingPlayer) {
    $hasD = DecisionQueueController::GetVariable("OV_HasDistortion") === "1";
    $hasND = DecisionQueueController::GetVariable("OV_HasNonDistortion") === "1";
    if($hasD && $hasND) {
        SummonMemorite($activatingPlayer, "duzpl7mqXl");
    }
}

$customDQHandlers["OV_SacActivator"] = function($player, $parts, $lastDecision) {
    if($lastDecision === "-" || $lastDecision === "") {
        OverlappingVisages_QueueOpponent($player);
        return;
    }
    $obj = GetZoneObject($lastDecision);
    if($obj !== null) {
        if(PropertyContains(EffectiveCardSubtypes($obj), "DISTORTION")) {
            DecisionQueueController::StoreVariable("OV_HasDistortion", "1");
        } else {
            DecisionQueueController::StoreVariable("OV_HasNonDistortion", "1");
        }
    }
    DoSacrificeFighter($player, $lastDecision);
    DecisionQueueController::CleanupRemovedCards();
    OverlappingVisages_QueueOpponent($player);
};

$customDQHandlers["OV_SacOpponent"] = function($player, $parts, $lastDecision) {
    $activatingPlayer = intval($parts[0]);
    if($lastDecision === "-" || $lastDecision === "") {
        OverlappingVisages_Finish($activatingPlayer);
        return;
    }
    $obj = GetZoneObject($lastDecision);
    if($obj !== null) {
        if(PropertyContains(EffectiveCardSubtypes($obj), "DISTORTION")) {
            DecisionQueueController::StoreVariable("OV_HasDistortion", "1");
        } else {
            DecisionQueueController::StoreVariable("OV_HasNonDistortion", "1");
        }
    }
    DoSacrificeFighter($player, $lastDecision);
    DecisionQueueController::CleanupRemovedCards();
    OverlappingVisages_Finish($activatingPlayer);
};

$customDQHandlers["Ready"] = function($player, $param, $lastResult) {
    if ($lastResult && $lastResult !== "-") {
        $target = &GetZoneObject($lastResult);
        if ($target !== null) {
            $target->Status = 2; // Ready the unit
        }
    }
};

$customDQHandlers["Bounce"] = function($player, $param, $lastResult) {
    if ($lastResult && $lastResult !== "-") {
        $obj = GetZoneObject($lastResult);
        OnLeaveField($player, $lastResult);
        $dest = EphemeralRedirectDest($obj, "myHand", $player);
        MZMove($player, $lastResult, $dest);
    }
};

$customDQHandlers["SpellshieldWindBuff"] = function($player, $param, $lastResult) {
    if($lastResult && $lastResult !== "-") {
        AddCounters($player, $lastResult, "buff", 1);
    }
};

// False Step (47o7eanl1g): after paying (2), all allies become distant
$customDQHandlers["FalseStepDistantAllies"] = function($player, $param, $lastResult) {
    $allies = ZoneSearch("myField", ["ALLY"]);
    foreach($allies as $allyMZ) {
        BecomeDistant($player, $allyMZ);
    }
};

// Veiled Dash (08kuz07nk4): choose optional second target, then reveal wind+prevent
$customDQHandlers["VeiledDashContinue"] = function($player, $param, $lastResult) {
    $target1 = DecisionQueueController::GetVariable("target1");
    if($target1 === null || $target1 === "-") return;
    BecomeDistant($player, $target1);
    // Check for second target (optional)
    $units = array_merge(
        ZoneSearch("myField", ["ALLY", "CHAMPION"]),
        ZoneSearch("theirField", ["ALLY", "CHAMPION"])
    );
    $units = FilterSpellshroudTargets($units);
    $remaining = array_values(array_diff($units, [$target1]));
    if(!empty($remaining)) {
        $remainStr = implode("&", $remaining);
        DecisionQueueController::AddDecision($player, "MZMAYCHOOSE", "$remainStr", 1);
        DecisionQueueController::AddDecision($player, "CUSTOM", "VeiledDashFinish", 1);
    } else {
        // No second target possible — just do wind reveal for target1
        VeiledDashApplyPrevention($player, $target1, null);
    }
};

$customDQHandlers["VeiledDashFinish"] = function($player, $param, $lastResult) {
    $target1 = DecisionQueueController::GetVariable("target1");
    $target2 = ($lastResult !== "-" && $lastResult !== null) ? $lastResult : null;
    if($target2 !== null) {
        BecomeDistant($player, $target2);
    }
    VeiledDashApplyPrevention($player, $target1, $target2);
};


function VeiledDashApplyPrevention($player, $target1, $target2) {
    $windCards = ZoneSearch("myMemory", cardElements: ["WIND"]);
    $windCount = count($windCards);
    if($windCount <= 0) return;
    $revealIDs = [];
    foreach($windCards as $wMZ) {
        $wObj = GetZoneObject($wMZ);
        if($wObj !== null) $revealIDs[] = $wObj->CardID;
    }
    if(!empty($revealIDs)) {
        SetFlashMessage('REVEAL:' . implode('|', $revealIDs));
    }
    AddTurnEffect($target1, "PREVENT_ALL_" . $windCount);
    if($target2 !== null) {
        AddTurnEffect($target2, "PREVENT_ALL_" . $windCount);
    }
}

// --- Foster: Awakened Frostguard (mnu1xhs5jw) On Foster helper ---
// "On Foster: You may banish up to two cards with floating memory from your graveyard.
//  For each card banished this way, put a buff counter on CARDNAME and draw a card."
function FrostguardOnFoster($player, $mzID) {
    $floatingCards = [];
    $graveyard = ZoneSearch("myGraveyard");
    foreach($graveyard as $gMZ) {
        $gObj = GetZoneObject($gMZ);
        if($gObj !== null && HasFloatingMemory($gObj)) $floatingCards[] = $gMZ;
    }
    if(empty($floatingCards)) return;
    $cardStr = implode("&", $floatingCards);
    DecisionQueueController::AddDecision($player, "MZMAYCHOOSE", $cardStr, 1, tooltip:"Banish_a_floating_memory_card_from_graveyard?");
    DecisionQueueController::AddDecision($player, "CUSTOM", "FrostguardBanish|$mzID|1", 1);
}

$customDQHandlers["FrostguardBanish"] = function($player, $parts, $lastDecision) {
    $mzID = $parts[0];
    $step = intval($parts[1]);
    if($lastDecision === null || $lastDecision === "-" || $lastDecision === "") {
        return; // Player declined
    }
    // Banish the chosen card
    MZMove($player, $lastDecision, "myBanish");
    NicoOnFloatingMemoryBanished($player);
    AddCounters($player, $mzID, "buff", 1);
    Draw($player, 1);
    if($step >= 2) return; // Already did 2
    // Offer second banish
    $floatingCards = [];
    $graveyard = ZoneSearch("myGraveyard");
    foreach($graveyard as $gMZ) {
        $gObj = GetZoneObject($gMZ);
        if($gObj !== null && HasFloatingMemory($gObj)) $floatingCards[] = $gMZ;
    }
    if(empty($floatingCards)) return;
    $cardStr = implode("&", $floatingCards);
    DecisionQueueController::AddDecision($player, "MZMAYCHOOSE", $cardStr, 1, tooltip:"Banish_another_floating_memory_card?");
    DecisionQueueController::AddDecision($player, "CUSTOM", "FrostguardBanish|$mzID|2", 1);
};

// --- Foster: Organize the Alliance (ch2bbmoqk2) ---
// "Target ally becomes fostered."
$customDQHandlers["OrganizeAllianceFoster"] = function($player, $parts, $lastDecision) {
    if($lastDecision === null || $lastDecision === "-" || $lastDecision === "") return;
    BecomeFostered($player, $lastDecision);
    // Seasoned Shieldmaster (qsm4o98vn1): whenever ally becomes fostered → draw into memory
    $field = &GetField($player);
    foreach($field as $fieldObj) {
        if(!$fieldObj->removed && $fieldObj->CardID === "qsm4o98vn1" && !HasNoAbilities($fieldObj)) {
            DrawIntoMemory($player, 1);
            break;
        }
    }
    // Fire OnFoster triggered ability on the target if it has one
    $targetObj = GetZoneObject($lastDecision);
    if($targetObj !== null && HasFoster($targetObj) && !HasNoAbilities($targetObj)) {
        OnFoster($player, $lastDecision);
    }
};



// Fight for the Crown (1lij42a9sh): each player sacrifices an ally.
// Uses explicit DQ handlers to correctly handle conditional branching and zone perspective.
// "myField-X" mzIDs are used for the opponent's queue so that when they process with their
// own $playerID, GetZoneObject("myField-X") correctly refers to their own field.
function FightForCrown_Start($player) {
    $myAllies = ZoneSearch("myField", ["ALLY"]);
    if(!empty($myAllies)) {
        DecisionQueueController::AddDecision($player, "MZCHOOSE", implode("&", $myAllies), 1);
        DecisionQueueController::AddDecision($player, "CUSTOM", "FightForCrown_SacActivator", 1);
    } else {
        FightForCrown_QueueOpponent($player);
    }
}
function FightForCrown_QueueOpponent($activatingPlayer) {
    $opponent = ($activatingPlayer == 1) ? 2 : 1;
    $opField = GetField($opponent);
    $opAllies = [];
    for($i = 0; $i < count($opField); ++$i) {
        if(!$opField[$i]->removed && PropertyContains(EffectiveCardType($opField[$i]), "ALLY")) {
            $opAllies[] = "myField-" . $i; // use myField from the opponent's perspective
        }
    }
    if(!empty($opAllies)) {
        DecisionQueueController::AddDecision($opponent, "MZCHOOSE", implode("&", $opAllies), 1);
        DecisionQueueController::AddDecision($opponent, "CUSTOM", "FightForCrown_SacOpponent", 1);
    }
}

$customDQHandlers["CheckEnhancePotency"] = function($player, $parts, $lastDecision) {
    EnhancePotencyCheck($player);
};

/**
 * Azure Protective Trinket (m3pal7cpvn): continue banishing up to N more fire element cards
 * from the chosen graveyard. Queues MZMAYCHOOSE + handler for each remaining pick.
 */
function AzureTrinketContinue($player, $gravRef, $remaining) {
    if($remaining <= 0) return;
    $fireGY = ZoneSearch($gravRef, cardElements: ["FIRE"]);
    if(empty($fireGY)) return;
    DecisionQueueController::AddDecision($player, "MZMAYCHOOSE", implode("&", $fireGY), 1, tooltip:"Banish_fire_card?");
    DecisionQueueController::AddDecision($player, "CUSTOM", "AzureTrinketPick|$gravRef|$remaining", 1);
}

$customDQHandlers["AzureTrinketPick"] = function($player, $parts, $lastDecision) {
    $gravRef = $parts[0];
    $remaining = intval($parts[1]);
    if($lastDecision == "-" || $lastDecision == "") return;
    $destZone = (strpos($gravRef, "my") === 0) ? "myBanish" : "theirBanish";
    MZMove($player, $lastDecision, $destZone);
    AzureTrinketContinue($player, $gravRef, $remaining - 1);
};

// Necklace of Hindsight (21g6ldxwrv): apply rearrange result to the opponent's deck
$customDQHandlers["NecklaceHindsightApply"] = function($player, $parts, $lastDecision) {
    $opponent = ($player == 1) ? 2 : 1;
    $zone = &GetDeck($opponent);
    $n = intval(DecisionQueueController::GetVariable("glimpseCount"));

    $piles = ["Top" => [], "Bottom" => []];
    $pileStrings = explode(";", $lastDecision);
    foreach($pileStrings as $pileStr) {
        $eqPos = strpos($pileStr, "=");
        if($eqPos === false) continue;
        $pileName = substr($pileStr, 0, $eqPos);
        $cardsStr = trim(substr($pileStr, $eqPos + 1));
        $pidList = ($cardsStr !== "") ? explode(",", $cardsStr) : [];
        $piles[$pileName] = $pidList;
    }

    $removedCards = [];
    for($i = 0; $i < $n; ++$i) {
        if(count($zone) > 0) {
            $removedCards[] = array_shift($zone);
        }
    }

    $cardMap = [];
    foreach($removedCards as $cardObj) {
        $cardMap[$cardObj->CardID][] = $cardObj;
    }
    $popCard = function($cardID) use (&$cardMap) {
        if(!isset($cardMap[$cardID]) || count($cardMap[$cardID]) === 0) return null;
        return array_shift($cardMap[$cardID]);
    };

    $topCards = $piles["Top"];
    for($i = count($topCards) - 1; $i >= 0; --$i) {
        $obj = $popCard($topCards[$i]);
        if($obj !== null) array_unshift($zone, $obj);
    }
    $bottomCards = $piles["Bottom"];
    foreach($bottomCards as $cardID) {
        $obj = $popCard($cardID);
        if($obj !== null) array_push($zone, $obj);
    }
};


$customDQHandlers["AstrolabeStarcallGlimpse"] = function($player, $parts, $lastDecision) {
    Glimpse($player, 5);
};

// --- Stargazer's Portent (btjuxztaug): copy the starcalled card's activation ---
$customDQHandlers["StargazersPortentCopy"] = function($player, $parts, $lastDecision) {
    global $cardActivatedAbilities;
    $cardID = $parts[0];
    $abilityKey = $cardID . ":0";
    if(isset($cardActivatedAbilities[$abilityKey])) {
        DecisionQueueController::StoreVariable("wasStarcalled", "YES");
        DecisionQueueController::StoreVariable("mzID", "COPY");
        $cardActivatedAbilities[$abilityKey]($player);
    }
};

$customDQHandlers["WindriderMageBounce"] = function($player, $parts, $lastDecision) {
    if($lastDecision === "YES") {
        $fieldIndex = intval($parts[0]);
        $field = &GetField($player);
        if(isset($field[$fieldIndex]) && !$field[$fieldIndex]->removed && $field[$fieldIndex]->CardID === "ZfCtSldRIy") {
            OnLeaveField($player, "myField-" . $fieldIndex);
            MZMove($player, "myField-" . $fieldIndex, "myHand");
            // Put an enlighten counter on champion
            $pField = &GetField($player);
            for($ci = 0; $ci < count($pField); ++$ci) {
                if(!$pField[$ci]->removed && CardType($pField[$ci]->CardID) === "CHAMPION") {
                    AddCounters($player, "myField-" . $ci, "enlighten", 1);
                    break;
                }
            }
        }
    }
};

$customDQHandlers["FirebloomRecollation"] = function($player, $parts, $lastDecision) {
    // $lastDecision is the chosen champion mzID
    if($lastDecision !== "-" && $lastDecision !== "") {
        $champObj = &GetZoneObject($lastDecision);
        if($champObj !== null && !$champObj->removed) {
            $champObj->Damage += 1;
        }
    }
};

$customDQHandlers["MorganSoulGuideRecollection"] = function($player, $parts, $lastDecision) {
    if($lastDecision === "YES") {
        Glimpse($player, 1);
    } else {
        RecoverChampion($player, 1);
    }
};

$customDQHandlers["RelicOfSunkenPastSacrifice"] = function($player, $parts, $lastDecision) {
    if($lastDecision === "YES") {
        $fieldIdx = intval($parts[0]);
        $field = &GetField($player);
        if(isset($field[$fieldIdx]) && !$field[$fieldIdx]->removed && $field[$fieldIdx]->CardID === "dqqwey9xys") {
            DoSacrificeFighter($player, "myField-" . $fieldIdx);
            Draw($player, 1);
        }
    }
};

// Lumen Borealis (3ejd9yj9rl): reveal chosen memory card when animal ally dies
$customDQHandlers["LumenBorealisReveal"] = function($player, $parts, $lastDecision) {
    if($lastDecision == "-" || $lastDecision == "") return;
    DoRevealCard($player, $lastDecision);
};

$customDQHandlers["ParcenetReveal"] = function($player, $parts, $lastDecision) {
    $deck = &GetDeck($player);
    if(empty($deck)) return;
    $topCard = $deck[0];
    DoRevealCard($player, "myDeck-0");
    if(CardElement($topCard->CardID) === "WIND") {
        // Find Parcenet's mzID so we can exclude it from choices
        $parcenetMZ = null;
        $field = &GetField($player);
        for($i = 0; $i < count($field); ++$i) {
            if(!$field[$i]->removed && $field[$i]->CardID === "xxoo7dl5j4") {
                $parcenetMZ = "myField-" . $i;
                break;
            }
        }
        $allies = ZoneSearch("myField", ["ALLY"]);
        $targets = [];
        foreach($allies as $a) {
            if($a !== $parcenetMZ) $targets[] = $a;
        }
        if(!empty($targets)) {
            $targetStr = implode("&", $targets);
            DecisionQueueController::AddDecision($player, "MZCHOOSE", $targetStr, 1, tooltip:"Choose_ally_to_gain_stealth");
            DecisionQueueController::AddDecision($player, "CUSTOM", "ParcenetGrantStealth", 1);
        }
    }
};

$customDQHandlers["ParcenetGrantStealth"] = function($player, $parts, $lastDecision) {
    if($lastDecision && $lastDecision !== "-") {
        AddTurnEffect($lastDecision, "xxoo7dl5j4_STEALTH");
    }
};

$customDQHandlers["GreenSlimeTransfer"] = function($player, $parts, $lastDecision) {
    if($lastDecision && $lastDecision !== "-") {
        $buffCount = intval(DecisionQueueController::GetVariable("GreenSlimeBuffCount"));
        if($buffCount > 0) {
            AddCounters($player, $lastDecision, "buff", $buffCount);
        }
    }
};

// Orb of Hubris: shuffle cards from hand into deck one at a time
function OrbOfHubrisShuffle($player, $remaining) {
    if($remaining <= 0) return;
    $hand = ZoneSearch("myHand");
    if(empty($hand)) return;
    $choices = implode("&", $hand);
    DecisionQueueController::AddDecision($player, "MZCHOOSE", $choices, 1, tooltip:"Choose_card_to_shuffle_into_deck");
    DecisionQueueController::AddDecision($player, "CUSTOM", "OrbOfHubrisShuffleStep|$remaining", 1);
}

$customDQHandlers["OrbOfHubrisShuffleStep"] = function($player, $parts, $lastDecision) {
    $remaining = intval($parts[0]);
    MZMove($player, $lastDecision, "myDeck");
    if($remaining > 1) {
        OrbOfHubrisShuffle($player, $remaining - 1);
    }
};

// Forgetful Concoction (7kr1haizu8): banish 2 random cards from opponent's memory
function ForgetfulConcoctionExecute($player) {
    $opponent = ($player == 1) ? 2 : 1;
    global $playerID;
    $memZone = ($opponent == $playerID) ? "myMemory" : "theirMemory";
    $banishZone = ($opponent == $playerID) ? "myBanish" : "theirBanish";
    $mem = GetZone($memZone);
    $indices = [];
    for($i = 0; $i < count($mem); ++$i) {
        if(!$mem[$i]->removed) $indices[] = $i;
    }
    shuffle($indices);
    $toBanish = array_slice($indices, 0, 2);
    // Sort descending so removals don't shift indices
    rsort($toBanish);
    foreach($toBanish as $idx) {
        $mzCard = $memZone . "-" . $idx;
        $banishedObj = &GetZoneObject($mzCard);
        if($banishedObj !== null) {
            $banishedObj->Counters['MEM_BANISHED'] = $opponent;
        }
        MZMove($opponent, $mzCard, $banishZone);
    }
    DecisionQueueController::CleanupRemovedCards();
}

// Tabula of Salvage (9cy4wipw4k): iteratively choose cards from GY to put on deck bottom
function TabulaSalvageLoop($player, $remaining) {
    if($remaining <= 0) return;
    $gy = ZoneSearch("myGraveyard");
    if(empty($gy)) return;
    $choices = implode("&", $gy);
    DecisionQueueController::AddDecision($player, "MZMAYCHOOSE", $choices, 1, tooltip:"Choose_card_to_put_on_deck_bottom");
    DecisionQueueController::AddDecision($player, "CUSTOM", "TabulaSalvagePick|$remaining", 1);
}

$customDQHandlers["TabulaSalvagePick"] = function($player, $parts, $lastDecision) {
    $remaining = intval($parts[0]);
    if($lastDecision == "-" || $lastDecision == "") return; // Player passed
    MZMove($player, $lastDecision, "myDeck");
    if($remaining > 1) {
        TabulaSalvageLoop($player, $remaining - 1);
    }
};

// --- Shifting Currents Custom DQ Handlers ---

$customDQHandlers["GemOfSearingFlameDamage"] = function($player, $params, $lastDecision) {
    if($lastDecision === "YES") {
        DealChampionDamage($player, 2);
    } else {
        $opponent = ($player == 1) ? 2 : 1;
        DealChampionDamage($opponent, 2);
    }
};

$customDQHandlers["WujiSacrifice"] = function($player, $params, $lastDecision) {
    if($lastDecision !== "YES") return;
    $mzID = DecisionQueueController::GetVariable("WujiMZ");
    $obj = GetZoneObject($mzID);
    if($obj === null || $obj->removed) return;
    MZMove($player, $mzID, "myGraveyard");
    DecisionQueueController::CleanupRemovedCards();
    // Target player mills 3
    DecisionQueueController::AddDecision($player, "YESNO", "-", 1,
        tooltip:"Mill_yourself?_(No=mill_opponent)");
    DecisionQueueController::AddDecision($player, "CUSTOM", "WujiMill", 1);
};

$customDQHandlers["WujiMill"] = function($player, $params, $lastDecision) {
    $target = ($lastDecision === "YES") ? $player : (($player == 1) ? 2 : 1);
    $deck = &GetDeck($target);
    for($i = 0; $i < 3 && count($deck) > 0; ++$i) {
        MZMove($target, ($target == $player ? "myDeck" : "theirDeck") . "-0", ($target == $player ? "myGraveyard" : "theirGraveyard"));
    }
};

$customDQHandlers["RuinousPillarsDestroy"] = function($player, $params, $lastDecision) {
    if($lastDecision === "-") return;
    AllyDestroyed($player, $lastDecision);
};

$customDQHandlers["ChangeShiftingCurrentsChoice"] = function($player, $params, $lastDecision) {
    if($lastDecision === "-") return;
    ChangeShiftingCurrents($player, $lastDecision);
};

$customDQHandlers["ChangeShiftingCurrentsAdjacentChoice"] = function($player, $params, $lastDecision) {
    if($lastDecision === "-") return;
    $current = GetShiftingCurrents($player);
    if(IsAdjacentDirection($current, $lastDecision)) {
        ChangeShiftingCurrents($player, $lastDecision);
    }
};

$customDQHandlers["UsurpTheWindsDirectionDraw"] = function($player, $params, $lastDecision) {
    // Usurp the Winds Kongming Bonus: if direction changed from West to South, draw a card
    $oldDir = DecisionQueueController::GetVariable("UsurpOldDir");
    $newDir = GetShiftingCurrents($player);
    if($oldDir === "WEST" && $newDir === "SOUTH") {
        Draw($player, 1);
    }
};

$customDQHandlers["SageProtectionApply"] = function($player, $params, $lastDecision) {
    if($lastDecision === "-") return;
    AddTurnEffect($lastDecision, "fqsa372jii");
    $count = intval($params[0]) + 1;
    if($count < 3) {
        SageProtectionChoose($player, $count);
    }
};

$customDQHandlers["MaChaoBanish"] = function($player, $params, $lastDecision) {
    if($lastDecision === "-") return;
    MZMove($player, $lastDecision, "theirBanish");
};

$customDQHandlers["SolarProvidenceDiscard"] = function($player, $params, $lastDecision) {
    if($lastDecision === "-") return;
    MZMove($player, $lastDecision, "myGraveyard");
};

$customDQHandlers["SolarProvidenceSCChoice"] = function($player, $params, $lastDecision) {
    QueueShiftingCurrentsChoice($player, "any", true);
};

/**
 * Strategem of Myriad Ice (id0ybub247): recursive banish loop.
 * Each iteration: find floating memory cards in graveyard, MayChoose one,
 * banish it and deal 3 damage to target unit opponent controls, then repeat.
 */
function StrategemBanishLoop($player, $sourceMZ) {
    $floatingGrave = ZoneSearch("myGraveyard", floatingMemoryOnly: true);
    if(empty($floatingGrave)) return;
    $targetStr = implode("&", $floatingGrave);
    DecisionQueueController::AddDecision($player, "MZMAYCHOOSE", $targetStr, 1,
        "Banish_a_floating_memory_card_from_graveyard");
    DecisionQueueController::AddDecision($player, "CUSTOM", "StrategemBanish|" . $sourceMZ, 1);
}

$customDQHandlers["StrategemBanish"] = function($player, $params, $lastDecision) {
    if($lastDecision === "-" || $lastDecision === "PASS") return;
    $sourceMZ = $params[0] ?? "";
    // Banish chosen card
    MZMove($player, $lastDecision, "myBanish");
    NicoOnFloatingMemoryBanished($player);
    // Deal 3 damage to target unit opponent controls
    $enemies = array_merge(
        ZoneSearch("theirField", ["ALLY"]),
        ZoneSearch("theirField", ["CHAMPION"])
    );
    $enemies = FilterSpellshroudTargets($enemies);
    if(!empty($enemies)) {
        $choices = implode("&", $enemies);
        DecisionQueueController::AddDecision($player, "MZCHOOSE", $choices, 1,
            "Deal_3_damage_to_target_unit");
        DecisionQueueController::AddDecision($player, "CUSTOM", "StrategemDealDamage|" . $sourceMZ, 1);
    }
    // Continue loop
    StrategemBanishLoop($player, $sourceMZ);
};

$customDQHandlers["StrategemDealDamage"] = function($player, $params, $lastDecision) {
    if($lastDecision === "-") return;
    $sourceMZ = $params[0] ?? "";
    DealDamage($player, $sourceMZ, $lastDecision, 3);
};

// Channel Manifold Desire (kywpjf1b4k): process banished preserved card
$customDQHandlers["ChannelManifoldBanish"] = function($player, $params, $lastDecision) {
    if($lastDecision === "-") return;
    // Get the reserve cost of the chosen card before banishing
    $obj = GetZoneObject($lastDecision);
    if($obj === null) return;
    $reserveCost = CardCost_reserve($obj->CardID);
    // Banish the preserved card from material deck
    MZMove($player, $lastDecision, "myBanish");
    // Empower X+2 (X = reserve cost of banished card)
    $empowerAmount = $reserveCost + 2;
    // Encode the empower amount in the effect ID
    $champions = ZoneSearch("myField", ["CHAMPION"]);
    if(!empty($champions)) {
        AddTurnEffect($champions[0], "kywpjf1b4k-" . $empowerAmount);
    }
    // If SC faces North, may put a preserved card from material deck into hand
    if(GetShiftingCurrents($player) === "NORTH") {
        global $Preserve_Cards;
        $material = GetZone("myMaterial");
        $preserved = [];
        for($i = 0; $i < count($material); $i++) {
            if(isset($Preserve_Cards[$material[$i]->CardID])) {
                $preserved[] = "myMaterial-" . $i;
            }
        }
        if(!empty($preserved)) {
            DecisionQueueController::AddDecision($player, "MZMAYCHOOSE", implode("&", $preserved), 1,
                "Return_a_preserved_card_to_hand?");
            DecisionQueueController::AddDecision($player, "CUSTOM", "ChannelManifoldReturn", 1);
        }
    }
};

$customDQHandlers["ChannelManifoldReturn"] = function($player, $params, $lastDecision) {
    if($lastDecision === "-" || $lastDecision === "PASS") return;
    MZMove($player, $lastDecision, "myHand");
};

// Bagua of Cardinal Fate (jgyx38zpl0): North — put a buff counter on chosen ally
$customDQHandlers["BaguaNorthBuff"] = function($player, $params, $lastDecision) {
    if($lastDecision === "-") return;
    AddCounters($player, $lastDecision, "buff", 1);
};

// Bagua of Cardinal Fate (jgyx38zpl0): South — return chosen ally to hand
$customDQHandlers["BaguaSouthReturn"] = function($player, $params, $lastDecision) {
    if($lastDecision === "-") return;
    MZMove($player, $lastDecision, "myHand");
};

// Solar Pinnacle (zeig1e49wb): deal 2 damage to chosen unit
$customDQHandlers["SolarPinnacleDamage"] = function($player, $params, $lastDecision) {
    if($lastDecision === "-") return;
    $sourceMZ = $params[0] ?? "";
    DealDamage($player, $sourceMZ, $lastDecision, 2);
};


/**
 * Right of Realm (ptrz1bqry4): "Whenever you activate a domain card, you may sacrifice
 * CARDNAME. If you do, that domain enters the field without any of its upkeep abilities."
 *
 * This DQ handler is called after the YesNo prompt. If YES, sacrifice Right of Realm
 * and tag the domain (on the EffectStack) with NO_UPKEEP so materialize-sacrifice
 * and recollection upkeep checks skip it.
 */
$customDQHandlers["RightOfRealmChoice"] = function($player, $parts, $lastDecision) {
    $rightOfRealmIdx = intval($parts[0]);
    if($lastDecision !== "YES") return;

    // Sacrifice Right of Realm
    $field = &GetField($player);
    if(isset($field[$rightOfRealmIdx]) && !$field[$rightOfRealmIdx]->removed
        && $field[$rightOfRealmIdx]->CardID === "ptrz1bqry4") {
        DoSacrificeFighter($player, "myField-" . $rightOfRealmIdx);
        DecisionQueueController::CleanupRemovedCards();
    }

    // Tag the domain on the EffectStack with NO_UPKEEP
    // The domain is currently the top of the EffectStack (about to resolve)
    $es = &GetEffectStack();
    if(count($es) > 0) {
        $topIdx = count($es) - 1;
        $es[$topIdx]->TurnEffects[] = "NO_UPKEEP";
    }
};

/**
 * Count the number of water element cards in a player's graveyard.
 * Centralized for future modifier support (e.g. "your deluge counts as having one more").
 */
function DelugeAmount($player) {
    global $playerID;
    $gravZone = $player == $playerID ? "myGraveyard" : "theirGraveyard";
    return count(ZoneSearch($gravZone, cardElements: ["WATER"]));
}

function SleeyRetreatCheckDefending($player, $target) {
    if(!IsUnitDefending($target)) return;
    $opponent = ($player == 1) ? 2 : 1;
    $oppHand = ZoneSearch("theirHand");
    if(count($oppHand) >= 2) {
        DecisionQueueController::AddDecision($opponent, "YESNO", "-", 1, tooltip:"Pay_2_to_prevent_combat_from_ending?");
        DecisionQueueController::AddDecision($opponent, "CUSTOM", "SleeyRetreatPayment", 1);
    } else {
        EndCombat($player);
    }
}

/**
 * Avalon, Cursed Isle (41WnFOT5YS): "Whenever you activate a water element card,
 * target player puts the top two cards of their deck into their graveyard."
 * YES = mill yourself, NO = mill opponent.
 */
$customDQHandlers["AvalonMill"] = function($player, $parts, $lastDecision) {
    if($lastDecision === "YES") {
        MillCards($player, "myDeck", "myGraveyard", 2);
    } else {
        MillCards($player, "theirDeck", "theirGraveyard", 2);
    }
};

// ============================================================================
// Paired Minds, Kindred Souls (7qjnqww067) helpers
// ============================================================================

/**
 * Queue the Horse ally choice from TempZone, or finish directly if none found.
 */
function PairedMindsChoose($player) {
    $horseAllies = ZoneSearch("myTempZone", ["ALLY"], cardSubtypes: ["HORSE"]);
    if(!empty($horseAllies)) {
        $horseStr = implode("&", $horseAllies);
        DecisionQueueController::AddDecision($player, "MZCHOOSE", $horseStr, 1, "Reveal_a_Horse_ally");
        DecisionQueueController::AddDecision($player, "CUSTOM", "PairedMindsResult", 1);
    } else {
        PairedMindsFinish($player);
    }
}

function PairedMindsFinish($player) {
    // Put remaining TempZone cards on bottom of deck
    $tempRemaining = ZoneSearch("myTempZone");
    $n = count($tempRemaining);
    for($i = 0; $i < $n; ++$i) {
        MZMove($player, "myTempZone-0", "myDeck");
    }
    // [Class Bonus] if unique ally, next Horse ally costs 2 less
    if(IsClassBonusActive($player, ["WARRIOR"])) {
        $myField = GetZone("myField");
        foreach($myField as $fObj) {
            if(!$fObj->removed && PropertyContains(EffectiveCardType($fObj), "ALLY") && PropertyContains(EffectiveCardType($fObj), "UNIQUE")) {
                AddGlobalEffects($player, "7qjnqww067");
                break;
            }
        }
    }
}

$customDQHandlers["PairedMindsResult"] = function($player, $parts, $lastDecision) {
    if($lastDecision !== "-" && $lastDecision !== "") {
        DoRevealCard($player, $lastDecision);
        MZMove($player, $lastDecision, "myHand");
    }
    PairedMindsFinish($player);
};


// ============================================================================
// Erupting Rhapsody (dBAdWMoPEz) — banish fire GY cards, +level, harmonize split damage
// ============================================================================

/**
 * Begin the loop of optionally banishing fire cards from graveyard.
 * @param int $player        The acting player
 * @param int $banishedCount Number of cards banished so far
 */
function EruptingRhapsodyContinue($player, $banishedCount) {
    $fireGY = ZoneSearch("myGraveyard", cardElements: ["FIRE"]);
    if(empty($fireGY)) {
        EruptingRhapsodyFinalize($player, $banishedCount);
        return;
    }
    DecisionQueueController::AddDecision($player, "MZMAYCHOOSE", implode("&", $fireGY), 1, tooltip:"Banish_a_fire_card_from_graveyard?");
    DecisionQueueController::AddDecision($player, "CUSTOM", "EruptingRhapsodyPick|$banishedCount", 1);
}

$customDQHandlers["EruptingRhapsodyPick"] = function($player, $parts, $lastDecision) {
    $banishedCount = intval($parts[0]);
    if($lastDecision == "-" || $lastDecision == "") {
        EruptingRhapsodyFinalize($player, $banishedCount);
        return;
    }
    MZMove($player, $lastDecision, "myBanish");
    EruptingRhapsodyContinue($player, $banishedCount + 1);
};

/**
 * After banishing is done: apply +1 level per banished card, then harmonize check.
 * @param int $player        The acting player
 * @param int $banishedCount Number of fire cards banished
 */
function EruptingRhapsodyFinalize($player, $banishedCount) {
    if($banishedCount > 0) {
        // Champion gets +1 level per banished card until end of turn
        $champions = ZoneSearch("myField", ["CHAMPION"]);
        if(!empty($champions)) {
            AddTurnEffect($champions[0], "dBAdWMoPEz-" . $banishedCount);
        }
    }
    // Harmonize: if you've activated a Melody card this turn
    if(IsHarmonizeActive($player)) {
        // Get champion's current level (including the boost just applied)
        $champMZArr = ZoneSearch("myField", ["CHAMPION"]);
        $level = 0;
        if(!empty($champMZArr)) {
            $champObj = GetZoneObject($champMZArr[0]);
            $level = ObjectCurrentLevel($champObj);
        }
        if($level > 0) {
            $allUnits = array_merge(
                ZoneSearch("myField", ["ALLY", "CHAMPION"]),
                ZoneSearch("theirField", ["ALLY", "CHAMPION"])
            );
            $allUnits = FilterSpellshroudTargets($allUnits);
            if(!empty($allUnits)) {
                $mzID = DecisionQueueController::GetVariable("mzID");
                DecisionQueueController::StoreVariable("eruptingRhapsodySource", $mzID);
                $targetStr = implode("&", $allUnits);
                DecisionQueueController::AddDecision($player, "MZSPLITASSIGN", $level . "|" . $targetStr, 1, tooltip:"Split_LV_damage_among_units");
                DecisionQueueController::AddDecision($player, "CUSTOM", "EruptingRhapsodyDamage", 1);
            }
        }
    }
}

$customDQHandlers["EruptingRhapsodyDamage"] = function($player, $parts, $lastDecision) {
    $source = DecisionQueueController::GetVariable("eruptingRhapsodySource");
    ProcessSplitDamage($player, $source, $lastDecision);
};

// ============================================================================
// Lightweaver's Assault (zxB4tzy9iy) — [CB][EB] Reveal trigger: deal 2 to chosen unit
// ============================================================================

$customDQHandlers["LightweaverRevealDmg"] = function($player, $parts, $lastDecision) {
    if($lastDecision == "-" || $lastDecision == "") return;
    $revealedMZ = DecisionQueueController::GetVariable("revealedMZ");
    DealDamage($player, $revealedMZ, $lastDecision, 2);
};

// ============================================================================
// Advent of the Stormcaller (ZSSegCjquB) — reveal top LV, banish arcane, deal 2 each, rearrange rest
// ============================================================================

/**
 * Begin the loop of optionally banishing arcane cards from temp zone (revealed cards).
 * @param int $player        The acting player
 * @param int $banishedCount Number of arcane cards banished so far
 */
function AdventStormcallerBanishLoop($player, $banishedCount) {
    $arcaneTmp = ZoneSearch("myTempZone", cardElements: ["ARCANE"]);
    if(empty($arcaneTmp)) {
        AdventStormcallerDamagePhase($player, $banishedCount);
        return;
    }
    DecisionQueueController::AddDecision($player, "MZMAYCHOOSE", implode("&", $arcaneTmp), 1, tooltip:"Banish_an_arcane_card?");
    DecisionQueueController::AddDecision($player, "CUSTOM", "AdventBanishPick|$banishedCount", 1);
}

$customDQHandlers["AdventBanishPick"] = function($player, $parts, $lastDecision) {
    $banishedCount = intval($parts[0]);
    if($lastDecision == "-" || $lastDecision == "") {
        AdventStormcallerDamagePhase($player, $banishedCount);
        return;
    }
    MZMove($player, $lastDecision, "myBanish");
    AdventStormcallerBanishLoop($player, $banishedCount + 1);
};

/**
 * After banishing: deal 2 damage per banished card (each to a separate chosen unit).
 * @param int $player        The acting player
 * @param int $banishedCount Number of arcane cards banished
 */
function AdventStormcallerDamagePhase($player, $banishedCount) {
    if($banishedCount > 0) {
        DecisionQueueController::StoreVariable("adventDmgRemaining", strval($banishedCount));
        AdventStormcallerDamageStep($player, $banishedCount);
    } else {
        AdventStormcallerRearrange($player);
    }
}

/**
 * Recursive step: choose a unit and deal 2 damage, then continue or rearrange.
 * @param int $player    The acting player
 * @param int $remaining Number of damage choices remaining
 */
function AdventStormcallerDamageStep($player, $remaining) {
    if($remaining <= 0) {
        AdventStormcallerRearrange($player);
        return;
    }
    $allUnits = array_merge(
        ZoneSearch("myField", ["ALLY", "CHAMPION"]),
        ZoneSearch("theirField", ["ALLY", "CHAMPION"])
    );
    $allUnits = FilterSpellshroudTargets($allUnits);
    if(empty($allUnits)) {
        AdventStormcallerRearrange($player);
        return;
    }
    DecisionQueueController::AddDecision($player, "MZCHOOSE", implode("&", $allUnits), 1, tooltip:"Choose_unit_to_deal_2_damage");
    DecisionQueueController::AddDecision($player, "CUSTOM", "AdventDealDmg|$remaining", 1);
}

$customDQHandlers["AdventDealDmg"] = function($player, $parts, $lastDecision) {
    $remaining = intval($parts[0]);
    if($lastDecision != "-" && $lastDecision != "") {
        $mzID = DecisionQueueController::GetVariable("mzID");
        DealDamage($player, $mzID, $lastDecision, 2);
    }
    AdventStormcallerDamageStep($player, $remaining - 1);
};

/**
 * After damage phase: move remaining temp zone cards to deck top, then Glimpse to rearrange.
 */
function AdventStormcallerRearrange($player) {
    $tempCards = ZoneSearch("myTempZone");
    if(empty($tempCards)) return;
    $n = count($tempCards);
    // Move temp zone cards to top of deck
    $deck = &GetDeck($player);
    $tempZone = &GetTempZone($player);
    foreach($tempZone as $obj) {
        if(!$obj->removed) {
            $newDeckObj = new Deck($obj->CardID, 'Deck', $player);
            array_unshift($deck, $newDeckObj);
            $obj->Remove();
        }
    }
    // Reindex deck
    for($i = 0; $i < count($deck); ++$i) {
        $deck[$i]->mzIndex = $i;
    }
    // Use Glimpse to let the player arrange top N
    Glimpse($player, $n);
}

// --- Smash with Obelisk (2kkvoqk1l7): sacrifice domain and store its reserve cost ---
$customDQHandlers["SmashWithObeliskSacrifice"] = function($player, $parts, $lastDecision) {
    if($lastDecision == "-" || $lastDecision == "") {
        DecisionQueueController::StoreVariable("smashObeliskBonus", "0");
        return;
    }
    $obj = GetZoneObject($lastDecision);
    $cost = ($obj !== null) ? CardCost_reserve($obj->CardID) : 0;
    DoSacrificeFighter($player, $lastDecision);
    DecisionQueueController::StoreVariable("smashObeliskBonus", strval($cost));
};

// Shield Fragmentation (CHU96qWwaS): sacrifice a Shield item as additional cost
$customDQHandlers["ShieldFragmentSacrifice"] = function($player, $parts, $lastDecision) {
    if($lastDecision === "-" || $lastDecision === "") return;
    OnLeaveField($player, $lastDecision);
    MZMove($player, $lastDecision, "myGraveyard");
    DecisionQueueController::CleanupRemovedCards();
};

// Prismatic Edge (FxYwR2azTt): deal 3 damage to chosen unit (fire element effect)
$customDQHandlers["PrismaticEdgeFire"] = function($player, $parts, $lastDecision) {
    if($lastDecision === "-" || $lastDecision === "") return;
    $sourceMZ = $parts[0];
    DealDamage($player, $sourceMZ, $lastDecision, 3);
};

// Harness Mana (G2XFRE8rFX): iteratively put cards from hand into memory
$customDQHandlers["HarnessManaLoop"] = function($player, $parts, $lastDecision) {
    if($lastDecision === "-" || $lastDecision === "" || $lastDecision === "PASS") return;
    MZMove($player, $lastDecision, "myMemory");
    $hand = ZoneSearch("myHand");
    if(!empty($hand)) {
        $handStr = implode("&", $hand);
        DecisionQueueController::AddDecision($player, "MZMAYCHOOSE", $handStr, 1, tooltip:"Put_a_card_into_memory");
        DecisionQueueController::AddDecision($player, "CUSTOM", "HarnessManaLoop", 1);
    }
};

$customDQHandlers["FiretunedAutomatonDiscard"] = function($player, $parts, $lastDecision) {
    if($lastDecision == "-" || $lastDecision == "" || $lastDecision == "PASS") return;
    DoDiscardCard($player, $lastDecision);
};

$customDQHandlers["FrigidBashPayment"] = function($player, $parts, $lastDecision) {
    $targetMZ = $parts[0];
    if($lastDecision === "YES") {
        // Player chose to pay (2) — queue 2 reserve card payments
        ReserveCard($player);
        ReserveCard($player);
    } else {
        // Player declined — target doesn't wake up during next wake up phase
        AddTurnEffect($targetMZ, "SKIP_WAKEUP");
    }
};

$customDQHandlers["SleeyRetreatPayment"] = function($player, $parts, $lastDecision) {
    if($lastDecision === "YES") {
        ReserveCard($player);
        ReserveCard($player);
    } else {
        EndCombat($player);
    }
};

$customDQHandlers["SanctumOfEsotericTruth1"] = function($player, $parts, $lastDecision) {
    if($lastDecision === "-" || $lastDecision === "" || $lastDecision === "PASS") return;
    // Put first card on bottom of deck
    MZMove($player, $lastDecision, "myDeck");
    // Choose second card
    $handAndMemory = array_merge(ZoneSearch("myHand"), ZoneSearch("myMemory"));
    if(!empty($handAndMemory)) {
        $choices = implode("&", $handAndMemory);
        DecisionQueueController::AddDecision($player, "MZCHOOSE", $choices, 1,
            tooltip:"Sanctum:_Put_card_on_bottom_of_deck_(2/2)");
        DecisionQueueController::AddDecision($player, "CUSTOM", "SanctumOfEsotericTruth2", 1);
    }
};

$customDQHandlers["SanctumOfEsotericTruth2"] = function($player, $parts, $lastDecision) {
    if($lastDecision === "-" || $lastDecision === "" || $lastDecision === "PASS") return;
    // Put second card on bottom of deck
    MZMove($player, $lastDecision, "myDeck");
    // Draw two cards
    Draw($player, 2);
};

// --- Fanatical Devotee (1gxrpx8jyp): multi-step banish fire cards from graveyard ---
function FanaticalDevoteeContinue($player, $firstChoice) {
    if($firstChoice == "-" || $firstChoice == "" || $firstChoice == "PASS") return;
    // Banish the first chosen card
    MZMove($player, $firstChoice, "myBanish");
    // Build choices for second fire card (excluding the one we just banished)
    $fireCards = ZoneSearch("myGraveyard", cardElements: ["FIRE"]);
    $filtered = [];
    $skippedSelf = false;
    foreach($fireCards as $fc) {
        $obj = GetZoneObject($fc);
        if(!$skippedSelf && $obj->CardID === "1gxrpx8jyp") {
            $skippedSelf = true;
            continue;
        }
        $filtered[] = $fc;
    }
    if(empty($filtered)) return;
    $choices2 = implode("&", $filtered);
    DecisionQueueController::AddDecision($player, "MZCHOOSE", $choices2, 1, tooltip:"Banish_second_fire_card");
    DecisionQueueController::AddDecision($player, "CUSTOM", "FanaticalDevoteeBanish2", 1);
}

$customDQHandlers["FanaticalDevoteeBanish2"] = function($player, $parts, $lastDecision) {
    if($lastDecision == "-" || $lastDecision == "") return;
    // Banish the second fire card
    MZMove($player, $lastDecision, "myBanish");
    // Now deal 3 damage to target champion
    $champions = array_merge(
        ZoneSearch("myField", ["CHAMPION"]),
        ZoneSearch("theirField", ["CHAMPION"])
    );
    $champions = FilterSpellshroudTargets($champions);
    if(empty($champions)) return;
    $targetStr = implode("&", $champions);
    DecisionQueueController::AddDecision($player, "MZCHOOSE", $targetStr, 1, tooltip:"Choose_champion_to_deal_3_damage");
    DecisionQueueController::AddDecision($player, "CUSTOM", "FanaticalDevoteeDamage", 1);
};

$customDQHandlers["FanaticalDevoteeDamage"] = function($player, $parts, $lastDecision) {
    if($lastDecision == "-" || $lastDecision == "") return;
    // Determine which player's champion was chosen
    $targetPlayer = (strpos($lastDecision, "their") === 0) ? (($player == 1) ? 2 : 1) : $player;
    DealChampionDamage($targetPlayer, 3);
};

$customDQHandlers["HeatwaveGeneratorBuff"] = function($player, $parts, $lastDecision) {
    if($lastDecision === "-" || $lastDecision === "" || $lastDecision === "PASS") return;
    AddTurnEffect($lastDecision, "fzcyfrzrpl");
};

$customDQHandlers["SwordSaintGY_Apply"] = function($player, $parts, $lastDecision) {
    if($lastDecision !== "-" && $lastDecision !== "" && $lastDecision !== "PASS") {
        AddTurnEffect($lastDecision, "lpy7ie4v8n");
    }
};

// Seaside Rangefinder (5qyee9vkp8): GY activation → target unit becomes distant
$customDQHandlers["SeasideRangefinderGY_Apply"] = function($player, $parts, $lastDecision) {
    if($lastDecision !== "-" && $lastDecision !== "" && $lastDecision !== "PASS") {
        BecomeDistant($player, $lastDecision);
    }
};

$customDQHandlers["EternalKingdomUpkeep"] = function($player, $parts, $lastDecision) {
    $fieldIdx = intval($parts[0]);
    $field = &GetField($player);
    if($lastDecision === "YES") {
        // Pay 2 reserve
        $hand = &GetHand($player);
        if(count($hand) >= 2) {
            // Must use block 100 (same as DoActivateCard) so each ReserveCard's
            // MZCHOOSE→Process chain (blocks 1/99) completes before the next fires.
            DecisionQueueController::AddDecision($player, "CUSTOM", "ReserveCard", 100);
            DecisionQueueController::AddDecision($player, "CUSTOM", "ReserveCard", 100);
        } else {
            // Can't pay — sacrifice
            if(isset($field[$fieldIdx]) && !$field[$fieldIdx]->removed && $field[$fieldIdx]->CardID === "fyoz23yfzk") {
                DoSacrificeFighter($player, "myField-" . $fieldIdx);
                DecisionQueueController::CleanupRemovedCards();
            }
        }
    } else {
        // Sacrifice
        if(isset($field[$fieldIdx]) && !$field[$fieldIdx]->removed && $field[$fieldIdx]->CardID === "fyoz23yfzk") {
            DoSacrificeFighter($player, "myField-" . $fieldIdx);
            DecisionQueueController::CleanupRemovedCards();
        }
    }
};

/**
 * Enhance Hearing: after player chooses or passes, move remaining TempZone to bottom of deck.
 */
function EnhanceHearingFinish($player, $chosen) {
    if($chosen !== "PASS" && $chosen !== "-" && $chosen !== "") {
        Reveal($player, revealedMZ: $chosen);
        MZMove($player, $chosen, "myHand");
    }
    // Move remaining TempZone cards to bottom of deck
    $remaining = ZoneSearch("myTempZone");
    foreach($remaining as $rmz) {
        MZMove($player, $rmz, "myDeck");
    }
}

// --- Assemble the Ancients (moi0a5uhjx): sacrifice domains then summon Automaton Drone tokens ---

function AssembleAncientsSacrifice($player, $count) {
    $domains = ZoneSearch("myField", ["DOMAIN"]);
    if(empty($domains)) {
        AssembleAncientsFinalize($player, $count);
        return;
    }
    $domainStr = implode("&", $domains);
    DecisionQueueController::AddDecision($player, "MZMAYCHOOSE", $domainStr, 1, "Sacrifice_a_domain?");
    DecisionQueueController::AddDecision($player, "CUSTOM", "AssembleAncientsSacChoice|$count", 1);
}

$customDQHandlers["AssembleAncientsSacChoice"] = function($player, $parts, $lastDecision) {
    $count = intval($parts[0]);
    if($lastDecision === "-" || $lastDecision === "" || $lastDecision === "PASS") {
        AssembleAncientsFinalize($player, $count);
        return;
    }
    DoSacrificeFighter($player, $lastDecision);
    DecisionQueueController::CleanupRemovedCards();
    AssembleAncientsSacrifice($player, $count + 1);
};

function AssembleAncientsFinalize($player, $count) {
    if($count <= 0) return;
    for($i = 0; $i < $count; ++$i) {
        MZAddZone($player, "myField", "mu6gvnta6q"); // Automaton Drone token
        $field = &GetField($player);
        $newIdx = count($field) - 1;
        $field[$newIdx]->Status = 1; // Enter rested
        AddCounters($player, "myField-" . $newIdx, "buff", $count);
        AddTurnEffect("myField-" . $newIdx, "VIGOR_EOT");
    }
}

// --- Navigation Compass (sw2ugmnmp5) DQ handler ---
$customDQHandlers["NavCompass_Discard"] = function($player, $parts, $lastDecision) {
    if($lastDecision === "-" || $lastDecision === "") return;
    DoDiscardCard($player, $lastDecision);
};

// --- Portside Pirate (6p3p5iqigc) DQ handler ---
$customDQHandlers["PortsidePirateBanish"] = function($player, $parts, $lastDecision) {
    if($lastDecision === "-" || $lastDecision === "" || $lastDecision === "PASS") return;
    $hadFloating = HasFloatingMemory(GetZoneObject($lastDecision));
    MZMove($player, $lastDecision, "myBanish");
    if($hadFloating) NicoOnFloatingMemoryBanished($player);
};

// --- Smashing Force (88rx6p3p5i) helpers ---
function SmashingForceBanishStart($player) {
    $fireCards = ZoneSearch("myGraveyard", cardElements: ["FIRE"]);
    if(count($fireCards) < 2) return;
    $fireStr = implode("&", $fireCards);
    DecisionQueueController::AddDecision($player, "MZCHOOSE", $fireStr, 1, "Banish_first_fire_card");
    DecisionQueueController::AddDecision($player, "CUSTOM", "SmashingForceBanish1", 1);
}

$customDQHandlers["SmashingForceBanish1"] = function($player, $parts, $lastDecision) {
    if($lastDecision === "-" || $lastDecision === "" || $lastDecision === "PASS") return;
    $hadFloating = HasFloatingMemory(GetZoneObject($lastDecision));
    MZMove($player, $lastDecision, "myBanish");
    if($hadFloating) NicoOnFloatingMemoryBanished($player);
    $fireCards2 = ZoneSearch("myGraveyard", cardElements: ["FIRE"]);
    if(empty($fireCards2)) return;
    $fireStr2 = implode("&", $fireCards2);
    DecisionQueueController::AddDecision($player, "MZCHOOSE", $fireStr2, 1, "Banish_second_fire_card");
    DecisionQueueController::AddDecision($player, "CUSTOM", "SmashingForceBanish2", 1);
};

$customDQHandlers["SmashingForceBanish2"] = function($player, $parts, $lastDecision) {
    if($lastDecision === "-" || $lastDecision === "" || $lastDecision === "PASS") return;
    $hadFloating = HasFloatingMemory(GetZoneObject($lastDecision));
    MZMove($player, $lastDecision, "myBanish");
    if($hadFloating) NicoOnFloatingMemoryBanished($player);
    // Now destroy target item or weapon with memory cost 0 or reserve cost <= 4
    $validTargets = [];
    $allItems = array_merge(
        ZoneSearch("myField", ["ITEM", "REGALIA"]),
        ZoneSearch("theirField", ["ITEM", "REGALIA"]),
        ZoneSearch("myField", ["WEAPON"]),
        ZoneSearch("theirField", ["WEAPON"])
    );
    foreach($allItems as $mzI) {
        $iObj = GetZoneObject($mzI);
        if(CardCost_memory($iObj->CardID) == 0 || CardCost_reserve($iObj->CardID) <= 4) {
            $validTargets[] = $mzI;
        }
    }
    if(empty($validTargets)) return;
    $targetStr = implode("&", $validTargets);
    DecisionQueueController::AddDecision($player, "MZCHOOSE", $targetStr, 1, "Destroy_target_item_or_weapon");
    DecisionQueueController::AddDecision($player, "CUSTOM", "SmashingForceDestroy", 1);
};

$customDQHandlers["SmashingForceDestroy"] = function($player, $parts, $lastDecision) {
    if($lastDecision === "-" || $lastDecision === "") return;
    $targetObj = GetZoneObject($lastDecision);
    if($targetObj === null) return;
    OnLeaveField($player, $lastDecision);
    $dest = $player == $targetObj->Controller ? "myGraveyard" : "theirGraveyard";
    MZMove($player, $lastDecision, $dest);
    DecisionQueueController::CleanupRemovedCards();
};

// --- Converge Reflections (TBVLLRPiwP): sacrifice the chosen non-token item/weapon (additional cost) ---
$customDQHandlers["ConvergeReflectionsSacrifice"] = function($player, $parts, $lastDecision) {
    if($lastDecision === "-" || $lastDecision === "") return;
    $obj = GetZoneObject($lastDecision);
    if($obj === null) return;
    OnLeaveField($player, $lastDecision);
    MZMove($player, $lastDecision, "myGraveyard");
    DecisionQueueController::CleanupRemovedCards();
};

// --- Meteor Strike (dwavcoxpnj): destroy chosen non-champion object ---
$customDQHandlers["MeteorStrikeDestroy"] = function($player, $parts, $lastDecision) {
    if($lastDecision === "-" || $lastDecision === "") return;
    $targetObj = GetZoneObject($lastDecision);
    if($targetObj === null) return;
    OnLeaveField($player, $lastDecision);
    $dest = $player == $targetObj->Controller ? "myGraveyard" : "theirGraveyard";
    MZMove($player, $lastDecision, $dest);
    DecisionQueueController::CleanupRemovedCards();
};

// --- Exorcism (4n6dd4f01r): sacrifice each ephemeral object unless controller pays (1) ---
function ExorcismProcess($player) {
    // Collect all ephemeral objects from both fields
    $targets = [];
    for($p = 1; $p <= 2; ++$p) {
        $field = &GetField($p);
        for($i = 0; $i < count($field); ++$i) {
            if(!$field[$i]->removed && IsEphemeral($field[$i]) && !PropertyContains(EffectiveCardType($field[$i]), "CHAMPION")) {
                global $playerID;
                $mzRef = ($p == $playerID) ? "myField-" . $i : "theirField-" . $i;
                $targets[] = $p . "|" . $mzRef;
            }
        }
    }
    if(empty($targets)) return;
    $encoded = implode(",", $targets);
    DecisionQueueController::AddDecision($player, "CUSTOM", "ExorcismNext|" . $encoded, 1);
}

$customDQHandlers["ExorcismNext"] = function($player, $parts, $lastDecision) {
    $encoded = $parts[0];
    $targets = explode(",", $encoded);
    if(empty($targets)) return;
    $current = array_shift($targets);
    $pair = explode("|", $current);
    $controller = intval($pair[0]);
    $mzRef = $pair[1];
    $obj = GetZoneObject($mzRef);
    if($obj === null || $obj->removed) {
        // Object already gone, skip to next
        if(!empty($targets)) {
            $remaining = implode(",", $targets);
            DecisionQueueController::AddDecision($player, "CUSTOM", "ExorcismNext|" . $remaining, 1);
        }
        return;
    }
    // Check if controller has cards in hand to pay (1)
    $hand = &GetHand($controller);
    $remaining = implode(",", $targets);
    if(count($hand) > 0) {
        DecisionQueueController::AddDecision($controller, "YESNO", "-", 1,
            tooltip:"Pay_(1)_to_keep_" . CardName($obj->CardID) . "?_(Exorcism)");
        DecisionQueueController::AddDecision($controller, "CUSTOM", "ExorcismPayOrSacrifice|" . $mzRef . "|" . $remaining, 1);
    } else {
        // Can't pay, sacrifice immediately
        DoSacrificeFighter($controller, $mzRef);
        DecisionQueueController::CleanupRemovedCards();
        if(!empty($targets)) {
            DecisionQueueController::AddDecision($player, "CUSTOM", "ExorcismNext|" . $remaining, 1);
        }
    }
};

$customDQHandlers["ExorcismPayOrSacrifice"] = function($player, $parts, $lastDecision) {
    $mzRef = $parts[0];
    $remaining = $parts[1] ?? "";
    $obj = GetZoneObject($mzRef);
    if($obj === null || $obj->removed) {
        if($remaining !== "") {
            // Determine original activating player from the remaining targets 
            DecisionQueueController::AddDecision($player, "CUSTOM", "ExorcismNext|" . $remaining, 1);
        }
        return;
    }
    if($lastDecision === "YES") {
        // Pay (1) — move a card from hand to memory
        $hand = &GetHand($player);
        if(count($hand) > 0) {
            MZMove($player, "myHand-0", "myMemory");
        }
    } else {
        // Sacrifice the ephemeral object
        DoSacrificeFighter($player, $mzRef);
        DecisionQueueController::CleanupRemovedCards();
    }
    if($remaining !== "") {
        DecisionQueueController::AddDecision($player, "CUSTOM", "ExorcismNext|" . $remaining, 1);
    }
};

// --- Alkahest (xfpk9xycwz): choose Potion for age counter ---
$customDQHandlers["AlkahestAgeCounter"] = function($player, $parts, $lastDecision) {
    if($lastDecision === "-" || $lastDecision === "") return;
    AddCounters($player, $lastDecision, "age", 1);
};

// --- Washuru (k5iv040vcq): banish chosen graveyard card ---
$customDQHandlers["WashuruBanish"] = function($player, $parts, $lastDecision) {
    if($lastDecision === "-" || $lastDecision === "") return;
    MZMove($player, $lastDecision, "myBanish");
};

$customDQHandlers["NightshadeWither"] = function($player, $parts, $lastDecision) {
    if($lastDecision === "-" || $lastDecision === "") return;
    AddCounters($player, $lastDecision, "wither", 1);
};

// --- Misteye Archer (m6c8xy4cje): finish look — put water to GY, become distant, prevent 2 ---
$customDQHandlers["MisteyeArcherFinish"] = function($player, $parts, $lastDecision) {
    $mzID = $parts[0];
    if($lastDecision === "YES") {
        $tempCards = ZoneSearch("myTempZone");
        if(!empty($tempCards)) {
            MZMove($player, $tempCards[count($tempCards) - 1], "myGraveyard");
        }
        BecomeDistant($player, $mzID);
        AddTurnEffect($mzID, "PREVENT_ALL_2");
    } else {
        PutTempZoneOnTopOfDeck($player);
    }
};

// --- Swooping Talons (rj52215upu) helpers ---
function SwoopingTalonsMode1($player) {
    $allAllies = array_merge(ZoneSearch("myField", ["ALLY"]), ZoneSearch("theirField", ["ALLY"]));
    if(empty($allAllies)) return;
    $allyStr = implode("&", $allAllies);
    DecisionQueueController::AddDecision($player, "MZCHOOSE", $allyStr, 1, "Deal_2_damage_to_target_ally");
    DecisionQueueController::AddDecision($player, "CUSTOM", "SwoopingTalons_DealDmg", 1);
}

function SwoopingTalonsMode2($player) {
    $validItems = [];
    $allItems = array_merge(ZoneSearch("myField", ["ITEM", "REGALIA"]), ZoneSearch("theirField", ["ITEM", "REGALIA"]));
    foreach($allItems as $mzI) {
        $iObj = GetZoneObject($mzI);
        if(CardCost_memory($iObj->CardID) == 0 || CardCost_reserve($iObj->CardID) <= 4) {
            $validItems[] = $mzI;
        }
    }
    if(empty($validItems)) return;
    $itemStr = implode("&", $validItems);
    DecisionQueueController::AddDecision($player, "MZCHOOSE", $itemStr, 1, "Destroy_target_item");
    DecisionQueueController::AddDecision($player, "CUSTOM", "SwoopingTalons_DestroyItem", 1);
}

$customDQHandlers["SwoopingTalons_DealDmg"] = function($player, $parts, $lastDecision) {
    if($lastDecision === "-" || $lastDecision === "") return;
    $mzID = DecisionQueueController::GetVariable("mzID");
    DealDamage($player, $mzID, $lastDecision, 2);
};

$customDQHandlers["SwoopingTalons_DestroyItem"] = function($player, $parts, $lastDecision) {
    if($lastDecision === "-" || $lastDecision === "") return;
    $targetObj = GetZoneObject($lastDecision);
    if($targetObj === null) return;
    OnLeaveField($player, $lastDecision);
    $dest = $player == $targetObj->Controller ? "myGraveyard" : "theirGraveyard";
    $dest = EphemeralRedirectDest($targetObj, $dest, $player);
    MZMove($player, $lastDecision, $dest);
    DecisionQueueController::CleanupRemovedCards();
};

$customDQHandlers["SwoopingTalons_Choice"] = function($player, $parts, $lastDecision) {
    if($lastDecision === "YES") {
        SwoopingTalonsMode1($player);
    } else {
        SwoopingTalonsMode2($player);
    }
};

// --- Tonoris, Genesis Aegis (ta6qsesw2u): recollection phase Obelisk summon ---

function TonorisRecollection($player, $fieldIndex) {
    $field = &GetField($player);
    $obj = $field[$fieldIndex];
    $obelisks = [
        "wk0pw0y6is" => "Summon_Obelisk_of_Armaments?",
        "xy5lh23qu7" => "Summon_Obelisk_of_Fabrication?",
        "d6soporhlq" => "Summon_Obelisk_of_Protection?"
    ];
    $counters = is_array($obj->Counters) ? $obj->Counters : [];
    $chosen = isset($counters['tonoris_chosen']) ? $counters['tonoris_chosen'] : [];
    $available = [];
    foreach($obelisks as $id => $tooltip) {
        if(!in_array($id, $chosen)) {
            $available[$id] = $tooltip;
        }
    }
    if(empty($available)) return;
    if(count($available) == 1) {
        $obeliskID = array_key_first($available);
        MZAddZone($player, "myField", $obeliskID);
        $chosen[] = $obeliskID;
        if(!is_array($field[$fieldIndex]->Counters)) $field[$fieldIndex]->Counters = [];
        $field[$fieldIndex]->Counters['tonoris_chosen'] = $chosen;
        return;
    }
    $firstID = array_key_first($available);
    $firstTooltip = $available[$firstID];
    DecisionQueueController::AddDecision($player, "YESNO", "-", 1, tooltip:$firstTooltip);
    DecisionQueueController::AddDecision($player, "CUSTOM", "TonorisChooseObelisk|$fieldIndex|$firstID", 1);
}

$customDQHandlers["TonorisChooseObelisk"] = function($player, $parts, $lastDecision) {
    $fieldIndex = intval($parts[0]);
    $currentObeliskID = $parts[1];
    $field = &GetField($player);
    if(!isset($field[$fieldIndex]) || $field[$fieldIndex]->removed) return;
    $obj = $field[$fieldIndex];
    $counters = is_array($obj->Counters) ? $obj->Counters : [];
    $chosen = isset($counters['tonoris_chosen']) ? $counters['tonoris_chosen'] : [];

    if($lastDecision === "YES") {
        MZAddZone($player, "myField", $currentObeliskID);
        $chosen[] = $currentObeliskID;
        if(!is_array($field[$fieldIndex]->Counters)) $field[$fieldIndex]->Counters = [];
        $field[$fieldIndex]->Counters['tonoris_chosen'] = $chosen;
        return;
    }

    // Player said NO — move to next available obelisk
    $obelisks = ["wk0pw0y6is", "xy5lh23qu7", "d6soporhlq"];
    $tooltips = [
        "wk0pw0y6is" => "Summon_Obelisk_of_Armaments?",
        "xy5lh23qu7" => "Summon_Obelisk_of_Fabrication?",
        "d6soporhlq" => "Summon_Obelisk_of_Protection?"
    ];
    $remaining = [];
    foreach($obelisks as $id) {
        if(!in_array($id, $chosen) && $id !== $currentObeliskID) {
            $remaining[] = $id;
        }
    }
    if(count($remaining) == 1) {
        MZAddZone($player, "myField", $remaining[0]);
        $chosen[] = $remaining[0];
        if(!is_array($field[$fieldIndex]->Counters)) $field[$fieldIndex]->Counters = [];
        $field[$fieldIndex]->Counters['tonoris_chosen'] = $chosen;
    } elseif(count($remaining) > 1) {
        DecisionQueueController::AddDecision($player, "YESNO", "-", 1, tooltip:$tooltips[$remaining[0]]);
        DecisionQueueController::AddDecision($player, "CUSTOM", "TonorisChooseObelisk|$fieldIndex|$remaining[0]", 1);
    }
};

// --- Synthetic Core (w0y6isxy5l): return dying Automaton ally to memory ---

$customDQHandlers["SyntheticCoreChoice"] = function($player, $parts, $lastDecision) {
    if($lastDecision !== "YES") return;
    $fieldIndex = intval($parts[0]);
    $dyingCardID = $parts[1];
    // Banish Synthetic Core
    MZMove($player, "myField-" . $fieldIndex, "myBanish");
    DecisionQueueController::CleanupRemovedCards();
    // Return the dying ally from graveyard to memory
    $gy = GetZone("myGraveyard");
    for($gi = count($gy) - 1; $gi >= 0; --$gi) {
        if(!$gy[$gi]->removed && $gy[$gi]->CardID === $dyingCardID) {
            MZMove($player, "myGraveyard-" . $gi, "myMemory");
            break;
        }
    }
};

// --- Claude, Fated Visionary (52215upufy): return up to N Automaton allies from GY to memory ---
function ClaudeReturnAutomatonContinue($player, $remaining) {
    if($remaining <= 0) return;
    $gyAutomatons = ZoneSearch("myGraveyard", ["ALLY"], cardSubtypes: ["AUTOMATON"]);
    if(empty($gyAutomatons)) return;
    $targetStr = implode("&", $gyAutomatons);
    DecisionQueueController::AddDecision($player, "MZMAYCHOOSE", $targetStr, 1, tooltip:"Return_Automaton_ally_from_graveyard_to_memory?");
    DecisionQueueController::AddDecision($player, "CUSTOM", "ClaudeReturnAutomaton|$remaining", 1);
}

$customDQHandlers["ClaudeReturnAutomaton"] = function($player, $parts, $lastDecision) {
    $remaining = intval($parts[0]);
    if($lastDecision === "-" || $lastDecision === "") return;
    MZMove($player, $lastDecision, "myMemory");
    ClaudeReturnAutomatonContinue($player, $remaining - 1);
};

// --- Winbless Gatekeeper (y5ttkk39i1): On Enter may pay (2) to buff Guardian ally ---

$customDQHandlers["WinblessGatekeeperPay"] = function($player, $parts, $lastDecision) {
    if($lastDecision !== "YES") return;
    $hand = &GetHand($player);
    if(count($hand) < 2) return;
    ReserveCard($player);
    ReserveCard($player);
    $guardianAllies = ZoneSearch("myField", ["ALLY"], cardSubtypes: ["GUARDIAN"]);
    if(empty($guardianAllies)) return;
    $targetStr = implode("&", $guardianAllies);
    // Block 105: must come after the two ReserveCard_Choice(100) chains fully
    // resolve (inner blocks 1/99) before the Guardian ally target is chosen.
    DecisionQueueController::AddDecision($player, "MZCHOOSE", $targetStr, 105, "Put_buff_on_Guardian_ally");
    DecisionQueueController::AddDecision($player, "CUSTOM", "WinblessGatekeeperBuff", 105);
};

$customDQHandlers["WinblessGatekeeperBuff"] = function($player, $parts, $lastDecision) {
    if($lastDecision === "-" || $lastDecision === "" || $lastDecision === "PASS") return;
    AddCounters($player, $lastDecision, "buff", 1);
};

// --- Reconstructive Surgery (z308kuz07n): choose one DQ handlers ---

$customDQHandlers["ReconSurgeryBuff"] = function($player, $parts, $lastDecision) {
    if($lastDecision === "-" || $lastDecision === "" || $lastDecision === "PASS") return;
    AddCounters($player, $lastDecision, "buff", 1);
};

$customDQHandlers["ReconSurgeryReturn"] = function($player, $parts, $lastDecision) {
    if($lastDecision === "-" || $lastDecision === "" || $lastDecision === "PASS") return;
    MZMove($player, $lastDecision, "myMemory");
};

$customDQHandlers["ReconSurgeryChoice"] = function($player, $parts, $lastDecision) {
    if($lastDecision === "YES") {
        $automatonAllies = ZoneSearch("myField", ["ALLY"], cardSubtypes: ["AUTOMATON"]);
        $automatonAllies = FilterSpellshroudTargets($automatonAllies);
        if(empty($automatonAllies)) return;
        $targetStr = implode("&", $automatonAllies);
        DecisionQueueController::AddDecision($player, "MZCHOOSE", $targetStr, 1, "Put_buff_on_Automaton_ally");
        DecisionQueueController::AddDecision($player, "CUSTOM", "ReconSurgeryBuff", 1);
    } else {
        $gyAutomatons = ZoneSearch("myGraveyard", ["ALLY"], cardSubtypes: ["AUTOMATON"]);
        if(empty($gyAutomatons)) return;
        $targetStr = implode("&", $gyAutomatons);
        DecisionQueueController::AddDecision($player, "MZCHOOSE", $targetStr, 1, "Return_Automaton_to_memory");
        DecisionQueueController::AddDecision($player, "CUSTOM", "ReconSurgeryReturn", 1);
    }
};

// --- Geni, Gifted Mechanist (wuir99sx6q): banish from graveyard DQ handlers ---

$customDQHandlers["GeniMechanistBanish"] = function($player, $parts, $lastDecision) {
    if($lastDecision === "-" || $lastDecision === "") return;
    $chosenObj = GetZoneObject($lastDecision);
    if($chosenObj === null) return;
    $chosenCardID = $chosenObj->CardID;
    $hadFloating = HasFloatingMemory($chosenObj);
    MZMove($player, $lastDecision, "myBanish");
    // If Automaton ally, summon Automaton Drone
    if(PropertyContains(CardType($chosenCardID), "ALLY") && PropertyContains(CardSubtypes($chosenCardID), "AUTOMATON")) {
        MZAddZone($player, "myField", "mu6gvnta6q");
    }
    // If floating memory, buff an Automaton ally
    if($hadFloating) {
        $automatonAllies = ZoneSearch("myField", ["ALLY"], cardSubtypes: ["AUTOMATON"]);
        if(!empty($automatonAllies)) {
            $targetStr = implode("&", $automatonAllies);
            DecisionQueueController::AddDecision($player, "MZCHOOSE", $targetStr, 1, "Put_2_buff_counters_on_Automaton");
            DecisionQueueController::AddDecision($player, "CUSTOM", "GeniMechanistBuff", 1);
        }
    }
};

$customDQHandlers["GeniMechanistBuff"] = function($player, $parts, $lastDecision) {
    if($lastDecision === "-" || $lastDecision === "" || $lastDecision === "PASS") return;
    AddCounters($player, $lastDecision, "buff", 2);
};

// --- Fractal of Creation (x7mnu1xhs5): summon token copy DQ handler ---

$customDQHandlers["FractalCreationCopy"] = function($player, $parts, $lastDecision) {
    if($lastDecision === "-" || $lastDecision === "" || $lastDecision === "PASS") return;
    $targetObj = GetZoneObject($lastDecision);
    if($targetObj === null) return;
    MZAddZone($player, "myField", $targetObj->CardID);
};

// Obelisk of Armaments — summon Aurousteel Greatsword after reserve payment
$customDQHandlers["ObeliskArmamentsSummon"] = function($player, $parts, $lastDecision) {
    MZAddZone($player, "myField", "hkurfp66pv"); // Aurousteel Greatsword token
};

// Obelisk of Fabrication — summon Automaton Drone with buff counter after reserve payment
$customDQHandlers["ObeliskFabricationSummon"] = function($player, $parts, $lastDecision) {
    MZAddZone($player, "myField", "mu6gvnta6q"); // Automaton Drone token
    $field = &GetField($player);
    $newIdx = count($field) - 1;
    AddCounters($player, "myField-" . $newIdx, "buff", 1);
};

// ============================================================================
// Gun & Bullet Infrastructure
// ============================================================================

/**
 * Renewable cards: if banished from field or intent, go to material deck instead.
 */
$Renewable_Cards = [
    "f8urrqtjot" => true, // Turbulent Bullet
    "ywc08c9htu" => true, // Cascading Round
    "ao8bki6fxx" => true, // Steel Slug
    "hreqhj1trn" => true, // Windpiercer
    "XgzTexcCSA" => true, // Punishing Cartridge
];

/**
 * Check if a weapon (Gun) currently has a bullet loaded.
 * Loaded bullets are stored in the gun's Subcards array.
 */
function IsGunLoaded($obj) {
    return is_array($obj->Subcards) && !empty($obj->Subcards);
}

/**
 * Get mzIDs of unloaded Gun weapons the player controls that are awake.
 */
function GetUnloadedGuns($player) {
    $guns = ZoneSearch("myField", ["WEAPON"], cardSubtypes: ["GUN"]);
    $unloaded = [];
    foreach($guns as $mzID) {
        $obj = GetZoneObject($mzID);
        if($obj !== null && !IsGunLoaded($obj)) {
            $unloaded[] = $mzID;
        }
    }
    return $unloaded;
}

/**
 * Get mzIDs of unloaded Bow weapons the player controls.
 */
function GetUnloadedBows($player) {
    $bows = ZoneSearch("myField", ["WEAPON"], cardSubtypes: ["BOW"]);
    $unloaded = [];
    foreach($bows as $mzID) {
        $obj = GetZoneObject($mzID);
        if($obj !== null && !IsGunLoaded($obj)) {
            $unloaded[] = $mzID;
        }
    }
    return $unloaded;
}

/**
 * Load an arrow into a bow weapon.
 * The arrow is removed from the field (its CardID is stored in the bow's Subcards).
 */
function LoadArrowIntoBow($player, $arrowMZ, $bowMZ) {
    $arrowObj = GetZoneObject($arrowMZ);
    $bowObj = &GetZoneObject($bowMZ);
    if($arrowObj === null || $bowObj === null) return;
    $arrowCardID = $arrowObj->CardID;
    if(!is_array($bowObj->Subcards)) $bowObj->Subcards = [];
    $bowObj->Subcards[] = $arrowCardID;
    $arrowObj->removed = true;
    DecisionQueueController::CleanupRemovedCards();
}

/**
 * Diana, Cursebreaker: materialize N Bullet cards from material deck sequentially.
 * Queues MZCHOOSE + MATERIALIZE for each bullet.
 */
function CursebreakerMaterializeBullets($player, $count) {
    for($b = 0; $b < $count; ++$b) {
        $materialZone = GetZone("myMaterial");
        $bulletMZs = [];
        for($i = 0; $i < count($materialZone); ++$i) {
            $obj = $materialZone[$i];
            if(!$obj->removed && PropertyContains(CardSubtypes($obj->CardID), "BULLET")) {
                $bulletMZs[] = "myMaterial-" . $i;
            }
        }
        if(empty($bulletMZs)) return;
        DecisionQueueController::AddDecision($player, "MZCHOOSE", implode("&", $bulletMZs), 1, "Materialize_Bullet_" . ($b + 1));
        DecisionQueueController::AddDecision($player, "CUSTOM", "MATERIALIZE", 1);
    }
}

/**
 * Load a bullet into a gun weapon.
 * The bullet is removed from the field (its CardID is stored in the gun's Subcards).
 * The bullet card must be on the field and the gun must be unloaded.
 */
function LoadBulletIntoGun($player, $bulletMZ, $gunMZ) {
    $bulletObj = GetZoneObject($bulletMZ);
    $gunObj = &GetZoneObject($gunMZ);
    if($bulletObj === null || $gunObj === null) return;
    $bulletCardID = $bulletObj->CardID;
    // Store bullet CardID in gun's Subcards
    if(!is_array($gunObj->Subcards)) $gunObj->Subcards = [];
    $gunObj->Subcards[] = $bulletCardID;
    // Remove bullet from field silently (loading is a cost, no LeaveField triggers)
    $bulletObj->removed = true;
    DecisionQueueController::CleanupRemovedCards();

    // Loaded Thoughts (hh88rx6p3p): [CB] Whenever becomes loaded, may put top card of deck into GY
    if($gunObj->CardID === "hh88rx6p3p" && !HasNoAbilities($gunObj)) {
        if(IsClassBonusActive($player, ["RANGER"])) {
            $deck = GetDeck($player);
            if(!empty($deck)) {
                DecisionQueueController::AddDecision($player, "YESNO", "-", 1, tooltip:"Put_top_card_of_deck_into_graveyard?_(Loaded_Thoughts)");
                DecisionQueueController::AddDecision($player, "CUSTOM", "LoadedThoughtsMill", 1);
            }
        }
    }

    // Shadow's Twin (5vettczb14): Whenever becomes loaded, +2 POWER until end of turn
    if($gunObj->CardID === "5vettczb14" && !HasNoAbilities($gunObj)) {
        global $playerID;
        $gunZone = ($player == $playerID) ? "myField" : "theirField";
        $gunField = GetZone($gunZone);
        for($sti = 0; $sti < count($gunField); ++$sti) {
            if(!$gunField[$sti]->removed && $gunField[$sti]->CardID === "5vettczb14") {
                AddTurnEffect($gunZone . "-" . $sti, "5vettczb14_POWER");
                break;
            }
        }
    }
}

/**
 * Count Curse cards in a player's champion lineage.
 */
function CountCursesInLineage($player) {
    $lineage = GetChampionLineage($player);
    $count = 0;
    foreach($lineage as $cardID) {
        if(PropertyContains(CardSubtypes($cardID), "CURSE")) {
            $count++;
        }
    }
    return $count;
}

/**
 * Creeping Torment (zrplywc08c) inherited trigger:
 * "Whenever you draw your 2nd card each turn, deal 2 unpreventable damage to this champion."
 * Called from DoDrawCard when the 2nd card is drawn.
 */
function CreepingTormentDrawTrigger($player) {
    if(AreCurseLineageAbilitiesSuppressed($player)) return;
    if(!ChampionHasInLineage($player, "zrplywc08c")) return;
    $champions = ZoneSearch("myField", ["CHAMPION"]);
    if(empty($champions)) return;
    DealUnpreventableDamage($player, $champions[0], $champions[0], 2);
}

/**
 * Get Curse card IDs from a champion's lineage along with their indices.
 * @param int $player The player whose champion to check.
 * @return array Array of ['cardID' => string, 'index' => int] for each curse subcard.
 */
function GetCursesInLineage($player) {
    $field = &GetField($player);
    foreach($field as $obj) {
        if(!$obj->removed && PropertyContains(EffectiveCardType($obj), "CHAMPION") && $obj->Controller == $player) {
            if(!is_array($obj->Subcards)) return [];
            $curses = [];
            foreach($obj->Subcards as $idx => $cardID) {
                if(PropertyContains(CardSubtypes($cardID), "CURSE")) {
                    $curses[] = ['cardID' => $cardID, 'index' => $idx];
                }
            }
            return $curses;
        }
    }
    return [];
}

// --- Exorcise Curses (u1xhs5jwsl): choose up to 2 Curse cards from a champion's lineage ---

/**
 * Set up the Exorcise Curses flow: stage curses from the chosen champion's lineage
 * into TempZone and queue MZMAYCHOOSE decisions.
 * @param int    $player       The acting player.
 * @param int    $targetPlayer The player whose champion's lineage to exorcise.
 */
function ExorciseCursesSetup($player, $targetPlayer) {
    DecisionQueueController::StoreVariable("ExorciseTarget", $targetPlayer);
    $curses = GetCursesInLineage($targetPlayer);
    if(empty($curses)) return;
    $tempZone = &GetTempZone($player);
    // Clear TempZone first
    while(count($tempZone) > 0) array_pop($tempZone);
    foreach($curses as $curse) {
        MZAddZone($player, "myTempZone", $curse['cardID']);
    }
    $tempMZs = [];
    for($i = 0; $i < count($curses); ++$i) {
        $tempMZs[] = "myTempZone-" . $i;
    }
    $options = implode("&", $tempMZs);
    DecisionQueueController::AddDecision($player, "MZMAYCHOOSE", $options, 1, "Choose_Curse_to_discard");
    DecisionQueueController::AddDecision($player, "CUSTOM", "ExorciseCursesPick1", 1);
}

$customDQHandlers["ExorciseCursesPick1"] = function($player, $parts, $lastDecision) {
    $targetPlayer = intval(DecisionQueueController::GetVariable("ExorciseTarget"));
    if($lastDecision === "-" || $lastDecision === "") {
        // Player declined — clean up TempZone
        $tempZone = &GetTempZone($player);
        while(count($tempZone) > 0) array_pop($tempZone);
        return;
    }
    // Remove the chosen curse from lineage
    $chosenObj = GetZoneObject($lastDecision);
    $chosenCardID = $chosenObj->CardID;
    RemoveFromChampionLineage($targetPlayer, $chosenCardID, "myGraveyard");
    // Remove from TempZone
    MZRemove($player, $lastDecision);
    DecisionQueueController::CleanupRemovedCards();
    // Offer second pick if any curses remain in TempZone
    $tempZone = &GetTempZone($player);
    $remaining = [];
    for($i = 0; $i < count($tempZone); ++$i) {
        if(!$tempZone[$i]->removed) {
            $remaining[] = "myTempZone-" . $i;
        }
    }
    if(!empty($remaining)) {
        $options = implode("&", $remaining);
        DecisionQueueController::AddDecision($player, "MZMAYCHOOSE", $options, 1, "Choose_another_Curse_to_discard");
        DecisionQueueController::AddDecision($player, "CUSTOM", "ExorciseCursesPick2", 1);
    } else {
        while(count($tempZone) > 0) array_pop($tempZone);
    }
};

$customDQHandlers["ExorciseCursesPick2"] = function($player, $parts, $lastDecision) {
    $targetPlayer = intval(DecisionQueueController::GetVariable("ExorciseTarget"));
    $tempZone = &GetTempZone($player);
    if($lastDecision !== "-" && $lastDecision !== "") {
        $chosenObj = GetZoneObject($lastDecision);
        $chosenCardID = $chosenObj->CardID;
        RemoveFromChampionLineage($targetPlayer, $chosenCardID, "myGraveyard");
    }
    // Clean up TempZone
    while(count($tempZone) > 0) array_pop($tempZone);
};

/**
 * Add a card to the bottom of a player's champion lineage (Subcards array).
 * Used by curse spells and abilities that say "put CARDNAME on the bottom
 * of [target] champion's lineage."
 * @param int    $player The player whose champion receives the card.
 * @param string $cardID The card ID to add to the lineage.
 */
function AddToChampionLineage($player, $cardID) {
    $field = &GetField($player);
    for($i = 0; $i < count($field); ++$i) {
        if(!$field[$i]->removed && PropertyContains(EffectiveCardType($field[$i]), "CHAMPION") && $field[$i]->Controller == $player) {
            if(!is_array($field[$i]->Subcards)) $field[$i]->Subcards = [];
            $field[$i]->Subcards[] = $cardID;
            return;
        }
    }
}

/**
 * Remove a card from a player's champion lineage and return it to a destination zone.
 * Used by Exorcise Curses and similar effects that remove cards from lineage.
 * @param int    $player    The player whose champion's lineage to modify.
 * @param string $cardID    The card ID to remove.
 * @param string $destZone  Destination zone (e.g. "myGraveyard").
 * @return bool  True if the card was found and removed.
 */
function RemoveFromChampionLineage($player, $cardID, $destZone = "myGraveyard") {
    $field = &GetField($player);
    for($i = 0; $i < count($field); ++$i) {
        if(!$field[$i]->removed && PropertyContains(EffectiveCardType($field[$i]), "CHAMPION") && $field[$i]->Controller == $player) {
            if(!is_array($field[$i]->Subcards)) return false;
            $idx = array_search($cardID, $field[$i]->Subcards);
            if($idx === false) return false;
            array_splice($field[$i]->Subcards, $idx, 1);
            MZAddZone($player, $destZone, $cardID);
            return true;
        }
    }
    return false;
}

/**
 * Check if a card is Renewable.
 */
function IsRenewable($cardID) {
    global $Renewable_Cards;
    return isset($Renewable_Cards[$cardID]);
}

// --- Vanishing Shot: return hit ally to owner's memory ---
$customDQHandlers["VanishingShotReturn"] = function($player, $parts, $lastDecision) {
    if($lastDecision !== "YES") return;
    $targetMZ = DecisionQueueController::GetVariable("VanishingShotTarget");
    if(empty($targetMZ) || $targetMZ === "-") return;
    $targetObj = GetZoneObject($targetMZ);
    if($targetObj === null || $targetObj->removed) return;
    // Return to owner's memory
    global $playerID;
    $dest = $targetObj->Owner == $playerID ? "myMemory" : "theirMemory";
    MZMove($player, $targetMZ, $dest);
};

// --- LoadBullet: custom DQ handler for the REST:Load activated ability ---
$customDQHandlers["LoadBullet"] = function($player, $parts, $lastDecision) {
    // $parts[0] = mzID of the bullet that was rested
    // $lastDecision = mzID of the chosen unloaded gun
    $bulletMZ = $parts[0];
    if($lastDecision === "-" || $lastDecision === "") return;
    LoadBulletIntoGun($player, $bulletMZ, $lastDecision);
};

// --- LoadArrow: custom DQ handler for loading an arrow into a bow ---
$customDQHandlers["LoadArrow"] = function($player, $parts, $lastDecision) {
    // $parts[0] = mzID of the arrow on the field
    // $lastDecision = mzID of the chosen unloaded bow
    $arrowMZ = $parts[0];
    if($lastDecision === "-" || $lastDecision === "") return;
    LoadArrowIntoBow($player, $arrowMZ, $lastDecision);
};

// --- Undeniable Truth (UaUfw7yFTW): sacrifice ally, draw 1, add prep counter ---
$customDQHandlers["UndeniableTruthSacrifice"] = function($player, $parts, $lastDecision) {
    if($lastDecision === "-" || $lastDecision === "") return;
    DoSacrificeFighter($player, $lastDecision);
    Draw($player, 1);
    AddPrepCounter($player, 1);
};

// --- Shattered Hope (XOevViFTB3): Glimpse 1, Draw 1, add global sheen effect ---
// (Handled in macro code via AddGlobalEffects)

// --- Hua Xiong (TvugEkGGVd): REST + discard Polearm attack, then prevent 2 damage to target unit ---
// (The protect prevention is handled by the macro code via AddTurnEffect)

// --- Huang Zhong (XikXt8WyNp): Make Rangers distant, return self to memory ---
$customDQHandlers["HuangZhongDistant"] = function($player, $parts, $lastDecision) {
    // $parts[0] = mzID of Huang Zhong
    $mzID = $parts[0];
    global $playerID;
    $player = GetController($mzID);
    // Make all Ranger units on field distant
    $rangerAllies = ZoneSearch("myField", ["ALLY"], cardSubtypes: ["RANGER"]);
    foreach($rangerAllies as $rangerMZ) {
        BecomeDistant($player, $rangerMZ);
    }
    // Return Huang Zhong to memory
    MZMove($player, $mzID, "myMemory");
};

// --- Hua Xiong (TvugEkGGVd): Discard Polearm attack card ---
$customDQHandlers["HuaXiongDiscard"] = function($player, $parts, $lastDecision) {
    if($lastDecision === "-" || $lastDecision === "") return;
    DoDiscardCard($player, $lastDecision);
};

// --- Punishing Cartridge (XgzTexcCSA): Choose discard modes ---
function PunishingCartridgeChooseMode($player, $mzID, $modeCount) {
    if($modeCount <= 0) return;
    $modes = [];
    $modes[] = "Change_target_of_attack";
    $modes[] = "Grant_On_Champion_Hit_ability";
    // For simplicity, use a YESNO for the first mode, then queue the second
    DecisionQueueController::AddDecision($player, "YESNO", "-", 1, "Choose_first_mode:_Change_attack_target?");
    DecisionQueueController::AddDecision($player, "CUSTOM", "PunishingCartridgeMode1|$mzID|$modeCount", 1);
}

$customDQHandlers["PunishingCartridgeMode1"] = function($player, $parts, $lastDecision) {
    $mzID = $parts[0];
    $modeCount = intval($parts[1]);
    if($lastDecision === "YES") {
        // Apply "change target" mode if needed (happens via combat mechanics)
    }
    $modeCount--;
    if($modeCount > 0) {
        PunishingCartridgeChooseMode($player, $mzID, $modeCount);
    }
    // On Champion Hit granting: add global effect or TurnEffect to the weapon
    // (Usually handled by bullet On Hit mechanics)
};

// --- Zander, Blinding Steel (UAF6Nr7GUE): Opponent puts hand card into memory (iterative) ---
function ZanderBlindingSteelStep($player, $opponent, $remaining) {
    if($remaining <= 0) return;
    global $playerID;
    $handZone = $opponent == $playerID ? "myHand" : "theirHand";
    $hand = ZoneSearch($handZone);
    if(empty($hand)) return;
    $handStr = implode("&", $hand);
    DecisionQueueController::AddDecision($opponent, "MZCHOOSE", $handStr, 1, tooltip:"Put_card_from_hand_into_memory_($remaining_remaining)");
    DecisionQueueController::AddDecision($opponent, "CUSTOM", "ZanderBlindingSteelMemory|$remaining", 1);
}

$customDQHandlers["ZanderBlindingSteelMemory"] = function($player, $parts, $lastDecision) {
    if($lastDecision === "-" || $lastDecision === "") return;
    $memZone = (strpos($lastDecision, "my") === 0) ? "myMemory" : "theirMemory";
    MZMove($player, $lastDecision, $memZone);
    DecisionQueueController::CleanupRemovedCards();
    $remaining = intval($parts[0]) - 1;
    $opponent = $player;
    ZanderBlindingSteelStep(-1, $opponent, $remaining); // $turnPlayer is not used here, pass dummy
};

// --- Keep of the Golden Sashes (gjhv2etytr): upkeep — banish 2 from GY or sacrifice ---
$customDQHandlers["KeepGoldenSashesUpkeep"] = function($player, $parts, $lastDecision) {
    // $parts[0] = field index of the Keep
    $fieldIdx = intval($parts[0]);
    if($lastDecision === "YES") {
        // Player chose to banish 2 cards from graveyard
        global $playerID;
        $gravZone = $player == $playerID ? "myGraveyard" : "theirGraveyard";
        $gyCards = GetZone($gravZone);
        $gyMZs = [];
        for($gi = 0; $gi < count($gyCards); ++$gi) {
            if(!$gyCards[$gi]->removed) $gyMZs[] = $gravZone . "-" . $gi;
        }
        if(count($gyMZs) >= 2) {
            $gyStr = implode("&", $gyMZs);
            DecisionQueueController::AddDecision($player, "MZCHOOSE", $gyStr, 1, "Choose_first_card_to_banish_(Keep)");
            DecisionQueueController::AddDecision($player, "CUSTOM", "KeepGoldenSashesBanish1|" . $fieldIdx, 1);
        }
    } else {
        // Player declined — sacrifice the Keep
        DoSacrificeFighter($player, "myField-" . $fieldIdx);
        DecisionQueueController::CleanupRemovedCards();
    }
};

$customDQHandlers["KeepGoldenSashesBanish1"] = function($player, $parts, $lastDecision) {
    $fieldIdx = intval($parts[0]);
    if($lastDecision === "-" || $lastDecision === "") return;
    MZMove($player, $lastDecision, "myBanish");
    global $playerID;
    $gravZone = $player == $playerID ? "myGraveyard" : "theirGraveyard";
    $gyCards = GetZone($gravZone);
    $gyMZs = [];
    for($gi = 0; $gi < count($gyCards); ++$gi) {
        if(!$gyCards[$gi]->removed) $gyMZs[] = $gravZone . "-" . $gi;
    }
    if(!empty($gyMZs)) {
        $gyStr = implode("&", $gyMZs);
        DecisionQueueController::AddDecision($player, "MZCHOOSE", $gyStr, 1, "Choose_second_card_to_banish_(Keep)");
        DecisionQueueController::AddDecision($player, "CUSTOM", "KeepGoldenSashesBanish2", 1);
    }
};

$customDQHandlers["KeepGoldenSashesBanish2"] = function($player, $parts, $lastDecision) {
    if($lastDecision === "-" || $lastDecision === "") return;
    MZMove($player, $lastDecision, "myBanish");
};

// --- Relic of Dancing Embers (i8g5013x9j): sacrifice to deal 3 damage (after fire ally hit champion) ---
$customDQHandlers["RelicDancingEmbers"] = function($player, $parts, $lastDecision) {
    // $parts[0] = field index of Relic
    // $parts[1] = defender player number
    if($lastDecision !== "YES") return;
    $relicIdx = intval($parts[0]);
    $defenderPlayer = intval($parts[1]);
    $field = &GetField($player);
    if(isset($field[$relicIdx]) && !$field[$relicIdx]->removed && $field[$relicIdx]->CardID === "i8g5013x9j") {
        DoSacrificeFighter($player, "myField-" . $relicIdx);
        DecisionQueueController::CleanupRemovedCards();
        DealChampionDamage($defenderPlayer, 3);
    }
};

// --- Soothing Disillusion (geq18a4f2h): choose mode handler ---
$customDQHandlers["SoothingDisillusionMode"] = function($player, $parts, $lastDecision) {
    if($lastDecision === "YES") {
        // Mode 1: Destroy target phantasia
        $phantasias = array_merge(
            ZoneSearch("myField", ["PHANTASIA"]),
            ZoneSearch("theirField", ["PHANTASIA"])
        );
        $phantasias = FilterSpellshroudTargets($phantasias);
        if(!empty($phantasias)) {
            $pStr = implode("&", $phantasias);
            DecisionQueueController::AddDecision($player, "MZCHOOSE", $pStr, 1, "Choose_phantasia_to_destroy");
            DecisionQueueController::AddDecision($player, "CUSTOM", "SoothingDisillusionDestroy", 1);
        }
    } else {
        // Mode 2: Put buff counter on target Animal or Beast ally
        $allies = ZoneSearch("myField", ["ALLY"], cardSubtypes: ["ANIMAL", "BEAST"]);
        $theirAllies = ZoneSearch("theirField", ["ALLY"], cardSubtypes: ["ANIMAL", "BEAST"]);
        $targets = array_merge($allies, $theirAllies);
        $targets = FilterSpellshroudTargets($targets);
        if(!empty($targets)) {
            $tStr = implode("&", $targets);
            DecisionQueueController::AddDecision($player, "MZCHOOSE", $tStr, 1, "Choose_Animal/Beast_ally_for_buff_counter");
            DecisionQueueController::AddDecision($player, "CUSTOM", "SoothingDisillusionBuff", 1);
        }
    }
};

$customDQHandlers["SoothingDisillusionDestroy"] = function($player, $parts, $lastDecision) {
    if($lastDecision === "-" || $lastDecision === "") return;
    DoAllyDestroyed($player, $lastDecision);
};

$customDQHandlers["SoothingDisillusionBuff"] = function($player, $parts, $lastDecision) {
    if($lastDecision === "-" || $lastDecision === "") return;
    AddCounters($player, $lastDecision, "buff", 1);
};

// --- Cone of Frost (i7sbjy86ep): step-based handler for level-conditional damage ---
$customDQHandlers["ConeOfFrostStep"] = function($player, $parts, $lastDecision) {    // $parts[0] = step (1, 2, or 3), $parts[1] = source mzID
    $step = intval($parts[0]);
    $sourceMZ = $parts[1];
    $level = PlayerLevel($player);

    if($step == 1) {
        // Step 1: present first target choice (Level 1+)
        $targets = array_merge(
            ZoneSearch("myField", ["ALLY", "CHAMPION"]),
            ZoneSearch("theirField", ["ALLY", "CHAMPION"])
        );
        $targets = FilterSpellshroudTargets($targets);
        if(!empty($targets)) {
            $tStr = implode("&", $targets);
            DecisionQueueController::AddDecision($player, "MZMAYCHOOSE", $tStr, 1, "Deal_2_damage_to_target_(Cone_of_Frost_1)");
            DecisionQueueController::AddDecision($player, "CUSTOM", "ConeOfFrostStep|2|" . $sourceMZ, 1);
        }
    } elseif($step == 2) {
        // Process first target choice
        if($lastDecision !== "-" && $lastDecision !== "") {
            DealDamage($player, $sourceMZ, $lastDecision, 2);
        }
        // Step 2: present second target choice (Level 3+)
        if($level >= 3) {
            $targets = array_merge(
                ZoneSearch("myField", ["ALLY", "CHAMPION"]),
                ZoneSearch("theirField", ["ALLY", "CHAMPION"])
            );
            $targets = FilterSpellshroudTargets($targets);
            if(!empty($targets)) {
                $tStr = implode("&", $targets);
                DecisionQueueController::AddDecision($player, "MZMAYCHOOSE", $tStr, 1, "Deal_2_damage_to_target_(Cone_of_Frost_2)");
                DecisionQueueController::AddDecision($player, "CUSTOM", "ConeOfFrostStep|3|" . $sourceMZ, 1);
            }
        }
    } elseif($step == 3) {
        // Process second target choice
        if($lastDecision !== "-" && $lastDecision !== "") {
            DealDamage($player, $sourceMZ, $lastDecision, 2);
        }
        // Step 3: present third target choice (Level 5+)
        if($level >= 5) {
            $targets = array_merge(
                ZoneSearch("myField", ["ALLY", "CHAMPION"]),
                ZoneSearch("theirField", ["ALLY", "CHAMPION"])
            );
            $targets = FilterSpellshroudTargets($targets);
            if(!empty($targets)) {
                $tStr = implode("&", $targets);
                DecisionQueueController::AddDecision($player, "MZMAYCHOOSE", $tStr, 1, "Deal_2_damage_to_target_(Cone_of_Frost_3)");
                DecisionQueueController::AddDecision($player, "CUSTOM", "ConeOfFrostStep|4|" . $sourceMZ, 1);
            }
        }
    } elseif($step == 4) {
        // Process third target choice
        if($lastDecision !== "-" && $lastDecision !== "") {
            DealDamage($player, $sourceMZ, $lastDecision, 2);
        }
    }
};

// --- Fiery Swing (ijkyboiopv): recursive banish handler ---
$customDQHandlers["FierySwingBanish"] = function($player, $parts, $lastDecision) {
    // $parts[0] = source mzID, $parts[1] = count banished so far
    $sourceMZ = $parts[0];
    $count = intval($parts[1]);

    // Process previous choice
    if($lastDecision !== "-" && $lastDecision !== "") {
        MZMove($player, $lastDecision, "myBanish");
        $count++;
    }

    // Check if we can banish more (max 6)
    if($count < 6) {
        $fireGY = ZoneSearch("myGraveyard", cardElements: ["FIRE"]);
        if(!empty($fireGY)) {
            $fireStr = implode("&", $fireGY);
            DecisionQueueController::AddDecision($player, "MZMAYCHOOSE", $fireStr, 1, "Banish_fire_card_from_GY?_(" . $count . "/6_banished)");
            DecisionQueueController::AddDecision($player, "CUSTOM", "FierySwingBanish|" . $sourceMZ . "|" . $count, 1);
            return;
        }
    }

    // Done banishing — apply power bonus
    if($count > 0) {
        AddTurnEffect($sourceMZ, "ijkyboiopv-" . $count);
    }
};

// --- Force Load: step 1 — store bullet choice, then choose gun ---
$customDQHandlers["ForceLoadStoreBullet"] = function($player, $parts, $lastDecision) {
    if($lastDecision === "-" || $lastDecision === "") return;
    $guns = GetUnloadedGuns($player);
    if(empty($guns)) return;
    $gunStr = implode("&", $guns);
    DecisionQueueController::AddDecision($player, "MZCHOOSE", $gunStr, 1, "Choose_Gun_to_load");
    DecisionQueueController::AddDecision($player, "CUSTOM", "ForceLoadChooseGun|" . $lastDecision, 1);
};

// --- Force Load: step 2 — materialize bullet to field, load into gun ---
$customDQHandlers["ForceLoadChooseGun"] = function($player, $parts, $lastDecision) {
    // $parts[0] = mzID of the chosen bullet from material deck
    // $lastDecision = mzID of the chosen gun
    $bulletMZ = $parts[0];
    if($lastDecision === "-" || $lastDecision === "") return;
    // Materialize the bullet to field first (paying 0 cost since it's memory cost 0)
    $bulletObj = MZMove($player, $bulletMZ, "myField");
    $bulletObj->Controller = $player;
    DecisionQueueController::CleanupRemovedCards();
    // Now find the bullet on the field and load it into the gun
    $field = GetZone("myField");
    $bulletFieldMZ = null;
    for($i = count($field) - 1; $i >= 0; --$i) {
        if(!$field[$i]->removed && $field[$i] === $bulletObj) {
            $bulletFieldMZ = "myField-" . $i;
            break;
        }
    }
    if($bulletFieldMZ !== null) {
        LoadBulletIntoGun($player, $bulletFieldMZ, $lastDecision);
    }
};

// --- Refresh Chamber: after materialize, may banish floating from GY to put card in memory ---
$customDQHandlers["RefreshChamberBanish"] = function($player, $parts, $lastDecision) {
    if($lastDecision === "-" || $lastDecision === "") return;
    // Banish the floating memory card from graveyard
    MZMove($player, $lastDecision, "myBanish");
    // Find Refresh Chamber on the effect stack or graveyard (it just resolved to GY)
    // and move it to memory
    $gy = GetZone("myGraveyard");
    for($i = count($gy) - 1; $i >= 0; --$i) {
        if(!$gy[$i]->removed && $gy[$i]->CardID === "7nk45swaf8") {
            MZMove($player, "myGraveyard-" . $i, "myMemory");
            break;
        }
    }
};

// --- Load Soul: after materialize, may add durability to Gun and put card on lineage ---
$customDQHandlers["LoadSoulDurability"] = function($player, $parts, $lastDecision) {
    if($lastDecision === "-" || $lastDecision === "") return;
    // $lastDecision = mzID of the chosen Gun
    AddCounters($player, $lastDecision, "durability", 2);
    // Put Load Soul on bottom of champion's lineage
    $field = &GetField($player);
    for($i = 0; $i < count($field); ++$i) {
        if(!$field[$i]->removed && PropertyContains(EffectiveCardType($field[$i]), "CHAMPION") && $field[$i]->Controller == $player) {
            if(!is_array($field[$i]->Subcards)) $field[$i]->Subcards = [];
            $field[$i]->Subcards[] = "8tuhuy4xip"; // Load Soul CardID
            break;
        }
    }
    // Remove Load Soul from graveyard (it was just activated and resolved)
    $gy = GetZone("myGraveyard");
    for($gi = count($gy) - 1; $gi >= 0; --$gi) {
        if(!$gy[$gi]->removed && $gy[$gi]->CardID === "8tuhuy4xip") {
            MZRemove($player, "myGraveyard-" . $gi);
            DecisionQueueController::CleanupRemovedCards();
            break;
        }
    }
};

// --- Deploy Gunshield: target Gun gains spellshroud until EOT ---
$customDQHandlers["DeployGunshieldTarget"] = function($player, $parts, $lastDecision) {
    if($lastDecision === "-" || $lastDecision === "") return;
    AddTurnEffect($lastDecision, "SPELLSHROUD");
};

// --- Seeker's Rifle: On Kill pay (2) to materialize a Bullet ---
$customDQHandlers["SeekersRifleOnKill"] = function($player, $parts, $lastDecision) {
    if($lastDecision !== "YES") return;
    // Pay 2 reserve (move 2 cards from hand to memory)
    $hand = GetZone("myHand");
    if(count($hand) < 2) return;
    DecisionQueueController::AddDecision($player, "MZCHOOSE", implode("&", array_map(function($i) { return "myHand-" . $i; }, range(0, count($hand) - 1))), 1, "Pay_reserve_(1/2)");
    DecisionQueueController::AddDecision($player, "CUSTOM", "SeekersRiflePayReserve|1", 1);
};

$customDQHandlers["SeekersRiflePayReserve"] = function($player, $parts, $lastDecision) {
    $payNum = intval($parts[0]);
    if($lastDecision === "-" || $lastDecision === "") return;
    MZMove($player, $lastDecision, "myMemory");
    if($payNum < 2) {
        $hand = GetZone("myHand");
        if(!empty($hand)) {
            DecisionQueueController::AddDecision($player, "MZCHOOSE", implode("&", array_map(function($i) { return "myHand-" . $i; }, range(0, count($hand) - 1))), 1, "Pay_reserve_(2/2)");
            DecisionQueueController::AddDecision($player, "CUSTOM", "SeekersRiflePayReserve|2", 1);
        }
    } else {
        // Payment complete — materialize a Bullet
        SupplyDroneMaterialize($player);
    }
};

// --- Mindbreak Bullet: CB On Champion Hit — look at memory, discard a card ---
$customDQHandlers["MindbreakBulletDiscard"] = function($player, $parts, $lastDecision) {
    if($lastDecision === "-" || $lastDecision === "") return;
    // Discard the chosen card from opponent's memory
    MZMove($player, $lastDecision, "theirGraveyard");
};

// --- Freezing Round: On Champion Hit — banish random from memory, return at next end phase ---
$customDQHandlers["FreezingRoundReturn"] = function($player, $parts, $lastDecision) {
    // $parts[0] = cardID of the banished card, $parts[1] = opponent player number
    $cardID = $parts[0];
    $opponent = intval($parts[1]);
    // Find the banished card with FREEZING_ROUND_RETURN TurnEffect and return to memory
    $banishZone = $opponent == $player ? "myBanish" : "theirBanish";
    $memoryZone = $opponent == $player ? "myMemory" : "theirMemory";
    $zone = GetZone($banishZone);
    for($i = 0; $i < count($zone); ++$i) {
        if(!$zone[$i]->removed && $zone[$i]->CardID === $cardID && in_array("FREEZING_ROUND_RETURN", $zone[$i]->TurnEffects)) {
            $zone[$i]->TurnEffects = array_values(array_diff($zone[$i]->TurnEffects, ["FREEZING_ROUND_RETURN"]));
            MZMove($player, $banishZone . "-" . $i, $memoryZone);
            break;
        }
    }
};

// --- Turbulent Bullet: CB On Hit — up to 2 allies get +1 power ---
$customDQHandlers["TurbulentBulletBuff"] = function($player, $parts, $lastDecision) {
    $remaining = intval($parts[0]);
    if($lastDecision === "-" || $lastDecision === "" || $remaining <= 0) return;
    AddTurnEffect($lastDecision, "f8urrqtjot"); // Turbulent Bullet effect ID
    if($remaining > 1) {
        $allies = ZoneSearch("myField", ["ALLY"]);
        if(!empty($allies)) {
            DecisionQueueController::AddDecision($player, "MZMAYCHOOSE", implode("&", $allies), 1, "Choose_another_ally_for_+1_power?");
            DecisionQueueController::AddDecision($player, "CUSTOM", "TurbulentBulletBuff|0", 1);
        }
    }
};

// --- Supply Drone: materialize a 0-cost Bullet from material deck ---
function SupplyDroneMaterialize($player) {
    $materialZone = GetZone("myMaterial");
    $bulletMZs = [];
    for($i = 0; $i < count($materialZone); ++$i) {
        $obj = $materialZone[$i];
        if(!$obj->removed && PropertyContains(CardSubtypes($obj->CardID), "BULLET") && CardMemoryCost($obj) == 0) {
            $bulletMZs[] = "myMaterial-" . $i;
        }
    }
    if(empty($bulletMZs)) return;
    // The player materializes a 0-cost Bullet (pays memory cost which is 0)
    DecisionQueueController::AddDecision($player, "MZCHOOSE", implode("&", $bulletMZs), 1, "Materialize_a_0-cost_Bullet");
    DecisionQueueController::AddDecision($player, "CUSTOM", "MATERIALIZE", 1);
}

// Mad Hatter, Morose Heritor (2nc48s3oqh): materialize a Ranger regalia from material deck
function MadHatterMaterialize($player) {
    $materialZone = GetZone("myMaterial");
    $regalias = [];
    for($i = 0; $i < count($materialZone); ++$i) {
        $obj = $materialZone[$i];
        if(!$obj->removed && PropertyContains(CardType($obj->CardID), "REGALIA")
           && PropertyContains(CardClasses($obj->CardID), "RANGER")) {
            $regalias[] = "myMaterial-" . $i;
        }
    }
    if(empty($regalias)) return;
    DecisionQueueController::AddDecision($player, "MZCHOOSE", implode("&", $regalias), 1,
        tooltip:"Choose_a_Ranger_regalia_to_materialize");
    DecisionQueueController::AddDecision($player, "CUSTOM", "MATERIALIZE", 1);
}

// ============================================================================
// Celestial Calling (izm6h38lrj): reveal from deck, banish astra Spell, free activate next recollection
// ============================================================================

/**
 * Reveal cards from the top of the deck until an astra Spell is found.
 * Banish that card (tagged with CELESTIAL_CALLING TurnEffect), put the rest on bottom in random order.
 */
function CelestialCallingReveal($player) {
    $deck = &GetDeck($player);
    $foundIndex = -1;
    // Reveal cards top-down until we find an astra Spell
    for($i = 0; $i < count($deck); ++$i) {
        $cardID = $deck[$i]->CardID;
        if(CardElement($cardID) === "ASTRA" && PropertyContains(CardSubtypes($cardID), "SPELL")) {
            $foundIndex = $i;
            break;
        }
    }
    if($foundIndex < 0) {
        // No astra Spell found — put all revealed cards on bottom in random order
        // (all cards were "revealed" conceptually, but deck stays intact)
        return;
    }

    // Remove the found card and all cards above it from the deck
    $revealed = [];
    for($i = 0; $i <= $foundIndex; ++$i) {
        $revealed[] = array_shift($deck);
    }

    // Show the found card as a reveal
    $foundCard = $revealed[$foundIndex];
    SetFlashMessage('REVEAL:' . $foundCard->CardID);

    // Banish the found astra Spell, tag it for free activation next recollection
    $banishObj = AddBanish($player, $foundCard->CardID);
    $banishObj->AddTurnEffects("CELESTIAL_CALLING");

    // Put remaining revealed cards on the bottom of the deck in random order
    $remaining = [];
    for($i = 0; $i < count($revealed); ++$i) {
        if($i === $foundIndex) continue;
        $remaining[] = $revealed[$i];
    }
    shuffle($remaining);
    foreach($remaining as $cardObj) {
        array_push($deck, $cardObj);
    }
}

/**
 * Process Celestial Calling trigger during RecollectionPhase.
 * Scans banishment for a card with CELESTIAL_CALLING TurnEffect and offers free activation.
 */
function CelestialCallingRecollectionCheck($player) {
    $banish = GetZone("myBanish");
    for($i = 0; $i < count($banish); ++$i) {
        if(!$banish[$i]->removed && in_array("CELESTIAL_CALLING", $banish[$i]->TurnEffects)) {
            $mz = "myBanish-" . $i;
            DecisionQueueController::AddDecision($player, "YESNO", "-", 1,
                tooltip:"Activate_" . CardName($banish[$i]->CardID) . "_for_free?");
            DecisionQueueController::AddDecision($player, "CUSTOM", "CelestialCallingActivate|$mz|" . $banish[$i]->CardID, 1);
            return; // Only one at a time
        }
    }
}

$customDQHandlers["CelestialCallingActivate"] = function($player, $parts, $lastDecision) {
    $targetMZ = $parts[0];
    $targetCardID = $parts[1];
    // Clear the tag regardless of choice
    $banish = GetZone("myBanish");
    for($i = 0; $i < count($banish); ++$i) {
        if(!$banish[$i]->removed && in_array("CELESTIAL_CALLING", $banish[$i]->TurnEffects)) {
            $banish[$i]->TurnEffects = array_values(array_diff($banish[$i]->TurnEffects, ["CELESTIAL_CALLING"]));
            break;
        }
    }
    if($lastDecision !== "YES") return;

    // Find the card in banishment (index may have shifted)
    $banish = GetZone("myBanish");
    $actualMZ = null;
    for($i = 0; $i < count($banish); ++$i) {
        if(!$banish[$i]->removed && $banish[$i]->CardID === $targetCardID) {
            $actualMZ = "myBanish-" . $i;
            break;
        }
    }
    if($actualMZ === null) return;

    // Move to hand then activate for free (ignoreCost = true)
    $handObj = MZMove($player, $actualMZ, "myHand");
    DecisionQueueController::CleanupRemovedCards();
    // Find in hand
    $hand = GetZone("myHand");
    $handMZ = null;
    for($hi = count($hand) - 1; $hi >= 0; --$hi) {
        if(!$hand[$hi]->removed && $hand[$hi]->CardID === $targetCardID) {
            $handMZ = "myHand-" . $hi;
            break;
        }
    }
    if($handMZ === null) return;
    ActivateCard($player, $handMZ, true);
};

// ============================================================================
// Scry the Stars (oz23yfzk96): CB alt-cost banish Scry the Skies from GY
// ============================================================================

$customDQHandlers["PrimordialRitualSacrifice"] = function($player, $parts, $lastDecision) {
    if($lastDecision && $lastDecision !== "-") {
        DoSacrificeFighter($player, $lastDecision);
        DecisionQueueController::CleanupRemovedCards();
    }
};

// Sly Songstress (f28y5rn0dt): whenever you activate Harmony/Melody, may discard→draw
$customDQHandlers["SlySongstressDiscard"] = function($player, $parts, $lastDecision) {
    $fieldIdx = intval($parts[0]);
    if($lastDecision !== "YES") return;
    $field = &GetField($player);
    if(!isset($field[$fieldIdx]) || $field[$fieldIdx]->removed || $field[$fieldIdx]->CardID !== "f28y5rn0dt") return;
    $hand = &GetHand($player);
    if(count($hand) == 0) return;
    $handCards = [];
    for($i = 0; $i < count($hand); ++$i) {
        if(!$hand[$i]->removed) $handCards[] = "myHand-" . $i;
    }
    if(empty($handCards)) return;
    $handStr = implode("&", $handCards);
    DecisionQueueController::AddDecision($player, "MZCHOOSE", $handStr, 1, "Discard_a_card");
    DecisionQueueController::AddDecision($player, "CUSTOM", "SlySongstressDrawAfterDiscard", 1);
};

$customDQHandlers["SlySongstressDrawAfterDiscard"] = function($player, $parts, $lastDecision) {
    if($lastDecision && $lastDecision !== "-") {
        DoDiscardCard($player, $lastDecision);
        Draw($player, 1);
    }
};

$customDQHandlers["SquallbindPounceDistant"] = function($player, $parts, $lastDecision) {
    if($lastDecision !== "-") {
        BecomeDistant($player, $lastDecision);
    }
};

$customDQHandlers["SquallbindPounceSuppress"] = function($player, $parts, $lastDecision) {
    if($lastDecision !== "-") {
        SuppressAlly($player, $lastDecision);
    }
};

$customDQHandlers["WinblessHurricaneFarmReveal"] = function($player, $parts, $lastDecision) {
    if($lastDecision === "YES") {
        $windMem = ZoneSearch("myMemory", cardElements: ["WIND"]);
        // Reveal 3 wind cards from memory
        $revealIDs = [];
        for($ri = 0; $ri < min(3, count($windMem)); ++$ri) {
            $rObj = GetZoneObject($windMem[$ri]);
            if($rObj !== null) $revealIDs[] = $rObj->CardID;
        }
        if(!empty($revealIDs)) {
            SetFlashMessage('REVEAL:' . implode('|', $revealIDs));
        }
        // Summon a Powercell token
        MZAddZone($player, "myField", "qzzadf9q1v");
    }
};

$customDQHandlers["ScryTheStarsAltCost"] = function($player, $parts, $lastDecision) {
    $reserveCost = intval($parts[0]);
    if($lastDecision === "YES") {
        // Banish Scry the Skies (F9POfB5Nah) from graveyard
        $gy = GetZone("myGraveyard");
        for($i = count($gy) - 1; $i >= 0; --$i) {
            if(!$gy[$i]->removed && $gy[$i]->CardID === "F9POfB5Nah") {
                MZMove($player, "myGraveyard-" . $i, "myBanish");
                break;
            }
        }
    } else {
        // Pay normal reserve cost
        for($i = 0; $i < $reserveCost; ++$i) {
            DecisionQueueController::AddDecision($player, "CUSTOM", "ReserveCard", 100);
        }
    }
    DecisionQueueController::StoreVariable("additionalCostPaid", "NO");
    DecisionQueueController::AddDecision($player, "CUSTOM", "EffectStackOpportunity", 100);
};

// ============================================================================
// Nico, Whiplash Allure (5bbae3z4py) + Magebane Lash (oh300z2sns) helpers
// ============================================================================

// --- Imperial Apprentice (u6o6eanbrf): may banish floating memory from GY to draw ---
$customDQHandlers["ImperialApprenticeFloating"] = function($player, $parts, $lastDecision) {
    if($lastDecision === "-" || $lastDecision === "" || $lastDecision === "PASS") return;
    MZMove($player, $lastDecision, "myBanish");
    Draw($player, 1);
};

// --- Cell Assembler (vc8sugly4w): summon Powercell rested after paying (2) ---
$customDQHandlers["CellAssemblerSummon"] = function($player, $parts, $lastDecision) {
    $pcObj = MZAddZone($player, "myField", "qzzadf9q1v");
    $pcObj->Status = 1; // rested
};

// --- Tonic of Remembrance (uqrptjej4m): return a card from memory to hand ---
$customDQHandlers["TonicOfRemembranceReturn"] = function($player, $parts, $lastDecision) {
    if($lastDecision === "-" || $lastDecision === "" || $lastDecision === "PASS") return;
    MZMove($player, $lastDecision, "myHand");
};

// Krustallan Ruins (fei7chsbal): pay (1) or rest the entering ally
$customDQHandlers["KrustallanRuinsPayOrRest"] = function($player, $parts, $lastDecision) {
    $fieldIdx = intval($parts[0]);
    $field = &GetField($player);
    if(!isset($field[$fieldIdx]) || $field[$fieldIdx]->removed) return;
    if($lastDecision === "YES") {
        // Pay (1): move a card from hand to memory
        DecisionQueueController::AddDecision($player, "CUSTOM", "ReserveCard", 100);
    } else {
        // Rest the ally
        $field[$fieldIdx]->Status = 1;
    }
};

// --- Fractal of Rain (3zb9p4lgdl): target player mills 1 ---
$customDQHandlers["FractalOfRainMill"] = function($player, $parts, $lastDecision) {
    if($lastDecision === "-" || $lastDecision === "" || $lastDecision === "PASS") return;
    // Determine which player owns the chosen champion
    $targetObj = GetZoneObject($lastDecision);
    if($targetObj === null) return;
    $targetPlayer = $targetObj->Controller;
    global $playerID;
    $deckRef = ($targetPlayer == $playerID) ? "myDeck" : "theirDeck";
    $gyRef = ($targetPlayer == $playerID) ? "myGraveyard" : "theirGraveyard";
    MillCards($player, $deckRef, $gyRef, 1);
};

// Suffocating Miasma recollection: debuff chosen ally or deal 2 unpreventable to champion
$customDQHandlers["SuffocatingMiasmaRecollection"] = function($player, $parts, $lastDecision) {
    if($lastDecision !== "-" && $lastDecision !== "" && $lastDecision !== "PASS") {
        AddCounters($player, $lastDecision, "debuff", 1);
    } else {
        $champMZ = FindChampionMZ($player);
        if($champMZ !== null) {
            DealUnpreventableDamage($player, $champMZ, $champMZ, 2);
        }
    }
};

// Clockwork Musicbox: banish just-activated Harmony/Melody and tag it
$customDQHandlers["MusicboxBanish"] = function($player, $parts, $lastDecision) {
    if($lastDecision !== "YES") return;
    $cardID = DecisionQueueController::GetVariable("MusicboxBanishCardID");
    if(empty($cardID)) return;
    // Find the card — it could be in graveyard (ACTION) or on field (ALLY/PHANTASIA)
    $gy = GetZone("myGraveyard");
    for($i = count($gy) - 1; $i >= 0; --$i) {
        if(!$gy[$i]->removed && $gy[$i]->CardID === $cardID) {
            $banished = MZMove($player, "myGraveyard-" . $i, "myBanish");
            if($banished !== null) $banished->Counters['_musicbox'] = 1;
            return;
        }
    }
    $field = GetZone("myField");
    $musicboxIdx = intval(DecisionQueueController::GetVariable("MusicboxFieldIdx") ?? "-1");
    for($i = count($field) - 1; $i >= 0; --$i) {
        if(!$field[$i]->removed && $field[$i]->CardID === $cardID && $i !== $musicboxIdx) {
            $banished = MZMove($player, "myField-" . $i, "myBanish");
            if($banished !== null) $banished->Counters['_musicbox'] = 1;
            DecisionQueueController::CleanupRemovedCards();
            return;
        }
    }
};

// Clockwork Musicbox REST: activate a card banished by Musicbox
$customDQHandlers["MusicboxActivate"] = function($player, $parts, $lastDecision) {
    if($lastDecision === "-" || $lastDecision === "" || $lastDecision === "PASS") return;
    MusicboxActivateCard($player, $lastDecision);
};

function MusicboxActivateCard($player, $banishedMZ) {
    $handObj = MZMove($player, $banishedMZ, "myHand");
    $hand = &GetHand($player);
    $handIdx = count($hand) - 1;
    ActivateCard($player, "myHand-" . $handIdx, false);
}

// Sinister Mindreaver: continue after first memory pick
function SinisterMindreaverContinue($player, $pick1) {
    if($pick1 === "-" || $pick1 === "" || $pick1 === "PASS") return;
    MZMove($player, $pick1, "theirGraveyard");
    $oppMemory2 = ZoneSearch("theirMemory");
    if(!empty($oppMemory2)) {
        DecisionQueueController::AddDecision($player, "MZMAYCHOOSE", implode("&", $oppMemory2), 1, "Discard_another_from_memory?");
        DecisionQueueController::AddDecision($player, "CUSTOM", "SinisterMindreaverPick2", 1);
    } else {
        $opponent = ($player == 1) ? 2 : 1;
        DrawIntoMemory($opponent, 1);
    }
}

$customDQHandlers["SinisterMindreaverPick2"] = function($player, $parts, $lastDecision) {
    $opponent = ($player == 1) ? 2 : 1;
    if($lastDecision !== "-" && $lastDecision !== "" && $lastDecision !== "PASS") {
        MZMove($player, $lastDecision, "theirGraveyard");
        DrawIntoMemory($opponent, 2);
    } else {
        DrawIntoMemory($opponent, 1);
    }
};

// Loaded Thoughts (hh88rx6p3p): mill top card of deck when gun becomes loaded
$customDQHandlers["LoadedThoughtsMill"] = function($player, $parts, $lastDecision) {
    if($lastDecision !== "YES") return;
    $deck = GetDeck($player);
    if(empty($deck)) return;
    MZMove($player, "myDeck-0", "myGraveyard");
};

$customDQHandlers["NefariousTimepieceChoose"] = function($player, $parts, $lastDecision) {
    if($lastDecision === "-" || $lastDecision === "" || $lastDecision === "PASS") return;
    $timepieceMZ = DecisionQueueController::GetVariable("NefariousTimepieceMZ");
    if(empty($timepieceMZ)) return;
    $timepieceObj = GetZoneObject($timepieceMZ);
    $chosenObj = GetZoneObject($lastDecision);
    if($timepieceObj === null || $chosenObj === null) return;
    $effects = $timepieceObj->TurnEffects ?? [];
    $timepieceObj->TurnEffects = array_values(array_filter($effects, function($effect) {
        return strpos($effect, "h1njd7z5j3-") !== 0;
    }));
    AddTurnEffect($timepieceMZ, "h1njd7z5j3-" . $chosenObj->CardID);
};

$customDQHandlers["EchoicGuardTarget"] = function($player, $parts, $lastDecision) {
    if($lastDecision === "-" || $lastDecision === "" || $lastDecision === "PASS") return;
    $targetObj = GetZoneObject($lastDecision);
    if($targetObj === null) return;
    AddTurnEffect($lastDecision, "PREVENT_ALL_2");
    $reserveCost = intval(CardCost_reserve($targetObj->CardID) ?? 0);
    if($reserveCost <= 0) {
        MZAddZone($player, "myField", $targetObj->CardID);
        return;
    }
    if(CountReserveSources($player) < $reserveCost) return;
    DecisionQueueController::StoreVariable("EchoicGuardCopyCardID", $targetObj->CardID);
    DecisionQueueController::StoreVariable("EchoicGuardCopyCost", strval($reserveCost));
    DecisionQueueController::AddDecision($player, "YESNO", "-", 1, tooltip:"Pay_" . $reserveCost . "_to_summon_a_token_copy?");
    DecisionQueueController::AddDecision($player, "CUSTOM", "EchoicGuardPay", 1);
};

$customDQHandlers["EchoicGuardPay"] = function($player, $parts, $lastDecision) {
    if($lastDecision !== "YES") return;
    $reserveCost = intval(DecisionQueueController::GetVariable("EchoicGuardCopyCost") ?? "0");
    for($i = 0; $i < $reserveCost; ++$i) ReserveCard($player);
    DecisionQueueController::AddDecision($player, "CUSTOM", "EchoicGuardCopy", 105);
};

$customDQHandlers["EchoicGuardCopy"] = function($player, $parts, $lastDecision) {
    $cardID = DecisionQueueController::GetVariable("EchoicGuardCopyCardID");
    if(!empty($cardID)) MZAddZone($player, "myField", $cardID);
};

$customDQHandlers["ShiftingMiragePay"] = function($player, $parts, $lastDecision) {
    if($lastDecision === "YES") {
        ReserveCard($player);
        ReserveCard($player);
        return;
    }
    $champMZ = DecisionQueueController::GetVariable("ShiftingMirageChampion");
    if(!empty($champMZ)) AddTurnEffect($champMZ, "STEALTH");
};

$customDQHandlers["GloamspireHeadhunterPay"] = function($player, $parts, $lastDecision) {
    $target = DecisionQueueController::GetVariable("GloamspireHeadhunterTarget");
    if($lastDecision === "YES") {
        if(count(GetHand($player)) < 3) {
            if(!empty($target)) {
                DoAllyDestroyed($player, $target);
                DecisionQueueController::CleanupRemovedCards();
            }
            return;
        }
        ReserveCard($player);
        ReserveCard($player);
        ReserveCard($player);
        return;
    }
    if(!empty($target)) {
        DoAllyDestroyed($player, $target);
        DecisionQueueController::CleanupRemovedCards();
    }
};

function RipplesOfAtrophyResolve($player) {
    $allObjects = array_merge(
        ZoneSearch("myField", ["ALLY", "REGALIA"]),
        ZoneSearch("myField", cardSubtypes: ["WEAPON"]),
        ZoneSearch("theirField", ["ALLY", "REGALIA"]),
        ZoneSearch("theirField", cardSubtypes: ["WEAPON"])
    );
    $allObjects = FilterSpellshroudTargets($allObjects);
    $allObjects = array_values(array_filter($allObjects, function($mz) {
        $obj = GetZoneObject($mz);
        return $obj !== null && !PropertyContains(EffectiveCardType($obj), "CHAMPION");
    }));
    if(empty($allObjects)) return;
    DecisionQueueController::StoreVariable("RipplesOfAtrophyChosen", "");
    DecisionQueueController::AddDecision($player, "MZMAYCHOOSE", implode("&", $allObjects), 1,
        tooltip:"Choose_object_to_put_two_wither_counters_on");
    DecisionQueueController::AddDecision($player, "CUSTOM", "RipplesOfAtrophyLoop", 1);
}

$customDQHandlers["RipplesOfAtrophyLoop"] = function($player, $parts, $lastDecision) {
    if($lastDecision === "-" || $lastDecision === "" || $lastDecision === "PASS") return;
    AddCounters($player, $lastDecision, "wither", 2);
    $chosenRaw = DecisionQueueController::GetVariable("RipplesOfAtrophyChosen");
    $chosen = $chosenRaw === null || $chosenRaw === "" ? [] : explode("|", $chosenRaw);
    $chosen[] = $lastDecision;
    DecisionQueueController::StoreVariable("RipplesOfAtrophyChosen", implode("|", $chosen));
    $allObjects = array_merge(
        ZoneSearch("myField", ["ALLY", "REGALIA"]),
        ZoneSearch("myField", cardSubtypes: ["WEAPON"]),
        ZoneSearch("theirField", ["ALLY", "REGALIA"]),
        ZoneSearch("theirField", cardSubtypes: ["WEAPON"])
    );
    $allObjects = FilterSpellshroudTargets($allObjects);
    $allObjects = array_values(array_filter($allObjects, function($mz) use ($chosen) {
        $obj = GetZoneObject($mz);
        if($obj === null || PropertyContains(EffectiveCardType($obj), "CHAMPION")) return false;
        return !in_array($mz, $chosen);
    }));
    if(empty($allObjects)) return;
    DecisionQueueController::AddDecision($player, "MZMAYCHOOSE", implode("&", $allObjects), 1,
        tooltip:"Choose_another_object_to_put_two_wither_counters_on");
    DecisionQueueController::AddDecision($player, "CUSTOM", "RipplesOfAtrophyLoop", 1);
};

function GalestreamInsightPutRestOnBottom($player) {
    $remaining = ZoneSearch("myTempZone");
    foreach($remaining as $rmz) {
        MZMove($player, $rmz, "myDeck");
    }
}

function GalestreamInsightResolve($player) {
    $deck = GetDeck($player);
    $lookCount = min(6, count($deck));
    if($lookCount <= 0) return;
    for($i = 0; $i < $lookCount; ++$i) {
        MZMove($player, "myDeck-0", "myTempZone");
    }
    $candidates = ZoneSearch("myTempZone", cardSubtypes: ["SPELL"]);
    if(empty($candidates)) {
        GalestreamInsightPutRestOnBottom($player);
        return;
    }
    DecisionQueueController::AddDecision($player, "MZMAYCHOOSE", implode("&", $candidates), 1,
        tooltip:"Reveal_a_Spell_card_to_put_into_memory?");
    DecisionQueueController::AddDecision($player, "CUSTOM", "GalestreamInsightReveal", 1);
}

$customDQHandlers["GalestreamInsightReveal"] = function($player, $parts, $lastDecision) {
    if($lastDecision !== "-" && $lastDecision !== "" && $lastDecision !== "PASS") {
        Reveal($player, $lastDecision);
        MZMove($player, $lastDecision, "myMemory");
    }
    GalestreamInsightPutRestOnBottom($player);
};

function DusksoulStoneActivated($player) {
    $myGY = ZoneSearch("myGraveyard");
    $theirGY = ZoneSearch("theirGraveyard");
    if(empty($myGY) && empty($theirGY)) return;
    $targets = array_merge($myGY, $theirGY);
    DecisionQueueController::AddDecision($player, "MZMAYCHOOSE", implode("&", $targets), 1,
        tooltip:"Choose_a_card_to_banish_(1_of_up_to_2)");
    DecisionQueueController::AddDecision($player, "CUSTOM", "DusksoulStoneBanishStart", 1);
}

$customDQHandlers["DusksoulStoneBanishStart"] = function($player, $parts, $lastDecision) {
    if($lastDecision === "-" || $lastDecision === "" || $lastDecision === "PASS") return;
    $gyRef = strpos($lastDecision, "theirGraveyard-") === 0 ? "theirGraveyard" : "myGraveyard";
    MZMove($player, $lastDecision, "myBanish");
    $remaining = ZoneSearch($gyRef);
    if(empty($remaining)) return;
    DecisionQueueController::AddDecision($player, "MZMAYCHOOSE", implode("&", $remaining), 1,
        tooltip:"Choose_another_card_to_banish_from_the_same_graveyard?");
    DecisionQueueController::AddDecision($player, "CUSTOM", "DusksoulStoneBanishFinish", 1);
};

$customDQHandlers["DusksoulStoneBanishFinish"] = function($player, $parts, $lastDecision) {
    if($lastDecision === "-" || $lastDecision === "" || $lastDecision === "PASS") return;
    MZMove($player, $lastDecision, "myBanish");
};

function StandBeforeTheQueenResolve($player) {
    $targets = ZoneSearch("theirField", ["ALLY", "CHAMPION"]);
    $targets = array_merge($targets, ZoneSearch("myField", ["ALLY", "CHAMPION"]));
    $targets = FilterSpellshroudTargets($targets);
    if(empty($targets)) return;
    DecisionQueueController::AddDecision($player, "MZCHOOSE", implode("&", $targets), 1,
        tooltip:"Choose_target_unit");
    DecisionQueueController::AddDecision($player, "CUSTOM", "StandBeforeTheQueenTarget", 1);
}

$customDQHandlers["StandBeforeTheQueenTarget"] = function($player, $parts, $lastDecision) {
    if($lastDecision === "-" || $lastDecision === "" || $lastDecision === "PASS") return;
    AddTurnEffect($lastDecision, "STAND_BEFORE_THE_QUEEN_2");
    if(GetSheenCount($player) >= 8) {
        AddTurnEffect($lastDecision, "STAND_BEFORE_THE_QUEEN_STEALTH");
    }
};

$customDQHandlers["StandBeforeTheQueenPay"] = function($player, $parts, $lastDecision) {
    $target = DecisionQueueController::GetVariable("StandBeforeTheQueenTarget");
    if($lastDecision === "YES") {
        ReserveCard($player);
        ReserveCard($player);
        return;
    }
    if(!empty($target)) AddTurnEffect($target, "STEALTH");
};

$customDQHandlers["BlastshotPumpChoose"] = function($player, $parts, $lastDecision) {
    if($lastDecision === "-" || $lastDecision === "" || $lastDecision === "PASS") return;
    $damage = intval(DecisionQueueController::GetVariable("BlastshotPumpDamage") ?? "0");
    $source = DecisionQueueController::GetVariable("BlastshotPumpSource");
    if($damage > 0) DealDamage($player, $source, $lastDecision, $damage);
};

/**
 * Fire whenever a floating memory card is banished from the player's graveyard.
 * If Nico (5bbae3z4py) is on that player's field and has abilities, add a lash counter.
 */
function NicoOnFloatingMemoryBanished($player) {
    global $playerID;
    $zone = ($player == $playerID) ? "myField" : "theirField";
    $field = &GetField($player);
    $champion = GetPlayerChampion($player);
    $champMZ = $champion !== null ? $champion->GetMzID() : null;
    foreach($field as $fi => $fObj) {
        if($fObj->removed) continue;
        if($fObj->CardID === "5bbae3z4py" && !HasNoAbilities($fObj)) {
            AddCounters($player, "$zone-$fi", "lash", 1);
        }
        if($fObj->CardID === "ukurlcbgzi" && !HasNoAbilities($fObj) && $champMZ !== null) {
            DealUnpreventableDamage($player, "$zone-$fi", $champMZ, 2);
        }
    }
}

/**
 * Magebane Lash (oh300z2sns): Nico Bonus — when Nico, Whiplash Allure is the champion
 * and takes non-combat damage, recover 2 if Magebane Lash is on the field with abilities.
 */
function MagebaneNicoBonusCheck($player) {
    $field = &GetField($player);
    foreach($field as $fObj) {
        if($fObj->removed) continue;
        if($fObj->CardID === "oh300z2sns" && !HasNoAbilities($fObj)) {
            RecoverChampion($player, 2);
            return;
        }
    }
}

/**
 * Aegis of Dawn (abipl6gt7l): whenever your champion is dealt 4+ damage,
 * summon an Automaton Drone token. Only fires if Aegis of Dawn is on the field.
 */
function AegisOfDawnTrigger($player) {
    $field = &GetField($player);
    for($i = 0; $i < count($field); ++$i) {
        if(!$field[$i]->removed && $field[$i]->CardID === "abipl6gt7l" && !HasNoAbilities($field[$i])) {
            MZAddZone($player, "myField", "mu6gvnta6q"); // Automaton Drone token
        }
    }
}

// --- Backup Charger (9gv4vm4kj3): after reserve payment, summon Powercell rested + draw into memory ---
$customDQHandlers["BackupChargerEffect"] = function($player, $parts, $lastDecision) {
    MZAddZone($player, "myField", "qzzadf9q1v"); // Powercell token
    $field = &GetField($player);
    $field[count($field) - 1]->Status = 1; // Rested
    DrawIntoMemory($player, 1);
};

// --- Lunar Conduit (0yetaebjlw): deal damage = charge counters, then remove 1 charge ---
$customDQHandlers["LunarConduitDamage"] = function($player, $parts, $lastDecision) {
    $sourceMZ = $parts[0];
    if($lastDecision === "-" || $lastDecision === "") return;
    $sourceObj = GetZoneObject($sourceMZ);
    if($sourceObj === null) return;
    $chargeCount = GetCounterCount($sourceObj, "charge");
    if($chargeCount > 0) {
        DealDamage($player, $sourceMZ, $lastDecision, $chargeCount);
        RemoveCounters($player, $sourceMZ, "charge", 1);
    }
};

// Spirited Falconer: second buff counter pick after the first ally was chosen
$customDQHandlers["SpiritedFalconerBuff2"] = function($player, $parts, $lastDecision) {
    $chosen2 = $lastDecision;
    if($chosen2 !== "-" && $chosen2 !== "" && $chosen2 !== "PASS") {
        AddCounters($player, $chosen2, "buff", 1);
    }
};

// ============================================================================
// Powercell sacrifice triggers (shared by Turbo Charge, Atmos Armor, Powercell self-sacrifice)
// ============================================================================
function TriggerPowercellSacrifice($player) {
    // Powered Armsmaster (fpvw2ifz1n): [CB] whenever you sacrifice a Powercell, becomes distant
    if(IsClassBonusActive($player, ["RANGER"])) {
        global $playerID;
        $fieldZone = $player == $playerID ? "myField" : "theirField";
        $field = GetZone($fieldZone);
        for($ai = 0; $ai < count($field); ++$ai) {
            if(!$field[$ai]->removed && $field[$ai]->CardID === "fpvw2ifz1n" && !HasNoAbilities($field[$ai])) {
                BecomeDistant($player, $fieldZone . "-" . $ai);
            }
        }
    }

    // Charged Gunslinger (svdv3zb9p4): [CB] whenever you sacrifice a Powercell,
    // draw a card, discard a card. If fire discarded, becomes distant.
    if(IsClassBonusActive($player, ["RANGER"])) {
        global $playerID;
        $fieldZone = $player == $playerID ? "myField" : "theirField";
        $field = GetZone($fieldZone);
        for($ai = 0; $ai < count($field); ++$ai) {
            if(!$field[$ai]->removed && $field[$ai]->CardID === "svdv3zb9p4" && !HasNoAbilities($field[$ai])) {
                Draw($player, 1);
                $hand = ZoneSearch("myHand");
                if(!empty($hand)) {
                    DecisionQueueController::AddDecision($player, "MZCHOOSE", implode("&", $hand), 1, tooltip:"Discard_a_card_(Charged_Gunslinger)");
                    DecisionQueueController::AddDecision($player, "CUSTOM", "ChargedGunslingerDiscard|$fieldZone-$ai", 1);
                }
                break;
            }
        }
    }

    // Engineered Slime (kkz07nau5s): [CB] whenever you sacrifice a Powercell, choose one —
    // buff counter or spellshroud until end of turn
    if(IsClassBonusActive($player, ["TAMER"])) {
        global $playerID;
        $fieldZone = $player == $playerID ? "myField" : "theirField";
        $field = GetZone($fieldZone);
        for($ai = 0; $ai < count($field); ++$ai) {
            if(!$field[$ai]->removed && $field[$ai]->CardID === "kkz07nau5s" && !HasNoAbilities($field[$ai])) {
                DecisionQueueController::AddDecision($player, "YESNO", "-", 1, tooltip:"Buff_counter?_(No=Spellshroud)");
                DecisionQueueController::AddDecision($player, "CUSTOM", "EngineeredSlimeChoice|$fieldZone-$ai", 1);
                break;
            }
        }
    }
}

// Charged Gunslinger (svdv3zb9p4): discard handler — if fire element, become distant
$customDQHandlers["ChargedGunslingerDiscard"] = function($player, $parts, $lastDecision) {
    if($lastDecision === "-" || $lastDecision === "") return;
    $gunslingerMZ = $parts[0];
    $cardObj = GetZoneObject($lastDecision);
    $cardID = $cardObj !== null ? $cardObj->CardID : null;
    DoDiscardCard($player, $lastDecision);
    if($cardID !== null && CardElement($cardID) === "FIRE") {
        BecomeDistant($player, $gunslingerMZ);
    }
};

// Turbo Charge / Atmos Armor Type-Hermes: sacrifice a Powercell
$customDQHandlers["PowercellSacrifice"] = function($player, $parts, $lastDecision) {
    if($lastDecision === "-" || $lastDecision === "") return;
    OnLeaveField($player, $lastDecision);
    MZMove($player, $lastDecision, "myGraveyard");
    DecisionQueueController::CleanupRemovedCards();
    TriggerPowercellSacrifice($player);
};

// Overlord Mk III (sl7ddcgw05): iterative sacrifice of 4 Powercells
$customDQHandlers["OverlordSacrifice"] = function($player, $parts, $lastDecision) {
    $remaining = intval($parts[0]);
    if($lastDecision !== "-" && $lastDecision !== "") {
        OnLeaveField($player, $lastDecision);
        MZMove($player, $lastDecision, "myGraveyard");
        DecisionQueueController::CleanupRemovedCards();
        TriggerPowercellSacrifice($player);
    }
    if($remaining > 0) {
        $powercells = ZoneSearch("myField", cardSubtypes: ["POWERCELL"]);
        if(!empty($powercells)) {
            $pcChoices = implode("&", $powercells);
            DecisionQueueController::AddDecision($player, "MZCHOOSE", $pcChoices, 100, tooltip:"Sacrifice_a_Powercell_(" . (5 - $remaining) . "_of_4)");
            DecisionQueueController::AddDecision($player, "CUSTOM", "OverlordSacrifice|" . ($remaining - 1), 100);
        }
    }
};

// Overlord Mk III (sl7ddcgw05): end phase — banish Automaton from GY → buff + draw
$customDQHandlers["OverlordEndPhase"] = function($player, $parts, $lastDecision) {
    if($lastDecision === "-" || $lastDecision === "" || $lastDecision === "PASS") return;
    MZMove($player, $lastDecision, "myBanish");
    $fieldIdx = intval($parts[0]);
    AddCounters($player, "myField-" . $fieldIdx, "buff", 1);
    Draw($player, 1);
};

// Whirlwind Reaper (x7yc0ije4d): YesNo handler for end phase wake-up
$customDQHandlers["WhirlwindReaperWakeup"] = function($player, $parts, $lastDecision) {
    if($lastDecision !== "YES") return;
    $fieldIdx = intval($parts[0]);
    // Remove 1 preparation counter from champion
    $field = &GetField($player);
    foreach($field as $fi => $fObj) {
        if(!$fObj->removed && PropertyContains(EffectiveCardType($fObj), "CHAMPION")) {
            RemoveCounters($player, "myField-" . $fi, "preparation", 1);
            break;
        }
    }
    // Wake up Whirlwind Reaper
    $reaperObj = &$field[$fieldIdx];
    if(!$reaperObj->removed) {
        $reaperObj->Status = 2;
    }
};

// ============================================================================
// Kindling Flare (dcgw05qzza): iterative herb sacrifice as additional cost
// ============================================================================
$customDQHandlers["KindlingFlareSacHerb"] = function($player, $parts, $lastDecision) {
    $reserveCost = intval($parts[0]);
    $herbCount = intval(DecisionQueueController::GetVariable("kindlingHerbCount"));

    if($lastDecision === "-" || $lastDecision === "" || $lastDecision === "PASS") {
        // Player declined — done sacrificing, queue reserve payments
        for($i = 0; $i < $reserveCost; ++$i) {
            DecisionQueueController::AddDecision($player, "CUSTOM", "ReserveCard", 100);
        }
        DecisionQueueController::AddDecision($player, "CUSTOM", "EffectStackOpportunity", 100);
        return;
    }

    // Sacrifice the chosen herb
    OnLeaveField($player, $lastDecision);
    MZMove($player, $lastDecision, "myGraveyard");
    DecisionQueueController::CleanupRemovedCards();
    $herbCount++;
    DecisionQueueController::StoreVariable("kindlingHerbCount", strval($herbCount));

    // Check for more herbs
    $herbs = ZoneSearch("myField", cardSubtypes: ["HERB"]);
    if(!empty($herbs)) {
        DecisionQueueController::AddDecision($player, "MZMAYCHOOSE", implode("&", $herbs), 100, tooltip:"Sacrifice_another_Herb?");
        DecisionQueueController::AddDecision($player, "CUSTOM", "KindlingFlareSacHerb|$reserveCost", 100);
    } else {
        // No more herbs — queue reserve payments
        for($i = 0; $i < $reserveCost; ++$i) {
            DecisionQueueController::AddDecision($player, "CUSTOM", "ReserveCard", 100);
        }
        DecisionQueueController::AddDecision($player, "CUSTOM", "EffectStackOpportunity", 100);
    }
};

// ============================================================================
// Molten Cinder (df9q1vk8ao): ActivateAbility target champion that leveled up
// ============================================================================
$customDQHandlers["MoltenCinderTarget"] = function($player, $parts, $lastDecision) {
    if($lastDecision === "-" || $lastDecision === "") return;
    DealDamage($player, "df9q1vk8ao", $lastDecision, 3);
};

// ============================================================================
// Arcane Blast (pn9gQjV3Rb): deal 11 damage to chosen champion
// ============================================================================
$customDQHandlers["ArcaneBlastTarget"] = function($player, $parts, $lastDecision) {
    if($lastDecision === "-" || $lastDecision === "" || $lastDecision === "PASS") return;
    DealDamage($player, "pn9gQjV3Rb", $lastDecision, 11);
};

// ============================================================================
// Caretaker Horse (r5uyjq37zh): chosen ally becomes fostered
// ============================================================================
$customDQHandlers["CaretakerHorseFoster"] = function($player, $parts, $lastDecision) {
    if($lastDecision === "-" || $lastDecision === "" || $lastDecision === "PASS") return;
    BecomeFostered($player, $lastDecision);
    OnFoster($player, $lastDecision);
};

// ============================================================================
// Blazing Bowman (qry41lw9n0): banish fire card from GY for +2 POWER
// ============================================================================
$customDQHandlers["BlazingBowmanBanish"] = function($player, $parts, $lastDecision) {
    $mzID = $parts[0];
    if($lastDecision === "-" || $lastDecision === "" || $lastDecision === "PASS") return;
    MZMove($player, $lastDecision, "myBanish");
    AddTurnEffect($mzID, "qry41lw9n0");
};

// ============================================================================
// Modulating Cadence (p5p0azskw4): reveal Harmony/Melody from TempZone, rest to bottom
// ============================================================================
$customDQHandlers["ModulatingCadenceReveal"] = function($player, $parts, $lastDecision) {
    if($lastDecision !== "-" && $lastDecision !== "" && $lastDecision !== "PASS") {
        Reveal($player, revealedMZ: $lastDecision);
        MZMove($player, $lastDecision, "myHand");
    }
    // Put remaining TempZone cards on bottom of deck
    $remaining = ZoneSearch("myTempZone");
    foreach($remaining as $rmz) {
        MZMove($player, $rmz, "myDeck");
    }
};
$customDQHandlers["ModulatingCadenceBottom"] = function($player, $parts, $lastDecision) {
    // Put all TempZone cards on bottom of deck
    $remaining = ZoneSearch("myTempZone");
    foreach($remaining as $rmz) {
        MZMove($player, $rmz, "myDeck");
    }
};

// ============================================================================
// Lost in Thought (egbscxwjbq): iterative floating memory banish from GY
// ============================================================================
$customDQHandlers["LostInThoughtBanish"] = function($player, $parts, $lastDecision) {
    $banished = intval($parts[0]);

    if($lastDecision === "-" || $lastDecision === "" || $lastDecision === "PASS") {
        // Player declined — draw for all banished so far
        if($banished > 0) Draw($player, $banished);
        return;
    }

    // Banish the chosen card
    MZMove($player, $lastDecision, "myBanish");
    NicoOnFloatingMemoryBanished($player);
    $banished++;

    // Check for more floating memory cards in graveyard
    $floatingGY = ZoneSearch("myGraveyard", floatingMemoryOnly: true);
    if(!empty($floatingGY)) {
        DecisionQueueController::AddDecision($player, "MZMAYCHOOSE", implode("&", $floatingGY), 1, tooltip:"Banish_another_floating_memory_card?");
        DecisionQueueController::AddDecision($player, "CUSTOM", "LostInThoughtBanish|$banished", 1);
    } else {
        // No more — draw for all banished
        if($banished > 0) Draw($player, $banished);
    }
};

// ============================================================================
// Purified Shot (dcgw05q66h): [CB] On Champion Hit — banish up to X from opponent GY
// ============================================================================
$customDQHandlers["PurifiedShotBanish"] = function($player, $parts, $lastDecision) {
    $remaining = intval($parts[0]);

    if($lastDecision === "-" || $lastDecision === "" || $lastDecision === "PASS") {
        return; // Player declined
    }

    // Banish the chosen card from opponent's graveyard
    MZMove($player, $lastDecision, "theirBanish");
    $remaining--;

    if($remaining > 0) {
        $oppGY = ZoneSearch("theirGraveyard");
        if(!empty($oppGY)) {
            DecisionQueueController::AddDecision($player, "MZMAYCHOOSE", implode("&", $oppGY), 1, tooltip:"Banish_another_card_from_opponent_graveyard?");
            DecisionQueueController::AddDecision($player, "CUSTOM", "PurifiedShotBanish|$remaining", 1);
        }
    }
};

// ============================================================================
// Engineered Slime (kkz07nau5s): choose buff counter or spellshroud
// ============================================================================
$customDQHandlers["EngineeredSlimeChoice"] = function($player, $parts, $lastDecision) {
    $slimeMZ = $parts[0];
    $obj = GetZoneObject($slimeMZ);
    if($obj === null || $obj->removed) return;
    if($lastDecision === "YES") {
        AddCounters($player, $slimeMZ, "buff", 1);
    } else {
        AddTurnEffect($slimeMZ, "SPELLSHROUD");
    }
};

// ============================================================================
// Imperial Scout (nrow8iopvc): when becoming distant, may mill 2
// ============================================================================
$customDQHandlers["ImperialScoutMill"] = function($player, $parts, $lastDecision) {
    if($lastDecision !== "YES") return;
    for($i = 0; $i < 2; ++$i) {
        $deck = ZoneSearch("myDeck");
        if(!empty($deck)) {
            MZMove($player, $deck[0], "myGraveyard");
        }
    }
};

// ============================================================================
// Ash Filcher (b65hiv400w): [CB] On Attack — banish fire from GY for +2 POWER
// ============================================================================
$customDQHandlers["AshFilcherBanish"] = function($player, $parts, $lastDecision) {
    if($lastDecision === "-" || $lastDecision === "" || $lastDecision === "PASS") return;
    MZMove($player, $lastDecision, "myBanish");
    // Find Ash Filcher on the field and add POWER TurnEffect
    $field = GetZone("myField");
    foreach($field as $fi => $fObj) {
        if(!$fObj->removed && $fObj->CardID === "b65hiv400w") {
            AddTurnEffect("myField-" . $fi, "b65hiv400w");
            break;
        }
    }
};

// Scavenging Raccoon (fdt8ptrz1b): banish up to N cards from a single graveyard
function ScavengingRaccoonBanish($player, $gyRef, $remaining) {
    if($remaining <= 0) return;
    $gyCards = ZoneSearch($gyRef);
    if(empty($gyCards)) return;
    $gyStr = implode("&", $gyCards);
    DecisionQueueController::AddDecision($player, "MZMAYCHOOSE", $gyStr, 1,
        tooltip:"Banish_a_card_from_graveyard?");
    DecisionQueueController::AddDecision($player, "CUSTOM", "ScavengingRaccoonBanish|$gyRef|$remaining", 1);
}
$customDQHandlers["ScavengingRaccoonBanish"] = function($player, $parts, $lastDecision) {
    $gyRef = $parts[0];
    $remaining = intval($parts[1]);
    if($lastDecision === "-" || $lastDecision === "" || $lastDecision === "PASS") return;
    MZMove($player, $lastDecision, "myBanish");
    $remaining--;
    if($remaining > 0) {
        ScavengingRaccoonBanish($player, $gyRef, $remaining);
    }
};

// Lure the Abyss (gqh3mw478q): Reveal top 4, Specters to GY, rest to bottom
function LureTheAbyssExecute($player) {
    $deck = &GetDeck($player);
    if(empty($deck)) return;
    $count = min(4, count($deck));
    // Move top N cards to TempZone for reveal
    for($i = 0; $i < $count; $i++) {
        MZMove($player, "myDeck-0", "myTempZone");
    }
    // Now separate Specters from non-Specters
    $tempCards = ZoneSearch("myTempZone");
    foreach($tempCards as $tMZ) {
        $tObj = GetZoneObject($tMZ);
        if($tObj !== null && PropertyContains(CardSubtypes($tObj->CardID), "SPECTER")) {
            MZMove($player, $tMZ, "myGraveyard");
        }
    }
    // Put remaining cards on bottom of deck
    $remaining = ZoneSearch("myTempZone");
    foreach($remaining as $rMZ) {
        MZMove($player, $rMZ, "myDeck");
    }
}

// Noble Dissolution (iullthfLXc): destroy up to N phantasias iteratively
function NobleDissolutionDestroy($player, $remaining) {
    if($remaining <= 0) return;
    $phantasias = array_merge(
        ZoneSearch("myField", ["PHANTASIA"]),
        ZoneSearch("theirField", ["PHANTASIA"])
    );
    $phantasias = FilterSpellshroudTargets($phantasias);
    if(empty($phantasias)) return;
    $targetStr = implode("&", $phantasias);
    DecisionQueueController::AddDecision($player, "MZMAYCHOOSE", $targetStr, 1,
        tooltip:"Destroy_target_phantasia");
    DecisionQueueController::AddDecision($player, "CUSTOM", "NobleDissolutionDestroy|$remaining", 1);
}
$customDQHandlers["NobleDissolutionDestroy"] = function($player, $parts, $lastDecision) {
    $remaining = intval($parts[0]);
    if($lastDecision === "-" || $lastDecision === "" || $lastDecision === "PASS") return;
    $obj = GetZoneObject($lastDecision);
    if($obj === null) return;
    $owner = $obj->Owner;
    OnLeaveField($player, $lastDecision);
    $gravZone = ($player == $owner) ? "myGraveyard" : "theirGraveyard";
    $gravZone = EphemeralRedirectDest($obj, $gravZone, $player);
    MZMove($player, $lastDecision, $gravZone);
    DecisionQueueController::CleanupRemovedCards();
    $remaining--;
    if($remaining > 0) {
        NobleDissolutionDestroy($player, $remaining);
    }
};

// ============================================================================
// Primal Whip (az2b8nfh95): [CB][Lv2+] On Attack — up to 2 non-Human allies +1 POWER
// ============================================================================
$customDQHandlers["PrimalWhipBuff"] = function($player, $parts, $lastDecision) {
    $iteration = intval($parts[0]);
    if($lastDecision === "-" || $lastDecision === "" || $lastDecision === "PASS") return;
    AddTurnEffect($lastDecision, "az2b8nfh95");
    if($iteration >= 2) return;
    // Offer second choice from remaining non-Human allies
    $nonHumans = [];
    $field = GetZone("myField");
    foreach($field as $idx => $fObj) {
        if($fObj->removed) continue;
        if(!PropertyContains(EffectiveCardType($fObj), "ALLY")) continue;
        if(PropertyContains(EffectiveCardSubtypes($fObj), "HUMAN")) continue;
        $mz = "myField-" . $idx;
        if($mz === $lastDecision) continue;
        $nonHumans[] = $mz;
    }
    if(empty($nonHumans)) return;
    $choices = implode("&", $nonHumans);
    DecisionQueueController::AddDecision($player, "MZMAYCHOOSE", $choices, 1, tooltip:"Choose_another_non-Human_ally_for_+1_POWER");
    DecisionQueueController::AddDecision($player, "CUSTOM", "PrimalWhipBuff|2", 1);
};

// ============================================================================
// Orb of Regret (BY0E8si926): shuffle up to 3 cards from hand into deck, draw that many
// ============================================================================
$customDQHandlers["OrbOfRegretShuffle"] = function($player, $parts, $lastDecision) {
    $iteration = intval($parts[0]);
    $count = intval(DecisionQueueController::GetVariable("OrbOfRegretCount"));
    if($lastDecision === "-" || $lastDecision === "" || $lastDecision === "PASS") {
        // Done choosing — shuffle deck and draw the number shuffled in
        if($count > 0) {
            ShuffleZone("myDeck");
            Draw($player, $count);
        }
        return;
    }
    // Move chosen card from hand to deck (bottom)
    MZMove($player, $lastDecision, "myDeck");
    $count++;
    DecisionQueueController::StoreVariable("OrbOfRegretCount", strval($count));
    if($iteration >= 3) {
        // Reached max — shuffle and draw
        ShuffleZone("myDeck");
        Draw($player, $count);
        return;
    }
    // Offer another choice
    $hand = ZoneSearch("myHand");
    if(empty($hand)) {
        // No cards left in hand — shuffle and draw
        ShuffleZone("myDeck");
        Draw($player, $count);
        return;
    }
    $handStr = implode("&", $hand);
    DecisionQueueController::AddDecision($player, "MZMAYCHOOSE", $handStr, 1, tooltip:"Shuffle_another_card_into_deck?");
    DecisionQueueController::AddDecision($player, "CUSTOM", "OrbOfRegretShuffle|" . ($iteration + 1), 1);
};

// ============================================================================
// Creative Shock (BqDw4Mei4C): draw 2 discard 1, CB fire → deal 2 to unit
// ============================================================================
$customDQHandlers["CreativeShockDiscard"] = function($player, $parts, $lastDecision) {
    if($lastDecision === "-" || $lastDecision === "") return;
    // Check element before discarding
    $obj = GetZoneObject($lastDecision);
    $isFire = ($obj !== null && CardElement($obj->CardID) === "FIRE");
    DoDiscardCard($player, $lastDecision);
    if(!IsClassBonusActive($player, ["MAGE"])) return;
    if(!$isFire) return;
    // Offer to deal 2 damage to a unit
    $targets = array_merge(
        ZoneSearch("myField", ["ALLY", "CHAMPION"]),
        ZoneSearch("theirField", ["ALLY", "CHAMPION"])
    );
    $targets = FilterSpellshroudTargets($targets);
    if(empty($targets)) return;
    $targetStr = implode("&", $targets);
    DecisionQueueController::AddDecision($player, "MZMAYCHOOSE", $targetStr, 1, tooltip:"Deal_2_damage_to_a_unit?");
    DecisionQueueController::AddDecision($player, "CUSTOM", "CreativeShockDamage", 1);
};

$customDQHandlers["CreativeShockDamage"] = function($player, $parts, $lastDecision) {
    if($lastDecision === "-" || $lastDecision === "" || $lastDecision === "PASS") return;
    $mzID = DecisionQueueController::GetVariable("mzID");
    DealDamage($player, $mzID, $lastDecision, 2);
};

// ============================================================================
// Expunge (r73opcqtzs): mandatory Curse discard from a champion's lineage
// ============================================================================
$customDQHandlers["ExpungeChosenChampion"] = function($player, $parts, $lastDecision) {
    $reserveCost = intval($parts[0]);
    DecisionQueueController::StoreVariable("expungeChampion", $lastDecision);
    DecisionQueueController::AddDecision($player, "CUSTOM", "ExpungePickCurse|$reserveCost", 1);
};

$customDQHandlers["ExpungePickCurse"] = function($player, $parts, $lastDecision) {
    $reserveCost = intval($parts[0]);
    $champMZ = DecisionQueueController::GetVariable("expungeChampion");
    $champObj = &GetZoneObject($champMZ);
    if($champObj === null) return;

    $curses = [];
    foreach($champObj->Subcards as $scIdx => $scID) {
        if(PropertyContains(CardSubtypes($scID), "CURSE")) {
            $curses[] = ['idx' => $scIdx, 'cardID' => $scID];
        }
    }

    if(count($curses) == 1) {
        $curse = $curses[0];
        $curseCost = CardCost_reserve($curse['cardID']);
        array_splice($champObj->Subcards, $curse['idx'], 1);
        MZAddZone($player, "myGraveyard", $curse['cardID']);
        DecisionQueueController::StoreVariable("expungeCurseCost", strval($curseCost));
        for($i = 0; $i < $reserveCost; ++$i) {
            DecisionQueueController::AddDecision($player, "CUSTOM", "ReserveCard", 100);
        }
        DecisionQueueController::AddDecision($player, "CUSTOM", "EffectStackOpportunity", 100);
    } else {
        // Multiple Curses — put into TempZone for UI display
        foreach($curses as $c) {
            MZAddZone($player, "myTempZone", $c['cardID']);
        }
        $tempCards = ZoneSearch("myTempZone");
        $tempStr = implode("&", $tempCards);
        DecisionQueueController::AddDecision($player, "MZCHOOSE", $tempStr, 1, tooltip:"Choose_Curse_to_discard");
        DecisionQueueController::AddDecision($player, "CUSTOM", "ExpungeCurseChosen|$reserveCost|$champMZ", 1);
    }
};

$customDQHandlers["ExpungeCurseChosen"] = function($player, $parts, $lastDecision) {
    $reserveCost = intval($parts[0]);
    $champMZ = $parts[1];
    $champObj = &GetZoneObject($champMZ);

    $chosenObj = GetZoneObject($lastDecision);
    $chosenCardID = $chosenObj->CardID;
    $curseCost = CardCost_reserve($chosenCardID);

    // Remove the chosen Curse CardID from the champion's Subcards
    for($i = 0; $i < count($champObj->Subcards); ++$i) {
        if($champObj->Subcards[$i] === $chosenCardID) {
            array_splice($champObj->Subcards, $i, 1);
            break;
        }
    }

    // Clear TempZone
    $tempZone = &GetZone("myTempZone");
    foreach($tempZone as $tObj) {
        $tObj->Remove();
    }

    // Record the discarded Curse in the graveyard
    MZAddZone($player, "myGraveyard", $chosenCardID);

    DecisionQueueController::StoreVariable("expungeCurseCost", strval($curseCost));
    for($i = 0; $i < $reserveCost; ++$i) {
        DecisionQueueController::AddDecision($player, "CUSTOM", "ReserveCard", 100);
    }
    DecisionQueueController::AddDecision($player, "CUSTOM", "EffectStackOpportunity", 100);
};

// ============================================================================
// Ravishing Finale (jlgx72rfgv): mandatory banish 2 floating memory from GY
// ============================================================================
$customDQHandlers["RavishingFinaleBanish1"] = function($player, $parts, $lastDecision) {
    $reserveCost = intval($parts[0]);
    if($lastDecision === "-" || $lastDecision === "") return;
    $hadFloating = HasFloatingMemory(GetZoneObject($lastDecision));
    MZMove($player, $lastDecision, "myBanish");
    if($hadFloating) NicoOnFloatingMemoryBanished($player);
    // Second pick
    $floatingGY = [];
    $gy = GetZone("myGraveyard");
    for($gi = 0; $gi < count($gy); ++$gi) {
        if(!$gy[$gi]->removed && HasFloatingMemory($gy[$gi])) {
            $floatingGY[] = "myGraveyard-" . $gi;
        }
    }
    if(empty($floatingGY)) return;
    $floatingStr = implode("&", $floatingGY);
    DecisionQueueController::AddDecision($player, "MZCHOOSE", $floatingStr, 100, tooltip:"Banish_floating-memory_card_(2_of_2)");
    DecisionQueueController::AddDecision($player, "CUSTOM", "RavishingFinaleBanish2|$reserveCost", 100);
};

$customDQHandlers["RavishingFinaleBanish2"] = function($player, $parts, $lastDecision) {
    $reserveCost = intval($parts[0]);
    if($lastDecision === "-" || $lastDecision === "") return;
    $hadFloating = HasFloatingMemory(GetZoneObject($lastDecision));
    MZMove($player, $lastDecision, "myBanish");
    if($hadFloating) NicoOnFloatingMemoryBanished($player);
    // Queue normal reserve payments + opportunity
    DecisionQueueController::StoreVariable("additionalCostPaid", "NO");
    for($i = 0; $i < $reserveCost; ++$i) {
        DecisionQueueController::AddDecision($player, "CUSTOM", "ReserveCard", 100);
    }
    DecisionQueueController::AddDecision($player, "CUSTOM", "EffectStackOpportunity", 100);
};

// ============================================================================
// Purification (k8ao8bki6f): choose up to 2 Curse cards from lineage to discard
// ============================================================================
function PurificationCursePick($player) {
    $curses = GetCursesInLineage($player);
    if(empty($curses)) return;
    $tempZone = &GetTempZone($player);
    while(count($tempZone) > 0) array_pop($tempZone);
    foreach($curses as $curse) {
        MZAddZone($player, "myTempZone", $curse['cardID']);
    }
    $tempMZs = [];
    for($i = 0; $i < count($curses); ++$i) {
        $tempMZs[] = "myTempZone-" . $i;
    }
    $options = implode("&", $tempMZs);
    DecisionQueueController::AddDecision($player, "MZMAYCHOOSE", $options, 1, "Choose_Curse_to_discard");
    DecisionQueueController::AddDecision($player, "CUSTOM", "PurificationCurse1", 1);
}

$customDQHandlers["PurificationCurse1"] = function($player, $parts, $lastDecision) {
    $tempZone = &GetTempZone($player);
    if($lastDecision === "-" || $lastDecision === "" || $lastDecision === "PASS") {
        while(count($tempZone) > 0) array_pop($tempZone);
        return;
    }
    $chosenObj = GetZoneObject($lastDecision);
    $chosenCardID = $chosenObj->CardID;
    MZRemove($player, $lastDecision);
    DecisionQueueController::CleanupRemovedCards();
    RemoveFromChampionLineage($player, $chosenCardID, "myGraveyard");
    RecoverChampion($player, 2);
    // Offer second pick
    $tempZone = &GetTempZone($player);
    $remaining = [];
    for($i = 0; $i < count($tempZone); ++$i) {
        if(!$tempZone[$i]->removed) {
            $remaining[] = "myTempZone-" . $i;
        }
    }
    if(!empty($remaining)) {
        $options = implode("&", $remaining);
        DecisionQueueController::AddDecision($player, "MZMAYCHOOSE", $options, 1, "Choose_another_Curse_to_discard");
        DecisionQueueController::AddDecision($player, "CUSTOM", "PurificationCurse2", 1);
    } else {
        while(count($tempZone) > 0) array_pop($tempZone);
    }
};

$customDQHandlers["PurificationCurse2"] = function($player, $parts, $lastDecision) {
    $tempZone = &GetTempZone($player);
    if($lastDecision !== "-" && $lastDecision !== "" && $lastDecision !== "PASS") {
        $chosenObj = GetZoneObject($lastDecision);
        $chosenCardID = $chosenObj->CardID;
        RemoveFromChampionLineage($player, $chosenCardID, "myGraveyard");
        RecoverChampion($player, 2);
    }
    while(count($tempZone) > 0) array_pop($tempZone);
};

// ============================================================================
// Reverse Affliction (1bxh5xz2uz): banish a Curse from your champion's lineage,
// put an omen counter on it
// ============================================================================
function ReverseAfflictionSetup($player) {
    $curses = GetCursesInLineage($player);
    if(empty($curses)) return;
    $tempZone = &GetTempZone($player);
    while(count($tempZone) > 0) array_pop($tempZone);
    foreach($curses as $curse) {
        MZAddZone($player, "myTempZone", $curse['cardID']);
    }
    $tempMZs = [];
    for($i = 0; $i < count($curses); ++$i) {
        $tempMZs[] = "myTempZone-" . $i;
    }
    $options = implode("&", $tempMZs);
    DecisionQueueController::AddDecision($player, "MZCHOOSE", $options, 1, tooltip:"Banish_a_Curse_from_lineage");
    DecisionQueueController::AddDecision($player, "CUSTOM", "ReverseAfflictionPick", 1);
}

$customDQHandlers["ReverseAfflictionPick"] = function($player, $parts, $lastDecision) {
    $tempZone = &GetTempZone($player);
    if($lastDecision === "-" || $lastDecision === "" || $lastDecision === "PASS") {
        while(count($tempZone) > 0) array_pop($tempZone);
        return;
    }
    $chosenObj = GetZoneObject($lastDecision);
    $chosenCardID = $chosenObj->CardID;
    MZRemove($player, $lastDecision);
    DecisionQueueController::CleanupRemovedCards();
    // Remove from champion lineage and banish
    RemoveFromChampionLineage($player, $chosenCardID, "myBanish");
    // Put omen counter on the banished card
    global $playerID;
    $banish = GetBanish($player);
    $prefix = ($player == $playerID) ? "myBanish" : "theirBanish";
    for($i = count($banish) - 1; $i >= 0; --$i) {
        if(!$banish[$i]->removed && $banish[$i]->CardID === $chosenCardID) {
            PutOmenCounter($player, $prefix . "-" . $i);
            break;
        }
    }
    // Clean up remaining TempZone
    $tempZone = &GetTempZone($player);
    while(count($tempZone) > 0) array_pop($tempZone);
};

// ============================================================================
// Bertha, Spry Howitzer (ki6fxxgmue): look at top 5, may activate Ranger action
// ============================================================================
function BerthaLookFinish($player) {
    $tempCards = ZoneSearch("myTempZone");
    // Find valid Ranger action cards with reserve cost ≤ 2
    $validActions = [];
    foreach($tempCards as $tmz) {
        $tObj = GetZoneObject($tmz);
        if(PropertyContains(CardType($tObj->CardID), "ACTION")
            && PropertyContains(CardClasses($tObj->CardID), "RANGER")
            && CardCost_reserve($tObj->CardID) <= 2) {
            $validActions[] = $tmz;
        }
    }
    if(!empty($validActions)) {
        $actionStr = implode("&", $validActions);
        DecisionQueueController::AddDecision($player, "MZMAYCHOOSE", $actionStr, 1, tooltip:"Activate_a_Ranger_action_for_free?");
        DecisionQueueController::AddDecision($player, "CUSTOM", "BerthaChooseAction", 1);
    } else {
        // No valid actions — shuffle rest to bottom of deck
        BerthaPutRestOnBottom($player);
    }
}

$customDQHandlers["BerthaChooseAction"] = function($player, $parts, $lastDecision) {
    if($lastDecision === "-" || $lastDecision === "" || $lastDecision === "PASS") {
        BerthaPutRestOnBottom($player);
        return;
    }
    // Move chosen card to hand, then activate it for free
    $chosenObj = GetZoneObject($lastDecision);
    $chosenCardID = $chosenObj->CardID;
    MZMove($player, $lastDecision, "myHand");
    $hand = &GetHand($player);
    $handIdx = count($hand) - 1;
    // Remove remaining TempZone to bottom of deck first
    BerthaPutRestOnBottom($player);
    // Activate the card for free (ignoreCost = true)
    ActivateCard($player, "myHand-" . $handIdx, true);
};

function BerthaPutRestOnBottom($player) {
    $remaining = ZoneSearch("myTempZone");
    foreach($remaining as $rmz) {
        MZMove($player, $rmz, "myDeck");
    }
}

// ============================================================================
// Liquid Amnesia (k0hliqs2hi): banish random cards from memory
// ============================================================================
function LiquidAmnesiaBanish($player, $memRef, $remaining) {
    if($remaining <= 0) return;
    $memCards = ZoneSearch($memRef);
    if(empty($memCards)) return;
    // Pick random card from memory
    $randomIdx = array_rand($memCards);
    $randomCard = $memCards[$randomIdx];
    // Convert to banish zone reference
    $banishRef = (strpos($memRef, "my") === 0) ? "myBanish" : "theirBanish";
    MZMove($player, $randomCard, $banishRef);
    $remaining--;
    if($remaining > 0) {
        LiquidAmnesiaBanish($player, $memRef, $remaining);
    }
}

// ============================================================================
// Tristan Package — Helper Functions
// ============================================================================

/**
 * Check if a player's champion is a Tristan for Tristan Bonus abilities.
 * Returns true if any Tristan card ID is in the champion's lineage.
 */
function IsTristanBonus($player) {
    $tristanIDs = ["K5luT8aRzc", "he6kd7hocc", "bjlwabipl6", "gt7lh9v221", "4upufooz13"];
    $lineage = GetChampionLineage($player);
    foreach($tristanIDs as $tid) {
        if(in_array($tid, $lineage)) return true;
    }
    return false;
}

/**
 * Find the mzID of a player's champion on the field.
 * @param int $player The player number.
 * @return string|null The mzID (e.g. "myField-0") or null if not found.
 */
function FindChampionMZ($player) {
    global $playerID;
    $zone = ($player == $playerID) ? "myField" : "theirField";
    $champions = ZoneSearch($zone, ["CHAMPION"]);
    return !empty($champions) ? $champions[0] : null;
}

/**
 * Add a preparation counter to a player's champion.
 * @param int $player The player whose champion gets the counter.
 * @return bool True if counter was added, false if no champion found.
 */
function AddPrepCounter($player, $amount = 1) {
    $champMZ = FindChampionMZ($player);
    if($champMZ === null) return false;
    AddCounters($player, $champMZ, "preparation", $amount);
    return true;
}

/**
 * Gain Agility N for this turn. Adds a global effect tracking the agility amount.
 * At the beginning of the end phase, the player returns N cards from memory to hand.
 * @param int $player The player gaining agility.
 * @param int $amount The agility value (e.g. 3).
 */
function GainAgility($player, $amount) {
    AddGlobalEffects($player, "AGILITY_" . $amount);
}

// ============================================================================
// Penumbral Waltz — prep counter removal chain
// ============================================================================

/**
 * Start the YESNO chain for removing prep counters for Penumbral Waltz.
 */
function PenumbralWaltzChooseX($player) {
    $champMZ = FindChampionMZ($player);
    if($champMZ === null) { PenumbralWaltzResolve($player); return; }
    $champObj = GetZoneObject($champMZ);
    if($champObj === null || GetPrepCounterCount($champObj) <= 0) { PenumbralWaltzResolve($player); return; }
    $x = intval(DecisionQueueController::GetVariable("penumbralWaltzX") ?? "0");
    DecisionQueueController::AddDecision($player, "YESNO", "-", 1, tooltip:"Remove_a_preparation_counter?_(current_X=" . $x . ")");
    DecisionQueueController::AddDecision($player, "CUSTOM", "PenumbralWaltzCost", 1);
}

$customDQHandlers["PenumbralWaltzCost"] = function($player, $parts, $lastDecision) {
    $x = intval(DecisionQueueController::GetVariable("penumbralWaltzX") ?? "0");
    if($lastDecision === "YES") {
        $champMZ = FindChampionMZ($player);
        if($champMZ !== null) {
            RemoveCounters($player, $champMZ, "preparation", 1);
            $x++;
            DecisionQueueController::StoreVariable("penumbralWaltzX", strval($x));
        }
        // Check for more counters
        $champObj = ($champMZ !== null) ? GetZoneObject($champMZ) : null;
        if($champObj !== null && GetPrepCounterCount($champObj) > 0) {
            PenumbralWaltzChooseX($player);
            return;
        }
    }
    PenumbralWaltzResolve($player);
};

/**
 * Resolve Penumbral Waltz after X prep counters have been removed.
 * Prevent X+3 damage to champion. If Tristan Bonus and X >= 3, summon 2 Ominous Shadows.
 */
function PenumbralWaltzResolve($player) {
    $x = intval(DecisionQueueController::GetVariable("penumbralWaltzX") ?? "0");
    $champMZ = FindChampionMZ($player);
    if($champMZ !== null) {
        $preventAmount = $x + 3;
        $champObj = &GetZoneObject($champMZ);
        if($champObj !== null) {
            // Add prevention as per-card TurnEffect (PREVENT_CHAMP_N pattern)
            AddTurnEffect($champMZ, "PREVENT_CHAMP_" . $preventAmount);
        }
    }
    if($x >= 3 && IsTristanBonus($player)) {
        MZAddZone($player, "myField", "gveirpdm44");
        MZAddZone($player, "myField", "gveirpdm44");
    }
}

// ============================================================================
// Sadi, Blood Harvester — ActivateAbility handler
// ============================================================================

$customDQHandlers["SadiReturnToHand"] = function($player, $parts, $lastDecision) {
    $mzID = $parts[0] ?? null;
    if($mzID === null) return;
    $obj = GetZoneObject($mzID);
    if($obj === null || $obj->removed || $obj->CardID !== "ugly4wiffe") return;
    MZMove($player, $mzID, "myHand");
    AddPrepCounter($player);
};

// ============================================================================
// Gloamspire Mantle — Summon Ominous Shadow after payment
// ============================================================================

$customDQHandlers["GloamspireMantleSummon"] = function($player, $parts, $lastDecision) {
    MZAddZone($player, "myField", "gveirpdm44");
};

// ============================================================================
// Extricating Touch (4a8hl5dben) — choose player → hand/memory → pick card → discard
// ============================================================================

$customDQHandlers["ExtricatingTouchZoneChoice"] = function($player, $parts, $lastDecision) {
    // $lastDecision = chosen champion mzID (the target player)
    if($lastDecision === "-" || $lastDecision === "" || $lastDecision === "PASS") return;
    $targetObj = GetZoneObject($lastDecision);
    if($targetObj === null) return;
    $targetPlayer = $targetObj->Controller;
    DecisionQueueController::StoreVariable("extricatingTarget", strval($targetPlayer));
    DecisionQueueController::AddDecision($player, "YESNO", "-", 1, tooltip:"Reveal_hand?_(No=Reveal_memory)");
    DecisionQueueController::AddDecision($player, "CUSTOM", "ExtricatingTouchReveal", 1);
};

$customDQHandlers["ExtricatingTouchReveal"] = function($player, $parts, $lastDecision) {
    $targetPlayer = intval(DecisionQueueController::GetVariable("extricatingTarget"));
    global $playerID;
    if($lastDecision === "YES") {
        // Reveal hand
        $handZone = $targetPlayer == $playerID ? "myHand" : "theirHand";
        $hand = GetZone($handZone);
        $handMZs = [];
        for($i = 0; $i < count($hand); ++$i) {
            if(!$hand[$i]->removed) $handMZs[] = $handZone . "-" . $i;
        }
        if(empty($handMZs)) return;
        DecisionQueueController::AddDecision($player, "MZCHOOSE", implode("&", $handMZs), 1, tooltip:"Choose_card_to_discard");
        DecisionQueueController::AddDecision($player, "CUSTOM", "ExtricatingTouchDiscard", 1);
    } else {
        // Reveal memory
        $memZone = $targetPlayer == $playerID ? "myMemory" : "theirMemory";
        $mem = GetZone($memZone);
        $memMZs = [];
        for($i = 0; $i < count($mem); ++$i) {
            if(!$mem[$i]->removed) $memMZs[] = $memZone . "-" . $i;
        }
        if(empty($memMZs)) return;
        DecisionQueueController::AddDecision($player, "MZCHOOSE", implode("&", $memMZs), 1, tooltip:"Choose_card_to_discard");
        DecisionQueueController::AddDecision($player, "CUSTOM", "ExtricatingTouchDiscard", 1);
    }
};

$customDQHandlers["ExtricatingTouchDiscard"] = function($player, $parts, $lastDecision) {
    if($lastDecision === "-" || $lastDecision === "" || $lastDecision === "PASS") return;
    $targetPlayer = intval(DecisionQueueController::GetVariable("extricatingTarget"));
    DoDiscardCard($targetPlayer, $lastDecision);
};

// ============================================================================
// Zhou Yu, Enlightened Sage (55d7vo62fc) — [CB] On Enter: materialize Book/Scripture from material deck
// ============================================================================

$customDQHandlers["ZhouYuMaterialize"] = function($player, $parts, $lastDecision) {
    global $customDQHandlers;
    if($lastDecision === "-" || $lastDecision === "" || $lastDecision === "PASS") return;
    $customDQHandlers["MATERIALIZE"]($player, [], $lastDecision);
};

// ============================================================================
// Red Hare (5du8f077ua) — On Attack discard-to-draw flow
// ============================================================================

$customDQHandlers["RedHareDiscardDraw"] = function($player, $parts, $lastDecision) {
    if($lastDecision !== "YES") return;
    $hand = GetZone("myHand");
    if(empty($hand)) return;
    $handMZs = [];
    for($i = 0; $i < count($hand); ++$i) {
        if(!$hand[$i]->removed) $handMZs[] = "myHand-" . $i;
    }
    if(empty($handMZs)) return;
    DecisionQueueController::AddDecision($player, "MZCHOOSE", implode("&", $handMZs), 1, tooltip:"Choose_card_to_discard");
    DecisionQueueController::AddDecision($player, "CUSTOM", "RedHareDiscardFinish", 1);
};

$customDQHandlers["RedHareDiscardFinish"] = function($player, $parts, $lastDecision) {
    if($lastDecision === "-" || $lastDecision === "" || $lastDecision === "PASS") return;
    DoDiscardCard($player, $lastDecision);
    Draw($player, 1);
};

// ============================================================================
// Scorching Imperilment (aj7pz79wsp): discard → draw handler
// ============================================================================
$customDQHandlers["ScorchingImperilmentDiscard"] = function($player, $params, $lastDecision) {
    if($lastDecision === "-" || $lastDecision === "") return;
    DoDiscardCard($player, $lastDecision);
    Draw($player, 1);
};

// ============================================================================
// Wither Upkeep — at start of main phase, each object with wither counters:
// controller sacrifices it unless they pay (1) per wither counter, then remove.
// ============================================================================
function WitherUpkeep($player) {
    global $playerID;
    $zone = $player == $playerID ? "myField" : "theirField";
    $field = GetZone($zone);
    for($i = 0; $i < count($field); $i++) {
        if($field[$i]->removed) continue;
        $witherCount = GetCounterCount($field[$i], "wither");
        if($witherCount <= 0) continue;
        // Skip champions (wither only affects non-champion objects per rules)
        if(PropertyContains(EffectiveCardType($field[$i]), "CHAMPION")) continue;
        $handCount = count(GetZone("myHand"));
        $mz = $zone . "-" . $i;
        if($handCount >= $witherCount) {
            DecisionQueueController::AddDecision($player, "YESNO", "-", 1,
                tooltip:"Pay_" . $witherCount . "_to_keep_withered_object?");
            DecisionQueueController::AddDecision($player, "CUSTOM", "WitherUpkeepProcess|" . $mz . "|" . $witherCount, 1);
        } else {
            // Can't afford — auto-sacrifice
            DoSacrificeFighter($player, $mz);
            DecisionQueueController::CleanupRemovedCards();
        }
    }
}

$customDQHandlers["WitherUpkeepProcess"] = function($player, $params, $lastDecision) {
    $mz = $params[0];
    $witherCount = intval($params[1]);
    if($lastDecision === "YES") {
        // Pay reserve cost (hand→memory) for each wither counter
        for($i = 0; $i < $witherCount; $i++) {
            DecisionQueueController::AddDecision($player, "CUSTOM", "ReserveCard", 100);
        }
        // After payment, remove wither counters
        DecisionQueueController::AddDecision($player, "CUSTOM", "WitherUpkeepClear|" . $mz, 1);
    } else {
        // Sacrifice the object
        $obj = GetZoneObject($mz);
        if($obj !== null && !$obj->removed) {
            DoSacrificeFighter($player, $mz);
            DecisionQueueController::CleanupRemovedCards();
        }
    }
};

$customDQHandlers["WitherUpkeepClear"] = function($player, $params, $lastDecision) {
    $mz = $params[0];
    $obj = GetZoneObject($mz);
    if($obj !== null && !$obj->removed) {
        ClearCounters($player, $mz, "wither");
    }
};

// ============================================================================
// Diao Chan, Idyll Corsage (d7l6i5thdy): banish destroyed opponent's non-token
// object and summon a Flowerbud token for that opponent
// ============================================================================
$customDQHandlers["DiaoChanIdyllBanish"] = function($player, $params, $lastDecision) {
    if($lastDecision !== "YES") return;
    $cardID = $params[0];
    $destroyedController = intval($params[1]);
    global $playerID;
    // Find the card in the opponent's graveyard (most common destination) and banish it
    $oppGY = $destroyedController == $playerID ? "myGraveyard" : "theirGraveyard";
    $oppBanish = $destroyedController == $playerID ? "myBanish" : "theirBanish";
    $gy = GetZone($oppGY);
    $found = false;
    for($i = count($gy) - 1; $i >= 0; $i--) {
        if($gy[$i]->CardID === $cardID) {
            MZMove($player, $oppGY . "-" . $i, $oppBanish);
            $found = true;
            break;
        }
    }
    if(!$found) {
        // Check material deck (renewable cards go there)
        $oppMat = $destroyedController == $playerID ? "myMaterial" : "theirMaterial";
        $mat = GetZone($oppMat);
        for($i = count($mat) - 1; $i >= 0; $i--) {
            if($mat[$i]->CardID === $cardID) {
                MZMove($player, $oppMat . "-" . $i, $oppBanish);
                $found = true;
                break;
            }
        }
    }
    // Summon a Flowerbud token for the opponent (the destroyed card's controller)
    MZAddZone($destroyedController, "myField", "yn78t73w1p"); // Flowerbud token
};

// ============================================================================
// Diao Chan, Idyll Corsage (d7l6i5thdy): On Enter — choose any amount of
// non-champion objects loop: add wither counter, repeat until pass
// ============================================================================
$customDQHandlers["DiaoChanIdyllWitherLoop"] = function($player, $params, $lastDecision) {
    if($lastDecision === "-" || $lastDecision === "") return;
    AddCounters($player, $lastDecision, "wither", 1);
    // Offer to choose another (exclude already-chosen — filter still-valid targets)
    $allObjects = array_merge(
        ZoneSearch("myField", ["ALLY", "REGALIA"]),
        ZoneSearch("myField", cardSubtypes: ["WEAPON"]),
        ZoneSearch("theirField", ["ALLY", "REGALIA"]),
        ZoneSearch("theirField", cardSubtypes: ["WEAPON"])
    );
    $allObjects = array_filter($allObjects, function($mz) {
        $obj = GetZoneObject($mz);
        return $obj !== null && !PropertyContains(EffectiveCardType($obj), "CHAMPION");
    });
    $allObjects = array_values($allObjects);
    if(empty($allObjects)) return;
    $choices = implode("&", $allObjects);
    DecisionQueueController::AddDecision($player, "MZMAYCHOOSE", $choices, 1,
        tooltip:"Choose_another_object_to_put_wither_counter_on");
    DecisionQueueController::AddDecision($player, "CUSTOM", "DiaoChanIdyllWitherLoop", 1);
};

// ============================================================================
// Enthralling Chime (mhc5a9jpi6): gain control of target ally with 3+ wither
// ============================================================================
$customDQHandlers["EnthrallingChimeGainControl"] = function($player, $params, $lastDecision) {
    if($lastDecision === "-" || $lastDecision === "") return;
    $obj = &GetZoneObject($lastDecision);
    if($obj === null || $obj->removed) return;
    $obj->Controller = $player;
};

// ============================================================================
// Bloom: Autumn's Fall (pebu7agtcd): choose Acerbica or Washuru for each
// sacrificed Flowerbud, then summon for the opponent
// ============================================================================
function BloomAutumnChooseTokens($player, $opponent, $remaining) {
    if($remaining <= 0) return;
    // YESNO: "Summon_Acerbica?" — YES = Acerbica, NO = Washuru
    DecisionQueueController::AddDecision($player, "YESNO", "-", 1,
        tooltip:"Summon_Acerbica_for_opponent?_(NO=Washuru)_(" . $remaining . "_remaining)");
    DecisionQueueController::AddDecision($player, "CUSTOM", "BloomAutumnToken|" . $opponent . "|" . $remaining, 1);
}

$customDQHandlers["BloomAutumnToken"] = function($player, $params, $lastDecision) {
    $opponent = intval($params[0]);
    $remaining = intval($params[1]);
    if($lastDecision === "YES") {
        MZAddZone($opponent, "myField", "7ax4ywyv19"); // Acerbica
    } else {
        MZAddZone($opponent, "myField", "k5iv040vcq"); // Washuru
    }
    BloomAutumnChooseTokens($player, $opponent, $remaining - 1);
};

// ============================================================================
// Ameliorating Mantra (b3sm5e7pan): remove wither counters from up to 2 targets
// ============================================================================
function FindWitherTargets($exclude = null) {
    $targets = [];
    foreach(["myField", "theirField"] as $zoneName) {
        $field = GetZone($zoneName);
        for($i = 0; $i < count($field); $i++) {
            if(!$field[$i]->removed && GetCounterCount($field[$i], "wither") > 0) {
                $mz = $zoneName . "-" . $i;
                if($mz !== $exclude) $targets[] = $mz;
            }
        }
    }
    return $targets;
}

function AmelioratingMantraContinue($player, $firstTarget) {
    if($firstTarget !== "-" && $firstTarget !== "") {
        RemoveCounters($player, $firstTarget, "wither", 2);
    }
    $targets = FindWitherTargets($firstTarget);
    $targets = FilterSpellshroudTargets($targets);
    if(empty($targets)) return;
    DecisionQueueController::AddDecision($player, "MZMAYCHOOSE", implode("&", $targets), 1,
        tooltip:"Remove_2_wither_from_another_target?");
    DecisionQueueController::AddDecision($player, "CUSTOM", "AmelioratingMantraSecond", 1);
}

$customDQHandlers["AmelioratingMantraSecond"] = function($player, $params, $lastDecision) {
    if($lastDecision === "-" || $lastDecision === "") return;
    RemoveCounters($player, $lastDecision, "wither", 2);
};

// ============================================================================
// Stabilizing Capacitance (c4sy8u49sk): memory → deck bottom loop + draw into memory
// ============================================================================
function StabilizingCapacitanceLoop($player, $count) {
    $memCards = ZoneMZIndices("myMemory");
    if(empty($memCards) || $memCards === "") {
        StabilizingCapacitanceFinish($player, $count);
        return;
    }
    DecisionQueueController::AddDecision($player, "MZMAYCHOOSE", $memCards, 1,
        tooltip:"Put_a_card_from_memory_on_bottom_of_deck?");
    DecisionQueueController::AddDecision($player, "CUSTOM", "StabilizingCapacitancePick|$count", 1);
}

function StabilizingCapacitanceFinish($player, $count) {
    if($count > 0) {
        DrawIntoMemory($player, $count);
    }
    // [Class Bonus] [Level 7+] Draw a card
    if(IsClassBonusActive($player, ["MAGE"]) && PlayerLevel($player) >= 7) {
        Draw($player, 1);
    }
}

$customDQHandlers["StabilizingCapacitancePick"] = function($player, $params, $lastDecision) {
    $count = intval($params[0]);
    if($lastDecision === "-" || $lastDecision === "") {
        StabilizingCapacitanceFinish($player, $count);
        return;
    }
    MZMove($player, $lastDecision, "myDeck");
    StabilizingCapacitanceLoop($player, $count + 1);
};

// ============================================================================
// Sylph's Envelopment (c7d7xdy3y9): banish ally, return to field rested, buff if phantasia
// ============================================================================
// ============================================================================
// Scorched Conquest (HPDawzCDdr): destroy up to three target domains
// ============================================================================
function ScorchedConquestStep($player, $remaining) {
    if($remaining <= 0) return;
    $domains = array_merge(
        ZoneSearch("myField", ["DOMAIN"]),
        ZoneSearch("theirField", ["DOMAIN"])
    );
    if(empty($domains)) return;
    $targetStr = implode("&", $domains);
    DecisionQueueController::AddDecision($player, "MZMAYCHOOSE", $targetStr, 1, tooltip:"Destroy_a_domain?_(" . $remaining . "_remaining)");
    DecisionQueueController::AddDecision($player, "CUSTOM", "ScorchedConquestDestroy|" . $remaining, 1);
}

$customDQHandlers["ScorchedConquestDestroy"] = function($player, $parts, $lastDecision) {
    $remaining = intval($parts[0]);
    if($lastDecision === "-" || $lastDecision === "") return;
    // Destroy the chosen domain (move to its owner's graveyard)
    $obj = GetZoneObject($lastDecision);
    if($obj === null) return;
    global $playerID;
    $destGrave = ($obj->Controller ?? $player) == $playerID ? "myGraveyard" : "theirGraveyard";
    MZMove($player, $lastDecision, $destGrave);
    ScorchedConquestStep($player, $remaining - 1);
};

// ============================================================================
// Regal Inquisition (KVbuQJyWsU): look at opponent hand+memory, discard any, replace from deck
// ============================================================================
function RegalInquisitionStep($player) {
    global $playerID;
    $opponent = ($player == 1) ? 2 : 1;
    $handZone = $opponent == $playerID ? "myHand" : "theirHand";
    $memoryZone = $opponent == $playerID ? "myMemory" : "theirMemory";
    $choices = [];
    $hand = GetZone($handZone);
    for($i = 0; $i < count($hand); ++$i) {
        if(!$hand[$i]->removed) $choices[] = $handZone . "-" . $i;
    }
    $memory = GetZone($memoryZone);
    for($i = 0; $i < count($memory); ++$i) {
        if(!$memory[$i]->removed) $choices[] = $memoryZone . "-" . $i;
    }
    if(empty($choices)) return;
    $targetStr = implode("&", $choices);
    DecisionQueueController::AddDecision($player, "MZMAYCHOOSE", $targetStr, 1, tooltip:"Discard_a_card_from_opponent_hand/memory?");
    DecisionQueueController::AddDecision($player, "CUSTOM", "RegalInquisitionDiscard", 1);
}

$customDQHandlers["RegalInquisitionDiscard"] = function($player, $parts, $lastDecision) {
    if($lastDecision === "-" || $lastDecision === "") return;
    global $playerID;
    $opponent = ($player == 1) ? 2 : 1;
    $gravZone = $opponent == $playerID ? "myGraveyard" : "theirGraveyard";
    $deckZone = $opponent == $playerID ? "myDeck" : "theirDeck";
    $handZone = $opponent == $playerID ? "myHand" : "theirHand";
    // Discard the chosen card to opponent's graveyard
    MZMove($opponent, $lastDecision, $gravZone);
    // Opponent reveals top of deck and puts into hand
    $deck = GetZone($deckZone);
    if(!empty($deck)) {
        Reveal($opponent, $deckZone . "-0");
        MZMove($opponent, $deckZone . "-0", $handZone);
    }
    // Continue choosing
    RegalInquisitionStep($player);
};

// ============================================================================
// Aetherwing Loading Infrastructure
// ============================================================================

/**
 * Get mzIDs of Aetherwing weapons the player controls on the field.
 * Unlike Guns/Bows, Aetherwings can hold multiple loaded cards, so we return ALL of them.
 */
function GetAetherwingWeapons($player) {
    return ZoneSearch("myField", ["WEAPON"], cardSubtypes: ["AETHERWING"]);
}

/**
 * Alias used by generated code. Returns Aetherwing weapons (all, not filtered by loaded).
 */
function GetUnloadedAetherwings($player) {
    return GetAetherwingWeapons($player);
}

/**
 * Load a card from its current zone into an Aetherwing weapon's Subcards.
 * The source card is removed from its zone; its CardID is stored in the weapon's Subcards array.
 */
function LoadIntoAetherwing($player, $sourceMZ, $wingMZ) {
    $sourceObj = GetZoneObject($sourceMZ);
    $wingObj = &GetZoneObject($wingMZ);
    if($sourceObj === null || $wingObj === null) return;
    if(!is_array($wingObj->Subcards)) $wingObj->Subcards = [];
    $wingObj->Subcards[] = $sourceObj->CardID;
    $sourceObj->removed = true;
    DecisionQueueController::CleanupRemovedCards();
}

/**
 * "You may load [cardID] into an Aetherwing weapon you control."
 * Finds the most recent copy of $cardID in the player's graveyard and offers an optional load.
 */
function MayLoadIntoAetherwing($player, $cardID) {
    $wings = GetAetherwingWeapons($player);
    if(empty($wings)) return;
    $gy = GetZone("myGraveyard");
    $sourceMZ = null;
    for($i = count($gy) - 1; $i >= 0; --$i) {
        if(!$gy[$i]->removed && $gy[$i]->CardID === $cardID) {
            $sourceMZ = "myGraveyard-" . $i;
            break;
        }
    }
    if($sourceMZ === null) return;
    if(count($wings) === 1) {
        DecisionQueueController::StoreVariable("MLAE_sourceMZ", $sourceMZ);
        DecisionQueueController::StoreVariable("MLAE_wingMZ", $wings[0]);
        DecisionQueueController::AddDecision($player, "YESNO", "-", 1, tooltip:"Load_into_Aetherwing_weapon?");
        DecisionQueueController::AddDecision($player, "CUSTOM", "MayLoadAetherwingConfirm", 1);
        return;
    }
    DecisionQueueController::StoreVariable("MLAE_sourceMZ", $sourceMZ);
    DecisionQueueController::AddDecision($player, "MZMAYCHOOSE", implode("&", $wings), 1, tooltip:"Load_into_Aetherwing_weapon?");
    DecisionQueueController::AddDecision($player, "CUSTOM", "LoadAetherwingSelect", 1);
}

/**
 * Load a card from graveyard into an Aetherwing weapon (mandatory, no may).
 * Used when loading is not optional.
 */
function LoadCardIntoAetherwingFromGY($player, $cardID) {
    $wings = GetAetherwingWeapons($player);
    if(empty($wings)) return;
    $gy = GetZone("myGraveyard");
    $sourceMZ = null;
    for($i = count($gy) - 1; $i >= 0; --$i) {
        if(!$gy[$i]->removed && $gy[$i]->CardID === $cardID) {
            $sourceMZ = "myGraveyard-" . $i;
            break;
        }
    }
    if($sourceMZ === null) return;
    if(count($wings) === 1) {
        LoadIntoAetherwing($player, $sourceMZ, $wings[0]);
        return;
    }
    DecisionQueueController::StoreVariable("MLAE_sourceMZ", $sourceMZ);
    DecisionQueueController::AddDecision($player, "MZCHOOSE", implode("&", $wings), 1, tooltip:"Choose_Aetherwing_weapon");
    DecisionQueueController::AddDecision($player, "CUSTOM", "LoadAetherwingSelect", 1);
}

$customDQHandlers["LoadAetherwingSelect"] = function($player, $parts, $lastDecision) {
    $sourceMZ = DecisionQueueController::GetVariable("MLAE_sourceMZ");
    if($lastDecision !== "-" && $lastDecision !== "" && $lastDecision !== "PASS"
       && $sourceMZ !== null && $sourceMZ !== "") {
        LoadIntoAetherwing($player, $sourceMZ, $lastDecision);
    }
};

$customDQHandlers["MayLoadAetherwingConfirm"] = function($player, $parts, $lastDecision) {
    if($lastDecision !== "YES") return;
    $sourceMZ = DecisionQueueController::GetVariable("MLAE_sourceMZ");
    $wingMZ = DecisionQueueController::GetVariable("MLAE_wingMZ");
    if($sourceMZ !== null && $wingMZ !== null) {
        LoadIntoAetherwing($player, $sourceMZ, $wingMZ);
    }
};

/**
 * Count Aethercharge cards currently in the intent zone.
 */
function CountAetherchargesInIntent($player) {
    $count = 0;
    $intentCards = GetIntentCards($player);
    foreach($intentCards as $mzID) {
        $obj = GetZoneObject($mzID);
        if($obj !== null && PropertyContains(CardSubtypes($obj->CardID), "AETHERCHARGE")) {
            $count++;
        }
    }
    return $count;
}

/**
 * Check if the player's champion is Diana (any level).
 */
function IsDianaBonus($player) {
    return ChampionHasInLineage($player, "m7f6r8f3y8")   // Diana, Aether Dilettante (L1)
        || ChampionHasInLineage($player, "wiztyu6o24")    // Diana, Judgment's Arrow (L2)
        || ChampionHasInLineage($player, "7ozuj68m69")    // Diana, Deadly Duelist
        || ChampionHasInLineage($player, "o0qtb31x97")    // Diana, Cursebreaker
        || ChampionHasInLineage($player, "e3z4pyx8bd");   // Diana, Keen Huntress
}

/**
 * Track Aethercharge activation count per player per turn.
 */
function AetherchargeActivatedCount($player) {
    $_ti = json_decode(GetMacroTurnIndex() ?: '{}', true) ?: [];
    return $_ti["AetherchargeActivated"][$player] ?? 0;
}

function IncrementAetherchargeCount($player) {
    $_ti = json_decode(GetMacroTurnIndex() ?: '{}', true) ?: [];
    $_ti["AetherchargeActivated"][$player] = ($_ti["AetherchargeActivated"][$player] ?? 0) + 1;
    SetMacroTurnIndex(json_encode($_ti));
}

// ============================================================================
// Diana L2 (wiztyu6o24): Enter — load up to 2 Aethercharge from hand/memory
// into Aetherwing, draw into memory per card loaded
// ============================================================================
function DianaL2LoadSequence($player) {
    $wings = GetAetherwingWeapons($player);
    if(empty($wings)) return;
    // Gather Aethercharge from hand + memory
    $targets = [];
    $hand = GetZone("myHand");
    for($i = 0; $i < count($hand); ++$i) {
        if($hand[$i]->removed) continue;
        if(PropertyContains(CardSubtypes($hand[$i]->CardID), "AETHERCHARGE")) {
            $targets[] = "myHand-" . $i;
        }
    }
    $mem = GetZone("myMemory");
    for($i = 0; $i < count($mem); ++$i) {
        if($mem[$i]->removed) continue;
        if(PropertyContains(CardSubtypes($mem[$i]->CardID), "AETHERCHARGE")) {
            $targets[] = "myMemory-" . $i;
        }
    }
    if(empty($targets)) return;
    if(count($wings) === 1) {
        DecisionQueueController::StoreVariable("DianaL2Wing", $wings[0]);
        DecisionQueueController::StoreVariable("DianaL2Loaded", "0");
        $targetStr = implode("&", $targets);
        DecisionQueueController::AddDecision($player, "MZMAYCHOOSE", $targetStr, 1, tooltip:"Load_Aethercharge_into_Aetherwing");
        DecisionQueueController::AddDecision($player, "CUSTOM", "DianaL2LoadChoice|1", 1);
    } else {
        // Choose weapon first
        DecisionQueueController::StoreVariable("DianaL2Loaded", "0");
        $wingStr = implode("&", $wings);
        DecisionQueueController::AddDecision($player, "MZCHOOSE", $wingStr, 1, tooltip:"Choose_Aetherwing_weapon");
        DecisionQueueController::AddDecision($player, "CUSTOM", "DianaL2WeaponChosen", 1);
    }
}

$customDQHandlers["DianaL2WeaponChosen"] = function($player, $parts, $lastDecision) {
    if($lastDecision === "-" || $lastDecision === "" || $lastDecision === "PASS") return;
    DecisionQueueController::StoreVariable("DianaL2Wing", $lastDecision);
    // Now offer Aethercharge from hand/memory
    $targets = [];
    $hand = GetZone("myHand");
    for($i = 0; $i < count($hand); ++$i) {
        if($hand[$i]->removed) continue;
        if(PropertyContains(CardSubtypes($hand[$i]->CardID), "AETHERCHARGE")) {
            $targets[] = "myHand-" . $i;
        }
    }
    $mem = GetZone("myMemory");
    for($i = 0; $i < count($mem); ++$i) {
        if($mem[$i]->removed) continue;
        if(PropertyContains(CardSubtypes($mem[$i]->CardID), "AETHERCHARGE")) {
            $targets[] = "myMemory-" . $i;
        }
    }
    if(empty($targets)) return;
    $targetStr = implode("&", $targets);
    DecisionQueueController::AddDecision($player, "MZMAYCHOOSE", $targetStr, 1, tooltip:"Load_Aethercharge_into_Aetherwing");
    DecisionQueueController::AddDecision($player, "CUSTOM", "DianaL2LoadChoice|1", 1);
};

$customDQHandlers["DianaL2LoadChoice"] = function($player, $parts, $lastDecision) {
    $round = intval($parts[0]);
    if($lastDecision === "-" || $lastDecision === "" || $lastDecision === "PASS") {
        // Done loading — draw into memory for each card loaded
        $loaded = intval(DecisionQueueController::GetVariable("DianaL2Loaded") ?? "0");
        if($loaded > 0) DrawIntoMemory($player, $loaded);
        return;
    }
    $wingMZ = DecisionQueueController::GetVariable("DianaL2Wing");
    if($wingMZ === null) return;
    LoadIntoAetherwing($player, $lastDecision, $wingMZ);
    $loaded = intval(DecisionQueueController::GetVariable("DianaL2Loaded") ?? "0") + 1;
    DecisionQueueController::StoreVariable("DianaL2Loaded", strval($loaded));
    if($round >= 2) {
        // Max 2 loads reached — draw into memory
        DrawIntoMemory($player, $loaded);
        return;
    }
    // Offer second load
    $targets = [];
    $hand = GetZone("myHand");
    for($i = 0; $i < count($hand); ++$i) {
        if($hand[$i]->removed) continue;
        if(PropertyContains(CardSubtypes($hand[$i]->CardID), "AETHERCHARGE")) {
            $targets[] = "myHand-" . $i;
        }
    }
    $mem = GetZone("myMemory");
    for($i = 0; $i < count($mem); ++$i) {
        if($mem[$i]->removed) continue;
        if(PropertyContains(CardSubtypes($mem[$i]->CardID), "AETHERCHARGE")) {
            $targets[] = "myMemory-" . $i;
        }
    }
    if(empty($targets)) {
        DrawIntoMemory($player, $loaded);
        return;
    }
    $targetStr = implode("&", $targets);
    DecisionQueueController::AddDecision($player, "MZMAYCHOOSE", $targetStr, 1, tooltip:"Load_another_Aethercharge");
    DecisionQueueController::AddDecision($player, "CUSTOM", "DianaL2LoadChoice|2", 1);
};

// ============================================================================
// Alacritous Huntress (7i24g0nbxz): Enter — reveal Aethercharge from hand,
// discard or load into Aetherwing, draw 1
// ============================================================================
function AlacritousHuntressResolve($player, $chosenMZ) {
    $wings = GetAetherwingWeapons($player);
    if(empty($wings)) {
        // No Aetherwings — must discard
        MZMove($player, $chosenMZ, "myGraveyard");
        Draw($player, 1);
        return;
    }
    DecisionQueueController::StoreVariable("AH_chosenMZ", $chosenMZ);
    DecisionQueueController::AddDecision($player, "YESNO", "Load_into_Aetherwing?", 1);
    DecisionQueueController::AddDecision($player, "CUSTOM", "AlacritousLoadOrDiscard", 1);
}

$customDQHandlers["AlacritousLoadOrDiscard"] = function($player, $parts, $lastDecision) {
    $chosenMZ = DecisionQueueController::GetVariable("AH_chosenMZ");
    if($chosenMZ === null || $chosenMZ === "") return;
    if($lastDecision === "YES") {
        $wings = GetAetherwingWeapons($player);
        if(!empty($wings)) {
            if(count($wings) === 1) {
                LoadIntoAetherwing($player, $chosenMZ, $wings[0]);
            } else {
                DecisionQueueController::StoreVariable("MLAE_sourceMZ", $chosenMZ);
                $wingStr = implode("&", $wings);
                DecisionQueueController::AddDecision($player, "MZCHOOSE", $wingStr, 1, tooltip:"Choose_Aetherwing_weapon");
                DecisionQueueController::AddDecision($player, "CUSTOM", "LoadAetherwingSelect", 1);
            }
        } else {
            MZMove($player, $chosenMZ, "myGraveyard");
        }
    } else {
        MZMove($player, $chosenMZ, "myGraveyard");
    }
    Draw($player, 1);
};

// ============================================================================
// Blazing Cindercharge (e48axaql3n): CB load self + up to 2 fire Aethercharge
// ============================================================================
function BlazeLoadIntoAetherwing($player) {
    $wings = GetAetherwingWeapons($player);
    if(empty($wings)) return;
    // Find self (Blazing Cindercharge) in GY
    $gy = GetZone("myGraveyard");
    $selfMZ = null;
    for($i = count($gy) - 1; $i >= 0; --$i) {
        if(!$gy[$i]->removed && $gy[$i]->CardID === "e48axaql3n") {
            $selfMZ = "myGraveyard-" . $i;
            break;
        }
    }
    if($selfMZ === null) return;
    if(count($wings) === 1) {
        LoadIntoAetherwing($player, $selfMZ, $wings[0]);
        DecisionQueueController::StoreVariable("BlazeWing", $wings[0]);
        DecisionQueueController::AddDecision($player, "CUSTOM", "BlazeContinueFireLoads|2", 1);
    } else {
        DecisionQueueController::StoreVariable("BlazeSourceMZ", $selfMZ);
        $wingStr = implode("&", $wings);
        DecisionQueueController::AddDecision($player, "MZCHOOSE", $wingStr, 1, tooltip:"Choose_Aetherwing_weapon");
        DecisionQueueController::AddDecision($player, "CUSTOM", "BlazeWeaponChosen|2", 1);
    }
}

$customDQHandlers["BlazeWeaponChosen"] = function($player, $parts, $lastDecision) {
    if($lastDecision === "-" || $lastDecision === "" || $lastDecision === "PASS") return;
    $remaining = intval($parts[0]);
    $selfMZ = DecisionQueueController::GetVariable("BlazeSourceMZ");
    if($selfMZ !== null) {
        LoadIntoAetherwing($player, $selfMZ, $lastDecision);
    }
    DecisionQueueController::StoreVariable("BlazeWing", $lastDecision);
    DecisionQueueController::AddDecision($player, "CUSTOM", "BlazeContinueFireLoads|" . $remaining, 1);
};

$customDQHandlers["BlazeContinueFireLoads"] = function($player, $parts, $lastDecision) {
    $remaining = intval($parts[0]);
    if($remaining <= 0) return;
    $wingMZ = DecisionQueueController::GetVariable("BlazeWing");
    if($wingMZ === null || $wingMZ === "") return;
    $wingObj = GetZoneObject($wingMZ);
    if($wingObj === null) return;
    // Find fire Aethercharge in GY
    $gy = GetZone("myGraveyard");
    $fireAC = [];
    foreach($gy as $i => $obj) {
        if($obj->removed) continue;
        if(CardElement($obj->CardID) !== "FIRE") continue;
        $subtypes = CardSubtypes($obj->CardID);
        if(!in_array("AETHERCHARGE", $subtypes)) continue;
        $fireAC[] = "myGraveyard-" . $i;
    }
    if(empty($fireAC)) return;
    $targetStr = implode("&", $fireAC);
    DecisionQueueController::AddDecision($player, "MZMAYCHOOSE", $targetStr, 1, tooltip:"Load_fire_Aethercharge_into_Aetherwing");
    DecisionQueueController::AddDecision($player, "CUSTOM", "BlazeFireLoadChoice|" . ($remaining - 1), 1);
};

$customDQHandlers["BlazeFireLoadChoice"] = function($player, $parts, $lastDecision) {
    if($lastDecision === "-" || $lastDecision === "" || $lastDecision === "PASS") return;
    $remaining = intval($parts[0]);
    $wingMZ = DecisionQueueController::GetVariable("BlazeWing");
    if($wingMZ === null) return;
    LoadIntoAetherwing($player, $lastDecision, $wingMZ);
    if($remaining > 0) {
        DecisionQueueController::AddDecision($player, "CUSTOM", "BlazeContinueFireLoads|" . $remaining, 1);
    }
};

// ============================================================================
// Legion's Wingspan (iuixf9rdmu): [Diana Bonus] On Attack — for each wind
// element card in intent, choose an ally you control → +1 power
// ============================================================================
function LegionsWingspanBuffLoop($player, $count) {
    if($count <= 0) return;
    $allies = ZoneSearch("myField", ["ALLY"]);
    if(empty($allies)) return;
    $allyStr = implode("&", $allies);
    DecisionQueueController::StoreVariable("LWB_remaining", strval($count));
    DecisionQueueController::AddDecision($player, "MZCHOOSE", $allyStr, 1, tooltip:"Choose_ally_for_+1_power");
    DecisionQueueController::AddDecision($player, "CUSTOM", "LegionsWingspanBuff", 1);
}

$customDQHandlers["LegionsWingspanBuff"] = function($player, $parts, $lastDecision) {
    if($lastDecision === "-" || $lastDecision === "" || $lastDecision === "PASS") return;
    AddTurnEffect($lastDecision, "iuixf9rdmu_POWER");
    $remaining = intval(DecisionQueueController::GetVariable("LWB_remaining")) - 1;
    if($remaining > 0) {
        LegionsWingspanBuffLoop($player, $remaining);
    }
};

// ============================================================================
// Salamander's Breath (mob9nu6lal): [Diana Bonus] On Attack — may banish up to
// X fire from GY (X = Aethercharge in intent). +1 power per card banished.
// ============================================================================
function SalamandersBreathBanishLoop($player, $maxBanish, $banishedSoFar) {
    $fireGY = ZoneSearch("myGraveyard", cardElements: ["FIRE"]);
    if(empty($fireGY) || $maxBanish <= 0) {
        if($banishedSoFar > 0) {
            $champMZ = FindChampionMZ($player);
            if($champMZ !== null) {
                for($i = 0; $i < $banishedSoFar; ++$i) {
                    AddTurnEffect($champMZ, "mob9nu6lal_POWER");
                }
            }
        }
        return;
    }
    DecisionQueueController::StoreVariable("SB_maxRemaining", strval($maxBanish));
    DecisionQueueController::StoreVariable("SB_banished", strval($banishedSoFar));
    $fireStr = implode("&", $fireGY);
    DecisionQueueController::AddDecision($player, "MZMAYCHOOSE", $fireStr, 1, tooltip:"Banish_fire_card_for_+1_power");
    DecisionQueueController::AddDecision($player, "CUSTOM", "SalamandersBanish", 1);
}

$customDQHandlers["SalamandersBanish"] = function($player, $parts, $lastDecision) {
    $maxRemaining = intval(DecisionQueueController::GetVariable("SB_maxRemaining"));
    $banished = intval(DecisionQueueController::GetVariable("SB_banished"));
    if($lastDecision === "-" || $lastDecision === "" || $lastDecision === "PASS") {
        if($banished > 0) {
            $champMZ = FindChampionMZ($player);
            if($champMZ !== null) {
                for($i = 0; $i < $banished; ++$i) AddTurnEffect($champMZ, "mob9nu6lal_POWER");
            }
        }
        return;
    }
    MZMove($player, $lastDecision, "myBanish");
    $banished++;
    $maxRemaining--;
    SalamandersBreathBanishLoop($player, $maxRemaining, $banished);
};

// ============================================================================
// Manaflare Barrage (a8mmiv2ptn): may load into Aetherwing weapon
// ============================================================================
function ManaflareBarrageLoad($player, $mzID) {
    $weapons = ZoneSearch("myField", ["WEAPON"], cardSubtypes: ["AETHERWING"]);
    $unloaded = [];
    foreach($weapons as $mz) {
        $obj = GetZoneObject($mz);
        if($obj !== null && !IsGunLoaded($obj)) $unloaded[] = $mz;
    }
    if(empty($unloaded)) return;
    $bowStr = implode("&", $unloaded);
    DecisionQueueController::AddDecision($player, "MZMAYCHOOSE", $bowStr, 1, tooltip:"Load_Manaflare_Barrage_into_Aetherwing?");
    DecisionQueueController::AddDecision($player, "CUSTOM", "LoadArrow|" . $mzID, 1);
}

function SylphEnvelopmentExecute($player, $target) {
    $obj = GetZoneObject($target);
    if($obj === null) return;
    $cardID = $obj->CardID;
    $isPhantasia = PropertyContains(EffectiveCardType($obj), "PHANTASIA");
    OnLeaveField($player, $target);
    MZMove($player, $target, "myBanish");
    // Find the banished card and return it to field
    $banish = GetZone("myBanish");
    for($i = count($banish) - 1; $i >= 0; --$i) {
        if(!$banish[$i]->removed && $banish[$i]->CardID === $cardID) {
            $returnedObj = MZMove($player, "myBanish-" . $i, "myField");
            $returnedObj->Status = 1; // Rested
            if($isPhantasia) {
                $field = &GetField($player);
                AddCounters($player, "myField-" . (count($field) - 1), "buff", 1);
            }
            break;
        }
    }
}

// ============================================================================
// Death Essence Amulet (ddag7ue0k7) — DQ handlers
// ============================================================================
$customDQHandlers["DeathEssenceAmuletBanish"] = function($player, $parts, $lastDecision) {
    if($lastDecision !== "YES") return;
    global $playerID;
    $idx = intval($parts[0]);
    $zone = $player == $playerID ? "myField" : "theirField";
    $obj = GetZoneObject($zone . "-" . $idx);
    if($obj === null || $obj->CardID !== "ddag7ue0k7") return;
    OnLeaveField($player, $zone . "-" . $idx);
    MZMove($player, $zone . "-" . $idx, "myBanish");
    DecisionQueueController::CleanupRemovedCards();
    // Choose hand or memory
    DecisionQueueController::AddDecision($player, "YESNO", "-", 1, tooltip:"Look_at_opponent's_hand?_(No_=_memory)");
    DecisionQueueController::AddDecision($player, "CUSTOM", "DeathEssenceAmuletChooseZone", 1);
};

$customDQHandlers["DeathEssenceAmuletChooseZone"] = function($player, $parts, $lastDecision) {
    global $playerID;
    $opponent = ($player == 1) ? 2 : 1;
    if($lastDecision === "YES") {
        $opZone = $opponent == $playerID ? "myHand" : "theirHand";
    } else {
        $opZone = $opponent == $playerID ? "myMemory" : "theirMemory";
    }
    $cards = ZoneSearch($opZone);
    if(empty($cards)) return;
    $cardStr = implode("&", $cards);
    DecisionQueueController::AddDecision($player, "MZCHOOSE", $cardStr, 1, "Choose_a_card_to_discard");
    DecisionQueueController::AddDecision($player, "CUSTOM", "DeathEssenceAmuletDiscard", 1);
};

$customDQHandlers["DeathEssenceAmuletDiscard"] = function($player, $parts, $lastDecision) {
    if($lastDecision === "-" || $lastDecision === "") return;
    global $playerID;
    $opponent = ($player == 1) ? 2 : 1;
    $destGrav = $opponent == $playerID ? "myGraveyard" : "theirGraveyard";
    MZMove($opponent, $lastDecision, $destGrav);
};

// ============================================================================
// Poisonous Breezecap (e3ldc3r8j7) — suppress replacement DQ handler
// ============================================================================
$customDQHandlers["PoisonousBreezecapSuppressReplace"] = function($player, $parts, $lastDecision) {
    $mzCard = $parts[0];
    if($lastDecision === "YES") {
        // Sacrifice instead: move to graveyard
        $obj = GetZoneObject($mzCard);
        if($obj === null) return;
        $controller = $obj->Controller;
        OnLeaveField($controller, $mzCard);
        MZMove($controller, $mzCard, "myGraveyard");
        DecisionQueueController::CleanupRemovedCards();
        // Each opponent banishes a card at random from their memory
        $opponent = ($controller == 1) ? 2 : 1;
        global $playerID;
        $opMemZone = $opponent == $playerID ? "myMemory" : "theirMemory";
        $opBanishZone = $opponent == $playerID ? "myBanish" : "theirBanish";
        $memCards = ZoneSearch($opMemZone);
        if(!empty($memCards)) {
            $randomIdx = array_rand($memCards);
            MZMove($opponent, $memCards[$randomIdx], $opBanishZone);
        }
    } else {
        // Proceed with normal suppress
        SuppressAlly($player, $mzCard, skipReplacementCheck: true);
    }
};

function CountReserveSources($player) {
    global $playerID;
    $available = count(GetHand($player));
    $zone = $player == $playerID ? "myField" : "theirField";
    $field = GetZone($zone);
    foreach($field as $fObj) {
        if($fObj->removed) continue;
        if(isset($fObj->Status) && $fObj->Status == 2 && HasReservable($fObj)) {
            ++$available;
        }
    }
    return $available;
}

function ViridescentAetherstreakTargets($player) {
    $targets = array_merge(
        ZoneSearch("myField", ["ALLY", "CHAMPION"]),
        ZoneSearch("theirField", ["ALLY", "CHAMPION"])
    );
    return FilterSpellshroudTargets($targets);
}

function ViridescentAetherstreakAetherwings($player) {
    $weapons = ZoneSearch("myField", ["WEAPON"], cardSubtypes: ["AETHERWING"]);
    $unloaded = [];
    foreach($weapons as $mz) {
        $obj = GetZoneObject($mz);
        if($obj !== null && !IsGunLoaded($obj)) {
            $unloaded[] = $mz;
        }
    }
    return $unloaded;
}

function ViridescentAetherstreakFinalize($player) {
    $target = DecisionQueueController::GetVariable("VAE_target");
    if($target !== null && $target !== "" && $target !== "-") {
        BecomeDistant($player, $target);
    }
    if(DecisionQueueController::GetVariable("VAE_prevent") === "YES") {
        $units = array_merge(
            ZoneSearch("myField", ["ALLY", "CHAMPION"]),
            ZoneSearch("theirField", ["ALLY", "CHAMPION"])
        );
        foreach($units as $mz) {
            $obj = GetZoneObject($mz);
            if($obj !== null && IsDistant($obj)) {
                AddTurnEffect($mz, "PREVENT_ALL_2");
            }
        }
    }
}

function ViridescentAetherstreakAskModeB($player) {
    $chosen = intval(DecisionQueueController::GetVariable("VAE_count"));
    if($chosen >= 2) {
        ViridescentAetherstreakFinalize($player);
        return;
    }
    DecisionQueueController::AddDecision($player, "YESNO", "-", 1, tooltip:"Choose_mode:_prevent_next_2_damage_to_each_distant_unit");
    DecisionQueueController::AddDecision($player, "CUSTOM", "ViridescentAetherstreakModeB", 1);
}

function ViridescentAetherstreakAskModeC($player) {
    $chosen = intval(DecisionQueueController::GetVariable("VAE_count"));
    if($chosen >= 2) {
        ViridescentAetherstreakFinalize($player);
        return;
    }
    $weapons = ViridescentAetherstreakAetherwings($player);
    if(empty($weapons)) {
        ViridescentAetherstreakFinalize($player);
        return;
    }
    DecisionQueueController::AddDecision($player, "YESNO", "-", 1, tooltip:"Choose_mode:_load_into_an_Aetherwing_weapon");
    DecisionQueueController::AddDecision($player, "CUSTOM", "ViridescentAetherstreakModeC", 1);
}

function ViridescentAetherstreakStart($player, $mzID) {
    DecisionQueueController::StoreVariable("VAE_source", $mzID);
    DecisionQueueController::StoreVariable("VAE_count", "0");
    DecisionQueueController::StoreVariable("VAE_target", "-");
    DecisionQueueController::StoreVariable("VAE_prevent", "NO");
    $targets = ViridescentAetherstreakTargets($player);
    if(!empty($targets)) {
        DecisionQueueController::AddDecision($player, "YESNO", "-", 1, tooltip:"Choose_mode:_up_to_one_target_unit_becomes_distant");
        DecisionQueueController::AddDecision($player, "CUSTOM", "ViridescentAetherstreakModeA", 1);
    } else {
        ViridescentAetherstreakAskModeB($player);
    }
}

$customDQHandlers["ViridescentAetherstreakModeA"] = function($player, $parts, $lastDecision) {
    if($lastDecision === "YES") {
        DecisionQueueController::StoreVariable("VAE_count", strval(intval(DecisionQueueController::GetVariable("VAE_count")) + 1));
        $targets = ViridescentAetherstreakTargets($player);
        if(!empty($targets)) {
            DecisionQueueController::AddDecision($player, "MZMAYCHOOSE", implode("&", $targets), 1, tooltip:"Choose_up_to_one_target_unit");
            DecisionQueueController::AddDecision($player, "CUSTOM", "ViridescentAetherstreakTargetA", 1);
            return;
        }
    }
    ViridescentAetherstreakAskModeB($player);
};

$customDQHandlers["ViridescentAetherstreakTargetA"] = function($player, $parts, $lastDecision) {
    if($lastDecision !== "-" && $lastDecision !== "" && $lastDecision !== "PASS") {
        DecisionQueueController::StoreVariable("VAE_target", $lastDecision);
    }
    ViridescentAetherstreakAskModeB($player);
};

$customDQHandlers["ViridescentAetherstreakModeB"] = function($player, $parts, $lastDecision) {
    if($lastDecision === "YES") {
        DecisionQueueController::StoreVariable("VAE_prevent", "YES");
        DecisionQueueController::StoreVariable("VAE_count", strval(intval(DecisionQueueController::GetVariable("VAE_count")) + 1));
    }
    ViridescentAetherstreakAskModeC($player);
};

$customDQHandlers["ViridescentAetherstreakModeC"] = function($player, $parts, $lastDecision) {
    if($lastDecision !== "YES") {
        ViridescentAetherstreakFinalize($player);
        return;
    }
    $weapons = ViridescentAetherstreakAetherwings($player);
    if(empty($weapons)) {
        ViridescentAetherstreakFinalize($player);
        return;
    }
    DecisionQueueController::StoreVariable("VAE_count", strval(intval(DecisionQueueController::GetVariable("VAE_count")) + 1));
    DecisionQueueController::AddDecision($player, "MZCHOOSE", implode("&", $weapons), 1, tooltip:"Choose_Aetherwing_weapon_to_load");
    DecisionQueueController::AddDecision($player, "CUSTOM", "ViridescentAetherstreakLoad", 1);
};

$customDQHandlers["ViridescentAetherstreakLoad"] = function($player, $parts, $lastDecision) {
    $sourceMZ = DecisionQueueController::GetVariable("VAE_source");
    if($lastDecision !== "-" && $lastDecision !== "" && $sourceMZ !== null && $sourceMZ !== "") {
        LoadArrowIntoBow($player, $sourceMZ, $lastDecision);
    }
    ViridescentAetherstreakFinalize($player);
};

$customDQHandlers["RafalesSlashPayment"] = function($player, $parts, $lastDecision) {
    if($lastDecision !== "YES") return;
    if(CountReserveSources($player) < 3) return;
    for($i = 0; $i < 3; ++$i) {
        DecisionQueueController::AddDecision($player, "CUSTOM", "ReserveCard", 1);
    }
    DecisionQueueController::AddDecision($player, "CUSTOM", "RafalesSlashSummon", 1);
};

$customDQHandlers["RafalesSlashSummon"] = function($player, $parts, $lastDecision) {
    MZAddZone($player, "myField", "L67r0GlRHR");
    $field = &GetField($player);
    $field[count($field) - 1]->Status = 1;
};

// ============================================================================
// Malevolent Vow (up6fw61vf1): discard up to 3 cards, recover 3+3X, put on lineage
// ============================================================================
function MalevolentVowFinish($player) {
    $count = intval(DecisionQueueController::GetVariable("malevolentVowDiscardCount") ?? "0");
    RecoverChampion($player, 3 + 3 * $count);
    $gy = GetZone("myGraveyard");
    for($gi = count($gy) - 1; $gi >= 0; --$gi) {
        if(!$gy[$gi]->removed && $gy[$gi]->CardID === "up6fw61vf1") {
            MZRemove($player, "myGraveyard-" . $gi);
            DecisionQueueController::CleanupRemovedCards();
            break;
        }
    }
    AddToChampionLineage($player, "up6fw61vf1");
}

$customDQHandlers["MalevolentVow1"] = function($player, $parts, $lastDecision) {
    if($lastDecision !== "-" && $lastDecision !== "" && $lastDecision !== "PASS") {
        DoDiscardCard($player, $lastDecision);
        DecisionQueueController::CleanupRemovedCards();
        $count = intval(DecisionQueueController::GetVariable("malevolentVowDiscardCount") ?? "0") + 1;
        DecisionQueueController::StoreVariable("malevolentVowDiscardCount", strval($count));
        $hand = ZoneSearch("myHand");
        if(!empty($hand)) {
            $handStr = implode("&", $hand);
            DecisionQueueController::AddDecision($player, "MZMAYCHOOSE", $handStr, 1, tooltip:"Discard_a_card_(Malevolent_Vow_2/3)");
            DecisionQueueController::AddDecision($player, "CUSTOM", "MalevolentVow2", 1);
            return;
        }
    }
    MalevolentVowFinish($player);
};

$customDQHandlers["MalevolentVow2"] = function($player, $parts, $lastDecision) {
    if($lastDecision !== "-" && $lastDecision !== "" && $lastDecision !== "PASS") {
        DoDiscardCard($player, $lastDecision);
        DecisionQueueController::CleanupRemovedCards();
        $count = intval(DecisionQueueController::GetVariable("malevolentVowDiscardCount") ?? "0") + 1;
        DecisionQueueController::StoreVariable("malevolentVowDiscardCount", strval($count));
        $hand = ZoneSearch("myHand");
        if(!empty($hand)) {
            $handStr = implode("&", $hand);
            DecisionQueueController::AddDecision($player, "MZMAYCHOOSE", $handStr, 1, tooltip:"Discard_a_card_(Malevolent_Vow_3/3)");
            DecisionQueueController::AddDecision($player, "CUSTOM", "MalevolentVow3", 1);
            return;
        }
    }
    MalevolentVowFinish($player);
};

$customDQHandlers["MalevolentVow3"] = function($player, $parts, $lastDecision) {
    if($lastDecision !== "-" && $lastDecision !== "" && $lastDecision !== "PASS") {
        DoDiscardCard($player, $lastDecision);
        DecisionQueueController::CleanupRemovedCards();
        $count = intval(DecisionQueueController::GetVariable("malevolentVowDiscardCount") ?? "0") + 1;
        DecisionQueueController::StoreVariable("malevolentVowDiscardCount", strval($count));
    }
    MalevolentVowFinish($player);
};

// ============================================================================
// Rile the Abyss (ye7f7o5yut): draw 1; discard up to 2 Specter cards; draw 1 per discard
// ============================================================================
$customDQHandlers["RileTheAbyss1"] = function($player, $parts, $lastDecision) {
    if($lastDecision !== "-" && $lastDecision !== "" && $lastDecision !== "PASS") {
        DoDiscardCard($player, $lastDecision);
        DecisionQueueController::CleanupRemovedCards();
        Draw($player, 1);
        $specters = ZoneSearch("myHand", cardSubtypes: ["SPECTER"]);
        if(!empty($specters)) {
            $specterStr = implode("&", $specters);
            DecisionQueueController::AddDecision($player, "MZMAYCHOOSE", $specterStr, 1, tooltip:"Discard_another_Specter_(Rile_the_Abyss)");
            DecisionQueueController::AddDecision($player, "CUSTOM", "RileTheAbyss2", 1);
        }
    }
};

$customDQHandlers["RileTheAbyss2"] = function($player, $parts, $lastDecision) {
    if($lastDecision !== "-" && $lastDecision !== "" && $lastDecision !== "PASS") {
        DoDiscardCard($player, $lastDecision);
        DecisionQueueController::CleanupRemovedCards();
        Draw($player, 1);
    }
};

// ============================================================================
// Imperious Galebind (2goaqn7ImP): suppress up to 3 target allies, items, or weapons
// ============================================================================
function ImperiousGalebindLoop($player, $remaining) {
    if($remaining <= 0) return;
    $targets = array_merge(
        ZoneSearch("myField", ["ALLY"]),
        ZoneSearch("theirField", ["ALLY"]),
        ZoneSearch("myField", ["ITEM", "REGALIA"]),
        ZoneSearch("theirField", ["ITEM", "REGALIA"]),
        ZoneSearch("myField", ["WEAPON"]),
        ZoneSearch("theirField", ["WEAPON"])
    );
    $targets = FilterSpellshroudTargets($targets);
    $targets = array_unique($targets);
    if(empty($targets)) return;
    $targetStr = implode("&", $targets);
    DecisionQueueController::AddDecision($player, "MZMAYCHOOSE", $targetStr, 1, tooltip:"Suppress_a_target_(" . $remaining . "_remaining)");
    DecisionQueueController::AddDecision($player, "CUSTOM", "ImperiousGalebind|" . $remaining, 1);
}

$customDQHandlers["ImperiousGalebind"] = function($player, $parts, $lastDecision) {
    $remaining = intval($parts[0]);
    if($lastDecision === "PASS" || $lastDecision === "-" || empty($lastDecision)) return;
    SuppressAlly($player, $lastDecision);
    DecisionQueueController::CleanupRemovedCards();
    ImperiousGalebindLoop($player, $remaining - 1);
};

// ============================================================================
// Bandersnatch, Frumious Foe (4yqL9xtzVi): activated ability — sacrifice ally for +2 POWER + cleave
// ============================================================================
$customDQHandlers["BandersnatchSacrifice"] = function($player, $parts, $lastDecision) {
    if($lastDecision === "PASS" || $lastDecision === "-" || empty($lastDecision)) return;
    $mzID = $parts[0]; // mzID of Bandersnatch
    DoSacrificeFighter($player, $lastDecision);
    DecisionQueueController::CleanupRemovedCards();
    // Re-locate Bandersnatch after sacrifice (indices may have shifted)
    global $playerID;
    $zone = $player == $playerID ? "myField" : "theirField";
    $field = GetZone($zone);
    for($i = 0; $i < count($field); $i++) {
        if(!$field[$i]->removed && $field[$i]->CardID === "4yqL9xtzVi") {
            $newMZ = $zone . "-" . $i;
            AddTurnEffect($newMZ, "4yqL9xtzVi_POWER");
            AddTurnEffect($newMZ, "4yqL9xtzVi_CLEAVE");
            break;
        }
    }
};

// ============================================================================
// Ethereal Absorption (4zEOAaLdap): additional cost — return a regalia to material deck
// ============================================================================
$customDQHandlers["EtherealAbsorptionCost"] = function($player, $parts, $lastDecision) {
    if($lastDecision === "PASS" || $lastDecision === "-" || empty($lastDecision)) return;
    $obj = GetZoneObject($lastDecision);
    if($obj === null) return;
    OnLeaveField($player, $lastDecision);
    MZMove($player, $lastDecision, "myMaterial");
    Draw($player, 1);
    AddPrepCounter($player, 2);
};

// ============================================================================
// Twisted Verdict (ANrnYgZNgq): opponent looks at top 5, chooses 2 for your memory
// ============================================================================
function TwistedVerdictStart($player) {
    $deck = &GetDeck($player);
    if(empty($deck)) return;
    $count = min(5, count($deck));
    // Move top N cards to TempZone
    for($i = 0; $i < $count; $i++) {
        MZMove($player, "myDeck-0", "myTempZone");
    }
    $opponent = ($player == 1) ? 2 : 1;
    TwistedVerdictChoose($player, $opponent, 2);
}

function TwistedVerdictChoose($owner, $opponent, $remaining) {
    if($remaining <= 0) {
        TwistedVerdictFinish($owner);
        return;
    }
    global $playerID;
    $tempRef = ($owner == $playerID) ? "myTempZone" : "theirTempZone";
    $tempCards = ZoneSearch($tempRef);
    if(empty($tempCards)) return;
    $targetStr = implode("&", $tempCards);
    DecisionQueueController::AddDecision($opponent, "MZCHOOSE", $targetStr, 1, tooltip:"Choose_card_for_opponent's_memory_(" . $remaining . "_remaining)");
    DecisionQueueController::AddDecision($opponent, "CUSTOM", "TwistedVerdictPick|" . $owner . "|" . $remaining, 1);
}

function TwistedVerdictFinish($owner) {
    global $playerID;
    $tempRef = ($owner == $playerID) ? "myTempZone" : "theirTempZone";
    $deckRef = ($owner == $playerID) ? "myDeck" : "theirDeck";
    // Put remaining cards on bottom of deck in any order (random for sim)
    $remaining = ZoneSearch($tempRef);
    foreach($remaining as $mz) {
        MZMove($playerID, $mz, $deckRef);
    }
}

$customDQHandlers["TwistedVerdictPick"] = function($player, $parts, $lastDecision) {
    $owner = intval($parts[0]);
    $remaining = intval($parts[1]);
    if($lastDecision === "PASS" || $lastDecision === "-" || empty($lastDecision)) {
        TwistedVerdictFinish($owner);
        return;
    }
    global $playerID;
    $memRef = ($owner == $playerID) ? "myMemory" : "theirMemory";
    MZMove($playerID, $lastDecision, $memRef);
    TwistedVerdictChoose($owner, $player, $remaining - 1);
};

// ============================================================================
// Conjuring Fluorescence (Erpyb3AGgp): materialize a regalia from material deck
// ============================================================================
function ConjuringFluorescenceMaterialize($player) {
    $materialZone = GetZone("myMaterial");
    $regalias = [];
    for($i = 0; $i < count($materialZone); $i++) {
        $obj = $materialZone[$i];
        if(!$obj->removed && PropertyContains(CardType($obj->CardID), "REGALIA")) {
            $regalias[] = "myMaterial-" . $i;
        }
    }
    if(empty($regalias)) return;
    DecisionQueueController::AddDecision($player, "MZCHOOSE", implode("&", $regalias), 1,
        tooltip:"Choose_a_regalia_to_materialize");
    DecisionQueueController::AddDecision($player, "CUSTOM", "MATERIALIZE", 1);
}

// ============================================================================
// Buffeting Hurricane (CjL1WPvWHw): deal 2 damage to chosen champion on suppress
// ============================================================================
$customDQHandlers["BuffetingHurricaneDamage"] = function($player, $parts, $lastDecision) {
    if($lastDecision === "PASS" || $lastDecision === "-" || empty($lastDecision)) return;
    DealDamage($player, null, $lastDecision, 2);
};

// ============================================================================
// Spirit of Purity (FUCJA8IAMi): Lineage Release — each player banishes 2 from GY
// ============================================================================
function SpiritOfPurityBanishLoop($player, $targetPlayer, $remaining) {
    if($remaining <= 0) {
        // After the activating player finishes, do the opponent
        $opponent = ($player == 1) ? 2 : 1;
        if($targetPlayer === $player) {
            SpiritOfPurityBanishLoop($player, $opponent, 2);
        }
        return;
    }
    global $playerID;
    $gravZone = $targetPlayer == $playerID ? "myGraveyard" : "theirGraveyard";
    $gy = ZoneSearch($gravZone);
    if(empty($gy)) {
        if($targetPlayer === $player) {
            $opponent = ($player == 1) ? 2 : 1;
            SpiritOfPurityBanishLoop($player, $opponent, 2);
        }
        return;
    }
    $gyStr = implode("&", $gy);
    DecisionQueueController::AddDecision($targetPlayer, "MZCHOOSE", $gyStr, 1, tooltip:"Banish_a_card_from_graveyard_($remaining_remaining)");
    DecisionQueueController::AddDecision($targetPlayer, "CUSTOM", "SpiritOfPurityBanish|$player|$targetPlayer|$remaining", 1);
}

$customDQHandlers["SpiritOfPurityBanish"] = function($player, $parts, $lastDecision) {
    if($lastDecision === "-" || $lastDecision === "" || $lastDecision === "PASS") return;
    MZMove($player, $lastDecision, str_contains($lastDecision, "my") ? "myBanish" : "theirBanish");
    DecisionQueueController::CleanupRemovedCards();
    $activatingPlayer = intval($parts[0]);
    $targetPlayer = intval($parts[1]);
    $remaining = intval($parts[2]) - 1;
    SpiritOfPurityBanishLoop($activatingPlayer, $targetPlayer, $remaining);
};

// ============================================================================
// Purifying Thurible (LeyUk5auEP): (X), Banish — opponent banishes X from GY
// ============================================================================
$customDQHandlers["PurifyingThuriblePay"] = function($player, $parts, $lastDecision) {
    $x = intval($lastDecision);
    if($x <= 0) return;
    // Queue X ReserveCard payments
    for($i = 0; $i < $x; ++$i) {
        DecisionQueueController::AddDecision($player, "CUSTOM", "ReserveCard", 100);
    }
    DecisionQueueController::AddDecision($player, "CUSTOM", "PurifyingThuribleEffect|$x", 100);
};

$customDQHandlers["PurifyingThuribleEffect"] = function($player, $parts, $lastDecision) {
    $x = intval($parts[0]);
    if($x >= 3) {
        DrawIntoMemory($player, 1);
    }
    $opponent = ($player == 1) ? 2 : 1;
    PurifyingThuribleBanishLoop($player, $opponent, $x);
};

function PurifyingThuribleBanishLoop($activator, $opponent, $remaining) {
    if($remaining <= 0) return;
    global $playerID;
    $gravZone = $opponent == $playerID ? "myGraveyard" : "theirGraveyard";
    $gy = ZoneSearch($gravZone);
    if(empty($gy)) return;
    $gyStr = implode("&", $gy);
    DecisionQueueController::AddDecision($opponent, "MZCHOOSE", $gyStr, 1, tooltip:"Banish_a_card_from_your_graveyard_($remaining_remaining)");
    DecisionQueueController::AddDecision($opponent, "CUSTOM", "PurifyingThuribleBanish|$activator|$remaining", 1);
}

$customDQHandlers["PurifyingThuribleBanish"] = function($player, $parts, $lastDecision) {
    if($lastDecision === "-" || $lastDecision === "" || $lastDecision === "PASS") return;
    global $playerID;
    $banishZone = (strpos($lastDecision, "my") === 0) ? "myBanish" : "theirBanish";
    MZMove($player, $lastDecision, $banishZone);
    DecisionQueueController::CleanupRemovedCards();
    $activator = intval($parts[0]);
    $remaining = intval($parts[1]) - 1;
    PurifyingThuribleBanishLoop($activator, $player, $remaining);
};

// ============================================================================
// Sacred Engulfment (QvQhg1EOBR): banish fire from GY, then empower 4+X
// ============================================================================
function SacredEngulfmentBanishLoop($player, $banishedCount) {
    $fireGY = ZoneSearch("myGraveyard", cardElements: ["FIRE"]);
    if(empty($fireGY)) {
        Empower($player, 4 + $banishedCount, "QvQhg1EOBR");
        return;
    }
    $fireStr = implode("&", $fireGY);
    DecisionQueueController::AddDecision($player, "MZMAYCHOOSE", $fireStr, 1, tooltip:"Banish_a_fire_card_from_GY?");
    DecisionQueueController::AddDecision($player, "CUSTOM", "SacredEngulfmentProcess|$banishedCount", 1);
}

$customDQHandlers["SacredEngulfmentProcess"] = function($player, $parts, $lastDecision) {
    $banishedCount = intval($parts[0]);
    if($lastDecision === "PASS" || $lastDecision === "-" || empty($lastDecision)) {
        Empower($player, 4 + $banishedCount, "QvQhg1EOBR");
        return;
    }
    MZMove($player, $lastDecision, "myBanish");
    DecisionQueueController::CleanupRemovedCards();
    SacredEngulfmentBanishLoop($player, $banishedCount + 1);
};

// ============================================================================
// Enfeebling Orb (T3cx65VM3D): opponent puts 2 cards from hand into memory
// ============================================================================
function EnfeebleOrbChooseStep($player, $opponent, $remaining) {
    if($remaining <= 0) return;
    global $playerID;
    $handZone = $opponent == $playerID ? "myHand" : "theirHand";
    $hand = ZoneSearch($handZone);
    if(empty($hand)) return;
    $handStr = implode("&", $hand);
    DecisionQueueController::AddDecision($opponent, "MZCHOOSE", $handStr, 1, tooltip:"Put_a_card_from_hand_into_memory_($remaining_remaining)");
    DecisionQueueController::AddDecision($opponent, "CUSTOM", "EnfeebleOrbMove|$player|$remaining", 1);
}

$customDQHandlers["EnfeebleOrbMove"] = function($player, $parts, $lastDecision) {
    if($lastDecision === "-" || $lastDecision === "" || $lastDecision === "PASS") return;
    $memZone = (strpos($lastDecision, "my") === 0) ? "myMemory" : "theirMemory";
    MZMove($player, $lastDecision, $memZone);
    DecisionQueueController::CleanupRemovedCards();
    $activator = intval($parts[0]);
    $remaining = intval($parts[1]) - 1;
    $opponent = $player;
    EnfeebleOrbChooseStep($activator, $opponent, $remaining);
};

// ============================================================================
// Obscured Offering (S3ODMQ0V0o): banish 2 from material deck as additional cost
// ============================================================================
$customDQHandlers["ObscuredOfferingBanishMat"] = function($player, $parts, $lastDecision) {
    if($lastDecision === "-" || $lastDecision === "" || $lastDecision === "PASS") return;
    MZMove($player, $lastDecision, "myBanish");
    DecisionQueueController::CleanupRemovedCards();
    $step = intval($parts[0]);
    if($step < 2) {
        $mat = ZoneSearch("myMaterial");
        if(!empty($mat)) {
            $matStr = implode("&", $mat);
            DecisionQueueController::AddDecision($player, "MZCHOOSE", $matStr, 100, tooltip:"Banish_from_material_(2_of_2)");
            DecisionQueueController::AddDecision($player, "CUSTOM", "ObscuredOfferingBanishMat|2", 100);
        }
    }
};

// ============================================================================
// Dusklight Communion (5upufyoz23): banish astra/umbra from material deck, store element
// ============================================================================
$customDQHandlers["DusklightCommunionCost"] = function($player, $parts, $lastDecision) {
    if($lastDecision === "-" || $lastDecision === "" || $lastDecision === "PASS") return;
    $obj = GetZoneObject($lastDecision);
    if($obj === null) return;
    $element = CardElement($obj->CardID);
    MZMove($player, $lastDecision, "myBanish");
    DecisionQueueController::CleanupRemovedCards();
    DecisionQueueController::StoreVariable("dusklightBanishedElement", $element);
};

$customDQHandlers["DusklightCommunionDestroy"] = function($player, $parts, $lastDecision) {
    if($lastDecision === "-" || $lastDecision === "" || $lastDecision === "PASS") return;
    $obj = GetZoneObject($lastDecision);
    if($obj === null || $obj->removed) return;
    AllyDestroyed($player, $lastDecision);
};

// ============================================================================
// Mirrorbound Covenant (PKnOTdQJJ1): influence cap discard loop
// ============================================================================
function MirrorboundInfluenceDiscard($player, $excess) {
    if($excess <= 0) return;
    global $playerID;
    $handZone = $player == $playerID ? "myHand" : "theirHand";
    $hand = ZoneSearch($handZone);
    if(empty($hand)) return;
    $handStr = implode("&", $hand);
    DecisionQueueController::AddDecision($player, "MZCHOOSE", $handStr, 1, tooltip:"Put_a_card_into_memory_(influence_cap_7,_$excess_remaining)");
    DecisionQueueController::AddDecision($player, "CUSTOM", "MirrorboundInfluenceMove|$excess", 1);
}

$customDQHandlers["MirrorboundInfluenceMove"] = function($player, $parts, $lastDecision) {
    if($lastDecision === "-" || $lastDecision === "" || $lastDecision === "PASS") return;
    $memZone = (strpos($lastDecision, "my") === 0) ? "myMemory" : "theirMemory";
    MZMove($player, $lastDecision, $memZone);
    DecisionQueueController::CleanupRemovedCards();
    $remaining = intval($parts[0]) - 1;
    MirrorboundInfluenceDiscard($player, $remaining);
};

// --- Wavekeeper's Bond (WWlknyTxGA): [Level 3+] may sacrifice to draw into memory at end phase ---
$customDQHandlers["WavekeepersBondSacrifice"] = function($player, $parts, $lastDecision) {
    if($lastDecision !== "YES") return;
    $fieldIdx = intval($parts[0]);
    $field = &GetField($player);
    if(isset($field[$fieldIdx]) && !$field[$fieldIdx]->removed && $field[$fieldIdx]->CardID === "WWlknyTxGA") {
        DoSacrificeFighter($player, "myField-" . $fieldIdx);
        DrawIntoMemory($player, 1);
    }
};

// --- Squallsnare (cQZgiYS0w4): second target selection + suppress both ---
function SquallsnareContinue($player, $firstMZ) {
    DecisionQueueController::StoreVariable("squallsnare_first", $firstMZ);
    $firstObj = GetZoneObject($firstMZ);
    if($firstObj === null) return;
    $firstCost = intval(CardCost_reserve($firstObj->CardID));
    $allAllies = array_merge(
        ZoneSearch("myField", ["ALLY"]),
        ZoneSearch("theirField", ["ALLY"])
    );
    $allAllies = FilterSpellshroudTargets($allAllies);
    $remaining = [];
    foreach($allAllies as $mz) {
        if($mz === $firstMZ) continue;
        $o = GetZoneObject($mz);
        if($o !== null && intval(CardCost_reserve($o->CardID)) === $firstCost) {
            $remaining[] = $mz;
        }
    }
    if(empty($remaining)) return;
    $remStr = implode("&", $remaining);
    DecisionQueueController::AddDecision($player, "MZCHOOSE", $remStr, 1, tooltip:"Squallsnare:_suppress_second_ally_with_cost_$firstCost");
    DecisionQueueController::AddDecision($player, "CUSTOM", "SquallsnareFinish", 1);
}

$customDQHandlers["SquallsnareFinish"] = function($player, $parts, $lastDecision) {
    if($lastDecision === "-" || $lastDecision === "") return;
    $firstMZ = DecisionQueueController::GetVariable("squallsnare_first");
    $secondMZ = $lastDecision;
    // Suppress in reverse index order if same zone to avoid reindexing issues
    $firstParts = explode("-", $firstMZ);
    $secondParts = explode("-", $secondMZ);
    if($firstParts[0] === $secondParts[0] && intval($firstParts[1]) < intval($secondParts[1])) {
        SuppressAlly($player, $secondMZ);
        SuppressAlly($player, $firstMZ);
    } else {
        SuppressAlly($player, $firstMZ);
        SuppressAlly($player, $secondMZ);
    }
};

// --- Tweedledum, Rattled Dancer (UmZpK4rt2M): opponent chose the target defender ---
$customDQHandlers["TweedledumTargetChosen"] = function($player, $parts, $lastDecision) {
    $attackerMZ = $parts[0];
    $attackerPlayer = intval($parts[1]);
    if($lastDecision === "-" || $lastDecision === "") {
        ClearIntent($attackerPlayer);
        DecisionQueueController::ClearVariable("CombatAttacker");
        DecisionQueueController::ClearVariable("CombatWeapon");
        return;
    }
    // The opponent chose from their perspective (myField-X). Flip to attacker's perspective.
    $targetMZ = FlipZonePerspective($lastDecision);
    // Forward to AttackTargetChosen handler with the attacker's player
    global $customDQHandlers;
    $customDQHandlers["AttackTargetChosen"]($attackerPlayer, [$attackerMZ], $targetMZ);
};

// --- Alizarin Longbowman (inQV2nZfdJ): BecomeDistant — each player draws ---
$customDQHandlers["AlizarinLongbowmanDraw"] = function($player, $parts, $lastDecision) {
    if($lastDecision === "YES") {
        Draw($player, 1);
        $opp = ($player == 1) ? 2 : 1;
        Draw($opp, 1);
    }
};

// --- Vacuous Call (ex6AXz6IhB): discard ally card from hand as cost ---
$customDQHandlers["VacuousCallDiscardAlly"] = function($player, $parts, $lastDecision) {
    if($lastDecision !== "-" && $lastDecision !== "") {
        DoDiscardCard($player, $lastDecision);
    }
};

// --- Equivalent Exchange (qvB66hupGQ): optional banish 2 ally cards from single GY ---
$customDQHandlers["EquivalentExchangeGYBanish"] = function($player, $parts, $lastDecision) {
    // Check both GYs for 2+ ally cards
    $myAllyGY = [];
    $gy = GetZone("myGraveyard");
    for($gi = 0; $gi < count($gy); ++$gi) {
        if($gy[$gi]->removed) continue;
        if(PropertyContains(CardType($gy[$gi]->CardID), "ALLY")) {
            $myAllyGY[] = "myGraveyard-" . $gi;
        }
    }
    $theirAllyGY = [];
    $tgy = GetZone("theirGraveyard");
    for($gi = 0; $gi < count($tgy); ++$gi) {
        if($tgy[$gi]->removed) continue;
        if(PropertyContains(CardType($tgy[$gi]->CardID), "ALLY")) {
            $theirAllyGY[] = "theirGraveyard-" . $gi;
        }
    }
    $myValid = count($myAllyGY) >= 2;
    $theirValid = count($theirAllyGY) >= 2;
    if(!$myValid && !$theirValid) return;

    if($myValid && $theirValid) {
        DecisionQueueController::AddDecision($player, "YESNO", "-", 1, tooltip:"Banish_allies_from_your_graveyard?");
        DecisionQueueController::AddDecision($player, "CUSTOM", "EqExchangePickGY", 1);
    } else if($myValid) {
        DecisionQueueController::AddDecision($player, "YESNO", "-", 1, tooltip:"Banish_2_allies_from_your_graveyard?");
        DecisionQueueController::AddDecision($player, "CUSTOM", "EqExchangeConfirmMy", 1);
    } else {
        DecisionQueueController::AddDecision($player, "YESNO", "-", 1, tooltip:"Banish_2_allies_from_opponent's_graveyard?");
        DecisionQueueController::AddDecision($player, "CUSTOM", "EqExchangeConfirmTheir", 1);
    }
};

$customDQHandlers["EqExchangePickGY"] = function($player, $parts, $lastDecision) {
    if($lastDecision === "YES") {
        // Banish from my GY
        $myAllyGY = [];
        $gy = GetZone("myGraveyard");
        for($gi = 0; $gi < count($gy); ++$gi) {
            if($gy[$gi]->removed) continue;
            if(PropertyContains(CardType($gy[$gi]->CardID), "ALLY")) {
                $myAllyGY[] = "myGraveyard-" . $gi;
            }
        }
        $str = implode("&", $myAllyGY);
        DecisionQueueController::AddDecision($player, "MZCHOOSE", $str, 1, "Banish_first_ally");
        DecisionQueueController::AddDecision($player, "CUSTOM", "EqExchangeBanish1|my", 1);
    } else {
        // Banish from their GY
        $theirAllyGY = [];
        $tgy = GetZone("theirGraveyard");
        for($gi = 0; $gi < count($tgy); ++$gi) {
            if($tgy[$gi]->removed) continue;
            if(PropertyContains(CardType($tgy[$gi]->CardID), "ALLY")) {
                $theirAllyGY[] = "theirGraveyard-" . $gi;
            }
        }
        $str = implode("&", $theirAllyGY);
        DecisionQueueController::AddDecision($player, "MZCHOOSE", $str, 1, "Banish_first_ally");
        DecisionQueueController::AddDecision($player, "CUSTOM", "EqExchangeBanish1|their", 1);
    }
};

$customDQHandlers["EqExchangeConfirmMy"] = function($player, $parts, $lastDecision) {
    if($lastDecision !== "YES") return;
    $myAllyGY = [];
    $gy = GetZone("myGraveyard");
    for($gi = 0; $gi < count($gy); ++$gi) {
        if($gy[$gi]->removed) continue;
        if(PropertyContains(CardType($gy[$gi]->CardID), "ALLY")) {
            $myAllyGY[] = "myGraveyard-" . $gi;
        }
    }
    $str = implode("&", $myAllyGY);
    DecisionQueueController::AddDecision($player, "MZCHOOSE", $str, 1, "Banish_first_ally");
    DecisionQueueController::AddDecision($player, "CUSTOM", "EqExchangeBanish1|my", 1);
};

$customDQHandlers["EqExchangeConfirmTheir"] = function($player, $parts, $lastDecision) {
    if($lastDecision !== "YES") return;
    $theirAllyGY = [];
    $tgy = GetZone("theirGraveyard");
    for($gi = 0; $gi < count($tgy); ++$gi) {
        if($tgy[$gi]->removed) continue;
        if(PropertyContains(CardType($tgy[$gi]->CardID), "ALLY")) {
            $theirAllyGY[] = "theirGraveyard-" . $gi;
        }
    }
    $str = implode("&", $theirAllyGY);
    DecisionQueueController::AddDecision($player, "MZCHOOSE", $str, 1, "Banish_first_ally");
    DecisionQueueController::AddDecision($player, "CUSTOM", "EqExchangeBanish1|their", 1);
};

$customDQHandlers["EqExchangeBanish1"] = function($player, $parts, $lastDecision) {
    $gyPrefix = $parts[0]; // "my" or "their"
    MZMove($player, $lastDecision, "myBanish");
    DecisionQueueController::CleanupRemovedCards();
    // Offer second ally from same GY
    $zone = $gyPrefix === "my" ? "myGraveyard" : "theirGraveyard";
    $allyGY = [];
    $gy = GetZone($zone);
    for($gi = 0; $gi < count($gy); ++$gi) {
        if($gy[$gi]->removed) continue;
        if(PropertyContains(CardType($gy[$gi]->CardID), "ALLY")) {
            $allyGY[] = $zone . "-" . $gi;
        }
    }
    if(empty($allyGY)) return;
    $str = implode("&", $allyGY);
    DecisionQueueController::AddDecision($player, "MZCHOOSE", $str, 1, "Banish_second_ally");
    DecisionQueueController::AddDecision($player, "CUSTOM", "EqExchangeBanish2", 1);
};

$customDQHandlers["EqExchangeBanish2"] = function($player, $parts, $lastDecision) {
    MZMove($player, $lastDecision, "myBanish");
    DecisionQueueController::CleanupRemovedCards();
    // Wake token + buff counter
    $tokenIdx = intval(DecisionQueueController::GetVariable("eqExchangeTokenIdx"));
    $field = &GetField($player);
    if(isset($field[$tokenIdx]) && !$field[$tokenIdx]->removed && $field[$tokenIdx]->CardID === "duzpl7mqXl") {
        global $playerID;
        $tokenZone = $player == $playerID ? "myField" : "theirField";
        $tokenMZ = $tokenZone . "-" . $tokenIdx;
        WakeupCard($player, $tokenMZ);
        AddCounters($player, $tokenMZ, "buff", 1);
    }
};


// ============================================================================
// Ignition Draw (RhSPMn8Lix): look at top 6, banish up to 2 Aethercharge,
// put remainder on bottom in any order, activate banished this turn
// ============================================================================
function IgnitionDrawStart($player) {
    $deck = &GetDeck($player);
    $n = min(6, count($deck));
    if($n == 0) return;
    for($i = 0; $i < $n; $i++) {
        MZMove($player, "myDeck-0", "myTempZone");
    }
    DecisionQueueController::StoreVariable("IgnitionDraw_BanishCount", "0");
    DecisionQueueController::StoreVariable("IgnitionDraw_TotalRevealed", strval($n));
    IgnitionDrawChooseStep($player);
}

function IgnitionDrawChooseStep($player) {
    $count = intval(DecisionQueueController::GetVariable("IgnitionDraw_BanishCount") ?? "0");
    if($count >= 2) {
        IgnitionDrawRearrange($player);
        return;
    }
    $tempZone = GetZone("myTempZone");
    $aethercharges = [];
    for($i = 0; $i < count($tempZone); $i++) {
        if(!$tempZone[$i]->removed && PropertyContains(CardSubtypes($tempZone[$i]->CardID), "AETHERCHARGE")) {
            $aethercharges[] = "myTempZone-" . $i;
        }
    }
    if(empty($aethercharges)) {
        IgnitionDrawRearrange($player);
        return;
    }
    $targetStr = implode("&", $aethercharges);
    DecisionQueueController::AddDecision($player, "MZMAYCHOOSE", $targetStr, 1, "Banish_an_Aethercharge_card?");
    DecisionQueueController::AddDecision($player, "CUSTOM", "IgnitionDrawBanishChoice", 1);
}

$customDQHandlers["IgnitionDrawBanishChoice"] = function($player, $parts, $lastDecision) {
    if($lastDecision === "-" || $lastDecision === "" || $lastDecision === "PASS") {
        IgnitionDrawRearrange($player);
        return;
    }
    $obj = GetZoneObject($lastDecision);
    if($obj === null) { IgnitionDrawRearrange($player); return; }
    $banishedMZ = MZMove($player, $lastDecision, "myBanish");
    // Tag banished card so it can be activated from banishment this turn
    $banish = GetZone("myBanish");
    for($bi = count($banish) - 1; $bi >= 0; $bi--) {
        if(!$banish[$bi]->removed && $banish[$bi]->CardID === $obj->CardID) {
            if(!is_array($banish[$bi]->Counters)) $banish[$bi]->Counters = [];
            $banish[$bi]->Counters['_ignitionDraw'] = 1;
            break;
        }
    }
    $count = intval(DecisionQueueController::GetVariable("IgnitionDraw_BanishCount") ?? "0");
    DecisionQueueController::StoreVariable("IgnitionDraw_BanishCount", strval($count + 1));
    IgnitionDrawChooseStep($player);
};

function IgnitionDrawRearrange($player) {
    $tempZone = GetZone("myTempZone");
    $remaining = [];
    for($i = 0; $i < count($tempZone); $i++) {
        if(!$tempZone[$i]->removed) {
            $remaining[] = $tempZone[$i]->CardID;
        }
    }
    if(empty($remaining)) return;
    $param = "Top=;Bottom=" . implode(",", $remaining);
    DecisionQueueController::AddDecision($player, "MZREARRANGE", $param, 1, "Put_remaining_on_bottom_of_deck_in_any_order");
    DecisionQueueController::AddDecision($player, "CUSTOM", "IgnitionDrawRearrangeApply", 1);
}

$customDQHandlers["IgnitionDrawRearrangeApply"] = function($player, $parts, $lastDecision) {
    $deck = &GetDeck($player);
    $tempZone = &GetTempZone($player);

    // Parse the MZREARRANGE result
    $piles = ["Top" => [], "Bottom" => []];
    foreach(explode(";", $lastDecision) as $pileStr) {
        $eqPos = strpos($pileStr, "=");
        if($eqPos === false) continue;
        $pileName = substr($pileStr, 0, $eqPos);
        $cardsStr = trim(substr($pileStr, $eqPos + 1));
        if(isset($piles[$pileName])) {
            $piles[$pileName] = ($cardsStr !== "") ? explode(",", $cardsStr) : [];
        }
    }

    // Remove all remaining tempzone objects
    foreach($tempZone as $obj) {
        if(!$obj->removed) $obj->Remove();
    }
    DecisionQueueController::CleanupRemovedCards();

    // All cards go to bottom of deck (Bottom pile first, then any accidentally placed in Top)
    $allCards = array_merge($piles["Bottom"], $piles["Top"]);
    foreach($allCards as $cid) {
        $newObj = new Deck($cid, 'Deck', $player);
        $deck[] = $newObj;
    }
};

// ============================================================================
// PutCardFromGYIntoDeckAt: move a card from graveyard into deck at a given position
// ============================================================================
/**
 * Find the first copy of $cardID in the player's graveyard and insert it
 * into their deck at the given 0-based position.
 * Position 0 = top, position 3 = fourth from top, etc.
 * If the deck is shorter than $position, the card goes to the bottom.
 */
function PutCardFromGYIntoDeckAt($player, $cardID, $position) {
    global $playerID;
    $gyZone = ($player == $playerID) ? "myGraveyard" : "theirGraveyard";
    $gy = GetZone($gyZone);
    $foundMZ = null;
    for($i = 0; $i < count($gy); $i++) {
        if(!$gy[$i]->removed && $gy[$i]->CardID === $cardID) {
            $foundMZ = $gyZone . "-" . $i;
            break;
        }
    }
    if($foundMZ === null) return;
    // Remove from graveyard
    $obj = GetZoneObject($foundMZ);
    if($obj === null) return;
    $obj->Remove();
    DecisionQueueController::CleanupRemovedCards();
    // Insert into deck at position
    $deck = &GetDeck($player);
    $newObj = new Deck($cardID, 'Deck', $player);
    $pos = min($position, count($deck));
    array_splice($deck, $pos, 0, [$newObj]);
}

// --- Chessman Sacrifice DQ handlers ---

// ChessmanSacrifice: sacrifice the chosen Chessman ally. Param: parts[0] = activating card ID.
$customDQHandlers["ChessmanSacrifice"] = function($player, $parts, $lastDecision) {
    if($lastDecision === "-" || $lastDecision === "" || $lastDecision === "PASS") return;
    $activatingCardID = $parts[0] ?? "";
    $sacObj = GetZoneObject($lastDecision);
    $sacrificedCardID = $sacObj !== null ? $sacObj->CardID : "";
    // Store sacrificed info for Queen's Gambit (NGAy4rNwUo) Enter ability
    DecisionQueueController::StoreVariable("chessmanSacrificeCardID", $sacrificedCardID);
    $isQueen = $sacObj !== null && PropertyContains(EffectiveCardSubtypes($sacObj), "QUEEN");
    DecisionQueueController::StoreVariable("chessmanSacrificeWasQueen", $isQueen ? "YES" : "NO");
    DoSacrificeFighter($player, $lastDecision);
    DecisionQueueController::CleanupRemovedCards();
};

// SacrificePlayCost1: first sacrifice for Sacrifice Play (1jmQ9XSLph).
$customDQHandlers["SacrificePlayCost1"] = function($player, $parts, $lastDecision) {
    if($lastDecision === "-" || $lastDecision === "" || $lastDecision === "PASS") {
        DecisionQueueController::StoreVariable("sacrificePlayCount", "0");
        return;
    }
    DoSacrificeFighter($player, $lastDecision);
    DecisionQueueController::CleanupRemovedCards();
    // Offer second sacrifice
    $awakeChessman = [];
    $myField = GetZone("myField");
    for($fi = 0; $fi < count($myField); ++$fi) {
        if(!$myField[$fi]->removed && PropertyContains(EffectiveCardType($myField[$fi]), "ALLY")
           && PropertyContains(EffectiveCardSubtypes($myField[$fi]), "CHESSMAN")
           && isset($myField[$fi]->Status) && $myField[$fi]->Status == 2) {
            $awakeChessman[] = "myField-" . $fi;
        }
    }
    if(!empty($awakeChessman)) {
        $sacStr = implode("&", $awakeChessman);
        DecisionQueueController::AddDecision($player, "MZMAYCHOOSE", $sacStr, 100, tooltip:"Sacrifice_a_second_awake_Chessman_ally?");
        DecisionQueueController::AddDecision($player, "CUSTOM", "SacrificePlayCost2", 100);
    } else {
        DecisionQueueController::StoreVariable("sacrificePlayCount", "1");
    }
};

// SacrificePlayCost2: second sacrifice for Sacrifice Play.
$customDQHandlers["SacrificePlayCost2"] = function($player, $parts, $lastDecision) {
    if($lastDecision === "-" || $lastDecision === "" || $lastDecision === "PASS") {
        DecisionQueueController::StoreVariable("sacrificePlayCount", "1");
        return;
    }
    DoSacrificeFighter($player, $lastDecision);
    DecisionQueueController::CleanupRemovedCards();
    DecisionQueueController::StoreVariable("sacrificePlayCount", "2");
};

// BriarSpindleWakeup: wake each Chessman ally you control (ability 0 effect).
$customDQHandlers["BriarSpindleWakeup"] = function($player, $parts, $lastDecision) {
    global $playerID;
    $zone = $player == $playerID ? "myField" : "theirField";
    $field = GetZone($zone);
    for($i = 0; $i < count($field); ++$i) {
        if(!$field[$i]->removed && PropertyContains(EffectiveCardType($field[$i]), "ALLY")
           && PropertyContains(EffectiveCardSubtypes($field[$i]), "CHESSMAN")
           && isset($field[$i]->Status) && $field[$i]->Status == 1) {
            WakeupCard($player, $zone . "-" . $i);
        }
    }
};

// HuntChooseMode: player chose YES/NO for a Hunt mode offer. parts: [huntMZ, modeName, remainingModes]
$customDQHandlers["HuntChooseMode"] = function($player, $parts, $lastDecision) {
    $huntMZ = $parts[0] ?? "";
    $modeName = $parts[1] ?? "";
    $remainingModes = $parts[2] ?? "";

    if($lastDecision === "YES") {
        // Execute chosen mode
        $huntObj = &GetZoneObject($huntMZ);
        if($huntObj === null) return;
        global $playerID;
        $zone = $player == $playerID ? "myField" : "theirField";

        if($modeName === "BISHOP") {
            AddCounters($player, $huntMZ, "hunt_bishop", 1);
            Draw($player, amount: 1);
        } elseif($modeName === "KNIGHT") {
            AddCounters($player, $huntMZ, "hunt_knight", 1);
            $knights = ZoneSearch($zone, ["ALLY"], cardSubtypes: ["CHESSMAN", "KNIGHT"]);
            if(!empty($knights)) {
                if(count($knights) === 1) {
                    AddCounters($player, $knights[0], "buff", 1);
                } else {
                    $knightStr = implode("&", $knights);
                    DecisionQueueController::AddDecision($player, "MZCHOOSE", $knightStr, 1, tooltip:"Put_a_buff_counter_on_a_Knight");
                    DecisionQueueController::AddDecision($player, "CUSTOM", "HuntKnightBuff", 1);
                }
            }
        } elseif($modeName === "ROOK") {
            AddCounters($player, $huntMZ, "hunt_rook", 1);
            $rooks = ZoneSearch($zone, ["ALLY"], cardSubtypes: ["CHESSMAN", "ROOK"]);
            if(!empty($rooks)) {
                if(count($rooks) === 1) {
                    $rookMZ = $rooks[0];
                    $combatTarget = DecisionQueueController::GetVariable("CombatTarget");
                    if($combatTarget !== null && $combatTarget !== "-" && $combatTarget !== "") {
                        DecisionQueueController::StoreVariable("CombatTarget", $rookMZ);
                    }
                    AddTurnEffect($rookMZ, "Y6PZntlVDl_LIFE");
                } else {
                    $rookStr = implode("&", $rooks);
                    DecisionQueueController::AddDecision($player, "MZCHOOSE", $rookStr, 1, tooltip:"Redirect_attack_to_Rook");
                    DecisionQueueController::AddDecision($player, "CUSTOM", "HuntRookRedirect", 1);
                }
            }
        }
    } else {
        // Skip this mode, offer next one
        if(empty($remainingModes)) return;
        $modes = explode(",", $remainingModes);
        $nextMode = array_shift($modes);
        $nextRemaining = implode(",", $modes);
        $tooltips = [
            "BISHOP" => "Bishop_mode:_draw_a_card?",
            "KNIGHT" => "Knight_mode:_put_buff_counter?",
            "ROOK" => "Rook_mode:_redirect_attack_+2_LIFE?"
        ];
        $tooltip = $tooltips[$nextMode] ?? "Choose_Hunt_mode?";
        if(empty($modes)) {
            // Last mode — auto-execute
            DecisionQueueController::AddDecision($player, "PASSPARAMETER", "YES", 1);
        } else {
            DecisionQueueController::AddDecision($player, "YESNO", "-", 1, tooltip:$tooltip);
        }
        DecisionQueueController::AddDecision($player, "CUSTOM", "HuntChooseMode|$huntMZ|$nextMode|$nextRemaining", 1);
    }
};

// HuntKnightBuff: put a buff counter on the chosen Knight ally.
$customDQHandlers["HuntKnightBuff"] = function($player, $parts, $lastDecision) {
    if($lastDecision === "-" || $lastDecision === "") return;
    AddCounters($player, $lastDecision, "buff", 1);
};

// HuntRookRedirect: redirect attack to chosen Rook and give +2 LIFE.
$customDQHandlers["HuntRookRedirect"] = function($player, $parts, $lastDecision) {
    if($lastDecision === "-" || $lastDecision === "") return;
    $combatTarget = DecisionQueueController::GetVariable("CombatTarget");
    if($combatTarget !== null && $combatTarget !== "-" && $combatTarget !== "") {
        DecisionQueueController::StoreVariable("CombatTarget", $lastDecision);
    }
    AddTurnEffect($lastDecision, "Y6PZntlVDl_LIFE");
};

// LagomorphPieceReturn: return a chosen Chessman Command card from GY to memory.
$customDQHandlers["LagomorphPieceReturn"] = function($player, $parts, $lastDecision) {
    if($lastDecision === "-" || $lastDecision === "" || $lastDecision === "PASS") return;
    MZMove($player, $lastDecision, "myMemory");
};

// PawnToQueenSacrifice: on-hit sacrifice of Pawn/Golden Pawn to summon Queen Piece.
$customDQHandlers["PawnToQueenSacrifice"] = function($player, $parts, $lastDecision) {
    $attackerMZ = $parts[0] ?? "";
    if($lastDecision !== "YES") return;
    $obj = GetZoneObject($attackerMZ);
    if($obj === null || $obj->removed) return;
    DoSacrificeFighter($player, $attackerMZ);
    DecisionQueueController::CleanupRemovedCards();
    SummonQueenPieceToken($player);
};

// WindfallCheckBanish: opponent banishes chosen GY card, then re-queues if more needed.
// parts[0] = remaining count after this banish.
$customDQHandlers["WindfallCheckBanish"] = function($player, $parts, $lastDecision) {
    $remaining = intval($parts[0] ?? 0);
    if($lastDecision === "-" || $lastDecision === "" || $lastDecision === "PASS") return;
    MZMove($player, $lastDecision, "myBanish");
    DecisionQueueController::CleanupRemovedCards();
    if($remaining <= 0) return;
    global $playerID;
    $oppGYZone = $player == $playerID ? "myGraveyard" : "theirGraveyard";
    $oppGY = GetZone($oppGYZone);
    $validGY = [];
    for($i = 0; $i < count($oppGY); ++$i) {
        if(!$oppGY[$i]->removed) $validGY[] = $oppGYZone . "-" . $i;
    }
    if(empty($validGY)) return;
    $gyStr = implode("&", $validGY);
    DecisionQueueController::AddDecision($player, "MZCHOOSE", $gyStr, 1, tooltip:"Banish_a_card_from_graveyard");
    DecisionQueueController::AddDecision($player, "CUSTOM", "WindfallCheckBanish|" . ($remaining - 1), 1);
};

// UnmakeDualitySacrifice: sacrifice the chosen divine relic regalia (declaring cost for Unmake Duality)
$customDQHandlers["UnmakeDualitySacrifice"] = function($player, $parts, $lastDecision) {
    if($lastDecision === "-" || $lastDecision === "") return;
    $obj = GetZoneObject($lastDecision);
    if($obj === null) return;
    OnLeaveField($player, $lastDecision);
    MZMove($player, $lastDecision, "myGraveyard");
    DecisionQueueController::CleanupRemovedCards();
};

// CleansingReunionBanish: banish the chosen card from opponent's graveyard, chain remaining
$customDQHandlers["CleansingReunionBanish"] = function($player, $parts, $lastDecision) {
    $remaining = intval($parts[0] ?? 0);
    if($lastDecision === "-" || $lastDecision === "") return;
    MZMove($player, $lastDecision, "myBanish");
    DecisionQueueController::CleanupRemovedCards();
    if($remaining <= 0) return;
    global $playerID;
    $gyZone = $player == $playerID ? "myGraveyard" : "theirGraveyard";
    $gy = GetZone($gyZone);
    $gyCards = [];
    for($gi = 0; $gi < count($gy); ++$gi) {
        if(!$gy[$gi]->removed) $gyCards[] = $gyZone . "-" . $gi;
    }
    if(empty($gyCards)) return;
    $gyStr = implode("&", $gyCards);
    $total = $remaining + (intval($parts[0] ?? 0) - $remaining) + 1; // approximate for tooltip
    DecisionQueueController::AddDecision($player, "MZCHOOSE", $gyStr, 1, tooltip:"Banish_a_card_from_graveyard");
    DecisionQueueController::AddDecision($player, "CUSTOM", "CleansingReunionBanish|" . ($remaining - 1), 1);
};

// MirroredConfrontationReveal: if YES, draw into memory and recover 2
$customDQHandlers["MirroredConfrontationReveal"] = function($player, $parts, $lastDecision) {
    if($lastDecision !== "YES") return;
    DrawIntoMemory($player, 1);
    RecoverChampion($player, 2);
};

// FracturingSlashMove: move 1 sheen counter from chosen unit to defender
$customDQHandlers["FracturingSlashMove"] = function($player, $parts, $lastDecision) {
    $defenderMZ = $parts[0] ?? null;
    if($lastDecision === "-" || $lastDecision === "" || $lastDecision === "PASS") return;
    if($defenderMZ === null) return;
    $sourceObj = GetZoneObject($lastDecision);
    if($sourceObj === null || GetCounterCount($sourceObj, "sheen") < 1) return;
    $defObj = GetZoneObject($defenderMZ);
    if($defObj === null) return;
    RemoveCounters($player, $lastDecision, "sheen", 1);
    AddCounters($defObj->Controller ?? $player, $defenderMZ, "sheen", 1);
};

// WeaponsmithDurability: put a durability counter on the chosen weapon
$customDQHandlers["WeaponsmithDurability"] = function($player, $parts, $lastDecision) {
    if($lastDecision === "-" || $lastDecision === "") return;
    AddCounters($player, $lastDecision, "durability", 1);
};

// ShrivelingVinesWither: put 2 wither counters on chosen opponent object
$customDQHandlers["ShrivelingVinesWither"] = function($player, $parts, $lastDecision) {
    if($lastDecision === "-" || $lastDecision === "") return;
    AddCounters($player, $lastDecision, "wither", 2);
};

// Spirit Blade: Dispersion — choose Sword weapons to strip durability + banish
$customDQHandlers["SpiritBladeChooseSword"] = function($player, $parts, $lastDecision) {
    global $playerID;
    $totalDurability = intval($parts[0] ?? 0);

    if($lastDecision === "-" || $lastDecision === "" || $lastDecision === "PASS") {
        // Player done choosing — proceed to split damage
        if($totalDurability > 0) {
            $allUnits = array_merge(
                ZoneSearch("myField", ["ALLY", "CHAMPION"]),
                ZoneSearch("theirField", ["ALLY", "CHAMPION"])
            );
            $allUnits = FilterSpellshroudTargets($allUnits);
            if(!empty($allUnits)) {
                $targetStr = implode("&", $allUnits);
                DecisionQueueController::AddDecision($player, "MZSPLITASSIGN", "$targetStr|$totalDurability|Split_damage_(Spirit_Blade:_Dispersion)", 1);
                DecisionQueueController::AddDecision($player, "CUSTOM", "SpiritBladeSplitDamage", 1);
            }
        }
        return;
    }

    // Process chosen sword: remove durability counters, banish it
    $swordObj = GetZoneObject($lastDecision);
    if($swordObj !== null) {
        $durCount = GetCounterCount($swordObj, "durability");
        $totalDurability += $durCount;
        RemoveCounters($player, $lastDecision, "durability", $durCount);
        MZMove($player, $lastDecision, "myBanish");
        DecisionQueueController::CleanupRemovedCards();
    }

    // Offer more swords
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
    if(!empty($swords)) {
        DecisionQueueController::AddDecision($player, "MZMAYCHOOSE", implode("&", $swords), 1,
            tooltip:"Choose_another_Sword_weapon_(Spirit_Blade:_Dispersion)");
        DecisionQueueController::AddDecision($player, "CUSTOM", "SpiritBladeChooseSword|$totalDurability", 1);
    } else {
        // No more swords — proceed to split damage
        if($totalDurability > 0) {
            $allUnits = array_merge(
                ZoneSearch("myField", ["ALLY", "CHAMPION"]),
                ZoneSearch("theirField", ["ALLY", "CHAMPION"])
            );
            $allUnits = FilterSpellshroudTargets($allUnits);
            if(!empty($allUnits)) {
                $targetStr = implode("&", $allUnits);
                DecisionQueueController::AddDecision($player, "MZSPLITASSIGN", "$targetStr|$totalDurability|Split_damage_(Spirit_Blade:_Dispersion)", 1);
                DecisionQueueController::AddDecision($player, "CUSTOM", "SpiritBladeSplitDamage", 1);
            }
        }
    }
};

// Spirit Blade: Dispersion — process split damage result
$customDQHandlers["SpiritBladeSplitDamage"] = function($player, $parts, $lastDecision) {
    if($lastDecision === "-" || $lastDecision === "") return;
    // Use the spell's mzID as the source (it's in graveyard now after activation)
    ProcessSplitDamage($player, "7Rsid05Cf6", $lastDecision);
};

// Seep Into the Mind — opponent adds 3 sheen counters to chosen unit
$customDQHandlers["SeepIntoTheMindSheen"] = function($player, $parts, $lastDecision) {
    $caster = intval($parts[0]);
    if($lastDecision === "-" || $lastDecision === "") return;
    AddCounters($player, $lastDecision, "sheen", 3);
    // [Merlin Bonus][Sheen 6+] Look at opponent's memory and discard a card
    if(IsMerlinBonusActive($caster) && GetSheenCount($caster) >= 6) {
        $oppMemory = ZoneSearch("theirMemory");
        if(!empty($oppMemory)) {
            DecisionQueueController::AddDecision($caster, "MZCHOOSE", implode("&", $oppMemory), 1,
                tooltip:"Discard_a_card_from_opponent's_memory_(Seep_Into_the_Mind)");
            DecisionQueueController::AddDecision($caster, "CUSTOM", "SeepIntoTheMindDiscard", 1);
        }
    }
};

// Seep Into the Mind — discard chosen card from memory
$customDQHandlers["SeepIntoTheMindDiscard"] = function($player, $parts, $lastDecision) {
    if($lastDecision === "-" || $lastDecision === "") return;
    MZMove($player, $lastDecision, "theirGraveyard");
};

// Etherealys' Promise — banish to draw a card
$customDQHandlers["EtherealysPromiseBanish"] = function($player, $parts, $lastDecision) {
    if($lastDecision !== "YES") return;
    $idx = intval($parts[0]);
    global $playerID;
    $fieldZone = $player == $playerID ? "myField" : "theirField";
    $field = GetZone($fieldZone);
    if(isset($field[$idx]) && !$field[$idx]->removed && $field[$idx]->CardID === "7n0bv1sqgb") {
        MZMove($player, $fieldZone . "-" . $idx, "myBanish");
        DecisionQueueController::CleanupRemovedCards();
        Draw($player, 1);
    }
};

function SinistreStabCommitToLineage($player, $targetPlayer) {
    $intentCards = GetIntentCards($player);
    foreach($intentCards as $iMZ) {
        $iObj = GetZoneObject($iMZ);
        if($iObj === null || $iObj->CardID !== "e1xj8mqr2o") continue;
        if(!in_array("CURSE_TO_LINEAGE", $iObj->TurnEffects ?? [])) {
            $iObj->TurnEffects[] = "CURSE_TO_LINEAGE";
        }
        AddToChampionLineage($targetPlayer, "e1xj8mqr2o");
        break;
    }
}

function SinistreStabOnHit($player) {
    if(GetOmenCount($player) < 5) {
        SinistreStabCommitToLineage($player, $player);
        return;
    }
    $hitTarget = DecisionQueueController::GetVariable("CombatTarget");
    if($hitTarget === null || $hitTarget === "" || $hitTarget === "-") return;
    $hitObj = GetZoneObject($hitTarget);
    if($hitObj === null || !PropertyContains(EffectiveCardType($hitObj), "CHAMPION")) return;
    DecisionQueueController::AddDecision($player, "YESNO", "-", 1,
        tooltip:"Put_Sinistre_Stab_on_hit_champion's_lineage?");
    DecisionQueueController::AddDecision($player, "CUSTOM", "SinistreStabChoice", 1);
}

$customDQHandlers["SinistreStabChoice"] = function($player, $parts, $lastDecision) {
    if($lastDecision !== "YES") return;
    $hitTarget = DecisionQueueController::GetVariable("CombatTarget");
    if($hitTarget === null || $hitTarget === "" || $hitTarget === "-") return;
    $hitObj = GetZoneObject($hitTarget);
    if($hitObj === null || !PropertyContains(EffectiveCardType($hitObj), "CHAMPION")) return;
    SinistreStabCommitToLineage($player, $hitObj->Controller);
};

function EventideLurePutRestOnBottom($player) {
    $remaining = ZoneSearch("myTempZone");
    foreach($remaining as $rmz) {
        MZMove($player, $rmz, "myDeck");
    }
}

function EventideLureEnter($player) {
    $deck = GetDeck($player);
    $lookCount = min(5, count($deck));
    if($lookCount <= 0) return;
    for($i = 0; $i < $lookCount; ++$i) {
        MZMove($player, "myDeck-0", "myTempZone");
    }
    $candidates = ZoneSearch("myTempZone", ["PHANTASIA"]);
    if(empty($candidates)) {
        EventideLurePutRestOnBottom($player);
        return;
    }
    DecisionQueueController::AddDecision($player, "MZMAYCHOOSE", implode("&", $candidates), 1,
        tooltip:"Reveal_a_phantasia_card_to_put_into_memory?");
    DecisionQueueController::AddDecision($player, "CUSTOM", "EventideLureReveal", 1);
}

$customDQHandlers["EventideLureReveal"] = function($player, $parts, $lastDecision) {
    if($lastDecision !== "-" && $lastDecision !== "" && $lastDecision !== "PASS") {
        Reveal($player, $lastDecision);
        MZMove($player, $lastDecision, "myMemory");
    }
    EventideLurePutRestOnBottom($player);
};

function LustrousSlimeApplyReveals($player, $mzID) {
    $revealCount = intval(DecisionQueueController::GetVariable("LustrousSlimeRevealCount"));
    if($revealCount > 0) {
        AddCounters($player, $mzID, "buff", $revealCount);
    }
}

function LustrousSlimeRevealLoop($player, $mzID) {
    $chosenRaw = DecisionQueueController::GetVariable("LustrousSlimeChosen");
    $chosen = $chosenRaw === null || $chosenRaw === "" ? [] : explode("|", $chosenRaw);
    $targets = [];
    $memory = GetZone("myMemory");
    for($i = 0; $i < count($memory); ++$i) {
        if($memory[$i]->removed) continue;
        $memMZ = "myMemory-" . $i;
        if(in_array($memMZ, $chosen)) continue;
        if(PropertyContains(CardSubtypes($memory[$i]->CardID), "SLIME")) {
            $targets[] = $memMZ;
        }
    }
    if(empty($targets)) {
        LustrousSlimeApplyReveals($player, $mzID);
        return;
    }
    DecisionQueueController::AddDecision($player, "MZMAYCHOOSE", implode("&", $targets), 1,
        tooltip:"Reveal_a_Slime_card_from_memory?");
    DecisionQueueController::AddDecision($player, "CUSTOM", "LustrousSlimeReveal|$mzID", 1);
}

function LustrousSlimeEnter($player) {
    $mzID = DecisionQueueController::GetVariable("mzID");
    DecisionQueueController::StoreVariable("LustrousSlimeRevealCount", "0");
    DecisionQueueController::StoreVariable("LustrousSlimeChosen", "");
    LustrousSlimeRevealLoop($player, $mzID);
}

$customDQHandlers["LustrousSlimeReveal"] = function($player, $parts, $lastDecision) {
    $mzID = $parts[0];
    if($lastDecision === "-" || $lastDecision === "" || $lastDecision === "PASS") {
        LustrousSlimeApplyReveals($player, $mzID);
        return;
    }
    Reveal($player, $lastDecision);
    $chosenRaw = DecisionQueueController::GetVariable("LustrousSlimeChosen");
    $chosen = $chosenRaw === null || $chosenRaw === "" ? [] : explode("|", $chosenRaw);
    $chosen[] = $lastDecision;
    DecisionQueueController::StoreVariable("LustrousSlimeChosen", implode("|", $chosen));
    $revealCount = intval(DecisionQueueController::GetVariable("LustrousSlimeRevealCount"));
    DecisionQueueController::StoreVariable("LustrousSlimeRevealCount", strval($revealCount + 1));
    LustrousSlimeRevealLoop($player, $mzID);
};

$customDQHandlers["GlimmerEssenceAmuletChoice"] = function($player, $parts, $lastDecision) {
    if($lastDecision !== "YES") return;
    global $playerID;
    $fieldZone = $player == $playerID ? "myField" : "theirField";
    $field = GetZone($fieldZone);
    for($i = 0; $i < count($field); ++$i) {
        if($field[$i]->removed || $field[$i]->CardID !== "dy4urpjbjm") continue;
        OnLeaveField($player, $fieldZone . "-" . $i);
        MZMove($player, $fieldZone . "-" . $i, "myBanish");
        DecisionQueueController::CleanupRemovedCards();
        Draw($player, 1);
        break;
    }
};

$customDQHandlers["FortifyingAromaTarget"] = function($player, $parts, $lastDecision) {
    if($lastDecision === "-" || $lastDecision === "" || $lastDecision === "PASS") return;
    AddCounters($player, $lastDecision, "buff", 2);
};

$customDQHandlers["GalvanizingGaleTarget"] = function($player, $parts, $lastDecision) {
    if($lastDecision === "-" || $lastDecision === "" || $lastDecision === "PASS") return;
    AddTurnEffect($lastDecision, "f00cEmu6Ql");
    if(IsEmpowered($player)) {
        DrawIntoMemory($player, 1);
    }
};

$customDQHandlers["TaijiCrystalStrategemsRest"] = function($player, $parts, $lastDecision) {
    if($lastDecision !== "YES") return;
    $mzID = DecisionQueueController::GetVariable("TaijiCrystalStrategemsMZ");
    $obj = GetZoneObject($mzID);
    if($obj === null || $obj->removed || $obj->Status != 2) return;
    RestCard($player, $mzID);
    $opponent = ($player == 1) ? 2 : 1;
    DealChampionDamage($opponent, 3);
};

function LungeOfEvokingWindsChoose($player, $remaining) {
    if($remaining <= 0) return;
    $windCards = ZoneSearch("myMemory", cardElements: ["WIND"]);
    if(empty($windCards)) return;
    DecisionQueueController::AddDecision($player, "MZMAYCHOOSE", implode("&", $windCards), 1,
        tooltip:"Reveal_a_wind_card_from_memory_to_return_to_hand?");
    DecisionQueueController::AddDecision($player, "CUSTOM", "LungeOfEvokingWindsChoose|" . $remaining, 1);
}

$customDQHandlers["LungeOfEvokingWindsChoose"] = function($player, $parts, $lastDecision) {
    if($lastDecision === "-" || $lastDecision === "" || $lastDecision === "PASS") return;
    DoRevealCard($player, $lastDecision);
    MZMove($player, $lastDecision, "myHand");
    $remaining = intval($parts[0]) - 1;
    if($remaining > 0) {
        LungeOfEvokingWindsChoose($player, $remaining);
    }
};

?>

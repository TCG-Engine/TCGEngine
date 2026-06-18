<?php

// --- Brew Costs Registry ---
// Maps potion cardID => array of ingredient slots.
// Each slot: ["type"=>"CARD","cardID"=>"..."] for a specific herb,
//            ["type"=>"SUBTYPE","subtype"=>"..."] for a subtype-filtered herb,
//            ["type"=>"HERB"] for any herb.
// "count" defaults to 1 when omitted.
$brewCosts = [];
$brewCosts["bae3z4pyx8"] = [["type"=>"HERB","count"=>3]]; // Serum of Wisdom: 3 Herbs
$brewCosts["qtb31x97n2"] = [["type"=>"HERB","count"=>2]]; // Potion of Healing: 2 Herbs
$brewCosts["g616r0zadf"] = [["type"=>"CARD","cardID"=>"jnltv5klry"], ["type"=>"HERB"]]; // Bottled Forgelight: Razorvine + Herb
$brewCosts["h38lrj5221"] = [["type"=>"CARD","cardID"=>"i0a5uhjxhk"], ["type"=>"HERB"]]; // Distilled Atrophy: Blightroot + Herb
$brewCosts["tjot4nmxqs"] = [["type"=>"HERB","count"=>2]]; // Wildgrowth Elixir: 2 Herbs
$brewCosts["l8ao8bls6g"] = [["type"=>"CARD","cardID"=>"soporhlq2k"]]; // Convalescent Tonic: Fraysia
$brewCosts["lpnvx7mnu1"] = [["type"=>"CARD","cardID"=>"69iq4d5vet"], ["type"=>"HERB","count"=>2]]; // Draught of Stamina: Springleaf + 2 Herbs
$brewCosts["9g44vm5kt3"] = [["type"=>"CARD","cardID"=>"5joh300z2s"], ["type"=>"HERB"]]; // Empowering Tincture: Manaroot + Herb
$brewCosts["14m4c8ljye"] = [["type"=>"CARD","cardID"=>"bd7ozuj68m"], ["type"=>"SUBTYPE","subtype"=>"ADJUVANT","count"=>2], ["type"=>"SUBTYPE","subtype"=>"CATALYST","count"=>2]]; // Condensed Supernova: Silvershine + 2 Adjuvants + 2 Catalysts
$brewCosts["df9q1vk8ao"] = [["type"=>"SUBTYPE","subtype"=>"FLOWER"], ["type"=>"HERB"]]; // Molten Cinder: One Flower, One Herb
$brewCosts["y5ttkat9hr"] = [["type"=>"CARD","cardID"=>"69iq4d5vet"]]; // Aqua Vitae: One Springleaf
$brewCosts["7kr1haizu8"] = [["type"=>"SUBTYPE","subtype"=>"ROOT"], ["type"=>"HERB"]]; // Forgetful Concoction: One Root, One Herb
$brewCosts["nsjukk5zk4"] = [["type"=>"SUBTYPE","subtype"=>"FLOWER"], ["type"=>"HERB"]]; // Invigorating Concoction: One Flower, One Herb
$brewCosts["yorsltrnu3"] = [["type"=>"SUBTYPE","subtype"=>"LEAF"], ["type"=>"HERB"]]; // Explosive Concoction: One Leaf, One Herb
$brewCosts["0Z1r8GC8a8"] = [["type"=>"HERB","count"=>2]]; // Speed Potion: 2 Herbs
$brewCosts["NwK5wge8wy"] = [["type"=>"HERB","count"=>3]]; // Alpha Philterbeast: 3 Herbs
$brewCosts["O1OU62Zx2Y"] = [["type"=>"HERB","count"=>1]]; // Distilled Water: 1 Herb
$brewCosts["gnYM2V6TTw"] = [["type"=>"SAME_NAME_HERBS","count"=>2]]; // Soothing Potion: 2 Herbs with the same name
$brewCosts["hj1trn0yet"] = [["type"=>"HERB","count"=>4]]; // Hide in Bush: 4 Herbs
$brewCosts["me0xxw0plq"] = [["type"=>"CARD","cardID"=>"bd7ozuj68m","count"=>2], ["type"=>"HERB","count"=>3]]; // Refracted Twilight: 2 Silvershine + 3 Herbs
$brewCosts["vt9y597fqr"] = [["type"=>"DIFFERENT_NAME_HERBS","count"=>4]]; // Prima Materia: 4 Herbs with different names

// --- Gather (Grand Archive keyword): summon a random herb token ---

function Gather($player) {
    $herbTokens = ["i0a5uhjxhk", "5joh300z2s", "bd7ozuj68m", "soporhlq2k", "jnltv5klry", "69iq4d5vet"];
    $randomIndex = EngineRandomInt(0, count($herbTokens) - 1);
    $randomHerb = $herbTokens[$randomIndex];
    MZAddZone($player, "myField", $randomHerb);
    OnGather($player);
}

// =======================================================================
// --- Brew Mechanic ---
// =======================================================================

/**
 * Check whether the player controls enough herbs to pay a brew cost.
 */
function CanPayBrewCost($player, $slots) {
    $herbs = ZoneSearch("myField", cardSubtypes: ["HERB"]);
    if(empty($herbs)) return false;

    $available = [];
    foreach($herbs as $mz) {
        $obj = GetZoneObject($mz);
        $available[] = [
            'mz' => $mz,
            'cardID' => $obj->CardID,
            'subtypes' => CardSubtypes($obj->CardID)
        ];
    }

    // Greedy check: satisfy specific-card slots first, then subtype, then generic
    $used = [];

    foreach($slots as $slot) {
        $count = $slot['count'] ?? 1;
        if($slot['type'] !== 'CARD') continue;
        for($c = 0; $c < $count; ++$c) {
            $found = false;
            for($i = 0; $i < count($available); ++$i) {
                if(in_array($i, $used)) continue;
                if($available[$i]['cardID'] === $slot['cardID']) {
                    $used[] = $i;
                    $found = true;
                    break;
                }
            }
            if(!$found) return false;
        }
    }

    foreach($slots as $slot) {
        $count = $slot['count'] ?? 1;
        if($slot['type'] !== 'SUBTYPE') continue;
        for($c = 0; $c < $count; ++$c) {
            $found = false;
            for($i = 0; $i < count($available); ++$i) {
                if(in_array($i, $used)) continue;
                if(PropertyContains($available[$i]['subtypes'], $slot['subtype'])) {
                    $used[] = $i;
                    $found = true;
                    break;
                }
            }
            if(!$found) return false;
        }
    }

    foreach($slots as $slot) {
        $count = $slot['count'] ?? 1;
        if($slot['type'] !== 'HERB') continue;
        for($c = 0; $c < $count; ++$c) {
            $found = false;
            for($i = 0; $i < count($available); ++$i) {
                if(in_array($i, $used)) continue;
                $used[] = $i;
                $found = true;
                break;
            }
            if(!$found) return false;
        }
    }

    foreach($slots as $slot) {
        $count = $slot['count'] ?? 1;
        if($slot['type'] !== 'SAME_NAME_HERBS') continue;
        $byCardID = [];
        foreach($available as $entry) {
            $cardID = $entry['cardID'];
            if(!isset($byCardID[$cardID])) $byCardID[$cardID] = 0;
            $byCardID[$cardID]++;
        }
        $hasEnoughSameName = false;
        foreach($byCardID as $qty) {
            if($qty >= $count) {
                $hasEnoughSameName = true;
                break;
            }
        }
        if(!$hasEnoughSameName) return false;
    }

    foreach($slots as $slot) {
        $count = $slot['count'] ?? 1;
        if($slot['type'] !== 'DIFFERENT_NAME_HERBS') continue;
        $uniqueCardIDs = [];
        foreach($available as $entry) {
            $uniqueCardIDs[$entry['cardID']] = true;
        }
        if(count($uniqueCardIDs) < $count) return false;
    }

    return true;
}

/**
 * Flatten brew cost slots into sequential selection steps.
 * Specific-card slots first, then subtype, then generic herbs.
 */
function FlattenBrewSlots($slots) {
    $steps = [];
    $herbNames = [
        "i0a5uhjxhk" => "Blightroot", "5joh300z2s" => "Manaroot",
        "bd7ozuj68m" => "Silvershine", "soporhlq2k" => "Fraysia",
        "jnltv5klry" => "Razorvine",   "69iq4d5vet" => "Springleaf",
    ];
    foreach($slots as $slot) {
        $count = $slot['count'] ?? 1;
        if($slot['type'] === 'CARD') {
            $name = $herbNames[$slot['cardID']] ?? 'Herb';
            for($i = 0; $i < $count; ++$i)
                $steps[] = ["filter" => "CARD:" . $slot['cardID'], "tooltip" => "Sacrifice_" . $name];
        }
    }
    foreach($slots as $slot) {
        $count = $slot['count'] ?? 1;
        if($slot['type'] === 'SUBTYPE') {
            for($i = 0; $i < $count; ++$i)
                $steps[] = ["filter" => "SUBTYPE:" . $slot['subtype'], "tooltip" => "Sacrifice_" . $slot['subtype'] . "_herb"];
        }
    }
    foreach($slots as $slot) {
        $count = $slot['count'] ?? 1;
        if($slot['type'] === 'HERB') {
            for($i = 0; $i < $count; ++$i)
                $steps[] = ["filter" => "HERB", "tooltip" => "Sacrifice_an_herb"];
        }
    }
    return $steps;
}

function MZNumericIndex($mzID) {
    if(!is_string($mzID) || $mzID === "") return -1;
    $lastDash = strrpos($mzID, "-");
    if($lastDash === false) return -1;
    return intval(substr($mzID, $lastDash + 1));
}

/**
 * Get available herb mzIDs matching a filter, excluding already-chosen mzIDs.
 */
function GetFilteredHerbs($filter, $excludeMZs) {
    $herbs = ZoneSearch("myField", cardSubtypes: ["HERB"]);
    $matches = [];
    foreach($herbs as $mz) {
        if(in_array($mz, $excludeMZs)) continue;
        $obj = GetZoneObject($mz);
        if(str_starts_with($filter, "CARD:")) {
            if($obj->CardID === substr($filter, 5)) $matches[] = $mz;
        } elseif(str_starts_with($filter, "SUBTYPE:")) {
            if(PropertyContains(CardSubtypes($obj->CardID), substr($filter, 8))) $matches[] = $mz;
        } else {
            $matches[] = $mz;
        }
    }
    return $matches;
}

function GetDifferentNameHerbs($excludeMZs) {
    $excludedCardIDs = [];
    foreach($excludeMZs as $mz) {
        if($mz === null || $mz === "") continue;
        $obj = GetZoneObject($mz);
        if($obj === null) continue;
        $excludedCardIDs[$obj->CardID] = true;
    }

    $herbs = ZoneSearch("myField", cardSubtypes: ["HERB"]);
    $matches = [];
    foreach($herbs as $mz) {
        if(in_array($mz, $excludeMZs)) continue;
        $obj = GetZoneObject($mz);
        if($obj === null) continue;
        if(isset($excludedCardIDs[$obj->CardID])) continue;
        $matches[] = $mz;
    }
    return $matches;
}

/**
 * Called after herbs are sacrificed for a Brew. Fires "whenever you brew" triggers.
 */
function OnBrew($player) {
    AddGlobalEffects($player, "BREWED_POTION");

    // Imperial Alchemist (ve1d47o7ea): whenever you brew a Potion → buff counter
    $field = &GetField($player);
    global $playerID;
    $fieldZone = ($player == $playerID) ? "myField" : "theirField";
    for($i = 0; $i < count($field); ++$i) {
        if($field[$i]->removed) continue;
        if($field[$i]->CardID === "ve1d47o7ea" && !HasNoAbilities($field[$i])) {
            AddCounters($player, $fieldZone . "-" . $i, "buff", 1);
        }
        // Astromech Attendant (mloejozihs): [CB] whenever you brew → draw into memory
        if($field[$i]->CardID === "mloejozihs" && !HasNoAbilities($field[$i])) {
            if(IsClassBonusActive($player, ["CLERIC"])) {
                DrawIntoMemory($player, 1);
            }
        }
        // Essence Crucible (DF5Ffwv7DJ): [Arisanna Bonus] whenever you brew → refinement counter
        if($field[$i]->CardID === "DF5Ffwv7DJ" && !HasNoAbilities($field[$i])) {
            if(IsArisannaBonusActive($player)) {
                AddCounters($player, $fieldZone . "-" . $i, "refinement", 1);
            }
        }
    }
}

// --- DeclareBrew handler: player chose YES/NO to brew ---
$customDQHandlers["DeclareBrew"] = function($player, $parts, $lastDecision) {
    global $brewCosts;
    $cardID = $parts[0];
    $reserveCost = intval($parts[1]);

    if($lastDecision !== "YES") {
        DecisionQueueController::StoreVariable("wasBrewed", "NO");
        DecisionQueueController::StoreVariable("additionalCostPaid", "NO");
        DecisionQueueController::StoreVariable("brewMode", "");
        for($i = 0; $i < $reserveCost; ++$i) {
            DecisionQueueController::AddDecision($player, "CUSTOM", "ReserveCard", 100);
        }
        DecisionQueueController::AddDecision($player, "CUSTOM", "EffectStackOpportunity", 100);
        return;
    }

    // Player chose to brew — start sequential herb selection
    DecisionQueueController::StoreVariable("wasBrewed", "YES");
    DecisionQueueController::StoreVariable("additionalCostPaid", "NO");
    DecisionQueueController::StoreVariable("brewMode", "");

    if($cardID === "gnYM2V6TTw") {
        $allHerbs = ZoneSearch("myField", cardSubtypes: ["HERB"]);
        $herbsByCard = [];
        foreach($allHerbs as $herbMZ) {
            $herbObj = GetZoneObject($herbMZ);
            if($herbObj === null) continue;
            $hid = $herbObj->CardID;
            if(!isset($herbsByCard[$hid])) $herbsByCard[$hid] = [];
            $herbsByCard[$hid][] = $herbMZ;
        }
        $firstPickChoices = [];
        foreach($herbsByCard as $sameNameHerbs) {
            if(count($sameNameHerbs) >= 2) {
                foreach($sameNameHerbs as $mz) $firstPickChoices[] = $mz;
            }
        }
        if(empty($firstPickChoices)) {
            DecisionQueueController::StoreVariable("wasBrewed", "NO");
            for($i = 0; $i < $reserveCost; ++$i) {
                DecisionQueueController::AddDecision($player, "CUSTOM", "ReserveCard", 100);
            }
            DecisionQueueController::AddDecision($player, "CUSTOM", "EffectStackOpportunity", 100);
            return;
        }
        DecisionQueueController::StoreVariable("brewMode", "SOOTHING_SAME_NAME");
        DecisionQueueController::StoreVariable("brewChosen", "");
        $choiceStr = implode("&", $firstPickChoices);
        DecisionQueueController::AddDecision($player, "MZCHOOSE", $choiceStr, 100, tooltip:"Sacrifice_an_herb");
        DecisionQueueController::AddDecision($player, "CUSTOM", "BrewSelectHerb", 100);
        return;
    }

    if($cardID === "vt9y597fqr") {
        $firstPickChoices = ZoneSearch("myField", cardSubtypes: ["HERB"]);
        if(empty($firstPickChoices)) {
            DecisionQueueController::StoreVariable("wasBrewed", "NO");
            for($i = 0; $i < $reserveCost; ++$i) {
                DecisionQueueController::AddDecision($player, "CUSTOM", "ReserveCard", 100);
            }
            DecisionQueueController::AddDecision($player, "CUSTOM", "EffectStackOpportunity", 100);
            return;
        }
        DecisionQueueController::StoreVariable("brewMode", "PRIMA_DIFFERENT_NAMES");
        DecisionQueueController::StoreVariable("brewChosen", "");
        $choiceStr = implode("&", $firstPickChoices);
        DecisionQueueController::AddDecision($player, "MZCHOOSE", $choiceStr, 100, tooltip:"Sacrifice_an_herb");
        DecisionQueueController::AddDecision($player, "CUSTOM", "BrewSelectHerb", 100);
        return;
    }

    $slots = $brewCosts[$cardID];
    $steps = FlattenBrewSlots($slots);

    DecisionQueueController::StoreVariable("brewSteps", json_encode($steps));
    DecisionQueueController::StoreVariable("brewChosen", "");
    DecisionQueueController::StoreVariable("brewStepIndex", "0");

    $available = GetFilteredHerbs($steps[0]['filter'], []);
    if(empty($available)) {
        DecisionQueueController::StoreVariable("wasBrewed", "NO");
        for($i = 0; $i < $reserveCost; ++$i) {
            DecisionQueueController::AddDecision($player, "CUSTOM", "ReserveCard", 100);
        }
        DecisionQueueController::AddDecision($player, "CUSTOM", "EffectStackOpportunity", 100);
        return;
    }
    $herbStr = implode("&", $available);
    DecisionQueueController::AddDecision($player, "MZCHOOSE", $herbStr, 100, tooltip:$steps[0]['tooltip']);
    DecisionQueueController::AddDecision($player, "CUSTOM", "BrewSelectHerb", 100);
};

// --- BrewSelectHerb handler: process one herb selection and queue next or finalize ---
$customDQHandlers["BrewSelectHerb"] = function($player, $parts, $lastDecision) {
    $chosen = DecisionQueueController::GetVariable("brewChosen");
    $chosen = $chosen === "" ? $lastDecision : $chosen . "," . $lastDecision;
    DecisionQueueController::StoreVariable("brewChosen", $chosen);

    if(DecisionQueueController::GetVariable("brewMode") === "SOOTHING_SAME_NAME") {
        $chosenArr = array_values(array_filter(explode(",", $chosen)));
        if(count($chosenArr) >= 2) {
            DecisionQueueController::StoreVariable("brewMode", "");
            BrewFinalizeHerbs($player, $chosen);
            return;
        }
        if(empty($chosenArr)) return;
        $firstObj = GetZoneObject($chosenArr[0]);
        if($firstObj === null) return;
        $secondPickChoices = GetFilteredHerbs("CARD:" . $firstObj->CardID, $chosenArr);
        if(empty($secondPickChoices)) return;
        DecisionQueueController::AddDecision($player, "MZCHOOSE", implode("&", $secondPickChoices), 100, tooltip:"Sacrifice_a_matching_herb");
        DecisionQueueController::AddDecision($player, "CUSTOM", "BrewSelectHerb", 100);
        return;
    }

    if(DecisionQueueController::GetVariable("brewMode") === "PRIMA_DIFFERENT_NAMES") {
        $chosenArr = array_values(array_filter(explode(",", $chosen)));
        if(count($chosenArr) >= 4) {
            DecisionQueueController::StoreVariable("brewMode", "");
            BrewFinalizeHerbs($player, $chosen);
            return;
        }
        $nextPickChoices = GetDifferentNameHerbs($chosenArr);
        if(empty($nextPickChoices)) return;
        DecisionQueueController::AddDecision($player, "MZCHOOSE", implode("&", $nextPickChoices), 100, tooltip:"Sacrifice_a_different-name_herb");
        DecisionQueueController::AddDecision($player, "CUSTOM", "BrewSelectHerb", 100);
        return;
    }

    $stepIndex = intval(DecisionQueueController::GetVariable("brewStepIndex")) + 1;
    DecisionQueueController::StoreVariable("brewStepIndex", strval($stepIndex));

    $steps = json_decode(DecisionQueueController::GetVariable("brewSteps"), true);

    if($stepIndex >= count($steps)) {
        // All herbs selected — sacrifice them and fire brew event
        BrewFinalizeHerbs($player, $chosen);
        return;
    }

    // More herbs needed — queue next selection
    $excludeMZs = explode(",", $chosen);
    $nextStep = $steps[$stepIndex];
    $available = GetFilteredHerbs($nextStep['filter'], $excludeMZs);
    if(empty($available)) {
        BrewFinalizeHerbs($player, $chosen);
        return;
    }
    $herbStr = implode("&", $available);
    DecisionQueueController::AddDecision($player, "MZCHOOSE", $herbStr, 100, tooltip:$nextStep['tooltip']);
    DecisionQueueController::AddDecision($player, "CUSTOM", "BrewSelectHerb", 100);
};

/**
 * Sacrifice all chosen herbs and fire the Brew event.
 */
function BrewFinalizeHerbs($player, $chosenStr) {
    $chosenArr = array_values(array_unique(array_filter(explode(",", $chosenStr), function($mzID) {
        return $mzID !== "";
    })));
    usort($chosenArr, function($a, $b) {
        return MZNumericIndex($b) - MZNumericIndex($a);
    });
    $herbCount = 0;
    foreach($chosenArr as $herbMZ) {
        $herbObj = GetZoneObject($herbMZ);
        if($herbObj !== null) {
            $herbCount++;
            OnLeaveField($player, $herbMZ);
            MZMove($player, $herbMZ, "myGraveyard");
        }
    }
    TriggerHerbSacrificeEffects($player, $herbCount);
    DecisionQueueController::CleanupRemovedCards();
    OnBrew($player);
    DecisionQueueController::AddDecision($player, "CUSTOM", "EffectStackOpportunity", 100);
}

// --- Gather trigger: whenever you Gather, check for listening cards ---
function OnGather($player) {
    $field = &GetField($player);
    for($i = 0; $i < count($field); ++$i) {
        if($field[$i]->removed) continue;
        switch($field[$i]->CardID) {
            case "ettczb14m4": // Alchemist's Kit: whenever you gather → refinement counter
                if(!HasNoAbilities($field[$i])) {
                    AddCounters($player, "myField-" . $i, "refinement", 1);
                }
                break;
            case "lgdlx7mdk0": // Cinderbloom Tender: [Class Bonus] Whenever you gather, deal 1 damage to each champion
                if(!HasNoAbilities($field[$i]) && IsClassBonusActive($player, ["CLERIC"])) {
                    $opponent = ($player == 1) ? 2 : 1;
                    DealChampionDamage($player, 1);
                    DealChampionDamage($opponent, 1);
                }
                break;
        }
    }
}

// --- Fertile Grounds (8bls6g7xgw): recollection phase — summon a token copy of an Herb you control ---
function FertileGroundsRecollection($player) {
    $herbs = ZoneSearch("myField", cardSubtypes: ["HERB"]);
    if(empty($herbs)) return;
    if(count($herbs) == 1) {
        // Only one herb — copy it automatically
        $obj = GetZoneObject($herbs[0]);
        MZAddZone($player, "myField", $obj->CardID);
        return;
    }
    $herbStr = implode("&", $herbs);
    DecisionQueueController::AddDecision($player, "MZCHOOSE", $herbStr, 1, tooltip:"Choose_herb_to_copy");
    DecisionQueueController::AddDecision($player, "CUSTOM", "FertileGroundsCopy", 1);
}

$customDQHandlers["FertileGroundsCopy"] = function($player, $parts, $lastDecision) {
    if($lastDecision === "-" || $lastDecision === "" || $lastDecision === "PASS") return;
    $obj = GetZoneObject($lastDecision);
    if($obj === null) return;
    MZAddZone($player, "myField", $obj->CardID);
};

// --- Starlit Apothecary (ShQkyQMBCT): recollection phase — summon a brewed Potion/Herb copy unless opponent pays ---
function StarlitApothecaryResolveCopy($player, $targetMZ) {
    $targetObj = GetZoneObject($targetMZ);
    if($targetObj === null || $targetObj->removed) return;

    MZAddZone($player, "myField", $targetObj->CardID);
    if(!PropertyContains(CardSubtypes($targetObj->CardID), "POTION")) return;

    $field = &GetField($player);
    $newIndex = count($field) - 1;
    if($newIndex < 0 || $field[$newIndex]->removed) return;
    if(!isset($field[$newIndex]->Counters) || !is_array($field[$newIndex]->Counters)) {
        $field[$newIndex]->Counters = [];
    }
    $field[$newIndex]->Counters["brewed"] = 1;
}

function StarlitApothecaryQueueCopy($player, $targetMZ) {
    $targetObj = GetZoneObject($targetMZ);
    if($targetObj === null || $targetObj->removed) return;

    $opponent = ($player == 1) ? 2 : 1;
    if(CountAvailableReservePayments($opponent) < 4) {
        StarlitApothecaryResolveCopy($player, $targetMZ);
        return;
    }

    DecisionQueueController::AddDecision($opponent, "YESNO", "-", 1,
        tooltip:"Pay_(4)_to_prevent_Starlit_Apothecary?");
    DecisionQueueController::AddDecision($opponent, "CUSTOM",
        "StarlitApothecaryPayChoice|" . $player . "|" . $targetMZ, 1);
}

function StarlitApothecaryRecollection($player) {
    $targets = array_merge(
        ZoneSearch("myField", cardSubtypes: ["POTION"]),
        ZoneSearch("myField", cardSubtypes: ["HERB"])
    );
    if(empty($targets)) return;

    if(count($targets) == 1) {
        StarlitApothecaryQueueCopy($player, $targets[0]);
        return;
    }

    DecisionQueueController::AddDecision($player, "MZCHOOSE", implode("&", $targets), 1,
        tooltip:"Choose_Potion_or_Herb_to_copy");
    DecisionQueueController::AddDecision($player, "CUSTOM", "StarlitApothecaryChoose", 1);
}

$customDQHandlers["StarlitApothecaryChoose"] = function($player, $parts, $lastDecision) {
    if($lastDecision === "-" || $lastDecision === "" || $lastDecision === "PASS") return;
    StarlitApothecaryQueueCopy($player, $lastDecision);
};

$customDQHandlers["StarlitApothecaryPayChoice"] = function($payingPlayer, $parts, $lastDecision) {
    $controller = intval($parts[0] ?? $payingPlayer);
    $targetMZ = $parts[1] ?? "";

    if($lastDecision === "YES" && CountAvailableReservePayments($payingPlayer) >= 4) {
        for($i = 0; $i < 4; ++$i) {
            DecisionQueueController::AddDecision($payingPlayer, "CUSTOM", "ReserveCard", 100);
        }
        return;
    }

    StarlitApothecaryResolveCopy($controller, $targetMZ);
};

// --- Potion Infusion sacrifice trigger: check for infusion TurnEffects before potion's own effect ---
function ProcessPotionInfusionTriggers($player, $potionMZ) {
    $obj = GetZoneObject($potionMZ);
    if($obj === null) return;
    // Store potion CardID for EnhancePotencyCheck
    DecisionQueueController::StoreVariable("potionCardID", $obj->CardID);
    $te = $obj->TurnEffects;
    if(!is_array($te)) return;

    foreach($te as $effect) {
        switch($effect) {
            case "INFUSION_CLARITY": // Potion Infusion: Clarity — draw 2
                Draw($player, 2);
                break;
            case "INFUSION_STARLIGHT": // Potion Infusion: Starlight — +4 level until eot
                $champs = ZoneSearch("myField", ["CHAMPION"]);
                if(!empty($champs)) {
                    AddTurnEffect($champs[0], "INFUSION_STARLIGHT");
                }
                break;
            case "INFUSION_FROSTBITE": // Potion Infusion: Frostbite — rest target unit, next water damage +4
                $units = array_merge(
                    ZoneSearch("myField", ["ALLY", "CHAMPION"]),
                    ZoneSearch("theirField", ["ALLY", "CHAMPION"])
                );
                $units = FilterSpellshroudTargets($units);
                if(!empty($units)) {
                    $targetStr = implode("&", $units);
                    DecisionQueueController::AddDecision($player, "MZCHOOSE", $targetStr, 1, tooltip:"Rest_target_unit_(Frostbite)");
                    DecisionQueueController::AddDecision($player, "CUSTOM", "InfusionFrostbiteApply", 1);
                }
                break;
            case "ENHANCE_POTENCY": // Enhance Potency — copy the next sacrifice ability
                DecisionQueueController::StoreVariable("enhancePotency", "YES");
                break;
            case "INFUSION_BLAZE": // Potion Infusion: Blaze — deal 4 to target attacking ally
                $atkMZ = DecisionQueueController::GetVariable("CombatAttacker");
                if($atkMZ !== null && $atkMZ !== "-" && $atkMZ !== "") {
                    $atkObj = GetZoneObject($atkMZ);
                    if($atkObj !== null && !$atkObj->removed
                       && PropertyContains(EffectiveCardType($atkObj), "ALLY")) {
                        DealDamage($player, $potionMZ, $atkMZ, 4);
                    }
                }
                break;
            case "INFUSION_SEAL":
                QueueNegateActivation($player, [], "default", 2);
                break;
            case "INFUSION_VOLATILITY":
                $damage = 4 + EngineRandomInt(1, 6);
                for($p = 1; $p <= 2; ++$p) {
                    if($p == $player) continue;
                    DealChampionDamage($p, $damage);
                }
                break;
        }
    }
}

$customDQHandlers["InfusionFrostbiteApply"] = function($player, $parts, $lastDecision) {
    if($lastDecision === "-" || $lastDecision === "") return;
    $targetObj = GetZoneObject($lastDecision);
    if($targetObj === null) return;
    // Rest the target
    ExhaustCard($player, $lastDecision);
    // Mark it: next water damage +4
    AddTurnEffect($lastDecision, "FROSTBITE_WATER_VULN");
};

// --- Enhance Potency: re-invoke the potion's SACRIFICE ability if enhanced ---
function EnhancePotencyCheck($player) {
    if(DecisionQueueController::GetVariable("enhancePotency") === "YES") {
        DecisionQueueController::StoreVariable("enhancePotency", "NO");
        global $activateAbilityAbilities;
        $cardID = DecisionQueueController::GetVariable("potionCardID");
        if($cardID !== null && isset($activateAbilityAbilities[$cardID . ":0"])) {
            $activateAbilityAbilities[$cardID . ":0"]($player);
        }
    }
}

// --- Barter Herbs: sacrifice up to 2 herbs, summon chosen herbs ---
function BarterHerbsSacrificeLoop($player, $count) {
    if($count >= 2) return;
    $herbs = ZoneSearch("myField", cardSubtypes: ["HERB"]);
    if(empty($herbs)) return;
    DecisionQueueController::AddDecision($player, "MZMAYCHOOSE", implode("&", $herbs), 1, tooltip:"Sacrifice_an_Herb?");
    DecisionQueueController::AddDecision($player, "CUSTOM", "BarterHerbsSacrifice|$count", 1);
}

$customDQHandlers["BarterHerbsSacrifice"] = function($player, $parts, $lastDecision) {
    $count = intval($parts[0]);
    if($lastDecision === "-" || $lastDecision === "" || $lastDecision === "PASS") return;
    OnLeaveField($player, $lastDecision);
    MZMove($player, $lastDecision, "myGraveyard");
    DecisionQueueController::CleanupRemovedCards();
    // Present choice of which herb to summon
    BarterHerbsChooseType($player, $count);
};

function BarterHerbsChooseType($player, $count) {
    $herbTokens = ["i0a5uhjxhk", "5joh300z2s", "bd7ozuj68m", "soporhlq2k", "jnltv5klry", "69iq4d5vet"];
    foreach($herbTokens as $herbID) {
        MZAddZone($player, "myTempZone", $herbID);
    }
    $choices = ZoneSearch("myTempZone");
    DecisionQueueController::AddDecision($player, "MZCHOOSE", implode("&", $choices), 1, tooltip:"Choose_Herb_to_summon");
    DecisionQueueController::AddDecision($player, "CUSTOM", "BarterHerbsSelect|$count", 1);
}

$customDQHandlers["BarterHerbsSelect"] = function($player, $parts, $lastDecision) {
    $count = intval($parts[0]);
    $chosenID = null;
    if($lastDecision !== "-" && $lastDecision !== "" && $lastDecision !== "PASS") {
        $obj = GetZoneObject($lastDecision);
        if($obj !== null) $chosenID = $obj->CardID;
    }
    // Clear TempZone
    $tempZone = &GetTempZone($player);
    foreach($tempZone as $t) { $t->Remove(); }
    $tempZone = [];
    if($chosenID !== null) {
        MZAddZone($player, "myField", $chosenID);
    }
    BarterHerbsSacrificeLoop($player, $count + 1);
};

// --- Brewing Kit: sacrifice 3 herbs then look at top 6 for a Potion ---
function BrewingKitHerbSacrifice($player, $count) {
    if($count >= 3) {
        BrewingKitLookTop6($player);
        return;
    }
    $herbs = ZoneSearch("myField", cardSubtypes: ["HERB"]);
    if(count($herbs) < 3 - $count) return;
    DecisionQueueController::AddDecision($player, "MZCHOOSE", implode("&", $herbs), 1, tooltip:"Sacrifice_Herb_(" . ($count+1) . "_of_3)");
    DecisionQueueController::AddDecision($player, "CUSTOM", "BrewingKitHerbSac|$count", 1);
}

$customDQHandlers["BrewingKitHerbSac"] = function($player, $parts, $lastDecision) {
    $count = intval($parts[0]);
    if($lastDecision === "-" || $lastDecision === "") return;
    OnLeaveField($player, $lastDecision);
    MZMove($player, $lastDecision, "myGraveyard");
    DecisionQueueController::CleanupRemovedCards();
    BrewingKitHerbSacrifice($player, $count + 1);
};

function BrewingKitLookTop6($player) {
    $deck = &GetDeck($player);
    $n = min(6, count($deck));
    if($n == 0) return;
    // Move top N from deck to TempZone
    for($i = 0; $i < $n; ++$i) {
        MZAddZone($player, "myTempZone", $deck[0]->CardID);
        array_shift($deck);
    }
    // Reindex deck
    for($i = 0; $i < count($deck); ++$i) {
        $deck[$i]->mzIndex = $i;
    }
    // Find Potion items in TempZone
    $potions = ZoneSearch("myTempZone", cardSubtypes: ["POTION"]);
    if(!empty($potions)) {
        DecisionQueueController::AddDecision($player, "MZMAYCHOOSE", implode("&", $potions), 1, tooltip:"Reveal_a_Potion_and_put_into_hand?");
        DecisionQueueController::AddDecision($player, "CUSTOM", "BrewingKitPotionPick", 1);
    } else {
        BrewingKitCleanup($player);
    }
}

$customDQHandlers["BrewingKitPotionPick"] = function($player, $parts, $lastDecision) {
    if($lastDecision !== "-" && $lastDecision !== "" && $lastDecision !== "PASS") {
        MZMove($player, $lastDecision, "myHand");
    }
    BrewingKitCleanup($player);
};

function BrewingKitCleanup($player) {
    $remaining = ZoneSearch("myTempZone");
    $deck = &GetDeck($player);
    $tempZone = &GetTempZone($player);
    foreach($tempZone as $obj) {
        if(!$obj->removed) {
            $newObj = new Deck($obj->CardID, 'Deck', $player);
            array_push($deck, $newObj);
            $obj->Remove();
        }
    }
    $tempZone = [];
    // Reindex deck
    for($i = 0; $i < count($deck); ++$i) {
        $deck[$i]->mzIndex = $i;
    }
}

// --- Distilled Atrophy (h38lrj5221): delevels target champion X times, deals X if would reach 0 ---
function DistilledAtrophyApplyDirect($player, $targetMZ) {
    $x = intval(DecisionQueueController::GetVariable("ageCounters"));
    if($x <= 0 || $targetMZ === "-" || $targetMZ === "" || $targetMZ === "PASS") return;
    $targetIsOpponent = (strpos($targetMZ, "their") === 0);
    $targetPlayer = $targetIsOpponent ? (1 - $player) : $player;
    // Get current level before deleveling
    $champField = &GetField($targetPlayer);
    $currentLevel = 1;
    foreach($champField as $obj) {
        if(!$obj->removed && PropertyContains(EffectiveCardType($obj), "CHAMPION")) {
            $currentLevel = ObjectCurrentLevel($obj);
            break;
        }
    }
    for($i = 0; $i < $x; $i++) {
        Delevel($targetPlayer);
    }
    // Deal X damage if champion's level would be 0 or lower
    if($currentLevel <= $x) {
        DealChampionDamage($targetPlayer, $x);
    }
}

// --- Arisanna, Master Alchemist (ltv5klryvf): Inherited Effect ---
// At the beginning of your end phase, you may sacrifice two Herbs with the same name to draw a card.
// Flow: check done in EndPhase(); if valid herb pairs exist → MZMAYCHOOSE (passable) → auto-sacrifice partner → Draw.

function MasterAlchemistGetDuplicateHerbs() {
    $herbs = ZoneSearch("myField", cardSubtypes: ["HERB"]);
    $counts = [];
    foreach($herbs as $mz) {
        $obj = GetZoneObject($mz);
        if($obj === null) continue;
        $counts[$obj->CardID][] = $mz;
    }
    // Return only herbs whose CardID appears at least twice
    $valid = [];
    foreach($counts as $cardID => $mzList) {
        if(count($mzList) >= 2) {
            $valid = array_merge($valid, $mzList);
        }
    }
    return array_unique($valid);
}

$customDQHandlers["MasterAlchemistEndPhaseMayChoose"] = function($player, $parts, $lastDecision) {
    if($lastDecision === "-" || $lastDecision === "" || $lastDecision === "PASS") return;
    MasterAlchemistResolveHerbPairAndDraw($player, $lastDecision);
};

function MasterAlchemistResolveHerbPairAndDraw($player, $chosenMZ) {
    $chosenObj = GetZoneObject($chosenMZ);
    if($chosenObj === null) return;
    $targetCardID = $chosenObj->CardID;
    // Find the matching second herb (different mzID, same CardID)
    $herbs = ZoneSearch("myField", cardSubtypes: ["HERB"]);
    $partner = null;
    foreach($herbs as $mz) {
        if($mz === $chosenMZ) continue;
        $obj = GetZoneObject($mz);
        if($obj !== null && $obj->CardID === $targetCardID) {
            $partner = $mz;
            break;
        }
    }
    if($partner === null) return; // Pair no longer available (edge case)
    // Sacrifice higher index first to avoid index shifts
    $first = $chosenMZ;
    $second = $partner;
    $idxFirst = MZNumericIndex($first);
    $idxSecond = MZNumericIndex($second);
    if($idxFirst < $idxSecond) { [$first, $second] = [$second, $first]; }
    DoSacrificeFighter($player, $first);
    DoSacrificeFighter($player, $second);
    DecisionQueueController::CleanupRemovedCards();
    Draw($player, 1);
}

$customDQHandlers["MasterAlchemistHerbSelect"] = function($player, $parts, $lastDecision) {
    if($lastDecision === "-" || $lastDecision === "" || $lastDecision === "PASS") return;
    MasterAlchemistResolveHerbPairAndDraw($player, $lastDecision);
};

// --- Wildgrowth Elixir (tjot4nmxqs): puts X buff counters on target ally ---
function WildgrowthElixirApplyDirect($player, $targetMZ) {
    $x = intval(DecisionQueueController::GetVariable("ageCounters"));
    if($x <= 0 || $targetMZ === "-" || $targetMZ === "" || $targetMZ === "PASS") return;
    AddCounters($player, $targetMZ, "buff", $x);
}

// --- Bottled Forgelight (g616r0zadf): deal 2 damage to a unit ---
function BottledForgelightDamageEffect($player, $sourceMZ) {
    $units = array_merge(
        ZoneSearch("myField", ["ALLY", "CHAMPION"]),
        ZoneSearch("theirField", ["ALLY", "CHAMPION"])
    );
    $units = FilterSpellshroudTargets($units);
    if(empty($units)) return;
    DecisionQueueController::StoreVariable("bfgSourceMZ", $sourceMZ);
    DecisionQueueController::AddDecision($player, "MZCHOOSE", implode("&", $units), 1, tooltip:"Deal_2_damage_to_a_unit_(Bottled_Forgelight)");
    DecisionQueueController::AddDecision($player, "CUSTOM", "BFG_DealDamage", 1);
}

$customDQHandlers["BFG_DealDamage"] = function($player, $parts, $lastDecision) {
    if($lastDecision === "-" || $lastDecision === "" || $lastDecision === "PASS") return;
    $srcMZ = DecisionQueueController::GetVariable("bfgSourceMZ");
    DealDamage($player, $srcMZ, $lastDecision, 2);
};

?>

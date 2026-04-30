<?php
// GrandArchiveSim/Custom/OpportunityLogic.php
// Custom logic for the "Opportunity" card in the Grand Archive Sim.


/**
 * Check if a player currently has Opportunity to act at fast speed.
 * A player has Opportunity when:
 *  - The EffectStack is non-empty (a spell/ability is pending resolution), OR
 *  - Combat is active (between attack declaration and cleanup)
 */
function HasOpportunity($player) {
    if(!empty(GetLiveEffectStackEntries())) return true;
    if(IsCombatActive()) return true;
    return false;
}

function GetLiveEffectStackEntries() {
    $effectStack = GetEffectStack();
    return array_values(array_filter($effectStack, function($obj) {
        return $obj !== null && !(isset($obj->removed) && $obj->removed);
    }));
}

function GetEffectStackActivationTargets($player, $filters = []) {
    $effectStack = GetLiveEffectStackEntries();
    $targets = [];
    for($i = 0; $i < count($effectStack); ++$i) {
        $obj = $effectStack[$i];
        if($obj === null || $obj->removed) continue;
        if(is_array($obj->TurnEffects ?? null) && in_array("CANT_BE_NEGATED", $obj->TurnEffects)) continue;
        if(isset($filters['excludeController']) && intval($filters['excludeController']) === intval($obj->Controller)) continue;
        if(isset($filters['controller']) && intval($filters['controller']) !== intval($obj->Controller)) continue;
        $cardID = $obj->CardID;
        if(isset($filters['types'])) {
            $matched = false;
            foreach($filters['types'] as $type) {
                if(PropertyContains(CardType($cardID), $type)) {
                    $matched = true;
                    break;
                }
            }
            if(!$matched) continue;
        }
        if(isset($filters['subtypes'])) {
            $matched = false;
            foreach($filters['subtypes'] as $subtype) {
                if(PropertyContains(CardSubtypes($cardID), $subtype)) {
                    $matched = true;
                    break;
                }
            }
            if(!$matched) continue;
        }
        if(isset($filters['elements']) && !in_array(CardElement($cardID), $filters['elements'])) continue;
        if(isset($filters['reserveCost']) && intval(CardCost_reserve($cardID)) !== intval($filters['reserveCost'])) continue;
        if(isset($filters['sourceZone']) && GetEffectStackSourceZone("EffectStack-" . $i) !== $filters['sourceZone']) continue;
        $targets[] = "EffectStack-" . $i;
    }
    return $targets;
}

function GetEffectStackSourceZone($mzID) {
    $parts = explode("-", $mzID);
    if(count($parts) < 2) return "";
    $idx = intval($parts[1]);
    $_ti = json_decode(GetMacroTurnIndex() ?: '{}', true) ?: [];
    return $_ti["EffectStackSourceZone"][$idx] ?? "";
}

function TrackEffectStackSourceZone($mzID, $sourceZone) {
    $parts = explode("-", $mzID);
    if(count($parts) < 2) return;
    $_ti = json_decode(GetMacroTurnIndex() ?: '{}', true) ?: [];
    if(!isset($_ti["EffectStackSourceZone"])) $_ti["EffectStackSourceZone"] = [];
    $_ti["EffectStackSourceZone"][intval($parts[1])] = $sourceZone;
    SetMacroTurnIndex(json_encode($_ti));
}

function ReconcileEffectStackSourceZones() {
    $_ti = json_decode(GetMacroTurnIndex() ?: '{}', true) ?: [];
    if(!isset($_ti["EffectStackSourceZone"]) || !is_array($_ti["EffectStackSourceZone"])) return;
    $oldSources = $_ti["EffectStackSourceZone"];
    $effectStack = GetEffectStack();
    $newSources = [];
    for($i = 0; $i < count($effectStack); ++$i) {
        $obj = $effectStack[$i];
        if($obj === null || $obj->removed) continue;
        if(isset($oldSources[$i])) $newSources[] = $oldSources[$i];
    }
    if(empty($newSources)) unset($_ti["EffectStackSourceZone"]);
    else $_ti["EffectStackSourceZone"] = $newSources;
    SetMacroTurnIndex(empty($_ti) ? '{}' : json_encode($_ti));
}

function TrackCardActivationNegated($player, $cardID, $negatedController = null) {
    $_ti = json_decode(GetMacroTurnIndex() ?: '{}', true) ?: [];
    if(!isset($_ti["CardActivationNegated"])) $_ti["CardActivationNegated"] = [];
    $_ti["CardActivationNegated"][$player] = ($_ti["CardActivationNegated"][$player] ?? 0) + 1;
    if(!isset($_ti["CardActivationNegatedCards"])) $_ti["CardActivationNegatedCards"] = [];
    if(!isset($_ti["CardActivationNegatedCards"][$player])) $_ti["CardActivationNegatedCards"][$player] = [];
    $_ti["CardActivationNegatedCards"][$player][] = $cardID;
    if($negatedController !== null) {
        if(!isset($_ti["ControlledActivationNegated"])) $_ti["ControlledActivationNegated"] = [];
        $_ti["ControlledActivationNegated"][$negatedController] = ($_ti["ControlledActivationNegated"][$negatedController] ?? 0) + 1;
        if(!isset($_ti["ControlledActivationNegatedCards"])) $_ti["ControlledActivationNegatedCards"] = [];
        if(!isset($_ti["ControlledActivationNegatedCards"][$negatedController])) $_ti["ControlledActivationNegatedCards"][$negatedController] = [];
        $_ti["ControlledActivationNegatedCards"][$negatedController][] = $cardID;
    }
    SetMacroTurnIndex(json_encode($_ti));
}

function CardActivationNegatedThisTurn($player) {
    $_ti = json_decode(GetMacroTurnIndex() ?: '{}', true) ?: [];
    return ($_ti["CardActivationNegated"][$player] ?? 0) > 0;
}

function TriggerReversalsPolarity($controller, $cardID) {
    if(CardElement($cardID) !== "ARCANE") return;
    $damage = max(0, intval(CardCost_reserve($cardID)));
    if($damage <= 0) return;
    $targets = array_merge(
        ZoneSearch("myField", ["CHAMPION"], forPlayer: $controller),
        ZoneSearch("theirField", ["CHAMPION"], forPlayer: $controller)
    );
    if(empty($targets)) return;
    $targetStr = implode("&", $targets);
    $field = &GetField($controller);
    for($i = 0; $i < count($field); ++$i) {
        if($field[$i]->removed || $field[$i]->CardID !== "zVdqJRbsk1" || HasNoAbilities($field[$i])) continue;
        DecisionQueueController::AddDecision($controller, "MZCHOOSE", $targetStr, 1, "Choose_champion_for_Reversal's_Polarity");
        DecisionQueueController::AddDecision($controller, "CUSTOM", "ReversalsPolarityDamage|" . $damage, 1);
    }
}

$customDQHandlers["ReversalsPolarityDamage"] = function($player, $parts, $lastDecision) {
    if($lastDecision === "-" || $lastDecision === "" || $lastDecision === "PASS") return;
    $damage = max(0, intval($parts[0] ?? 0));
    if($damage <= 0) return;
    DealDamage($player, "zVdqJRbsk1", $lastDecision, $damage);
};

function NegatedActivationDestination($mzID, $mode = "default") {
    $obj = GetZoneObject($mzID);
    if($obj === null) return "myGraveyard";
    if($mode === "banish") return "myBanish";
    if($mode === "memory") return "myMemory";
    if(PropertyContains(CardType($obj->CardID), "REGALIA")) return "myBanish";
    return "myGraveyard";
}

function NegateCardActivation($player, $targetMZ, $destinationMode = "default") {
    $target = GetZoneObject($targetMZ);
    if($target === null || $target->removed) return false;
    $cardID = $target->CardID;
    $controller = intval($target->Controller);
    $dest = NegatedActivationDestination($targetMZ, $destinationMode);
    global $playerID;
    $savedPlayerID = $playerID;
    $playerID = $controller;
    MZMove($controller, $targetMZ, $dest);
    $playerID = $savedPlayerID;
    ReconcileEffectStackSourceZones();
    TrackCardActivationNegated($player, $cardID, $controller);
    TriggerReversalsPolarity($controller, $cardID);
    QueueConstellatorySpireTrigger($player);
    DecisionQueueController::CleanupRemovedCards();
    return true;
}

function QueueNegateActivation($player, $filters = [], $destinationMode = "default", $payAmount = -1, $handler = "NegateActivationResolve") {
    $targets = GetEffectStackActivationTargets($player, $filters);
    if(empty($targets)) return;
    DecisionQueueController::AddDecision($player, "MZCHOOSE", implode("&", $targets), 1, "Choose_activation_to_negate");
    DecisionQueueController::AddDecision($player, "CUSTOM", $handler . "|" . $destinationMode . "|" . intval($payAmount), 1);
}

function QueueNegateAllActivationsByCard($player, $cardID, $controller, $destinationMode = "default", $payAmount = -1) {
    $matches = [];
    $effectStack = GetEffectStack();
    for($i = 0; $i < count($effectStack); ++$i) {
        $obj = $effectStack[$i];
        if($obj === null || $obj->removed) continue;
        if(is_array($obj->TurnEffects ?? null) && in_array("CANT_BE_NEGATED", $obj->TurnEffects)) continue;
        if($obj->CardID !== $cardID) continue;
        if(intval($obj->Controller) !== intval($controller)) continue;
        $matches[] = "EffectStack-" . $i;
    }
    if(empty($matches)) return;

    DecisionQueueController::StoreVariable("NegateByCardTargets", implode(",", $matches));
    DecisionQueueController::StoreVariable("NegateByCardDestination", $destinationMode);
    DecisionQueueController::StoreVariable("NegateByCardPay", strval(intval($payAmount)));
    DecisionQueueController::StoreVariable("NegateByCardPlayer", strval($player));
    DecisionQueueController::AddDecision($player, "CUSTOM", "NegateByCardContinue|0", 1);
}

$customDQHandlers["NegateActivationResolve"] = function($player, $parts, $lastDecision) {
    if($lastDecision === "-" || $lastDecision === "" || $lastDecision === "PASS") return;
    $destinationMode = $parts[0] ?? "default";
    $payAmount = intval($parts[1] ?? -1);
    if($payAmount <= 0) {
        NegateCardActivation($player, $lastDecision, $destinationMode);
        return;
    }
    $target = GetZoneObject($lastDecision);
    if($target === null || $target->removed) return;
    DecisionQueueController::StoreVariable("pendingNegateTarget", $lastDecision);
    DecisionQueueController::StoreVariable("pendingNegateDestination", $destinationMode);
    DecisionQueueController::StoreVariable("pendingNegatePayAmount", strval($payAmount));
    $controller = intval($target->Controller);
    if(CountAvailableReservePayments($controller) < $payAmount) {
        NegateCardActivation($player, $lastDecision, $destinationMode);
        return;
    }
    DecisionQueueController::AddDecision($controller, "YESNO", "-", 1, "Pay_" . $payAmount . "_to_prevent_negate?");
    DecisionQueueController::AddDecision($controller, "CUSTOM", "NegateActivationPayChoice|" . $player, 1);
};

$customDQHandlers["NegateByCardContinue"] = function($player, $parts, $lastDecision) {
    $index = intval($parts[0] ?? 0);
    $targetsStr = DecisionQueueController::GetVariable("NegateByCardTargets") ?? "";
    if($targetsStr === "") return;
    $targets = array_values(array_filter(explode(",", $targetsStr), fn($v) => $v !== ""));
    if($index >= count($targets)) {
        DecisionQueueController::ClearVariable("NegateByCardTargets");
        DecisionQueueController::ClearVariable("NegateByCardDestination");
        DecisionQueueController::ClearVariable("NegateByCardPay");
        DecisionQueueController::ClearVariable("NegateByCardPlayer");
        DecisionQueueController::ClearVariable("NegateByCardCurrentIndex");
        DecisionQueueController::ClearVariable("NegateByCardCurrentTarget");
        return;
    }

    $negatingPlayer = intval(DecisionQueueController::GetVariable("NegateByCardPlayer") ?? $player);
    $destinationMode = DecisionQueueController::GetVariable("NegateByCardDestination") ?? "default";
    $payAmount = intval(DecisionQueueController::GetVariable("NegateByCardPay") ?? "-1");
    $targetMZ = $targets[$index];
    $targetObj = GetZoneObject($targetMZ);
    if($targetObj === null || $targetObj->removed) {
        DecisionQueueController::AddDecision($negatingPlayer, "CUSTOM", "NegateByCardContinue|" . ($index + 1), 1);
        return;
    }

    if($payAmount <= 0 || CountAvailableReservePayments(intval($targetObj->Controller)) < $payAmount) {
        NegateCardActivation($negatingPlayer, $targetMZ, $destinationMode);
        DecisionQueueController::AddDecision($negatingPlayer, "CUSTOM", "NegateByCardContinue|" . ($index + 1), 1);
        return;
    }

    DecisionQueueController::StoreVariable("NegateByCardCurrentIndex", strval($index));
    DecisionQueueController::StoreVariable("NegateByCardCurrentTarget", $targetMZ);
    DecisionQueueController::AddDecision(intval($targetObj->Controller), "YESNO", "-", 1,
        "Pay_" . $payAmount . "_to_prevent_negate?");
    DecisionQueueController::AddDecision(intval($targetObj->Controller), "CUSTOM", "NegateByCardPayChoice|" . $negatingPlayer, 1);
};

$customDQHandlers["NegateByCardPayChoice"] = function($payingPlayer, $parts, $lastDecision) {
    $negatingPlayer = intval($parts[0] ?? $payingPlayer);
    $targetMZ = DecisionQueueController::GetVariable("NegateByCardCurrentTarget");
    $destinationMode = DecisionQueueController::GetVariable("NegateByCardDestination") ?? "default";
    $payAmount = intval(DecisionQueueController::GetVariable("NegateByCardPay") ?? "0");
    $currentIndex = intval(DecisionQueueController::GetVariable("NegateByCardCurrentIndex") ?? "0");

    if($lastDecision === "YES" && count(GetHand($payingPlayer)) >= $payAmount) {
        for($i = 0; $i < $payAmount; ++$i) {
            DecisionQueueController::AddDecision($payingPlayer, "CUSTOM", "ReserveCard", 100);
        }
    } else {
        NegateCardActivation($negatingPlayer, $targetMZ, $destinationMode);
    }
    DecisionQueueController::AddDecision($negatingPlayer, "CUSTOM", "NegateByCardContinue|" . ($currentIndex + 1), 1);
};

$customDQHandlers["NegateActivationPayChoice"] = function($payingPlayer, $parts, $lastDecision) {
    $negatingPlayer = intval($parts[0] ?? $payingPlayer);
    $targetMZ = DecisionQueueController::GetVariable("pendingNegateTarget");
    $destinationMode = DecisionQueueController::GetVariable("pendingNegateDestination") ?? "default";
    $payAmount = intval(DecisionQueueController::GetVariable("pendingNegatePayAmount") ?? "0");
    if($lastDecision === "YES" && count(GetHand($payingPlayer)) >= $payAmount) {
        for($i = 0; $i < $payAmount; ++$i) {
            DecisionQueueController::AddDecision($payingPlayer, "CUSTOM", "ReserveCard", 100);
        }
    } else {
        NegateCardActivation($negatingPlayer, $targetMZ, $destinationMode);
    }
    DecisionQueueController::ClearVariable("pendingNegateTarget");
    DecisionQueueController::ClearVariable("pendingNegateDestination");
    DecisionQueueController::ClearVariable("pendingNegatePayAmount");
};

$customDQHandlers["NegateActivationTetherChoice"] = function($player, $parts, $lastDecision) {
    if($lastDecision === "-" || $lastDecision === "" || $lastDecision === "PASS") return;
    $target = GetZoneObject($lastDecision);
    if($target === null || $target->removed) return;
    DecisionQueueController::StoreVariable("pendingNegateTarget", $lastDecision);
    $controller = intval($target->Controller);
    DecisionQueueController::AddDecision($controller, "YESNO", "-", 1, "Take_unpreventable_damage_to_prevent_negate?");
    DecisionQueueController::AddDecision($controller, "CUSTOM", "NegateActivationTetherResolve|" . $player, 1);
};

$customDQHandlers["NegateActivationTetherResolve"] = function($controller, $parts, $lastDecision) {
    $negatingPlayer = intval($parts[0] ?? $controller);
    $targetMZ = DecisionQueueController::GetVariable("pendingNegateTarget");
    if($lastDecision === "YES") {
        $champMZ = FindChampionMZ($controller);
        if($champMZ !== null) DealUnpreventableDamage($negatingPlayer, "215upufyoz", $champMZ, 1 + PlayerLevel($negatingPlayer));
    } else {
        NegateCardActivation($negatingPlayer, $targetMZ, "default");
    }
    DecisionQueueController::ClearVariable("pendingNegateTarget");
};

$customDQHandlers["NegateActivationDrawChoice"] = function($player, $parts, $lastDecision) {
    if($lastDecision === "-" || $lastDecision === "" || $lastDecision === "PASS") return;
    $target = GetZoneObject($lastDecision);
    if($target === null || $target->removed) return;
    DecisionQueueController::StoreVariable("pendingNegateTarget", $lastDecision);
    $controller = intval($target->Controller);
    DecisionQueueController::AddDecision($controller, "YESNO", "-", 1, "Let_opponent_draw_two_to_prevent_negate?");
    DecisionQueueController::AddDecision($controller, "CUSTOM", "NegateActivationDrawResolve|" . $player, 1);
};

$customDQHandlers["NegateActivationDrawResolve"] = function($controller, $parts, $lastDecision) {
    $negatingPlayer = intval($parts[0] ?? $controller);
    $targetMZ = DecisionQueueController::GetVariable("pendingNegateTarget");
    if($lastDecision === "YES") {
        Draw($negatingPlayer, 2);
    } else {
        NegateCardActivation($negatingPlayer, $targetMZ, "banish");
    }
    DecisionQueueController::ClearVariable("pendingNegateTarget");
};

$customDQHandlers["NegateActivationSuffocatingChoice"] = function($player, $parts, $lastDecision) {
    if($lastDecision === "-" || $lastDecision === "" || $lastDecision === "PASS") return;
    $target = GetZoneObject($lastDecision);
    if($target === null || $target->removed) return;
    DecisionQueueController::StoreVariable("pendingNegateTarget", $lastDecision);
    $controller = intval($target->Controller);
    $damage = 3 + PlayerLevel($player);
    DecisionQueueController::AddDecision($controller, "YESNO", "-", 1,
        "Have_Suffocating_Ash_deal_" . $damage . "_unpreventable_damage_to_your_champion_to_prevent_negate?");
    DecisionQueueController::AddDecision($controller, "CUSTOM", "NegateActivationSuffocatingResolve|" . $player . "|" . $damage, 1);
};

$customDQHandlers["NegateActivationSuffocatingResolve"] = function($controller, $parts, $lastDecision) {
    $negatingPlayer = intval($parts[0] ?? $controller);
    $damage = intval($parts[1] ?? "0");
    $targetMZ = DecisionQueueController::GetVariable("pendingNegateTarget");
    if($lastDecision === "YES") {
        $champMZ = FindChampionMZ($controller);
        if($champMZ !== null && $damage > 0) {
            DealUnpreventableDamage($negatingPlayer, "d6xkecLJ5S", $champMZ, $damage);
        }
    } else {
        NegateCardActivation($negatingPlayer, $targetMZ, "default");
    }
    DecisionQueueController::ClearVariable("pendingNegateTarget");
};

// Blossoming Denial (1nnpbddblx): optional negate unless pays (3), then summon 2 Flowerbud tokens + [L5+] Recover X phantasias
$customDQHandlers["BlossomingDenialNegate"] = function($player, $parts, $lastDecision) {
    if($lastDecision === "-" || $lastDecision === "" || $lastDecision === "PASS") return;
    $target = GetZoneObject($lastDecision);
    if($target === null || $target->removed) return;
    DecisionQueueController::StoreVariable("pendingNegateTarget", $lastDecision);
    DecisionQueueController::StoreVariable("pendingNegateDestination", "default");
    DecisionQueueController::StoreVariable("pendingNegatePayAmount", "3");
    $controller = intval($target->Controller);
    if(CountAvailableReservePayments($controller) < 3) {
        NegateCardActivation($player, $lastDecision, "default");
        return;
    }
    DecisionQueueController::AddDecision($controller, "YESNO", "-", 1, "Pay_3_to_prevent_negate?");
    DecisionQueueController::AddDecision($controller, "CUSTOM", "NegateActivationPayChoice|" . $player, 1);
};

$customDQHandlers["BlossomingDenialFinal"] = function($player, $parts, $lastDecision) {
    $opponent = ($player == 1) ? 2 : 1;
    // Each opponent summons two Flowerbud tokens
    MZAddZone($opponent, "myField", "yn78t73w1p");
    MZAddZone($opponent, "myField", "yn78t73w1p");
    // [Level 5+] Recover X where X = phantasias on field
    if(PlayerLevel($player) >= 5) {
        $phantasiaCount = count(ZoneSearch("myField", ["PHANTASIA"])) + count(ZoneSearch("theirField", ["PHANTASIA"]));
        if($phantasiaCount > 0) RecoverChampion($player, $phantasiaCount);
    }
};

// Imperial Accord (1S7Q5fqX5u): negate advanced element unless pays (6), may banish negated card
$customDQHandlers["ImperialAccordNegate"] = function($player, $parts, $lastDecision) {
    if($lastDecision === "-" || $lastDecision === "" || $lastDecision === "PASS") return;
    $target = GetZoneObject($lastDecision);
    if($target === null || $target->removed) return;
    $cardID = $target->CardID;
    DecisionQueueController::StoreVariable("imperialAccordCardID", $cardID);
    $controller = intval($target->Controller);
    if(CountAvailableReservePayments($controller) < 6) {
        NegateCardActivation($player, $lastDecision, "default");
        return;
    }
    DecisionQueueController::StoreVariable("pendingNegateTarget", $lastDecision);
    DecisionQueueController::StoreVariable("pendingNegateDestination", "default");
    DecisionQueueController::StoreVariable("pendingNegatePayAmount", "6");
    DecisionQueueController::AddDecision($controller, "YESNO", "-", 1, "Pay_6_to_prevent_negate?");
    DecisionQueueController::AddDecision($controller, "CUSTOM", "ImperialAccordPayChoice|" . $player, 1);
};

$customDQHandlers["ImperialAccordPayChoice"] = function($payingPlayer, $parts, $lastDecision) {
    $negatingPlayer = intval($parts[0] ?? $payingPlayer);
    $targetMZ = DecisionQueueController::GetVariable("pendingNegateTarget");
    $payAmount = intval(DecisionQueueController::GetVariable("pendingNegatePayAmount") ?? "0");
    if($lastDecision === "YES" && count(GetHand($payingPlayer)) >= $payAmount) {
        // Controller paid — negate doesn't happen; clear stored card ID
        DecisionQueueController::ClearVariable("imperialAccordCardID");
        for($i = 0; $i < $payAmount; ++$i) {
            DecisionQueueController::AddDecision($payingPlayer, "CUSTOM", "ReserveCard", 100);
        }
    } else {
        // Controller didn't pay — negate fires
        NegateCardActivation($negatingPlayer, $targetMZ, "default");
    }
    DecisionQueueController::ClearVariable("pendingNegateTarget");
    DecisionQueueController::ClearVariable("pendingNegateDestination");
    DecisionQueueController::ClearVariable("pendingNegatePayAmount");
};

$customDQHandlers["ImperialAccordMayBanish"] = function($player, $parts, $lastDecision) {
    $cardID = DecisionQueueController::GetVariable("imperialAccordCardID");
    DecisionQueueController::ClearVariable("imperialAccordCardID");
    if(empty($cardID)) return;
    // Find card in opponent's graveyard
    $gy = GetZone("theirGraveyard");
    for($i = count($gy) - 1; $i >= 0; --$i) {
        if(!$gy[$i]->removed && $gy[$i]->CardID === $cardID) {
            DecisionQueueController::AddDecision($player, "MZMAYCHOOSE", "theirGraveyard-" . $i, 1, tooltip:"Banish_negated_card?");
            DecisionQueueController::AddDecision($player, "CUSTOM", "ImperialAccordBanish", 1);
            return;
        }
    }
};

$customDQHandlers["ImperialAccordBanish"] = function($player, $parts, $lastDecision) {
    if($lastDecision === "-" || $lastDecision === "" || $lastDecision === "PASS") return;
    $obj = GetZoneObject($lastDecision);
    if($obj === null || $obj->removed) return;
    MZMove(intval($obj->Controller), $lastDecision, "myBanish");
    DecisionQueueController::CleanupRemovedCards();
};

function QueueConstellatorySpireTrigger($player) {
    $field = GetField($player);
    for($i = 0; $i < count($field); ++$i) {
        if($field[$i]->removed || $field[$i]->CardID !== "yd609g44vm" || HasNoAbilities($field[$i]) || $field[$i]->Status != 2) continue;
        DecisionQueueController::AddDecision($player, "YESNO", "-", 1, "Rest_The_Constellatory_Spire_to_deal_2_damage?");
        DecisionQueueController::AddDecision($player, "CUSTOM", "ConstellatorySpireRest|" . $i, 1);
    }
}

$customDQHandlers["ConstellatorySpireRest"] = function($player, $parts, $lastDecision) {
    if($lastDecision !== "YES") return;
    $idx = intval($parts[0] ?? -1);
    $mzID = "myField-" . $idx;
    $spire = GetZoneObject($mzID);
    if($spire === null || $spire->removed || $spire->CardID !== "yd609g44vm" || $spire->Status != 2) return;
    ExhaustCard($player, $mzID);
    $targets = array_merge(ZoneSearch("myField", ["ALLY", "CHAMPION", "PHANTASIA"]), ZoneSearch("theirField", ["ALLY", "CHAMPION", "PHANTASIA"]));
    if(empty($targets)) return;
    DecisionQueueController::AddDecision($player, "MZCHOOSE", implode("&", $targets), 1, "Choose_unit_for_2_damage");
    DecisionQueueController::AddDecision($player, "CUSTOM", "ConstellatorySpireDamage", 1);
};

$customDQHandlers["ConstellatorySpireDamage"] = function($player, $parts, $lastDecision) {
    if($lastDecision === "-" || $lastDecision === "" || $lastDecision === "PASS") return;
    DealDamage($player, "yd609g44vm", $lastDecision, 2);
};

// =============================================================================
// Opportunity / Priority System
// =============================================================================

/**
 * Get the list of fast-speed cards a player can play from their hand.
 * Returns an array of mzID strings from the player's own perspective (e.g. "myHand-0").
 *
 * @param int $player The player to check.
 * @return array Array of mzID strings for fast-speed hand cards.
 */
// Cards with [Class Bonus] Fast Activation — cardID => required class(es)
$cbFastActivationCards = [
    "a5igwbsmks" => ["TAMER"], // Spirited Falconer
    "itwys9kf4r" => ["ASSASSIN"], // Cloaked Executioner
    "cqadnk9iz0" => ["TAMER"], // Baby Green Slime
    "2bbmoqk2c7" => ["GUARDIAN"], // Rose, Eternal Paragon
    "f0ht2tsn0y" => ["GUARDIAN"], // Astarte, Celestial Dawn
    "jozihslnhz" => ["ASSASSIN"], // Sinister Mindreaver
    "yky280mtts" => ["TAMER"], // Flamebreak Chorus
    "uPn9SZdqrr" => ["GUARDIAN"], // Gustguard Bastion
];

// Cards with unconditional Fast Activation (no class bonus required)
$unconditionalFastCards = [
    "aljx2ru1w3" => true, // Flashfire Horse
];

function EncodeOpportunityAbilityChoice($mzID, $abilityIndex, $label = "") {
    $cleanLabel = trim(str_replace(["@", "&", ":"], ["", "_", "-"], $label));
    return $mzID . "@Activate-" . intval($abilityIndex) . ($cleanLabel !== "" ? "@" . $cleanLabel : "");
}

function DecodeOpportunityAbilityChoice($selection) {
    if(!is_string($selection) || $selection === "") return null;
    $parts = explode("@", $selection);
    if(count($parts) < 2) return null;
    $mzID = $parts[0];
    $payload = $parts[1] ?? "";
    if(strpos($payload, "Activate-") !== 0) return null;
    return [
        'mzID' => $mzID,
        'abilityIndex' => intval(substr($payload, strlen("Activate-")))
    ];
}

function GetPlayableFastAbilities($player) {
    global $playerID;
    global $Cardistry_Cards;
    $savedPlayerID = $playerID;
    $playerID = $player;

    $field = &GetField($player);
    $choices = [];
    $existingFlash = function_exists("GetFlashMessage") ? GetFlashMessage() : "";

    foreach($field as $i => $obj) {
        if($obj == null) continue;
        if($obj->removed || HasNoAbilities($obj)) continue;
        if($obj->CardID === "uvgflagxbb" && HasOpportunity($player)) continue;
        if($obj->CardID === "wCAIuvPOAT" && CountPreservedCardsInMaterial($player) < 5) continue;
        if(GetCounterCount($obj, "frenzy") > 0) continue;
        if(isset($Cardistry_Cards[$obj->CardID]) && isset($obj->Counters['cardistry_used'])) continue;

        $mzID = "myField-" . $i;
        $staticAbilityCount = CardActivateAbilityCount($obj->CardID);
        $staticAbilityNames = function_exists("CardActivateAbilityCountNames")
            ? CardActivateAbilityCountNames($obj->CardID)
            : [];

        for($abilityIndex = 0; $abilityIndex < $staticAbilityCount; ++$abilityIndex) {
            $canActivate = function_exists("CanActivateAbility")
                ? CanActivateAbility($player, $mzID, $abilityIndex)
                : true;
            if(function_exists("SetFlashMessage")) SetFlashMessage($existingFlash);
            if(!$canActivate) continue;

            $label = $staticAbilityNames[$abilityIndex] ?? ("Ability_" . ($abilityIndex + 1));
            $choices[] = EncodeOpportunityAbilityChoice($mzID, $abilityIndex, $label);
        }

        $dynamicAbilitiesRaw = GetDynamicAbilities($obj);
        if($dynamicAbilitiesRaw === "" || $dynamicAbilitiesRaw === "[]") continue;

        $dynamicAbilities = json_decode($dynamicAbilitiesRaw, true);
        if(!is_array($dynamicAbilities)) continue;

        foreach($dynamicAbilities as $dynamicAbility) {
            $abilityIndex = intval($dynamicAbility['index'] ?? -1);
            if($abilityIndex < 0) continue;

            $canActivate = function_exists("CanActivateAbility")
                ? CanActivateAbility($player, $mzID, $abilityIndex)
                : true;
            if(function_exists("SetFlashMessage")) SetFlashMessage($existingFlash);
            if(!$canActivate) continue;

            $label = str_replace(" ", "_", $dynamicAbility['name'] ?? ("Ability_" . ($abilityIndex + 1)));
            $choices[] = EncodeOpportunityAbilityChoice($mzID, $abilityIndex, $label);
        }
    }

    if(function_exists("SetFlashMessage")) SetFlashMessage($existingFlash);
    $playerID = $savedPlayerID;
    return $choices;
}

function GetPlayableOpportunityChoices($player) {
    $choices = GetPlayableFastCards($player);
    $abilityChoices = GetPlayableFastAbilities($player);
    if(!empty($abilityChoices)) $choices = array_merge($choices, $abilityChoices);
    return $choices;
}

function ResolveOpportunitySelection($player, $selection) {
    $abilityChoice = DecodeOpportunityAbilityChoice($selection);
    if($abilityChoice !== null) {
        ActivateAbility($player, $abilityChoice['mzID'], $abilityChoice['abilityIndex']);
        return true;
    }

    if(TryLostPromisesMemory($player, $selection) || TryGlimmerCast($player, $selection)) {
        return true;
    }

    ActivateCard($player, $selection, false);
    return true;
}

function GetPlayableFastCards($player) {
    global $cbFastActivationCards, $unconditionalFastCards;
    $hand = &GetHand($player);
    $fastCards = [];
    for($i = 0; $i < count($hand); $i++) {
        $obj = $hand[$i];
        if(isset($obj->removed) && $obj->removed) continue;
        $speed = CardSpeed($obj->CardID);
        if($speed === true) {
            $fastCards[] = "myHand-" . $i;
        } elseif(isset($cbFastActivationCards[$obj->CardID]) && IsClassBonusActive($player, $cbFastActivationCards[$obj->CardID])) {
            $fastCards[] = "myHand-" . $i;
        } elseif(isset($unconditionalFastCards[$obj->CardID])) {
            $fastCards[] = "myHand-" . $i;
        } elseif($obj->CardID === "zeig1e49wb" && GetShiftingCurrents($player) === "NORTH") {
            // Solar Pinnacle: Fast Activation while SC faces North
            $fastCards[] = "myHand-" . $i;
        } elseif($obj->CardID === "yrzexkW5Ej" && GetCurrentPhase() === "RECOLLECTION" && $player != GetTurnPlayer()) {
            // Sink the Mind: may be activated during an opponent's recollection phase
            $fastCards[] = "myHand-" . $i;
        } elseif($obj->CardID === "0oyxjld8jh") {
            // Guan Yu, Prime Exemplar: Fast Activation if a Human ally you controlled died this turn
            $deadAllies = AllyDestroyedTurnCards($player);
            foreach($deadAllies as $deadCardID => $deadCount) {
                if(PropertyContains(CardSubtypes($deadCardID), "HUMAN")) {
                    $fastCards[] = "myHand-" . $i;
                    break;
                }
            }
        } elseif(GlobalEffectCount($player, "w1wgpeifd0") > 0 && PropertyContains(CardType($obj->CardID), "ALLY")) {
            // Expeditious Opening: next ally activated this turn gets fast activation
            $fastCards[] = "myHand-" . $i;
        } elseif(GlobalEffectCount($player, "t4owmcva0f") > 0
            && PropertyContains(CardType($obj->CardID), "ACTION")
            && PropertyContains(CardClasses($obj->CardID), "RANGER")) {
            // Bombastic Sprint: next Ranger action activated this turn gets fast activation
            $fastCards[] = "myHand-" . $i;
        } elseif($obj->CardID === "B1EbF6jcYF" && IsAliceBonusActive($player)) {
            // Golden Gambit: [Alice Bonus] Fast Activation
            $fastCards[] = "myHand-" . $i;
        } elseif($obj->CardID === "pvzvqx16w4" && GetChampionDamageTakenThisTurn($player) >= 35) {
            // Annihilation: [Damage 35+] Fast Activation
            $fastCards[] = "myHand-" . $i;
        }
    }

    // Diao Chan inherited ability: include eligible Reaction Spells from memory
    // Only when it's the opponent's turn, champion is awake, and has glimmer
    $turnPlayer = GetTurnPlayer();
    if($player != $turnPlayer && ChampionHasInLineage($player, "00xbh8oc00")) {
        $champObj = GetPlayerChampion($player);
        if($champObj !== null && $champObj->Status == 2 && !HasNoAbilities($champObj)) {
            $glimmerCount = GetCounterCount($champObj, "glimmer");
            if($glimmerCount > 0) {
                $memory = &GetMemory($player);
                for($mi = 0; $mi < count($memory); ++$mi) {
                    if(isset($memory[$mi]->removed) && $memory[$mi]->removed) continue;
                    $memCardID = $memory[$mi]->CardID;
                    if(PropertyContains(CardSubtypes($memCardID), "REACTION")
                        && PropertyContains(CardSubtypes($memCardID), "SPELL")) {
                        $spellCost = intval(CardCost_reserve($memCardID));
                        if($spellCost <= $glimmerCount) {
                            $fastCards[] = "myMemory-" . $mi;
                        }
                    }
                }
            }
        }
    }

    // Lost Promises (gN8uFKSip0): [Alice Bonus] while champion is defending,
    // banish 6 from GY → activate from memory without paying reserve cost
    if($player != $turnPlayer && IsAliceBonusActive($player) && IsChampionBeingAttacked($player)) {
        $gy = GetZone("myGraveyard");
        $gyCount = 0;
        foreach($gy as $gyObj) { if(!$gyObj->removed) $gyCount++; }
        if($gyCount >= 6) {
            $memory = &GetMemory($player);
            for($mi = 0; $mi < count($memory); ++$mi) {
                if(isset($memory[$mi]->removed) && $memory[$mi]->removed) continue;
                if($memory[$mi]->CardID === "gN8uFKSip0") {
                    $fastCards[] = "myMemory-" . $mi;
                }
            }
        }
    }

    return $fastCards;
}

// --- EffectStack Opportunity ---------------------------------------------------
// After a card enters the EffectStack, the player who activated it gets priority
// first (they can chain more fast cards), then the opponent. Both must pass for
// the topmost card to resolve.

/**
 * Try to activate Lost Promises (gN8uFKSip0) from memory via the banish-6 alternate cost.
 * Returns true if the activation was initiated (caller should not call ActivateCard after this).
 * @param int    $player The player trying to use Lost Promises
 * @param string $mzID   The selected card's mzID (expected "myMemory-N")
 */
function TryLostPromisesMemory($player, $mzID) {
    if(strpos($mzID, "myMemory-") !== 0) return false;
    $obj = GetZoneObject($mzID);
    if($obj === null || $obj->removed || $obj->CardID !== "gN8uFKSip0") return false;
    if(!IsAliceBonusActive($player)) return false;
    if(!IsChampionBeingAttacked($player)) return false;
    $gy = GetZone("myGraveyard");
    $gyCount = 0;
    foreach($gy as $gyObj) { if(!$gyObj->removed) $gyCount++; }
    if($gyCount < 6) return false;
    LostPromisesBanishLoop($player, 6, $mzID);
    return true;
}

/**
 * DQ handler: After a card is placed on the EffectStack and costs are paid,
 * grant Opportunity. Per rules, the player who activated receives priority first.
 *
 * $player = the player who just placed a card on the EffectStack.
 */
$customDQHandlers["EffectStackOpportunity"] = function($player, $parts, $lastDecision) {
    ReconcileEffectStackSourceZones();
    DecisionQueueController::CleanupRemovedCards();
    $effectStack = GetLiveEffectStackEntries();
    if(empty($effectStack)) return;

    $otherPlayer = ($player == 1) ? 2 : 1;

    // Active player gets priority first (per rules: they can chain)
    $fastCards = GetPlayableOpportunityChoices($player);
    if(!empty($fastCards)) {
        $cardList = implode("&", $fastCards);
        DecisionQueueController::AddDecision($player, "MZMAYCHOOSE", $cardList, 100, "Take_a_fast_action?");
        DecisionQueueController::AddDecision($player, "CUSTOM", "EffectStackActiveResponse|$otherPlayer", 100, "", 1);
    } else {
        // Active player can't respond, check opponent
        $fastCards2 = GetPlayableOpportunityChoices($otherPlayer);
        if(!empty($fastCards2)) {
            $cardList = implode("&", $fastCards2);
            DecisionQueueController::AddDecision($otherPlayer, "MZMAYCHOOSE", $cardList, 100, "Take_a_fast_action?");
            DecisionQueueController::AddDecision($otherPlayer, "CUSTOM", "EffectStackOpponentResponse", 100, "", 1);
        } else {
            // Neither can respond, auto-resolve
            ResolveTopOfEffectStack();
        }
    }
};

/**
 * DQ handler: active player responded to EffectStack Opportunity.
 * $parts[0] = the other player's ID.
 */
$customDQHandlers["EffectStackActiveResponse"] = function($player, $parts, $lastDecision) {
    $otherPlayer = intval($parts[0]);
    if($lastDecision === "-" || $lastDecision === "" || $lastDecision === "PASS") {
        // Active player passed, check opponent
        $fastCards = GetPlayableOpportunityChoices($otherPlayer);
        if(!empty($fastCards)) {
            $cardList = implode("&", $fastCards);
            DecisionQueueController::AddDecision($otherPlayer, "MZMAYCHOOSE", $cardList, 100, "Take_a_fast_action?");
            DecisionQueueController::AddDecision($otherPlayer, "CUSTOM", "EffectStackOpponentResponse", 100, "", 1);
        } else {
            // Both passed (opponent has no cards), resolve
            ResolveTopOfEffectStack();
        }
    } else {
        // Active player took a fast action — they keep priority
        ResolveOpportunitySelection($player, $lastDecision);
    }
};

/**
 * DQ handler: opponent responded to EffectStack Opportunity.
 */
$customDQHandlers["EffectStackOpponentResponse"] = function($player, $parts, $lastDecision) {
    if($lastDecision === "-" || $lastDecision === "" || $lastDecision === "PASS") {
        // Both players passed, resolve top of stack
        ResolveTopOfEffectStack();
    } else {
        // Opponent took a fast action — they get priority
        ResolveOpportunitySelection($player, $lastDecision);
    }
};

/**
 * DQ handler: After a card resolves from the EffectStack and all its abilities
 * finish, check whether there are more cards on the stack to resolve.
 * If stack is non-empty, grant Opportunity (turn player gets priority first after resolution).
 * If stack is empty, check for a pending Opportunity window (combat/ability) and re-grant it.
 *
 * Uses high block (200) so it runs after any ability decisions (block 1-100).
 */
$customDQHandlers["PostResolutionCheck"] = function($player, $parts, $lastDecision) {
    DecisionQueueController::StoreVariable("isImbued", "NO");
    ReconcileEffectStackSourceZones();
    DecisionQueueController::CleanupRemovedCards();
    $effectStack = GetLiveEffectStackEntries();

    if(!empty($effectStack)) {
        // More cards to resolve — turn player gets priority first (per rules)
        $turnPlayer = GetTurnPlayer();
        $otherPlayer = ($turnPlayer == 1) ? 2 : 1;

        $fastCards = GetPlayableOpportunityChoices($turnPlayer);
        if(!empty($fastCards)) {
            $cardList = implode("&", $fastCards);
            DecisionQueueController::AddDecision($turnPlayer, "MZMAYCHOOSE", $cardList, 100, "Take_a_fast_action?");
            DecisionQueueController::AddDecision($turnPlayer, "CUSTOM", "EffectStackActiveResponse|$otherPlayer", 100, "", 1);
        } else {
            $fastCards2 = GetPlayableOpportunityChoices($otherPlayer);
            if(!empty($fastCards2)) {
                $cardList = implode("&", $fastCards2);
                DecisionQueueController::AddDecision($otherPlayer, "MZMAYCHOOSE", $cardList, 100, "Take_a_fast_action?");
                DecisionQueueController::AddDecision($otherPlayer, "CUSTOM", "EffectStackOpponentResponse", 100, "", 1);
            } else {
                ResolveTopOfEffectStack();
            }
        }
    } else {
        $consumedOuterWindow = DecisionQueueController::GetVariable("ConsumedOuterOpportunityWindow");
        if($consumedOuterWindow === "YES") {
            $pendingHandler = DecisionQueueController::GetVariable("PendingOpportunityHandler");
            DecisionQueueController::ClearVariable("ConsumedOuterOpportunityWindow");
            if(IsCombatOpportunityContinuation($pendingHandler)) {
                $firstPlayer = intval(DecisionQueueController::GetVariable("PendingOpportunityFirstPlayer") ?? GetTurnPlayer());
                $nextPlayer = intval(DecisionQueueController::GetVariable("PendingOpportunityNextPlayer") ?? GetTurnPlayer());
                GrantOpportunityWindow($firstPlayer, $pendingHandler, $nextPlayer);
                return;
            }
            ClearOpportunityVariables();
            return;
        }

        // Stack is empty — check for a pending Opportunity window (combat/ability)
        $pendingHandler = DecisionQueueController::GetVariable("PendingOpportunityHandler");
        if($pendingHandler !== null && $pendingHandler !== "") {
            $firstPlayer = intval(DecisionQueueController::GetVariable("PendingOpportunityFirstPlayer") ?? GetTurnPlayer());
            $nextPlayer = intval(DecisionQueueController::GetVariable("PendingOpportunityNextPlayer") ?? GetTurnPlayer());
            // Re-grant the Opportunity window (re-checks fast cards for both players)
            GrantOpportunityWindow($firstPlayer, $pendingHandler, $nextPlayer);
        }
    }
};

/**
 * Resolve the top card of the EffectStack.
 *
 * Swaps $playerID to match the card owner so that all my/their zone references
 * resolve correctly, then calls the generated CardActivated() wrapper (which
 * stores mzID, tracks MacroTurnIndex, calls OnCardActivated, and processes
 * ability decisions). After resolution, queues PostResolutionCheck to handle
 * remaining EffectStack entries.
 */
function ResolveTopOfEffectStack() {
    ReconcileEffectStackSourceZones();
    DecisionQueueController::CleanupRemovedCards();
    $effectStack = GetLiveEffectStackEntries();
    if(empty($effectStack)) return;

    $topIndex = count($effectStack) - 1;
    $topObj = $effectStack[$topIndex];
    if($topObj == null) {
        $topIndex = $topIndex - 1;
        if($topIndex < 0) return;
        $topObj = $effectStack[$topIndex];
        if($topObj == null) return;
    }
    $cardOwner = $topObj->Controller;
    $topMZ = "EffectStack-" . $topIndex;

    // Swap $playerID to the card owner for correct my/their resolution
    global $playerID;
    $savedPlayerID = $playerID;
    $playerID = $cardOwner;

    // Call the generated CardActivated() wrapper, which:
    //  - Stores mzID variable for ability code
    //  - Tracks MacroTurnIndex
    //  - Calls OnCardActivated (moves card, fires abilities)
    //  - Calls ExecuteStaticMethods to process any ability decisions
    CardActivated($cardOwner, $topMZ);
    ReconcileEffectStackSourceZones();

    // Queue PostResolutionCheck to run after all ability interactions (block 200)
    DecisionQueueController::AddDecision($cardOwner, "CUSTOM", "PostResolutionCheck", 200);

    // Process PostResolutionCheck now if no interactive decisions are pending
    $dqController = new DecisionQueueController();
    $dqController->ExecuteStaticMethods($cardOwner, "-");

    // Restore $playerID
    $playerID = $savedPlayerID;
}

// --- General Opportunity Window ------------------------------------------------
// Used for combat and ability Opportunity windows. Stores a pending handler
// in DQ variables so that after any EffectStack detour (fast card played during
// the window), PostResolutionCheck can re-grant the window.

/**
 * Grant a full 2-player priority Opportunity window.
 * $firstPlayer gets priority first. After both pass, $nextHandler runs for $nextPlayer.
 * If either player plays a fast card, the EffectStack handles it, and after it
 * empties, PostResolutionCheck re-grants this window via the stored variables.
 *
 * @param int    $firstPlayer Player who gets priority first.
 * @param string $nextHandler CUSTOM DQ handler name to queue after both pass.
 * @param int    $nextPlayer  Player for whom to queue $nextHandler (default = $firstPlayer).
 */
function GrantOpportunityWindow($firstPlayer, $nextHandler, $nextPlayer = null) {
    if($nextPlayer === null) $nextPlayer = $firstPlayer;
    $secondPlayer = ($firstPlayer == 1) ? 2 : 1;

    // Store pending state so PostResolutionCheck can re-grant after EffectStack detour
    DecisionQueueController::StoreVariable("PendingOpportunityHandler", $nextHandler);
    DecisionQueueController::StoreVariable("PendingOpportunityNextPlayer", strval($nextPlayer));
    DecisionQueueController::StoreVariable("PendingOpportunityFirstPlayer", strval($firstPlayer));

    // Check first player's fast cards
    $fastCards1 = GetPlayableOpportunityChoices($firstPlayer);
    if(!empty($fastCards1)) {
        $cardList = implode("&", $fastCards1);
        DecisionQueueController::AddDecision($firstPlayer, "MZMAYCHOOSE", $cardList, 100, "Take_a_fast_action?");
        DecisionQueueController::AddDecision($firstPlayer, "CUSTOM", "OpportunityWindowFirstResponse", 100, "", 1);
    } else {
        // First player can't act, try second
        $fastCards2 = GetPlayableOpportunityChoices($secondPlayer);
        if(!empty($fastCards2)) {
            $cardList = implode("&", $fastCards2);
            DecisionQueueController::AddDecision($secondPlayer, "MZMAYCHOOSE", $cardList, 100, "Take_a_fast_action?");
            DecisionQueueController::AddDecision($secondPlayer, "CUSTOM", "OpportunityWindowSecondResponse", 100, "", 1);
        } else {
            // Neither can act, resolve immediately
            ResolveOpportunityWindow();
        }
    }
}

/**
 * Both players passed the Opportunity window. Clear pending state and queue the next handler.
 */
function ResolveOpportunityWindow() {
    $nextHandler = DecisionQueueController::GetVariable("PendingOpportunityHandler");
    $nextPlayer = intval(DecisionQueueController::GetVariable("PendingOpportunityNextPlayer") ?? "1");
    ClearOpportunityVariables();

    if($nextHandler === null || $nextHandler === "" || $nextHandler === "NoOp") return;

    global $playerID;
    $savedPlayerID = $playerID;
    $playerID = $nextPlayer;

    DecisionQueueController::AddDecision($nextPlayer, "CUSTOM", $nextHandler, 100);
    $dqController = new DecisionQueueController();
    $dqController->ExecuteStaticMethods($nextPlayer, "-");

    $playerID = $savedPlayerID;
}

function ClearOpportunityVariables() {
    DecisionQueueController::ClearVariable("PendingOpportunityHandler");
    DecisionQueueController::ClearVariable("PendingOpportunityNextPlayer");
    DecisionQueueController::ClearVariable("PendingOpportunityFirstPlayer");
}

function IsCombatOpportunityContinuation($handler) {
    return in_array($handler, ["CombatDealDamage", "CleaveDealDamage"], true);
}

/**
 * First player in an Opportunity window responded.
 */
$customDQHandlers["OpportunityWindowFirstResponse"] = function($player, $parts, $lastDecision) {
    if($lastDecision === "-" || $lastDecision === "" || $lastDecision === "PASS") {
        // First player passed, check second player
        $secondPlayer = ($player == 1) ? 2 : 1;
        $fastCards = GetPlayableOpportunityChoices($secondPlayer);
        if(!empty($fastCards)) {
            $cardList = implode("&", $fastCards);
            DecisionQueueController::AddDecision($secondPlayer, "MZMAYCHOOSE", $cardList, 100, "Take_a_fast_action?");
            DecisionQueueController::AddDecision($secondPlayer, "CUSTOM", "OpportunityWindowSecondResponse", 100, "", 1);
        } else {
            // Both passed (second has no cards), resolve
            ResolveOpportunityWindow();
        }
    } else {
        // Taking a fast action consumes this outer window. The action's own
        // effect-stack / ability-response flow will provide any follow-up priority.
        DecisionQueueController::StoreVariable("ConsumedOuterOpportunityWindow", "YES");
        $pendingHandler = DecisionQueueController::GetVariable("PendingOpportunityHandler");
        if(!IsCombatOpportunityContinuation($pendingHandler)) {
            ClearOpportunityVariables();
        }
        ResolveOpportunitySelection($player, $lastDecision);
    }
};

/**
 * Second player in an Opportunity window responded.
 */
$customDQHandlers["OpportunityWindowSecondResponse"] = function($player, $parts, $lastDecision) {
    if($lastDecision === "-" || $lastDecision === "" || $lastDecision === "PASS") {
        // Both players passed, resolve
        ResolveOpportunityWindow();
    } else {
        // Taking a fast action consumes this outer window. The action's own
        // effect-stack / ability-response flow will provide any follow-up priority.
        DecisionQueueController::StoreVariable("ConsumedOuterOpportunityWindow", "YES");
        $pendingHandler = DecisionQueueController::GetVariable("PendingOpportunityHandler");
        if(!IsCombatOpportunityContinuation($pendingHandler)) {
            ClearOpportunityVariables();
        }
        ResolveOpportunitySelection($player, $lastDecision);
    }
};

$customDQHandlers["AbilityOpportunityResume"] = function($player, $parts, $lastDecision) {
    $resumeHandler = DecisionQueueController::GetVariable("AbilityResumePendingHandler");
    $resumeNextPlayer = intval(DecisionQueueController::GetVariable("AbilityResumePendingNextPlayer") ?? "0");
    $resumeFirstPlayer = intval(DecisionQueueController::GetVariable("AbilityResumePendingFirstPlayer") ?? "0");

    DecisionQueueController::ClearVariable("AbilityResumePendingHandler");
    DecisionQueueController::ClearVariable("AbilityResumePendingNextPlayer");
    DecisionQueueController::ClearVariable("AbilityResumePendingFirstPlayer");

    if($resumeHandler === null || $resumeHandler === "" || $resumeFirstPlayer <= 0) return;
    GrantOpportunityWindow($resumeFirstPlayer, $resumeHandler, $resumeNextPlayer > 0 ? $resumeNextPlayer : $resumeFirstPlayer);
};

/**
 * No-op handler for Opportunity windows that don't have a next step
 * (e.g., ability Opportunity — after both pass, game simply continues).
 */
$customDQHandlers["NoOp"] = function($player, $parts, $lastDecision) {
    // Intentionally empty
};

/**
 * DQ handler: After an activated ability resolves, grant Opportunity.
 * Per rules: the player who activated the ability receives priority first.
 * After both pass, game simply continues (NoOp).
 */
$customDQHandlers["AbilityOpportunity"] = function($player, $parts, $lastDecision) {
    ReconcileEffectStackSourceZones();
    DecisionQueueController::CleanupRemovedCards();
    $effectStack = GetLiveEffectStackEntries();
    if(!empty($effectStack)) {
        // An activated ability can resolve while another effect is still on the
        // stack. In that case resume the normal stack-priority flow.
        DecisionQueueController::AddDecision($player, "CUSTOM", "EffectStackOpportunity", 100);
        return;
    }

    $consumedOuterWindow = DecisionQueueController::GetVariable("ConsumedOuterOpportunityWindow");
    if($consumedOuterWindow === "YES") {
        // A fast action chosen from an outer normal opportunity window should
        // use only its own stack-response flow. Suppress any intermediate
        // ability-granted window until PostResolutionCheck clears the flag.
        return;
    }

    $pendingHandler = DecisionQueueController::GetVariable("PendingOpportunityHandler");
    if($pendingHandler !== null && $pendingHandler !== "") {
        DecisionQueueController::StoreVariable("AbilityResumePendingHandler", $pendingHandler);
        DecisionQueueController::StoreVariable(
            "AbilityResumePendingNextPlayer",
            strval(intval(DecisionQueueController::GetVariable("PendingOpportunityNextPlayer") ?? $player))
        );
        DecisionQueueController::StoreVariable(
            "AbilityResumePendingFirstPlayer",
            strval(intval(DecisionQueueController::GetVariable("PendingOpportunityFirstPlayer") ?? $player))
        );
        ClearOpportunityVariables();
        GrantOpportunityWindow($player, "AbilityOpportunityResume", $player);
        return;
    }

    GrantOpportunityWindow($player, "NoOp", $player);
};

?>

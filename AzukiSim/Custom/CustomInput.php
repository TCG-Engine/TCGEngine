<?php

function CustomWidgetInput($playerID, $actionCard, $action) {
    $cardArr = explode("-", $actionCard);
    $zone = $cardArr[0];
    $index = isset($cardArr[1]) ? $cardArr[1] : "";

    switch ($zone) {
        case "myLeaderHealth":
        case "myIKZToken":
        case "myLeaderHealthSlot":
        case "myIKZTokenSlot":
            if (strcasecmp($action, "Pass") === 0) {
                // Pass button — end turn
                HandlePassButton($playerID);
            }
            break;

        case "myGarden":
        case "myAlley":
        case "myLeader":
            // Entity/Leader ability activation
            if (strpos($action, ':') !== false) {
                $actionParts = explode(':', $action);
                $abilityIndex = intval($actionParts[1] ?? 0);
                SaveUndoVersion($playerID);
                ActivateAbility($playerID, $actionCard, $abilityIndex);
            } else if ($action === "Attack") {
                // Attack action — will prompt for target
                HandleAttackSetup($playerID, $actionCard);
            }
            break;

        case "myGateSlot":
            // Gate activation — portal from Alley to Garden
            if ($action === "UseGate") {
                HandleGateUsage($playerID);
            }
            break;

        default:
            break;
    }
}

function HandlePassButton($playerID) {
    // Check if we can pass (no pending decisions or effects)
    $effectStack = &GetEffectStack();
    DecisionQueueController::CleanupRemovedCards();
    $effectStack = &GetEffectStack();

    if(!empty($effectStack)) {
        SetFlashMessage("Cannot pass while effects are pending resolution.");
        return;
    }

    $dqController = new DecisionQueueController();
    if(!$dqController->AllQueuesEmpty()) {
        SetFlashMessage("Cannot pass while decisions are pending.");
        return;
    }

    // Advance to next phase
    global $gCurrentPhase;
    $gCurrentPhase = "MAIN"; // Passing in Main stays in Main or goes to EOT
    AdvanceAndExecute("PASS");
    AutoAdvanceAndExecute();
}

function HandleAttackSetup($playerID, $attackerMZ) {
    // Validate attacker
    if(!CanAttackWith($playerID, $attackerMZ)) {
        SetFlashMessage("Cannot attack with this card.");
        return;
    }

    // Get valid targets: opponent's Leader and tapped entities in their Garden
    $opponent = ($playerID === 1) ? 2 : 1;
    $targets = [];

    // Add opponent's leader as target if still alive.
    if(intval(GetLeaderHealth($opponent)) > 0) {
        $targets[] = "theirLeader-0";
    }

    // Add tapped entities from opponent's garden
    $garden = &GetGarden($opponent);
    for($i = 0; $i < count($garden); ++$i) {
        if(!$garden[$i]->removed && $garden[$i]->Status == 1) {
            // Tapped entity — can be targeted
            $targets[] = "theirGarden-" . $i;
        }
    }

    if(empty($targets)) {
        // No valid targets — auto-attack leader
        ExecuteAttack($playerID, $attackerMZ, "theirLeader-0");
        return;
    }

    // Queue target selection
    $targetStr = implode("&", $targets);
    DecisionQueueController::AddDecision($playerID, "MZCHOOSE", $targetStr, 1, "Select_attack_target");
    DecisionQueueController::AddDecision($playerID, "CUSTOM", "RESOLVE_ATTACK|" . $attackerMZ, 1);
}

function ExecuteAttack($player, $attackerMZ, $targetMZ) {
    $attackerParts = explode("-", $attackerMZ);
    $attackerZone = $attackerParts[0];
    $attackerIndex = intval($attackerParts[1] ?? -1);

    $targetParts = explode("-", $targetMZ);
    $targetZone = $targetParts[0];
    $targetIndex = intval($targetParts[1] ?? -1);

    // Get attacker stats
    if($attackerZone === "myGarden") {
        $field = &GetGarden($player);
    } else if($attackerZone === "myLeader") {
        // Leader objects live in Garden for AzukiSim.
        $field = &GetGarden($player);
    } else {
        return;
    }

    if($attackerIndex < 0 || $attackerIndex >= count($field) || $field[$attackerIndex]->removed) return;
    $attacker = &$field[$attackerIndex];
    $attackerAttack = intval(CardAttack($attacker->CardID) ?? 0);

    // Get target stats
    $opponent = ($player === 1) ? 2 : 1;
    if($targetZone === "theirGarden") {
        $targetField = &GetGarden($opponent);
    } else {
        $targetField = null;
    }

    if($targetZone === "theirLeader") {
        $target = null;
        $targetHealth = intval(GetLeaderHealth($opponent));
        $targetAttack = LeaderAttack($opponent);
    } else {
        if(!is_array($targetField) || $targetIndex < 0 || $targetIndex >= count($targetField) || $targetField[$targetIndex]->removed) return;
        $target = &$targetField[$targetIndex];
        $targetHealth = intval(CardHealth($target->CardID) ?? 0);
        $targetAttack = intval(CardAttack($target->CardID) ?? 0);
    }

    // Exhaust attacker
    ExhaustEntity($player, $attackerMZ);

    // Resolve damage simultaneously
    if($attackerAttack > 0 && $targetZone !== "theirLeader") {
        $target->Damage = ($target->Damage ?? 0) + $attackerAttack;
    } else if($attackerAttack > 0 && $targetZone === "theirLeader") {
        DealDamageToLeader($opponent, $attackerAttack);
    }

    if($targetAttack > 0) {
        // Entities take combat damage that resets at end of turn.
        $attacker->Damage = ($attacker->Damage ?? 0) + $targetAttack;
    }

    // Check if target (entity) is destroyed
    if($targetZone !== "theirLeader" && $target->Damage >= $targetHealth) {
        // Send destroyed entity to opponent's discard
        $target->removed = true;
        $discardZone = &GetDiscard($opponent);
        array_push($discardZone, $target);
    }
}

function HandleGateUsage($playerID) {
    if(!CanUseGate($playerID)) {
        SetFlashMessage("Gate cannot be used. (Already used this turn or tapped)");
        return;
    }

    // Get entities in alley that can be portaled
    $alley = &GetAlley($playerID);
    $portalCandidates = [];

    for($i = 0; $i < count($alley); ++$i) {
        if(!$alley[$i]->removed) {
            $portalCandidates[] = "myAlley-" . $i;
        }
    }

    if(empty($portalCandidates)) {
        SetFlashMessage("No entities in Alley to portal.");
        return;
    }

    if(count($portalCandidates) === 1) {
        // Auto-select if only one option
        ExecuteGatePortal($playerID, $portalCandidates[0]);
        return;
    }

    // Queue entity selection
    $entityStr = implode("&", $portalCandidates);
    DecisionQueueController::AddDecision($playerID, "MZCHOOSE", $entityStr, 1, "Select_entity_to_portal");
    DecisionQueueController::AddDecision($playerID, "CUSTOM", "PORTAL_FROM_ALLEY", 1);
}

function ExecuteGatePortal($player, $entityMZ) {
    $alley = &GetAlley($player);
    $garden = &GetGarden($player);
    $gate = &GetGate($player);
    $entityParts = explode("-", $entityMZ);
    $entityIndex = intval($entityParts[1] ?? -1);

    if($entityIndex < 0 || $entityIndex >= count($alley) || $alley[$entityIndex]->removed) return;

    $entity = &$alley[$entityIndex];

    // Check if Garden is full (max 5 entities)
    if(count($garden) >= 5) {
        // Garden is full — must replace an entity
        SetFlashMessage("Garden is full. Must select an entity to replace.");
        // Queue replacement selection here
        return;
    }

    // Move from Alley to Garden
    $entity->removed = true;
    $newEntity = clone $entity;
    $newEntity->removed = false;
    array_push($garden, $newEntity);

    // Add Cooldown effect to the portaled entity
    if(!isset($newEntity->TurnEffects)) $newEntity->TurnEffects = [];
    if(!in_array("COOLDOWN", $newEntity->TurnEffects)) {
        $newEntity->TurnEffects[] = "COOLDOWN";
    }

    // Mark Gate as tapped and used this turn
    if(!empty($gate)) {
        $gate[0]->Status = 1;
        if(!isset($gate[0]->TurnEffects)) $gate[0]->TurnEffects = [];
        if(!in_array("GATE_USED_THIS_TURN", $gate[0]->TurnEffects)) {
            $gate[0]->TurnEffects[] = "GATE_USED_THIS_TURN";
        }
    }
}

?>

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
            if (stripos($action, "Activate") === 0 && strpos($action, ':') !== false) {
                $actionParts = explode(':', $action);
                $abilityIndex = intval($actionParts[1] ?? 0);
                SaveUndoVersion($playerID);
                ActivateAbility($playerID, $actionCard, $abilityIndex);
            } else if ($action === "Attack" || $action === "Activate") {
                // Default field activation path: attack setup in Garden.
                HandleAttackSetup($playerID, $actionCard);
            }
            break;

        case "myGate":
        case "myGateSlot":
            // Gate activation — portal from Alley to Garden
            if ($action === "UseGate" || $action === "Activate") {
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

    $attackerParts = explode('-', $attackerMZ);
    $attackerZone = $attackerParts[0] ?? '';
    if($attackerZone !== 'myGarden') {
        SetFlashMessage("Only Garden units can attack.");
        return;
    }

    // Add opponent's leader as target if still alive.
    $leaderIdx = FindLeaderIndexInGarden($opponent);
    if($leaderIdx >= 0 && intval(GetLeaderHealth($opponent)) > 0) {
        $targets[] = "theirGarden-" . $leaderIdx;
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
        $leaderIdx2 = FindLeaderIndexInGarden($opponent);
        $fallbackTarget = ($leaderIdx2 >= 0) ? "theirGarden-" . $leaderIdx2 : "";
        if($fallbackTarget !== "") AttackWith($playerID, $attackerMZ, $fallbackTarget);
        return;
    }

    // Queue target selection
    $targetStr = implode("&", $targets);
    DecisionQueueController::AddDecision($playerID, "MZCHOOSE", $targetStr, 1, "Select_attack_target");
    DecisionQueueController::AddDecision($playerID, "CUSTOM", "RESOLVE_ATTACK|" . $attackerMZ, 1);
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

    $gateMZ = "myGate-0";

    if(count($portalCandidates) === 1) {
        // Auto-select if only one option
        UseGate($playerID, $gateMZ, $portalCandidates[0]);
        return;
    }

    // Queue entity selection
    $entityStr = implode("&", $portalCandidates);
    DecisionQueueController::AddDecision($playerID, "MZCHOOSE", $entityStr, 1, "Select_entity_to_portal");
    DecisionQueueController::AddDecision($playerID, "CUSTOM", "PORTAL_FROM_ALLEY|" . $gateMZ, 1);
}

?>

<?php

function MaybeSaveUndoVersion($playerID) {
    SaveVersion($playerID);
}

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
                ActivateAbility($playerID, $actionCard, $abilityIndex);
            } else if ($action === "Attack") {
                // Default field activation path: attack setup in Garden.
                MaybeSaveUndoVersion($playerID);
                HandleAttackSetup($playerID, $actionCard);
            } else if ($action === "Activate") {
                if($zone === "myGarden") {
                    if(CanActivateAbilityRuntime($playerID, $actionCard, 0) && CanActivateAbility($playerID, $actionCard, 0)) {
                        ActivateAbility($playerID, $actionCard, 0);
                    } else {
                        MaybeSaveUndoVersion($playerID);
                        HandleAttackSetup($playerID, $actionCard);
                    }
                } else {
                    ActivateAbility($playerID, $actionCard, 0);
                }
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
    if(HasPendingAttackResponse()) {
        $resolverPlayer = intval($playerID);
        $expectedResponder = intval(GetPendingAttackResponderPlayer());
        if($expectedResponder === 1 || $expectedResponder === 2) {
            $resolverPlayer = $expectedResponder;
        }
        if(!ResolveAttackAfterResponses($resolverPlayer)) {
            SetFlashMessage('Only the defending player can pass to resolve this attack response window.');
        }
        return;
    }

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
    if($leaderIdx >= 0 && LeaderCurrentHealth($opponent) > 0) {
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
        if($fallbackTarget !== "") {
            if(!CanAttackRuntime($playerID, $attackerMZ, $fallbackTarget)) return;
            ExhaustEntity($playerID, $attackerMZ);
            TriggerEquippedWeaponOnAttack($playerID, $attackerMZ);
            OnAttackWithCard($playerID, $attackerMZ, $fallbackTarget);
            DecisionQueueController::AddDecision($playerID, "CUSTOM", "BEGIN_ATTACK_RESPONSE|" . $attackerMZ . "|" . $fallbackTarget, 1);
        }
        return;
    }

    // Queue target selection
    $targetStr = implode("&", $targets);
    DecisionQueueController::AddDecision($playerID, "MZCHOOSE", $targetStr, 1, "Select_attack_target");
    DecisionQueueController::AddDecision($playerID, "CUSTOM", "RESOLVE_ATTACK|" . $attackerMZ, 1);
}

function HandleGateUsage($playerID) {
    if(!CanUseGateRuntime($playerID)) {
        SetFlashMessage("Gate cannot be used. (Already used this turn or tapped)");
        return;
    }

    // Only untapped Alley entities can be portaled.
    $portalCandidates = GetPortalCandidates($playerID);

    if(empty($portalCandidates)) {
        SetFlashMessage("No untapped entities in Alley to portal.");
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

<?php

function CustomWidgetInput($playerID, $actionCard, $action) {
    $cardArr = explode("-", $actionCard);
    $zone = $cardArr[0];
    $index = $cardArr[1];
    switch ($zone) {
      case "myHealth"://Pass button
        DecisionQueueController::CleanupRemovedCards();
        if(TryPassFastOpportunityDecision($playerID)) {
            break;
        }
        // Only the turn player can pass
        if(GetTurnPlayer() !== $playerID) {
            SetFlashMessage("Only the turn player can pass.");
            break;
        }
        // Don't end turn if EffectStack has cards or DQs are pending
        $effectStack = &GetEffectStack();
        $effectStack = &GetEffectStack();
        if(!empty($effectStack)) {
            SetFlashMessage("Cannot pass while effects are pending resolution.");
            break;
        }
        $dqController = new DecisionQueueController();
        if(!$dqController->AllQueuesEmpty()) {
            SetFlashMessage("Cannot pass while decisions are pending.");
            break;
        }
        // Grand Archive rules: passing out of the main phase requires the non-turn
        // player to also pass Opportunity before the phase actually advances.
        RequestMainPhasePass($playerID);
        break;
      case "myField":
      case "myIntent":
        // Parse ability index from action (e.g., "Activate:0", "Activate:1")
        $abilityIndex = 0;
        if (strpos($action, ':') !== false) {
            $actionParts = explode(':', $action);
            $abilityIndex = intval($actionParts[1]);
        }
        // During Opportunity windows, DQs are expected to be non-empty.
        // Route activate clicks through the same selection resolver as mode=100.
        $dqChk = new DecisionQueueController();
        if(!$dqChk->AllQueuesEmpty()) {
            $routed = false;
            if(function_exists("HasOpportunity") && HasOpportunity($playerID)
                && function_exists("GetPlayableOpportunityChoices")
                && function_exists("ResolveOpportunitySelection")) {
                $prefix = $actionCard . "@Activate-" . $abilityIndex;
                $choices = GetPlayableOpportunityChoices($playerID);
                foreach($choices as $choice) {
                    if(strpos($choice, $prefix) === 0) {
                        // Mirror mode=100 decision handling: consume the pending
                        // MZMAYCHOOSE and feed the selected encoded choice through
                        // ExecuteStaticMethods so the normal response/cost pipeline runs.
                        $dqRoute = new DecisionQueueController();
                        $dqRoute->PopDecision($playerID);
                        $dqRoute->ExecuteStaticMethods($playerID, $choice);
                        $routed = true;
                        break;
                    }
                }
            }
            break;
        }
        SaveUndoVersion($playerID);
        // Field-resident REGALIA/ITEM cards whose activated ability is registered in
        // $cardActivatedAbilities (the reserve-cost dictionary backed by CardCardActivatedCount,
        // normally reached via DoActivateCard/ActivateCard for hand/material plays) rather than
        // $activateAbilityAbilities (the free/memory-cost-0 dictionary backed by
        // CardActivateAbilityCount, reached via ActivateAbility/DoActivatedAbility) have no static
        // ability entry for DoActivatedAbility to find -- its $staticAbilityCount comes back 0,
        // misclassifying index 0 as a "dynamic" ability that matches nothing. Route those through
        // ActivateCard instead, same as reserve-cost hand activations.
        $targetObj = GetZoneObject($actionCard);
        $targetCardID = $targetObj !== null ? ($targetObj->CardID ?? null) : null;
        if($targetCardID !== null && function_exists("CardActivateAbilityCount") && function_exists("CardCardActivatedCount")
            && CardActivateAbilityCount($targetCardID) === 0 && CardCardActivatedCount($targetCardID) > 0) {
            ActivateCard($playerID, $actionCard, false);
            break;
        }
        ActivateAbility($playerID, $actionCard, $abilityIndex);
        break;
      case "myHand":
        // During Opportunity windows, DQs are expected to be non-empty.
        // Route hand activate clicks through the same selection resolver as mode=100.
        $dqChk = new DecisionQueueController();
        if(!$dqChk->AllQueuesEmpty()) {
            if(HasOpportunity($playerID)) {
                // Parse ability index from action (e.g., "Activate:0", "Activate:1")
                $abilityIndex = 0;
                if (strpos($action, ':') !== false) {
                    $actionParts = explode(':', $action);
                    $abilityIndex = intval($actionParts[1]);
                }
                $prefix = $actionCard . "@Activate-" . $abilityIndex;
                $choices = GetPlayableOpportunityChoices($playerID);
                foreach($choices as $choice) {
                    if(strpos($choice, $prefix) === 0) {
                        $dqRoute = new DecisionQueueController();
                        $dqRoute->PopDecision($playerID);
                        $dqRoute->ExecuteStaticMethods($playerID, $choice);
                        break 2;
                    }
                }
            }
            break;
        }
        // Parse ability index from action (e.g., "Activate:0", "Activate:1")
        $abilityIndex = 0;
        if (strpos($action, ':') !== false) {
            $actionParts = explode(':', $action);
            $abilityIndex = intval($actionParts[1]);
        }
        SaveUndoVersion($playerID);
        HandActivatedAbility($playerID, $actionCard, $abilityIndex);
        break;
      default: break;
    }
}

?>

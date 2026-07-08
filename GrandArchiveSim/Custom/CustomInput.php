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
        global $gCurrentPhase;
        $gCurrentPhase = "MAIN";
        AdvanceAndExecute("PASS");
        AutoAdvanceAndExecute();
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

<?php

function CustomWidgetInput($playerID, $actionCard, $action) {
    $cardArr = explode("-", $actionCard);
    $zone = $cardArr[0];
    $index = $cardArr[1];
    switch ($zone) {
      case "myHealth"://Pass button
        // Don't end turn if EffectStack has cards or DQs are pending
        $effectStack = &GetEffectStack();
        DecisionQueueController::CleanupRemovedCards();
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
        // Block ability activation while DQs are pending
        $dqChk = new DecisionQueueController();
        if(!$dqChk->AllQueuesEmpty()) break;
        // Parse ability index from action (e.g., "Activate:0", "Activate:1")
        $abilityIndex = 0;
        if (strpos($action, ':') !== false) {
            $actionParts = explode(':', $action);
            $abilityIndex = intval($actionParts[1]);
        }
        SaveUndoVersion($playerID);
        ActivateAbility($playerID, $actionCard, $abilityIndex);
        break;
      default: break;
    }
}

?>

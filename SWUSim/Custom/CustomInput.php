<?php

function CustomWidgetInput($playerID, $actionCard, $action = '') {
    $cardArr = explode("-", $actionCard);
    $zone = $cardArr[0];
    switch ($zone) {
      case "myHealth": // Pass button (SWU: consecutive-pass tracking in SWUPassAction)
        if (intval(GetTurnPlayer()) !== intval($playerID)) {
            SetFlashMessage("Only the active player can pass.");
            break;
        }
        $effectStack = &GetEffectStack();
        DecisionQueueController::CleanupRemovedCards();
        $effectStack = &GetEffectStack();
        if (!empty($effectStack)) {
            SetFlashMessage("Cannot pass while effects are pending.");
            break;
        }
        $dqController = new DecisionQueueController();
        if (!$dqController->AllQueuesEmpty()) {
            SetFlashMessage("Cannot pass while decisions are pending.");
            break;
        }
        $currentPhase = GetCurrentPhase();
        if ($currentPhase === "MAIN") {
            SWUPassAction(intval($playerID));
        }
        break;
      case "InitiativeCounter": // Player clicks the initiative counter to claim it
        if (intval(GetTurnPlayer()) !== intval($playerID)) {
            SetFlashMessage("Only the active player can take the initiative.");
            break;
        }
        $dqController = new DecisionQueueController();
        if (!$dqController->AllQueuesEmpty()) {
            SetFlashMessage("Cannot take initiative while decisions are pending.");
            break;
        }
        if (GetCurrentPhase() !== "MAIN") break;
        SaveUndoVersion($playerID);
        SWUTakeInitiative(intval($playerID));
        break;
      case "BlastCounter":
      case "PlanCounter":
        // Twin Suns (Phase 4): take the blast/plan counter (CR §12.5). Same active-player + empty-queue
        // guards as initiative. SWUTakeCounter enforces one-counter-per-round + AVAILABLE + "taking = pass".
        if (SeatCountForGame() <= 2) break;   // premier: no counters
        if (intval(GetTurnPlayer()) !== intval($playerID)) {
            SetFlashMessage("Only the active player can take a counter.");
            break;
        }
        $dqController = new DecisionQueueController();
        if (!$dqController->AllQueuesEmpty()) {
            SetFlashMessage("Cannot take a counter while decisions are pending.");
            break;
        }
        if (GetCurrentPhase() !== "MAIN") break;
        SaveUndoVersion($playerID);
        SWUTakeCounter(intval($playerID), $zone === "BlastCounter" ? 'blast' : 'plan');
        break;
      case "myField":
      case "myIntent":
        if (intval(GetTurnPlayer()) !== intval($playerID)) {
            SetFlashMessage("Only the active player can act.");
            break;
        }
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
      case "myGroundArena":
      case "mySpaceArena":
        // SWU unit activated ability ("Action [...]") — $actionCard is the unit mzID.
        if (intval(GetTurnPlayer()) !== intval($playerID)) {
            SetFlashMessage("Only the active player can act.");
            break;
        }
        $dqChk = new DecisionQueueController();
        if (!$dqChk->AllQueuesEmpty()) {
            SetFlashMessage("Cannot use an ability while decisions are pending.");
            break;
        }
        if (function_exists('SWUGetUnitActionProvider')) {
            $uObj = GetZoneObject($actionCard);
            if ($uObj !== null && empty($uObj->removed) && SWUGetUnitActionProvider($uObj) !== '') {
                SaveUndoVersion($playerID);
                SWUUnitAction($playerID, $actionCard);
                // Drain auto-decisions (PASSPARAMETER → effect → SWU_AFTER_ACTION); stops at any
                // MZCHOOSE/YESNO, which the player answers via the normal decision pipeline.
                (new DecisionQueueController())->ExecuteStaticMethods($playerID, "-");
            }
        }
        break;
      case "myHand":
        if (intval(GetTurnPlayer()) !== intval($playerID)) {
            SetFlashMessage("Only the active player can act.");
            break;
        }
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
        HandActivatedAbility($playerID, $actionCard, $abilityIndex);
        break;
      case "myLeader":
        // Actions: "LeaderAbility", "DeployLeader", "DeployLeader:Unit", "DeployLeader:Pilot"
        if (intval(GetTurnPlayer()) !== intval($playerID)) {
            SetFlashMessage("Only the active player can use their leader.");
            break;
        }
        $dqChk = new DecisionQueueController();
        if (!$dqChk->AllQueuesEmpty()) {
            SetFlashMessage("Cannot use leader while decisions are pending.");
            break;
        }
        if (GetCurrentPhase() !== "MAIN") break;

        // Twin Suns: the myLeader-{N} action string carries the clicked leader's index (0 or 1).
        $leaderIndex = isset($cardArr[1]) ? intval($cardArr[1]) : 0;
        $leaderObj   = SWUGetLeaderByIndex(intval($playerID), $leaderIndex);
        if ($leaderObj === null) break;

        $actionMain = $action;
        $deployMode = 'Unit';
        if (strpos($action, ':') !== false) {
            $parts      = explode(':', $action, 2);
            $actionMain = $parts[0];
            $deployMode = $parts[1];
        }

        if ($actionMain === 'LeaderAbility') {
            SaveUndoVersion($playerID);
            SWULeaderAction(intval($playerID), $leaderObj->CardID, $leaderIndex);
        } elseif ($actionMain === 'DeployLeader') {
            SaveUndoVersion($playerID);
            SWUDeployLeader(intval($playerID), $deployMode, '', $leaderIndex);
        }
        break;
      case "myBase":
        if (intval(GetTurnPlayer()) !== intval($playerID)) {
            SetFlashMessage("Only the active player can use their base.");
            break;
        }
        $dqChk = new DecisionQueueController();
        if (!$dqChk->AllQueuesEmpty()) {
            SetFlashMessage("Cannot use base while decisions are pending.");
            break;
        }
        if (GetCurrentPhase() !== "MAIN") break;
        SaveUndoVersion($playerID);
        SWUBaseAction(intval($playerID));
        break;
      case "myResources":
        if (intval(GetTurnPlayer()) !== intval($playerID)) {
            SetFlashMessage("Only the active player can act.");
            break;
        }
        $dqChk = new DecisionQueueController();
        if (!$dqChk->AllQueuesEmpty()) break;
        if (GetCurrentPhase() !== "MAIN") break;
        SaveUndoVersion($playerID);
        SWUSmuggleResource(intval($playerID), intval($cardArr[1]));
        break;
        case 'PlayFromDiscard':
            SaveUndoVersion($playerID);
            SWUPlayFromDiscard(intval($playerID), intval($cardArr[1]));
            break;
        case 'PlayFromOpponentDiscard':
            SaveUndoVersion($playerID);
            SWUPlayFromOpponentDiscard(intval($playerID), intval($cardArr[1]));
            break;
      case "GfPractice":
        // Goldfish practice "god-mode" actions (⚗ Practice menu). All act on the human's own
        // (P1) board. HARD-GATE: goldfish only + human seat (P1) only + your turn + no pending
        // decision. The UI is already goldfish-gated, but a crafted SubmitInput POST must be
        // rejected in real games — the UI is not a security boundary.
        if (!function_exists('SWUGameMode') || SWUGameMode() !== 'goldfish' || intval($playerID) !== 1) {
            SetFlashMessage("Practice actions are only available in Goldfish mode.");
            break;
        }
        if (intval(GetTurnPlayer()) !== intval($playerID)) {
            SetFlashMessage("Practice actions are only usable on your turn.");
            break;
        }
        $gfDqChk = new DecisionQueueController();
        if (!$gfDqChk->AllQueuesEmpty()) {
            SetFlashMessage("Finish the current decision first.");
            break;
        }
        if (GetCurrentPhase() !== "MAIN") break;

        // Action is "<name>" or "<name>:<number>".
        $gfAction = $action;
        $gfArg    = 0;
        if (strpos($action, ':') !== false) {
            $gfParts  = explode(':', $action, 2);
            $gfAction = $gfParts[0];
            $gfArg    = max(0, intval($gfParts[1]));
        }
        // The human's own units (defeat / bounce / damage-units all target these).
        $gfMyUnits = array_merge(
            ZoneSearch("myGroundArena", AnyUnitFilter),
            ZoneSearch("mySpaceArena",  AnyUnitFilter)
        );

        if ($gfAction === 'BaseDamage') {
            if ($gfArg <= 0) break;
            SaveUndoVersion($playerID);
            SWUDealDamageToBase($gfArg, 1);
        } elseif ($gfAction === 'DamageUnits') {
            if ($gfArg <= 0) break;
            if (empty($gfMyUnits)) { SetFlashMessage("You have no units to damage."); break; }
            SaveUndoVersion($playerID);
            DecisionQueueController::AddDecision(1, "MZSPLITASSIGN",
                $gfArg . "|" . implode('&', $gfMyUnits) . "|UPTO", 1,
                tooltip: "Assign_up_to_{$gfArg}_damage_among_your_units");
            DecisionQueueController::AddDecision(1, "CUSTOM", "SPLIT_DAMAGE", 1);
        } elseif ($gfAction === 'DefeatUnit') {
            if (empty($gfMyUnits)) { SetFlashMessage("You have no units to defeat."); break; }
            SaveUndoVersion($playerID);
            DecisionQueueController::AddDecision(1, "MZCHOOSE", implode('&', $gfMyUnits), 1,
                tooltip: "Choose_one_of_your_units_to_defeat");
            DecisionQueueController::AddDecision(1, "CUSTOM", "GfDefeat", 1);
        } elseif ($gfAction === 'BounceUnit') {
            if (empty($gfMyUnits)) { SetFlashMessage("You have no units to return."); break; }
            SaveUndoVersion($playerID);
            DecisionQueueController::AddDecision(1, "MZCHOOSE", implode('&', $gfMyUnits), 1,
                tooltip: "Choose_one_of_your_units_to_return_to_hand");
            DecisionQueueController::AddDecision(1, "CUSTOM", "GfBounce", 1);
        }
        break;
      default: break;
    }
}

?>

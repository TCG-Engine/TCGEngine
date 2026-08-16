<?php

const AZUKI_TUTORIAL_RECRUIT_CARD = 'S1-STT01-004_Black-Jade-Recruit_E_C_die';
const AZUKI_TUTORIAL_SHURIKEN_CARD = 'S1-STT01-012_Lightning-Shuriken_W_C_die';
const AZUKI_TUTORIAL_RAIZAN_CARD = 'S1-STT01-001_Raizan_L_L_die';
const AZUKI_TUTORIAL_DAGGER_CARD = 'S1-STT01-013_Black-Jade-Dagger_W_C_die';
const AZUKI_TUTORIAL_RESPONSE_CARD = 'S1-STT01-017_Lightning-Orb_S_UC_die';

function AzukiTutorialIsActive(): bool {
    return strval(DecisionQueueController::GetVariable('GameMode') ?? '') === 'tutorial';
}

function AzukiTutorialStep(): int {
    return max(0, intval(DecisionQueueController::GetVariable('TutorialStep') ?? 0));
}

function AzukiTutorialSetStep($step): void {
    DecisionQueueController::StoreVariable('TutorialStep', strval(max(0, intval($step))));
}

function AzukiTutorialTakeCards(&$pool, $cardIDs): array {
    $taken = [];
    foreach($cardIDs as $cardID) {
        $index = array_search($cardID, $pool, true);
        if($index === false) continue;
        $taken[] = $pool[$index];
        array_splice($pool, $index, 1);
    }
    return $taken;
}

function AzukiTutorialBuildStarterZones($player, $openingIDs, $authoredTopIDs = []): void {
    $starter = GetPreconstructedDeckConfig('Raizan');
    $pool = array_values($starter['deckList'] ?? []);
    $opening = AzukiTutorialTakeCards($pool, $openingIDs);
    $authoredTop = AzukiTutorialTakeCards($pool, $authoredTopIDs);

    $hand = &GetHand($player);
    $hand = [];
    foreach($opening as $index => $cardID) $hand[] = new Hand($cardID, 'Hand', $player, $index);

    $deck = &GetDeck($player);
    $deck = [];
    foreach(array_merge($authoredTop, $pool) as $index => $cardID) {
        $deck[] = new Deck($cardID, 'Deck', $player, $index);
    }
}

function AzukiTutorialSetupGame(): void {
    DecisionQueueController::StoreVariable('GameMode', 'tutorial');
    DecisionQueueController::StoreVariable('TutorialLesson', 'basics');
    AzukiTutorialSetStep(0);

    // Both seats use the complete Raizan starter list. The authored opening cards and deck top make
    // the lesson deterministic while leaving a real match for the unguided bot continuation.
    AzukiTutorialBuildStarterZones(1, [
        AZUKI_TUTORIAL_RECRUIT_CARD,
        AZUKI_TUTORIAL_SHURIKEN_CARD,
        'S1-STT01-005_Alpine-Prowler_E_C_die',
        'S1-STT01-007_Alley-Guy_E_C_die',
        'S1-STT01-003_Crate-Rat-Kurobo_E_C_die',
        'S1-STT01-009_Mastersmith-Yamada_E_UC_die',
        'S1-STT01-006_Silver-Current-Haruhi_E_R_die',
    ], [
        AZUKI_TUTORIAL_DAGGER_CARD,
        AZUKI_TUTORIAL_RECRUIT_CARD,
        'S1-STT01-003_Crate-Rat-Kurobo_E_C_die',
        'S1-STT01-009_Mastersmith-Yamada_E_UC_die',
        'S1-STT01-006_Silver-Current-Haruhi_E_R_die',
    ]);
    AzukiTutorialBuildStarterZones(2, [
        AZUKI_TUTORIAL_RESPONSE_CARD,
        AZUKI_TUTORIAL_RECRUIT_CARD,
        'S1-STT01-005_Alpine-Prowler_E_C_die',
        'S1-STT01-007_Alley-Guy_E_C_die',
        'S1-STT01-003_Crate-Rat-Kurobo_E_C_die',
        AZUKI_TUTORIAL_DAGGER_CARD,
        'S1-STT01-009_Mastersmith-Yamada_E_UC_die',
    ]);

    $p1Alley = &GetAlley(1);
    $p1Alley = [];
    $p2Alley = &GetAlley(2);
    $p2Alley = [];

    $p1Discard = &GetDiscard(1);
    $p1Discard = [];
    $p2Discard = &GetDiscard(2);
    $p2Discard = [];

    $p1IKZ = &GetIKZArea(1);
    $p1IKZ = [new IKZArea('IKZ-001_IKZ!_IKZ_die 2', 'IKZArea', 1, 0)];
    $p2IKZ = &GetIKZArea(2);
    $p2IKZ = [];

    $p1Token = &GetIKZToken(1);
    $p1Token = 0;
    $p2Token = &GetIKZToken(2);
    $p2Token = 0;
    DecisionQueueController::StoreVariable('P2_StartingIKZTokenPending', '1');

    $turnPlayer = &GetTurnPlayer();
    $turnPlayer = 1;
    $turnNumber = &GetTurnNumber();
    $turnNumber = 1;
    $phase = &GetCurrentPhase();
    $phase = 'MAIN';
    SetPhaseParameters('-');
    SetFlashMessage('');
}

function AzukiTutorialFindCard($player, $zoneName, $cardID) {
    if($zoneName === 'Garden') $zone = &GetGarden(intval($player));
    else if($zoneName === 'Alley') $zone = &GetAlley(intval($player));
    else if($zoneName === 'Hand') $zone = &GetHand(intval($player));
    else if($zoneName === 'Discard') $zone = &GetDiscard(intval($player));
    else return null;
    if(!is_array($zone)) return null;
    foreach($zone as $obj) {
        if(!is_object($obj) || (!empty($obj->removed))) continue;
        if(strval($obj->CardID ?? '') === $cardID) return $obj;
    }
    return null;
}

function AzukiTutorialExpectedMessage(): string {
    switch(AzukiTutorialStep()) {
        case 0: return 'Play Black Jade Recruit from your hand and choose the Alley.';
        case 1: return 'Discard Lightning Shuriken for Black Jade Recruit\'s On Play ability.';
        case 2: return 'Choose Black Jade Dagger from the top five cards.';
        case 3: return 'Put the remaining revealed cards on the bottom in any order.';
        case 4: return 'Use Surge Gate to portal Black Jade Recruit.';
        case 5: return 'Review Gate Power and the discard pile, then continue.';
        case 6: return 'Choose Lightning Shuriken from the selection popup.';
        case 7: return 'Attach Lightning Shuriken to Raizan.';
        case 8: return 'Attack the opposing leader with Raizan.';
        case 9: return 'Review the response window, then continue.';
        case 10: return 'Review Lightning Orb and the combat result, then continue.';
        case 11: return 'Pass the turn so Black Jade Recruit can clear cooldown.';
        case 12: return 'Review the opposing turn, then continue.';
        case 13: return 'Make a follow-up attack with Black Jade Recruit.';
        case 14: return 'Review the response window, then continue.';
        case 15: return 'Review the follow-up combat result, then continue.';
        default: return 'The basics lesson is complete.';
    }
}

function AzukiTutorialReject($message = ''): array {
    $message = $message !== '' ? $message : AzukiTutorialExpectedMessage();
    SetFlashMessage($message);
    return ['allowed' => false, 'message' => $message];
}

function AzukiTutorialActionCardID($mzID): string {
    $obj = GetZoneObject(strval($mzID));
    return is_object($obj) ? strval($obj->CardID ?? '') : '';
}

function AzukiTutorialIsPassAction($mode, $cardID): bool {
    if(intval($mode) !== 10001) return false;
    $parts = explode('!', strval($cardID));
    $actionCard = strval($parts[0] ?? '');
    $widgetType = strval($parts[1] ?? '');
    $action = strval($parts[2] ?? '');
    return in_array($actionCard, ['myLeaderHealth-0', 'myLeaderHealth', 'myLeaderHealthSlot'], true)
        && strcasecmp($widgetType, 'CustomInput') === 0
        && strcasecmp($action, 'Pass') === 0;
}

function AzukiTutorialIsContinueAction($mode, $cardID): bool {
    if(intval($mode) !== 10001) return false;
    $parts = explode('!', strval($cardID));
    return strcasecmp(strval($parts[0] ?? ''), 'Tutorial') === 0
        && strcasecmp(strval($parts[1] ?? ''), 'CustomInput') === 0
        && strcasecmp(strval($parts[2] ?? ''), 'Continue') === 0;
}

function AzukiTutorialIsDaggerSearcherDecision($mode, $cardID): bool {
    if(intval($mode) !== 100) return false;

    $dqController = new DecisionQueueController();
    $nextDecision = $dqController->NextDecision(1);
    if(!is_object($nextDecision) || strval($nextDecision->Type ?? '') !== 'MZREARRANGE') return false;

    $selectedIndex = null;
    foreach(explode(';', strval($cardID)) as $part) {
        $eqPos = strpos($part, '=');
        if($eqPos === false || trim(substr($part, 0, $eqPos)) !== 'Selected') continue;
        $selectedValue = trim(substr($part, $eqPos + 1));
        if($selectedValue === '' || !ctype_digit($selectedValue)) return false;
        $selectedIndex = intval($selectedValue);
        break;
    }
    if($selectedIndex === null) return false;

    $tempStart = intval(DecisionQueueController::GetVariable('P1_BottomDeckSearcher_TempStart') ?? 0);
    $tempZone = &GetTempZone(1);
    $selectedObject = $tempZone[$tempStart + $selectedIndex] ?? null;
    return is_object($selectedObject)
        && empty($selectedObject->removed)
        && strval($selectedObject->CardID ?? '') === AZUKI_TUTORIAL_DAGGER_CARD;
}

function AzukiTutorialContinue($player): void {
    if(!AzukiTutorialIsActive() || intval($player) !== 1) return;
    $step = AzukiTutorialStep();
    if($step === 9) {
        // The second player has not received any IKZ yet, so they cannot cast Lightning Orb during
        // this first-turn response window. Pass and let the attack resolve normally.
        HandlePassButton(2);
        AzukiTutorialUpdateProgress();
    } else if($step === 14) {
        // On the following turn the opponent has a ready IKZ. Pay its real cost and cast Lightning
        // Orb on the attacking 1-health Recruit, demonstrating that a Response can stop an attack.
        $outerPlayerID = $GLOBALS['playerID'] ?? null;
        $GLOBALS['playerID'] = 2;
        DoPlayCard(2, 'myHand-0', false);
        $dqController = new DecisionQueueController();
        $responseChoice = $dqController->NextDecision(2);
        if(is_object($responseChoice) && strval($responseChoice->Type ?? '') === 'MZCHOOSE') {
            $dqController->PopDecision(2);
            $dqController->ExecuteStaticMethods(2, 'theirGarden-1');
        }
        if($outerPlayerID === null) unset($GLOBALS['playerID']);
        else $GLOBALS['playerID'] = $outerPlayerID;
        if(HasPendingAttackResponse()) HandlePassButton(2);
        AzukiTutorialUpdateProgress();
    } else if($step === 12) {
        HandlePassButton(2);
        AzukiTutorialUpdateProgress();
    } else if($step === 5) {
        AzukiTutorialSetStep(6);
    } else if($step === 10) {
        AzukiTutorialSetStep(11);
    } else if($step === 15) {
        AzukiTutorialSetStep(16);
    } else if($step === 16) {
        // Preserve the lesson's current board and remove the action rails. From this point onward,
        // seat 2 is driven by the same browser-safe RL bot controller as a normal bot match.
        DecisionQueueController::StoreVariable('TutorialCompleted', '1');
        DecisionQueueController::StoreVariable('GameMode', 'rlbot');
        DecisionQueueController::StoreVariable('AzukiRlBotProfile', 'raizan');
        SetAzukiRlBotPlayers([2]);
        SetFlashMessage('Tutorial complete. Play the rest of this match against the bot!');
    }
}

/** Optional root hook called by Core/EngineActionRunner.php. */
function GameValidateEngineAction($action): array {
    if(!AzukiTutorialIsActive()) return ['allowed' => true];

    $player = intval($action['playerID'] ?? 0);
    $mode = intval($action['mode'] ?? 0);
    $cardID = strval($action['cardID'] ?? '');
    $step = AzukiTutorialStep();

    // Transport/settings actions do not mutate the lesson directly.
    if($mode === 10017 || $mode === 10015) return ['allowed' => true];

    // Opponent actions are performed synchronously by AzukiTutorialContinue(), not through this
    // browser-facing action hook.
    if($player === 2) {
        return AzukiTutorialReject('The tutorial opponent is controlled by the lesson.');
    }

    if($player !== 1) return AzukiTutorialReject();

    if(in_array($step, [5, 9, 10, 12, 14, 15, 16], true) && AzukiTutorialIsContinueAction($mode, $cardID)) {
        return ['allowed' => true];
    }

    if($step === 0) {
        if($mode === 10002 && AzukiTutorialActionCardID(explode('!', $cardID)[0] ?? '') === AZUKI_TUTORIAL_RECRUIT_CARD) {
            return ['allowed' => true];
        }
        if($mode === 100 && $cardID === 'myAlley') return ['allowed' => true];
    } else if($step === 1) {
        if($mode === 100 && AzukiTutorialActionCardID($cardID) === AZUKI_TUTORIAL_SHURIKEN_CARD) return ['allowed' => true];
    } else if($step === 2) {
        // The current searcher UI selects the card and orders the remainder in one local popup,
        // then submits one serialized MZREARRANGE decision on Confirm. Keep accepting the old
        // direct-card action as a compatibility path for an already-open legacy popup.
        if($mode === 100 && AzukiTutorialActionCardID($cardID) === AZUKI_TUTORIAL_DAGGER_CARD) return ['allowed' => true];
        if(AzukiTutorialIsDaggerSearcherDecision($mode, $cardID)) return ['allowed' => true];
    } else if($step === 3) {
        if($mode === 100) return ['allowed' => true];
    } else if($step === 4) {
        if($mode === 10002 && strpos($cardID, 'myGate-0!') === 0) return ['allowed' => true];
        if($mode === 10001 && strpos($cardID, 'myGate') === 0) return ['allowed' => true];
    } else if($step === 6) {
        if($mode === 100 && AzukiTutorialActionCardID($cardID) === AZUKI_TUTORIAL_SHURIKEN_CARD) return ['allowed' => true];
    } else if($step === 7) {
        if($mode === 100 && AzukiTutorialActionCardID($cardID) === AZUKI_TUTORIAL_RAIZAN_CARD) return ['allowed' => true];
    } else if($step === 8) {
        if($mode === 10002 && AzukiTutorialActionCardID(explode('!', $cardID)[0] ?? '') === AZUKI_TUTORIAL_RAIZAN_CARD) {
            return ['allowed' => true];
        }
        if($mode === 100 && strpos($cardID, 'theirGarden-') === 0
            && CardType(AzukiTutorialActionCardID($cardID)) === 'LEADER') {
            return ['allowed' => true];
        }
    } else if($step === 11) {
        if(AzukiTutorialIsPassAction($mode, $cardID)) return ['allowed' => true];
    } else if($step === 13) {
        if($mode === 10002 && AzukiTutorialActionCardID(explode('!', $cardID)[0] ?? '') === AZUKI_TUTORIAL_RECRUIT_CARD) {
            return ['allowed' => true];
        }
        if($mode === 100 && strpos($cardID, 'theirGarden-') === 0
            && CardType(AzukiTutorialActionCardID($cardID)) === 'LEADER') {
            return ['allowed' => true];
        }
    }

    return AzukiTutorialReject();
}

function AzukiTutorialUpdateProgress(): void {
    if(!AzukiTutorialIsActive()) return;
    $step = AzukiTutorialStep();

    if($step === 0 && AzukiTutorialFindCard(1, 'Alley', AZUKI_TUTORIAL_RECRUIT_CARD) !== null) {
        AzukiTutorialSetStep(1);
        SetFlashMessage('Black Jade Recruit may discard a Weapon to search the top five cards.');
        return;
    }
    if($step === 1 && AzukiTutorialFindCard(1, 'Discard', AZUKI_TUTORIAL_SHURIKEN_CARD) !== null) {
        AzukiTutorialSetStep(2);
        return;
    }
    $dqController = new DecisionQueueController();
    $nextDecision = $dqController->NextDecision(1);
    if($step === 2 && is_object($nextDecision) && strval($nextDecision->Type ?? '') === 'MZREARRANGE') {
        AzukiTutorialSetStep(3);
        return;
    }
    if($step === 2 && $nextDecision === null
        && AzukiTutorialFindCard(1, 'Hand', AZUKI_TUTORIAL_DAGGER_CARD) !== null) {
        // A tutorial saved on step 2 can reach here after the combined searcher submission.
        AzukiTutorialSetStep(4);
        return;
    }
    if($step === 3 && $nextDecision === null) {
        AzukiTutorialSetStep(4);
        return;
    }
    if($step === 4 && AzukiTutorialFindCard(1, 'Garden', AZUKI_TUTORIAL_RECRUIT_CARD) !== null) {
        AzukiTutorialSetStep(5);
        SetFlashMessage('Recruit has Gate Power 1, so Surge Gate can play the cost-1 Shuriken from discard.');
        return;
    }
    if($step === 6 && strval(DecisionQueueController::GetVariable('chosenWeapon') ?? '') !== '') {
        AzukiTutorialSetStep(7);
        return;
    }
    if($step === 7) {
        $leader = AzukiTutorialFindCard(1, 'Garden', AZUKI_TUTORIAL_RAIZAN_CARD);
        if(is_object($leader) && in_array(AZUKI_TUTORIAL_SHURIKEN_CARD, $leader->Subcards ?? [], true)) {
            AzukiTutorialSetStep(8);
            SetFlashMessage('Raizan can attack while equipped. Lightning Shuriken triggers whenever Raizan attacks.');
            return;
        }
    }
    if($step === 8 && HasPendingAttackResponse()) {
        AzukiTutorialSetStep(9);
        SetFlashMessage('The defending player now has a response window before combat resolves.');
        return;
    }
    if($step === 9) {
        $leader = AzukiTutorialFindCard(1, 'Garden', AZUKI_TUTORIAL_RAIZAN_CARD);
        if(is_object($leader) && intval($leader->Status ?? 2) === 1 && LeaderCurrentHealth(2) < 20 && !HasPendingAttackResponse()) {
            AzukiTutorialSetStep(10);
            SetFlashMessage('The opponent had no IKZ for a Response, so Raizan\'s attack resolved.');
            return;
        }
    }
    if($step === 11 && intval(GetTurnPlayer()) === 2) {
        AzukiTutorialSetStep(12);
        return;
    }
    if($step === 12 && intval(GetTurnPlayer()) === 1 && GetCurrentPhase() === 'MAIN') {
        AzukiTutorialSetStep(13);
        SetFlashMessage('Black Jade Recruit is ready and its cooldown is gone. Make your follow-up attack.');
        return;
    }
    if($step === 13 && HasPendingAttackResponse()) {
        AzukiTutorialSetStep(14);
        return;
    }
    if($step === 14) {
        $recruit = AzukiTutorialFindCard(1, 'Garden', AZUKI_TUTORIAL_RECRUIT_CARD);
        if($recruit === null && AzukiTutorialFindCard(2, 'Discard', AZUKI_TUTORIAL_RESPONSE_CARD) !== null && !HasPendingAttackResponse()) {
            AzukiTutorialSetStep(15);
            SetFlashMessage('Lightning Orb defeated the attacking Recruit before combat damage.');
            return;
        }
    }
}

/** Optional root hook called before an engine action mutates state. */
function GameBeforeEngineAction($action): void {
    if(function_exists('AzukiSimHistoryBeforeEngineAction')) AzukiSimHistoryBeforeEngineAction($action);
}

/** Optional root hook called after a successful engine action and before persistence. */
function GameAfterEngineAction($action, $result): void {
    if(empty($result['success'])) return;
    $mode = intval(is_array($action) ? ($action['mode'] ?? 0) : 0);
    if($mode !== 10004 && $mode !== 10020) AzukiTutorialUpdateProgress();
    if(function_exists('SimHistoryCommitPending')) SimHistoryCommitPending();
}

function AzukiTutorialPendingPlayerForClient(): int {
    // Tutorial opponent actions are executed synchronously by AzukiTutorialContinue().
    return 0;
}

?>

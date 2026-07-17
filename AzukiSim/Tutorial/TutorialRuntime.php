<?php

const AZUKI_TUTORIAL_RECRUIT_CARD = 'S1-STT01-004_Black-Jade-Recruit_E_C_die';
const AZUKI_TUTORIAL_SHURIKEN_CARD = 'S1-STT01-012_Lightning-Shuriken_W_C_die';
const AZUKI_TUTORIAL_RAIZAN_CARD = 'S1-STT01-001_Raizan_L_L_die';

function AzukiTutorialIsActive(): bool {
    return strval(DecisionQueueController::GetVariable('GameMode') ?? '') === 'tutorial';
}

function AzukiTutorialStep(): int {
    return max(0, intval(DecisionQueueController::GetVariable('TutorialStep') ?? 0));
}

function AzukiTutorialSetStep($step): void {
    DecisionQueueController::StoreVariable('TutorialStep', strval(max(0, intval($step))));
}

function AzukiTutorialSetupGame(): void {
    DecisionQueueController::StoreVariable('GameMode', 'tutorial');
    DecisionQueueController::StoreVariable('TutorialLesson', 'basics');
    AzukiTutorialSetStep(0);

    $p1Hand = &GetHand(1);
    $p1Hand = [new Hand(AZUKI_TUTORIAL_RECRUIT_CARD, 'Hand', 1, 0)];
    $p2Hand = &GetHand(2);
    $p2Hand = [];

    // The tutorial never uses the lobby's selected deck or a random shuffle. These authored draw
    // piles make reloads, future lesson steps, and scripted opponent turns fully reproducible.
    $p1DeckIDs = [
        'S1-STT01-013_Black-Jade-Dagger_W_C_die',
        'S1-STT01-004_Black-Jade-Recruit_E_C_die',
        'S1-STT01-003_Crate-Rat-Kurobo_E_C_die',
        'S1-STT01-012_Lightning-Shuriken_W_C_die',
        'S1-STT01-009_Mastersmith-Yamada_E_UC_die',
    ];
    $p2DeckIDs = [
        'S1-STT01-003_Crate-Rat-Kurobo_E_C_die',
        'S1-STT01-004_Black-Jade-Recruit_E_C_die',
        'S1-STT01-013_Black-Jade-Dagger_W_C_die',
        'S1-STT01-012_Lightning-Shuriken_W_C_die',
        'S1-STT01-009_Mastersmith-Yamada_E_UC_die',
    ];
    $p1Deck = &GetDeck(1);
    $p1Deck = [];
    foreach($p1DeckIDs as $index => $cardID) $p1Deck[] = new Deck($cardID, 'Deck', 1, $index);
    $p2Deck = &GetDeck(2);
    $p2Deck = [];
    foreach($p2DeckIDs as $index => $cardID) $p2Deck[] = new Deck($cardID, 'Deck', 2, $index);

    $p1Alley = &GetAlley(1);
    $p1Alley = [];
    $p2Alley = &GetAlley(2);
    $p2Alley = [];

    $p1Discard = &GetDiscard(1);
    $p1Discard = [new Discard(AZUKI_TUTORIAL_SHURIKEN_CARD, 'Discard', 1, 0)];
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
        case 1: return 'Use Surge Gate to portal Black Jade Recruit.';
        case 2: return 'Review Gate Power and the discard pile, then continue.';
        case 3: return 'Choose Lightning Shuriken from the selection popup.';
        case 4: return 'Attach Lightning Shuriken to Raizan.';
        case 5: return 'Attack the opposing leader with Raizan.';
        case 6: return 'Review the response window, then continue.';
        case 7: return 'Review Lightning Shuriken and the combat result, then continue.';
        case 8: return 'Pass the turn so Black Jade Recruit can clear cooldown.';
        case 9: return 'Review the opposing turn, then continue.';
        case 10: return 'Make a follow-up attack with Black Jade Recruit.';
        case 11: return 'Review the response window, then continue.';
        case 12: return 'Review the follow-up combat result, then continue.';
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

function AzukiTutorialContinue($player): void {
    if(!AzukiTutorialIsActive() || intval($player) !== 1) return;
    $step = AzukiTutorialStep();
    if($step === 6 || $step === 11) {
        // Scripted tutorial actions resolve synchronously with Continue. This keeps explanatory
        // pauses learner-controlled without relying on a second browser polling/controller cycle.
        HandlePassButton(2);
        AzukiTutorialUpdateProgress();
    } else if($step === 9) {
        HandlePassButton(2);
        AzukiTutorialUpdateProgress();
    } else if($step === 2) {
        AzukiTutorialSetStep(3);
    } else if($step === 7) {
        AzukiTutorialSetStep(8);
    } else if($step === 12) {
        AzukiTutorialSetStep(13);
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

    if(in_array($step, [2, 6, 7, 9, 11, 12], true) && AzukiTutorialIsContinueAction($mode, $cardID)) {
        return ['allowed' => true];
    }

    if($step === 0) {
        if($mode === 10002 && AzukiTutorialActionCardID(explode('!', $cardID)[0] ?? '') === AZUKI_TUTORIAL_RECRUIT_CARD) {
            return ['allowed' => true];
        }
        if($mode === 100 && $cardID === 'myAlley') return ['allowed' => true];
    } else if($step === 1) {
        if($mode === 10002 && strpos($cardID, 'myGate-0!') === 0) return ['allowed' => true];
        if($mode === 10001 && strpos($cardID, 'myGate') === 0) return ['allowed' => true];
    } else if($step === 3) {
        if($mode === 100 && AzukiTutorialActionCardID($cardID) === AZUKI_TUTORIAL_SHURIKEN_CARD) return ['allowed' => true];
    } else if($step === 4) {
        if($mode === 100 && AzukiTutorialActionCardID($cardID) === AZUKI_TUTORIAL_RAIZAN_CARD) return ['allowed' => true];
    } else if($step === 5) {
        if($mode === 10002 && AzukiTutorialActionCardID(explode('!', $cardID)[0] ?? '') === AZUKI_TUTORIAL_RAIZAN_CARD) {
            return ['allowed' => true];
        }
        if($mode === 100 && strpos($cardID, 'theirGarden-') === 0
            && CardType(AzukiTutorialActionCardID($cardID)) === 'LEADER') {
            return ['allowed' => true];
        }
    } else if($step === 8) {
        if(AzukiTutorialIsPassAction($mode, $cardID)) return ['allowed' => true];
    } else if($step === 10) {
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
        SetFlashMessage('Black Jade Recruit is protected in the Alley. Now use Surge Gate.');
        return;
    }
    if($step === 1 && AzukiTutorialFindCard(1, 'Garden', AZUKI_TUTORIAL_RECRUIT_CARD) !== null) {
        AzukiTutorialSetStep(2);
        SetFlashMessage('Recruit has Gate Power 1, so Surge Gate can play a cost-1 Weapon from discard.');
        return;
    }
    if($step === 3 && strval(DecisionQueueController::GetVariable('chosenWeapon') ?? '') !== '') {
        AzukiTutorialSetStep(4);
        return;
    }
    if($step === 4) {
        $leader = AzukiTutorialFindCard(1, 'Garden', AZUKI_TUTORIAL_RAIZAN_CARD);
        if(is_object($leader) && in_array(AZUKI_TUTORIAL_SHURIKEN_CARD, $leader->Subcards ?? [], true)) {
            AzukiTutorialSetStep(5);
            SetFlashMessage('Raizan can attack while equipped. Lightning Shuriken triggers whenever Raizan attacks.');
            return;
        }
    }
    if($step === 5 && HasPendingAttackResponse()) {
        AzukiTutorialSetStep(6);
        SetFlashMessage('The defending player now has a response window before combat resolves.');
        return;
    }
    if($step === 6) {
        $leader = AzukiTutorialFindCard(1, 'Garden', AZUKI_TUTORIAL_RAIZAN_CARD);
        if(is_object($leader) && intval($leader->Status ?? 2) === 1 && LeaderCurrentHealth(2) < 20 && !HasPendingAttackResponse()) {
            AzukiTutorialSetStep(7);
            SetFlashMessage('Lightning Shuriken milled the top card of your deck before combat damage.');
            return;
        }
    }
    if($step === 8 && intval(GetTurnPlayer()) === 2) {
        AzukiTutorialSetStep(9);
        return;
    }
    if($step === 9 && intval(GetTurnPlayer()) === 1 && GetCurrentPhase() === 'MAIN') {
        AzukiTutorialSetStep(10);
        SetFlashMessage('Black Jade Recruit is ready and its cooldown is gone. Make your follow-up attack.');
        return;
    }
    if($step === 10 && HasPendingAttackResponse()) {
        AzukiTutorialSetStep(11);
        return;
    }
    if($step === 11) {
        $recruit = AzukiTutorialFindCard(1, 'Garden', AZUKI_TUTORIAL_RECRUIT_CARD);
        if(is_object($recruit) && intval($recruit->Status ?? 2) === 1 && LeaderCurrentHealth(2) <= 18 && !HasPendingAttackResponse()) {
            AzukiTutorialSetStep(12);
            SetFlashMessage('Black Jade Recruit completed the follow-up attack.');
            return;
        }
    }
}

/** Optional root hook called after a successful engine action and before persistence. */
function GameAfterEngineAction($action, $result): void {
    if(!empty($result['success'])) AzukiTutorialUpdateProgress();
}

function AzukiTutorialPendingPlayerForClient(): int {
    // Tutorial opponent actions are executed synchronously by AzukiTutorialContinue().
    return 0;
}

?>

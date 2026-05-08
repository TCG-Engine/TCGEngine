<?php

$debugMode = true;
$customDQHandlers = [];

// --- Helper Functions ---

function DealDamageToLeader($player, $amount) {
    if($amount <= 0) return;
    $leaderZone = &GetLeader($player);
    if(empty($leaderZone)) return;
    $leader = &$leaderZone[0];
    $leader->Damage += $amount;
    $maxHealth = intval(CardHealth($leader->CardID) ?? 0);
    if($maxHealth > 0 && $leader->Damage >= $maxHealth) {
        // Player loses the game
        $gameWon = true; // Simplified — framework will handle formal win check
    }
}

function HealLeader($player, $amount) {
    if($amount <= 0) return;
    $leaderZone = &GetLeader($player);
    if(empty($leaderZone)) return;
    $leader = &$leaderZone[0];
    $maxHealth = intval(CardHealth($leader->CardID) ?? 0);
    $leader->Damage = max(0, $leader->Damage - $amount);
    if($maxHealth > 0 && $leader->Damage < 0) {
        $leader->Damage = 0; // No overheal
    }
}

function CanUseGate($player) {
    $gateZone = &GetGate($player);
    if(empty($gateZone)) return false;
    $gate = &$gateZone[0];
    // Gate can only be used once per turn and must be untapped
    if(!isset($gate->Status)) $gate->Status = 2;
    if($gate->Status == 1) return false; // Tapped

    // Check if gate was already used this turn
    if(isset($gate->TurnEffects) && in_array("GATE_USED_THIS_TURN", $gate->TurnEffects)) {
        return false;
    }
    return true;
}

function ExhaustEntity($player, $mzID) {
    $mzParts = explode("-", $mzID);
    $zone = $mzParts[0];
    $index = intval($mzParts[1] ?? -1);

    if($zone === "myGarden" || $zone === "theirGarden") {
        $field = &GetGarden(($zone === "myGarden") ? $player : 3 - $player);
    } else if($zone === "myAlley" || $zone === "theirAlley") {
        $field = &GetAlley(($zone === "myAlley") ? $player : 3 - $player);
    } else {
        return;
    }

    if($index >= 0 && $index < count($field) && !$field[$index]->removed) {
        $field[$index]->Status = 1; // 1 = tapped/exhausted
    }
}

function WakeEntity($player, $mzID) {
    $mzParts = explode("-", $mzID);
    $zone = $mzParts[0];
    $index = intval($mzParts[1] ?? -1);

    if($zone === "myGarden" || $zone === "theirGarden") {
        $field = &GetGarden(($zone === "myGarden") ? $player : 3 - $player);
    } else if($zone === "myAlley" || $zone === "theirAlley") {
        $field = &GetAlley(($zone === "myAlley") ? $player : 3 - $player);
    } else {
        return;
    }

    if($index >= 0 && $index < count($field) && !$field[$index]->removed) {
        $field[$index]->Status = 2; // 2 = ready/untapped
    }
}

function HasCooldown($entity) {
    return isset($entity->TurnEffects) && in_array("COOLDOWN", $entity->TurnEffects);
}

function CanAttackWith($player, $mzID) {
    $mzParts = explode("-", $mzID);
    $zone = $mzParts[0];
    $index = intval($mzParts[1] ?? -1);

    // Can only attack from Garden (front row)
    if($zone !== "myGarden") return false;

    $garden = &GetGarden($player);
    if($index < 0 || $index >= count($garden) || $garden[$index]->removed) return false;

    $entity = &$garden[$index];

    // Cannot attack if tapped or has cooldown
    if($entity->Status == 1) return false; // Tapped
    if(HasCooldown($entity)) return false;

    return true;
}

function ResetEntityDamage($player, $zone) {
    if($zone === "myGarden" || $zone === "myAlley") {
        $field = ($zone === "myGarden") ? GetGarden($player) : GetAlley($player);
    } else {
        return;
    }

    foreach($field as &$entity) {
        if(!$entity->removed && isset($entity->Damage)) {
            $entity->Damage = 0; // Reset entity damage at end of turn
        }
    }
}

function WakeAllCards($player) {
    $garden = &GetGarden($player);
    $alley = &GetAlley($player);
    $leader = &GetLeader($player);

    foreach($garden as &$entity) {
        if(!$entity->removed) $entity->Status = 2; // Ready all entities
    }

    foreach($alley as &$entity) {
        if(!$entity->removed) $entity->Status = 2; // Ready all entities
    }

    foreach($leader as &$ldr) {
        if(!$ldr->removed) $ldr->Status = 2; // Ready leader
    }
}

function GainIKZ($player, $amount) {
    $ikz = &GetIKZ($player);
    $ikz = min(10, $ikz + $amount); // IKZ capped at 10
}

function SelectionMetadata($obj) {
    $currentPhase = GetCurrentPhase();
    $turnPlayer = &GetTurnPlayer();

    // Azuki selections are only surfaced during the turn player's main phase.
    if($currentPhase !== "MAIN") {
        return json_encode(['highlight' => false]);
    }

    // Suppress baseline highlights while either player has queued decisions.
    $myQueue = &GetDecisionQueue($turnPlayer);
    $theirQueue = &GetDecisionQueue($turnPlayer == 1 ? 2 : 1);
    if(count($myQueue) > 0 || count($theirQueue) > 0) {
        return json_encode(['highlight' => false]);
    }

    // Hand/temp-zone highlights are only for the active player's own cards.
    $owner = isset($obj->Controller) ? intval($obj->Controller) : (isset($obj->PlayerID) ? intval($obj->PlayerID) : null);
    if($owner === null || $owner !== intval($turnPlayer)) {
        return json_encode(['highlight' => false]);
    }

    return json_encode(['color' => 'rgba(0, 255, 0, 0.95)']);
}

function CardType($cardID) {
    if(!is_string($cardID)) return '';
    if(strpos($cardID, '_L_L_') !== false) return 'LEADER';
    if(strpos($cardID, '_G_G_') !== false) return 'GATE';
    return 'CARD';
}

function CardHealth($cardID) {
    return CardType($cardID) === 'LEADER' ? 20 : 0;
}

function CardPower($cardID) {
    return 0;
}

function CardElement($cardID) {
    if(!is_string($cardID) || $cardID === '') return '';
    $parts = explode('_', $cardID);
    if(count($parts) < 3) return '';
    $element = $parts[count($parts) - 3] ?? '';
    return ($element === 'die' || $element === 'Die') ? '' : $element;
}

function CardSubtypes($cardID) {
    return '';
}

function CardClasses($cardID) {
    return '';
}

function ObjectCurrentPowerDisplay($obj) {
    return 0;
}

function ObjectCurrentHPDisplay($obj) {
    $baseHP = 0;
    if(isset($obj->CardID)) {
        $baseHP = CardHealth($obj->CardID);
    }
    $damage = isset($obj->Damage) ? intval($obj->Damage) : 0;
    return max(0, $baseHP - $damage);
}

function FieldSelectionMetadata($obj) {
    $currentPhase = GetCurrentPhase();
    if($currentPhase !== 'MAIN') {
        return json_encode(['highlight' => false]);
    }

    $turnPlayer = &GetTurnPlayer();
    $myQueue = &GetDecisionQueue($turnPlayer);
    $theirQueue = &GetDecisionQueue($turnPlayer == 1 ? 2 : 1);
    if(count($myQueue) > 0 || count($theirQueue) > 0) {
        return json_encode(['highlight' => false]);
    }

    $owner = isset($obj->Controller) ? intval($obj->Controller) : (isset($obj->PlayerID) ? intval($obj->PlayerID) : null);
    if($owner === null || $owner !== intval($turnPlayer)) {
        return json_encode(['highlight' => false]);
    }

    return json_encode(['color' => 'rgba(0, 255, 0, 0.95)']);
}

function CombatTargetIndicator($obj) {
    return '';
}

function CardCurrentEffects($obj) {
    if(!isset($obj->TurnEffects) || !is_array($obj->TurnEffects)) return '';
    return implode(',', array_values($obj->TurnEffects));
}

function CardDisplayEffects($obj) {
    return CardCurrentEffects($obj);
}

function CardHasAbility($obj) {
    return 0;
}

function CanAttack($player, $mzID, $targetMZ) {
    return false;
}

function DoPlayCard($player, $mzCard, $ignoreCost = false) {
    $sourceObject = &GetZoneObject($mzCard);
    if($sourceObject === null || (isset($sourceObject->removed) && $sourceObject->removed)) {
        return '';
    }

    $zoneName = isset($sourceObject->Location) ? $sourceObject->Location : '';
    if($zoneName !== 'Hand') {
        return '';
    }

    MZMove($player, $mzCard, 'myDiscard');
    DecisionQueueController::CleanupRemovedCards();
    return 'PLAY';
}

function DoAttack($player, $mzCard, $targetMZ) {
    return '';
}

function DoActivatedAbility($player, $mzCard, $abilityIndex = 0) {
    return '';
}

function DoUseGate($player, $gateMZ, $entityMZ) {
    $gateObj = &GetZoneObject($gateMZ);
    if($gateObj === null || (isset($gateObj->removed) && $gateObj->removed)) {
        return '';
    }

    if(!CanUseGate($player)) {
        return '';
    }

    if(!isset($gateObj->Status)) {
        $gateObj->Status = 2;
    }
    $gateObj->Status = 1;
    if(!isset($gateObj->TurnEffects) || !is_array($gateObj->TurnEffects)) {
        $gateObj->TurnEffects = [];
    }
    if(!in_array('GATE_USED_THIS_TURN', $gateObj->TurnEffects)) {
        $gateObj->TurnEffects[] = 'GATE_USED_THIS_TURN';
    }

    if(is_string($entityMZ) && $entityMZ !== '') {
        $entityObj = &GetZoneObject($entityMZ);
        if($entityObj !== null && !(isset($entityObj->removed) && $entityObj->removed)) {
            if(isset($entityObj->Location) && $entityObj->Location === 'Alley') {
                MZMove($player, $entityMZ, 'myGarden');
            }
        }
    }

    return 'GATE';
}

function CanActivateAbility($player, $mzID, $abilityIndex) {
    return false;
}

function ActionMap($actionCard) {
    global $playerID;

    $turnPlayer = &GetTurnPlayer();
    $currentPhase = GetCurrentPhase();

    if(!is_string($actionCard) || $actionCard === '') {
        return '';
    }

    $cardArr = explode('-', $actionCard);
    $cardZone = $cardArr[0] ?? '';

    // Ignore FSM clicks while decisions are pending; the UI can surface them again after the queue clears.
    $dqController = new DecisionQueueController();
    if(!$dqController->AllQueuesEmpty()) {
        return '';
    }

    if($cardZone === 'myHand' && $currentPhase === 'MAIN' && intval($playerID) === intval($turnPlayer)) {
        if(function_exists('PlayCard')) {
            PlayCard($playerID, $actionCard);
            return 'PLAY';
        }
    }

    return '';
}

// --- Phase Handlers ---

function OnStartOfTurn($player) {
    global $gCurrentPhase;

    // 1. Untap all cards
    WakeAllCards($player);

    // 2. Gain 1 IKZ (max 10)
    GainIKZ($player, 1);

    // 3. Draw 1 card (except player 1 on turn 1)
    $turnNumber = GetTurnNumber();
    if(!($player === 1 && $turnNumber === 1)) {
        // Queue draw decision
        DecisionQueueController::AddDecision($player, "CUSTOM", "DRAW|1", 1);
    }

    // 4. Resolve SOT effects (to be queued by card abilities)
}

function OnEndOfTurn($player) {
    // 1. Reset entity damage
    ResetEntityDamage($player, "myGarden");
    ResetEntityDamage($player, "myAlley");

    // 2. Expire turn effects
    ExpireTurnEffects($player);

    // 3. Tap Gate if it was used
    $gateZone = &GetGate($player);
    if(!empty($gateZone)) {
        $gate = &$gateZone[0];
        // Gate usage is tracked via TurnEffects, which are cleared at EOT
        if(isset($gate->TurnEffects) && in_array("GATE_USED_THIS_TURN", $gate->TurnEffects)) {
            // TurnEffects will be cleared, gate can be used again next turn
        }
    }
}

function ExpireTurnEffects($player) {
    $garden = &GetGarden($player);
    $alley = &GetAlley($player);
    $leader = &GetLeader($player);
    $gate = &GetGate($player);

    foreach($garden as &$entity) {
        if(!$entity->removed && isset($entity->TurnEffects)) {
            $entity->TurnEffects = []; // Clear turn effects
        }
    }

    foreach($alley as &$entity) {
        if(!$entity->removed && isset($entity->TurnEffects)) {
            $entity->TurnEffects = []; // Clear turn effects
        }
    }

    foreach($leader as &$ldr) {
        if(!$ldr->removed && isset($ldr->TurnEffects)) {
            $ldr->TurnEffects = []; // Clear turn effects
        }
    }

    foreach($gate as &$g) {
        if(!$g->removed && isset($g->TurnEffects)) {
            $g->TurnEffects = []; // Clear turn effects
        }
    }
}

// --- DQ Handlers ---
$customDQHandlers["DRAW"] = function($player, $params, $lastDecision) {
    $amount = isset($params[0]) ? intval($params[0]) : 1;
    $deck = &GetDeck($player);
    $hand = &GetHand($player);

    for($i = 0; $i < $amount; ++$i) {
        if(empty($deck)) break;
        $card = array_shift($deck);
        array_push($hand, $card);
    }
};

$customDQHandlers["PORTAL_FROM_ALLEY"] = function($player, $params, $lastDecision) {
    // Move entity from Alley to Garden via Gate
    $entityMZ = isset($params[0]) ? $params[0] : "";
    UseGate($player, $entityMZ);
};

?>

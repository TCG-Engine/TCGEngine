<?php

$debugMode = true;
$customDQHandlers = [];

// --- Helper Functions ---

function NormalizeFieldOwnership($obj, $player) {
    if(!is_object($obj)) return;

    if(!isset($obj->Owner) || intval($obj->Owner) <= 0) {
        $obj->Owner = intval($player);
    }

    if(!isset($obj->Controller) || intval($obj->Controller) <= 0) {
        $obj->Controller = intval($player);
    }
}

function GardenAfterAdd($player, $CardID, $Status, $Owner, $Damage, $Controller, $TurnEffects, $Counters, $Subcards) {
    $garden = &GetGarden($player);
    if(empty($garden)) return;
    $idx = count($garden) - 1;
    if($idx < 0 || !isset($garden[$idx])) return;
    NormalizeFieldOwnership($garden[$idx], $player);
}

function AlleyAfterAdd($player, $CardID, $Status, $Owner, $Damage, $Controller, $TurnEffects, $Counters, $Subcards) {
    $alley = &GetAlley($player);
    if(empty($alley)) return;
    $idx = count($alley) - 1;
    if($idx < 0 || !isset($alley[$idx])) return;
    NormalizeFieldOwnership($alley[$idx], $player);
}

function GateAfterAdd($player, $CardID, $Status, $Owner, $Controller, $TurnEffects, $Counters) {
    $gate = &GetGate($player);
    if(empty($gate)) return;
    $idx = count($gate) - 1;
    if($idx < 0 || !isset($gate[$idx])) return;
    NormalizeFieldOwnership($gate[$idx], $player);
}

// CardAttack(), CardHealth(), CardElement(), CardSubtypes() are provided by GeneratedCardDictionaries.php

function CardCost($cardID) {
    $cost = CardIkzCost($cardID);
    return $cost !== null && $cost >= 0 ? intval($cost) : 0;
}

function FindLeaderIndexInGarden($player) {
    $garden = &GetGarden($player);
    for($i = 0; $i < count($garden); ++$i) {
        if(isset($garden[$i]->removed) && $garden[$i]->removed) continue;
        if(CardType($garden[$i]->CardID ?? '') === 'LEADER') return $i;
    }
    return -1;
}

function LeaderMaxHealth($player) {
    $garden = &GetGarden($player);
    $leaderIndex = FindLeaderIndexInGarden($player);
    if($leaderIndex >= 0 && $leaderIndex < count($garden)) {
        $h = CardHealth($garden[$leaderIndex]->CardID ?? '');
        if($h === null || $h < 0) $h = 20; // default for leaders without health data in the API
        return max(1, intval($h));
    }
    return 20;
}

function LeaderAttack($player) {
    $garden = &GetGarden($player);
    $leaderIndex = FindLeaderIndexInGarden($player);
    if($leaderIndex >= 0 && $leaderIndex < count($garden)) {
        return max(0, intval(CardAttack($garden[$leaderIndex]->CardID ?? '')));
    }
    return 0;
}

function CardHasKeyword($cardID, $keyword) {
    $abilities = CardAbilities($cardID);
    if(!is_array($abilities)) return false;
    foreach($abilities as $ability) {
        if(is_string($ability) && strcasecmp($ability, $keyword) === 0) return true;
    }
    return false;
}

function CountActiveEntities($zone, $ignoreLeaders = true) {
    if(!is_array($zone)) return 0;
    $count = 0;
    for($i = 0; $i < count($zone); ++$i) {
        if(isset($zone[$i]->removed) && $zone[$i]->removed) continue;
        $cardID = $zone[$i]->CardID ?? '';
        if($ignoreLeaders && CardType($cardID) === 'LEADER') continue;
        ++$count;
    }
    return $count;
}

function CanPayIKZCost($player, $cost) {
    $cost = max(0, intval($cost));
    if($cost === 0) return true;
    
    $ikzArea = GetIKZArea($player);
    $ikzToken = intval(GetIKZToken($player));
    
    // Count untapped IKZ in the area
    $availableIKZ = 0;
    if(is_array($ikzArea)) {
        foreach($ikzArea as $ikz) {
            if(!isset($ikz->removed) || !$ikz->removed) {
                // IKZ is untapped (Status=2) or tapped (Status=1), both count as available
                // (A tapped IKZ could have been tapped earlier this turn)
                if(!isset($ikz->Status) || $ikz->Status == 2) {
                    $availableIKZ++;
                }
            }
        }
    }
    
    return ($availableIKZ + $ikzToken) >= $cost;
}

function PayIKZCost($player, $cost) {
    $cost = max(0, intval($cost));
    if($cost === 0) return true;
    if(!CanPayIKZCost($player, $cost)) return false;

    $ikzArea = &GetIKZArea($player);
    $ikzToken = &GetIKZToken($player);

    $remaining = $cost;
    
    // First, tap untapped IKZ in the area
    if(is_array($ikzArea)) {
        foreach($ikzArea as &$ikz) {
            if($remaining <= 0) break;
            if(isset($ikz->removed) && $ikz->removed) continue;
            if(!isset($ikz->Status)) $ikz->Status = 2;
            
            // Only tap if currently untapped
            if($ikz->Status == 2) {
                $ikz->Status = 1; // Tap it
                $remaining--;
            }
        }
    }

    // If still need to pay, use the token
    if($remaining > 0) {
        $token = intval($ikzToken);
        $fromToken = min($token, $remaining);
        $ikzToken = max(0, $token - $fromToken);
        $remaining -= $fromToken;
    }

    return $remaining <= 0;
}

function CountAvailableIKZ($player) {
    $ikzArea = GetIKZArea($player);
    $count = 0;
    if(is_array($ikzArea)) {
        foreach($ikzArea as $ikz) {
            if((!isset($ikz->removed) || !$ikz->removed) && (!isset($ikz->Status) || $ikz->Status == 2)) {
                $count++;
            }
        }
    }
    return $count + intval(GetIKZToken($player));
}

function FindReplaceableIndex($zone) {
    if(!is_array($zone)) return -1;
    for($i = 0; $i < count($zone); ++$i) {
        if(isset($zone[$i]->removed) && $zone[$i]->removed) continue;
        if(CardType($zone[$i]->CardID ?? '') === 'LEADER') continue;
        // Prefer replacing non-Godmode entities, but allow Godmode as a fallback.
        if(!CardHasKeyword($zone[$i]->CardID ?? '', 'Godmode')) return $i;
    }
    for($i = 0; $i < count($zone); ++$i) {
        if(isset($zone[$i]->removed) && $zone[$i]->removed) continue;
        if(CardType($zone[$i]->CardID ?? '') === 'LEADER') continue;
        return $i;
    }
    return -1;
}

function ResolveEntityPlayFromHand($player, $mzCard, $destination) {
    if($destination !== 'myGarden' && $destination !== 'myAlley') {
        return;
    }

    if($destination === 'myGarden') {
        $garden = &GetGarden($player);
        if(CountActiveEntities($garden, true) >= 5) {
            $replaceIndex = FindReplaceableIndex($garden);
            if($replaceIndex >= 0) {
                MZMove($player, 'myGarden-' . $replaceIndex, 'myDiscard');
                DecisionQueueController::CleanupRemovedCards();
            }
        }
    }
    else {
        $alley = &GetAlley($player);
        if(CountActiveEntities($alley, true) >= 5) {
            $replaceIndex = FindReplaceableIndex($alley);
            if($replaceIndex >= 0) {
                MZMove($player, 'myAlley-' . $replaceIndex, 'myDiscard');
                DecisionQueueController::CleanupRemovedCards();
            }
        }
    }

    MZMove($player, $mzCard, $destination);
    DecisionQueueController::CleanupRemovedCards();

    $placedZone = ($destination === 'myGarden') ? GetGarden($player) : GetAlley($player);
    $placedIndex = count($placedZone) - 1;
    if($placedIndex < 0) {
        return;
    }

    $newMZ = $destination . '-' . $placedIndex;
    $newObj = &GetZoneObject($newMZ);
    if($newObj === null || (isset($newObj->removed) && $newObj->removed)) {
        return;
    }

    NormalizeFieldOwnership($newObj, $player);

    if($destination === 'myGarden') {
        if(!isset($newObj->TurnEffects) || !is_array($newObj->TurnEffects)) {
            $newObj->TurnEffects = [];
        }
        if(!in_array('COOLDOWN', $newObj->TurnEffects)) {
            $newObj->TurnEffects[] = 'COOLDOWN';
        }
    }

    Enter($player, $newMZ);
    OnPlay($player, $newMZ);
}

function DealDamageToLeader($player, $amount) {
    if($amount <= 0) return;
    $leaderHealth = &GetLeaderHealth($player);
    $leaderHealth = max(0, intval($leaderHealth) - intval($amount));
}

function HealLeader($player, $amount) {
    if($amount <= 0) return;
    $leaderHealth = &GetLeaderHealth($player);
    $maxHealth = LeaderMaxHealth($player);
    $leaderHealth = min($maxHealth, intval($leaderHealth) + intval($amount));
}

function CanUseGate($player, $gateMZ = null, $entityMZ = null) {
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

    // Leaders do not attack unless weapon logic is implemented.
    if(CardType($entity->CardID ?? '') === 'LEADER') return false;

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

    foreach($garden as &$entity) {
        if(!$entity->removed) $entity->Status = 2; // Ready all entities
    }

    foreach($alley as &$entity) {
        if(!$entity->removed) $entity->Status = 2; // Ready all entities
    }
}

function GainIKZ($player, $amount) {
    $ikzArea = &GetIKZArea($player);
    $ikzPile = &GetIKZPile($player);
    
    if(!is_array($ikzArea)) $ikzArea = [];
    if(!is_array($ikzPile)) $ikzPile = [];
    
    // Count current IKZ in area
    $currentCount = 0;
    foreach($ikzArea as $ikz) {
        if(!isset($ikz->removed) || !$ikz->removed) {
            $currentCount++;
        }
    }
    
    // Don't exceed maximum of 10 in the area
    $canGain = max(0, 10 - $currentCount);
    $toAdd = min($amount, $canGain);
    $toOverflow = $amount - $toAdd;
    
    // Add IKZ to area (untapped, Status=2)
    for($i = 0; $i < $toAdd; ++$i) {
        $ikz = new IKZArea("IKZ-001_IKZ!_IKZ_die 2");
        $ikzArea[] = $ikz;
    }
    
    // Overflow goes to pile (also untapped)
    for($i = 0; $i < $toOverflow; ++$i) {
        $ikz = new IKZPile("IKZ-001_IKZ!_IKZ_die 2");
        $ikzPile[] = $ikz;
    }
}

function UntapAllIKZ($player) {
    $ikzArea = &GetIKZArea($player);
    $ikzPile = &GetIKZPile($player);
    
    if(is_array($ikzArea)) {
        foreach($ikzArea as &$ikz) {
            if(!isset($ikz->removed) || !$ikz->removed) {
                $ikz->Status = 2; // Untap
            }
        }
    }
    
    if(is_array($ikzPile)) {
        foreach($ikzPile as &$ikz) {
            if(!isset($ikz->removed) || !$ikz->removed) {
                $ikz->Status = 2; // Untap
            }
        }
    }
}

function PromoteIKZFromPile($player) {
    $ikzArea = &GetIKZArea($player);
    $ikzPile = &GetIKZPile($player);
    
    if(!is_array($ikzArea)) $ikzArea = [];
    if(!is_array($ikzPile)) $ikzPile = [];
    
    // Count current IKZ in area
    $currentCount = 0;
    foreach($ikzArea as $ikz) {
        if(!isset($ikz->removed) || !$ikz->removed) {
            $currentCount++;
        }
    }
    
    // Move IKZ from pile to area up to the maximum of 10
    $canAdd = max(0, 10 - $currentCount);
    $moved = 0;
    
    foreach($ikzPile as &$ikz) {
        if($moved >= $canAdd) break;
        if(isset($ikz->removed) && $ikz->removed) continue;
        
        // Convert to IKZArea and add to area
        $newIKZ = new IKZArea($ikz->Status . "");
        $ikzArea[] = $newIKZ;
        
        // Mark as removed from pile
        $ikz->removed = true;
        $moved++;
    }
}

function ResolveObjectOwner($obj) {
    if(!is_object($obj)) return null;

    if(isset($obj->Controller)) {
        $controller = intval($obj->Controller);
        if($controller > 0) return $controller;
    }

    if(isset($obj->PlayerID)) {
        $playerID = intval($obj->PlayerID);
        if($playerID > 0) return $playerID;
    }

    if(isset($obj->Owner)) {
        $owner = intval($obj->Owner);
        if($owner > 0) return $owner;
    }

    return null;
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
    $owner = ResolveObjectOwner($obj);
    if($owner === null || $owner !== intval($turnPlayer)) {
        return json_encode(['highlight' => false]);
    }

    return json_encode(['color' => 'rgba(0, 255, 0, 0.95)']);
}

function CardType($cardID) {
    $category = CardCategory($cardID);
    if(is_string($category) && $category !== '') {
        switch(strtoupper(trim($category))) {
            case 'ENTITY': return 'ENTITY';
            case 'SPELL': return 'SPELL';
            case 'WEAPON': return 'WEAPON';
            case 'LEADER': return 'LEADER';
            case 'GATE': return 'GATE';
            case 'IKZ': return 'IKZ';
        }
    }
    // Fallback: infer from card ID naming convention
    if(!is_string($cardID)) return '';
    if(strpos($cardID, '_L_L_') !== false) return 'LEADER';
    if(strpos($cardID, '_G_G_') !== false) return 'GATE';
    return 'ENTITY';
}

// CardHealth($cardID) is provided by GeneratedCardDictionaries.php
// CardElement($cardID) is provided by GeneratedCardDictionaries.php
// CardSubtypes($cardID) is provided by GeneratedCardDictionaries.php

function CardPower($cardID) {
    return CardAttack($cardID);
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

    $owner = ResolveObjectOwner($obj);
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
    if(!is_object($obj) || (isset($obj->removed) && $obj->removed)) return 0;

    if(GetCurrentPhase() !== 'MAIN') return 0;

    $turnPlayer = &GetTurnPlayer();
    $owner = ResolveObjectOwner($obj);
    if($owner === null || intval($owner) !== intval($turnPlayer)) return 0;

    $myQueue = &GetDecisionQueue($turnPlayer);
    $theirQueue = &GetDecisionQueue($turnPlayer == 1 ? 2 : 1);
    if(count($myQueue) > 0 || count($theirQueue) > 0) return 0;

    $location = isset($obj->Location) ? $obj->Location : '';
    $mzIndex = intval($obj->mzIndex ?? -1);

    // Garden cards surface Activate when they can currently declare an attack.
    if($location === 'Garden' && $mzIndex >= 0) {
        $mzID = 'myGarden-' . $mzIndex;
        if(CanAttackWith($turnPlayer, $mzID)) return 1;
    }

    // Gate surfaces Activate when it is usable and an alley unit exists to portal.
    if($location === 'Gate' && $mzIndex >= 0 && CanUseGate($turnPlayer, 'myGate-' . $mzIndex, '')) {
        $alley = &GetAlley($turnPlayer);
        for($i = 0; $i < count($alley); ++$i) {
            if(isset($alley[$i]->removed) && $alley[$i]->removed) continue;
            return 1;
        }
    }

    return 0;
}

function IsAttackTargetLegal($player, $targetMZ) {
    if(!is_string($targetMZ) || $targetMZ === '') return false;

    $opponent = ($player == 1) ? 2 : 1;
    $parts = explode('-', $targetMZ);
    $zone = $parts[0] ?? '';
    $index = intval($parts[1] ?? -1);

    if($zone !== 'theirGarden') return false;

    $garden = &GetGarden($opponent);
    if($index < 0 || $index >= count($garden)) return false;
    if(isset($garden[$index]->removed) && $garden[$index]->removed) return false;

    $cardID = $garden[$index]->CardID ?? '';
    if(CardType($cardID) === 'LEADER') {
        // Leader is always targetable while alive
        return intval(GetLeaderHealth($opponent)) > 0;
    }

    // Garden entities are attackable only while tapped.
    return intval($garden[$index]->Status ?? 2) == 1;
}

function CanAttack($player, $mzID, $targetMZ) {
    if(intval(GetTurnPlayer()) !== intval($player)) return false;
    if(GetCurrentPhase() !== 'MAIN') return false;
    if(!CanAttackWith($player, $mzID)) return false;
    return IsAttackTargetLegal($player, $targetMZ);
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

    $cardID = $sourceObject->CardID ?? '';
    if($cardID === '') {
        return '';
    }

    $cardType = CardType($cardID);
    $cardCost = CardCost($cardID);
    if(!$ignoreCost) {
        if(!CanPayIKZCost($player, $cardCost)) {
            SetFlashMessage('Not enough IKZ to play this card.');
            return '';
        }
        if(!PayIKZCost($player, $cardCost)) {
            return '';
        }
    }

    if($cardType === 'ENTITY') {
        DecisionQueueController::AddDecision($player, 'CHOOSEZONE', 'myGarden&myAlley', 1, 'Choose_lane_for_entity');
        DecisionQueueController::AddDecision($player, 'CUSTOM', 'PLAY_ENTITY_DEST|' . $mzCard, 1);
        return 'PLAY';
    } else if($cardType === 'SPELL') {
        $stack = &GetEffectStack();
        $beforeCount = count($stack);
        MZMove($player, $mzCard, 'EffectStack');
        DecisionQueueController::CleanupRemovedCards();

        $stack = &GetEffectStack();
        $stackIndex = count($stack) - 1;
        if($stackIndex >= $beforeCount) {
            $stackMZ = 'EffectStack-' . $stackIndex;
            OnPlay($player, $stackMZ);
            MZMove($player, $stackMZ, 'myDiscard');
        }
    } else {
        // Weapon and unsupported card types: pay cost, then send to discard for now.
        MZMove($player, $mzCard, 'myDiscard');
    }

    DecisionQueueController::CleanupRemovedCards();
    return 'PLAY';
}

function DoDrawCard($player, $amount) {
    $amount = max(0, intval($amount));
    $deck = &GetDeck($player);
    $hand = &GetHand($player);

    for($i = 0; $i < $amount; ++$i) {
        if(empty($deck)) break;
        $card = array_shift($deck);
        array_push($hand, $card);
    }

    return 'DRAW';
}

function OnEnter($player, $mzID) {
    global $customDQHandlers;
    if(isset($customDQHandlers['ON_ENTER']) && is_callable($customDQHandlers['ON_ENTER'])) {
        $obj = GetZoneObject($mzID);
        $cardID = ($obj !== null && isset($obj->CardID)) ? $obj->CardID : '';
        $customDQHandlers['ON_ENTER']($player, [$mzID, $cardID], null);
    }
    return 'ENTER';
}

function OnCardActivated($player, $mzID) {
    global $customDQHandlers;
    if(isset($customDQHandlers['ON_CARD_ACTIVATED']) && is_callable($customDQHandlers['ON_CARD_ACTIVATED'])) {
        $obj = GetZoneObject($mzID);
        $cardID = ($obj !== null && isset($obj->CardID)) ? $obj->CardID : '';
        $customDQHandlers['ON_CARD_ACTIVATED']($player, [$mzID, $cardID], null);
    }
    return 'CARD_ACTIVATED';
}

function OnPlayCard($player, $mzID) {
    global $onPlayAbilities;
    if(!isset($onPlayAbilities) || !is_array($onPlayAbilities)) {
        return 'ON_PLAY';
    }

    $obj = GetZoneObject($mzID);
    if($obj === null || (isset($obj->removed) && $obj->removed)) {
        return 'ON_PLAY';
    }

    $cardID = $obj->CardID ?? '';
    if($cardID === '') {
        return 'ON_PLAY';
    }

    $normalizedCardID = $cardID;
    $abilityCount = 0;
    if(function_exists('CardOnPlayCount')) {
        $abilityCount = max(
            intval(CardOnPlayCount($cardID)),
            intval(CardOnPlayCount($normalizedCardID))
        );
    }

    if($abilityCount <= 0) {
        if(isset($onPlayAbilities[$cardID . ':0'])) {
            $onPlayAbilities[$cardID . ':0']($player);
        } else if(isset($onPlayAbilities[$normalizedCardID . ':0'])) {
            $onPlayAbilities[$normalizedCardID . ':0']($player);
        }
        return 'ON_PLAY';
    }

    for($i = 0; $i < $abilityCount; ++$i) {
        $fullKey = $cardID . ':' . $i;
        $normalizedKey = $normalizedCardID . ':' . $i;
        if(isset($onPlayAbilities[$fullKey])) {
            $onPlayAbilities[$fullKey]($player);
        } else if(isset($onPlayAbilities[$normalizedKey])) {
            $onPlayAbilities[$normalizedKey]($player);
        }
    }

    return 'ON_PLAY';
}

function DoAttack($player, $mzCard, $targetMZ) {
    if(!CanAttack($player, $mzCard, $targetMZ)) return '';

    $opponent = ($player == 1) ? 2 : 1;
    $attackerParts = explode('-', $mzCard);
    $attackerZone = $attackerParts[0] ?? '';
    $attackerIndex = intval($attackerParts[1] ?? -1);

    if($attackerZone !== 'myGarden') return '';
    $myGarden = &GetGarden($player);
    if($attackerIndex < 0 || $attackerIndex >= count($myGarden)) return '';
    if(isset($myGarden[$attackerIndex]->removed) && $myGarden[$attackerIndex]->removed) return '';

    $attackerObj = &$myGarden[$attackerIndex];
    $attackerAttack = max(0, intval(CardAttack($attackerObj->CardID ?? '')));

    $defenderAttack = 0;
    $defenderHealth = 0;
    $targetIsLeader = false;
    $targetParts = explode('-', $targetMZ);
    $targetZone = $targetParts[0] ?? '';
    $targetIndex = intval($targetParts[1] ?? -1);

    if($targetZone !== 'theirGarden') return '';
    $theirGarden = &GetGarden($opponent);
    if($targetIndex < 0 || $targetIndex >= count($theirGarden)) return '';
    if(isset($theirGarden[$targetIndex]->removed) && $theirGarden[$targetIndex]->removed) return '';
    $targetCardID = $theirGarden[$targetIndex]->CardID ?? '';

    if(CardType($targetCardID) === 'LEADER') {
        $targetIsLeader = true;
        $defenderAttack = max(0, LeaderAttack($opponent));
        $defenderHealth = max(0, intval(GetLeaderHealth($opponent)));
    } else {
        $targetObj = &$theirGarden[$targetIndex];
        $defenderAttack = max(0, intval(CardAttack($targetObj->CardID ?? '')));
        $defenderHealth = max(0, intval(CardHealth($targetObj->CardID ?? '')));
    }

    ExhaustEntity($player, $mzCard);

    // Simultaneous combat damage
    if($attackerAttack > 0) {
        if($targetIsLeader) {
            DealDamageToLeader($opponent, $attackerAttack);
        } else {
            $theirGarden = &GetGarden($opponent);
            if(isset($theirGarden[$targetIndex]) && !(isset($theirGarden[$targetIndex]->removed) && $theirGarden[$targetIndex]->removed)) {
                $theirGarden[$targetIndex]->Damage = intval($theirGarden[$targetIndex]->Damage ?? 0) + $attackerAttack;
            }
        }
    }

    if($defenderAttack > 0) {
        $myGarden = &GetGarden($player);
        if(isset($myGarden[$attackerIndex]) && !(isset($myGarden[$attackerIndex]->removed) && $myGarden[$attackerIndex]->removed)) {
            $myGarden[$attackerIndex]->Damage = intval($myGarden[$attackerIndex]->Damage ?? 0) + $defenderAttack;
        }
    }

    // Destroy non-leader entities that reached 0 health after simultaneous damage.
    if(!$targetIsLeader) {
        $theirGarden = &GetGarden($opponent);
        if(isset($theirGarden[$targetIndex]) && !(isset($theirGarden[$targetIndex]->removed) && $theirGarden[$targetIndex]->removed)) {
            $targetDamage = intval($theirGarden[$targetIndex]->Damage ?? 0);
            if($defenderHealth > 0 && $targetDamage >= $defenderHealth) {
                MZMove($player, 'theirGarden-' . $targetIndex, 'theirDiscard');
            }
        }
    }

    $myGarden = &GetGarden($player);
    if(isset($myGarden[$attackerIndex]) && !(isset($myGarden[$attackerIndex]->removed) && $myGarden[$attackerIndex]->removed)) {
        $attackerHealth = max(0, intval(CardHealth($myGarden[$attackerIndex]->CardID ?? '')));
        $attackerDamage = intval($myGarden[$attackerIndex]->Damage ?? 0);
        if($attackerHealth > 0 && CardType($myGarden[$attackerIndex]->CardID ?? '') !== 'LEADER' && $attackerDamage >= $attackerHealth) {
            MZMove($player, 'myGarden-' . $attackerIndex, 'myDiscard');
        }
    }

    DecisionQueueController::CleanupRemovedCards();
    return 'ATTACK';
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
        if($entityObj !== null && !(isset($entityObj->removed) && $entityObj->removed) && isset($entityObj->Location) && $entityObj->Location === 'Alley') {
            $garden = &GetGarden($player);
            if(CountActiveEntities($garden, true) >= 5) {
                $replaceIndex = FindReplaceableIndex($garden);
                if($replaceIndex >= 0) {
                    MZMove($player, 'myGarden-' . $replaceIndex, 'myDiscard');
                    DecisionQueueController::CleanupRemovedCards();
                }
            }

            MZMove($player, $entityMZ, 'myGarden');
            DecisionQueueController::CleanupRemovedCards();

            $garden = &GetGarden($player);
            $addedIndex = count($garden) - 1;
            if($addedIndex >= 0) {
                $addedObj = &$garden[$addedIndex];
                if($addedObj !== null && !(isset($addedObj->removed) && $addedObj->removed)) {
                    NormalizeFieldOwnership($addedObj, $player);
                    if(!isset($addedObj->TurnEffects) || !is_array($addedObj->TurnEffects)) {
                        $addedObj->TurnEffects = [];
                    }
                    if(!in_array('COOLDOWN', $addedObj->TurnEffects)) {
                        $addedObj->TurnEffects[] = 'COOLDOWN';
                    }
                }
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

    // Fallback: allow direct card click on Garden cards to initiate attack setup.
    if($cardZone === 'myGarden' && $currentPhase === 'MAIN' && intval($playerID) === intval($turnPlayer)) {
        if(function_exists('HandleAttackSetup')) {
            HandleAttackSetup($playerID, $actionCard);
            return 'ATTACK_SETUP';
        }
    }

    // Fallback: allow direct gate click to start portal flow.
    if($cardZone === 'myGate' && $currentPhase === 'MAIN' && intval($playerID) === intval($turnPlayer)) {
        if(function_exists('HandleGateUsage')) {
            HandleGateUsage($playerID);
            return 'GATE_SETUP';
        }
    }

    return '';
}

// --- Phase Handlers ---

function OnStartOfTurn($player) {
    global $gCurrentPhase;

    // 1. Untap all cards (field and IKZ)
    WakeAllCards($player);
    UntapAllIKZ($player);

    // 2. Promote IKZ from pile to area if there's room
    PromoteIKZFromPile($player);
    
    // 3. Gain 1 IKZ (up to a maximum of 10 in area)
    GainIKZ($player, 1);

    // 4. Draw 1 card (except player 1 on turn 1)
    $turnNumber = GetTurnNumber();
    if(!($player === 1 && $turnNumber === 1)) {
        // Resolve draw immediately so SOT can auto-advance into MAIN.
        DoDrawCard($player, 1);
    }

    // 5. Resolve SOT effects (to be queued by card abilities)
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
    $gateMZ = isset($params[0]) && $params[0] !== '' ? $params[0] : 'myGate-0';
    $entityMZ = isset($params[1]) && $params[1] !== '' ? $params[1] : $lastDecision;
    if(!is_string($entityMZ) || $entityMZ === '' || strtoupper($entityMZ) === 'PASS') return;
    UseGate($player, $gateMZ, $entityMZ);
};

$customDQHandlers["RESOLVE_ATTACK"] = function($player, $params, $lastDecision) {
    $attackerMZ = isset($params[0]) ? $params[0] : '';
    $chosenTarget = is_string($lastDecision) ? $lastDecision : '';
    if($attackerMZ === '' || $chosenTarget === '' || strtoupper($chosenTarget) === 'PASS') return;
    AttackWith($player, $attackerMZ, $chosenTarget);
};

$customDQHandlers["PLAY_ENTITY_DEST"] = function($player, $params, $lastDecision) {
    $mzCard = isset($params[0]) ? $params[0] : '';
    if(!is_string($mzCard) || $mzCard === '') return;

    $sourceObject = &GetZoneObject($mzCard);
    if($sourceObject === null || (isset($sourceObject->removed) && $sourceObject->removed)) return;
    if(($sourceObject->Location ?? '') !== 'Hand') return;

    $destination = is_string($lastDecision) ? $lastDecision : '';
    if($destination !== 'myGarden' && $destination !== 'myAlley') {
        return;
    }
    ResolveEntityPlayFromHand($player, $mzCard, $destination);
};

// --- Phase Handler Wrappers for TurnController ---
function StartOfTurnPhase() {
    $player = GetTurnPlayer();
    OnStartOfTurn($player);
}

function MainPhase() {
    // Main phase is player-driven; no auto actions needed here yet.
    // Could add auto-triggers or forced actions if needed in future.
}

function EndOfTurnPhase() {
    $player = GetTurnPlayer();
    OnEndOfTurn($player);
    // Switch turn player and increment turn number
    $turnPlayer = &GetTurnPlayer();
    $turnNumber = &GetTurnNumber();
    $turnPlayer = ($turnPlayer == 1) ? 2 : 1;
    $turnNumber++;
}

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

function IsFieldZoneName($zoneName) {
    return $zoneName === 'myGarden' || $zoneName === 'theirGarden' || $zoneName === 'myAlley' || $zoneName === 'theirAlley';
}

function ResolveOwnerFromPerspectiveZone($player, $zoneName) {
    if(!is_string($zoneName) || $zoneName === '') return intval($player);
    if(strpos($zoneName, 'their') === 0) return intval($player) === 1 ? 2 : 1;
    return intval($player);
}

function ParsePerspectiveMzID($perspectivePlayer, $mzID) {
    if(!is_string($mzID) || $mzID === '') return null;

    $parts = explode('-', $mzID);
    $zoneToken = $parts[0] ?? '';
    $index = intval($parts[1] ?? -1);
    if($zoneToken === '' || $index < 0) return null;

    $locationToken = $zoneToken;
    if(strpos($zoneToken, 'my') === 0) {
        $locationToken = substr($zoneToken, 2);
    } elseif(strpos($zoneToken, 'their') === 0) {
        $locationToken = substr($zoneToken, 5);
    }
    if(!is_string($locationToken) || $locationToken === '') return null;

    return [
        'owner' => ResolveOwnerFromPerspectiveZone($perspectivePlayer, $zoneToken),
        'location' => $locationToken,
        'index' => $index
    ];
}

function HasPendingAttackResponse() {
    $attackerMZ = DecisionQueueController::GetVariable('PendingAttackAttackerMZ');
    $targetMZ = DecisionQueueController::GetVariable('PendingAttackTargetMZ');
    return is_string($attackerMZ) && $attackerMZ !== '' && is_string($targetMZ) && $targetMZ !== '';
}

function GetPendingAttackAttackerPlayer() {
    $raw = DecisionQueueController::GetVariable('PendingAttackAttackerPlayer');
    $player = intval($raw);
    return ($player === 1 || $player === 2) ? $player : 0;
}

function GetPendingAttackResponderPlayer() {
    $attacker = GetPendingAttackAttackerPlayer();
    if($attacker !== 1 && $attacker !== 2) return 0;
    return $attacker === 1 ? 2 : 1;
}

function BeginAttackResponseWindow($attackerPlayer, $attackerMZ, $targetMZ) {
    if(!is_string($attackerMZ) || $attackerMZ === '' || !is_string($targetMZ) || $targetMZ === '') return false;
    DecisionQueueController::StoreVariable('PendingAttackAttackerPlayer', strval(intval($attackerPlayer)));
    DecisionQueueController::StoreVariable('PendingAttackAttackerMZ', $attackerMZ);
    DecisionQueueController::StoreVariable('PendingAttackTargetMZ', $targetMZ);
    return true;
}

function ClearAttackResponseWindow() {
    DecisionQueueController::StoreVariable('PendingAttackAttackerPlayer', '');
    DecisionQueueController::StoreVariable('PendingAttackAttackerMZ', '');
    DecisionQueueController::StoreVariable('PendingAttackTargetMZ', '');
}

function ResolveAttackAfterResponses($responderPlayer) {
    if(!HasPendingAttackResponse()) return false;

    $attackerPlayer = GetPendingAttackAttackerPlayer();
    $expectedResponder = GetPendingAttackResponderPlayer();
    if($attackerPlayer === 0 || intval($responderPlayer) !== $expectedResponder) return false;

    $attackerMZ = DecisionQueueController::GetVariable('PendingAttackAttackerMZ');
    $targetMZ = DecisionQueueController::GetVariable('PendingAttackTargetMZ');
    ClearAttackResponseWindow();

    if(!is_string($attackerMZ) || $attackerMZ === '' || !is_string($targetMZ) || $targetMZ === '') return false;
    AttackWith($attackerPlayer, $attackerMZ, $targetMZ);
    return true;
}

function GetMacroCardIDCandidates($cardID) {
    $candidates = [];
    if(!is_string($cardID) || $cardID === '') return $candidates;

    $candidates[] = $cardID;

    // Some generated macro keys omit only the trailing variant token (e.g. "..._die" -> "..._").
    $trimmedTrailingToken = preg_replace('/[^_]*$/', '', $cardID);
    if(is_string($trimmedTrailingToken) && $trimmedTrailingToken !== '' && !in_array($trimmedTrailingToken, $candidates, true)) {
        $candidates[] = $trimmedTrailingToken;
    }

    // Also accept keys without the common "_die" suffix.
    $trimmedDieSuffix = preg_replace('/_die$/i', '', $cardID);
    if(is_string($trimmedDieSuffix) && $trimmedDieSuffix !== '' && !in_array($trimmedDieSuffix, $candidates, true)) {
        $candidates[] = $trimmedDieSuffix;
    }

    return $candidates;
}

function GetAttachedWeaponIDs($obj) {
    if(!is_object($obj) || !isset($obj->Subcards) || !is_array($obj->Subcards)) return [];

    $weapons = [];
    foreach($obj->Subcards as $subcardID) {
        if(!is_string($subcardID) || $subcardID === '') continue;
        if(CardType($subcardID) !== 'WEAPON') continue;
        $weapons[] = $subcardID;
    }
    return $weapons;
}

function GetSubcardTurnEffects($obj, $subcardIndex) {
    if(!is_object($obj) || intval($subcardIndex) < 0) return [];
    if(!isset($obj->Counters) || !is_array($obj->Counters)) return [];

    $map = $obj->Counters['_subcardTurnEffects'] ?? null;
    if(!is_array($map)) return [];

    $key = strval(intval($subcardIndex));
    $effects = $map[$key] ?? null;
    return is_array($effects) ? $effects : [];
}

function AddSubcardTurnEffect(&$obj, $subcardIndex, $effectID) {
    if(!is_object($obj) || intval($subcardIndex) < 0 || !is_string($effectID) || $effectID === '') return;
    if(!isset($obj->Counters) || !is_array($obj->Counters)) {
        $obj->Counters = [];
    }
    if(!isset($obj->Counters['_subcardTurnEffects']) || !is_array($obj->Counters['_subcardTurnEffects'])) {
        $obj->Counters['_subcardTurnEffects'] = [];
    }

    $key = strval(intval($subcardIndex));
    if(!isset($obj->Counters['_subcardTurnEffects'][$key]) || !is_array($obj->Counters['_subcardTurnEffects'][$key])) {
        $obj->Counters['_subcardTurnEffects'][$key] = [];
    }

    $obj->Counters['_subcardTurnEffects'][$key][] = $effectID;
}

function CountWeaponSubcardTurnEffects($obj, $weaponCardID, $effectID) {
    if(!is_object($obj) || !isset($obj->Subcards) || !is_array($obj->Subcards)) return 0;

    $count = 0;
    for($i = 0; $i < count($obj->Subcards); ++$i) {
        $subcardID = $obj->Subcards[$i] ?? '';
        if(!is_string($subcardID) || $subcardID !== $weaponCardID) continue;
        $effects = GetSubcardTurnEffects($obj, $i);
        foreach($effects as $effect) {
            if($effect === $effectID) ++$count;
        }
    }
    return $count;
}

function AddSubcardTurnEffectByCardID(&$obj, $weaponCardID, $effectID) {
    if(!is_object($obj) || !isset($obj->Subcards) || !is_array($obj->Subcards)) return false;

    for($i = count($obj->Subcards) - 1; $i >= 0; --$i) {
        $subcardID = $obj->Subcards[$i] ?? '';
        if(!is_string($subcardID) || $subcardID !== $weaponCardID) continue;
        AddSubcardTurnEffect($obj, $i, $effectID);
        return true;
    }

    return false;
}

function ApplyBlackJadeDaggerOnPlayBonus($player) {
    $targetKey = 'P' . intval($player) . '_BlackJadeDaggerTargetMZ';
    $targetMZ = DecisionQueueController::GetVariable($targetKey);
    if(!is_string($targetMZ) || $targetMZ === '') return;

    $targetObj = &GetZoneObject($targetMZ);
    if($targetObj === null || (isset($targetObj->removed) && $targetObj->removed)) return;

    AddSubcardTurnEffectByCardID($targetObj, 'S1-STT01-013_Black-Jade-Dagger_W_C_die', 'BLACK_JADE_DAGGER_BONUS');
}

function HasEquippedWeapon($obj) {
    return !empty(GetAttachedWeaponIDs($obj));
}

function EquippedWeaponAttackBonus($obj) {
    $bonus = 0;
    $weaponIDs = GetAttachedWeaponIDs($obj);
    foreach($weaponIDs as $weaponID) {
        $bonus += max(0, intval(CardAttack($weaponID)));
    }

    // Black Jade Dagger can grant an additional +1 attack when its On Play cost is paid.
    $blackJadeDaggerID = 'S1-STT01-013_Black-Jade-Dagger_W_C_die';
    $blackJadeBoostEffect = 'BLACK_JADE_DAGGER_BONUS';
    $bonus += CountWeaponSubcardTurnEffects($obj, $blackJadeDaggerID, $blackJadeBoostEffect);

    return $bonus;
}

function DiscardEquippedWeaponsFromObject($owner, $obj) {
    if(!is_object($obj)) return;
    if(!isset($obj->Subcards) || !is_array($obj->Subcards)) return;

    $remaining = [];
    foreach($obj->Subcards as $subcardID) {
        if(!is_string($subcardID) || $subcardID === '') continue;
        if(CardType($subcardID) === 'WEAPON') {
            AddDiscard($owner, CardID:$subcardID);
            continue;
        }
        $remaining[] = $subcardID;
    }
    $obj->Subcards = $remaining;
}

function HandleFieldCardBeforeLeaving($player, $mzIndex, $toZone) {
    if(!is_string($mzIndex) || $mzIndex === '') return;
    if(!is_string($toZone) || $toZone === '') return;

    $parts = explode('-', $mzIndex);
    $sourceZone = $parts[0] ?? '';
    if(!IsFieldZoneName($sourceZone)) return;
    if(IsFieldZoneName($toZone)) return;

    $obj = &GetZoneObject($mzIndex);
    if($obj === null || (isset($obj->removed) && $obj->removed)) return;

    $owner = ResolveObjectOwner($obj);
    if($owner === null || intval($owner) <= 0) {
        $owner = ResolveOwnerFromPerspectiveZone($player, $sourceZone);
    }

    DiscardEquippedWeaponsFromObject(intval($owner), $obj);
}

function SafeMZMove($player, $mzIndex, $toZone) {
    HandleFieldCardBeforeLeaving($player, $mzIndex, $toZone);
    return MZMove($player, $mzIndex, $toZone);
}

function ResolveWeaponEquipTargets($player) {
    $targets = [];
    $garden = &GetGarden($player);
    for($i = 0; $i < count($garden); ++$i) {
        if(isset($garden[$i]->removed) && $garden[$i]->removed) continue;
        $targets[] = 'myGarden-' . $i;
    }
    return $targets;
}

function AttachWeaponCardIDToTarget($targetObj, $weaponCardID) {
    if(!is_object($targetObj) || !is_string($weaponCardID) || $weaponCardID === '') return;
    if(!isset($targetObj->Subcards) || !is_array($targetObj->Subcards)) {
        $targetObj->Subcards = [];
    }
    $targetObj->Subcards[] = $weaponCardID;
}

function ResolveWeaponPlayFromHand($player, $mzCard, $targetMZ) {
    $sourceObj = &GetZoneObject($mzCard);
    if($sourceObj === null || (isset($sourceObj->removed) && $sourceObj->removed)) return;
    if(($sourceObj->Location ?? '') !== 'Hand') return;

    $targetObj = &GetZoneObject($targetMZ);
    if($targetObj === null || (isset($targetObj->removed) && $targetObj->removed)) return;
    if(($targetObj->Location ?? '') !== 'Garden') return;

    $weaponCardID = $sourceObj->CardID ?? '';
    if($weaponCardID === '' || CardType($weaponCardID) !== 'WEAPON') return;

    $stack = &GetEffectStack();
    $beforeCount = count($stack);
    SafeMZMove($player, $mzCard, 'EffectStack');
    DecisionQueueController::CleanupRemovedCards();

    $stack = &GetEffectStack();
    $stackIndex = count($stack) - 1;
    if($stackIndex >= $beforeCount) {
        $stackMZ = 'EffectStack-' . $stackIndex;
        OnPlay($player, $stackMZ);

        $stackObj = &GetZoneObject($stackMZ);
        if($stackObj !== null && !(isset($stackObj->removed) && $stackObj->removed)) {
            $stackObj->Remove();
        }
        DecisionQueueController::CleanupRemovedCards();
    }

    $targetObj = &GetZoneObject($targetMZ);
    if($targetObj === null || (isset($targetObj->removed) && $targetObj->removed)) return;
    AttachWeaponCardIDToTarget($targetObj, $weaponCardID);

    if($weaponCardID === 'S1-STT01-013_Black-Jade-Dagger_W_C_die') {
        $targetKey = 'P' . intval($player) . '_BlackJadeDaggerTargetMZ';
        DecisionQueueController::StoreVariable($targetKey, $targetMZ);
    }
}

function ResolveWeaponPlayFromDiscard($player, $weaponMZ, $targetMZ) {
    $sourceObj = &GetZoneObject($weaponMZ);
    if($sourceObj === null || (isset($sourceObj->removed) && $sourceObj->removed)) return;
    if(($sourceObj->Location ?? '') !== 'Discard') return;

    $targetObj = &GetZoneObject($targetMZ);
    if($targetObj === null || (isset($targetObj->removed) && $targetObj->removed)) return;
    if(($targetObj->Location ?? '') !== 'Garden') return;

    $weaponCardID = $sourceObj->CardID ?? '';
    if($weaponCardID === '' || CardType($weaponCardID) !== 'WEAPON') return;

    $stack = &GetEffectStack();
    $beforeCount = count($stack);
    SafeMZMove($player, $weaponMZ, 'EffectStack');
    DecisionQueueController::CleanupRemovedCards();

    $stack = &GetEffectStack();
    $stackIndex = count($stack) - 1;
    if($stackIndex >= $beforeCount) {
        $stackMZ = 'EffectStack-' . $stackIndex;
        OnPlay($player, $stackMZ);

        $stackObj = &GetZoneObject($stackMZ);
        if($stackObj !== null && !(isset($stackObj->removed) && $stackObj->removed)) {
            $stackObj->Remove();
        }
        DecisionQueueController::CleanupRemovedCards();
    }

    $targetObj = &GetZoneObject($targetMZ);
    if($targetObj === null || (isset($targetObj->removed) && $targetObj->removed)) return;
    AttachWeaponCardIDToTarget($targetObj, $weaponCardID);

    if($weaponCardID === 'S1-STT01-013_Black-Jade-Dagger_W_C_die') {
        $targetKey = 'P' . intval($player) . '_BlackJadeDaggerTargetMZ';
        DecisionQueueController::StoreVariable($targetKey, $targetMZ);
    }
}

function DiscardAllEquippedWeapons($player) {
    $garden = &GetGarden($player);
    foreach($garden as &$obj) {
        if(isset($obj->removed) && $obj->removed) continue;
        DiscardEquippedWeaponsFromObject($player, $obj);
    }

    $alley = &GetAlley($player);
    foreach($alley as &$obj) {
        if(isset($obj->removed) && $obj->removed) continue;
        DiscardEquippedWeaponsFromObject($player, $obj);
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
        $leaderObj = $garden[$leaderIndex];
        $baseAttack = max(0, intval(CardAttack($leaderObj->CardID ?? '')));
        return $baseAttack + EquippedWeaponAttackBonus($leaderObj);
    }
    return 0;
}

function LeaderCurrentHealth($player) {
    $garden = &GetGarden($player);
    $leaderIndex = FindLeaderIndexInGarden($player);
    if($leaderIndex < 0 || $leaderIndex >= count($garden)) return 0;

    $leaderObj = &$garden[$leaderIndex];
    if($leaderObj === null || (isset($leaderObj->removed) && $leaderObj->removed)) return 0;

    $maxHealth = LeaderMaxHealth($player);
    $damage = intval($leaderObj->Damage ?? 0);
    return max(0, $maxHealth - $damage);
}

function TriggerGameOver($loserPlayer) {
    $loserPlayer = intval($loserPlayer);
    if($loserPlayer !== 1 && $loserPlayer !== 2) return;

    $existingWinner = DecisionQueueController::GetVariable('GAMEOVER_WINNER');
    if(is_string($existingWinner) && $existingWinner !== '') return;

    $winner = ($loserPlayer === 1) ? 2 : 1;
    DecisionQueueController::StoreVariable('GAMEOVER_WINNER', strval($winner));
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
                SafeMZMove($player, 'myGarden-' . $replaceIndex, 'myDiscard');
                DecisionQueueController::CleanupRemovedCards();
            }
        }
    }
    else {
        $alley = &GetAlley($player);
        if(CountActiveEntities($alley, true) >= 5) {
            $replaceIndex = FindReplaceableIndex($alley);
            if($replaceIndex >= 0) {
                SafeMZMove($player, 'myAlley-' . $replaceIndex, 'myDiscard');
                DecisionQueueController::CleanupRemovedCards();
            }
        }
    }

    SafeMZMove($player, $mzCard, $destination);
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

function QueueLeaderDamageAnimation($player, $amount) {
    if(intval($amount) <= 0) return;

    $leaderIndex = FindLeaderIndexInGarden($player);
    if($leaderIndex >= 0) {
        QueueDamageAnimation('p' . $player . 'Garden-' . $leaderIndex, intval($amount), 500, true);
        return;
    }

    QueueDamageAnimation(intval($player) === 1 ? 'P1BASE' : 'P2BASE', intval($amount), 500, true);
}

function DealDamageToLeader($player, $amount) {
    $amount = max(0, intval($amount));
    if($amount <= 0) return;

    $garden = &GetGarden($player);
    $leaderIndex = FindLeaderIndexInGarden($player);
    if($leaderIndex < 0 || $leaderIndex >= count($garden)) return;

    $leaderObj = &$garden[$leaderIndex];
    if($leaderObj === null || (isset($leaderObj->removed) && $leaderObj->removed)) return;

    $leaderObj->Damage = intval($leaderObj->Damage ?? 0) + $amount;
    QueueLeaderDamageAnimation($player, $amount);

    if(LeaderCurrentHealth($player) <= 0) {
        TriggerGameOver($player);
    }
}

function HealLeader($player, $amount) {
    $amount = max(0, intval($amount));
    if($amount <= 0) return;

    $garden = &GetGarden($player);
    $leaderIndex = FindLeaderIndexInGarden($player);
    if($leaderIndex < 0 || $leaderIndex >= count($garden)) return;

    $leaderObj = &$garden[$leaderIndex];
    if($leaderObj === null || (isset($leaderObj->removed) && $leaderObj->removed)) return;

    $maxHealth = LeaderMaxHealth($player);
    $currentDamage = max(0, intval($leaderObj->Damage ?? 0));
    $newDamage = max(0, $currentDamage - $amount);
    $leaderObj->Damage = min($maxHealth, $newDamage);
}

function DealDamageToGardenTarget($player, $targetMZ, $amount) {
    $amount = max(0, intval($amount));
    if($amount <= 0) return;
    if(!is_string($targetMZ) || $targetMZ === '') return;

    $parts = explode('-', $targetMZ);
    $zone = $parts[0] ?? '';
    $index = intval($parts[1] ?? -1);
    if($zone !== 'myGarden' && $zone !== 'theirGarden') return;

    $targetPlayer = ($zone === 'myGarden') ? intval($player) : (intval($player) === 1 ? 2 : 1);
    $garden = &GetGarden($targetPlayer);

    if($index < 0 || $index >= count($garden)) return;
    if(isset($garden[$index]->removed) && $garden[$index]->removed) return;

    $targetCardID = $garden[$index]->CardID ?? '';
    if(CardType($targetCardID) === 'LEADER') {
        DealDamageToLeader($targetPlayer, $amount);
        return;
    }

    $garden[$index]->Damage = intval($garden[$index]->Damage ?? 0) + $amount;
    QueueDamageAnimation('p' . $targetPlayer . 'Garden-' . $index, $amount, 500, true);

    $targetHealth = intval(CardHealth($targetCardID));
    $targetDamage = intval($garden[$index]->Damage ?? 0);
    if($targetHealth > 0 && $targetDamage >= $targetHealth) {
        SafeMZMove($player, $zone . '-' . $index, ($zone === 'myGarden' ? 'myDiscard' : 'theirDiscard'));
        DecisionQueueController::CleanupRemovedCards();
    }
}

function GetPortalCandidates($player) {
    $alley = &GetAlley($player);
    $candidates = [];

    for($i = 0; $i < count($alley); ++$i) {
        $obj = &$alley[$i];
        if(isset($obj->removed) && $obj->removed) continue;
        $status = intval($obj->Status ?? 2);
        if($status == 1) continue; // must be untapped
        $candidates[] = 'myAlley-' . $i;
    }

    return $candidates;
}

function CanUseGateRuntime($player, $gateMZ = null, $entityMZ = null) {
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

    $candidates = GetPortalCandidates($player);
    if(empty($candidates)) return false;

    if(is_string($entityMZ) && $entityMZ !== '') {
        return in_array($entityMZ, $candidates);
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

    if(CardType($entity->CardID ?? '') === 'LEADER' && !HasEquippedWeapon($entity)) {
        return false;
    }

    // Cannot attack if tapped
    if($entity->Status == 1) return false; // Tapped
    if(HasCooldown($entity)) {
        $hasCharge = isset($entity->TurnEffects) && is_array($entity->TurnEffects) && in_array('CHARGE', $entity->TurnEffects, true);
        if(!$hasCharge) return false;
    }

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

    // Hand/temp-zone highlights are for the currently acting player.
    $owner = ResolveObjectOwner($obj);
    $actingPlayer = HasPendingAttackResponse() ? GetPendingAttackResponderPlayer() : intval($turnPlayer);
    if($owner === null || $owner !== intval($actingPlayer)) {
        return json_encode(['highlight' => false]);
    }

    // During a response window only cards with the [Response] timing tag are playable.
    if(HasPendingAttackResponse() && !CardHasTimingTag($obj->CardID, 'Response')) {
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

function CardHasTimingTag($cardID, $tagName) {
    if(!is_string($cardID) || $cardID === '' || !is_string($tagName) || $tagName === '') return false;
    $text = CardCardText($cardID);
    if(!is_string($text) || $text === '') return false;
    return stripos($text, '[' . $tagName . ']') !== false;
}

function CanPlaySpellByTiming($player, $cardID) {
    $isResponseWindow = HasPendingAttackResponse();
    $hasMainTiming = CardHasTimingTag($cardID, 'Main');
    $hasResponseTiming = CardHasTimingTag($cardID, 'Response');

    if($isResponseWindow) {
        if(intval($player) !== GetPendingAttackResponderPlayer()) return false;
        return $hasResponseTiming;
    }

    if($hasResponseTiming && !$hasMainTiming) return false;
    return true;
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
    if(!is_object($obj) || !isset($obj->CardID)) return 0;
    $base = max(0, intval(CardAttack($obj->CardID)));
    return $base + EquippedWeaponAttackBonus($obj);
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
    $actingPlayer = HasPendingAttackResponse() ? GetPendingAttackResponderPlayer() : intval($turnPlayer);
    if($owner === null || $owner !== intval($actingPlayer)) {
        return json_encode(['highlight' => false]);
    }

    return json_encode(['color' => 'rgba(0, 255, 0, 0.95)']);
}

function CombatTargetIndicator($obj) {
    if(!is_object($obj) || !isset($obj->Location)) return '';
    if(!HasPendingAttackResponse()) return '';
    if(!isset($obj->mzIndex)) return '';
    $objOwner = ResolveObjectOwner($obj);
    if($objOwner === null) return '';
    $objLocation = strval($obj->Location);
    $objIndex = intval($obj->mzIndex);
    if($objLocation === '' || $objIndex < 0) return '';

    $attackerPlayer = GetPendingAttackAttackerPlayer();
    if($attackerPlayer !== 1 && $attackerPlayer !== 2) return '';

    $attackerMZ = DecisionQueueController::GetVariable('PendingAttackAttackerMZ');
    $targetMZ = DecisionQueueController::GetVariable('PendingAttackTargetMZ');
    $attackerAbs = ParsePerspectiveMzID($attackerPlayer, $attackerMZ);
    $targetAbs = ParsePerspectiveMzID($attackerPlayer, $targetMZ);

    if(is_array($attackerAbs)
        && intval($attackerAbs['owner']) === intval($objOwner)
        && strcasecmp(strval($attackerAbs['location']), $objLocation) === 0
        && intval($attackerAbs['index']) === $objIndex) {
        return 'ATTACKER';
    }
    if(is_array($targetAbs)
        && intval($targetAbs['owner']) === intval($objOwner)
        && strcasecmp(strval($targetAbs['location']), $objLocation) === 0
        && intval($targetAbs['index']) === $objIndex) {
        return 'TARGET';
    }
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
    if(HasPendingAttackResponse()) return 0;

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

        $cardID = $obj->CardID ?? '';
        if($cardID !== '' && function_exists('CardActivateAbilityCount')) {
            $abilityCount = intval(CardActivateAbilityCount($cardID));
            for($abilityIndex = 0; $abilityIndex < $abilityCount; ++$abilityIndex) {
                if(!function_exists('CanActivateAbility') || CanActivateAbility($turnPlayer, $mzID, $abilityIndex)) {
                    return 1;
                }
            }
        }
    }

    // Gate surfaces Activate when it is usable and an untapped Alley unit exists to portal.
    if($location === 'Gate' && $mzIndex >= 0 && CanUseGateRuntime($turnPlayer, 'myGate-' . $mzIndex, '')) {
        if(!empty(GetPortalCandidates($turnPlayer))) return 1;
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
        return LeaderCurrentHealth($opponent) > 0;
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

    if($cardType === 'SPELL' && !CanPlaySpellByTiming($player, $cardID)) {
        if(HasPendingAttackResponse()) {
            SetFlashMessage('Only [Response] spells can be played by the defending player during this response window.');
        } else {
            SetFlashMessage('This spell cannot be played during the main phase.');
        }
        return '';
    }

    if($cardType === 'WEAPON') {
        $targets = ResolveWeaponEquipTargets($player);
        if(empty($targets)) {
            SetFlashMessage('No valid Garden target to equip this weapon.');
            return '';
        }
    }

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
    } else if($cardType === 'WEAPON') {
        $targets = ResolveWeaponEquipTargets($player);
        if(empty($targets)) {
            return '';
        }
        $targetStr = implode('&', $targets);
        DecisionQueueController::AddDecision($player, 'MZCHOOSE', $targetStr, 1, 'Select_Garden_target_to_equip');
        DecisionQueueController::AddDecision($player, 'CUSTOM', 'PLAY_WEAPON_TARGET|' . $mzCard, 1);
        return 'PLAY';
    } else if($cardType === 'SPELL') {
        $stack = &GetEffectStack();
        $beforeCount = count($stack);
        SafeMZMove($player, $mzCard, 'EffectStack');
        DecisionQueueController::CleanupRemovedCards();

        $stack = &GetEffectStack();
        $stackIndex = count($stack) - 1;
        if($stackIndex >= $beforeCount) {
            $stackMZ = 'EffectStack-' . $stackIndex;
            OnPlay($player, $stackMZ);
            SafeMZMove($player, $stackMZ, 'myDiscard');
        }
    } else {
        // Weapon and unsupported card types: pay cost, then send to discard for now.
        SafeMZMove($player, $mzCard, 'myDiscard');
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

    $cardIDCandidates = GetMacroCardIDCandidates($cardID);
    $abilityCount = 0;
    if(function_exists('CardOnPlayCount')) {
        for($i = 0; $i < count($cardIDCandidates); ++$i) {
            $abilityCount = max($abilityCount, intval(CardOnPlayCount($cardIDCandidates[$i])));
        }
    }

    if($abilityCount <= 0) {
        for($i = 0; $i < count($cardIDCandidates); ++$i) {
            $key = $cardIDCandidates[$i] . ':0';
            if(isset($onPlayAbilities[$key])) {
                $onPlayAbilities[$key]($player);
                break;
            }
        }
        return 'ON_PLAY';
    }

    for($i = 0; $i < $abilityCount; ++$i) {
        for($j = 0; $j < count($cardIDCandidates); ++$j) {
            $key = $cardIDCandidates[$j] . ':' . $i;
            if(isset($onPlayAbilities[$key])) {
                $onPlayAbilities[$key]($player);
                break;
            }
        }
    }

    return 'ON_PLAY';
}

function OnUseGateCard($player, $gateMZ) {
    global $useGateAbilities;
    if(!isset($useGateAbilities) || !is_array($useGateAbilities)) {
        return 'USE_GATE';
    }

    $obj = GetZoneObject($gateMZ);
    if($obj === null || (isset($obj->removed) && $obj->removed)) {
        return 'USE_GATE';
    }

    $cardID = $obj->CardID ?? '';
    if($cardID === '') {
        return 'USE_GATE';
    }

    $cardIDCandidates = GetMacroCardIDCandidates($cardID);
    $abilityCount = 0;
    if(function_exists('CardUseGateCount')) {
        for($i = 0; $i < count($cardIDCandidates); ++$i) {
            $abilityCount = max($abilityCount, intval(CardUseGateCount($cardIDCandidates[$i])));
        }
    }

    if($abilityCount <= 0) {
        for($i = 0; $i < count($cardIDCandidates); ++$i) {
            $key = $cardIDCandidates[$i] . ':0';
            if(isset($useGateAbilities[$key])) {
                $useGateAbilities[$key]($player);
                break;
            }
        }
        return 'USE_GATE';
    }

    for($i = 0; $i < $abilityCount; ++$i) {
        for($j = 0; $j < count($cardIDCandidates); ++$j) {
            $key = $cardIDCandidates[$j] . ':' . $i;
            if(isset($useGateAbilities[$key])) {
                $useGateAbilities[$key]($player);
                break;
            }
        }
    }

    return 'USE_GATE';
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
    $attackerAttack = max(0, intval(CardAttack($attackerObj->CardID ?? ''))) + EquippedWeaponAttackBonus($attackerObj);
    $attackerIsLeader = (CardType($attackerObj->CardID ?? '') === 'LEADER');

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
        $defenderHealth = max(0, LeaderCurrentHealth($opponent));
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
                QueueDamageAnimation('p' . $opponent . 'Garden-' . $targetIndex, $attackerAttack, 500, true);
            }
        }
    }

    if($defenderAttack > 0) {
        if($attackerIsLeader) {
            DealDamageToLeader($player, $defenderAttack);
        } else {
            $myGarden = &GetGarden($player);
            if(isset($myGarden[$attackerIndex]) && !(isset($myGarden[$attackerIndex]->removed) && $myGarden[$attackerIndex]->removed)) {
                $myGarden[$attackerIndex]->Damage = intval($myGarden[$attackerIndex]->Damage ?? 0) + $defenderAttack;
                QueueDamageAnimation('p' . $player . 'Garden-' . $attackerIndex, $defenderAttack, 500, true);
            }
        }
    }

    // Destroy non-leader entities that reached 0 health after simultaneous damage.
    if(!$targetIsLeader) {
        $theirGarden = &GetGarden($opponent);
        if(isset($theirGarden[$targetIndex]) && !(isset($theirGarden[$targetIndex]->removed) && $theirGarden[$targetIndex]->removed)) {
            $targetDamage = intval($theirGarden[$targetIndex]->Damage ?? 0);
            if($defenderHealth > 0 && $targetDamage >= $defenderHealth) {
                SafeMZMove($player, 'theirGarden-' . $targetIndex, 'theirDiscard');
            }
        }
    }

    $myGarden = &GetGarden($player);
    if(isset($myGarden[$attackerIndex]) && !(isset($myGarden[$attackerIndex]->removed) && $myGarden[$attackerIndex]->removed)) {
        $attackerHealth = max(0, intval(CardHealth($myGarden[$attackerIndex]->CardID ?? '')));
        $attackerDamage = intval($myGarden[$attackerIndex]->Damage ?? 0);
        if($attackerHealth > 0 && CardType($myGarden[$attackerIndex]->CardID ?? '') !== 'LEADER' && $attackerDamage >= $attackerHealth) {
            SafeMZMove($player, 'myGarden-' . $attackerIndex, 'myDiscard');
        }
    }

    DecisionQueueController::CleanupRemovedCards();
    return 'ATTACK';
}

function DoActivatedAbility($player, $mzCard, $abilityIndex = 0) {
    global $activateAbilityAbilities;

    $obj = GetZoneObject($mzCard);
    if($obj === null || (isset($obj->removed) && $obj->removed)) return '';

    $cardID = $obj->CardID ?? '';
    if(!is_string($cardID) || $cardID === '') return '';

    $abilityIndex = max(0, intval($abilityIndex));
    $cardIDCandidates = GetMacroCardIDCandidates($cardID);
    for($i = 0; $i < count($cardIDCandidates); ++$i) {
        $abilityKey = $cardIDCandidates[$i] . ':' . $abilityIndex;
        if(isset($activateAbilityAbilities[$abilityKey]) && is_callable($activateAbilityAbilities[$abilityKey])) {
            $activateAbilityAbilities[$abilityKey]($player);
            CardActivated($player, $mzCard);
            return 'ACTIVATE_ABILITY';
        }
    }

    return '';
}

function DoUseGate($player, $gateMZ, $entityMZ) {
    $gateObj = &GetZoneObject($gateMZ);
    if($gateObj === null || (isset($gateObj->removed) && $gateObj->removed)) {
        return '';
    }

    if(!CanUseGateRuntime($player, $gateMZ, $entityMZ)) {
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

    $portalSucceeded = false;
    if(is_string($entityMZ) && $entityMZ !== '') {
        $entityObj = &GetZoneObject($entityMZ);
        if($entityObj !== null && !(isset($entityObj->removed) && $entityObj->removed) && isset($entityObj->Location) && $entityObj->Location === 'Alley' && intval($entityObj->Status ?? 2) !== 1) {
            $entityCardID = $entityObj->CardID ?? '';
            if($entityCardID !== '') {
                DecisionQueueController::StoreVariable('entityMZCardID', $entityCardID);
            }

            $garden = &GetGarden($player);
            if(CountActiveEntities($garden, true) >= 5) {
                $replaceIndex = FindReplaceableIndex($garden);
                if($replaceIndex >= 0) {
                    SafeMZMove($player, 'myGarden-' . $replaceIndex, 'myDiscard');
                    DecisionQueueController::CleanupRemovedCards();
                }
            }

            SafeMZMove($player, $entityMZ, 'myGarden');
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

            $portalSucceeded = true;
        }
    }

    if($portalSucceeded) {
        OnUseGateCard($player, $gateMZ);
    }

    return 'GATE';
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

    if($cardZone === 'myHand' && $currentPhase === 'MAIN' && HasPendingAttackResponse() && intval($playerID) === GetPendingAttackResponderPlayer()) {
        if(function_exists('PlayCard')) {
            PlayCard($playerID, $actionCard);
            return 'PLAY_RESPONSE';
        }
    }

    if($cardZone === 'myHand' && $currentPhase === 'MAIN' && !HasPendingAttackResponse() && intval($playerID) === intval($turnPlayer)) {
        if(function_exists('PlayCard')) {
            PlayCard($playerID, $actionCard);
            return 'PLAY';
        }
    }

    // Fallback: allow direct card click on Garden cards to initiate attack setup.
    if($cardZone === 'myGarden' && $currentPhase === 'MAIN' && !HasPendingAttackResponse() && intval($playerID) === intval($turnPlayer)) {
        if(function_exists('HandleAttackSetup')) {
            HandleAttackSetup($playerID, $actionCard);
            return 'ATTACK_SETUP';
        }
    }

    // Fallback: allow direct gate click to start portal flow.
    if($cardZone === 'myGate' && $currentPhase === 'MAIN' && !HasPendingAttackResponse() && intval($playerID) === intval($turnPlayer)) {
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
    // 0. Weapons are temporary and are discarded from all equipped cards.
    DiscardAllEquippedWeapons($player);
    DiscardAllEquippedWeapons($player == 1 ? 2 : 1);

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
    BeginAttackResponseWindow($player, $attackerMZ, $chosenTarget);
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

$customDQHandlers["PLAY_WEAPON_TARGET"] = function($player, $params, $lastDecision) {
    $mzCard = isset($params[0]) ? $params[0] : '';
    if(!is_string($mzCard) || $mzCard === '') return;

    $targetMZ = is_string($lastDecision) ? $lastDecision : '';
    if($targetMZ === '' || strtoupper($targetMZ) === 'PASS') return;

    $targetObj = &GetZoneObject($targetMZ);
    if($targetObj === null || (isset($targetObj->removed) && $targetObj->removed)) return;
    if(($targetObj->Location ?? '') !== 'Garden') return;

    ResolveWeaponPlayFromHand($player, $mzCard, $targetMZ);
};

$customDQHandlers['RaizanActivate:Grant-Charge'] = function($player, $params, $lastDecision) {
    $targetMZ = is_string($lastDecision) ? $lastDecision : '';
    if($targetMZ === '' || $targetMZ === '-' || strtoupper($targetMZ) === 'PASS') return;

    $targetObj = &GetZoneObject($targetMZ);
    if($targetObj === null || (isset($targetObj->removed) && $targetObj->removed)) return;

    if(!CanPayIKZCost($player, 1)) {
        SetFlashMessage('Not enough IKZ available. You need 1 IKZ to activate this ability.');
        return;
    }

    if(!isset($targetObj->TurnEffects) || !is_array($targetObj->TurnEffects)) {
        $targetObj->TurnEffects = [];
    }

    if(!in_array('CHARGE', $targetObj->TurnEffects, true)) {
        $targetObj->TurnEffects[] = 'CHARGE';
    }

    PayIKZCost($player, 1);
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

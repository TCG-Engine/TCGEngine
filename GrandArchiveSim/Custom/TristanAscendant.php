<?php
// Tristan Lineage stays with the champion after leveling. The database owns its effects.
function GATristanAscendantSource($obj) {
    return $obj !== null && !$obj->removed
        && ($obj->CardID === 'sSIDPfkmkw' || in_array('sSIDPfkmkw', $obj->Subcards ?? [], true))
        && !HasNoAbilities($obj) && PropertyContains(EffectiveCardType($obj), 'CHAMPION');
}
function GATristanAscendantCanActivate($obj) {
    return GATristanAscendantSource($obj) && PropertyContains(EffectiveCardType($obj), 'ASCENDANT')
        && CountAvailableReservePayments(intval($obj->Controller)) >= 2025;
}
function GATristanAscendantDynamicIndex($obj) {
    foreach(json_decode(GetDynamicAbilities($obj) ?: '[]', true) ?? [] as $ability) {
        if(($ability['key'] ?? '') === 'tristanAscendant') return intval($ability['index']);
    }
    return -1;
}
function GATristanAscendantPay($player, $source) {
    if(!GATristanAscendantCanActivate($source) || intval($source->Controller) !== intval($player)) return;
    $uid = intval($source->UniqueID);
    DecisionQueueController::StoreVariable('tristanAscendantPayment', strval($uid));
    DecisionQueueController::StoreVariable('mzID', $source->GetMzID());
    $choices = GetReservePaymentChoiceSource($player);
    DecisionQueueController::AddDecision($player, 'MZMULTICHOOSE', '2025|2025|' . $choices, 100,
        tooltip:'Pay_2025_reserve_for_Tristan_Ascendant_Shadow');
    DecisionQueueController::AddDecision($player, 'CUSTOM', 'TristanAscendantPaid|' . $uid, 100);
}
$customDQHandlers['TristanAscendantPaid'] = function($player, $parts, $answer) {
    $uid = intval($parts[0] ?? 0);
    if($uid <= 0 || DecisionQueueController::GetVariable('tristanAscendantPayment') !== strval($uid)) return;
    DecisionQueueController::ClearVariable('tristanAscendantPayment');
    $mz = FindFieldMzByUniqueID($uid);
    $source = $mz === '' ? null : GetZoneObject($mz);
    if(!GATristanAscendantCanActivate($source) || intval($source->Controller) !== intval($player)) return;
    $choices = '2025|2025|' . GetReservePaymentChoiceSource($player);
    if(!GAValidateMultiChooseAnswer($choices, strval($answer)) || count(ParseReservePaymentSelections($answer)) !== 2025) return;
    if(!ProcessReservePaymentSelections($player, $answer, 2025)) return;
    DecisionQueueController::AddDecision($player, 'CUSTOM', 'AbilityActivated|sSIDPfkmkw|0', 101);
};

function GATristanIntentToken($obj) {
    foreach($obj->TurnEffects ?? [] as $effect) if(str_starts_with($effect, 'TRISTAN_CARD:')) return $effect;
    $serial = intval(GetUniqueIDCounter()) + 1;
    SetUniqueIDCounter($serial);
    $token = 'TRISTAN_CARD:' . $serial;
    $obj->TurnEffects[] = $token;
    return $token;
}
function GATristanFindIntent($player, $token) {
    global $playerID;
    foreach(GetIntent($player) as $i => $obj) {
        if(!$obj->removed && in_array($token, $obj->TurnEffects ?? [], true)) return ($player == $playerID ? 'myIntent-' : 'theirIntent-') . $i;
    }
    return '';
}
function GATristanHold($obj) {
    return $obj !== null && in_array('TRISTAN_HOLD', $obj->TurnEffects ?? [], true);
}
function GATristanQueueHit($player, $ref) {
    $obj = GetZoneObject($ref);
    if($obj === null || $obj->removed || HasNoAbilities($obj)) return;
    $owner = $player;
    foreach($obj->TurnEffects ?? [] as $effect) if(str_starts_with($effect, 'GA_CARD_OWNER:')) $owner = intval(substr($effect, 14));
    if(GlobalEffectCount($owner, 'TRISTAN_ASCENDANT_ATTACKS') === 0) return;
    $token = GATristanIntentToken($obj);
    if(!GATristanHold($obj)) $obj->TurnEffects[] = 'TRISTAN_HOLD';
    QueueTriggeredAbility($player, 'sSIDPfkmkw', 'DTR_EVENT', ['tristanIntentToken'=>$token, 'dtrEventMacro'=>'GrantedAbility']);
}
function GATristanAttackChoices($player) {
    $refs = [];
    foreach(ZoneSearch('myField', ['ALLY']) as $ref) {
        $obj = GetZoneObject($ref);
        if($obj === null || $obj->removed || $obj->Status != 2 || !PropertyContains(EffectiveCardType($obj), 'PHANTASIA')) continue;
        if(GetCounterCount($obj, 'frenzy') > 0 || in_array('CANT_ATTACK_NEXT_TURN', $obj->TurnEffects ?? [], true)) continue;
        if(GALinkedProperty($obj, 'cannotUseInAttack') > 0 || empty(GetValidAttackTargets($ref))) continue;
        $refs[] = $ref;
    }
    return $refs;
}
function GATristanReleaseIntent($player, $token) {
    $ref = GATristanFindIntent($player, $token);
    if($ref === '') return;
    $obj = GetZoneObject($ref);
    $cleaned = in_array('TRISTAN_CLEANED', $obj->TurnEffects ?? [], true);
    $obj->TurnEffects = array_values(array_diff($obj->TurnEffects, ['TRISTAN_HOLD', 'TRISTAN_CLEANED', 'TRISTAN_CLAIMED']));
    if($cleaned) {
        global $Renewable_Cards;
        $dest = IsEphemeral($obj) ? 'myBanish' : (isset($Renewable_Cards[$obj->CardID]) ? 'myMaterial' : 'myGraveyard');
        MZMove($player, NormalizeMzIDForController($ref, $player), $dest);
    }
}
function GATristanQueueAttack($player, $token, $choice) {
    $ref = GATristanFindIntent($player, $token);
    if($ref === '') return;
    $intent = GetZoneObject($ref);
    if(in_array('TRISTAN_CLAIMED', $intent->TurnEffects ?? [], true)) return;
    if(!in_array($choice, GATristanAttackChoices($player), true)) {
        GATristanReleaseIntent($player, $token);
        return;
    }
    $attacker = GetZoneObject($choice);
    $intent->TurnEffects[] = 'TRISTAN_CLAIMED';
    DecisionQueueController::AddDecision($player, 'CUSTOM', 'TristanAscendantAttack|' . intval($attacker->UniqueID) . '|' . $token, 250);
}
$customDQHandlers['TristanAscendantAttack'] = function($player, $parts, $answer) {
    $ref = GATristanFindIntent($player, $parts[1] ?? '');
    if($ref === '') return;
    $attackerRef = FindFieldMzByUniqueID(intval($parts[0] ?? 0));
    $attacker = $attackerRef === '' ? null : GetZoneObject($attackerRef);
    if($attacker === null || $attacker->removed || intval($attacker->Controller) !== intval($player)) {
        GATristanReleaseIntent($player, $parts[1]);
        return;
    }
    $otherQueue = &GetDecisionQueue(3 - $player);
    foreach($otherQueue as $i => $decision) {
        if(str_starts_with($decision->Param, 'CombatCleanup')) { array_splice($otherQueue, $i, 1); break; }
    }
    $intent = GetZoneObject($ref);
    $intent->TurnEffects = array_values(array_diff($intent->TurnEffects, ['TRISTAN_HOLD', 'TRISTAN_CLEANED', 'TRISTAN_CLAIMED']));
    if(!BeginCombatPhase($attackerRef, true)) {
        $intent->TurnEffects[] = 'TRISTAN_CLEANED';
        GATristanReleaseIntent($player, $parts[1]);
    }
};
$doesGlobalEffectApply['TRISTAN_ASCENDANT_ATTACKS'] = function($obj) { return false; };
$foreverEffects['TRISTAN_ASCENDANT_ATTACKS'] = true;

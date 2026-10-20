<?php
// Lorraine Lineage remains on the top champion; generation itself does not play the Swords.
function GALorraineAscendantSource($obj) {
    return $obj !== null && !$obj->removed && !HasNoAbilities($obj)
        && PropertyContains(EffectiveCardType($obj), 'CHAMPION')
        && ($obj->CardID === '81gvGHkuVb' || in_array('81gvGHkuVb', $obj->Subcards ?? [], true));
}
function GALorraineAscendantCanActivate($obj) {
    return GALorraineAscendantSource($obj) && PropertyContains(EffectiveCardType($obj), 'ASCENDANT')
        && CountAvailableReservePayments(intval($obj->Controller)) >= 2024;
}
function GALorraineAscendantDynamicIndex($obj) {
    foreach(json_decode(GetDynamicAbilities($obj) ?: '[]', true) ?? [] as $ability) {
        if(($ability['key'] ?? '') === 'lorraineAscendant') return intval($ability['index']);
    }
    return -1;
}
function GALorraineAscendantPay($player, $source) {
    if(!GALorraineAscendantCanActivate($source) || intval($source->Controller) !== intval($player)) return;
    $uid = intval($source->UniqueID);
    DecisionQueueController::StoreVariable('lorraineAscendantPayment', strval($uid));
    DecisionQueueController::StoreVariable('mzID', $source->GetMzID());
    $choices = GetReservePaymentChoiceSource($player);
    DecisionQueueController::AddDecision($player, 'MZMULTICHOOSE', '2024|2024|' . $choices, 100,
        tooltip:'Pay_2024_reserve_for_Lorraine_Ascendant_Wings');
    DecisionQueueController::AddDecision($player, 'CUSTOM', 'LorraineAscendantPaid|' . $uid, 100);
}
$customDQHandlers['LorraineAscendantPaid'] = function($player, $parts, $answer) {
    $uid = intval($parts[0] ?? 0);
    if($uid <= 0 || DecisionQueueController::GetVariable('lorraineAscendantPayment') !== strval($uid)) return;
    DecisionQueueController::ClearVariable('lorraineAscendantPayment');
    $mz = FindFieldMzByUniqueID($uid);
    $source = $mz === '' ? null : GetZoneObject($mz);
    if(!GALorraineAscendantCanActivate($source) || intval($source->Controller) !== intval($player)) return;
    $choices = '2024|2024|' . GetReservePaymentChoiceSource($player);
    if(!GAValidateMultiChooseAnswer($choices, strval($answer)) || count(ParseReservePaymentSelections($answer)) !== 2024) return;
    if(!ProcessReservePaymentSelections($player, $answer, 2024)) return;
    DecisionQueueController::AddDecision($player, 'CUSTOM', 'AbilityActivated|81gvGHkuVb|0', 101);
};
function GALorraineAscendantResolve($player) {
    if(GlobalEffectCount($player, 'LORRAINE_ASCENDANT_SWORDS_AS_ALLY') === 0) AddGlobalEffects($player, 'LORRAINE_ASCENDANT_SWORDS_AS_ALLY');
    DecisionQueueController::StoreVariable('lorraineAscendantSwords', '[]');
    GALorraineAscendantChooseMore($player);
}
function GALorraineAscendantChooseMore($player) {
    DecisionQueueController::AddDecision($player, 'YESNO', '-', 1, tooltip:'Generate_another_Sword_regalia_card?');
    DecisionQueueController::AddDecision($player, 'CUSTOM', 'LorraineAscendantMore', 1);
}
$customDQHandlers['LorraineAscendantMore'] = function($player, $parts, $answer) {
    $stored = DecisionQueueController::GetVariable('lorraineAscendantSwords');
    if($stored === null) return;
    if($answer === 'YES') {
        DecisionQueueController::AddDecision($player, 'NAMECARD', 'Sword_regalia', 1, tooltip:'Choose_a_Sword_regalia_card_to_generate');
        DecisionQueueController::AddDecision($player, 'CUSTOM', 'LorraineAscendantSword', 1);
        return;
    }
    if(!in_array($answer, ['NO', 'PASS', '-', ''], true)) return;
    DecisionQueueController::ClearVariable('lorraineAscendantSwords');
    $swords = json_decode($stored, true);
    if(!is_array($swords)) return;
    $swords = array_values(array_filter($swords, fn($cardID) => PropertyContains(CardType($cardID), 'REGALIA') && PropertyContains(CardSubtypes($cardID), 'SWORD') && GACanGenerateCard($player, $cardID)));
    GAPutFieldGroup($player, $swords);
};
$customDQHandlers['LorraineAscendantSword'] = function($player, $parts, $answer) {
    $stored = DecisionQueueController::GetVariable('lorraineAscendantSwords');
    if($stored === null) return;
    $swords = json_decode($stored, true);
    if(!is_array($swords)) return;
    $id = ResolveNamedCardInputToRepresentativeID(trim(strval($answer)));
    if($id !== null && PropertyContains(CardType($id), 'REGALIA') && PropertyContains(CardSubtypes($id), 'SWORD') && GACanGenerateCard($player, $id)) {
        $swords[] = $id;
        DecisionQueueController::StoreVariable('lorraineAscendantSwords', json_encode($swords));
    } else SetFlashMessage('Choose a Sword regalia card.');
    GALorraineAscendantChooseMore($player);
};
function GASwordMayAttackAsAlly($obj) {
    if($obj === null || $obj->removed || !PropertyContains(EffectiveCardType($obj), 'WEAPON') || !PropertyContains(EffectiveCardSubtypes($obj), 'SWORD')) return false;
    $player = intval($obj->Controller);
    return GlobalEffectCount($player, 'CQ1bxUyi0Q_SWORDS_AS_ALLY') > 0 || GlobalEffectCount($player, 'LORRAINE_ASCENDANT_SWORDS_AS_ALLY') > 0;
}
$doesGlobalEffectApply['LORRAINE_ASCENDANT_SWORDS_AS_ALLY'] = function($obj) { return false; };
$foreverEffects['LORRAINE_ASCENDANT_SWORDS_AS_ALLY'] = true;

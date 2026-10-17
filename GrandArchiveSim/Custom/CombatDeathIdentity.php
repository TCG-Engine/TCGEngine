<?php
// Graveyard cards have no serialized UniqueID. Track a particular arrival by its
// live ordinal; BeforeRemove invalidates it before a same-name card can reuse it.
// Live ordinals are unchanged by tombstone cleanup and save/load compaction.
function GAKilledCardRegistry() {
    return json_decode(DecisionQueueController::GetVariable('GAKilledCards') ?? '{}', true) ?: [];
}
function GAStoreKilledCardRegistry($cards) {
    if(empty($cards)) DecisionQueueController::ClearVariable('GAKilledCards');
    else DecisionQueueController::StoreVariable('GAKilledCards', json_encode($cards));
}
function GACaptureKilledCardDestination($source, $destination) {
    $captureValue = DecisionQueueController::GetVariable('GACaptureDeathUID');
    if($captureValue === null) return;
    $capture = intval($captureValue);
    if(intval($source->UniqueID ?? 0) !== $capture) return;
    if($destination === null || $destination->removed || $destination->Location !== 'Graveyard') return;
    foreach([1, 2] as $seat) {
        $ordinal = 0;
        foreach(GetGraveyard($seat) as $card) {
            if($card->removed) continue;
            if($card === $destination) {
                $token = $capture . ':' . (AllyDestroyedGameCallCount(1) + AllyDestroyedGameCallCount(2));
                $cards = GAKilledCardRegistry();
                $cards[$token] = ['seat' => $seat, 'ordinal' => $ordinal, 'cardID' => $card->CardID];
                GAStoreKilledCardRegistry($cards);
                DecisionQueueController::StoreVariable('GACapturedDeathToken', $token);
                return;
            }
            ++$ordinal;
        }
    }
}
function GAGraveyardIdentityLeaving($object, $trigger = '') {
    if($object->removed) return true;
    $cards = GAKilledCardRegistry();
    if(empty($cards)) return true;
    foreach([1, 2] as $seat) {
        $ordinal = 0;
        foreach(GetGraveyard($seat) as $candidate) {
            if($candidate->removed) continue;
            if($candidate === $object) {
                foreach($cards as $token => &$record) {
                    if(intval($record['seat']) !== $seat) continue;
                    if(intval($record['ordinal']) === $ordinal) unset($cards[$token]);
                    elseif(intval($record['ordinal']) > $ordinal) --$record['ordinal'];
                }
                unset($record);
                GAStoreKilledCardRegistry($cards);
                return true;
            }
            ++$ordinal;
        }
    }
    return true;
}
function GAResolveKilledCard($token) {
    $record = GAKilledCardRegistry()[$token] ?? null;
    if(!is_array($record)) return null;
    $ordinal = 0;
    foreach(GetGraveyard(intval($record['seat'])) as $index => $card) {
        if($card->removed) continue;
        if($ordinal++ !== intval($record['ordinal'])) continue;
        if($card->CardID !== $record['cardID']) return null;
        return ['seat' => intval($record['seat']), 'index' => $index, 'object' => $card];
    }
    return null;
}
function GAResolveLethalDamage($player, $target) {
    $object = GetZoneObject($target);
    if($object === null || $object->removed) return;
    $snapshot = clone $object;
    $power = max(0, ObjectCurrentPower($snapshot));
    $hp = max(0, ObjectCurrentHP($snapshot));
    $sheen = GetCounterCount($snapshot, 'sheen');
    $combatAttacker = DecisionQueueController::GetVariable('CombatAttacker');
    $priorCapture = DecisionQueueController::GetVariable('GACaptureDeathUID');
    $priorToken = DecisionQueueController::GetVariable('GACapturedDeathToken');
    $needsIdentity = false;
    $attackerPlayer = intval(DecisionQueueController::GetVariable('CombatAttackerPlayer') ?? $player);
    foreach(GetIntent($attackerPlayer) as $intent) {
        if(!$intent->removed && $intent->CardID === 'oqDz2jIBZI') $needsIdentity = true;
    }
    if($combatAttacker !== null && $needsIdentity) DecisionQueueController::StoreVariable('GACaptureDeathUID', strval($snapshot->UniqueID));
    else DecisionQueueController::ClearVariable('GACaptureDeathUID');
    DecisionQueueController::ClearVariable('GACapturedDeathToken');
    try {
        AllyDestroyed($player, $target);
        $token = strval(DecisionQueueController::GetVariable('GACapturedDeathToken') ?? '');
    } finally {
        if($priorCapture === null) DecisionQueueController::ClearVariable('GACaptureDeathUID');
        else DecisionQueueController::StoreVariable('GACaptureDeathUID', $priorCapture);
        if($priorToken === null) DecisionQueueController::ClearVariable('GACapturedDeathToken');
        else DecisionQueueController::StoreVariable('GACapturedDeathToken', $priorToken);
    }
    if($combatAttacker === null) return;
    SetCombatKillOccurred($snapshot->CardID, $sheen, $power, $hp, $token);
    if(DecisionQueueController::GetVariable('CombatIsCleave') === '1') {
        $attackerPlayer = intval(DecisionQueueController::GetVariable('CombatAttackerPlayer') ?? $player);
        DecisionQueueController::AddDecision($player, 'CUSTOM',
            'CleaveCombatKillTrigger|' . $attackerPlayer . '|' . $combatAttacker . '|' . $snapshot->CardID . '|' . $sheen . '|' . $power . '|' . $hp . '|' . $token, 100);
    }
}

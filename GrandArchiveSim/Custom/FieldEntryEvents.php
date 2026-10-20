<?php
// A group describes one simultaneous instruction, not all entries during an ability.
function GAPutFieldGroup($player, $cardIDs, $rested = false, $replacementChosen = false) {
    if(!$replacementChosen && !empty($cardIDs) && !in_array('hkurfp66pv', $cardIDs, true)
        && count(array_filter($cardIDs, 'IsToken')) === count($cardIDs) && TonorisCreationsWillActive($player)) {
        $data = base64_encode(json_encode(['cards' => array_values($cardIDs), 'rested' => boolval($rested)]));
        DecisionQueueController::AddDecision($player, 'YESNO', '-', 1, tooltip:'Replace_this_token_summon_with_Aurousteel_Greatswords?');
        DecisionQueueController::AddDecision($player, 'CUSTOM', 'GAFieldGroupTonoris|' . $data, 1);
        return;
    }
    global $gaFieldEntryGroups, $gaNextFieldEntryGroup, $gaFieldEntryGroupCounter;
    $id = ++$gaFieldEntryGroupCounter;
    $gaFieldEntryGroups[$id] = [];
    try {
        foreach($cardIDs as $cardID) {
            $gaNextFieldEntryGroup = $id;
            $obj = MZAddZone($player, 'myField', $cardID);
            $gaNextFieldEntryGroup = null;
            if($obj !== null && $rested) $obj->Status = 1;
        }
        GAQueueFieldEntryEvent($player, $gaFieldEntryGroups[$id]);
    } finally {
        $gaNextFieldEntryGroup = null;
        unset($gaFieldEntryGroups[$id]);
    }
}
function GAClaimFieldEntryGroup() {
    global $gaNextFieldEntryGroup;
    $id = $gaNextFieldEntryGroup;
    $gaNextFieldEntryGroup = null;
    return $id;
}
function GARecordFieldEntry($player, $obj, $group) {
    global $gaFieldEntryGroups;
    $entry = ['cardID' => $obj->CardID, 'type' => EffectiveCardType($obj), 'overrides' => $obj->Counters['_overrides'] ?? []];
    if($group !== null && isset($gaFieldEntryGroups[$group])) $gaFieldEntryGroups[$group][] = $entry;
    else GAQueueFieldEntryEvent($player, [$entry]);
}
function GASummonTokenCopy($player, $entry) {
    $id = strval($entry['cardID'] ?? '');
    if($id === '') return null;
    $copy = new stdClass();
    $copy->CardID = $id;
    $copy->Owner = $player;
    $overrides = $entry['overrides'] ?? [];
    if(!is_array($overrides)) $overrides = [];
    $type = strval($entry['type'] ?? CardType($id));
    $overrides['type'] = PropertyContains($type, 'TOKEN') ? $type : $type . ',TOKEN';
    // Printed tokens need no redundant override; copied non-token definitions do.
    $copy->Counters = IsToken($id) && empty($entry['overrides']) ? [] : ['_overrides' => $overrides];
    return MZAddZone($player, 'myField', $id, $copy);
}
function GAQueueFieldEntryEvent($player, $entries) {
    if(empty($entries)) return;
    $opponent = $player === 1 ? 2 : 1;
    $regalia = array_values(array_filter($entries, fn($entry) => PropertyContains($entry['type'], 'REGALIA')));
    $copies = empty($regalia) ? 0 : GlobalEffectCount($opponent, 'owq8s5fefw');
    if($copies > 0) {
        for($i = 0; $i < $copies; ++$i) RemoveGlobalEffect($opponent, 'owq8s5fefw');
        DecisionQueueController::AddDecision($opponent, 'CUSTOM', 'GAReplicateRegalia|' . $copies . '|' . base64_encode(json_encode($regalia)), 1);
    }
    if(GlobalEffectCount($player, 'ENKI_TRIGGERED') > 0) return;
    $tokens = [];
    foreach($entries as $entry) {
        if(PropertyContains($entry['type'], 'TOKEN') && intval(CardCost_reserve($entry['cardID'])) <= 1) $tokens[json_encode($entry)] = $entry;
    }
    if(empty($tokens)) return;
    $enkiCount = 0;
    foreach(GetField($player) as $boon) if(!$boon->removed && $boon->CardID === 'fZZqzAXNAc' && !HasNoAbilities($boon)) ++$enkiCount;
    if($enkiCount === 0) return;
    AddGlobalEffects($player, 'ENKI_TRIGGERED');
    $tokens = array_values($tokens);
    $encoded = base64_encode(json_encode($tokens));
    for($i = 0; $i < $enkiCount; ++$i) {
        if(count($tokens) > 1) {
            $labels = array_map(fn($entry) => str_replace(' ', '_', CardName($entry['cardID'])), $tokens);
            DecisionQueueController::AddDecision($player, 'MZMODAL', '1|1|' . implode('&', $labels), 1, tooltip:'Choose_a_token_from_this_summon_event_to_copy');
            DecisionQueueController::AddDecision($player, 'CUSTOM', 'GAEnkiCopy|' . $encoded, 1);
        } else DecisionQueueController::AddDecision($player, 'CUSTOM', 'GAEnkiCopy|' . $encoded . '|0', 1);
    }
}
$customDQHandlers['GAEnkiCopy'] = function($player, $parts, $answer) {
    $entries = json_decode(base64_decode(strval($parts[0] ?? ''), true) ?: '', true);
    $choice = strval($parts[1] ?? $answer);
    if(!is_array($entries) || !ctype_digit($choice) || !isset($entries[intval($choice)])) return;
    GASummonTokenCopy($player, $entries[intval($choice)]);
};
$customDQHandlers['GAReplicateRegalia'] = function($player, $parts, $answer) {
    $count = max(0, intval($parts[0] ?? 0));
    $entries = json_decode(base64_decode(strval($parts[1] ?? ''), true) ?: '', true);
    if(!is_array($entries)) return;
    foreach($entries as $entry) if(PropertyContains(strval($entry['type'] ?? ''), 'REGALIA')) {
        for($i = 0; $i < $count; ++$i) GASummonTokenCopy($player, $entry);
    }
};

$customDQHandlers['GAFieldGroupTonoris'] = function($player, $parts, $answer) {
    $data = json_decode(base64_decode(strval($parts[0] ?? ''), true) ?: '', true);
    if(!is_array($data) || !is_array($data['cards'] ?? null)) return;
    $cards = $answer === 'YES' ? array_fill(0, count($data['cards']), 'hkurfp66pv') : $data['cards'];
    GAPutFieldGroup($player, $cards, !empty($data['rested']), true);
};

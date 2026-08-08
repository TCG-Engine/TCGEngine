<?php
// SHD_197
// Cost 2 - L3-37 - Droid Revolutionary - [Cunning,Heroism] - Power 2 - HP 2
// Text: When Played: You may rescue a captured card. If you don't, give a Shield token to this unit. / Smuggle [4 resources Cunning Heroism] (If this card is a resource, you may play her for her smuggle cost. Replace her with the top card of your deck.)

// ─── SHD_197 L3-37 ────────────────────────────────────────────────────────────
// When Played: You may rescue a captured card. If you don't, give a Shield token to this unit.
// Captives are subcards (no mzID) → stage every captive in TempZone (myTempZone-N renders in the
// card-image popup) with a positional "captorUID:subIdx" map. Pass / no captives → shield L3-37.
$whenPlayedAbilities["SHD_197:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $self    = GetZoneObject($mzID);
    $selfUID = ($self !== null) ? intval($self->UniqueID ?? 0) : 0;
    $entries = [];   // "captorUID:subIdx" per captive
    $cids    = [];
    foreach ([1, 2] as $p) {
        foreach (GetUnitsInPlay($p) as $u) {
            if (!empty($u->removed) || !is_array($u->Subcards ?? null)) continue;
            foreach ($u->Subcards as $si => $sub) {
                $isCaptive = is_array($sub) ? !empty($sub['IsCaptive']) : !empty($sub->IsCaptive);
                $isRemoved = is_array($sub) ? !empty($sub['removed'])   : !empty($sub->removed);
                if (!$isCaptive || $isRemoved) continue;
                $entries[] = intval($u->UniqueID ?? 0) . ':' . $si;
                $cids[]    = is_array($sub) ? ($sub['CardID'] ?? '') : ($sub->CardID ?? '');
            }
        }
    }
    // "a captured card" is unqualified — a card captured by a BASE (SEC_195 Arrest) is just as rescuable
    // as one guarded by a unit, but it lives in a GlobalEffects flag instead of a Subcards slot.
    foreach (SWUCollectBaseCaptives() as $row) { $entries[] = $row['entry']; $cids[] = $row['cardID']; }
    if (empty($entries)) {   // nothing rescuable → the "if you don't" branch is automatic
        DoGiveShieldToken(intval($player), $mzID);
        return;
    }
    $temp = &GetTempZone(intval($player));
    while (count($temp) > 0) array_pop($temp);
    foreach ($cids as $cid) AddTempZone(intval($player), $cid);
    $tempMZs = [];
    for ($k = 0; $k < count($cids); $k++) $tempMZs[] = "myTempZone-" . $k;
    SWUQueueMayChooseTarget(intval($player), $tempMZs,
        "Rescue_a_captured_card?", "Rescue_a_captured_card_(pass_to_shield_L3-37_instead)",
        "SHD_197#0|{$selfUID}|" . implode(",", $entries));
};

$customDQHandlers["SHD_197#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    $selfUID = intval($parts[0] ?? 0);
    $entries = ($parts[1] ?? '') !== '' ? explode(",", $parts[1]) : [];
    $temp = &GetTempZone(intval($player));
    while (count($temp) > 0) array_pop($temp);   // drain the staging zone either way
    $pickedIdx = -1;
    if ($lastDecision && preg_match('/myTempZone-(\d+)/', (string)$lastDecision, $m)) $pickedIdx = intval($m[1]);
    if ($pickedIdx < 0 || !isset($entries[$pickedIdx])) {
        // Declined → "If you don't, give a Shield token to this unit."
        $selfMz = SWUFindMzByUID($selfUID);
        if ($selfMz !== null) DoGiveShieldToken(intval($player), $selfMz);
        return;
    }
    $entry = (string)$entries[$pickedIdx];
    if (strpos($entry, 'B:') === 0) {              // base captive — no captor object to splice out of
        $sub = SWUTakeBaseCaptiveByEntry($entry);
        if ($sub !== null) DoRescueUnit($sub, null);
        return;
    }
    [$captorUID, $subIdx] = array_map('intval', explode(':', $entry));
    $captorMz = SWUFindMzByUID($captorUID);
    if ($captorMz === null) return;
    $captor = GetZoneObject($captorMz);
    if ($captor === null || !is_array($captor->Subcards ?? null) || !isset($captor->Subcards[$subIdx])) return;
    $sub = $captor->Subcards[$subIdx];
    array_splice($captor->Subcards, $subIdx, 1);   // detach the captive from its captor
    DoRescueUnit($sub, $captor);                    // back to its OWNER's arena, exhausted (CR 8.34.3)
};

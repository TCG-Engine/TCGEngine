<?php
// SHD_076
// Cost 1 - Unexpected Escape - [Vigilance]
// Text: Exhaust a unit. You may rescue a captured card guarded by that unit.

// ─── SHD_076 Unexpected Escape (Event) continuation ───────────────────────────
// Exhaust the chosen unit; you may rescue a captured card guarded by THAT unit.
$customDQHandlers["SHD_076#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (SWUDecisionDeclined($lastDecision)) return;
    $o = GetZoneObject($lastDecision);
    if (SWUObjGone($o)) return;
    OnExhaustCard(intval($player), $lastDecision);
    $uid = intval($o->UniqueID ?? 0);
    $entries = []; $cids = [];
    if (is_array($o->Subcards ?? null)) {
        foreach ($o->Subcards as $si => $sub) {
            $isCaptive = is_array($sub) ? !empty($sub['IsCaptive']) : !empty($sub->IsCaptive);
            $isRemoved = is_array($sub) ? !empty($sub['removed'])   : !empty($sub->removed);
            if (!$isCaptive || $isRemoved) continue;
            $entries[] = $uid . ':' . $si;
            $cids[]    = is_array($sub) ? ($sub['CardID'] ?? '') : ($sub->CardID ?? '');
        }
    }
    if (empty($entries)) return;   // no captive to rescue — the exhaust already happened
    $temp = &GetTempZone(intval($player));
    while (count($temp) > 0) array_pop($temp);
    foreach ($cids as $cid) AddTempZone(intval($player), $cid);
    $tempMZs = [];
    for ($k = 0; $k < count($cids); $k++) $tempMZs[] = "myTempZone-" . $k;
    SWUQueueMayChooseTarget(intval($player), $tempMZs,
        "Rescue_a_captured_card?", "Rescue_a_captured_card", "SHD_076#1|" . implode(",", $entries));
};

$customDQHandlers["SHD_076#1"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    $entries = ($parts[0] ?? '') !== '' ? explode(",", $parts[0]) : [];
    $temp = &GetTempZone(intval($player));
    while (count($temp) > 0) array_pop($temp);
    if (!$lastDecision || !preg_match('/myTempZone-(\d+)/', (string)$lastDecision, $m)) return;
    $pickedIdx = intval($m[1]);
    if (!isset($entries[$pickedIdx])) return;
    [$captorUID, $subIdx] = array_map('intval', explode(':', $entries[$pickedIdx]));
    $captorMz = SWUFindMzByUID($captorUID);
    if ($captorMz === null) return;
    $captor = GetZoneObject($captorMz);
    if ($captor === null || !is_array($captor->Subcards ?? null) || !isset($captor->Subcards[$subIdx])) return;
    $sub = $captor->Subcards[$subIdx];
    array_splice($captor->Subcards, $subIdx, 1);
    DoRescueUnit($sub, $captor);
};

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["SHD_076:0"] = function($player, $mzID = '') {
// Unexpected Escape — "Exhaust a unit. You may rescue a captured card guarded by
                          // that unit."
            $targets = SWUAllUnits();
            if (empty($targets)) return;
            SWUQueueChooseTarget(intval($player), $targets, "Exhaust_a_unit", "SHD_076#0");
            return;
};

<?php
// SEC_056
// Cost 1 - Escape Pod - [Vigilance] - Power 0 - HP 3
// Text: When Played: You may have this unit capture a friendly non-Vehicle, non-leader unit.

// ── SEC Phase 3: Capture ─────────────────────────────────────────────────────
// SEC_056 Escape Pod — When Played: you may have THIS unit capture a friendly non-Vehicle, non-leader unit.
$whenPlayedAbilities["SEC_056:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $self = GetZoneObject($mzID);
    $selfUID = SWUObjUID($self, 0);
    $targets = [];
    foreach (array_merge(ZoneSearch("myGroundArena", NonLeaderUnitFilter), ZoneSearch("mySpaceArena", NonLeaderUnitFilter)) as $mz) {
        $o = GetZoneObject($mz);
        if (SWUObjGone($o)) continue;
        if (intval($o->UniqueID ?? 0) === $selfUID) continue;
        if (HasTrait($o->CardID ?? '', 'Vehicle')) continue;
        $targets[] = $mz;
    }
    if (empty($targets)) return;
    SWUQueueMayChooseTarget(intval($player), $targets, "Capture_a_friendly_unit?", "Choose_a_friendly_non-Vehicle_unit", "SEC_056#0|{$selfUID}");
};

$customDQHandlers["SEC_056#0"] = function($player, $parts, $lastDecision) {
    if (SWUDecisionDeclined($lastDecision)) return;
    global $playerID; $playerID = intval($player);
    $captor = SWUFindMzByUID(intval($parts[0] ?? 0));
    if ($captor !== null) DoCaptureUnit(intval($player), $captor, $lastDecision);
};

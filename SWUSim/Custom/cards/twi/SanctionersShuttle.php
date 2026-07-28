<?php
// TWI_213
// Cost 3 - Sanctioner's Shuttle - [Cunning] - Power 2 - HP 3
// Text: Coordinate - When Played: This unit captures an enemy non-leader unit that costs 3 or less. (Gain this ability while you control 3 or more units, including this one.)

// TWI_213 Sanctioner's Shuttle — "Coordinate - When Played: This unit captures an enemy non-leader
// unit that costs 3 or less." (This unit is the captor; enemy target in either arena, cost ≤ 3.)
$whenPlayedAbilities["TWI_213:0"] = function($player, $mzID) {
    if (!IsCoordinateActive(intval($player))) return;
    global $playerID;
    $playerID = intval($player);
    $captor = GetZoneObject($mzID);
    if ($captor === null) return;
    $captorUID = intval($captor->UniqueID ?? -1);
    $targets = [];
    foreach (['theirGroundArena', 'theirSpaceArena'] as $z) {
        foreach (ZoneSearch($z, NonLeaderUnitFilter) as $emz) {
            $eo = GetZoneObject($emz);
            if ($eo !== null && empty($eo->removed) && intval(CardCost($eo->CardID)) <= 3) $targets[] = $emz;
        }
    }
    if (empty($targets)) return;
    SWUQueueChooseTarget(intval($player), $targets,
        "Capture_an_enemy_non-leader_unit_costing_3_or_less", "TWI_213#0|" . $captorUID);
};

$customDQHandlers["TWI_213#0"] = function($player, $parts, $lastDecision) {
    if (SWUDecisionDeclined($lastDecision)) return;
    global $playerID;
    $playerID = intval($player);
    $captorMz = SWUFindMzByUID(intval($parts[0] ?? -1));
    if ($captorMz === null) return;
    DoCaptureUnit(intval($player), $captorMz, $lastDecision);
};

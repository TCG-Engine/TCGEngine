<?php
// SEC_192
// Cost 6 - Grand Moff Tarkin - Taking Krennic's Achievement - [Cunning,Villainy] - Power 2 - HP 6
// Text: When Played: Take control of an enemy non-leader Vehicle unit. When this unit leaves play, that unit's owner takes control of that unit.

// SEC_192 Grand Moff Tarkin — When Played: take control of an enemy non-leader Vehicle unit. The control
// reverts when Tarkin leaves play (SWU_SEC192 link flag + _SWURevertSec192Steals lazy sweep). Mandatory
// (no "may"): if a legal Vehicle exists, take one (choose if several); fizzle if none.
$whenPlayedAbilities["SEC_192:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $tarkin = GetZoneObject($mzID);
    if ($tarkin === null) return;
    $tarkinUID = intval($tarkin->UniqueID ?? 0);
    $targets = [];
    foreach (array_merge(ZoneSearch('theirGroundArena', AnyUnitFilter), ZoneSearch('theirSpaceArena', AnyUnitFilter)) as $mz) {
        $o = GetZoneObject($mz);
        if ($o !== null && empty($o->removed) && !IsLeaderUnit($o) && HasTrait($o->CardID ?? '', 'Vehicle')) $targets[] = $mz;
    }
    if (empty($targets)) return;
    SWUQueueChooseTarget(intval($player), $targets, "Take_control_of_an_enemy_Vehicle_unit", "SEC_192#0|" . $tarkinUID);
};

// Continuation: $lastDecision = chosen enemy Vehicle mzID; $parts[0] = Tarkin's UniqueID.
$customDQHandlers["SEC_192#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    $tarkinUID = intval($parts[0] ?? 0);
    $vehMz = $lastDecision ?? '';
    if ($vehMz === '' || $vehMz === '-' || $vehMz === 'PASS') return;
    $veh = GetZoneObject($vehMz);
    if (SWUObjGone($veh)) return;
    $stolenUID = intval($veh->UniqueID ?? 0);
    $newMz = SWUTakeControlOfUnit(intval($player), $vehMz);
    if ($newMz === '') return;                                  // immune to take-control (LAW_149) → no link
    AddGlobalEffects(intval($player), "SWU_SEC192|{$tarkinUID}|{$stolenUID}");
};

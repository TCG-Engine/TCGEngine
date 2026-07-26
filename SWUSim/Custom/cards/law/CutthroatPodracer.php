<?php
// LAW_213
// Cost 4 - Cutthroat Podracer - [Cunning,Villainy] - Power 4 - HP 4
// Text: When Played: You may deal 2 damage to an exhausted ground unit.

// LAW_213 Cutthroat Podracer — When Played: you may deal 2 damage to an exhausted ground unit.
$whenPlayedAbilities["LAW_213:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $targets = [];
    foreach (SWUAllUnits(null, GroundArena) as $mz) {
        $o = GetZoneObject($mz);
        if ($o !== null && empty($o->removed) && intval($o->Status ?? 0) === 0) $targets[] = $mz;   // exhausted
    }
    if (empty($targets)) return;
    SWUQueueMayChooseTarget(intval($player), $targets, "Deal_2_to_an_exhausted_ground_unit?", "Choose_a_unit", "DEAL_UNIT_DAMAGE|2");
};

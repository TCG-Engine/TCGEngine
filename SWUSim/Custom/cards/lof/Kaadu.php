<?php
// LOF_114
// Cost 4 - Kaadu - [Command] - Power 4 - HP 4
// Text: When Played: You may give another friendly unit Overwhelm for this phase. (When attacking an enemy unit, deal excess damage to the opponent's base.)

// LOF_114 Kaadu — When Played: may give another friendly unit Overwhelm for this phase.
$whenPlayedAbilities["LOF_114:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $self    = GetZoneObject($mzID);
    $selfUID = SWUObjUID($self);
    $targets = [];
    foreach (SWUAllUnits('my') as $mz) {
        $o = GetZoneObject($mz);
        if (SWUObjGone($o) || intval($o->UniqueID ?? -1) === $selfUID) continue;
        $targets[] = $mz;
    }
    if (empty($targets)) return;
    SWUQueueMayChooseTarget(intval($player), $targets, "Give_another_friendly_unit_Overwhelm?", "Choose_a_unit", "GRANT_PHASE_KEYWORD|OVERWHELM^LOF_114");
};

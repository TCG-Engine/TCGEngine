<?php
// TWI_104
// Cost 1 - Obedient Vanguard - [Command] - Power 1 - HP 1
// Text: Raid 1 (This unit gets +1/+0 while attacking.) / When Defeated: You may give a Trooper unit +2/+2 for this phase.

// TWI_104 Obedient Vanguard — "Raid 1. When Defeated: You may give a Trooper unit +2/+2 for this phase."
$whenDefeatedAbilities["TWI_104:0"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    $targets = [];
    foreach (['myGroundArena', 'mySpaceArena', 'theirGroundArena', 'theirSpaceArena'] as $z) {
        foreach (ZoneSearch($z, ['Unit', 'Token Unit', 'Leader Unit']) as $mz) {
            $o = GetZoneObject($mz);
            if ($o !== null && empty($o->removed) && TraitContains($o, 'Trooper')) $targets[] = $mz;
        }
    }
    if (empty($targets)) return;
    SWUQueueMayChooseTarget(intval($player), $targets, "Give_a_Trooper_unit_+2/+2_this_phase?", "Choose_a_Trooper_unit", "APPLY_PHASE_BUFF|2|2|TWI_104");
};

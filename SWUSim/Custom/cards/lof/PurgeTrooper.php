<?php
// LOF_133
// Cost 3 - Purge Trooper - [Aggression,Villainy] - Power 4 - HP 2
// Text: When Played: You may deal 2 damage to a Force unit.

// LOF_133 Purge Trooper — When Played: may deal 2 damage to a Force unit.
$whenPlayedAbilities["LOF_133:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $targets = [];
    foreach (SWUAllUnits() as $mz) {
        $o = GetZoneObject($mz);
        if ($o !== null && empty($o->removed) && TraitContains($o, 'Force')) $targets[] = $mz;
    }
    if (empty($targets)) return;
    SWUQueueMayChooseTarget(intval($player), $targets, "Deal_2_to_a_Force_unit?", "Choose_a_Force_unit", "DEAL_UNIT_DAMAGE|2");
};

<?php
// SOR_132
// Cost 4 - Imperial Interceptor - [Aggression,Villainy] - Power 3 - HP 2
// Text: When Played: You may deal 3 damage to a space unit.

// SOR_132 Imperial Interceptor — When Played: you may deal 3 to a space unit.
$whenPlayedAbilities["SOR_132:0"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    $targets = SWUAllUnits(null, SpaceArena);
    SWUQueueMayChooseTarget(intval($player), $targets,
        "Deal_3_damage_to_a_space_unit?", "Deal_3_to_a_space_unit", "DEAL_UNIT_DAMAGE|3");
};

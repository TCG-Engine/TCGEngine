<?php
// TWI_165
// Cost 6 - Kit Fisto - The Smiling Jedi - [Aggression] - Power 7 - HP 6
// Text: Saboteur / Coordinate - On Attack: You may deal 3 damage to a ground unit. (Gain this ability while you control 3 or more units.)

// TWI_165 Kit Fisto — "Saboteur. Coordinate - On Attack: You may deal 3 damage to a ground unit."
$onAttackAbilities["TWI_165:0"] = function($player, $mzID) {
    if (!IsCoordinateActive(intval($player))) return;
    global $playerID;
    $playerID = intval($player);
    $targets = array_merge(
        ZoneSearch('myGroundArena', ['Unit', 'Token Unit', 'Leader Unit']),
        ZoneSearch('theirGroundArena', ['Unit', 'Token Unit', 'Leader Unit'])
    );
    if (empty($targets)) return;
    SWUQueueMayChooseTarget(intval($player), $targets, "Deal_3_damage_to_a_ground_unit?", "Choose_a_ground_unit", "DEAL_UNIT_DAMAGE|3");
    // Combat owns the after-action.
};

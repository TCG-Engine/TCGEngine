<?php
// TWI_063
// Cost 4 - Vulture Interceptor Wing - [Vigilance] - Power 3 - HP 3
// Text: On Attack: Give an enemy unit -1/-1 for this phase.

// TWI_063 Vulture Interceptor Wing — "On Attack: Give an enemy unit -1/-1 for this phase."
$onAttackAbilities["TWI_063:0"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    $targets = array_merge(
        ZoneSearch('theirGroundArena', ['Unit', 'Token Unit', 'Leader Unit']),
        ZoneSearch('theirSpaceArena', ['Unit', 'Token Unit', 'Leader Unit'])
    );
    if (empty($targets)) return;
    SWUQueueMayChooseTarget(intval($player), $targets, "Give_an_enemy_unit_-1/-1?", "Choose_an_enemy_unit", "APPLY_PHASE_DEBUFF|1|1|TWI_063");
    // Combat owns the after-action.
};

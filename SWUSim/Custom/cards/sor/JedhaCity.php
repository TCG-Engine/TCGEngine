<?php
// SOR_028
// Jedha City - [Cunning] - HP 25
// Text: 
// Epic Action: Give a non-leader unit -4/-0 for this phase.

// SOR_028 Jedha City — Epic Action: Give a non-leader unit -4/-0 for this phase.
$baseAbilities["SOR_028"] = function($player) {
    global $playerID;
    $savedPID = $playerID;
    $playerID = $player;
    $targets = array_merge(
        ZoneSearch("myGroundArena",    NonLeaderUnitFilter),
        ZoneSearch("mySpaceArena",     NonLeaderUnitFilter),
        ZoneSearch("theirGroundArena", NonLeaderUnitFilter),
        ZoneSearch("theirSpaceArena",  NonLeaderUnitFilter)
    );
    $playerID = $savedPID;
    if (empty($targets)) { SWUAfterAction($player); return; }
    SWUQueueChooseTarget(intval($player), $targets, "Give_a_non-leader_unit_-4/-0_for_this_phase", "APPLY_PHASE_DEBUFF|4|0|SOR_028");
    SWUQueueAfterAction($player);
};

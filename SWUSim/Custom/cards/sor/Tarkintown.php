<?php
// SOR_025
// Tarkintown - [Aggression] - HP 25
// Text: 
// Epic Action: Deal 3 damage to a damaged non-leader unit.

// SOR_025 Tarkintown — Epic Action: Deal 3 damage to a damaged non-leader unit.
// ["Unit","Token Unit"] excludes deployed leaders; filter to Damage > 0.
$baseAbilities["SOR_025"] = function($player) {
    global $playerID;
    $savedPID = $playerID;
    $playerID = $player;
    $targets = [];
    foreach (array_merge(
        ZoneSearch("myGroundArena",    NonLeaderUnitFilter),
        ZoneSearch("mySpaceArena",     NonLeaderUnitFilter),
        ZoneSearch("theirGroundArena", NonLeaderUnitFilter),
        ZoneSearch("theirSpaceArena",  NonLeaderUnitFilter)
    ) as $mz) {
        $o = GetZoneObject($mz);
        if (SWUObjGone($o)) continue;
        if (intval($o->Damage ?? 0) > 0) $targets[] = $mz;
    }
    $playerID = $savedPID;
    if (empty($targets)) { SWUAfterAction($player); return; }
    SWUQueueChooseTarget(intval($player), $targets, "Deal_3_to_a_damaged_non-leader_unit", "DEAL_UNIT_DAMAGE|3");
    SWUQueueAfterAction($player);
};

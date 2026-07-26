<?php
// TWI_191
// Cost 1 - Wolf Pack Escort - [Cunning,Heroism] - Power 2 - HP 1
// Text: When Played: You may return a friendly non-leader, non-Vehicle unit to its owner's hand.

// TWI_191 Wolf Pack Escort — "When Played: You may return a friendly non-leader, non-Vehicle unit to its
// owner's hand."
$whenPlayedAbilities["TWI_191:0"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    $targets = [];
    foreach (["myGroundArena", "mySpaceArena"] as $z) {
        foreach (ZoneSearch($z, NonLeaderUnitFilter) as $mz) {
            $o = GetZoneObject($mz);
            if ($o !== null && empty($o->removed) && !HasTrait($o->CardID ?? '', 'Vehicle')) $targets[] = $mz;
        }
    }
    if (empty($targets)) return;
    SWUQueueMayChooseTarget(intval($player), $targets,
        "You_may_return_a_friendly_non-Vehicle_unit_to_hand", "Return_a_friendly_non-Vehicle_unit", "BOUNCE_UNIT");
};

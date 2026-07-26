<?php
// SHD_079
// Rival's Fall
// Text: Defeat a unit.

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["SHD_079:0"] = function($player, $mzID = '') {
// Rival's Fall — "Defeat a unit." (any unit, incl. deployed leaders)
            $targets = array_merge(
                ZoneSearch("myGroundArena",    AnyUnitFilter),
                ZoneSearch("mySpaceArena",     AnyUnitFilter),
                ZoneSearch("theirGroundArena", AnyUnitFilter),
                ZoneSearch("theirSpaceArena",  AnyUnitFilter)
            );
            if (empty($targets)) return;
            SWUQueueChooseTarget(intval($player), $targets, "Defeat_a_unit", "DEFEAT_UNIT");
            return;
};

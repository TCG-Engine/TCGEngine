<?php
// JTL_262
// Evasive Maneuver
// Text: Evasive Maneuver

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["JTL_262:0"] = function($player, $mzID = '') {
// Evasive Maneuver — exhaust a unit.
            global $playerID;
            $playerID = intval($player);
            $targets = array_merge(
                ZoneSearch("myGroundArena", AnyUnitFilter), ZoneSearch("mySpaceArena", AnyUnitFilter),
                ZoneSearch("theirGroundArena", AnyUnitFilter), ZoneSearch("theirSpaceArena", AnyUnitFilter)
            );
            if (empty($targets)) return;
            SWUQueueChooseTarget(intval($player), $targets, "Exhaust_a_unit", "EXHAUST_UNIT");
            return;
};

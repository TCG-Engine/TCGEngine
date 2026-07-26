<?php
// JTL_079
// Out the Airlock
// Text: Give a unit -5/-5 for this phase.

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["JTL_079:0"] = function($player, $mzID = '') {
// Out the Airlock — give a unit -5/-5 for this phase.
            global $playerID;
            $playerID = intval($player);
            $targets = array_merge(
                ZoneSearch("myGroundArena", AnyUnitFilter), ZoneSearch("mySpaceArena", AnyUnitFilter),
                ZoneSearch("theirGroundArena", AnyUnitFilter), ZoneSearch("theirSpaceArena", AnyUnitFilter)
            );
            if (empty($targets)) return;
            SWUQueueChooseTarget(intval($player), $targets, "Give_a_unit_-5/-5_this_phase", "APPLY_PHASE_DEBUFF|5|5|JTL_079");
            return;
};

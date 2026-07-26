<?php
// JTL_229
// Diversion
// Text: Give a unit Sentinel for this phase. (Units in its arena can't attack your non-Sentinel units or your base.)

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["JTL_229:0"] = function($player, $mzID = '') {
// Diversion — give a unit Sentinel for this phase.
            global $playerID;
            $playerID = intval($player);
            $targets = array_merge(
                ZoneSearch("myGroundArena", AnyUnitFilter), ZoneSearch("mySpaceArena", AnyUnitFilter),
                ZoneSearch("theirGroundArena", AnyUnitFilter), ZoneSearch("theirSpaceArena", AnyUnitFilter)
            );
            if (empty($targets)) return;
            SWUQueueChooseTarget(intval($player), $targets, "Give_a_unit_Sentinel_this_phase", "GRANT_PHASE_KEYWORD|JTL_229");
            return;
};

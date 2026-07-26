<?php
// LAW_131
// Incapacitate
// Text: Give a unit -2/-2 for this phase.

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["LAW_131:0"] = function($player, $mzID = '') {
// Incapacitate — "Give a unit -2/-2 for this phase." Any unit.
            global $playerID; $playerID = intval($player);
            $units = array_merge(
                ZoneSearch("myGroundArena",    AnyUnitFilter),
                ZoneSearch("mySpaceArena",     AnyUnitFilter),
                ZoneSearch("theirGroundArena", AnyUnitFilter),
                ZoneSearch("theirSpaceArena",  AnyUnitFilter)
            );
            if (empty($units)) return;
            SWUQueueChooseTarget(intval($player), $units, "Give_a_unit_-2/-2_for_this_phase", "APPLY_PHASE_DEBUFF|2|2|LAW_131");
            return;
};

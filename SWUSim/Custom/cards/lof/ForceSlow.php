<?php
// LOF_217
// Force Slow
// Text: Give an exhausted unit -8/-0 for this phase.

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["LOF_217:0"] = function($player, $mzID = '') {
// Force Slow — "Give an exhausted unit -8/-0 for this phase."
            global $playerID; $playerID = intval($player);
            $targets = [];
            foreach (array_merge(
                ZoneSearch("myGroundArena", AnyUnitFilter), ZoneSearch("mySpaceArena", AnyUnitFilter),
                ZoneSearch("theirGroundArena", AnyUnitFilter), ZoneSearch("theirSpaceArena", AnyUnitFilter)
            ) as $mz) {
                $o = GetZoneObject($mz);
                if (SWUObjGone($o)) continue;
                if (intval($o->Status ?? 0) !== 1) $targets[] = $mz;  // exhausted only
            }
            if (empty($targets)) return;
            SWUQueueChooseTarget(intval($player), $targets, "Give_an_exhausted_unit_-8/-0_this_phase", "APPLY_PHASE_DEBUFF|8|0|LOF_217");
            return;
};

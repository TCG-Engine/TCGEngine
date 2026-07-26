<?php
// LOF_262
// Go Into Hiding
// Text: Choose a unit. It can't be attacked this phase (unless it has Sentinel).

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["LOF_262:0"] = function($player, $mzID = '') {
// Go Into Hiding — "Choose a unit. It can't be attacked this phase (unless it has
                          // Sentinel)." Grants the CANT_BE_ATTACKED phase marker.
            global $playerID; $playerID = intval($player);
            $targets = array_merge(
                ZoneSearch("myGroundArena", AnyUnitFilter), ZoneSearch("mySpaceArena", AnyUnitFilter),
                ZoneSearch("theirGroundArena", AnyUnitFilter), ZoneSearch("theirSpaceArena", AnyUnitFilter)
            );
            if (empty($targets)) return;
            SWUQueueChooseTarget(intval($player), $targets, "Make_a_unit_unattackable_this_phase", "GRANT_PHASE_KEYWORD|CANT_BE_ATTACKED^LOF_262");
            return;
};

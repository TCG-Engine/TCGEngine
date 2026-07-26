<?php
// SOR_078
// Vanquish
// Text: Defeat a non-leader unit.

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["SOR_078:0"] = function($player, $mzID = '') {
// Vanquish — "Defeat a non-leader unit." (["Unit","Token Unit"] excludes leader units.)
            $targets = array_merge(
                ZoneSearch("myGroundArena",    NonLeaderUnitFilter),
                ZoneSearch("mySpaceArena",     NonLeaderUnitFilter),
                ZoneSearch("theirGroundArena", NonLeaderUnitFilter),
                ZoneSearch("theirSpaceArena",  NonLeaderUnitFilter)
            );
            if (empty($targets)) return;
            SWUQueueChooseTarget(intval($player), $targets, "Defeat_a_non-leader_unit", "DEFEAT_UNIT");
            return;
};

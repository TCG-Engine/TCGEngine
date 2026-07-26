<?php
// TWI_077
// Vanquish
// Text: Defeat a non-leader unit.

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["TWI_077:0"] = function($player, $mzID = '') {
// Vanquish — "Defeat a non-leader unit."
            global $playerID; $playerID = intval($player);
            $targets = array_merge(
                ZoneSearch("myGroundArena", NonLeaderUnitFilter), ZoneSearch("mySpaceArena", NonLeaderUnitFilter),
                ZoneSearch("theirGroundArena", NonLeaderUnitFilter), ZoneSearch("theirSpaceArena", NonLeaderUnitFilter)
            );
            if (empty($targets)) return;
            SWUQueueChooseTarget(intval($player), $targets, "Defeat_a_non-leader_unit", "DEFEAT_UNIT");
            return;
};

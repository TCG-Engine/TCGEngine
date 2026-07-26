<?php
// JTL_078
// Direct Hit
// Text: Defeat a non-leader Vehicle unit.

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["JTL_078:0"] = function($player, $mzID = '') {
// Direct Hit — defeat a non-leader Vehicle unit.
            global $playerID;
            $playerID = intval($player);
            $targets = [];
            foreach (array_merge(
                ZoneSearch("myGroundArena", NonLeaderUnitFilter), ZoneSearch("mySpaceArena", NonLeaderUnitFilter),
                ZoneSearch("theirGroundArena", NonLeaderUnitFilter), ZoneSearch("theirSpaceArena", NonLeaderUnitFilter)
            ) as $mz) {
                $o = GetZoneObject($mz);
                if ($o !== null && empty($o->removed) && HasTrait($o->CardID, 'Vehicle')) $targets[] = $mz;
            }
            if (empty($targets)) return;
            SWUQueueChooseTarget(intval($player), $targets, "Defeat_a_non-leader_Vehicle_unit", "DEFEAT_UNIT");
            return;
};

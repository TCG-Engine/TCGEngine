<?php
// SOR_077
// Takedown
// Text: Defeat a unit with 5 or less remaining HP.

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["SOR_077:0"] = function($player, $mzID = '') {
// Takedown — "Defeat a unit with 5 or less remaining HP."
            $targets = [];
            foreach (array_merge(
                ZoneSearch("myGroundArena",    AnyUnitFilter),
                ZoneSearch("mySpaceArena",     AnyUnitFilter),
                ZoneSearch("theirGroundArena", AnyUnitFilter),
                ZoneSearch("theirSpaceArena",  AnyUnitFilter)
            ) as $mz) {
                $o = GetZoneObject($mz);
                if (SWUObjGone($o)) continue;
                if (intval(ObjectCurrentHP($o)) - intval($o->Damage ?? 0) <= 5) $targets[] = $mz;
            }
            if (empty($targets)) return;
            SWUQueueChooseTarget(intval($player), $targets, "Defeat_a_unit_with_5_or_less_remaining_HP", "DEFEAT_UNIT");
            return;
};

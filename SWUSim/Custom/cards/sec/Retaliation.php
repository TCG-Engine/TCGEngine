<?php
// SEC_077
// Retaliation
// Text: Defeat a unit that dealt damage to a base this phase.

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["SEC_077:0"] = function($player, $mzID = '') {
// Retaliation — "Defeat a unit that dealt damage to a base this phase."
            global $playerID; $playerID = intval($player);
            $targets = [];
            foreach (array_merge(
                ZoneSearch("myGroundArena", AnyUnitFilter),    ZoneSearch("mySpaceArena", AnyUnitFilter),
                ZoneSearch("theirGroundArena", AnyUnitFilter), ZoneSearch("theirSpaceArena", AnyUnitFilter)
            ) as $mz) {
                $o = GetZoneObject($mz);
                if (SWUObjGone($o)) continue;
                $ctrl = intval($o->Controller ?? 0);
                if (GlobalEffectCount($ctrl, 'SWU_DEALT_BASEDMG_' . intval($o->UniqueID ?? 0)) > 0) $targets[] = $mz;
            }
            if (empty($targets)) return;
            SWUQueueChooseTarget(intval($player), $targets, "Defeat_a_unit_that_damaged_a_base_this_phase", "DEFEAT_UNIT");
            return;
};

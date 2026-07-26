<?php
// LOF_174
// Ataru Onslaught
// Text: Ready a Force unit with 4 or less power.

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["LOF_174:0"] = function($player, $mzID = '') {
// Ataru Onslaught — "Ready a Force unit with 4 or less power."
            global $playerID; $playerID = intval($player);
            $targets = [];
            foreach (array_merge(ZoneSearch("myGroundArena", AnyUnitFilter), ZoneSearch("mySpaceArena", AnyUnitFilter)) as $mz) {
                $o = GetZoneObject($mz);
                if (SWUObjGone($o)) continue;
                if (TraitContains($o, 'Force') && intval(ObjectCurrentPower($o)) <= 4) $targets[] = $mz;
            }
            if (empty($targets)) return;
            SWUQueueChooseTarget(intval($player), $targets, "Ready_a_Force_unit_with_4_or_less_power", "READY_UNIT");
            return;
};

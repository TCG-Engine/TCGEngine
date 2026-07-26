<?php
// JTL_179
// Koiogran Turn
// Text: Ready a Fighter or Transport unit with 6 or less power.

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["JTL_179:0"] = function($player, $mzID = '') {
// Koiogran Turn — ready a Fighter or Transport unit with 6 or less power.
            global $playerID;
            $playerID = intval($player);
            $targets = [];
            foreach (array_merge(
                ZoneSearch("myGroundArena", AnyUnitFilter), ZoneSearch("mySpaceArena", AnyUnitFilter),
                ZoneSearch("theirGroundArena", AnyUnitFilter), ZoneSearch("theirSpaceArena", AnyUnitFilter)
            ) as $mz) {
                $o = GetZoneObject($mz);
                if (SWUObjGone($o)) continue;
                if ((HasTrait($o->CardID, 'Fighter') || HasTrait($o->CardID, 'Transport')) && ObjectCurrentPower($o) <= 6) $targets[] = $mz;
            }
            if (empty($targets)) return;
            SWUQueueChooseTarget(intval($player), $targets, "Ready_a_Fighter/Transport_unit_with_6_or_less_power", "READY_UNIT");
            return;
};

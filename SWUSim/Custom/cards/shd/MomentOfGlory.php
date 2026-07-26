<?php
// SHD_130
// Moment of Glory
// Text: Give a unit +4/+4 for this phase.

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["SHD_130:0"] = function($player, $mzID = '') {
// Moment of Glory — "Give a unit +4/+4 for this phase."
            global $playerID; $playerID = intval($player);
            $targets = [];
            foreach (['myGroundArena', 'mySpaceArena', 'theirGroundArena', 'theirSpaceArena'] as $z) {
                foreach (ZoneSearch($z, AnyUnitFilter) as $mz) {
                    $o = GetZoneObject($mz);
                    if ($o !== null && empty($o->removed)) $targets[] = $mz;
                }
            }
            if (empty($targets)) return;
            SWUQueueChooseTarget(intval($player), $targets, "Give_a_unit_+4/+4_this_phase", "APPLY_PHASE_BUFF|4|4|SHD_130");
            return;
};

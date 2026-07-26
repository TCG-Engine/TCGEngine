<?php
// SHD_078
// Fell the Dragon
// Text: Defeat a non-leader unit with 5 or more power.

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["SHD_078:0"] = function($player, $mzID = '') {
// Fell the Dragon — "Defeat a non-leader unit with 5 or more power."
            $targets = [];
            foreach (['myGroundArena', 'mySpaceArena', 'theirGroundArena', 'theirSpaceArena'] as $z) {
                foreach (ZoneSearch($z, NonLeaderUnitFilter) as $mz) {
                    $o = GetZoneObject($mz);
                    if ($o !== null && empty($o->removed) && ObjectCurrentPower($o) >= 5) $targets[] = $mz;
                }
            }
            if (empty($targets)) return;
            SWUQueueChooseTarget(intval($player), $targets, "Defeat_a_non-leader_unit_with_5+_power", "DEFEAT_UNIT");
            return;
};

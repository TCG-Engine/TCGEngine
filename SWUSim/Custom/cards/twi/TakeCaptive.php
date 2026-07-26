<?php
// TWI_128
// Take Captive
// Text: A friendly unit captures an enemy non-leader unit in the same arena. (Put the captured card facedown under that unit until that unit leaves play.)

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["TWI_128:0"] = function($player, $mzID = '') {
// (identical reprint)
            global $playerID;
            $playerID = intval($player);
            // Build the list of friendly units that have at least one valid capture target:
            // an enemy NON-LEADER unit in the SAME arena (ground↔ground, space↔space).
            // Friendly Leader Units are allowed as capturers; captured unit must be non-leader.
            $capturers = [];
            foreach (['myGroundArena' => 'theirGroundArena', 'mySpaceArena' => 'theirSpaceArena'] as $myZone => $theirZone) {
                $enemyNonLeaders = array_values(array_filter(
                    ZoneSearch($theirZone, NonLeaderUnitFilter),
                    function($emz) { $eo = GetZoneObject($emz); return $eo !== null && empty($eo->removed); }
                ));
                if (empty($enemyNonLeaders)) continue;
                foreach (ZoneSearch($myZone, AnyUnitFilter) as $fmz) {
                    $fo = GetZoneObject($fmz);
                    if (SWUObjGone($fo)) continue;
                    $capturers[] = $fmz;
                }
            }
            if (empty($capturers)) return;
            SWUQueueChooseTarget(intval($player), array_values(array_unique($capturers)),
                'Choose_a_friendly_unit_to_capture_with', 'SHD_131#0');
            return;
};

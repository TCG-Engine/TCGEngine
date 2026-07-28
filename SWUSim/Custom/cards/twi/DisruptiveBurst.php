<?php
// TWI_075
// Disruptive Burst
// Text: Give each enemy unit -1/-1 for this phase.

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["TWI_075:0"] = function($player, $mzID = '') {
// Disruptive Burst — "Give each enemy unit -1/-1 for this phase."
            global $playerID;
            $playerID = intval($player);
            foreach (['theirGroundArena', 'theirSpaceArena'] as $z) {
                foreach (ZoneSearch($z, ['Unit', 'Token Unit', 'Leader Unit']) as $mz) {
                    $o = GetZoneObject($mz);
                    if ($o !== null && empty($o->removed)) SWUApplyPhaseDebuff($mz, 1, 1, 'TWI_075');
                }
            }
            return;
};

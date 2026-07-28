<?php
// TS26_48
// Cost 4 - Vanquish the Legion - [Vigilance]
// Text: Give each enemy ground unit -2/-2 for this phase.

$whenPlayedAbilities["TS26_48:0"] = function($player, $mzID = '') {
    global $playerID; $playerID = intval($player);
    foreach (ZoneSearch('theirGroundArena', ['Unit', 'Token Unit', 'Leader Unit']) as $mz) {
        $o = GetZoneObject($mz);
        if ($o !== null && empty($o->removed)) SWUApplyPhaseDebuff($mz, 2, 2, 'TS26_48');
    }
};

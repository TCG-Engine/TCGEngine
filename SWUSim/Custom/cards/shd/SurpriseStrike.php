<?php
// SHD_231
// Surprise Strike
// Text: Attack with a unit. It gets +3/+0 for this attack.

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["SHD_231:0"] = function($player, $mzID = '') {
// Surprise Strike — "Attack with a unit. It gets +3/+0 for this attack."
            $targets = [];
            foreach (['myGroundArena', 'mySpaceArena'] as $z) {
                foreach (ZoneSearch($z, AnyUnitFilter) as $mz) {
                    $o = GetZoneObject($mz);
                    if ($o !== null && empty($o->removed) && intval($o->Status ?? 0) === 1) $targets[] = $mz;
                }
            }
            if (empty($targets)) return;
            SWUQueueChooseTarget(intval($player), $targets, "Attack_with_a_unit_(+3/+0)", "SHD_231#0");
            return;
};

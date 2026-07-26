<?php
// TWI_052
// Hello There
// Text: Choose a unit that entered play this phase. It gets -4/-4 for this phase.

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["TWI_052:0"] = function($player, $mzID = '') {
// Hello There — "Choose a unit that entered play this phase. It gets -4/-4 for
                          // this phase." (SWU_PLAYED_UNIT_{uid} marks units that entered this phase.)
            global $playerID;
            $playerID = intval($player);
            $targets = [];
            foreach (['myGroundArena', 'mySpaceArena', 'theirGroundArena', 'theirSpaceArena'] as $z) {
                foreach (ZoneSearch($z, ['Unit', 'Token Unit', 'Leader Unit']) as $mz) {
                    $o = GetZoneObject($mz);
                    if (SWUObjGone($o)) continue;
                    $ctrl = intval($o->Controller ?? 0);
                    if ($ctrl > 0 && GlobalEffectCount($ctrl, 'SWU_PLAYED_UNIT_' . intval($o->UniqueID ?? -1)) > 0) $targets[] = $mz;
                }
            }
            if (empty($targets)) return;
            SWUQueueChooseTarget(intval($player), $targets, "Give_a_unit_that_entered_this_phase_-4/-4", "APPLY_PHASE_DEBUFF|4|4|TWI_052");
            return;
};

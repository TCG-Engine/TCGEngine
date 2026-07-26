<?php
// TWI_126
// Encouraging Leadership
// Text: Give each friendly unit +1/+1 for this phase.

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["TWI_126:0"] = function($player, $mzID = '') {
// Encouraging Leadership — "Give each friendly unit +1/+1 for this phase."
            global $playerID;
            $playerID = intval($player);
            foreach (['myGroundArena', 'mySpaceArena'] as $z) {
                foreach (ZoneSearch($z, ['Unit', 'Token Unit', 'Leader Unit']) as $mz) {
                    $o = GetZoneObject($mz);
                    if ($o !== null && empty($o->removed)) SWUApplyPhaseBuff($mz, 1, 1, 'TWI_126');
                }
            }
            return;
};

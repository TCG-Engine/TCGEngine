<?php
// TWI_103
// Pyrrhic Assault
// Text: For this phase, each friendly unit gains: "When Defeated: Deal 2 damage to an enemy unit."

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["TWI_103:0"] = function($player, $mzID = '') {
// Pyrrhic Assault — "For this phase, each friendly unit gains: 'When Defeated:
                          // Deal 2 damage to an enemy unit.'" Snapshot friendly units in play now and mark
                          // each with the phase-duration TWI_103 grant (read in CollectWhenDefeatedTriggers).
            global $playerID;
            $playerID = intval($player);
            foreach (['myGroundArena', 'mySpaceArena'] as $z) {
                foreach (ZoneSearch($z, ['Unit', 'Token Unit', 'Leader Unit']) as $mz) {
                    $o = GetZoneObject($mz);
                    if ($o !== null && empty($o->removed)) AddTurnEffect($mz, 'TWI_103');
                }
            }
            return;
};

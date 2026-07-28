<?php
// TWI_129
// In Defense of Kamino
// Text: For this phase, each friendly Republic unit gains Restore 2 and: "When Defeated: Create a Clone Trooper token."

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["TWI_129:0"] = function($player, $mzID = '') {
// In Defense of Kamino — "For this phase, each friendly Republic unit gains
                          // Restore 2 and: 'When Defeated: Create a Clone Trooper token.'" One marker per
                          // unit: registry row grants Restore 2; CollectWhenDefeatedTriggers reads it too.
            global $playerID;
            $playerID = intval($player);
            foreach (['myGroundArena', 'mySpaceArena'] as $z) {
                foreach (ZoneSearch($z, ['Unit', 'Token Unit', 'Leader Unit']) as $mz) {
                    $o = GetZoneObject($mz);
                    if ($o !== null && empty($o->removed) && HasTrait($o->CardID ?? '', 'Republic')) AddTurnEffect($mz, 'TWI_129');
                }
            }
            return;
};

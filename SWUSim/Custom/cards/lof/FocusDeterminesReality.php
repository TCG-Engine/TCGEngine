<?php
// LOF_152
// Focus Determines Reality
// Text: Each friendly Force unit gains Raid 1 and Saboteur for this phase.

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["LOF_152:0"] = function($player, $mzID = '') {
// Focus Determines Reality — "Each friendly Force unit gains Raid 1 and Saboteur
                        // for this phase."
            global $playerID; $playerID = intval($player);
            foreach (SWUFriendlyUnits() as $mz) {
                $o = GetZoneObject($mz);
                if (SWUObjGone($o)) continue;
                if (TraitContains($o, 'Force')) {
                    AddTurnEffect($mz, 'LOF_152');          // Raid 1 (registry token), this phase
                    AddTurnEffect($mz, 'SABOTEUR^LOF_152'); // Saboteur, this phase (source = LOF_152)
                }
            }
            return;
};

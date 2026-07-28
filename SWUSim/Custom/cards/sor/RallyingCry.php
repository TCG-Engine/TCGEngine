<?php
// SOR_154
// Rallying Cry
// Text: Each friendly unit gains Raid 2 this phase. (They get +2/+0 while attacking.)

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["SOR_154:0"] = function($player, $mzID = '') {
// Rallying Cry — "Each friendly unit gains Raid 2 this phase."
            foreach (array_merge(
                ZoneSearch("myGroundArena", AnyUnitFilter),
                ZoneSearch("mySpaceArena",  AnyUnitFilter)
            ) as $mz) {
                AddTurnEffect($mz, "SOR_154");   // CardID token; Raid value 2 comes from the registry, this phase
            }
            return;
};

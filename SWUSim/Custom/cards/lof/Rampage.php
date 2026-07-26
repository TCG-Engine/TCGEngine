<?php
// LOF_127
// Rampage
// Text: Each friendly Creature unit gets +2/+2 for this phase.

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["LOF_127:0"] = function($player, $mzID = '') {
// Rampage — "Each friendly Creature unit gets +2/+2 for this phase."
            global $playerID; $playerID = intval($player);
            foreach (array_merge(ZoneSearch("myGroundArena", AnyUnitFilter), ZoneSearch("mySpaceArena", AnyUnitFilter)) as $mz) {
                $o = GetZoneObject($mz);
                if (SWUObjGone($o)) continue;
                if (HasTrait($o->CardID ?? '', 'Creature')) SWUApplyPhaseBuff($mz, 2, 2, 'LOF_127');
            }
            return;
};

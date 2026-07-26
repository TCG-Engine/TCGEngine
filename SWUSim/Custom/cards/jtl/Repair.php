<?php
// JTL_075
// Repair
// Text: Heal 3 damage from a unit or base.

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["JTL_075:0"] = function($player, $mzID = '') {
// Repair — heal 3 damage from a unit or base.
            global $playerID;
            $playerID = intval($player);
            $targets = array_merge(
                ZoneSearch("myGroundArena", AnyUnitFilter), ZoneSearch("mySpaceArena", AnyUnitFilter),
                ZoneSearch("theirGroundArena", AnyUnitFilter), ZoneSearch("theirSpaceArena", AnyUnitFilter),
                ['myBase-0', 'theirBase-0']
            );
            SWUQueueChooseTarget(intval($player), $targets, "Heal_3_from_a_unit_or_base", "HEAL_TARGET|3");
            return;
};

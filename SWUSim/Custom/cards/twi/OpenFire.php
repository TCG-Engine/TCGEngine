<?php
// TWI_174
// Open Fire
// Text: Deal 4 damage to a unit.

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["TWI_174:0"] = function($player, $mzID = '') {
// Open Fire — "Deal 4 damage to a unit."
            global $playerID;
            $playerID = intval($player);
            $targets = array_merge(
                ZoneSearch("myGroundArena", AnyUnitFilter), ZoneSearch("mySpaceArena", AnyUnitFilter),
                ZoneSearch("theirGroundArena", AnyUnitFilter), ZoneSearch("theirSpaceArena", AnyUnitFilter)
            );
            if (empty($targets)) return;
            SWUQueueChooseTarget(intval($player), $targets, "Deal_4_damage_to_a_unit", "DEAL_UNIT_DAMAGE|4");
            return;
};

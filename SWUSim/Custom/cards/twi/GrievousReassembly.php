<?php
// TWI_073
// Grievous Reassembly
// Text: Heal 3 damage from a unit. Create a Battle Droid token.

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["TWI_073:0"] = function($player, $mzID = '') {
// Grievous Reassembly — "Heal 3 damage from a unit. Create a Battle Droid token."
            global $playerID;
            $playerID = intval($player);
            // Collect heal targets BEFORE creating the token (so the new token isn't offered).
            $targets = array_merge(
                ZoneSearch("myGroundArena", AnyUnitFilter), ZoneSearch("mySpaceArena", AnyUnitFilter),
                ZoneSearch("theirGroundArena", AnyUnitFilter), ZoneSearch("theirSpaceArena", AnyUnitFilter)
            );
            if (!empty($targets)) SWUQueueChooseTarget(intval($player), $targets, "Heal_3_damage_from_a_unit", "HEAL_TARGET|3");
            SWUCreateUnitToken(intval($player), 'TWI_T01'); // Battle Droid (unconditional)
            return;
};

<?php
// SOR_073
// Moment of Peace
// Text: Give a Shield token to a unit.

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["SOR_073:0"] = function($player, $mzID = '') {
// Moment of Peace — "Give a Shield token to a unit."
            $targets = array_merge(
                ZoneSearch("myGroundArena",    AnyUnitFilter),
                ZoneSearch("mySpaceArena",     AnyUnitFilter),
                ZoneSearch("theirGroundArena", AnyUnitFilter),
                ZoneSearch("theirSpaceArena",  AnyUnitFilter)
            );
            if (empty($targets)) return;
            SWUQueueChooseTarget(intval($player), $targets, "Give_a_Shield_token_to_a_unit", "GIVE_SHIELD");
            return;
};

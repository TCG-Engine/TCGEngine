<?php
// LOF_041
// Drain Essence
// Text: Deal 2 damage to a unit. The Force is with you (create your Force token).

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["LOF_041:0"] = function($player, $mzID = '') {
// Drain Essence — "Deal 2 damage to a unit. The Force is with you."
            // The Force creation is unconditional (separate sentence); the deal-2 fizzles cleanly with
            // no units in play.
            TheForceIsWithYou(intval($player));
            $targets = array_merge(
                ZoneSearch("myGroundArena",    AnyUnitFilter),
                ZoneSearch("mySpaceArena",     AnyUnitFilter),
                ZoneSearch("theirGroundArena", AnyUnitFilter),
                ZoneSearch("theirSpaceArena",  AnyUnitFilter)
            );
            if (empty($targets)) return;
            SWUQueueChooseTarget(intval($player), $targets, "Deal_2_damage_to_a_unit", "DEAL_UNIT_DAMAGE|2");
            return;
};

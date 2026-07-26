<?php
// LOF_263
// Last Words
// Text: If a friendly unit was defeated this phase, give 2 Experience tokens to a unit.

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["LOF_263:0"] = function($player, $mzID = '') {
// Last Words — "If a friendly unit was defeated this phase, give 2 Experience
                          // tokens to a unit."
            global $playerID; $playerID = intval($player);
            if (GlobalEffectCount(intval($player), 'SWU_FRIENDLY_DEFEATED') <= 0) return;
            $targets = array_merge(
                ZoneSearch("myGroundArena", AnyUnitFilter), ZoneSearch("mySpaceArena", AnyUnitFilter),
                ZoneSearch("theirGroundArena", AnyUnitFilter), ZoneSearch("theirSpaceArena", AnyUnitFilter)
            );
            if (empty($targets)) return;
            SWUQueueChooseTarget(intval($player), $targets, "Give_2_Experience_tokens_to_a_unit", "GIVE_EXPERIENCE|2");
            return;
};

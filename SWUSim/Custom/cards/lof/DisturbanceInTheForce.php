<?php
// LOF_216
// Disturbance in the Force
// Text: If a friendly unit left play this phase, the Force is with you (create your Force token) and you may give a Shield token to a unit.

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["LOF_216:0"] = function($player, $mzID = '') {
// Disturbance in the Force — "If a friendly unit left play this phase, the Force
                          // is with you and you may give a Shield token to a unit."
            if (GlobalEffectCount(intval($player), 'SWU_FRIENDLY_LEFT_PLAY') <= 0) return;
            TheForceIsWithYou(intval($player));
            $targets = array_merge(
                ZoneSearch("myGroundArena",    AnyUnitFilter),
                ZoneSearch("mySpaceArena",     AnyUnitFilter),
                ZoneSearch("theirGroundArena", AnyUnitFilter),
                ZoneSearch("theirSpaceArena",  AnyUnitFilter)
            );
            if (empty($targets)) return;
            SWUQueueMayChooseTarget(intval($player), $targets,
                "Give_a_Shield_token_to_a_unit?", "Choose_a_unit_to_Shield", "GIVE_SHIELD");
            return;
};

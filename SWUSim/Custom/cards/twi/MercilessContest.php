<?php
// TWI_238
// Merciless Contest
// Text: Each player chooses a non-leader unit they control. Defeat those units.

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["TWI_238:0"] = function($player, $mzID = '') {
// Merciless Contest — "Each player chooses a non-leader unit they control.
                          // Defeat those units." Caster picks + defeats one; opponent picks + defeats one.
            global $playerID; $playerID = intval($player);
            $mine = array_merge(ZoneSearch("myGroundArena", NonLeaderUnitFilter), ZoneSearch("mySpaceArena", NonLeaderUnitFilter));
            if (!empty($mine)) SWUQueueChooseTarget(intval($player), $mine, "Choose_your_non-leader_unit_to_defeat", "DEFEAT_UNIT");
            DecisionQueueController::AddDecision(intval($player), "CUSTOM", "OPP_DEFEAT_OWN_UNIT|1", 1);
            return;
};

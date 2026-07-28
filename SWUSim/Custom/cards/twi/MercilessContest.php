<?php
// TWI_238
// Merciless Contest
// Text: Each player chooses a non-leader unit they control. Defeat those units.

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["TWI_238:0"] = function($player, $mzID = '') {
// Merciless Contest — "Each player chooses a non-leader unit they control.
                          // Defeat those units." Caster picks + defeats one; opponent picks + defeats one.
            SWUOfferUnitTarget($player, $mzID, [
                'continuation' => 'DEFEAT_UNIT', 'side' => 'my', 'nonLeader' => true,
                'prompt' => "Choose_your_non-leader_unit_to_defeat",
            ]);
            DecisionQueueController::AddDecision(intval($player), "CUSTOM", "OPP_DEFEAT_OWN_UNIT|1", 1);
            return;
};

<?php
// TWI_238
// Merciless Contest
// Text: Each player chooses a non-leader unit they control. Defeat those units.

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["TWI_238:0"] = function($player, $mzID = '') {
// Merciless Contest — "Each player chooses a non-leader unit they control.
                          // Defeat those units." Caster picks + defeats one; opponent picks + defeats one.
            SWUOfferUnitTarget($player, $mzID, [
                // 'my', NOT 'friendly': the text is "a non-leader unit THEY CONTROL", so each
                // player picks from their OWN board. A teammate's unit is friendly but not
                // controlled by you (spec §2) — widening this would let you defeat their unit.
                'continuation' => 'DEFEAT_UNIT', 'side' => 'my', 'nonLeader' => true,
                'prompt' => "Choose_your_non-leader_unit_to_defeat",
            ]);
            // ⚠ "EACH PLAYER chooses" is a LOOP, not a pick. The caster's own half is the offer above;
            // this covers every OPPONENT, one resolution each. Do NOT use OPP_DEFEAT_OWN_UNIT here —
            // that is the "AN opponent" PICKER, which would both prompt the caster for a seat and hit
            // only one of them at 3+ seats.
            DecisionQueueController::AddDecision(intval($player), "CUSTOM", "EACH_OPPONENT_DEFEATS_OWN_UNIT|1", 1);
            return;
};

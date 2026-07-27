<?php
// SEC_182
// Cost 4 - Charged with Treason - [Aggression]
// Text: You may disclose AggressionAggression (reveal cards from your hand with these aspect icons among them). If you do, deal 5 damage to a unit.

// SEC_182 Charged with Treason — disclose succeeded → choose a unit, deal 5 to it.
$customDQHandlers["SEC_182#0"] = function($player, $parts, $lastDecision) {
    SWUOfferUnitTarget($player, '', [
        'continuation' => 'DEAL_UNIT_DAMAGE', 'amount' => 5, 'prompt' => "Deal_5_damage_to_a_unit",
    ]);
};

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["SEC_182:0"] = function($player, $mzID = '') {
// Charged with Treason — "You may disclose AggressionAggression → deal 5 to a unit."
            SWUQueueDisclose(intval($player), ['Aggression', 'Aggression'], "SEC_182#0",
                "Disclose_AggressionAggression_to_deal_5_to_a_unit");
            return;
};

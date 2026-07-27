<?php
// TWI_157
// Cost 1 - Disaffected Senator - [Aggression] - Power 0 - HP 4
// Text: Action [2 resources, Exhaust]: Deal 2 damage to a base.

// TWI_157 Disaffected Senator — "Action [2 resources, Exhaust]: Deal 2 damage to a base." (Registered
// after the `$unitAbilities = []` reset so the entry isn't wiped.)
$unitActionCostKind["TWI_157"] = 'exhaust';

$unitActionResourceCosts["TWI_157"] = 2;

$unitAbilities["TWI_157"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    SWUOfferBaseTarget(intval($player), ['continuation'=>'DEAL_BASE_DAMAGE','amount'=>2,'prompt'=>"Deal_2_damage_to_a_base"]);
    DecisionQueueController::AddDecision(intval($player), "CUSTOM", "SWU_AFTER_ACTION", 1);
};

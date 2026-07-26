<?php
// TWI_056
// Cost 1 - Compassionate Senator - [Vigilance] - Power 0 - HP 4
// Text: Action [2 resources, Exhaust]: Heal 2 damage from a unit or base.

// TWI_056 Compassionate Senator — "Action [2 resources, Exhaust]: Heal 2 damage from a unit or base."
$unitActionCostKind["TWI_056"] = 'exhaust';

$unitActionResourceCosts["TWI_056"] = 2;

$unitAbilities["TWI_056"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    SWUQueueChooseTarget(intval($player), _SWUAllUnitsAndBases(intval($player)), "Heal_2_damage_from_a_unit_or_base", "HEAL_TARGET|2");
    DecisionQueueController::AddDecision(intval($player), "CUSTOM", "SWU_AFTER_ACTION", 1);
};

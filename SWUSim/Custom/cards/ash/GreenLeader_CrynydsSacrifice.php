<?php
// ASH_153
// Cost 2 - Green Leader - Crynyd's Sacrifice - [Aggression,Heroism] - Power 3 - HP 1
// Text: When Defeated: You may deal 2 damage to a unit.

// ASH_153 Green Leader — When Defeated: you may deal 2 damage to a unit.
$whenDefeatedAbilities["ASH_153:0"] = function($player, $mzID) {
    SWUOfferUnitTarget($player, $mzID, [
        'continuation' => 'DEAL_UNIT_DAMAGE', 'amount' => 2, 'may' => true,
        'question' => "Deal_2_damage_to_a_unit?", 'prompt' => "Choose_a_unit",
    ]);
};

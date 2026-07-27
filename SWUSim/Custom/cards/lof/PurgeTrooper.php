<?php
// LOF_133
// Cost 3 - Purge Trooper - [Aggression,Villainy] - Power 4 - HP 2
// Text: When Played: You may deal 2 damage to a Force unit.

// LOF_133 Purge Trooper — When Played: may deal 2 damage to a Force unit.
$whenPlayedAbilities["LOF_133:0"] = function($player, $mzID) {
    SWUOfferUnitTarget(intval($player), $mzID, [
        'continuation' => 'DEAL_UNIT_DAMAGE', 'amount' => 2, 'traits' => 'Force', 'may' => true,
        'question' => "Deal_2_to_a_Force_unit?", 'prompt' => "Choose_a_Force_unit",
    ]);
};

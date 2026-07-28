<?php
// TWI_165
// Cost 6 - Kit Fisto - The Smiling Jedi - [Aggression] - Power 7 - HP 6
// Text: Saboteur / Coordinate - On Attack: You may deal 3 damage to a ground unit. (Gain this ability while you control 3 or more units.)

// TWI_165 Kit Fisto — "Saboteur. Coordinate - On Attack: You may deal 3 damage to a ground unit."
$onAttackAbilities["TWI_165:0"] = function($player, $mzID) {
    if (!IsCoordinateActive(intval($player))) return;
    SWUOfferUnitTarget($player, $mzID, [
        'continuation' => 'DEAL_UNIT_DAMAGE', 'amount' => 3, 'arena' => 'Ground', 'may' => true,
        'question' => "Deal_3_damage_to_a_ground_unit?", 'prompt' => "Choose_a_ground_unit",
    ]);
    // Combat owns the after-action.
};

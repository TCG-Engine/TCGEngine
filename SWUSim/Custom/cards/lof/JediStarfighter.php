<?php
// LOF_144
// Cost 2 - Jedi Starfighter - [Aggression,Heroism] - Power 1 - HP 4
// Text: On Attack: You may deal 1 damage to a space unit.

// LOF_144 Jedi Starfighter — On Attack: may deal 1 damage to a space unit.
$onAttackAbilities["LOF_144:0"] = function($player, $mzID) {
    SWUOfferUnitTarget(intval($player), $mzID, [
        'continuation' => 'DEAL_UNIT_DAMAGE', 'amount' => 1, 'arena' => 'Space', 'may' => true,
        'question' => "Deal_1_to_a_space_unit?", 'prompt' => "Deal_1_damage_to_a_space_unit",
    ]);
};

<?php
// TWI_038
// Cost 8 - Providence Destroyer - [Vigilance,Villainy] - Power 5 - HP 7
// Text: Exploit 2 (While playing this card, defeat up to 2 units you control. This card costs 2 resources less for each unit defeated this way.) / On Attack: Give an enemy space unit -2/-2 for this phase.

// TWI_038 Providence Destroyer — "On Attack: Give an enemy space unit -2/-2 for this phase."
$onAttackAbilities["TWI_038:0"] = function($player, $mzID) {
    SWUOfferUnitTarget(intval($player), $mzID, [
        'continuation' => 'APPLY_PHASE_DEBUFF|2|2|TWI_038', 'side' => 'their', 'arena' => 'Space', 'may' => true,
        'question' => "Give_an_enemy_space_unit_-2/-2?", 'prompt' => "Choose_an_enemy_space_unit",
    ]);
    // Combat owns the after-action.
};

<?php
// TWI_104
// Cost 1 - Obedient Vanguard - [Command] - Power 1 - HP 1
// Text: Raid 1 (This unit gets +1/+0 while attacking.) / When Defeated: You may give a Trooper unit +2/+2 for this phase.

// TWI_104 Obedient Vanguard — "Raid 1. When Defeated: You may give a Trooper unit +2/+2 for this phase."
$whenDefeatedAbilities["TWI_104:0"] = function($player, $mzID) {
    SWUOfferUnitTarget(intval($player), $mzID, [
        'continuation' => 'APPLY_PHASE_BUFF|2|2|TWI_104', 'side' => 'any', 'traits' => 'Trooper', 'may' => true,
        'question' => "Give_a_Trooper_unit_+2/+2_this_phase?", 'prompt' => "Choose_a_Trooper_unit",
    ]);
};

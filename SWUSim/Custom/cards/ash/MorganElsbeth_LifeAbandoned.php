<?php
// ASH_050
// Cost 6 - Morgan Elsbeth - Life Abandoned - [Vigilance,Villainy] - Power 5 - HP 6
// Text: Support (When you play this unit, you may attack with another unit. It gains this unit's other abilities for this attack.) / When Defeated: You may give a unit -2/-2 for this phase.

// ASH_050 Morgan Elsbeth — When Defeated: you may give a unit -2/-2 for this phase. (When Defeated is
// NOT lent by Support; fires only on Morgan's own defeat.)
$whenDefeatedAbilities["ASH_050:0"] = function($player, $mzID) {
    SWUOfferUnitTarget($player, $mzID, [
        'continuation' => 'APPLY_PHASE_DEBUFF|2|2|ASH_050', 'side' => 'any', 'may' => true,
        'question' => "Give_a_unit_-2/-2_this_phase?", 'prompt' => "Choose_a_unit",
    ]);
};

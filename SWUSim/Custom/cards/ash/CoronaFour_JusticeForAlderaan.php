<?php
// ASH_043
// Cost 2 - Corona Four - Justice for Alderaan - [Cunning,Vigilance,Heroism] - Power 2 - HP 3
// Text: On Attack: You may give a unit -2/-0 for this phase. / When Defeated: You may defeat a non-leader unit with 0 power.

// ASH_043 Corona Four — On Attack: you may give a unit -2/-0 for this phase. When Defeated: you may defeat
// a non-leader unit with 0 power.
$onAttackAbilities["ASH_043:0"] = function($player, $mzID) {
    SWUOfferUnitTarget($player, $mzID, [
        'continuation' => 'APPLY_PHASE_DEBUFF|2|0|ASH_043', 'side' => 'any', 'may' => true,
        'question' => "Give_a_unit_-2/-0_this_phase?", 'prompt' => "Choose_a_unit",
    ]);
};

$whenDefeatedAbilities["ASH_043:0"] = function($player, $mzID) {
    SWUOfferUnitTarget($player, $mzID, [
        'continuation' => 'DEFEAT_UNIT', 'nonLeader' => true,
        'extraFilter' => fn($o) => intval(ObjectCurrentPower($o)) === 0,
        'question' => "Defeat_a_non-leader_unit_with_0_power?", 'prompt' => "Choose_a_unit",
    ]);
};

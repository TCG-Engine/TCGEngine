<?php
// LOF_258
// Cost 2 - Peli Motto - I Should Charge You More - Power 1 - HP 4
// Text: On Attack: Give an Experience token to a friendly Vehicle or Droid unit.

// LOF_258 Peli Motto — On Attack: give an Experience token to a friendly Vehicle or Droid unit.
$onAttackAbilities["LOF_258:0"] = function($player, $mzID) {
    GiveTokenUpgrade($player, $mzID, [
        'traits' => ['Vehicle', 'Droid'], 'may' => true,
        'question' => "Give_Exp_to_a_friendly_Vehicle_or_Droid_unit?",
        'prompt'   => "Choose_a_unit",
    ]);
};

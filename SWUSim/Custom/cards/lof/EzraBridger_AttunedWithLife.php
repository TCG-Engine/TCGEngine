<?php
// LOF_046
// Cost 3 - Ezra Bridger - Attuned With Life - [Vigilance,Heroism] - Power 3 - HP 5
// Text: On Attack: You may give an Experience token to another Creature or Spectre unit.

// LOF_046 Ezra Bridger — On Attack: may give an Experience token to another Creature or Spectre unit.
$onAttackAbilities["LOF_046:0"] = function($player, $mzID) {
    GiveTokenUpgrade($player, $mzID, [
        'traits' => ['Creature', 'Spectre'], 'may' => true, 'excludeSelf' => true, 'friendlyOnly' => false,
        'question' => "Give_Exp_to_another_Creature_or_Spectre_unit?",
        'prompt'   => "Choose_a_unit",
    ]);
};

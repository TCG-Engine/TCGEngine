<?php
// LOF_250
// Cost 4 - Medical Frigate - [Heroism] - Power 3 - HP 6
// Text: On Attack: You may heal 2 damage from another unit.

// LOF_250 Medical Frigate — On Attack: may heal 2 damage from another unit.
$onAttackAbilities["LOF_250:0"] = function($player, $mzID) {
    SWUOfferUnitTarget($player, $mzID, ['continuation'=>'HEAL_TARGET','amount'=>2,'may'=>true,'excludeSelf'=>true,
        'question'=>"Heal_2_from_another_unit?",'prompt'=>"Choose_a_unit"]);
};

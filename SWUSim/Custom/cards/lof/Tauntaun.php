<?php
// LOF_064
// Cost 3 - Tauntaun - [Vigilance] - Power 3 - HP 3
// Text: When Defeated: You may give a Shield token to a damaged non-Vehicle unit.

// LOF_064 Tauntaun — When Defeated: may give a Shield token to a damaged non-Vehicle unit.
$whenDefeatedAbilities["LOF_064:0"] = function($player, $mzID) {
    SWUOfferUnitTarget($player, $mzID, ['continuation'=>'GIVE_SHIELD','may'=>true,'notTraits'=>['Vehicle'],
        'extraFilter'=>fn($o)=>intval($o->Damage ?? 0) > 0,
        'question'=>"Give_a_Shield_to_a_damaged_non-Vehicle_unit?",'prompt'=>"Choose_a_unit"]);
};

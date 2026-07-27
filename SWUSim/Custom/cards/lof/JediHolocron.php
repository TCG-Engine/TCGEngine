<?php
// LOF_051
// Cost 1 - Jedi Holocron - [Vigilance,Heroism] - Upgrade Power 1 - Upgrade HP 1
// Text: Attach to a Force unit. / Attached unit gains: "On Attack: You may heal 3 damage from another unit."

// LOF_051 Jedi Holocron — attached unit gains "On Attack: may heal 3 damage from another unit."
$onAttackAbilities["LOF_051:0"] = function($player, $mzID) {
    SWUOfferUnitTarget($player, $mzID, ['continuation'=>'HEAL_TARGET','amount'=>3,'may'=>true,'excludeSelf'=>true,
        'question'=>"Heal_3_from_another_unit?",'prompt'=>"Choose_a_unit"]);
};

<?php
// IBH_013
// Cost 3 - Recovery - [Heroism]
// Text: Heal 5 damage from a unit.

$whenPlayedAbilities["IBH_013:0"] = function($player, $mzID = '') {
    SWUOfferUnitTarget($player, $mzID, ['continuation'=>'HEAL_TARGET','amount'=>5,'prompt'=>"Heal_5_damage_from_a_unit"]);
};

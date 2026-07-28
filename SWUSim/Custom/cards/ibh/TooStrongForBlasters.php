<?php
// IBH_066 / IBH_091
// Cost 1 - Too Strong for Blasters - [Vigilance]
// Text: Heal 2 damage from a unit.

$whenPlayedAbilities["IBH_066:0"] = function($player, $mzID = '') {
    SWUOfferUnitTarget($player, $mzID, ['continuation'=>'HEAL_TARGET','amount'=>2,'prompt'=>"Heal_2_damage_from_a_unit"]);
};
$whenPlayedAbilities["IBH_091:0"] = $whenPlayedAbilities["IBH_066:0"];

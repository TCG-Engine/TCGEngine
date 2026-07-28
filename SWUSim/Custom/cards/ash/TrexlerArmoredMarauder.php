<?php
// ASH_082
// Cost 6 - Trexler Armored Marauder - [Vigilance] - Power 5 - HP 6
// Text: Grit (This unit gets +1/+0 for each damage on it.) / When Played: You may give a Shield token to a unit that costs 3 or less.

// ASH_082 Trexler Armored Marauder — Grit (keyword) + When Played: you may give a Shield token to a unit
// that costs 3 or less.
$whenPlayedAbilities["ASH_082:0"] = function($player, $mzID) {
    SWUOfferUnitTarget($player, $mzID, ['continuation'=>'GIVE_SHIELD','may'=>true,
        'extraFilter'=>fn($o)=>intval(CardCost($o->CardID ?? '')) <= 3,
        'question'=>"Give_a_Shield_to_a_unit_that_costs_3_or_less?",'prompt'=>"Choose_a_unit"]);
};

<?php
// LOF_242
// Cost 1 - Refugee of The Path - [Heroism] - Power 0 - HP 3
// Text: When Played: You may give a Shield token to a unit with Sentinel.

// LOF_242 Refugee of The Path — When Played: may give a Shield token to a unit with Sentinel.
$whenPlayedAbilities["LOF_242:0"] = function($player, $mzID) {
    SWUOfferUnitTarget($player, $mzID, ['continuation'=>'GIVE_SHIELD','may'=>true,
        'extraFilter'=>fn($o)=>HasKeyword_Sentinel($o),
        'question'=>"Give_a_Shield_to_a_Sentinel_unit?",'prompt'=>"Choose_a_Sentinel_unit"]);
};

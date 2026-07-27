<?php
// LAW_091
// Cost 2 - Val - It's Been a Ride, Babe - [Cunning,Vigilance] - Power 2 - HP 4
// Text: When Played: Give a Shield token to another friendly unit. / When Defeated: Give a Shield token to an enemy unit.

// LAW_091 Val — When Played: Shield to another friendly unit. When Defeated: Shield to an enemy unit.
$whenPlayedAbilities["LAW_091:0"] = function($player, $mzID) {
    GiveTokenUpgrade($player, $mzID, ['token'=>'SHIELD','excludeSelf'=>true,'prompt'=>"Give_a_Shield_token_to_another_friendly_unit"]);
};

$whenDefeatedAbilities["LAW_091:0"] = function($player, $mzID) {
    SWUOfferUnitTarget($player, $mzID, ['continuation'=>'GIVE_SHIELD','side'=>'their','prompt'=>"Give_a_Shield_token_to_an_enemy_unit"]);
};

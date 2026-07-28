<?php
// TWI_049
// Cost 6 - Knight of the Republic - [Vigilance,Heroism] - Power 4 - HP 7
// Text: When this unit is attacked: Create a Clone Trooper token.

// TWI_049 Knight of the Republic — "When this unit is attacked: Create a Clone Trooper token." (On Defense.)
$onDefenseAbilities["TWI_049:0"] = function($player, $mzID) {
    SWUCreateUnitToken(intval($player), 'TWI_T02');
};

<?php
// TWI_043
// Cost 2 - Outspoken Representative - [Vigilance,Heroism] - Power 0 - HP 3
// Text: While you control another Republic unit, this unit gains Sentinel. (Units in this arena can't attack your non-Sentinel units or your base.) / When Defeated: Create a Clone Trooper token.

// TWI_043 Outspoken Representative — "…When Defeated: Create a Clone Trooper token." (the Sentinel-
// while-Republic half is in HasConditionalKeyword_Sentinel.)
$whenDefeatedAbilities["TWI_043:0"] = function($player, $mzID) {
    SWUCreateUnitTokens(intval($player), 'TWI_T02', 1);
};

<?php
// TWI_032
// Cost 2 - Wartime Trade Official - [Vigilance,Villainy] - Power 1 - HP 3
// Text: When Defeated: Create a Battle Droid token.

// TWI_032 Wartime Trade Official / TWI_079 Confederate Courier — "When Defeated: Create a Battle Droid token."
$whenDefeatedAbilities["TWI_032:0"] = $whenDefeatedAbilities["TWI_079:0"] = function($player, $mzID) {
    SWUCreateUnitToken(intval($player), 'TWI_T01');
};

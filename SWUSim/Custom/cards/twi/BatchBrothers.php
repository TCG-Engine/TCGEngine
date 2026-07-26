<?php
// TWI_144
// Cost 3 - Batch Brothers - [Aggression,Heroism] - Power 2 - HP 1
// Text: When Played: Create a Clone Trooper token.

// TWI_144 Batch Brothers — "When Played: Create a Clone Trooper token."
$whenPlayedAbilities["TWI_144:0"] = function($player, $mzID) {
    SWUCreateUnitToken(intval($player), 'TWI_T02');
};

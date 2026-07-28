<?php
// TWI_095
// Cost 5 - Pelta Supply Frigate - [Command,Heroism] - Power 3 - HP 6
// Text: Coordinate - When Played: Create a Clone Trooper token. (Gain this ability while you control 3 or more units, including this one.)

// TWI_095 Pelta Supply Frigate — "Coordinate - When Played: Create a Clone Trooper token." (including
// this one → the just-played frigate counts toward the 3.)
$whenPlayedAbilities["TWI_095:0"] = function($player, $mzID) {
    if (IsCoordinateActive(intval($player))) SWUCreateUnitTokens(intval($player), 'TWI_T02', 1);
};

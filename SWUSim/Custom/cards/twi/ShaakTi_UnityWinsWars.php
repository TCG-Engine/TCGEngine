<?php
// TWI_094
// Cost 4 - Shaak Ti - Unity Wins Wars - [Command,Heroism] - Power 3 - HP 4
// Text: Each friendly token unit gets +1/+0. / On Attack: Create a Clone Trooper token.

// TWI_094 Shaak Ti — "Each friendly token unit gets +1/+0. On Attack: Create a Clone Trooper token."
// (The +1/+0 field-presence buff is in ObjectCurrentPower.)
$onAttackAbilities["TWI_094:0"] = function($player, $mzID) {
    SWUCreateUnitTokens(intval($player), 'TWI_T02', 1);
    // Combat owns the after-action.
};

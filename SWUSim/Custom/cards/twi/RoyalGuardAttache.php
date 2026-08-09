<?php
// TWI_059
// Cost 2 - Royal Guard Attaché - [Vigilance] - Power 2 - HP 5
// Text: When Played: Deal 2 damage to this unit.

// TWI_059 Royal Guard Attaché — "When Played: Deal 2 damage to this unit." (non-optional, no target)
$whenPlayedAbilities["TWI_059:0"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    SWUDealDamageToUnit($mzID, 2, intval($player));
};

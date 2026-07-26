<?php
// TWI_155
// Cost 2 - Twice the Pride - [Aggression,Aggression] - Upgrade Power 4 - Upgrade HP 0
// Text: When Played: Deal 2 damage to attached unit.

// TWI_155 Twice the Pride — "When Played: Deal 2 damage to attached unit." (Upgrade; $mzID = the host.)
$whenPlayedAbilities["TWI_155:0"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    SWUDealDamageToUnit($mzID, 2, intval($player));
};

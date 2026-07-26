<?php
// SEC_240
// Cost 3 - Hutt Cartel Starfighter - [Villainy] - Power 3 - HP 5
// Text: When Played: Deal 2 damage to this unit.

// SEC_240 Hutt Cartel Starfighter — When Played: deal 2 to this unit.
$whenPlayedAbilities["SEC_240:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    SWUDealDamageToUnit($mzID, 2, intval($player));
};

<?php
// TWI_121
// Cost 3 - General's Blade - [Command] - Upgrade Power 3 - Upgrade HP 3
// Text: Attach to a non-Vehicle unit. / If attached unit is a Jedi, it gains: "On Attack: The next unit you play this phase costs 2 resources less."

// TWI_121 General's Blade — "If attached unit is a Jedi, it gains: 'On Attack: The next unit you play
// this phase costs 2 resources less.'" (OnAttackFromUpgrade seam; $mzID = the host. Non-Vehicle attach.)
$onAttackAbilities["TWI_121:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $host = GetZoneObject($mzID);
    if (SWUObjGone($host) || !HasTrait($host->CardID ?? '', 'Jedi')) return;
    AddGlobalEffects(intval($player), 'SWU_TWI121_DISCOUNT_NEXT'); // next unit -2 this phase
};

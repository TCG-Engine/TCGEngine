<?php
// ASH_032
// Cost 2 - Rancor Keeper - [Vigilance,Aggression] - Power 2 - HP 4
// Text: When a friendly unit is dealt damage and survives: Deal 1 damage to any number of bases. Use this ability only once each round.

$customDQHandlers["ASH_032#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (SWUDecisionDeclined($lastDecision)) return;
    foreach (explode('&', $lastDecision) as $mz) {
        if ($mz === 'myBase-0')          SWUDealDamageToBase(1, intval($player));
        elseif ($mz === 'theirBase-0')   SWUDealDamageToBase(1, OtherPlayer(intval($player)));
    }
};

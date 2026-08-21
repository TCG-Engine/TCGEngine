<?php
// ASH_032
// Cost 2 - Rancor Keeper - [Vigilance,Aggression] - Power 2 - HP 4
// Text: When a friendly unit is dealt damage and survives: Deal 1 damage to any number of bases. Use this ability only once each round.

$customDQHandlers["ASH_032#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (SWUDecisionDeclined($lastDecision)) return;
    // ⚠ Decode the owner FROM the mzID. In Twin Suns the answer arrives as "p{n}Base-0", which
    // matched neither literal, so those bases silently took no damage at all.
    foreach (explode('&', $lastDecision) as $mz) {
        if (strpos($mz, 'Base') === false) continue;
        SWUDealDamageToBase(1, SWUMzOwner($mz, intval($player)));
    }
};

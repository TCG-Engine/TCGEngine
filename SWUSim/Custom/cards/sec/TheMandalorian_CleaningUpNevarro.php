<?php
// SEC_209
// Cost 8 - The Mandalorian - Cleaning Up Nevarro - [Cunning,Heroism] - Power 6 - HP 8
// Text: Ambush / When this unit attacks and defeats a unit: You may choose an enemy non-leader unit. This unit captures it.

// SEC_209 The Mandalorian — combat-hit capture continuation.
$customDQHandlers["SEC_209#0"] = function($player, $parts, $lastDecision) {
    if (SWUDecisionDeclined($lastDecision)) return;
    global $playerID; $playerID = intval($player);
    $captor = SWUFindMzByUID(intval($parts[0] ?? 0));
    if ($captor !== null) DoCaptureUnit(intval($player), $captor, $lastDecision);
};

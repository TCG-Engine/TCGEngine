<?php
// ASH_060
// Cost 4 - Cobb Vanth - Let Me Handle This - [Vigilance,Heroism] - Power 2 - HP 6
// Text: Grit / When you play another unit: You may deal 2 damage to this unit. If you do, give a Shield token to that unit.

// ASH_060 Cobb Vanth — continuation: deal 2 to Cobb; if Cobb survived the cost, Shield the played unit.
$customDQHandlers["ASH_060#0"] = function($player, $parts, $lastDecision) {
    if ($lastDecision !== 'YES') return;
    global $playerID; $playerID = intval($player);
    $cobbMz    = SWUFindMzByUID(intval($parts[0] ?? 0));
    $playedUID = intval($parts[1] ?? 0);
    if ($cobbMz === null) return;
    SWUDealDamageToUnit($cobbMz, 2, intval($player));
    // Re-resolve the played unit by UID AFTER the self-damage: if the 2 defeats Cobb, CleanupRemovedCards
    // shifts arena indices, so the mzID captured earlier would be stale. The Shield is still given even when
    // Cobb dies to the cost (the "if you do" gate is paying the damage, not surviving it).
    $playedMz = SWUFindMzByUID($playedUID);
    if ($playedMz !== null) {
        $po = GetZoneObject($playedMz);
        if ($po !== null && empty($po->removed)) DoGiveShieldToken(intval($player), $playedMz);   // "give a Shield to that unit"
    }
};

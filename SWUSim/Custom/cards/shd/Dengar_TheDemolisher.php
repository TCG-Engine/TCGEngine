<?php
// SHD_133
// Cost 1 - Dengar - The Demolisher - [Villainy,Aggression] - Power 2 - HP 2
// Text: When you play an upgrade on a unit: You may deal 1 damage to that unit.

// ─── SHD_133 Dengar (reactive: upgrade played on a unit → may deal 1 to it) ───
$customDQHandlers["SHD_133#0"] = function($player, $parts, $lastDecision) {
    if (($lastDecision ?? '') !== 'YES') return;
    global $playerID; $playerID = intval($player);
    $hostMz = SWUFindMzByUID(intval($parts[0] ?? 0));
    if ($hostMz === null) return;
    $o = GetZoneObject($hostMz);
    if (SWUObjGone($o)) return;
    SWUDealDamageToUnit($hostMz, 1, intval($player));
};

<?php
// SHD_239
// Cost 3 - Toro Calican - Ambitious Upstart - [Villainy] - Power 3 - HP 5
// Text: When you play another Bounty Hunter unit: You may deal 1 damage to it. If you do, ready this unit. Use this ability only once each round.

// ─── SHD_239 Toro Calican (reactive: play BH unit → may deal 1 to it, then ready Toro, once/round) ───
$customDQHandlers["SHD_239#0"] = function($player, $parts, $lastDecision) {
    if (($lastDecision ?? '') !== 'YES') return;
    global $playerID; $playerID = intval($player);
    $playedMz = SWUFindMzByUID(intval($parts[0] ?? 0));
    $toroMz   = SWUFindMzByUID(intval($parts[1] ?? 0));
    if ($playedMz === null) return;
    $po = GetZoneObject($playedMz);
    if (SWUObjGone($po)) return;
    SWUDealDamageToUnit($playedMz, 1, intval($player));   // deal 1 to the just-played Bounty Hunter unit
    if ($toroMz !== null) { $to = GetZoneObject($toroMz); if ($to !== null && empty($to->removed)) $to->Status = 1; }   // ready Toro
    AddGlobalEffects(intval($player), 'SWU_SHD239_USED');   // once each round — consumed on actual use
};

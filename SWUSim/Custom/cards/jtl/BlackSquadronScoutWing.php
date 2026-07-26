<?php
// JTL_202
// Cost 5 - Black Squadron Scout Wing - [Cunning,Heroism] - Power 4 - HP 6
// Text: When you play an upgrade on this unit: You may attack with this unit. It gets +1/+0 for this attack.

// ── JTL_202 Black Squadron Scout Wing — on YES, the host gets +1/+0 and attacks. ──────────────────────
$customDQHandlers["JTL_202#0"] = function($player, $parts, $lastDecision) {
    if ($lastDecision !== 'YES') return;
    global $playerID;
    $playerID = intval($player);
    $mz = SWUFindMzByUID(intval($parts[0] ?? 0));
    if ($mz === null) return;
    $obj = GetZoneObject($mz);
    if (SWUObjGone($obj) || intval($obj->Status) !== 1) return;
    SWUAddAttackPowerBonus($mz, 1);
    BeginSWUAttack(intval($player), $mz);
};

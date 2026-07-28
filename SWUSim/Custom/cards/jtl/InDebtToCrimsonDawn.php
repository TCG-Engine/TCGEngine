<?php
// JTL_192
// Cost 2 - In Debt to Crimson Dawn - [Cunning,Villainy] - Upgrade Power 0 - Upgrade HP 0
// Text: When attached unit readies: Exhaust it unless its controller pays 2 resources.

// ── JTL_192 In Debt to Crimson Dawn — regroup ready tax: pay 2 (YES) to keep ready, else exhaust. ─────
$customDQHandlers["JTL_192#0"] = function($player, $parts, $lastDecision) {
    global $playerID;
    $playerID = intval($player);
    $mz  = $parts[0] ?? '';
    $obj = GetZoneObject($mz);
    if (SWUObjGone($obj)) return;
    if ($lastDecision === 'YES' && SWUResourceCount(intval($player), true) >= 2) {
        SWUPayCost(intval($player), 2, 0, false);   // pay 2 ready resources to keep it ready (effect cost, not halved by JTL_105)
    } else {
        $obj->Status = 0;                    // exhaust it
    }
};

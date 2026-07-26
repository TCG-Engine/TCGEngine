<?php
// ASH_088
// Cost 3 - The Conflict Within - [Vigilance] - Upgrade Power 0 - Upgrade HP 0
// Text: Attached unit gains: "When this unit readies: You may pay 3 resources. If you don't, exhaust this unit."

// ASH_088 The Conflict Within — regroup ready-step continuation: pay 3 to keep ready, else exhaust.
$customDQHandlers["ASH_088#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    $mz  = $parts[0] ?? '';
    $obj = GetZoneObject($mz);
    if (SWUObjGone($obj)) return;
    if ($lastDecision === 'YES' && SWUResourceCount(intval($player), true) >= 3) {
        SWUPayCost(intval($player), 3, 0, false);   // pay 3 ready resources to keep it ready (effect cost, not halved by JTL_105)
    } else {
        $obj->Status = 0;                    // exhaust it
    }
};

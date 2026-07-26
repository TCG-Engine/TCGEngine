<?php
// SEC_264
// Cost 2 - Clandestine Connections - Upgrade Power 1 - Upgrade HP 1
// Text: Attached unit gains: "On Attack: You may pay 2 resources. If you do, deal 2 damage to a base."

// SEC_264 Clandestine Connections (Upgrade) — granted "On Attack: you may pay 2 resources → deal 2 to a
// base." Rides the generic upgrade On Attack seam (fires with the host mzID when the bearer attacks).
$onAttackAbilities["SEC_264:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    if (SWUResourceCount(intval($player), true) < 2) return;   // need 2 ready resources
    DecisionQueueController::AddDecision(intval($player), "YESNO", "-", 1, tooltip: "Pay_2_resources_to_deal_2_to_a_base?");
    DecisionQueueController::AddDecision(intval($player), "CUSTOM", "SEC_264#0", 1);
};

$customDQHandlers["SEC_264#0"] = function($player, $parts, $lastDecision) {
    if ($lastDecision !== 'YES') return;
    global $playerID; $playerID = intval($player);
    if (!SWUExhaustResources(intval($player), 2)) return;
    SWUQueueChooseTarget(intval($player), ['myBase-0', 'theirBase-0'], "Deal_2_to_a_base", "DEAL_BASE_DAMAGE|2");
};

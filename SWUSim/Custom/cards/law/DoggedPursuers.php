<?php
// LAW_198
// Cost 5 - Dogged Pursuers - [Aggression] - Power 5 - HP 5
// Text: When Played: You may pay 1 resource. If you do, deal 2 damage to a ground unit.

// LAW_198 Dogged Pursuers — When Played: you may pay 1 resource. If you do, deal 2 to a ground unit.
$whenPlayedAbilities["LAW_198:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    if (SWUTotalPaymentCapacity(intval($player)) < 1) return;
    DecisionQueueController::AddDecision(intval($player), "YESNO", "-", 1, tooltip: "Pay_1_resource_to_deal_2_to_a_ground_unit?");
    DecisionQueueController::AddDecision(intval($player), "CUSTOM", "LAW_198#0", 1);
};

$customDQHandlers["LAW_198#0"] = function($player, $parts, $lastDecision) {
    if ($lastDecision !== 'YES') return;
    global $playerID; $playerID = intval($player);
    if (!SWUPayInlineAbilityCost(intval($player), 1)) return;
    $ground = SWUAllUnits(null, GroundArena);
    if (empty($ground)) return;
    SWUQueueChooseTarget(intval($player), $ground, "Deal_2_to_a_ground_unit", "DEAL_UNIT_DAMAGE|2");
};

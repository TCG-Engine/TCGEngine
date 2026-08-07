<?php
// LAW_258
// Cost 2 - Criminal Contact - Power 1 - HP 4
// Text: On Attack: You may pay 2 resources. If you do, create a Credit token.

// LAW_258 Criminal Contact — On Attack: you may pay 2 resources. If you do, create a Credit token.
$onAttackAbilities["LAW_258:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    if (SWUTotalPaymentCapacity(intval($player)) < 2) return;
    DecisionQueueController::AddDecision(intval($player), "YESNO", "-", 1, tooltip: "Pay_2_resources_to_create_a_Credit_token?");
    DecisionQueueController::AddDecision(intval($player), "CUSTOM", "LAW_258#0", 1);
};

$customDQHandlers["LAW_258#0"] = function($player, $parts, $lastDecision) {
    if ($lastDecision !== 'YES') return;
    global $playerID; $playerID = intval($player);
    if (!SWUPayInlineAbilityCost(intval($player), 2)) return;
    SWUCreateCreditToken(intval($player), 1);
};

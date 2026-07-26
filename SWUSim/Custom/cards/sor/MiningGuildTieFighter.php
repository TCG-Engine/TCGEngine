<?php
// SOR_206
// Cost 1 - Mining Guild TIE Fighter - [Cunning] - Power 1 - HP 2
// Text: On Attack: You may pay [2 resources]. If you do, draw a card.

// SOR_206 Mining Guild TIE Fighter — On Attack: You may pay 2 resources. If you do, draw a card.
$onAttackAbilities["SOR_206:0"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    if (SWUResourceCount(intval($player), true) < 2) return; // can't pay → not offered
    DecisionQueueController::AddDecision($player, 'YESNO', '-', 1, 'Pay_2_resources_to_draw_a_card?');
    DecisionQueueController::AddDecision($player, 'CUSTOM', 'SOR_206#0', 1);
};

$customDQHandlers["SOR_206#0"] = function($player, $parts, $lastDecision) {
    if ($lastDecision !== 'YES' && $lastDecision !== '1') return;
    global $playerID;
    $playerID = intval($player);
    if (!SWUExhaustResources($player, 2)) return; // pay the cost; fizzle if somehow unaffordable
    DoDrawCard(intval($player), 1);
};

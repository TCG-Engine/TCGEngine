<?php
// LOF_184
// Cost 4 - Second Sister - Seeking the Holocron - [Cunning,Villainy] - Power 3 - HP 6
// Text: On Attack: You may discard 2 cards from your deck. For each Force card discarded this way, ready a resource.

// LOF_184 Second Sister — On Attack: may discard 2 from your deck. For each Force card discarded, ready a resource.
$onAttackAbilities["LOF_184:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    DecisionQueueController::AddDecision($player, "YESNO", "-", 1, tooltip: "Discard_2_from_your_deck_(ready_a_resource_per_Force_card)?");
    DecisionQueueController::AddDecision($player, "CUSTOM", "LOF_184#0", 1);
};

$customDQHandlers["LOF_184#0"] = function($player, $parts, $lastDecision) {
    if ($lastDecision !== 'YES') return;
    global $playerID; $playerID = intval($player);
    $forceCount = 0;
    for ($i = 0; $i < 2; $i++) {
        $cid = SWUMillTopCard(intval($player));
        if ($cid !== null && HasTrait($cid, 'Force')) $forceCount++;
    }
    if ($forceCount > 0) SWUReadyResources(intval($player), $forceCount);
};

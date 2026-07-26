<?php
// SOR_145
// Cost 4 - K-2SO - Cassian's Counterpart - [Aggression,Heroism] - Power 4 - HP 4
// Text: Overwhelm / When Defeated: For each opponent, choose one: either deal 3 damage to that player's base, or that player discards a card from their hand.

// SOR_145 K-2SO — "When Defeated: For each opponent, choose one: either deal 3 damage to that player's
// base, or that player discards a card from their hand." 2-player → one opponent; K-2SO's controller
// ($player) chooses via OPTIONCHOOSE. (Iterate opponents for Twin Suns later.)
$whenDefeatedAbilities["SOR_145:0"] = function($player, $mzID) {
    // Twin Suns: "for each opponent, choose one" — a sequential OPTIONCHOOSE per opponent (K-2SO's
    // controller chooses each). 2-player → one opponent → one prompt, byte-identical.
    $multi = SeatCountForGame() > 2;
    foreach (OpponentsOf(intval($player)) as $opp) {
        DecisionQueueController::AddDecision(intval($player), "OPTIONCHOOSE", "Base&Discard", 1,
            tooltip: $multi ? "P{$opp}:_deal_3_to_their_base_or_make_them_discard?"
                            : "Deal_3_to_their_base_or_make_them_discard?");
        DecisionQueueController::AddDecision(intval($player), "CUSTOM", "SOR_145#0|{$opp}", 1);
    }
};

$customDQHandlers["SOR_145#0"] = function($player, $parts, $lastDecision) {
    global $playerID;
    $controller = intval($player);
    $opp = intval($parts[0] ?? OtherPlayer($controller));
    $playerID = $controller;
    if ($lastDecision === "Discard") {
        SWUDiscardCards($controller, 1, $opp);   // that specific opponent discards 1
    } else {
        SWUDealDamageToBase(3, $opp);
    }
};

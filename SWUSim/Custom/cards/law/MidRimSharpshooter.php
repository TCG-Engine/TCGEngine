<?php
// LAW_193
// Cost 3 - Mid Rim Sharpshooter - [Aggression] - Power 3 - HP 3
// Text: Saboteur (When this unit attacks, ignore Sentinel and defeat the defender's Shields.) / When Played: You may pay 1 resource. If you do, an opponent discards a card from their hand.

// LAW_193 Mid Rim Sharpshooter — Saboteur + When Played: you may pay 1 resource. If you do, an opponent
// discards a card from their hand.
$whenPlayedAbilities["LAW_193:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    if (SWUResourceCount(intval($player), readyOnly: true) < 1) return;
    DecisionQueueController::AddDecision(intval($player), "YESNO", "-", 1, tooltip: "Pay_1_resource_to_make_an_opponent_discard_a_card?");
    DecisionQueueController::AddDecision(intval($player), "CUSTOM", "LAW_193#0", 1);
};

$customDQHandlers["LAW_193#0"] = function($player, $parts, $lastDecision) {
    if ($lastDecision !== 'YES') return;
    global $playerID; $playerID = intval($player);
    if (!SWUExhaustResources(intval($player), 1)) return;
    SWUDiscardCards(intval($player), 1);   // makes the opponent discard
};

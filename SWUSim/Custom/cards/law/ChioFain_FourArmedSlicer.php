<?php
// LAW_048
// Cost 2 - Chio Fain - Four-Armed Slicer - [Vigilance,Aggression] - Power 2 - HP 4
// Text: On Attack: You may choose 2 players. If you do, they each draw a card.

// LAW_048 Chio Fain — On Attack: you may choose 2 players. If you do, they each draw a card. (2-player:
// both players.)
$onAttackAbilities["LAW_048:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    DecisionQueueController::AddDecision(intval($player), "YESNO", "-", 1, tooltip: "Both_players_draw_a_card?");
    DecisionQueueController::AddDecision(intval($player), "CUSTOM", "LAW_048#0", 1);
};

$customDQHandlers["LAW_048#0"] = function($player, $parts, $lastDecision) {
    if ($lastDecision !== 'YES') return;
    global $playerID; $playerID = intval($player);
    DoDrawCard(intval($player), 1);
    DoDrawCard(OtherPlayer(intval($player)), 1);
};

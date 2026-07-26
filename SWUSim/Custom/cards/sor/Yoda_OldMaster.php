<?php
// SOR_045
// Cost 3 - Yoda - Old Master - [Vigilance,Heroism] - Power 2 - HP 4
// Text: Restore 2 (When this unit attacks, heal 2 damage from your base.) / When Defeated: Choose any number of players. They each draw a card.

// SOR_045 Yoda — When Defeated: "Choose any number of players. They each draw a card." 2-player: a
// 3-way choice (You / Opponent / Both). (Twin Suns multiplayer will use per-player checkboxes later.)
$whenDefeatedAbilities["SOR_045:0"] = function($player, $mzID) {
    if (SeatCountForGame() <= 2) {
        DecisionQueueController::AddDecision(intval($player), "OPTIONCHOOSE", "You&Opponent&Both", 1, "Choose_who_draws_a_card");
        DecisionQueueController::AddDecision(intval($player), "CUSTOM", "YODA_DRAW", 1);
        return;
    }
    // Twin Suns: "any number of players" → a YES/NO per player (caster + each opponent), in seat order.
    foreach (array_merge([intval($player)], OpponentsOf(intval($player))) as $seat) {
        DecisionQueueController::AddDecision(intval($player), "YESNO", "-", 1,
            tooltip: ($seat === intval($player) ? "You_draw_a_card?" : "P{$seat}_draws_a_card?"));
        DecisionQueueController::AddDecision(intval($player), "CUSTOM", "YODA_DRAW_ONE|{$seat}", 1);
    }
};

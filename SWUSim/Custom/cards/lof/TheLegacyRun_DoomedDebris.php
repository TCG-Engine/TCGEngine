<?php
// LOF_213
// Cost 5 - The Legacy Run - Doomed Debris - [Cunning] - Power 3 - HP 3
// Text: When Defeated: Deal 6 damage divided as you choose among enemy units.

// LOF_213 The Legacy Run — When Defeated: deal 6 damage divided as you choose among enemy units.
$whenDefeatedAbilities["LOF_213:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $enemies = SWUAllUnits('their');
    if (empty($enemies)) return;
    DecisionQueueController::AddDecision($player, "MZSPLITASSIGN", "6|" . implode('&', $enemies), 1,
        tooltip: "Deal_6_damage_divided_among_enemy_units");
    DecisionQueueController::AddDecision($player, "CUSTOM", "SPLIT_DAMAGE", 1);
};

<?php
// SEC_145
// Cost 10 - Confidence in Victory - [Aggression,Villainy]
// Text: Play only as your first action in the action phase. / Choose an arena. At the start of the regroup phase, if you are the only player who controls units in that arena, you win the game.

// SEC_145 Confidence in Victory — store the chosen arena for the regroup-phase win check.
$customDQHandlers["SEC_145#0"] = function($player, $parts, $lastDecision) {
    $arena = ($lastDecision === 'Space') ? 'Space' : 'Ground';
    AddGlobalEffects(intval($player), "SWU_CONFIDENCE|" . intval($player) . "|" . $arena);
};

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["SEC_145:0"] = function($player, $mzID = '') {
// Confidence in Victory — Choose an arena. At the start of the regroup phase, if
                          // you are the only player who controls units in that arena, you win the game.
                          // (The "first action only" restriction is enforced in SWUCardPlayBlocked.)
            global $playerID; $playerID = intval($player);
            DecisionQueueController::AddDecision(intval($player), "OPTIONCHOOSE", "Ground&Space", 1, tooltip:"Choose_an_arena");
            DecisionQueueController::AddDecision(intval($player), "CUSTOM", "SEC_145#0", 1);
            return;
};

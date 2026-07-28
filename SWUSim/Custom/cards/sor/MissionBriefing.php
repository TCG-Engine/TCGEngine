<?php
// SOR_171
// Cost 3 - Mission Briefing - [Aggression]
// Text: Choose a player. They draw 2 cards.

// SOR_171 Mission Briefing (event) — draw 2 for the chosen player (OPTIONCHOOSE You/Opponent).
$customDQHandlers["SOR_171#0"] = function($player, $parts, $lastDecision) {
    global $playerID;
    $playerID = intval($player);
    $target = SWUDecodePlayerPick($lastDecision, intval($player)); // "You"→caster, "Opponent"/"P{n}"→that player
    DoDrawCard($target, 2);
};

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["SOR_171:0"] = function($player, $mzID = '') {
// Mission Briefing — "Choose a player. They draw 2 cards."
            global $playerID;
            $playerID = intval($player);
            DecisionQueueController::AddDecision($player, "OPTIONCHOOSE", SWUPlayerPickerLabels(intval($player)), 1, "Which_player_draws_2_cards?");
            DecisionQueueController::AddDecision($player, "CUSTOM", "SOR_171#0", 1);
            return;
};

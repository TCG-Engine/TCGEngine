<?php
// HMW_194 Run Amok — Event, cost 3, [Aggression], Disaster.
// "Create a Beast token. Deal 1 damage to a friendly ground unit and 1 damage to an enemy ground unit."
// Mandatory. The new Beast is itself a friendly ground unit, so the first damage always has a target.
// The two damages are independent: no enemy ground unit only skips the second. "friendly" spans the team.

$whenPlayedAbilities["HMW_194:0"] = function($player, $mzID = '') {
    global $playerID; $playerID = intval($player);
    SWUCreateUnitToken(intval($player), 'HMW_T03');
    DecisionQueueController::AddDecision(intval($player), "CUSTOM", "HMW_194#0", 1);
};

$customDQHandlers["HMW_194#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    SWUOfferUnitTarget(intval($player), '', [
        'continuation' => 'DEAL_UNIT_DAMAGE', 'amount' => 1, 'side' => 'friendly', 'arena' => 'Ground',
        'prompt' => 'Deal_1_damage_to_a_friendly_ground_unit',
    ]);
    DecisionQueueController::AddDecision(intval($player), "CUSTOM", "HMW_194#1", 1);
};

$customDQHandlers["HMW_194#1"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    SWUOfferUnitTarget(intval($player), '', [
        'continuation' => 'DEAL_UNIT_DAMAGE', 'amount' => 1, 'side' => 'their', 'arena' => 'Ground',
        'prompt' => 'Deal_1_damage_to_an_enemy_ground_unit',
    ]);
};

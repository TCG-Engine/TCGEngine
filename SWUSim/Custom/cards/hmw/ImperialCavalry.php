<?php
// HMW_250 Imperial Cavalry — Unit (Ground) 4/4, cost 6, [Villainy], Imperial/Trooper.
// "When Played: Create a Beast token and deal 1 damage to an enemy unit."
// Mandatory; the damage is independent of the token (no enemy unit only skips it).

$whenPlayedAbilities["HMW_250:0"] = function($player, $mzID = '') {
    global $playerID; $playerID = intval($player);
    SWUCreateUnitToken(intval($player), 'HMW_T03');
    DecisionQueueController::AddDecision(intval($player), "CUSTOM", "HMW_250#0", 1);
};

$customDQHandlers["HMW_250#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    SWUOfferUnitTarget(intval($player), '', [
        'continuation' => 'DEAL_UNIT_DAMAGE', 'amount' => 1, 'side' => 'their',
        'prompt' => 'Deal_1_damage_to_an_enemy_unit',
    ]);
};

<?php
// HMW_186 Mining Guild Trespasser — Unit (Space) 5/5, cost 6, [Aggression], Vehicle/Transport.
// "When Played: You may deal 2 damage to a base and 2 damage to an enemy unit."
// One "may" covers both halves. The halves are independent ("and", not "if you do"): with no enemy unit the
// base damage still happens. "a base" is any base, your own included.

$whenPlayedAbilities["HMW_186:0"] = function($player, $mzID = '') {
    global $playerID; $playerID = intval($player);
    DecisionQueueController::AddDecision(intval($player), "YESNO", "-", 1, tooltip: "Deal_2_damage_to_a_base_and_2_damage_to_an_enemy_unit?");
    DecisionQueueController::AddDecision(intval($player), "CUSTOM", "HMW_186#0", 1);
};

$customDQHandlers["HMW_186#0"] = function($player, $parts, $lastDecision) {
    if ($lastDecision !== 'YES') return;
    global $playerID; $playerID = intval($player);
    SWUOfferBaseTarget(intval($player), ['continuation' => 'DEAL_BASE_DAMAGE', 'amount' => 2, 'prompt' => 'Deal_2_damage_to_a_base']);
    DecisionQueueController::AddDecision(intval($player), "CUSTOM", "HMW_186#1", 1);
};

$customDQHandlers["HMW_186#1"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    SWUOfferUnitTarget(intval($player), '', [
        'continuation' => 'DEAL_UNIT_DAMAGE', 'amount' => 2, 'side' => 'their',
        'prompt' => 'Deal_2_damage_to_an_enemy_unit',
    ]);
};

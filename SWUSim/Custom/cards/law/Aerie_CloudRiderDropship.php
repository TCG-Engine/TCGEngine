<?php
// LAW_184
// Cost 6 - Aerie - Cloud-Rider Dropship - [Aggression,Heroism] - Power 3 - HP 7
// Text: On Attack: Deal 2 damage to an enemy ground unit and 2 damage to a base.

// LAW_184 Aerie — On Attack: deal 2 damage to an enemy ground unit and 2 damage to a base.
$onAttackAbilities["LAW_184:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    SWUDealDamageToBase(2, OtherPlayer(intval($player)));   // 2 to the enemy base (direct)
    SWUOfferUnitTarget(intval($player), $mzID, [
        'continuation' => 'DEAL_UNIT_DAMAGE', 'amount' => 2, 'side' => 'their', 'arena' => 'Ground', 'may' => true,
        'question' => "Deal_2_to_an_enemy_ground_unit?", 'prompt' => "Choose_an_enemy_ground_unit",
    ]);
};

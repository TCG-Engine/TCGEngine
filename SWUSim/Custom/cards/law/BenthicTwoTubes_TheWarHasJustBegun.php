<?php
// LAW_057
// Cost 2 - Benthic "Two Tubes" - The War Has Just Begun - [Command,Aggression] - Power 3 - HP 2
// Text: On Attack: Deal 1 damage to an enemy ground unit. / When Defeated: Deal 1 damage to a base.

// LAW_057 Benthic "Two Tubes" — On Attack: deal 1 to an enemy ground unit. When Defeated: deal 1 to a base.
$onAttackAbilities["LAW_057:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    SWUOfferUnitTarget(intval($player), $mzID, [
        'continuation' => 'DEAL_UNIT_DAMAGE', 'amount' => 1, 'side' => 'their', 'arena' => 'Ground', 'may' => true,
        'question' => "Deal_1_to_an_enemy_ground_unit?", 'prompt' => "Deal_1_damage_to_an_enemy_ground_unit",
    ]);
};

$whenDefeatedAbilities["LAW_057:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    SWUOfferBaseTarget(intval($player), ['continuation'=>'DEAL_BASE_DAMAGE','amount'=>1,'prompt'=>"Deal_1_to_a_base"]);
};

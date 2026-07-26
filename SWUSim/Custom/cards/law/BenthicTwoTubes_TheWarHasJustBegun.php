<?php
// LAW_057
// Cost 2 - Benthic "Two Tubes" - The War Has Just Begun - [Command,Aggression] - Power 3 - HP 2
// Text: On Attack: Deal 1 damage to an enemy ground unit. / When Defeated: Deal 1 damage to a base.

// LAW_057 Benthic "Two Tubes" — On Attack: deal 1 to an enemy ground unit. When Defeated: deal 1 to a base.
$onAttackAbilities["LAW_057:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $enemy = ZoneSearch("theirGroundArena", AnyUnitFilter);
    if (empty($enemy)) return;
    SWUQueueMayChooseTarget(intval($player), $enemy, "Deal_1_to_an_enemy_ground_unit?", "Choose_an_enemy_ground_unit", "DEAL_UNIT_DAMAGE|1");
};

$whenDefeatedAbilities["LAW_057:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    SWUQueueChooseTarget(intval($player), ['myBase-0', 'theirBase-0'], "Deal_1_to_a_base", "DEAL_BASE_DAMAGE|1");
};

<?php
// LOF_145
// Cost 3 - Jedi Knight - [Aggression,Heroism] - Power 3 - HP 3
// Text: When Played: If you have the initiative, deal 2 damage to an enemy ground unit.

// LOF_145 Jedi Knight — When Played: if you have the initiative, deal 2 damage to an enemy ground unit.
$whenPlayedAbilities["LOF_145:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    if (!PlayerHasIniative(intval($player))) return;
    $targets = ZoneSearch("theirGroundArena", AnyUnitFilter);
    if (empty($targets)) return;
    SWUQueueChooseTarget(intval($player), $targets, "Deal_2_to_an_enemy_ground_unit", "DEAL_UNIT_DAMAGE|2");
};

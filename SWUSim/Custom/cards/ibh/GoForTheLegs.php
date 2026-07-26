<?php
// IBH_018 / IBH_045
// Cost 1 - Go for the Legs - [Cunning]
// Text: Exhaust an enemy ground unit.

$whenPlayedAbilities["IBH_018:0"] = function($player, $mzID = '') {
    global $playerID; $playerID = intval($player);
    $targets = ZoneSearch("theirGroundArena", AnyUnitFilter);
    if (empty($targets)) return;
    SWUQueueChooseTarget(intval($player), $targets, "Exhaust_an_enemy_ground_unit", "EXHAUST_UNIT");
};
$whenPlayedAbilities["IBH_045:0"] = $whenPlayedAbilities["IBH_018:0"];

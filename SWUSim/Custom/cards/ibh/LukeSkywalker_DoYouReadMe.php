<?php
// IBH_020
// Cost 7 - Luke Skywalker - Do You Read Me? - [Command,Heroism] - Power 6 - HP 6
// Text: Restore 2 (When this unit attacks, heal 2 damage from your base.) / When Played: You may deal 3 damage to a ground unit.

// IBH_020 Luke Skywalker — Restore 2 (keyword) + When Played: you may deal 3 damage to a ground unit.
$whenPlayedAbilities["IBH_020:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $targets = SWUAllUnits(null, GroundArena);
    if (empty($targets)) return;
    SWUQueueMayChooseTarget(intval($player), $targets, "Deal_3_to_a_ground_unit?", "Choose_a_ground_unit", "DEAL_UNIT_DAMAGE|3");
};

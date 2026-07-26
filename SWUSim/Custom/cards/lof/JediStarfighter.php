<?php
// LOF_144
// Cost 2 - Jedi Starfighter - [Aggression,Heroism] - Power 1 - HP 4
// Text: On Attack: You may deal 1 damage to a space unit.

// LOF_144 Jedi Starfighter — On Attack: may deal 1 damage to a space unit.
$onAttackAbilities["LOF_144:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $targets = SWUAllUnits(null, SpaceArena);
    if (empty($targets)) return;
    SWUQueueMayChooseTarget(intval($player), $targets, "Deal_1_to_a_space_unit?", "Choose_a_space_unit", "DEAL_UNIT_DAMAGE|1");
};

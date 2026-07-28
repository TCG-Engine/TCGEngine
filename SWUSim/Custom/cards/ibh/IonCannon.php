<?php
// IBH_016
// Cost 4 - Ion Cannon - [Cunning] - Power 0 - HP 5
// Text: Action [Exhaust]: Deal 3 damage to a space unit.

// IBH_016 / IBH_027 Ion Cannon — Action [Exhaust]: deal 3 damage to a space unit.
$unitAbilities["IBH_016"] =
$unitAbilities["IBH_027"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $targets = SWUAllUnits(null, SpaceArena);
    if (empty($targets)) { SWUAfterAction($player); return; }
    SWUQueueChooseTarget(intval($player), $targets, "Deal_3_to_a_space_unit", "DEAL_UNIT_DAMAGE|3");
    SWUQueueAfterAction($player);
};

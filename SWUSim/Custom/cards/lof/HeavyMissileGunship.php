<?php
// LOF_134
// Cost 4 - Heavy Missile Gunship - [Aggression,Villainy] - Power 4 - HP 3
// Text: Action [Exhaust]: Deal 2 damage to a ground unit.

// LOF_134 Heavy Missile Gunship — Action [Exhaust]: deal 2 damage to a ground unit.
$unitAbilities["LOF_134"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $targets = SWUAllUnits(null, GroundArena);
    if (empty($targets)) { SWUAfterAction($player); return; }
    SWUQueueChooseTarget(intval($player), $targets, "Deal_2_to_a_ground_unit", "DEAL_UNIT_DAMAGE|2");
    SWUQueueAfterAction($player);
};

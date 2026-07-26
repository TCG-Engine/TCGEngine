<?php
// LOF_259
// Cost 5 - Ravening Gundark - Power 5 - HP 4
// Text: When Played: Deal 1 damage to a ground unit.

// LOF_259 Ravening Gundark — When Played: deal 1 damage to a ground unit.
$whenPlayedAbilities["LOF_259:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $targets = SWUAllUnits(null, GroundArena);
    if (empty($targets)) return;
    SWUQueueChooseTarget(intval($player), $targets, "Deal_1_to_a_ground_unit", "DEAL_UNIT_DAMAGE|1");
};

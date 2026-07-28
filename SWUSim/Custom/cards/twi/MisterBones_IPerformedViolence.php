<?php
// TWI_154
// Cost 1 - Mister Bones - I Performed Violence - [Aggression,Aggression] - Power 3 - HP 1
// Text: On Attack: If you have no cards in your hand, you may deal 3 damage to a ground unit.

// TWI_154 Mister Bones — "On Attack: If you have no cards in your hand, you may deal 3 damage to a ground unit."
$onAttackAbilities["TWI_154:0"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    if (count(ZoneSearch("myHand")) > 0) return; // must have an empty hand
    $targets = SWUAllUnits(null, GroundArena);
    if (empty($targets)) return;
    SWUQueueMayChooseTarget(intval($player), $targets,
        "You_may_deal_3_damage_to_a_ground_unit", "Deal_3_damage_to_a_ground_unit", "DEAL_UNIT_DAMAGE|3");
};

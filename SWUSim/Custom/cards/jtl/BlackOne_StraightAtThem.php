<?php
// JTL_147
// Cost 2 - Black One - Straight At Them - [Aggression,Heroism] - Power 2 - HP 3
// Text: While this unit is upgraded, it gets +1/+0. / On Attack: If you control Poe Dameron (as a unit, upgrade, or leader), you may deal 1 damage to a unit.

// JTL_147 Black One — On Attack: If you control Poe Dameron (unit, upgrade, or leader), may deal 1 to a unit.
$onAttackAbilities["JTL_147:0"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    if (!_SWUControlsTitle(intval($player), ['Poe Dameron'])) return;
    SWUOfferUnitTarget($player, $mzID, [
        'continuation' => 'DEAL_UNIT_DAMAGE', 'amount' => 1, 'side' => 'any', 'may' => true,
        'question' => "Deal_1_damage_to_a_unit", 'prompt' => "Deal_1_damage_to_a_unit",
    ]);
};

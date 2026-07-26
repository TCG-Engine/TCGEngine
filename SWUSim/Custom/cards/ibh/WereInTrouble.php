<?php
// IBH_061 / IBH_086
// Cost 3 - We're In Trouble - [Aggression]
// Text: Deal 3 damage to a unit.

$whenPlayedAbilities["IBH_061:0"] = function($player, $mzID = '') {
    global $playerID; $playerID = intval($player);
    $targets = SWUAllUnits();
    if (empty($targets)) return;
    SWUQueueChooseTarget(intval($player), $targets, "Deal_3_damage_to_a_unit", "DEAL_UNIT_DAMAGE|3");
};
$whenPlayedAbilities["IBH_086:0"] = $whenPlayedAbilities["IBH_061:0"];

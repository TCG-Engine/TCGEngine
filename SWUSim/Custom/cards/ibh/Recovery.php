<?php
// IBH_013
// Cost 3 - Recovery - [Heroism]
// Text: Heal 5 damage from a unit.

$whenPlayedAbilities["IBH_013:0"] = function($player, $mzID = '') {
    global $playerID; $playerID = intval($player);
    $targets = SWUAllUnits();
    if (empty($targets)) return;
    SWUQueueChooseTarget(intval($player), $targets, "Heal_5_damage_from_a_unit", "HEAL_TARGET|5");
};

<?php
// IBH_066 / IBH_091
// Cost 1 - Too Strong for Blasters - [Vigilance]
// Text: Heal 2 damage from a unit.

$whenPlayedAbilities["IBH_066:0"] = function($player, $mzID = '') {
    global $playerID; $playerID = intval($player);
    $targets = SWUAllUnits();
    if (empty($targets)) return;
    SWUQueueChooseTarget(intval($player), $targets, "Heal_2_damage_from_a_unit", "HEAL_TARGET|2");
};
$whenPlayedAbilities["IBH_091:0"] = $whenPlayedAbilities["IBH_066:0"];

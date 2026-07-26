<?php
// LAW_097
// Cost 1 - Imperial Door Technician - [Vigilance,Villainy] - Power 2 - HP 2
// Text: When Defeated: Heal 2 damage from your base.

// LAW_097 Imperial Door Technician — When Defeated: heal 2 from your base.
$whenDefeatedAbilities["LAW_097:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    OnHealBase(intval($player), intval($player), 2);
};

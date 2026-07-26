<?php
// IBH_015
// Cost 2 - Tauntaun Mount - [Command] - Power 2 - HP 2
// Text: When Defeated: Heal 2 damage from your base.

// IBH_015 / IBH_028 / IBH_051 Tauntaun Mount — When Defeated: heal 2 damage from your base.
$whenDefeatedAbilities["IBH_015:0"] =
$whenDefeatedAbilities["IBH_028:0"] =
$whenDefeatedAbilities["IBH_051:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    OnHealBase(intval($player), intval($player), 2);
};

<?php
// LOF_147
// Cost 5 - Kit Fisto's Aethersprite - Good Hunting - [Aggression,Heroism] - Power 4 - HP 5
// Text: Saboteur / When Played: You may defeat any number of upgrades on a unit.

// LOF_147 Kit Fisto's Aethersprite — Saboteur + When Played: may defeat any number of upgrades on a unit.
$whenPlayedAbilities["LOF_147:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    SWUQueueDefeatUpgrade(intval($player), "Defeat_any_number_of_upgrades_on_a_unit", may: true, max: 99, filter: '', min: 0);
};

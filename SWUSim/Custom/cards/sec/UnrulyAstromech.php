<?php
// SEC_221
// Cost 3 - Unruly Astromech - [Cunning] - Power 3 - HP 2
// Text: Hidden (This unit can't be attacked if it was played this phase.) / When Defeated: Exhaust an enemy unit.

// SEC_221 Unruly Astromech — Hidden + When Defeated: exhaust an enemy unit.
$whenDefeatedAbilities["SEC_221:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $enemies = array_values(SWUAllUnits('their'));
    if (empty($enemies)) return;
    SWUQueueChooseTarget(intval($player), $enemies, "Exhaust_an_enemy_unit", "EXHAUST_UNIT");
};

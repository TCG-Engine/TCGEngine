<?php
// SOR_108
// Cost 1 - Vanguard Infantry - [Command] - Power 1 - HP 2
// Text: When Defeated: You may give an Experience token to a unit.

// SOR_108 Vanguard Infantry — When Defeated: you may give an Experience token to a unit.
$whenDefeatedAbilities["SOR_108:0"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    $targets = SWUAllUnits();
    SWUQueueMayChooseTarget(intval($player), $targets,
        "Give_an_Experience_token_to_a_unit?", "Give_an_Experience_token_to_a_unit", "GIVE_EXPERIENCE|1");
};

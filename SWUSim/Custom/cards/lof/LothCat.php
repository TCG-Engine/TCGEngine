<?php
// LOF_207
// Cost 2 - Loth-Cat - [Cunning] - Power 2 - HP 1
// Text: When Played/When Defeated: You may exhaust a ground unit.

// LOF_207 Loth-Cat — When Played/When Defeated: may exhaust a ground unit.
$whenPlayedAbilities["LOF_207:0"] =
$whenDefeatedAbilities["LOF_207:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $targets = SWUAllUnits(null, GroundArena);
    if (empty($targets)) return;
    SWUQueueMayChooseTarget(intval($player), $targets, "Exhaust_a_ground_unit?", "Choose_a_ground_unit", "EXHAUST_UNIT");
};
